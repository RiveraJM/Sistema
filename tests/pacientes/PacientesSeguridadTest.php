<?php

use PHPUnit\Framework\TestCase;
use App\Models\Paciente;
use App\Config\Database;

class PacientesSeguridadTest extends TestCase
{
    private $db;
    private $paciente;

    /**
     * Antes de cada test
     */
    protected function setUp(): void
    {
        // Crear un mock de la conexión para evitar usar la BD real
        $dbMock = $this->createMock(Database::class);

        // Simular que getConnection devuelve un PDO in-memory (sin tocar tu BD)
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Crear tabla simulada
        $pdo->exec("
            CREATE TABLE pacientes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                dni TEXT,
                nombre TEXT,
                apellido TEXT,
                telefono TEXT,
                correo TEXT
            );
        ");

        $dbMock->method('getConnection')->willReturn($pdo);

        $this->db = $pdo;
        $this->paciente = new Paciente($this->db);
    }

    /**
     * Después de cada test
     */
    protected function tearDown(): void
    {
        $this->db = null;
        $this->paciente = null;
    }

    /**
     * Test sanitización básica
     */
    public function testSanitizacionEntrada()
    {
        $input = "<script>alert('xss');</script>";
        $sanitized = $this->paciente->sanitizar($input);

        $this->assertNotEquals($input, $sanitized);
        $this->assertEquals("alert('xss');", $sanitized);
    }

    /**
     * Validación de DNI correcto
     */
    public function testValidacionDniCorrecto()
    {
        $dni = "12345678";
        $resultado = $this->paciente->validarDni($dni);

        $this->assertTrue($resultado);
    }

    /**
     * Validación de DNI incorrecto
     */
    public function testValidacionDniIncorrecto()
    {
        $dni = "ABC123";
        $resultado = $this->paciente->validarDni($dni);

        $this->assertFalse($resultado);
    }

    /**
     * Inserción segura sin tocar tu BD real
     */
    public function testInsertarPacienteSeguro()
    {
        $datos = [
            "dni" => "87654321",
            "nombre" => "Carlos",
            "apellido" => "Perez",
            "telefono" => "987654321",
            "correo" => "carlos@example.com"
        ];

        $resultado = $this->paciente->crearPaciente($datos);

        $this->assertTrue($resultado);

        $stmt = $this->db->query("SELECT COUNT(*) FROM pacientes");
        $count = $stmt->fetchColumn();

        $this->assertEquals(1, $count);
    }

    /**
     * Prueba contra intento de SQL Injection
     */
    public function testPrevencionSqlInjection()
    {
        $datos = [
            "dni" => "12345678'; DROP TABLE pacientes; --",
            "nombre" => "Prueba",
            "apellido" => "Ataque",
            "telefono" => "999999999",
            "correo" => "test@test.com"
        ];

        $resultado = $this->paciente->crearPaciente($datos);

        $this->assertTrue($resultado);

        // Verificar que NO se haya eliminado la tabla
        $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='pacientes';");
        $tabla = $stmt->fetchColumn();

        $this->assertEquals('pacientes', $tabla);
    }
}
