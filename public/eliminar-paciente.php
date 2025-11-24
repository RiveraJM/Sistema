<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

// Verificar permisos
if (!hasPermission('gestionar_pacientes')) {
    echo json_encode(['success' => false, 'message' => 'No tiene permisos']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $paciente_id = $data['paciente_id'] ?? null;
    
    if (!$paciente_id) {
        echo json_encode(['success' => false, 'message' => 'ID de paciente no proporcionado']);
        exit();
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Verificar si el paciente tiene citas
        $query_citas = "SELECT COUNT(*) as total FROM citas WHERE paciente_id = :id AND estado NOT IN ('cancelada', 'ausente')";
        $stmt_citas = $db->prepare($query_citas);
        $stmt_citas->bindParam(':id', $paciente_id);
        $stmt_citas->execute();
        $tiene_citas = $stmt_citas->fetch(PDO::FETCH_ASSOC);
        
        if ($tiene_citas['total'] > 0) {
            echo json_encode(['success' => false, 'message' => 'No se puede eliminar. El paciente tiene citas activas.']);
            exit();
        }
        
        // Cambiar estado a inactivo en lugar de eliminar físicamente
        $query = "UPDATE pacientes SET estado = 'inactivo' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $paciente_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Paciente eliminado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el paciente']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>