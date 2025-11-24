<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

$usuario_id = getUserId();

// Obtener datos del usuario
$query = "SELECT * FROM usuarios WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $usuario_id);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: logout.php");
    exit();
}

// Si es médico, obtener datos adicionales
$datos_medico = null;
if ($usuario['rol'] === 'medico') {
    $query_medico = "SELECT m.*, e.nombre as especialidad
                    FROM medicos m
                    INNER JOIN especialidades e ON m.especialidad_id = e.id
                    WHERE m.usuario_id = :usuario_id";
    $stmt_medico = $db->prepare($query_medico);
    $stmt_medico->bindParam(':usuario_id', $usuario_id);
    $stmt_medico->execute();
    $datos_medico = $stmt_medico->fetch(PDO::FETCH_ASSOC);
}

// Obtener estadísticas del usuario
$stats = [];

if ($usuario['rol'] === 'medico' && $datos_medico) {
    // Estadísticas para médicos
    $query_stats = "SELECT 
                    COUNT(*) as total_citas,
                    SUM(CASE WHEN estado = 'atendida' THEN 1 ELSE 0 END) as citas_atendidas,
                    COUNT(DISTINCT paciente_id) as pacientes_unicos
                    FROM citas 
                    WHERE medico_id = :medico_id";
    $stmt_stats = $db->prepare($query_stats);
    $stmt_stats->execute([':medico_id' => $datos_medico['id']]);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
    // Historias clínicas registradas
    $query_historias = "SELECT COUNT(*) as total FROM historia_clinica WHERE medico_id = :medico_id";
    $stmt_historias = $db->prepare($query_historias);
    $stmt_historias->execute([':medico_id' => $datos_medico['id']]);
    $stats['historias'] = $stmt_historias->fetch(PDO::FETCH_ASSOC)['total'];
}

// Procesar formulario
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'actualizar_perfil') {
        $nombre = sanitizeInput($_POST['nombre'] ?? '');
        $apellido = sanitizeInput($_POST['apellido'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $telefono = sanitizeInput($_POST['telefono'] ?? '');
        
        if (empty($nombre) || empty($apellido) || empty($email)) {
            $error = 'Complete todos los campos obligatorios';
        } else {
            try {
                // Verificar si el email ya existe (excluyendo el usuario actual)
                $query_check = "SELECT COUNT(*) as total FROM usuarios WHERE email = :email AND id != :id";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([':email' => $email, ':id' => $usuario_id]);
                $existe = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if ($existe['total'] > 0) {
                    $error = 'El email ya está registrado por otro usuario';
                } else {
                    $query = "UPDATE usuarios SET 
                             nombre = :nombre,
                             apellido = :apellido,
                             email = :email,
                             telefono = :telefono
                             WHERE id = :id";
                    
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':nombre' => $nombre,
                        ':apellido' => $apellido,
                        ':email' => $email,
                        ':telefono' => $telefono,
                        ':id' => $usuario_id
                    ]);
                    
                    $success = 'Perfil actualizado correctamente';
                    
                    // Actualizar datos locales
                    $usuario['nombre'] = $nombre;
                    $usuario['apellido'] = $apellido;
                    $usuario['email'] = $email;
                    $usuario['telefono'] = $telefono;
                    
                    // Actualizar sesión
                    $_SESSION['usuario_nombre'] = $nombre . ' ' . $apellido;
                }
            } catch (Exception $e) {
                $error = 'Error al actualizar el perfil';
            }
        }
    } elseif ($accion === 'cambiar_password') {
        $password_actual = $_POST['password_actual'] ?? '';
        $password_nueva = $_POST['password_nueva'] ?? '';
        $password_confirmar = $_POST['password_confirmar'] ?? '';
        
        if (empty($password_actual) || empty($password_nueva) || empty($password_confirmar)) {
            $error = 'Complete todos los campos de contraseña';
        } elseif (strlen($password_nueva) < 6) {
            $error = 'La nueva contraseña debe tener al menos 6 caracteres';
        } elseif ($password_nueva !== $password_confirmar) {
            $error = 'Las contraseñas nuevas no coinciden';
        } else {
            // Verificar contraseña actual
            if (password_verify($password_actual, $usuario['password'])) {
                try {
                    $password_hash = password_hash($password_nueva, PASSWORD_BCRYPT);
                    
                    $query = "UPDATE usuarios SET password = :password WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':password' => $password_hash,
                        ':id' => $usuario_id
                    ]);
                    
                    $success = 'Contraseña actualizada correctamente';
                    
                    // Limpiar campos
                    $_POST = [];
                } catch (Exception $e) {
                    $error = 'Error al cambiar la contraseña';
                }
            } else {
                $error = 'La contraseña actual es incorrecta';
            }
        }
    }
}

