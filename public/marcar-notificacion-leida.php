<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $notificacion_id = $data['notificacion_id'] ?? null;
    
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
        $stmt->execute([
            ':id' => $notificacion_id,
            ':usuario_id' => getUserId()
        ]);
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>