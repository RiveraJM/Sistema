<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$notificacion_id = $data['id'] ?? null;
$usuario_id = getUserId();

if (!$notificacion_id) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que la notificación pertenece al usuario
    $query = "UPDATE notificaciones 
              SET leida = 1, fecha_lectura = NOW() 
              WHERE id = :id AND usuario_id = :usuario_id";
    
    $stmt = $db->prepare($query);
    $result = $stmt->execute([
        ':id' => $notificacion_id,
        ':usuario_id' => $usuario_id
    ]);
    
    echo json_encode(['success' => $result]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>