
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once 'db.php';

$database = new Database();
$db = $database->connect();

$pelicula_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$pelicula_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de película no válido.']);
    exit;
}

$stmt_pelicula = $db->prepare("SELECT id, titulo, sinopsis, poster_url FROM peliculas WHERE id = ?");
$stmt_pelicula->bind_param("i", $pelicula_id);
$stmt_pelicula->execute();
$res_pelicula = $stmt_pelicula->get_result();
$pelicula = $res_pelicula->fetch_assoc();

if (!$pelicula) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Película no encontrada.']);
    exit;
}

$stmt_funciones = $db->prepare("SELECT f.id as funcion_id, s.nombre as sala, f.horario FROM funciones f JOIN salas s ON f.sala_id = s.id WHERE f.pelicula_id = ? ORDER BY f.horario");
$stmt_funciones->bind_param("i", $pelicula_id);
$stmt_funciones->execute();
$res_funciones = $stmt_funciones->get_result();

$funciones = [];
while ($fila = $res_funciones->fetch_assoc()) {
    $fila['horario'] = date('H:i', strtotime($fila['horario']));
    $funciones[] = $fila;
}

$pelicula['funciones'] = $funciones;
echo json_encode($pelicula);

$db->close();
?>