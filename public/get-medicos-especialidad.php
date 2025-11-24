<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$especialidad_id = $_GET['especialidad_id'] ?? null;

if (!$especialidad_id) {
    echo json_encode([]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT m.id, CONCAT(u.nombre, ' ', u.apellido) as nombre, m.consultorio
              FROM medicos m
              INNER JOIN usuarios u ON m.usuario_id = u.id
              WHERE m.especialidad_id = :especialidad_id 
              AND m.estado = 'activo'
              ORDER BY u.nombre";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':especialidad_id', $especialidad_id);
    $stmt->execute();
    
    $medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($medicos);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>