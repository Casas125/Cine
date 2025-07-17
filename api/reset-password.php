<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once 'db.php';

$data = json_decode(file_get_contents("php://input"));
$token = $data->token ?? '';
$password = $data->password ?? '';

if (empty($token) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos.']);
    exit;
}

$database = new Database();
$db = $database->connect();

$stmt = $db->prepare("SELECT id, reset_token_expires_at FROM usuarios WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El token no es válido.']);
    exit;
}

$user = $result->fetch_assoc();
$now = new DateTime();
$expires = new DateTime($user['reset_token_expires_at']);

if ($now > $expires) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El token ha expirado.']);
    exit;
}

$password_hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $db->prepare("UPDATE usuarios SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
$stmt->bind_param("si", $password_hash, $user['id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => '¡Contraseña actualizada con éxito! Ya puedes iniciar sesión.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la contraseña.']);
}

$stmt->close();
$db->close();
?>
