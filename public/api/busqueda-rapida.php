<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode(['resultados' => []]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$search_term = '%' . $query . '%';
$resultados = [];

// Buscar pacientes (máximo 5)
$query_pacientes = "SELECT id, CONCAT(nombre, ' ', apellido) as nombre, dni, 'paciente' as tipo
                   FROM pacientes
                   WHERE nombre LIKE :search OR apellido LIKE :search OR dni LIKE :search
                   LIMIT 5";
$stmt = $db->prepare($query_pacientes);
$stmt->bindParam(':search', $search_term);
$stmt->execute();
$resultados = array_merge($resultados, $stmt->fetchAll(PDO::FETCH_ASSOC));

// Buscar médicos (máximo 5)
$query_medicos = "SELECT m.id, CONCAT(u.nombre, ' ', u.apellido) as nombre, e.nombre as especialidad, 'medico' as tipo
                 FROM medicos m
                 INNER JOIN usuarios u ON m.usuario_id = u.id
                 INNER JOIN especialidades e ON m.especialidad_id = e.id
                 WHERE u.nombre LIKE :search OR u.apellido LIKE :search
                 LIMIT 5";
$stmt = $db->prepare($query_medicos);
$stmt->bindParam(':search', $search_term);
$stmt->execute();
$resultados = array_merge($resultados, $stmt->fetchAll(PDO::FETCH_ASSOC));

echo json_encode(['resultados' => $resultados]);
?>