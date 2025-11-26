<?php
use PHPUnit\Framework\TestCase;

class PacientesEdgeCasesTest extends TestCase
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

    public function testPacienteSinNombreDebeFallar()
    {
        $this->expectException(PDOException::class);

        $dni = rand(10000000,99999999);

        $this->pdo->prepare("
            INSERT INTO pacientes (dni,nombre,apellido,fecha_nacimiento,sexo)
            VALUES (:dni,'', 'Test','1990-01-01','M')
        ")->execute(['dni'=>$dni]);
    }

    public function testPacienteConFechaFuturaDebeFallar()
    {
        $this->expectException(PDOException::class);

        $future = date('Y-m-d', strtotime('+5 years'));

        $this->pdo->prepare("
            INSERT INTO pacientes (dni,nombre,apellido,fecha_nacimiento,sexo)
            VALUES ('".rand(10000000,99999999)."','Test','Fecha','$future','F')
        ")->execute();
    }
}
