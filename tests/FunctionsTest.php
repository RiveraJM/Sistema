<?php
use PHPUnit\Framework\TestCase;

// Incluir clases y funciones necesarias usando rutas absolutas dinámicas
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php'; // Aquí debe estar tu sanitizeInput()

class FunctionsTest extends TestCase
{
    // Test para la función sanitizeInput
    public function testSanitizeInput()
    {
        $input = "<script>alert('hack');</script>";
        $expected = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        $this->assertEquals($expected, sanitizeInput($input));
    }

    // Test para la conexión a la base de datos
    public function testDatabaseConnection()
    {
        $database = new Database();
        $conn = $database->getConnection();
        $this->assertInstanceOf(PDO::class, $conn);
    }
}
