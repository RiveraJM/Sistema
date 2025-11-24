<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

// Verificar permisos (solo admin)
if (getUserRole() !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No tiene permisos']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $usuario_id = $data['usuario_id'] ?? null;
    $nuevo_estado = $data['estado'] ?? null;
    
    if (!$usuario_id || !$nuevo_estado) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit();
    }
    
    // No permitir que un admin se desactive a sí mismo
    if ($usuario_id == getUserId()) {
        echo json_encode(['success' => false, 'message' => 'No puede cambiar su propio estado']);
        exit();
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Actualizar estado
        $query = "UPDATE usuarios SET estado = :estado WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':estado', $nuevo_estado);
        $stmt->bindParam(':id', $usuario_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>