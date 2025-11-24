<?php
use PHPUnit\Framework\TestCase;

class UsuariosTest extends TestCase
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

    public function testAgregarUsuario()
    {
        $username = 'maria_' . rand(1000, 9999);

        // Limpiar usuario si existe
        $this->pdo->prepare("DELETE FROM usuarios WHERE username = :username")->execute(['username' => $username]);

        // Insertar usuario
        $stmt = $this->pdo->prepare("
            INSERT INTO usuarios (username, password, nombre, apellido, email, rol)
            VALUES (:username, :password, :nombre, :apellido, :email, 'recepcionista')
        ");
        $stmt->execute([
            'username' => $username,
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'nombre'   => 'Maria',
            'apellido' => 'Lopez',
            'email'    => $username.'@example.com'
        ]);

        // Test que el usuario fue agregado
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($user, "El usuario debería ser agregado correctamente");
    }
}
