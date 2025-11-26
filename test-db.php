<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = (new Database())->getConnection();
    echo "Conexión exitosa<br>";

    $query = $db->query("SHOW TABLES");
    $tables = $query->fetchAll(PDO::FETCH_COLUMN);

    echo "Tablas encontradas:<br>";
    print_r($tables);

} catch (Exception $e) {
    echo "Error de conexión: " . $e->getMessage();
}
