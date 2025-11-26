<?php
use PHPUnit\Framework\TestCase;

class PacientesCRUDTest extends TestCase
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

    public function testCrearPaciente()
    {
        $dni = rand(10000000, 99999999);

        $this->pdo->prepare("DELETE FROM pacientes WHERE dni=:dni")->execute(['dni'=>$dni]);

        $stmt = $this->pdo->prepare("
            INSERT INTO pacientes (dni,nombre,apellido,fecha_nacimiento,sexo,estado)
            VALUES (:dni,'Test','Paciente','1990-01-01','M','activo')
        ");
        $stmt->execute(['dni'=>$dni]);

        $pac = $this->pdo->query("SELECT * FROM pacientes WHERE dni=$dni")->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($pac);
    }

    public function testActualizarPaciente()
    {
        $dni = rand(10000000, 99999999);

        $this->pdo->prepare("
            INSERT INTO pacientes (dni,nombre,apellido,fecha_nacimiento,sexo)
            VALUES (:dni,'Juan','Perez','1980-01-01','M')
        ")->execute(['dni'=>$dni]);

        $this->pdo->prepare("UPDATE pacientes SET nombre='Juan Editado' WHERE dni=:dni")->execute(['dni'=>$dni]);

        $nombre = $this->pdo->query("SELECT nombre FROM pacientes WHERE dni=$dni")->fetchColumn();

        $this->assertEquals('Juan Editado', $nombre);

        $this->pdo->prepare("DELETE FROM pacientes WHERE dni=:dni")->execute(['dni'=>$dni]);
    }

    public function testEliminarPaciente()
    {
        $dni = rand(10000000, 99999999);

        $this->pdo->prepare("
            INSERT INTO pacientes (dni,nombre,apellido,fecha_nacimiento,sexo)
            VALUES (:dni,'Eliminar','Test','2000-01-01','F')
        ")->execute(['dni'=>$dni]);

        $this->pdo->prepare("DELETE FROM pacientes WHERE dni=:dni")->execute(['dni'=>$dni]);

        $pac = $this->pdo->query("SELECT * FROM pacientes WHERE dni=$dni")->fetch(PDO::FETCH_ASSOC);

        $this->assertEmpty($pac);
    }
}
