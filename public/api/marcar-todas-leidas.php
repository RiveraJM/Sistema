<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$usuario_id = getUserId();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE notificaciones 
              SET leida = 1, fecha_lectura = NOW() 
              WHERE usuario_id = :usuario_id AND leida = 0";
    
    $stmt = $db->prepare($query);
    $result = $stmt->execute([':usuario_id' => $usuario_id]);
    
    echo json_encode(['success' => $result]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>