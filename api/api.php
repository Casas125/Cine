<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'db.php';
require_once 'vendor/autoload.php'; // Para JWT

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Clave secreta para firmar los tokens JWT. ¡Guárdala de forma segura!
$secret_key = "TU_CLAVE_SECRETA_SUPER_SEGURA_123";

function get_user_id_from_token() {
    global $secret_key;
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

$accion = $_GET['accion'] ?? '';

switch ($accion) {
    case 'get_peliculas':
        $query = "SELECT p.id, p.titulo, p.sinopsis, p.poster_url, s.nombre as sala, f.horario, f.id as funcion_id 
                  FROM peliculas p 
                  JOIN funciones f ON p.id = f.pelicula_id 
                  JOIN salas s ON f.sala_id = s.id 
                  ORDER BY p.titulo, f.horario";
        $resultado = $db->query($query);
        
        $peliculas = [];
        while ($fila = $resultado->fetch_assoc()) {
            if (!isset($peliculas[$fila['titulo']])) {
                $peliculas[$fila['titulo']] = [
                    'id' => $fila['id'],
                    'titulo' => $fila['titulo'],
                    'sinopsis' => $fila['sinopsis'],
                    'poster_url' => $fila['poster_url'],
                    'funciones' => []
                ];
            }
            $peliculas[$fila['titulo']]['funciones'][] = [
                'funcion_id' => $fila['funcion_id'],
                'sala' => $fila['sala'],
                'horario' => date('H:i', strtotime($fila['horario']))
            ];
        }
        echo json_encode(array_values($peliculas));
        break;

    case 'get_asientos_ocupados':
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
        break;

    case 'procesar_compra':
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
            // Verificar que los asientos no estén ya ocupados (doble chequeo)
            $placeholders = implode(',', array_fill(0, count($asientos), '?'));
            $check_stmt = $db->prepare("SELECT asiento FROM boletos WHERE funcion_id = ? AND asiento IN ($placeholders)");
            $types = 'i' . str_repeat('s', count($asientos));
            $check_stmt->bind_param($types, $funcion_id, ...$asientos);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            if ($result->num_rows > 0) {
                throw new Exception("Lo sentimos, uno o más asientos seleccionados acaban de ser ocupados. Por favor, elige otros.");
            }

            // REVERSIÓN: Generar texto simple para el QR
            $qr_data_combined = "CineMax Boletos - Usuario: $user_id - Función: $funcion_id - Asientos: " . implode(', ', $asientos);

            // REVERSIÓN: Insertar sin el token_compra
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
        break;
    
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
        break;
}

$db->close();
?>