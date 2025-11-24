<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$especialidad_id = $_GET['especialidad_id'] ?? null;

if (!$especialidad_id) {
    echo json_encode(['success' => false, 'message' => 'Especialidad no proporcionada']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT 
              m.id,
              CONCAT(u.nombre, ' ', u.apellido) as nombre,
              m.consultorio
              FROM medicos m
              INNER JOIN usuarios u ON m.usuario_id = u.id
              WHERE m.especialidad_id = :especialidad_id
              AND u.estado = 'activo'
              ORDER BY u.nombre, u.apellido";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':especialidad_id' => $especialidad_id]);
    $medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'medicos' => $medicos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener médicos'
    ]);
}
?>