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

// Obtener lista de especialidades
$query_especialidades = "SELECT id, nombre FROM especialidades WHERE estado = 'activo' ORDER BY nombre";
$stmt_especialidades = $db->query($query_especialidades);
$especialidades = $stmt_especialidades->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Datos del usuario
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nombre = sanitizeInput($_POST['nombre'] ?? '');
    $apellido = sanitizeInput($_POST['apellido'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefono = sanitizeInput($_POST['telefono'] ?? '');
    
    // Datos del médico
    $especialidad_id = $_POST['especialidad_id'] ?? '';
    $numero_colegiatura = sanitizeInput($_POST['numero_colegiatura'] ?? '');
    $consultorio = sanitizeInput($_POST['consultorio'] ?? '');
    $duracion_consulta = $_POST['duracion_consulta'] ?? 30;
    
    // Validaciones
    if (empty($username) || empty($password) || empty($nombre) || empty($apellido) || empty($email) || 
        empty($especialidad_id) || empty($numero_colegiatura) || empty($consultorio)) {
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
                    // Verificar si el número de colegiatura ya existe
                    $query_col = "SELECT COUNT(*) as total FROM medicos WHERE numero_colegiatura = :numero";
                    $stmt_col = $db->prepare($query_col);
                    $stmt_col->execute([':numero' => $numero_colegiatura]);
                    $existe_col = $stmt_col->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existe_col['total'] > 0) {
                        $error = 'El número de colegiatura ya está registrado';
                    } else {
                        // Iniciar transacción
                        $db->beginTransaction();
                        
                        try {
                            // Insertar usuario
                            $password_hash = password_hash($password, PASSWORD_BCRYPT);
                            
                            $query_usuario = "INSERT INTO usuarios 
                                            (username, password, nombre, apellido, email, telefono, rol, estado) 
                                            VALUES (:username, :password, :nombre, :apellido, :email, :telefono, 'medico', 'activo')";
                            
                            $stmt_usuario = $db->prepare($query_usuario);
                            $stmt_usuario->execute([
                                ':username' => $username,
                                ':password' => $password_hash,
                                ':nombre' => $nombre,
                                ':apellido' => $apellido,
                                ':email' => $email,
                                ':telefono' => $telefono
                            ]);
                            
                            $usuario_id = $db->lastInsertId();
                            
                            // Insertar médico
                            $query_medico = "INSERT INTO medicos 
                                           (usuario_id, especialidad_id, numero_colegiatura, consultorio, duracion_consulta, estado) 
                                           VALUES (:usuario_id, :especialidad_id, :numero_colegiatura, :consultorio, :duracion_consulta, 'activo')";
                            
                            $stmt_medico = $db->prepare($query_medico);
                            $stmt_medico->execute([
                                ':usuario_id' => $usuario_id,
                                ':especialidad_id' => $especialidad_id,
                                ':numero_colegiatura' => $numero_colegiatura,
                                ':consultorio' => $consultorio,
                                ':duracion_consulta' => $duracion_consulta
                            ]);
                            
                            $medico_id = $db->lastInsertId();
                            
                            // Asignar permisos básicos a médicos
                            $permisos_medico = [1, 3, 5, 10, 11]; // ver_dashboard, ver_citas, ver_pacientes, ver_historia_clinica, editar_historia_clinica
                            
                            $query_permisos = "INSERT INTO usuario_permisos (usuario_id, permiso_id) VALUES (:usuario_id, :permiso_id)";
                            $stmt_permisos = $db->prepare($query_permisos);
                            
                            foreach ($permisos_medico as $permiso_id) {
                                $stmt_permisos->execute([
                                    ':usuario_id' => $usuario_id,
                                    ':permiso_id' => $permiso_id
                                ]);
                            }
                            
                            // Confirmar transacción
                            $db->commit();
                            
                            $success = 'Médico registrado exitosamente';
                            
                            // Limpiar formulario
                            $_POST = [];
                            
                            // Redirigir después de 2 segundos
                            header("refresh:2;url=medicos.php");
                            
                        } catch (Exception $e) {
                            $db->rollBack();
                            throw $e;
                        }
                    }
                }
            }
            
        } catch (Exception $e) {
            $error = 'Error al registrar el médico: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Médico - Sistema Clínico</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
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
                        <h1 class="page-title">Nuevo Médico</h1>
                        <p class="page-subtitle">Registra un nuevo médico en el sistema</p>
                    </div>
                    <a href="medicos.php" class="btn btn-secondary">
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

                <form method="POST" action="nuevo-medico.php" id="formNuevoMedico">
                    <!-- Datos de Usuario -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Datos de Acceso al Sistema</h3>
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
                                    <small style="color: var(--text-secondary);">
                                        Usuario para iniciar sesión en el sistema
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-lock"></i> Contraseña *
                                    </label>
                                    <div class="password-input">
                                        <input type="password" name="password" id="password" class="form-control" 
                                               placeholder="Mínimo 6 caracteres" 
                                               required>
                                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')">
                                            <i class="fas fa-eye" id="eye-icon-password"></i>
                                        </button>
                                    </div>
                                    <div id="password-strength" class="password-strength"></div>
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
                                           placeholder="Ej: juan.perez@clinica.com" 
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
                    </div>

                    <!-- Datos Profesionales -->
                    <div class="card mt-2">
                        <div class="card-header">
                            <h3 class="card-title">Información Profesional</h3>
                        </div>
                        <div class="card-body">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-stethoscope"></i> Especialidad *
                                    </label>
                                    <select name="especialidad_id" class="form-control" required>
                                        <option value="">Seleccione especialidad</option>
                                        <?php foreach ($especialidades as $especialidad): ?>
                                        <option value="<?php echo $especialidad['id']; ?>" 
                                                <?php echo (($_POST['especialidad_id'] ?? '') == $especialidad['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($especialidad['nombre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-id-card"></i> N° Colegiatura *
                                    </label>
                                    <input type="text" name="numero_colegiatura" class="form-control" 
                                           placeholder="Ej: CMP-12345" 
                                           value="<?php echo $_POST['numero_colegiatura'] ?? ''; ?>"
                                           required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-door-open"></i> Consultorio *
                                    </label>
                                    <input type="text" name="consultorio" class="form-control" 
                                           placeholder="Ej: Consultorio 101" 
                                           value="<?php echo $_POST['consultorio'] ?? ''; ?>"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-clock"></i> Duración Consulta (minutos) *
                                    </label>
                                    <select name="duracion_consulta" class="form-control" required>
                                        <option value="15" <?php echo (($_POST['duracion_consulta'] ?? 30) == 15) ? 'selected' : ''; ?>>15 minutos</option>
                                        <option value="20" <?php echo (($_POST['duracion_consulta'] ?? 30) == 20) ? 'selected' : ''; ?>>20 minutos</option>
                                        <option value="30" <?php echo (($_POST['duracion_consulta'] ?? 30) == 30) ? 'selected' : ''; ?>>30 minutos</option>
                                        <option value="45" <?php echo (($_POST['duracion_consulta'] ?? 30) == 45) ? 'selected' : ''; ?>>45 minutos</option>
                                        <option value="60" <?php echo (($_POST['duracion_consulta'] ?? 30) == 60) ? 'selected' : ''; ?>>60 minutos</option>
                                    </select>
                                </div>
                            </div>

                        </div>
                        <div class="card-body" style="border-top: 1px solid var(--border-color); display: flex; gap: 10px; justify-content: flex-end;">
                            <a href="medicos.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Registrar Médico
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Toggle password visibility
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById('eye-icon-' + inputId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
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
                strengthDiv.textContent = '✓ Contraseña fuerte';
                strengthDiv.className = 'password-strength strong';
            }
        });

        // Validación de teléfono (solo números)
        document.querySelector('input[name="telefono"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>