
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once 'db.php';

$database = new Database();
$db = $database->connect();

$funcion_id = filter_input(INPUT_GET, 'funcion_id', FILTER_SANITIZE_NUMBER_INT);
$stmt = $db->prepare("SELECT asiento FROM boletos WHERE funcion_id = ?");
$stmt->bind_param("i", $funcion_id);
$stmt->execute();
$resultado = $stmt->get_result();

$asientos = [];
while ($fila = $resultado->fetch_assoc()) {
    $asientos[] = $fila['asiento'];
}
echo json_encode($asientos);

$db->close();
?>