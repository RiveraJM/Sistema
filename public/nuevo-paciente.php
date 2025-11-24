<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

// Verificar permisos
if (!hasPermission('gestionar_pacientes')) {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener lista de seguros
$query_seguros = "SELECT id, nombre FROM seguros WHERE estado = 'activo' ORDER BY nombre";
$stmt_seguros = $db->query($query_seguros);
$seguros = $stmt_seguros->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = sanitizeInput($_POST['dni'] ?? '');
    $nombre = sanitizeInput($_POST['nombre'] ?? '');
    $apellido = sanitizeInput($_POST['apellido'] ?? '');
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
    $sexo = $_POST['sexo'] ?? '';
    $telefono = sanitizeInput($_POST['telefono'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $direccion = sanitizeInput($_POST['direccion'] ?? '');
    $seguro_id = $_POST['seguro_id'] ?? null;
    $numero_poliza = sanitizeInput($_POST['numero_poliza'] ?? '');
    
    // Validaciones
    if (empty($dni) || empty($nombre) || empty($apellido) || empty($fecha_nacimiento) || empty($sexo)) {
        $error = 'Por favor complete todos los campos obligatorios';
    } else {
        try {
            // Verificar si el DNI ya existe
            $query_verificar = "SELECT COUNT(*) as total FROM pacientes WHERE dni = :dni";
            $stmt_verificar = $db->prepare($query_verificar);
            $stmt_verificar->execute([':dni' => $dni]);
            $existe = $stmt_verificar->fetch(PDO::FETCH_ASSOC);
            
            if ($existe['total'] > 0) {
                $error = 'Ya existe un paciente registrado con ese DNI';
            } else {
                // Insertar paciente
                $query = "INSERT INTO pacientes 
                         (dni, nombre, apellido, fecha_nacimiento, sexo, telefono, email, direccion, seguro_id, numero_poliza, estado) 
                         VALUES (:dni, :nombre, :apellido, :fecha_nacimiento, :sexo, :telefono, :email, :direccion, :seguro_id, :numero_poliza, 'activo')";
                
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':dni' => $dni,
                    ':nombre' => $nombre,
                    ':apellido' => $apellido,
                    ':fecha_nacimiento' => $fecha_nacimiento,
                    ':sexo' => $sexo,
                    ':telefono' => $telefono,
                    ':email' => $email,
                    ':direccion' => $direccion,
                    ':seguro_id' => $seguro_id ?: null,
                    ':numero_poliza' => $numero_poliza
                ]);
                
                $paciente_id = $db->lastInsertId();
                
                $success = 'Paciente registrado exitosamente';
                
                // Limpiar formulario
                $_POST = [];
                
                // Redirigir después de 2 segundos
                header("refresh:2;url=pacientes.php");
            }
            
        } catch (Exception $e) {
            $error = 'Error al registrar el paciente: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Paciente - Sistema Clínico</title>
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
                        <h1 class="page-title">Nuevo Paciente</h1>
                        <p class="page-subtitle">Registra un nuevo paciente en el sistema</p>
                    </div>
                    <a href="pacientes.php" class="btn btn-secondary">
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

                <form method="POST" action="nuevo-paciente.php" id="formNuevoPaciente">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Datos Personales</h3>
                        </div>
                        <div class="card-body">
                            
                            <!-- Fila 1: DNI, Nombre, Apellido -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-id-card"></i> DNI *
                                    </label>
                                    <input type="text" name="dni" class="form-control" 
                                           placeholder="Ej: 72345678" 
                                           maxlength="20" 
                                           value="<?php echo $_POST['dni'] ?? ''; ?>"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i> Nombre *
                                    </label>
                                    <input type="text" name="nombre" class="form-control" 
                                           placeholder="Ej: Carlos" 
                                           value="<?php echo $_POST['nombre'] ?? ''; ?>"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i> Apellido *
                                    </label>
                                    <input type="text" name="apellido" class="form-control" 
                                           placeholder="Ej: Rodríguez" 
                                           value="<?php echo $_POST['apellido'] ?? ''; ?>"
                                           required>
                                </div>
                            </div>

                            <!-- Fila 2: Fecha Nacimiento, Sexo -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-birthday-cake"></i> Fecha de Nacimiento *
                                    </label>
                                    <input type="date" name="fecha_nacimiento" class="form-control" 
                                           max="<?php echo date('Y-m-d'); ?>"
                                           value="<?php echo $_POST['fecha_nacimiento'] ?? ''; ?>"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-venus-mars"></i> Sexo *
                                    </label>
                                    <select name="sexo" class="form-control" required>
                                        <option value="">Seleccione</option>
                                        <option value="M" <?php echo (($_POST['sexo'] ?? '') === 'M') ? 'selected' : ''; ?>>Masculino</option>
                                        <option value="F" <?php echo (($_POST['sexo'] ?? '') === 'F') ? 'selected' : ''; ?>>Femenino</option>
                                    </select>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card mt-2">
                        <div class="card-header">
                            <h3 class="card-title">Datos de Contacto</h3>
                        </div>
                        <div class="card-body">
                            
                            <!-- Fila 3: Teléfono, Email -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-phone"></i> Teléfono
                                    </label>
                                    <input type="tel" name="telefono" class="form-control" 
                                           placeholder="Ej: 987654321" 
                                           value="<?php echo $_POST['telefono'] ?? ''; ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-envelope"></i> Email
                                    </label>
                                    <input type="email" name="email" class="form-control" 
                                           placeholder="Ej: correo@example.com" 
                                           value="<?php echo $_POST['email'] ?? ''; ?>">
                                </div>
                            </div>

                            <!-- Fila 4: Dirección -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-map-marker-alt"></i> Dirección
                                </label>
                                <textarea name="direccion" class="form-control" rows="2" 
                                          placeholder="Ej: Av. Arequipa 1234, Lima"><?php echo $_POST['direccion'] ?? ''; ?></textarea>
                            </div>

                        </div>
                    </div>

                    <div class="card mt-2">
                        <div class="card-header">
                            <h3 class="card-title">Información del Seguro</h3>
                        </div>
                        <div class="card-body">
                            
                            <!-- Fila 5: Seguro, Número de Póliza -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-shield-alt"></i> Seguro Médico
                                    </label>
                                    <select name="seguro_id" id="seguro_id" class="form-control">
                                        <option value="">Sin seguro</option>
                                        <?php foreach ($seguros as $seguro): ?>
                                        <option value="<?php echo $seguro['id']; ?>" 
                                                <?php echo (($_POST['seguro_id'] ?? '') == $seguro['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($seguro['nombre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-file-contract"></i> Número de Póliza
                                    </label>
                                    <input type="text" name="numero_poliza" id="numero_poliza" class="form-control" 
                                           placeholder="Ej: ES-123456" 
                                           value="<?php echo $_POST['numero_poliza'] ?? ''; ?>">
                                </div>
                            </div>

                        </div>
                        <div class="card-body" style="border-top: 1px solid var(--border-color); display: flex; gap: 10px; justify-content: flex-end;">
                            <a href="pacientes.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Registrar Paciente
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Habilitar/deshabilitar número de póliza según seguro
        document.getElementById('seguro_id').addEventListener('change', function() {
            const numeroPoliza = document.getElementById('numero_poliza');
            if (this.value) {
                numeroPoliza.removeAttribute('disabled');
            } else {
                numeroPoliza.value = '';
            }
        });

        // Validación de DNI (solo números)
        document.querySelector('input[name="dni"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Validación de teléfono (solo números)
        document.querySelector('input[name="telefono"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>