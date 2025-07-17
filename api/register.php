<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once 'db.php';

$database = new Database();
$db = $database->connect();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->nombre) && !empty($data->email) && !empty($data->password)) {
    // Validar email
    if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Formato de email inválido."));
        exit();
    }

    // Comprobar si el email ya existe
    $check_query = "SELECT id FROM usuarios WHERE email = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bind_param("s", $data->email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        http_response_code(409); // Conflict
        echo json_encode(array("success" => false, "message" => "El correo electrónico ya está registrado."));
        $check_stmt->close();
        exit();
    }
    $check_stmt->close();

    // Crear usuario
    $query = "INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);

    $password_hash = password_hash($data->password, PASSWORD_BCRYPT);

    $stmt->bind_param("sss", $data->nombre, $data->email, $password_hash);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(array("success" => true, "message" => "Usuario creado exitosamente. Ahora puedes iniciar sesión."));
        // Opcional: Iniciar sesión automáticamente y devolver un token JWT aquí.
    } else {
        http_response_code(503);
        echo json_encode(array("success" => false, "message" => "No se pudo crear el usuario."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Datos incompletos."));
}
?>