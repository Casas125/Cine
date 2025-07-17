
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once 'db.php';

$database = new Database();
$db = $database->connect();

$query = "SELECT id, titulo, poster_url FROM peliculas ORDER BY titulo";
$resultado = $db->query($query);
$peliculas = [];
while ($fila = $resultado->fetch_assoc()) {
    $peliculas[] = $fila;
}
echo json_encode($peliculas);

$db->close();
?>