// Calcular tiempo desde el registro
$fecha_registro = new DateTime($usuario['fecha_registro'] ?? 'now');
$ahora = new DateTime();
$tiempo_activo = $ahora->diff($fecha_registro);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Sistema Clínico</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 48px;
        }
        .profile-name {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .profile-role {
            font-size: 16px;
            opacity: 0.9;
        }
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        .stat-box {
            background: rgba(255, 255, 255, 0.15);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 12px;
            opacity: 0.9;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--background-main);
            border-radius: 20px;
            font-size: 14px;
            color: var(--text-secondary);
        }
        .password-strength {
            font-size: 12px;
            margin-top: 5px;
        }
        .password-strength.weak { color: #EF4444; }
        .password-strength.medium { color: #F59E0B; }
        .password-strength.strong { color: #10B981; }
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
                        <h1 class="page-title">Mi Perfil</h1>
                        <p class="page-subtitle">Gestiona tu información personal y configuración</p>
                    </div>
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

                <!-- Encabezado de Perfil -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas <?php echo $usuario['rol'] === 'admin' ? 'fa-user-shield' : ($usuario['rol'] === 'medico' ? 'fa-user-md' : 'fa-user-tie'); ?>"></i>
                    </div>
                    <div class="profile-name">
                        <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>
                    </div>
                    <div class="profile-role">
                        <?php 
                        $roles = [
                            'admin' => 'Administrador',
                            'medico' => 'Médico',
                            'recepcionista' => 'Recepcionista'
                        ];
                        echo $roles[$usuario['rol']] ?? 'Usuario';
                        ?>
                    </div>
                    
                    <?php if ($usuario['rol'] === 'medico' && $datos_medico): ?>
                    <div style="margin-top: 15px; font-size: 14px; opacity: 0.9;">
                        <?php echo htmlspecialchars($datos_medico['especialidad']); ?>
                        <?php if ($datos_medico['numero_colegiatura']): ?>
                        | CMP: <?php echo htmlspecialchars($datos_medico['numero_colegiatura']); ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Estadísticas del Usuario -->
                    <?php if ($usuario['rol'] === 'medico' && !empty($stats)): ?>
                    <div class="profile-stats">
                        <div class="stat-box">
                            <div class="stat-value"><?php echo $stats['total_citas']; ?></div>
                            <div class="stat-label">Total Citas</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?php echo $stats['citas_atendidas']; ?></div>
                            <div class="stat-label">Atendidas</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?php echo $stats['pacientes_unicos']; ?></div>
                            <div class="stat-label">Pacientes</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?php echo $stats['historias']; ?></div>
                            <div class="stat-label">Historias</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- Información Personal -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user"></i> Información Personal
                            </h3>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="accion" value="actualizar_perfil">
                            
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-user"></i> Nombre *
                                        </label>
                                        <input type="text" name="nombre" class="form-control" 
                                               value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-user"></i> Apellido *
                                        </label>
                                        <input type="text" name="apellido" class="form-control" 
                                               value="<?php echo htmlspecialchars($usuario['apellido']); ?>" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-envelope"></i> Email *
                                    </label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-phone"></i> Teléfono
                                    </label>
                                    <input type="tel" name="telefono" class="form-control" 
                                           value="<?php echo htmlspecialchars($usuario['telefono']); ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user-tag"></i> Usuario
                                    </label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo htmlspecialchars($usuario['username']); ?>" 
                                           disabled style="background: var(--background-main);">
                                    <small style="color: var(--text-secondary);">
                                        El nombre de usuario no se puede modificar
                                    </small>
                                </div>
                            </div>
                            
                            <div class="card-body" style="border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Cambiar Contraseña -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-lock"></i> Cambiar Contraseña
                            </h3>
                        </div>
                        <form method="POST" id="formPassword">
                            <input type="hidden" name="accion" value="cambiar_password">
                            
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-lock"></i> Contraseña Actual *
                                    </label>
                                    <div class="password-input">
                                        <input type="password" name="password_actual" id="password_actual" class="form-control" required>
                                        <button type="button" class="toggle-password" onclick="togglePassword('password_actual')">
                                            <i class="fas fa-eye" id="eye-password_actual"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-key"></i> Nueva Contraseña *
                                    </label>
                                    <div class="password-input">
                                        <input type="password" name="password_nueva" id="password_nueva" class="form-control" 
                                               minlength="6" required onkeyup="checkPasswordStrength()">
                                        <button type="button" class="toggle-password" onclick="togglePassword('password_nueva')">
                                            <i class="fas fa-eye" id="eye-password_nueva"></i>
                                        </button>
                                    </div>
                                    <div id="password-strength" class="password-strength"></div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-check-circle"></i> Confirmar Nueva Contraseña *
                                    </label>
                                    <div class="password-input">
                                        <input type="password" name="password_confirmar" id="password_confirmar" class="form-control" 
                                               minlength="6" required onkeyup="checkPasswordMatch()">
                                        <button type="button" class="toggle-password" onclick="togglePassword('password_confirmar')">
                                            <i class="fas fa-eye" id="eye-password_confirmar"></i>
                                        </button>
                                    </div>
                                    <div id="password-match" style="font-size: 12px; margin-top: 5px;"></div>
                                </div>

                                <div style="padding: 12px; background: rgba(0, 212, 212, 0.05); border-radius: 8px; font-size: 13px;">
                                    <strong>Requisitos de contraseña:</strong>
                                    <ul style="margin: 8px 0 0 20px; padding: 0;">
                                        <li>Mínimo 6 caracteres</li>
                                        <li>Se recomienda incluir mayúsculas, minúsculas y números</li>
                                        <li>Evita usar información personal</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="card-body" style="border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Cambiar Contraseña
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Información Adicional -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle"></i> Información de la Cuenta
                        </h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div>
                                <strong style="color: var(--text-secondary); font-size: 12px;">ESTADO DE LA CUENTA</strong>
                                <div style="margin-top: 8px;">
                                    <span class="badge <?php echo $usuario['estado'] === 'activo' ? 'badge-success' : 'badge-danger'; ?>" 
                                          style="font-size: 14px;">
                                        <i class="fas fa-circle"></i>
                                        <?php echo ucfirst($usuario['estado']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div>
                                <strong style="color: var(--text-secondary); font-size: 12px;">MIEMBRO DESDE</strong>
                                <div style="margin-top: 8px; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-calendar-alt" style="color: var(--primary-color);"></i>
                                    <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'] ?? 'now')); ?>
                                    <span style="color: var(--text-secondary); font-size: 12px;">
                                        (<?php 
                                        if ($tiempo_activo->y > 0) {
                                            echo $tiempo_activo->y . ' año' . ($tiempo_activo->y > 1 ? 's' : '');
                                        } elseif ($tiempo_activo->m > 0) {
                                            echo $tiempo_activo->m . ' mes' . ($tiempo_activo->m > 1 ? 'es' : '');
                                        } else {
                                            echo $tiempo_activo->d . ' día' . ($tiempo_activo->d > 1 ? 's' : '');
                                        }
                                        ?>)
                                    </span>
                                </div>
                            </div>
                            
                            <div>
                                <strong style="color: var(--text-secondary); font-size: 12px;">ÚLTIMO ACCESO</strong>
                                <div style="margin-top: 8px; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-clock" style="color: var(--primary-color);"></i>
                                    <?php 
                                    if ($usuario['ultimo_acceso']) {
                                        echo date('d/m/Y H:i', strtotime($usuario['ultimo_acceso']));
                                    } else {
                                        echo 'Este es tu primer acceso';
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <?php if ($usuario['rol'] === 'medico' && $datos_medico): ?>
                            <div>
                                <strong style="color: var(--text-secondary); font-size: 12px;">CONSULTORIO</strong>
                                <div style="margin-top: 8px; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-door-open" style="color: var(--primary-color);"></i>
                                    <?php echo htmlspecialchars($datos_medico['consultorio']); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById('eye-' + fieldId);
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('password_nueva').value;
            const strengthDiv = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthDiv.textContent = '';
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            if (strength <= 2) {
                strengthDiv.textContent = '⚠️ Contraseña débil';
                strengthDiv.className = 'password-strength weak';
            } else if (strength <= 3) {
                strengthDiv.textContent = '✓ Contraseña media';
                strengthDiv.className = 'password-strength medium';
            } else {
                strengthDiv.textContent = '✓✓ Contraseña fuerte';
                strengthDiv.className = 'password-strength strong';
            }
            
            checkPasswordMatch();
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password_nueva').value;
            const confirmar = document.getElementById('password_confirmar').value;
            const matchDiv = document.getElementById('password-match');
            
            if (confirmar.length === 0) {
                matchDiv.textContent = '';
                return;
            }
            
            if (password === confirmar) {
                matchDiv.textContent = '✓ Las contraseñas coinciden';
                matchDiv.style.color = '#10B981';
            } else {
                matchDiv.textContent = '✗ Las contraseñas no coinciden';
                matchDiv.style.color = '#EF4444';
            }
        }

        // Validar formulario antes de enviar
        document.getElementById('formPassword').addEventListener('submit', function(e) {
            const password = document.getElementById('password_nueva').value;
            const confirmar = document.getElementById('password_confirmar').value;
            
            if (password !== confirmar) {
                e.preventDefault();
                mostrarAlerta('Las contraseñas no coinciden', 'error');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                mostrarAlerta('La contraseña debe tener al menos 6 caracteres', 'error');
                return false;
            }
        });
    </script>
</body>
</html>