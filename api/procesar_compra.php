
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once 'db.php';
require_once 'vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

function get_user_id_from_token() {
    $secret_key = "TU_CLAVE_SECRETA_SUPER_SEGURA_123";
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    if (!$authHeader) return null;
    list($jwt) = sscanf($authHeader, 'Bearer %s');
    if (!$jwt) return null;
    try {
        $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
        return $decoded->data->id;
    } catch (Exception $e) {
        return null;
    }
}

$database = new Database();
$db = $database->connect();

$user_id = get_user_id_from_token();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$funcion_id = $data['funcion_id'];
$asientos = $data['asientos'];

if (empty($asientos)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se seleccionaron asientos.']);
    exit;
}

$db->begin_transaction();
try {
    $placeholders = implode(',', array_fill(0, count($asientos), '?'));
    $check_stmt = $db->prepare("SELECT asiento FROM boletos WHERE funcion_id = ? AND asiento IN ($placeholders)");
    $types = 'i' . str_repeat('s', count($asientos));
    $check_stmt->bind_param($types, $funcion_id, ...$asientos);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    if ($result->num_rows > 0) {
        throw new Exception("Lo sentimos, uno o más asientos seleccionados acaban de ser ocupados.");
    }

    $qr_data_combined = "CineMax Boletos - Usuario: $user_id - Función: $funcion_id - Asientos: " . implode(', ', $asientos);

    $stmt = $db->prepare("INSERT INTO boletos (funcion_id, usuario_id, asiento) VALUES (?, ?, ?)");
    
    foreach ($asientos as $asiento) {
        $stmt->bind_param("iis", $funcion_id, $user_id, $asiento);
        $stmt->execute();
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Compra realizada con éxito.', 'qr_data' => $qr_data_combined]);

} catch (Exception $e) {
    $db->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$db->close();
?>