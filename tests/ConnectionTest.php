<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config/database.php';

class ConnectionTest extends TestCase
{
    public function testDatabaseConnection()
    {
        $database = new Database();
        
        try {
            $conn = $database->getConnection();
            $this->assertInstanceOf(PDO::class, $conn, "La conexión no devolvió una instancia PDO");
        } catch (Exception $e) {
            $this->fail("Error al conectar: " . $e->getMessage());
        }
    }

    public function testCanQueryTables()
    {
        $database = new Database();
        $conn = $database->getConnection();

        try {
            $stmt = $conn->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $this->assertNotEmpty($tables, "La base de datos no tiene tablas o no se pudo consultar.");
        } catch (Exception $e) {
            $this->fail("Error consultando tablas: " . $e->getMessage());
        }
    }
}
