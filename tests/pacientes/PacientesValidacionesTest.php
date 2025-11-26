<?php
use PHPUnit\Framework\TestCase;

class PacientesValidacionesTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        $host = getenv('DB_HOST') ?: 'db';
        $db   = getenv('DB_NAME') ?: 'sistema_clinico';
        $user = getenv('DB_USER') ?: 'admin';
        $pass = getenv('DB_PASS') ?: 'adminpass';
        $port = getenv('DB_PORT') ?: 3306;

        $this->pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8",$user,$pass);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function testDNINoNumericoDebeFallar()
    {
        $this->expectException(PDOException::class);

        $this->pdo->prepare("
            INSERT INTO pacientes (dni,nombre,apellido,fecha_nacimiento,sexo)
            VALUES ('ABC123','Test','Paciente','1990-01-01','M')
        ")->execute();
    }

    public function testNombreMuyLargoDebeFallar()
    {
        $this->expectException(PDOException::class);

        $nombre = str_repeat('A', 300);

        $stmt = $this->pdo->prepare("
            INSERT INTO pacientes (dni,nombre,apellido,fecha_nacimiento,sexo)
            VALUES ('".rand(10000000,99999999)."', :nom, 'Test', '1990-01-01', 'F')
        ");
        $stmt->execute(['nom'=>$nombre]);
    }
}
