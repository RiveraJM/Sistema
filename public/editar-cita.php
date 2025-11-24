<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

// Verificar permisos
if (!hasPermission('gestionar_citas') && getUserRole() !== 'recepcionista') {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$cita_id = $_GET['id'] ?? null;

if (!$cita_id) {
    header("Location: citas.php");
    exit();
}

// Obtener datos de la cita
$query = "SELECT c.*, p.id as paciente_id, m.especialidad_id
          FROM citas c
          INNER JOIN pacientes p ON c.paciente_id = p.id
          INNER JOIN medicos m ON c.medico_id = m.id
          WHERE c.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $cita_id);
$stmt->execute();
$cita = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cita) {
    header("Location: citas.php");
    exit();
}

// Verificar que la cita no esté atendida o cancelada
if ($cita['estado'] === 'atendida' || $cita['estado'] === 'cancelada') {
    header("Location: citas.php");
    exit();
}

// Obtener lista de pacientes
$query_pacientes = "SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo, dni 
                    FROM pacientes 
                    WHERE estado = 'activo' 
                    ORDER BY nombre";
$stmt_pacientes = $db->query($query_pacientes);
$pacientes = $stmt_pacientes->fetchAll(PDO::FETCH_ASSOC);

// Obtener especialidades
$query_especialidades = "SELECT id, nombre FROM especialidades WHERE estado = 'activo' ORDER BY nombre";
$stmt_especialidades = $db->query($query_especialidades);
$especialidades = $stmt_especialidades->fetchAll(PDO::FETCH_ASSOC);

// Obtener médicos de la especialidad actual
$query_medicos = "SELECT m.id, CONCAT(u.nombre, ' ', u.apellido) as nombre, m.consultorio
                  FROM medicos m
                  INNER JOIN usuarios u ON m.usuario_id = u.id
                  WHERE m.especialidad_id = :especialidad_id 
                  AND m.estado = 'activo'
                  ORDER BY u.nombre";
