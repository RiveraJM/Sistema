<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$medico_id = $_GET['medico_id'] ?? null;
$fecha = $_GET['fecha'] ?? null;

if (!$medico_id || !$fecha) {
    echo json_encode([]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener día de la semana
    $dias_semana = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
    $dia_semana = $dias_semana[date('w', strtotime($fecha))];
    
    // Obtener horarios del médico para ese día
    $query = "SELECT hora_inicio, hora_fin, cupos_por_hora 
              FROM horarios_medicos 
              WHERE medico_id = :medico_id 
              AND dia_semana = :dia_semana 
              AND estado = 'activo'";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':medico_id' => $medico_id,
        ':dia_semana' => $dia_semana
    ]);
    
    $horario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$horario) {
        echo json_encode([]);
        exit();
    }
    
    // Verificar si hay excepciones para esta fecha
    $query_excepcion = "SELECT COUNT(*) as total FROM excepciones_horario 
                        WHERE medico_id = :medico_id AND fecha = :fecha";
    $stmt_excepcion = $db->prepare($query_excepcion);
    $stmt_excepcion->execute([
        ':medico_id' => $medico_id,
        ':fecha' => $fecha
    ]);
    $excepcion = $stmt_excepcion->fetch(PDO::FETCH_ASSOC);
    
    if ($excepcion['total'] > 0) {
        echo json_encode([]);
        exit();
    }
    
    // Generar slots de tiempo
    $hora_inicio = strtotime($horario['hora_inicio']);
    $hora_fin = strtotime($horario['hora_fin']);
    $duracion_consulta = 30; // minutos
    
    $horarios = [];
    $hora_actual = $hora_inicio;
    
    while ($hora_actual < $hora_fin) {
        $hora_slot = date('H:i:s', $hora_actual);
        
        // Verificar si ya existe cita en este horario
        $query_cita = "SELECT COUNT(*) as total FROM citas 
                       WHERE medico_id = :medico_id 
                       AND fecha = :fecha 
                       AND hora = :hora 
                       AND estado NOT IN ('cancelada', 'ausente')";
        $stmt_cita = $db->prepare($query_cita);
        $stmt_cita->execute([
            ':medico_id' => $medico_id,
            ':fecha' => $fecha,
            ':hora' => $hora_slot
        ]);
        $cita_existe = $stmt_cita->fetch(PDO::FETCH_ASSOC);
        
        $horarios[] = [
            'hora' => date('H:i', $hora_actual),
            'disponible' => $cita_existe['total'] < $horario['cupos_por_hora']
        ];
        
        $hora_actual = strtotime("+{$duracion_consulta} minutes", $hora_actual);
    }
    
    echo json_encode($horarios);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>