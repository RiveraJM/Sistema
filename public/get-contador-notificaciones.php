<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) as total FROM notificaciones 
             WHERE usuario_id = :usuario_id AND leida = 0";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':usuario_id' => getUserId()]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'total' => $result['total']]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>