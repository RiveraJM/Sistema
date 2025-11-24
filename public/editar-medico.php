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

$medico_id = $_GET['id'] ?? null;

if (!$medico_id) {
    header("Location: medicos.php");
    exit();
}

// Obtener datos del médico
$query = "SELECT m.*, u.username, u.nombre, u.apellido, u.email, u.telefono
          FROM medicos m
          INNER JOIN usuarios u ON m.usuario_id = u.id
          WHERE m.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $medico_id);
$stmt->execute();
$medico = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$medico) {
    header("Location: medicos.php");
    exit();
}

// Obtener lista de especialidades
$query_especialidades = "SELECT id, nombre FROM especialidades WHERE estado = 'activo' ORDER BY nombre";
$stmt_especialidades = $db->query($query_especialidades);
$especialidades = $stmt_especialidades->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitizeInput($_POST['nombre'] ?? '');
    $apellido = sanitizeInput($_POST['apellido'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefono = sanitizeInput($_POST['telefono'] ?? '');
    $especialidad_id = $_POST['especialidad_id'] ?? '';
    $numero_colegiatura = sanitizeInput($_POST['numero_colegiatura'] ?? '');
    $consultorio = sanitizeInput($_POST['consultorio'] ?? '');
    $duracion_consulta = $_POST['duracion_consulta'] ?? 30;
    $nueva_password = $_POST['nueva_password'] ?? '';
    
    // Validaciones
    if (empty($nombre) || empty($apellido) || empty($email) || empty($especialidad_id) || 
        empty($numero_colegiatura) || empty($consultorio)) {
        $error = 'Por favor complete todos los campos obligatorios';
    } else {
        try {
            // Verificar si el email ya existe (excluyendo el médico actual)
            $query_email = "SELECT COUNT(*) as total FROM usuarios WHERE email = :email AND id != :user_id";
            $stmt_email = $db->prepare($query_email);
            $stmt_email->execute([':email' => $email, ':user_id' => $medico['usuario_id']]);
            $existe_email = $stmt_email->fetch(PDO::FETCH_ASSOC);
            
            if ($existe_email['total'] > 0) {
                $error = 'El email ya está registrado por otro usuario';
            } else {
                // Verificar si el número de colegiatura ya existe (excluyendo el médico actual)
                $query_col = "SELECT COUNT(*) as total FROM medicos WHERE numero_colegiatura = :numero AND id != :medico_id";
                $stmt_col = $db->prepare($query_col);
                $stmt_col->execute([':numero' => $numero_colegiatura, ':medico_id' => $medico_id]);
                $existe_col = $stmt_col->fetch(PDO::FETCH_ASSOC);
                
                if ($existe_col['total'] > 0) {
                    $error = 'El número de colegiatura ya está registrado';
                } else {
                    // Iniciar transacción
                    $db->beginTransaction();
                    
                    try {
                        // Actualizar usuario
                        $query_usuario = "UPDATE usuarios SET 
                                        nombre = :nombre,
                                        apellido = :apellido,
                                        email = :email,
                                        telefono = :telefono";
                        
                        // Si hay nueva contraseña, incluirla
                        if (!empty($nueva_password)) {
                            if (strlen($nueva_password) < 6) {
                                throw new Exception('La contraseña debe tener al menos 6 caracteres');
                            }
                            $password_hash = password_hash($nueva_password, PASSWORD_BCRYPT);
                            $query_usuario .= ", password = :password";
                        }
                        
                        $query_usuario .= " WHERE id = :usuario_id";
                        
                        $stmt_usuario = $db->prepare($query_usuario);
                        $params_usuario = [
                            ':nombre' => $nombre,
                            ':apellido' => $apellido,
                            ':email' => $email,
                            ':telefono' => $telefono,
                            ':usuario_id' => $medico['usuario_id']
                        ];
                        
                        if (!empty($nueva_password)) {
                            $params_usuario[':password'] = $password_hash;
                        }
                        
                        $stmt_usuario->execute($params_usuario);
                        
                        // Actualizar médico
                        $query_medico = "UPDATE medicos SET 
                                       especialidad_id = :especialidad_id,
                                       numero_colegiatura = :numero_colegiatura,
                                       consultorio = :consultorio,
                                       duracion_consulta = :duracion_consulta
                                       WHERE id = :medico_id";
                        
                        $stmt_medico = $db->prepare($query_medico);
                        $stmt_medico->execute([
                            ':especialidad_id' => $especialidad_id,
                            ':numero_colegiatura' => $numero_colegiatura,
                            ':consultorio' => $consultorio,
                            ':duracion_consulta' => $duracion_consulta,
                            ':medico_id' => $medico_id
                        ]);
                        
                        // Confirmar transacción
                        $db->commit();
                        
                        $success = 'Médico actualizado exitosamente';
                        
                        // Actualizar datos locales
                        $medico['nombre'] = $nombre;
                        $medico['apellido'] = $apellido;
                        $medico['email'] = $email;
                        $medico['telefono'] = $telefono;
                        $medico['especialidad_id'] = $especialidad_id;
                        $medico['numero_colegiatura'] = $numero_colegiatura;
                        $medico['consultorio'] = $consultorio;
                        $medico['duracion_consulta'] = $duracion_consulta;
                        
                        // Redirigir después de 2 segundos
                        header("refresh:2;url=medicos.php");
                        
                    } catch (Exception $e) {
                        $db->rollBack();
                        throw $e;
                    }
                }
            }
            
        } catch (Exception $e) {
            $error = 'Error al actualizar el médico: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Médico - Sistema Clínico</title>
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
                        <h1 class="page-title">Editar Médico #<?php echo $medico_id; ?></h1>
                        <p class="page-subtitle">Modifica los datos del médico</p>
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

                <form method="POST" action="editar-medico.php?id=<?php echo $medico_id; ?>">
                    <!-- Datos de Usuario -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Datos de Acceso al Sistema</h3>
                        </div>
                        <div class="card-body">
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user"></i> Usuario
                                </label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($medico['username']); ?>"
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
                                           value="<?php echo htmlspecialchars($medico['nombre']); ?>"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i> Apellido *
                                    </label>
                                    <input type="text" name="apellido" class="form-control" 
                                           value="<?php echo htmlspecialchars($medico['apellido']); ?>"
                                           required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-envelope"></i> Email *
                                    </label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($medico['email']); ?>"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-phone"></i> Teléfono
                                    </label>
                                    <input type="tel" name="telefono" class="form-control" 
                                           value="<?php echo htmlspecialchars($medico['telefono']); ?>">
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
                                                <?php echo $medico['especialidad_id'] == $especialidad['id'] ? 'selected' : ''; ?>>
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
                                           value="<?php echo htmlspecialchars($medico['numero_colegiatura']); ?>"
                                           required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-door-open"></i> Consultorio *
                                    </label>
                                    <input type="text" name="consultorio" class="form-control" 
                                           value="<?php echo htmlspecialchars($medico['consultorio']); ?>"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-clock"></i> Duración Consulta (minutos) *
                                    </label>
                                    <select name="duracion_consulta" class="form-control" required>
                                        <option value="15" <?php echo $medico['duracion_consulta'] == 15 ? 'selected' : ''; ?>>15 minutos</option>
                                        <option value="20" <?php echo $medico['duracion_consulta'] == 20 ? 'selected' : ''; ?>>20 minutos</option>
                                        <option value="30" <?php echo $medico['duracion_consulta'] == 30 ? 'selected' : ''; ?>>30 minutos</option>
                                        <option value="45" <?php echo $medico['duracion_consulta'] == 45 ? 'selected' : ''; ?>>45 minutos</option>
                                        <option value="60" <?php echo $medico['duracion_consulta'] == 60 ? 'selected' : ''; ?>>60 minutos</option>
                                    </select>
                                </div>
                            </div>

                        </div>
                        <div class="card-body" style="border-top: 1px solid var(--border-color); display: flex; gap: 10px; justify-content: flex-end;">
                            <a href="medicos.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Médico
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Botón para gestionar horarios -->
                <div class="card mt-2">
                    <div class="card-body" style="text-align: center;">
                        <a href="gestionar-horarios.php?medico_id=<?php echo $medico_id; ?>" class="btn btn-success">
                            <i class="fas fa-clock"></i> Gestionar Horarios de Atención
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>