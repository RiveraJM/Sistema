<?php
use PHPUnit\Framework\TestCase;

class CitasTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO(
            'mysql:host=db;dbname=sistema_clinico;charset=utf8',
            'admin',
            'adminpass',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public function testAgregarCita()
    {
        // --- Crear paciente temporal ---
        $dni = 'DNI' . rand(1000, 9999);
        $stmt = $this->pdo->prepare("
            INSERT INTO pacientes (dni, nombre, apellido, fecha_nacimiento, sexo, estado)
            VALUES (:dni, :nombre, :apellido, :fecha_nac, :sexo, 'activo')
        ");
        $stmt->execute([
            'dni' => $dni,
            'nombre' => 'Test',
            'apellido' => 'Paciente',
            'fecha_nac' => '1990-01-01',
            'sexo' => 'M'
        ]);
        $paciente_id = $this->pdo->lastInsertId();

        // --- Crear usuario temporal para el médico ---
        $username = 'medico_' . rand(1000, 9999);
        $stmt = $this->pdo->prepare("
            INSERT INTO usuarios (username, password, nombre, apellido, email, rol, estado)
            VALUES (:username, :password, :nombre, :apellido, :email, 'medico', 'activo')
        ");
        $stmt->execute([
            'username' => $username,
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'nombre' => 'Test',
            'apellido' => 'Medico',
            'email' => $username . '@example.com'
        ]);
        $usuario_id = $this->pdo->lastInsertId();

        // --- Crear médico temporal ---
        $stmt = $this->pdo->prepare("
            INSERT INTO medicos (usuario_id, especialidad_id, numero_colegiatura, estado)
            VALUES (:usuario_id, :especialidad_id, :num_colegiatura, 'activo')
        ");
        $stmt->execute([
            'usuario_id' => $usuario_id,
            'especialidad_id' => 1, // asegúrate que exista
            'num_colegiatura' => 'MED' . rand(1000, 9999)
        ]);
        $medico_id = $this->pdo->lastInsertId();

        // --- Crear cita ---
        $stmt = $this->pdo->prepare("
            INSERT INTO citas (paciente_id, medico_id, fecha, hora, tipo_cita, estado)
            VALUES (:paciente_id, :medico_id, :fecha, :hora, 'consulta', 'programada')
        ");
        $stmt->execute([
            'paciente_id' => $paciente_id,
            'medico_id' => $medico_id,
            'fecha' => date('Y-m-d'),
            'hora' => date('H:i:s')
        ]);
        $cita_id = $this->pdo->lastInsertId();

        $this->assertIsNumeric($cita_id, "Cita creada correctamente");

        // --- Limpiar datos ---
        $this->pdo->prepare("DELETE FROM citas WHERE id = :id")->execute(['id' => $cita_id]);
        $this->pdo->prepare("DELETE FROM medicos WHERE id = :id")->execute(['id' => $medico_id]);
        $this->pdo->prepare("DELETE FROM usuarios WHERE id = :id")->execute(['id' => $usuario_id]);
        $this->pdo->prepare("DELETE FROM pacientes WHERE id = :id")->execute(['id' => $paciente_id]);
    }
}
