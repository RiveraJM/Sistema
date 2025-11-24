<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        header("Location: login.php?error=empty");
        exit();
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        // Buscar usuario activo
        $query = "SELECT u.*, GROUP_CONCAT(p.nombre) as permisos 
                  FROM usuarios u
                  LEFT JOIN usuario_permisos up ON u.id = up.usuario_id
                  LEFT JOIN permisos p ON up.permiso_id = p.id
                  WHERE u.username = :username AND u.estado = 'activo'
                  GROUP BY u.id";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verificar contraseña (asegúrate de que tu DB tenga hash bcrypt)
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['apellido'] = $user['apellido'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['permisos'] = $user['permisos'] ? explode(',', $user['permisos']) : [];

                // Actualizar último acceso
                $updateQuery = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bindParam(':id', $user['id']);
                $updateStmt->execute();

                header("Location: dashboard.php");
                exit();
            } else {
                header("Location: login.php?error=invalid");
                exit();
            }
        } else {
            header("Location: login.php?error=invalid");
            exit();
        }

    } catch (PDOException $e) {
        // Para depuración temporal, muestra el error real
        die("Error en login: " . $e->getMessage());
        // Una vez depurado, cambia a:
        // header("Location: login.php?error=system");
        // exit();
    }
} else {
    header("Location: login.php");
    exit();
}
