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

$usuario_id = $_GET['id'] ?? null;

if (!$usuario_id) {
    header("Location: usuarios.php");
    exit();
}

// Obtener datos del usuario
$query = "SELECT * FROM usuarios WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $usuario_id);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: usuarios.php");
    exit();
}

// Procesar formulario
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitizeInput($_POST['nombre'] ?? '');
    $apellido = sanitizeInput($_POST['apellido'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefono = sanitizeInput($_POST['telefono'] ?? '');
    $rol = $_POST['rol'] ?? '';
    $nueva_password = $_POST['nueva_password'] ?? '';
    
    // Validaciones
    if (empty($nombre) || empty($apellido) || empty($email) || empty($rol)) {
        $error = 'Por favor complete todos los campos obligatorios';
    } else {
        try {
            // Verificar si el email ya existe (excluyendo el usuario actual)
            $query_email = "SELECT COUNT(*) as total FROM usuarios WHERE email = :email AND id != :id";
            $stmt_email = $db->prepare($query_email);
            $stmt_email->execute([':email' => $email, ':id' => $usuario_id]);
            $existe_email = $stmt_email->fetch(PDO::FETCH_ASSOC);
            
            if ($existe_email['total'] > 0) {
                $error = 'El email ya está registrado por otro usuario';
            } else {
                // Actualizar usuario
                $query = "UPDATE usuarios SET 
                         nombre = :nombre,
                         apellido = :apellido,
                         email = :email,
                         telefono = :telefono,
                         rol = :rol";
                
                // Si hay nueva contraseña, incluirla
                $params = [
                    ':nombre' => $nombre,
                    ':apellido' => $apellido,
                    ':email' => $email,
                    ':telefono' => $telefono,
                    ':rol' => $rol,
                    ':id' => $usuario_id
                ];
                
                if (!empty($nueva_password)) {
                    if (strlen($nueva_password) < 6) {
                        throw new Exception('La contraseña debe tener al menos 6 caracteres');
                    }
                    $password_hash = password_hash($nueva_password, PASSWORD_BCRYPT);
                    $query .= ", password = :password";
                    $params[':password'] = $password_hash;
                }
                
                $query .= " WHERE id = :id";
                
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                
                $success = 'Usuario actualizado exitosamente';
                
                // Actualizar datos locales
                $usuario['nombre'] = $nombre;
                $usuario['apellido'] = $apellido;
                $usuario['email'] = $email;
                $usuario['telefono'] = $telefono;
                $usuario['rol'] = $rol;
                
                // Redirigir después de 2 segundos
                header("refresh:2;url=usuarios.php");
            }
            
        } catch (Exception $e) {
            $error = 'Error al actualizar el usuario: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Sistema Clínico</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
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
                        <h1 class="page-title">Editar Usuario #<?php echo $usuario_id; ?></h1>
                        <p class="page-subtitle">Modifica los datos del usuario</p>
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

                <form method="POST" action="editar-usuario.php?id=<?php echo $usuario_id; ?>">
                    <!-- Datos de Acceso -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Datos de Acceso</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user"></i> Usuario
                                </label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($usuario['username']); ?>"
                                       disabled>
                                <small style="color: var(--text-secondary);">
                                    El nombre de usuario no se puede modificar
                                </small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-lock"></i> Nueva Contraseña
                                </label>
                                <input type="password" name="nueva_password" class="form-control" 
                                       placeholder="Dejar en blanco para no cambiar">
                                <small style="color: var(--text-secondary);">
                                    Solo ingrese una contraseña si desea cambiarla (mínimo 6 caracteres)
                                </small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user-tag"></i> Rol *
                                </label>
                                <select name="rol" class="form-control" required>
                                    <option value="admin" <?php echo $usuario['rol'] === 'admin' ? 'selected' : ''; ?>>
                                        Administrador
                                    </option>
                                    <option value="medico" <?php echo $usuario['rol'] === 'medico' ? 'selected' : ''; ?>>
                                        Médico
                                    </option>
                                    <option value="recepcionista" <?php echo $usuario['rol'] === 'recepcionista' ? 'selected' : ''; ?>>
                                        Recepcionista
                                    </option>
                                </select>
                                <?php if ($usuario['rol'] === 'medico'): ?>
                                <small style="color: var(--text-secondary);">
                                    <i class="fas fa-info-circle"></i>
                                    Si cambia el rol de Médico, perderá el acceso a sus datos médicos asociados
                                </small>
                                <?php endif; ?>
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
                                           value="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i> Apellido *
                                    </label>
                                    <input type="text" name="apellido" class="form-control" 
                                           value="<?php echo htmlspecialchars($usuario['apellido']); ?>"
                                           required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-envelope"></i> Email *
                                    </label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($usuario['email']); ?>"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-phone"></i> Teléfono
                                    </label>
                                    <input type="tel" name="telefono" class="form-control" 
                                           value="<?php echo htmlspecialchars($usuario['telefono']); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="card-body" style="border-top: 1px solid var(--border-color); display: flex; gap: 10px; justify-content: flex-end;">
                            <a href="usuarios.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Usuario
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Acceso rápido a permisos -->
                <div class="card mt-2">
                    <div class="card-body" style="text-align: center;">
                        <a href="permisos-usuario.php?id=<?php echo $usuario_id; ?>" class="btn btn-success">
                            <i class="fas fa-shield-alt"></i> Gestionar Permisos de este Usuario
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>