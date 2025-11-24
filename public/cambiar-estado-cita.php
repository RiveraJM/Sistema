<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $cita_id = $data['cita_id'] ?? null;
    $nuevo_estado = $data['estado'] ?? null;
    
    if (!$cita_id || !$nuevo_estado) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit();
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Obtener datos actuales de la cita
        $query = "SELECT * FROM citas WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $cita_id);
        $stmt->execute();
        $cita_actual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cita_actual) {
            echo json_encode(['success' => false, 'message' => 'Cita no encontrada']);
            exit();
        }
        
        // Actualizar estado
        $query = "UPDATE citas SET estado = :estado";
        
        // Si inicia atención, registrar hora
        if ($nuevo_estado === 'en_atencion') {
            $query .= ", hora_atencion = NOW()";
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':estado', $nuevo_estado);
        $stmt->bindParam(':id', $cita_id);
        
        if ($stmt->execute()) {
            // Registrar en historial
            $queryHistorial = "INSERT INTO historial_citas (cita_id, accion, estado_anterior, estado_nuevo, usuario_id) 
                              VALUES (:cita_id, 'cambio_estado', :estado_ant, :estado_new, :user_id)";
            $stmtHistorial = $db->prepare($queryHistorial);
            $stmtHistorial->execute([
                ':cita_id' => $cita_id,
                ':estado_ant' => $cita_actual['estado'],
                ':estado_new' => $nuevo_estado,
                ':user_id' => getUserId()
            ]);
            
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