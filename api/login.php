<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once 'db.php';
require_once 'vendor/autoload.php';

use \Firebase\JWT\JWT;

$database = new Database();
$db = $database->connect();

$data = json_decode(file_get_contents("php://input"));

$email = $data->email;
$password = $data->password;

$query = "SELECT id, nombre, email, password FROM usuarios WHERE email = ? LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (password_verify($password, $row['password'])) {
        $secret_key = "TU_CLAVE_SECRETA_SUPER_SEGURA_123";
        $issuer_claim = "localhost"; // El emisor del token
        $audience_claim = "localhost"; // La audiencia del token
        $issuedat_claim = time(); // Hora de emisión
        $notbefore_claim = $issuedat_claim; // Token válido desde ahora
        $expire_claim = $issuedat_claim + 3600; // Expira en 1 hora

        $token = array(
            "iss" => $issuer_claim,
            "aud" => $audience_claim,
            "iat" => $issuedat_claim,
            "nbf" => $notbefore_claim,
            "exp" => $expire_claim,
            "data" => array(
                "id" => $row['id'],
                "nombre" => $row['nombre'],
                "email" => $row['email']
            )
        );

        $jwt = JWT::encode($token, $secret_key, 'HS256');
        http_response_code(200);
        echo json_encode(array("success" => true, "message" => "Login exitoso.", "token" => $jwt));
    } else {
        http_response_code(401);
        echo json_encode(array("success" => false, "message" => "Contraseña incorrecta."));
    }
} else {
    http_response_code(404);
    echo json_encode(array("success" => false, "message" => "Usuario no encontrado."));
}
?>