$stmt_medicos = $db->prepare($query_medicos);
$stmt_medicos->bindParam(':especialidad_id', $cita['especialidad_id']);
$stmt_medicos->execute();
$medicos = $stmt_medicos->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paciente_id = $_POST['paciente_id'] ?? '';
    $especialidad_id = $_POST['especialidad_id'] ?? '';
    $medico_id = $_POST['medico_id'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $tipo_cita = $_POST['tipo_cita'] ?? 'consulta';
    $estado = $_POST['estado'] ?? 'programada';
    $motivo_consulta = $_POST['motivo_consulta'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';
    
    // Validaciones
    if (empty($paciente_id) || empty($medico_id) || empty($fecha) || empty($hora)) {
        $error = 'Por favor complete todos los campos obligatorios';
    } else {
        try {
            // Verificar disponibilidad (excluyendo la cita actual)
            $query_verificar = "SELECT COUNT(*) as total FROM citas 
                               WHERE medico_id = :medico_id 
                               AND fecha = :fecha 
                               AND hora = :hora 
                               AND id != :cita_id
                               AND estado NOT IN ('cancelada', 'ausente')";
            $stmt_verificar = $db->prepare($query_verificar);
            $stmt_verificar->execute([
                ':medico_id' => $medico_id,
                ':fecha' => $fecha,
                ':hora' => $hora,
                ':cita_id' => $cita_id
            ]);
            $existe = $stmt_verificar->fetch(PDO::FETCH_ASSOC);
            
            if ($existe['total'] > 0) {
                $error = 'Ya existe una cita programada para ese médico en esa fecha y hora';
            } else {
                // Actualizar cita
                $query = "UPDATE citas SET 
                         paciente_id = :paciente_id,
                         medico_id = :medico_id,
                         fecha = :fecha,
                         hora = :hora,
                         tipo_cita = :tipo_cita,
                         estado = :estado,
                         motivo_consulta = :motivo_consulta,
                         observaciones = :observaciones
                         WHERE id = :cita_id";
                
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':paciente_id' => $paciente_id,
                    ':medico_id' => $medico_id,
                    ':fecha' => $fecha,
                    ':hora' => $hora,
                    ':tipo_cita' => $tipo_cita,
                    ':estado' => $estado,
                    ':motivo_consulta' => $motivo_consulta,
                    ':observaciones' => $observaciones,
                    ':cita_id' => $cita_id
                ]);
                
                // Registrar en historial si cambió fecha, hora o médico
                if ($cita['fecha'] != $fecha || $cita['hora'] != $hora || $cita['medico_id'] != $medico_id) {
                    $query_historial = "INSERT INTO historial_citas 
                                       (cita_id, accion, fecha_anterior, hora_anterior, medico_anterior_id, 
                                        fecha_nueva, hora_nueva, medico_nuevo_id, usuario_id, motivo) 
                                       VALUES (:cita_id, 'reprogramacion', :fecha_ant, :hora_ant, :medico_ant,
                                               :fecha_new, :hora_new, :medico_new, :usuario_id, 'Edición de cita')";
                    $stmt_historial = $db->prepare($query_historial);
                    $stmt_historial->execute([
                        ':cita_id' => $cita_id,
                        ':fecha_ant' => $cita['fecha'],
                        ':hora_ant' => $cita['hora'],
                        ':medico_ant' => $cita['medico_id'],
                        ':fecha_new' => $fecha,
                        ':hora_new' => $hora,
                        ':medico_new' => $medico_id,
                        ':usuario_id' => getUserId()
                    ]);
                }
                
                $success = 'Cita actualizada exitosamente';
                
                // Actualizar datos locales
                $cita['paciente_id'] = $paciente_id;
                $cita['medico_id'] = $medico_id;
                $cita['fecha'] = $fecha;
                $cita['hora'] = $hora;
                $cita['tipo_cita'] = $tipo_cita;
                $cita['estado'] = $estado;
                $cita['motivo_consulta'] = $motivo_consulta;
                $cita['observaciones'] = $observaciones;
                
                // Redirigir después de 2 segundos
                header("refresh:2;url=citas.php");
            }
            
        } catch (Exception $e) {
            $error = 'Error al actualizar la cita: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cita - Sistema Clínico</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .horario-item {
            padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }
        .horario-item:hover {
            border-color: var(--primary-color);
            background: rgba(0, 212, 212, 0.05);
        }
        .horario-item.selected {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
        }
        .horario-item.ocupado {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f5f5f5;
        }
        .horarios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 15px;
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
                        <h1 class="page-title">Editar Cita #<?php echo $cita_id; ?></h1>
                        <p class="page-subtitle">Modifica los datos de la cita médica</p>
                    </div>
                    <a href="citas.php" class="btn btn-secondary">
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

                <form method="POST" action="editar-cita.php?id=<?php echo $cita_id; ?>" id="formEditarCita">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Datos de la Cita</h3>
                        </div>
                        <div class="card-body">
                            
                            <!-- Fila 1: Paciente -->
                            <div class="form-row">
                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i> Paciente *
                                    </label>
                                    <select name="paciente_id" id="paciente_id" class="form-control" required>
                                        <option value="">Seleccione un paciente</option>
                                        <?php foreach ($pacientes as $paciente): ?>
                                        <option value="<?php echo $paciente['id']; ?>" <?php echo $paciente['id'] == $cita['paciente_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($paciente['nombre_completo']) . ' - DNI: ' . $paciente['dni']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Fila 2: Especialidad y Médico -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-stethoscope"></i> Especialidad *
                                    </label>
                                    <select name="especialidad_id" id="especialidad_id" class="form-control" required>
                                        <option value="">Seleccione especialidad</option>
                                        <?php foreach ($especialidades as $especialidad): ?>
                                        <option value="<?php echo $especialidad['id']; ?>" <?php echo $especialidad['id'] == $cita['especialidad_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($especialidad['nombre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user-md"></i> Médico *
                                    </label>
                                    <select name="medico_id" id="medico_id" class="form-control" required>
                                        <option value="">Seleccione un médico</option>
                                        <?php foreach ($medicos as $medico): ?>
                                        <option value="<?php echo $medico['id']; ?>" <?php echo $medico['id'] == $cita['medico_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($medico['nombre']) . ' - ' . $medico['consultorio']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Fila 3: Fecha, Tipo y Estado -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-calendar"></i> Fecha *
                                    </label>
                                    <input type="date" name="fecha" id="fecha" class="form-control" 
                                           value="<?php echo $cita['fecha']; ?>"
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-clipboard-list"></i> Tipo de Cita *
                                    </label>
                                    <select name="tipo_cita" class="form-control" required>
                                        <option value="consulta" <?php echo $cita['tipo_cita'] === 'consulta' ? 'selected' : ''; ?>>Consulta</option>
                                        <option value="control" <?php echo $cita['tipo_cita'] === 'control' ? 'selected' : ''; ?>>Control</option>
                                        <option value="emergencia" <?php echo $cita['tipo_cita'] === 'emergencia' ? 'selected' : ''; ?>>Emergencia</option>
                                        <option value="otro" <?php echo $cita['tipo_cita'] === 'otro' ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-info-circle"></i> Estado *
                                    </label>
                                    <select name="estado" class="form-control" required>
                                        <option value="programada" <?php echo $cita['estado'] === 'programada' ? 'selected' : ''; ?>>Programada</option>
                                        <option value="confirmada" <?php echo $cita['estado'] === 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                                        <option value="en_atencion" <?php echo $cita['estado'] === 'en_atencion' ? 'selected' : ''; ?>>En Atención</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Horarios Disponibles -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-clock"></i> Hora * 
                                    <span style="font-weight: normal; color: var(--text-secondary);">
                                        (Actual: <?php echo date('H:i', strtotime($cita['hora'])); ?>)
                                    </span>
                                </label>
                                <div id="horariosDisponibles">
                                    <p style="color: var(--text-secondary); text-align: center; padding: 20px;">
                                        Haga clic en "Cargar Horarios" para ver disponibilidad
                                    </p>
                                </div>
                                <button type="button" onclick="cargarHorarios()" class="btn btn-secondary btn-sm" style="margin-top: 10px;">
                                    <i class="fas fa-sync-alt"></i> Cargar Horarios
                                </button>
                                <input type="hidden" name="hora" id="hora_seleccionada" value="<?php echo $cita['hora']; ?>" required>
                            </div>

                            <!-- Motivo y Observaciones -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-comment-medical"></i> Motivo de Consulta *
                                    </label>
                                    <textarea name="motivo_consulta" class="form-control" rows="3" 
                                              placeholder="Describa el motivo de la consulta..." required><?php echo htmlspecialchars($cita['motivo_consulta']); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-notes-medical"></i> Observaciones
                                    </label>
                                    <textarea name="observaciones" class="form-control" rows="3" 
                                              placeholder="Observaciones adicionales (opcional)"><?php echo htmlspecialchars($cita['observaciones']); ?></textarea>
                                </div>
                            </div>

                        </div>
                        <div class="card-body" style="border-top: 1px solid var(--border-color); display: flex; gap: 10px; justify-content: flex-end;">
                            <a href="citas.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Cita
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Cargar médicos cuando se selecciona especialidad
        document.getElementById('especialidad_id').addEventListener('change', function() {
            const especialidadId = this.value;
            const medicoSelect = document.getElementById('medico_id');
            
            if (especialidadId) {
                fetch(`get-medicos-especialidad.php?especialidad_id=${especialidadId}`)
                    .then(response => response.json())
                    .then(data => {
                        medicoSelect.innerHTML = '<option value="">Seleccione un médico</option>';
                        data.forEach(medico => {
                            const option = document.createElement('option');
                            option.value = medico.id;
                            option.textContent = medico.nombre + ' - ' + medico.consultorio;
                            medicoSelect.appendChild(option);
                        });
                    });
            }
            
            // Limpiar horarios
            document.getElementById('horariosDisponibles').innerHTML = '<p style="color: var(--text-secondary); text-align: center; padding: 20px;">Haga clic en "Cargar Horarios" para ver disponibilidad</p>';
        });

        // Cargar horarios
        function cargarHorarios() {
            const medicoId = document.getElementById('medico_id').value;
            const fecha = document.getElementById('fecha').value;
            
            if (!medicoId || !fecha) {
                mostrarAlerta('Seleccione médico y fecha primero', 'warning');
                return;
            }
            
            fetch(`get-horarios-disponibles.php?medico_id=${medicoId}&fecha=${fecha}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('horariosDisponibles');
                    const horaActual = '<?php echo date('H:i', strtotime($cita['hora'])); ?>';
                    
                    if (data.length > 0) {
                        container.innerHTML = '<div class="horarios-grid"></div>';
                        const grid = container.querySelector('.horarios-grid');
                        
                        data.forEach(horario => {
                            const div = document.createElement('div');
                            div.className = 'horario-item' + (horario.disponible ? '' : ' ocupado');
                            div.textContent = horario.hora;
                            
                            // Marcar la hora actual como seleccionada
                            if (horario.hora === horaActual) {
                                div.classList.add('selected');
                                document.getElementById('hora_seleccionada').value = horario.hora + ':00';
                            }
                            
                            if (horario.disponible || horario.hora === horaActual) {
                                div.onclick = function() {
                                    document.querySelectorAll('.horario-item').forEach(item => {
                                        item.classList.remove('selected');
                                    });
                                    this.classList.add('selected');
                                    document.getElementById('hora_seleccionada').value = horario.hora + ':00';
                                };
                            }
                            
                            grid.appendChild(div);
                        });
                    } else {
                        container.innerHTML = '<p style="color: var(--text-secondary); text-align: center; padding: 20px;">No hay horarios disponibles para esta fecha</p>';
                    }
                });
        }

        // Validación del formulario
        document.getElementById('formEditarCita').addEventListener('submit', function(e) {
            const hora = document.getElementById('hora_seleccionada').value;
            if (!hora) {
                e.preventDefault();
                mostrarAlerta('Por favor seleccione un horario', 'error');
            }
        });
    </script>
</body>
</html>