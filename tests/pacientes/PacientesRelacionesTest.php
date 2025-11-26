<?php
use PHPUnit\Framework\TestCase;

class PacientesRelacionesTest extends TestCase
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

    public function testPacienteConCita()
    {
        // paciente
        $dni = rand(10000000,99999999);
        $this->pdo->prepare("
            INSERT INTO pacientes (dni,nombre,apellido,fecha_nacimiento,sexo)
            VALUES (:dni,'Rel','Test','1980-01-01','M')
        ")->execute(['dni'=>$dni]);
        $paciente_id = $this->pdo->lastInsertId();

        // user + medico
        $user = "med_" . rand(1000,9999);
        $this->pdo->prepare("
            INSERT INTO usuarios (username,password,nombre,apellido,email,rol)
            VALUES (:u,'123','Med','Test',:e,'medico')
        ")->execute(['u'=>$user,'e'=>$user.'@mail.com']);
        $uid = $this->pdo->lastInsertId();

        $this->pdo->prepare("
            INSERT INTO medicos (usuario_id,especialidad_id,numero_colegiatura)
            VALUES (:u,1,'C".rand(1000,9999)."')
        ")->execute(['u'=>$uid]);
        $mid = $this->pdo->lastInsertId();

        // cita
        $this->pdo->prepare("
            INSERT INTO citas (paciente_id,medico_id,fecha,hora)
            VALUES (:p,:m,CURDATE(),CURTIME())
        ")->execute(['p'=>$paciente_id,'m'=>$mid]);

        $count = $this->pdo->query("SELECT COUNT(*) FROM citas WHERE paciente_id=$paciente_id")->fetchColumn();
        $this->assertGreaterThanOrEqual(1, $count);

        // limpieza
        $this->pdo->exec("DELETE FROM citas WHERE paciente_id=$paciente_id");
        $this->pdo->exec("DELETE FROM medicos WHERE id=$mid");
        $this->pdo->exec("DELETE FROM usuarios WHERE id=$uid");
        $this->pdo->exec("DELETE FROM pacientes WHERE id=$paciente_id");
    }

    public function testPacienteConSeguro()
    {
        // seguro
        $this->pdo->prepare("INSERT INTO seguros (nombre,tipo) VALUES ('SeguroTest','privado')")->execute();
        $sid = $this->pdo->lastInsertId();

        // paciente
        $dni = rand(10000000,99999999);
        $this->pdo->prepare("
            INSERT INTO pacientes (dni,nombre,apellido,fecha_nacimiento,sexo,seguro_id)
            VALUES (:dni,'Test','Seguro','1990-01-01','F',:seg)
        ")->execute(['dni'=>$dni,'seg'=>$sid]);
        $pid = $this->pdo->lastInsertId();

        $stored = $this->pdo->query("SELECT seguro_id FROM pacientes WHERE id=$pid")->fetchColumn();

        $this->assertEquals($sid, $stored);

        // limpieza
        $this->pdo->exec("DELETE FROM pacientes WHERE id=$pid");
        $this->pdo->exec("DELETE FROM seguros WHERE id=$sid");
    }
}
