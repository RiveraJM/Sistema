<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$dni = $_GET['dni'] ?? '';

if (empty($dni)) {
    echo json_encode(['success' => false, 'message' => 'DNI no proporcionado']);
    exit();
}

if (strlen($dni) !== 8 || !ctype_digit($dni)) {
    echo json_encode(['success' => false, 'message' => 'El DNI debe tener 8 dígitos']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT 
              p.*,
              s.nombre as seguro_nombre,
              TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) as edad
              FROM pacientes p
              LEFT JOIN seguros s ON p.seguro_id = s.id
              WHERE p.dni = :dni
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':dni' => $dni]);
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($paciente) {
        echo json_encode([
            'success' => true,
            'paciente' => [
                'id' => $paciente['id'],
                'nombre' => $paciente['nombre'],
                'apellido' => $paciente['apellido'],
                'dni' => $paciente['dni'],
                'edad' => $paciente['edad'],
                'sexo' => $paciente['sexo'],
                'telefono' => $paciente['telefono'],
                'email' => $paciente['email'],
                'seguro' => $paciente['seguro_nombre']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró paciente con DNI: ' . $dni
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al buscar el paciente'
    ]);
}
?>