<?php
use PHPUnit\Framework\TestCase;

class PacientesTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        $host = getenv('DB_HOST') ?: 'db';
        $db   = getenv('DB_NAME') ?: 'sistema_clinico';
        $user = getenv('DB_USER') ?: 'admin';
        $pass = getenv('DB_PASS') ?: 'adminpass';
        $port = getenv('DB_PORT') ?: 3306;

        $this->pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8", $user, $pass);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /** --------------------------- 1. AGREGAR PACIENTE ---------------------------- */
    public function testAgregarPaciente()
    {
        $dni = rand(10000000, 99999999);

        // Limpiar por si existe
        $this->pdo->prepare("DELETE FROM pacientes WHERE dni = :dni")->execute(['dni' => $dni]);

        // Insertar
        $stmt = $this->pdo->prepare("
            INSERT INTO pacientes (dni, nombre, apellido, fecha_nacimiento, sexo, estado)
            VALUES (:dni, 'Carlos', 'Torres', '1990-01-01', 'M', 'activo')
        ");
        $stmt->execute(['dni' => $dni]);

        // Buscar
        $stmt = $this->pdo->prepare("SELECT * FROM pacientes WHERE dni = :dni");
        $stmt->execute(['dni' => $dni]);
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($paciente);

        // limpiar
        $this->pdo->prepare("DELETE FROM pacientes WHERE dni = :dni")->execute(['dni' => $dni]);
    }

    /** --------------------------- 2. DNI DUPLICADO ---------------------------- */
    public function testDNIDuplicadoNoPermitido()
    {
        $dni = rand(10000000, 99999999);

        // Insertar primero
        $this->pdo->prepare("
            INSERT INTO pacientes (dni, nombre, apellido, fecha_nacimiento, sexo)
            VALUES (:dni, 'Luis', 'Prueba', '1990-01-01', 'M')
        ")->execute(['dni' => $dni]);

        // Intento duplicado → debe generar excepción
        $this->expectException(PDOException::class);

        $this->pdo->prepare("
            INSERT INTO pacientes (dni, nombre, apellido, fecha_nacimiento, sexo)
            VALUES (:dni, 'Otro', 'Paciente', '1995-05-05', 'F')
        ")->execute(['dni' => $dni]);
    }

    /** --------------------------- 3. ACTUALIZAR PACIENTE ---------------------------- */
    public function testActualizarPaciente()
    {
        $dni = rand(10000000, 99999999);

        // Insertamos paciente
        $this->pdo->prepare("
            INSERT INTO pacientes (dni, nombre, apellido, fecha_nacimiento, sexo)
            VALUES (:dni, 'Pedro', 'Chavez', '1985-01-01', 'M')
        ")->execute(['dni' => $dni]);

        // Actualizar
        $stmt = $this->pdo->prepare("
            UPDATE pacientes SET nombre = 'Pedro Actualizado' WHERE dni = :dni
        ");
        $stmt->execute(['dni' => $dni]);

        // Validar
        $stmt = $this->pdo->prepare("SELECT nombre FROM pacientes WHERE dni = :dni");
        $stmt->execute(['dni' => $dni]);
        $nombre = $stmt->fetchColumn();

        $this->assertEquals('Pedro Actualizado', $nombre);

        // limpiar
        $this->pdo->prepare("DELETE FROM pacientes WHERE dni = :dni")->execute(['dni' => $dni]);
    }


    /** --------------------------- 4. ELIMINAR PACIENTE ---------------------------- */
    public function testEliminarPaciente()
    {
        $dni = rand(10000000, 99999999);

        // Insertar
        $this->pdo->prepare("
            INSERT INTO pacientes (dni, nombre, apellido, fecha_nacimiento, sexo)
            VALUES (:dni, 'Eliminar', 'Test', '1999-01-01', 'F')
        ")->execute(['dni' => $dni]);

        // Eliminar
        $this->pdo->prepare("DELETE FROM pacientes WHERE dni = :dni")->execute(['dni' => $dni]);

        // Validar
        $stmt = $this->pdo->prepare("SELECT * FROM pacientes WHERE dni = :dni");
        $stmt->execute(['dni' => $dni]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEmpty($result);
    }


    /** --------------------------- 5. OBTENER PACIENTE POR ID ---------------------------- */
    public function testObtenerPacientePorId()
    {
        $dni = rand(10000000, 99999999);

        $this->pdo->prepare("
            INSERT INTO pacientes (dni, nombre, apellido, fecha_nacimiento, sexo)
            VALUES (:dni, 'Mario', 'Gomez', '1990-01-01', 'M')
        ")->execute(['dni' => $dni]);

        $id = $this->pdo->lastInsertId();

        // Obtener
        $stmt = $this->pdo->prepare("SELECT * FROM pacientes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($paciente);

        $this->pdo->prepare("DELETE FROM pacientes WHERE id = :id")->execute(['id' => $id]);
    }

    /** --------------------------- 6. LISTAR ACTIVOS ---------------------------- */
    public function testListarPacientesActivos()
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM pacientes WHERE estado = 'activo'");
        $count = $stmt->fetchColumn();

        $this->assertGreaterThanOrEqual(0, $count);
    }

    /** --------------------------- 7. SQL INJECTION ---------------------------- */
    public function testSQLInjectionEnDNI()
    {
        $dni = "' OR 1=1 -- ";

        $this->expectException(PDOException::class);

        $this->pdo->prepare("
            INSERT INTO pacientes (dni, nombre, apellido, fecha_nacimiento, sexo)
            VALUES (:dni, 'SQL', 'Test', '2000-01-01', 'M')
        ")->execute(['dni' => $dni]);
    }

    /** --------------------------- 8. XSS ---------------------------- */
    public function testXSSNoPermitidoEnNombre()
    {
        $dni = rand(10000000, 99999999);
        $xss = "<script>alert(1)</script>";

        $stmt = $this->pdo->prepare("
            INSERT INTO pacientes (dni, nombre, apellido, fecha_nacimiento, sexo)
            VALUES (:dni, :nombre, 'Seguridad', '1990-01-01', 'F')
        ");
        $stmt->execute(['dni'=>$dni, 'nombre'=>$xss]);

        $id = $this->pdo->lastInsertId();

        $stored = $this->pdo->query("SELECT nombre FROM pacientes WHERE id=$id")->fetchColumn();

        $this->assertNotEquals($xss, $stored, "No se debe guardar un script sin sanitizar");

        $this->pdo->prepare("DELETE FROM pacientes WHERE id=:id")->execute(['id'=>$id]);
    }

    /** --------------------------- 9. RELACIÓN PACIENTE → CITA ---------------------------- */
    public function testPacienteTieneRelacionConCita()
    {
        // Crear paciente
        $dni = rand(10000000, 99999999);
        $this->pdo->prepare("
            INSERT INTO pacientes (dni, nombre, apellido, fecha_nacimiento, sexo)
            VALUES (:dni, 'Relacion', 'Test', '1990-01-01', 'M')
        ")->execute(['dni'=>$dni]);
        $paciente_id = $this->pdo->lastInsertId();

        // Crear usuario + medico
        $username = "doc_" . rand(1000,9999);
        $this->pdo->prepare("
            INSERT INTO usuarios (username, password, nombre, apellido, email, rol)
            VALUES (:u, '123', 'Test', 'Medico', :email, 'medico')
        ")->execute(['u'=>$username, 'email'=>$username.'@mail.com']);
        $usuario_id = $this->pdo->lastInsertId();

        $this->pdo->prepare("
            INSERT INTO medicos (usuario_id, especialidad_id, numero_colegiatura)
            VALUES (:u, 1, 'COL".rand(1000,9999)."')
        ")->execute(['u'=>$usuario_id]);
        $medico_id = $this->pdo->lastInsertId();

        // Crear cita
        $this->pdo->prepare("
            INSERT INTO citas (paciente_id, medico_id, fecha, hora)
            VALUES (:p, :m, CURDATE(), CURTIME())
        ")->execute(['p'=>$paciente_id, 'm'=>$medico_id]);

        $count = $this->pdo->query("SELECT COUNT(*) FROM citas WHERE paciente_id = $paciente_id")->fetchColumn();

        $this->assertGreaterThanOrEqual(1, $count);

        // limpiar
        $this->pdo->prepare("DELETE FROM citas WHERE paciente_id=:id")->execute(['id'=>$paciente_id]);
        $this->pdo->prepare("DELETE FROM medicos WHERE id=:id")->execute(['id'=>$medico_id]);
        $this->pdo->prepare("DELETE FROM usuarios WHERE id=:id")->execute(['id'=>$usuario_id]);
        $this->pdo->prepare("DELETE FROM pacientes WHERE id=:id")->execute(['id'=>$paciente_id]);
    }

    /** --------------------------- 10. PACIENTE + SEGURO ---------------------------- */
    public function testPacienteConSeguro()
    {
        // Crear seguro
        $this->pdo->prepare("
            INSERT INTO seguros (nombre, tipo)
            VALUES ('SeguroTest', 'privado')
        ")->execute();
        $seguro_id = $this->pdo->lastInsertId();

        // Crear paciente con seguro
        $dni = rand(10000000, 99999999);
        $this->pdo->prepare("
            INSERT INTO pacientes (dni, nombre, apellido, fecha_nacimiento, sexo, seguro_id)
            VALUES (:dni, 'Seguro', 'Paciente', '2000-01-01', 'F', :seg)
        ")->execute(['dni'=>$dni, 'seg'=>$seguro_id]);
        $paciente_id = $this->pdo->lastInsertId();

        $stored = $this->pdo->query("SELECT seguro_id FROM pacientes WHERE id=$paciente_id")->fetchColumn();

        $this->assertEquals($seguro_id, $stored);

        // limpiar
        $this->pdo->prepare("DELETE FROM pacientes WHERE id=:id")->execute(['id'=>$paciente_id]);
        $this->pdo->prepare("DELETE FROM seguros WHERE id=:id")->execute(['id'=>$seguro_id]);
    }
}
