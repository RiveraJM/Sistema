<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

// Verificar permisos (solo admin)
if (getUserRole() !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Procesar formulario
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nombre = sanitizeInput($_POST['nombre'] ?? '');
    $apellido = sanitizeInput($_POST['apellido'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefono = sanitizeInput($_POST['telefono'] ?? '');
    $rol = $_POST['rol'] ?? '';
    
    // Validaciones
    if (empty($username) || empty($password) || empty($nombre) || empty($apellido) || empty($email) || empty($rol)) {
        $error = 'Por favor complete todos los campos obligatorios';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        try {
            // Verificar si el username ya existe
            $query_user = "SELECT COUNT(*) as total FROM usuarios WHERE username = :username";
            $stmt_user = $db->prepare($query_user);
            $stmt_user->execute([':username' => $username]);
            $existe_user = $stmt_user->fetch(PDO::FETCH_ASSOC);
            
            if ($existe_user['total'] > 0) {
                $error = 'El nombre de usuario ya está en uso';
            } else {
                // Verificar si el email ya existe
                $query_email = "SELECT COUNT(*) as total FROM usuarios WHERE email = :email";
                $stmt_email = $db->prepare($query_email);
                $stmt_email->execute([':email' => $email]);
                $existe_email = $stmt_email->fetch(PDO::FETCH_ASSOC);
                
                if ($existe_email['total'] > 0) {
                    $error = 'El email ya está registrado';
                } else {
                    // Insertar usuario
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    
                    $query = "INSERT INTO usuarios 
                             (username, password, nombre, apellido, email, telefono, rol, estado) 
                             VALUES (:username, :password, :nombre, :apellido, :email, :telefono, :rol, 'activo')";
                    
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':username' => $username,
                        ':password' => $password_hash,
                        ':nombre' => $nombre,
                        ':apellido' => $apellido,
                        ':email' => $email,
                        ':telefono' => $telefono,
                        ':rol' => $rol
                    ]);
                    
                    $usuario_id = $db->lastInsertId();
                    
                    // Asignar permisos básicos según el rol
                    $permisos_default = [
                        'admin' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], // Todos
                        'medico' => [1, 3, 5, 10, 11], // Ver dashboard, citas, pacientes, historia clínica
                        'recepcionista' => [1, 2, 3, 4, 5, 6] // Ver dashboard, gestionar citas y pacientes
                    ];
                    
                    $permisos = $permisos_default[$rol] ?? [1];
                    
                    $query_permisos = "INSERT INTO usuario_permisos (usuario_id, permiso_id) VALUES (:usuario_id, :permiso_id)";
                    $stmt_permisos = $db->prepare($query_permisos);
                    
                    foreach ($permisos as $permiso_id) {
                        $stmt_permisos->execute([
                            ':usuario_id' => $usuario_id,
                            ':permiso_id' => $permiso_id
                        ]);
                    }
                    
                    $success = 'Usuario registrado exitosamente con permisos predeterminados';
                    
                    // Limpiar formulario
                    $_POST = [];
                    
                    // Redirigir después de 2 segundos
                    header("refresh:2;url=usuarios.php");
                }
            }
            
        } catch (Exception $e) {
            $error = 'Error al registrar el usuario: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Usuario - Sistema Clínico</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .rol-card {
            padding: 20px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        .rol-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        .rol-card.selected {
            border-color: var(--primary-color);
            background: rgba(0, 212, 212, 0.05);
        }
        .rol-card input[type="radio"] {
            display: none;
        }
        .rol-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="dashboard-content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Nuevo Usuario</h1>
                        <p class="page-subtitle">Registra un nuevo usuario del sistema</p>
                    </div>
                    <a href="usuarios.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert" style="background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="nuevo-usuario.php">
                    <!-- Selección de Rol -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Seleccione el Rol del Usuario *</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                                <label class="rol-card" onclick="selectRol('admin')">
                                    <input type="radio" name="rol" value="admin" required>
                                    <div class="rol-icon" style="color: #EF4444;">
                                        <i class="fas fa-user-shield"></i>
                                    </div>
                                    <h4 style="margin: 0 0 5px 0;">Administrador</h4>
                                    <p style="margin: 0; font-size: 13px; color: var(--text-secondary);">
                                        Acceso total al sistema
                                    </p>
                                </label>
                                
                                <label class="rol-card" onclick="selectRol('medico')">
                                    <input type="radio" name="rol" value="medico" required>
                                    <div class="rol-icon" style="color: #00D4D4;">
                                        <i class="fas fa-user-md"></i>
                                    </div>
                                    <h4 style="margin: 0 0 5px 0;">Médico</h4>
                                    <p style="margin: 0; font-size: 13px; color: var(--text-secondary);">
                                        Gestión de citas e historias clínicas
                                    </p>
                                </label>
                                
                                <label class="rol-card" onclick="selectRol('recepcionista')">
                                    <input type="radio" name="rol" value="recepcionista" required>
                                    <div class="rol-icon" style="color: #F59E0B;">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <h4 style="margin: 0 0 5px 0;">Recepcionista</h4>
                                    <p style="margin: 0; font-size: 13px; color: var(--text-secondary);">
                                        Gestión de citas y pacientes
                                    </p>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Datos de Acceso -->
                    <div class="card mt-2">
                        <div class="card-header">
                            <h3 class="card-title">Datos de Acceso</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i> Usuario *
                                    </label>
                                    <input type="text" name="username" class="form-control" 
                                           placeholder="Ej: jperez" 
                                           value="<?php echo $_POST['username'] ?? ''; ?>"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-lock"></i> Contraseña *
                                    </label>
                                    <input type="password" name="password" id="password" class="form-control" 
                                           placeholder="Mínimo 6 caracteres" 
                                           required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos Personales -->
                    <div class="card mt-2">
                        <div class="card-header">
                            <h3 class="card-title">Datos Personales</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i> Nombre *
                                    </label>
                                    <input type="text" name="nombre" class="form-control" 
                                           placeholder="Ej: Juan" 
                                           value="<?php echo $_POST['nombre'] ?? ''; ?>"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i> Apellido *
                                    </label>
                                    <input type="text" name="apellido" class="form-control" 
                                           placeholder="Ej: Pérez" 
                                           value="<?php echo $_POST['apellido'] ?? ''; ?>"
                                           required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-envelope"></i> Email *
                                    </label>
                                    <input type="email" name="email" class="form-control" 
                                           placeholder="Ej: usuario@clinica.com" 
                                           value="<?php echo $_POST['email'] ?? ''; ?>"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-phone"></i> Teléfono
                                    </label>
                                    <input type="tel" name="telefono" class="form-control" 
                                           placeholder="Ej: 987654321" 
                                           value="<?php echo $_POST['telefono'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="card-body" style="border-top: 1px solid var(--border-color); display: flex; gap: 10px; justify-content: flex-end;">
                            <a href="usuarios.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Registrar Usuario
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        function selectRol(rol) {
            document.querySelectorAll('.rol-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
        }
    </script>
</body>
</html>