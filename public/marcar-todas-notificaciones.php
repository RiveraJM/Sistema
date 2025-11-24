<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "UPDATE notificaciones 
                 SET leida = 1, fecha_lectura = NOW() 
                 WHERE usuario_id = :usuario_id AND leida = 0";
        
        $stmt = $db->prepare($query);
        $stmt->execute([':usuario_id' => getUserId()]);
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>