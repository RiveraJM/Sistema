<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

// Verificar permisos
if (!hasPermission('editar_historia_clinica')) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $historia_id = $_POST['historia_id'] ?? null;
    $fecha_evolucion = $_POST['fecha_evolucion'] ?? null;
    $nota_evolucion = sanitizeInput($_POST['nota_evolucion'] ?? '');
    
    if (!$historia_id || !$fecha_evolucion || !$nota_evolucion) {
        $_SESSION['error'] = 'Datos incompletos';
        header("Location: detalle-historia.php?id=$historia_id");
        exit();
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Obtener ID del médico actual
        $usuario_id = getUserId();
        $query_medico = "SELECT id FROM medicos WHERE usuario_id = :usuario_id";
        $stmt_medico = $db->prepare($query_medico);
        $stmt_medico->execute([':usuario_id' => $usuario_id]);
        $medico = $stmt_medico->fetch(PDO::FETCH_ASSOC);
        
        if (!$medico) {
            $_SESSION['error'] = 'Solo los médicos pueden agregar evoluciones';
            header("Location: detalle-historia.php?id=$historia_id");
            exit();
        }
        
        // Insertar evolución
        $query = "INSERT INTO evoluciones (historia_id, medico_id, fecha_evolucion, nota_evolucion) 
                 VALUES (:historia_id, :medico_id, :fecha_evolucion, :nota_evolucion)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':historia_id' => $historia_id,
            ':medico_id' => $medico['id'],
            ':fecha_evolucion' => $fecha_evolucion,
            ':nota_evolucion' => $nota_evolucion
        ]);
        
        $_SESSION['success'] = 'Evolución registrada correctamente';
        header("Location: detalle-historia.php?id=$historia_id");
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error al guardar la evolución';
        header("Location: detalle-historia.php?id=$historia_id");
    }
} else {
    header("Location: dashboard.php");
}
?>