<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once 'db.php';

$data = json_decode(file_get_contents("php://input"));
$email = $data->email ?? '';

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El correo electrónico es requerido.']);
    exit;
}

$database = new Database();
$db = $database->connect();

$stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'No se encontró un usuario con ese correo electrónico.']);
    exit;
}

$token = bin2hex(random_bytes(50));
$expires = new DateTime('NOW');
$expires->add(new DateInterval('PT1H')); // 1 hora de validez
$expires_str = $expires->format('Y-m-d H:i:s');

$stmt = $db->prepare("UPDATE usuarios SET reset_token = ?, reset_token_expires_at = ? WHERE email = ?");
$stmt->bind_param("sss", $token, $expires_str, $email);

if ($stmt->execute()) {
    // SIMULACIÓN DE ENVÍO DE CORREO
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $project_folder = dirname(dirname($_SERVER['REQUEST_URI']));
    $reset_link = $base_url . $project_folder . "/reset-password.html?token=" . $token;

    echo json_encode([
        'success' => true, 
        'message' => 'Se ha generado un enlace para restablecer tu contraseña.',
        'reset_link' => $reset_link // Se devuelve para la simulación
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo generar el enlace de restablecimiento.']);
}

$stmt->close();
$db->close();
?>