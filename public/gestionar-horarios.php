<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Permitir acceso a admin y médicos
if (!esAdmin() && !esMedico()) {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$medico_id = $_GET['medico_id'] ?? null;

if (!$medico_id) {
    header("Location: medicos.php");
    exit();
}

// Obtener datos del médico
$query = "SELECT m.*, CONCAT(u.nombre, ' ', u.apellido) as nombre_completo, e.nombre as especialidad
          FROM medicos m
          INNER JOIN usuarios u ON m.usuario_id = u.id
          INNER JOIN especialidades e ON m.especialidad_id = e.id
          WHERE m.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $medico_id);
$stmt->execute();
$medico = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$medico) {
    header("Location: medicos.php");
    exit();
}

// Obtener horarios actuales
$query_horarios = "SELECT * FROM horarios_medicos WHERE medico_id = :medico_id ORDER BY FIELD(dia_semana, 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo')";
$stmt_horarios = $db->prepare($query_horarios);
$stmt_horarios->bindParam(':medico_id', $medico_id);
$stmt_horarios->execute();
$horarios_actuales = $stmt_horarios->fetchAll(PDO::FETCH_ASSOC);

// Convertir a array asociativo por día
$horarios = [];
foreach ($horarios_actuales as $horario) {
    $horarios[$horario['dia_semana']] = $horario;
}

$dias_semana = [
    'lunes' => 'Lunes',
    'martes' => 'Martes',
    'miercoles' => 'Miércoles',
    'jueves' => 'Jueves',
    'viernes' => 'Viernes',
    'sabado' => 'Sábado',
    'domingo' => 'Domingo'
];

// Procesar formulario
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Eliminar horarios anteriores
        $query_delete = "DELETE FROM horarios_medicos WHERE medico_id = :medico_id";
        $stmt_delete = $db->prepare($query_delete);
        $stmt_delete->execute([':medico_id' => $medico_id]);
        
        // Insertar nuevos horarios
        $query_insert = "INSERT INTO horarios_medicos (medico_id, dia_semana, hora_inicio, hora_fin, cupos_por_hora, estado) 
                        VALUES (:medico_id, :dia_semana, :hora_inicio, :hora_fin, :cupos_por_hora, :estado)";
        $stmt_insert = $db->prepare($query_insert);
        
        $horarios_insertados = 0;
        
        foreach ($dias_semana as $dia_key => $dia_nombre) {
            $activo = isset($_POST['dia_' . $dia_key]);
            
            if ($activo) {
                $hora_inicio = $_POST['hora_inicio_' . $dia_key] ?? '';
                $hora_fin = $_POST['hora_fin_' . $dia_key] ?? '';
                $cupos_por_hora = $_POST['cupos_' . $dia_key] ?? 1;
                
                // Validar que las horas estén completas
                if (empty($hora_inicio) || empty($hora_fin)) {
                    throw new Exception("Complete las horas para $dia_nombre");
                }
                
                // Validar que hora_fin sea mayor que hora_inicio
                if (strtotime($hora_fin) <= strtotime($hora_inicio)) {
                    throw new Exception("La hora de fin debe ser mayor que la hora de inicio en $dia_nombre");
                }
                
                $stmt_insert->execute([
                    ':medico_id' => $medico_id,
                    ':dia_semana' => $dia_key,
                    ':hora_inicio' => $hora_inicio,
                    ':hora_fin' => $hora_fin,
                    ':cupos_por_hora' => $cupos_por_hora,
                    ':estado' => 'activo'
                ]);
                
                $horarios_insertados++;
            }
        }
        
        if ($horarios_insertados === 0) {
            throw new Exception("Debe configurar al menos un día de atención");
        }
        
        $db->commit();
        
        $success = "Horarios actualizados correctamente. Se configuraron $horarios_insertados días de atención.";
        
        // Recargar horarios
        $stmt_horarios->execute([':medico_id' => $medico_id]);
        $horarios_actuales = $stmt_horarios->fetchAll(PDO::FETCH_ASSOC);
        $horarios = [];
        foreach ($horarios_actuales as $horario) {
            $horarios[$horario['dia_semana']] = $horario;
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Horarios - Sistema Clínico</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .horario-dia {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            background: white;
        }
        .horario-dia.activo {
            border-color: var(--primary-color);
            background: rgba(0, 212, 212, 0.02);
        }
        .horario-dia.inactivo {
            opacity: 0.6;
            background: #f9f9f9;
        }
        .horario-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        .horario-header label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 16px;
            color: var(--text-primary);
            cursor: pointer;
        }
        .horario-header input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .horario-campos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        .campo-horario {
            display: flex;
            flex-direction: column;
        }
        .campo-horario label {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 5px;
            font-weight: 500;
        }
        .campo-horario input {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
        }
        .campo-horario input:disabled {
            background: #f5f5f5;
            cursor: not-allowed;
        }
        .btn-aplicar-todos {
            margin-bottom: 20px;
        }
        .quick-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
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
                        <h1 class="page-title">Gestionar Horarios de Atención</h1>
                        <p class="page-subtitle">
                            Dr(a). <?php echo htmlspecialchars($medico['nombre_completo']); ?> - 
                            <?php echo htmlspecialchars($medico['especialidad']); ?>
                        </p>
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

                <form method="POST" action="gestionar-horarios.php?medico_id=<?php echo $medico_id; ?>" id="formHorarios">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Configuración de Horarios</h3>
                        </div>
                        <div class="card-body">
                            
                            <!-- Acciones Rápidas -->
                            <div class="quick-actions">
                                <button type="button" onclick="seleccionarDiasLaborales()" class="btn btn-sm btn-primary">
                                    <i class="fas fa-calendar-check"></i> Días Laborales (L-V)
                                </button>
                                <button type="button" onclick="seleccionarTodos()" class="btn btn-sm btn-success">
                                    <i class="fas fa-check-double"></i> Todos los Días
                                </button>
                                <button type="button" onclick="deseleccionarTodos()" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-times"></i> Ninguno
                                </button>
                            </div>

                            <!-- Plantilla para aplicar a todos -->
                            <div class="card" style="margin-bottom: 20px; background: rgba(0, 212, 212, 0.05);">
                                <div class="card-body">
                                    <h4 style="margin: 0 0 15px 0; font-size: 14px; color: var(--text-primary);">
                                        <i class="fas fa-copy"></i> Aplicar mismo horario a días seleccionados
                                    </h4>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items: end;">
                                        <div class="campo-horario">
                                            <label>Hora Inicio</label>
                                            <input type="time" id="plantilla_inicio" class="form-control">
                                        </div>
                                        <div class="campo-horario">
                                            <label>Hora Fin</label>
                                            <input type="time" id="plantilla_fin" class="form-control">
                                        </div>
                                        <div class="campo-horario">
                                            <label>Cupos/Hora</label>
                                            <input type="number" id="plantilla_cupos" class="form-control" min="1" max="10" value="1">
                                        </div>
                                        <button type="button" onclick="aplicarPlantilla()" class="btn btn-warning">
                                            <i class="fas fa-paste"></i> Aplicar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Configuración por día -->
                            <?php foreach ($dias_semana as $dia_key => $dia_nombre): ?>
                            <?php 
                            $horario_dia = $horarios[$dia_key] ?? null;
                            $activo = $horario_dia !== null;
                            ?>
                            <div class="horario-dia <?php echo $activo ? 'activo' : 'inactivo'; ?>" id="dia_<?php echo $dia_key; ?>">
                                <div class="horario-header">
                                    <label>
                                        <input type="checkbox" 
                                               name="dia_<?php echo $dia_key; ?>" 
                                               id="check_<?php echo $dia_key; ?>"
                                               <?php echo $activo ? 'checked' : ''; ?>
                                               onchange="toggleDia('<?php echo $dia_key; ?>')">
                                        <span>
                                            <i class="fas fa-calendar-day"></i>
                                            <?php echo $dia_nombre; ?>
                                        </span>
                                    </label>
                                    <?php if ($activo): ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-check"></i> Configurado
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="horario-campos">
                                    <div class="campo-horario">
                                        <label>
                                            <i class="fas fa-clock"></i> Hora Inicio
                                        </label>
                                        <input type="time" 
                                               name="hora_inicio_<?php echo $dia_key; ?>" 
                                               id="hora_inicio_<?php echo $dia_key; ?>"
                                               class="form-control"
                                               value="<?php echo $horario_dia ? date('H:i', strtotime($horario_dia['hora_inicio'])) : '08:00'; ?>"
                                               <?php echo !$activo ? 'disabled' : ''; ?>>
                                    </div>
                                    
                                    <div class="campo-horario">
                                        <label>
                                            <i class="fas fa-clock"></i> Hora Fin
                                        </label>
                                        <input type="time" 
                                               name="hora_fin_<?php echo $dia_key; ?>" 
                                               id="hora_fin_<?php echo $dia_key; ?>"
                                               class="form-control"
                                               value="<?php echo $horario_dia ? date('H:i', strtotime($horario_dia['hora_fin'])) : '17:00'; ?>"
                                               <?php echo !$activo ? 'disabled' : ''; ?>>
                                    </div>
                                    
                                    <div class="campo-horario">
                                        <label>
                                            <i class="fas fa-users"></i> Cupos por Hora
                                        </label>
                                        <input type="number" 
                                               name="cupos_<?php echo $dia_key; ?>" 
                                               id="cupos_<?php echo $dia_key; ?>"
                                               class="form-control"
                                               min="1" 
                                               max="10"
                                               value="<?php echo $horario_dia ? $horario_dia['cupos_por_hora'] : 1; ?>"
                                               <?php echo !$activo ? 'disabled' : ''; ?>>
                                    </div>
                                    
                                    <div class="campo-horario">
                                        <label style="opacity: 0;">Info</label>
                                        <div style="padding: 10px; background: var(--background-main); border-radius: 8px; font-size: 12px; color: var(--text-secondary);">
                                            <?php if ($horario_dia): ?>
                                                <?php
                                                $inicio = strtotime($horario_dia['hora_inicio']);
                                                $fin = strtotime($horario_dia['hora_fin']);
                                                $duracion = ($fin - $inicio) / 3600;
                                                $slots = ceil($duracion * 60 / $medico['duracion_consulta']);
                                                $total_cupos = $slots * $horario_dia['cupos_por_hora'];
                                                ?>
                                                <div><strong><?php echo $slots; ?></strong> slots</div>
                                                <div><strong><?php echo $total_cupos; ?></strong> cupos totales</div>
                                            <?php else: ?>
                                                <div>No configurado</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <!-- Información adicional -->
                            <div style="margin-top: 20px; padding: 15px; background: rgba(0, 212, 212, 0.05); border-left: 4px solid var(--primary-color); border-radius: 8px;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                    <i class="fas fa-info-circle" style="color: var(--primary-color);"></i>
                                    <strong>Información sobre la configuración:</strong>
                                </div>
                                <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: var(--text-secondary);">
                                    <li>La duración de consulta configurada es de <strong><?php echo $medico['duracion_consulta']; ?> minutos</strong></li>
                                    <li>Los cupos por hora determinan cuántos pacientes pueden reservar en el mismo horario</li>
                                    <li>Los slots se generan automáticamente según la duración de consulta</li>
                                    <li>Puedes usar la plantilla para aplicar el mismo horario a múltiples días</li>
                                </ul>
                            </div>

                        </div>
                        <div class="card-body" style="border-top: 1px solid var(--border-color); display: flex; gap: 10px; justify-content: flex-end;">
                            <a href="medicos.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Horarios
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        const diasSemana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        
        function toggleDia(dia) {
            const checkbox = document.getElementById('check_' + dia);
            const container = document.getElementById('dia_' + dia);
            const horaInicio = document.getElementById('hora_inicio_' + dia);
            const horaFin = document.getElementById('hora_fin_' + dia);
            const cupos = document.getElementById('cupos_' + dia);
            
            if (checkbox.checked) {
                container.classList.remove('inactivo');
                container.classList.add('activo');
                horaInicio.disabled = false;
                horaFin.disabled = false;
                cupos.disabled = false;
            } else {
                container.classList.remove('activo');
                container.classList.add('inactivo');
                horaInicio.disabled = true;
                horaFin.disabled = true;
                cupos.disabled = true;
            }
        }
        
        function seleccionarDiasLaborales() {
            const diasLaborales = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
            
            diasSemana.forEach(dia => {
                const checkbox = document.getElementById('check_' + dia);
                if (diasLaborales.includes(dia)) {
                    checkbox.checked = true;
                } else {
                    checkbox.checked = false;
                }
                toggleDia(dia);
            });
        }
        
        function seleccionarTodos() {
            diasSemana.forEach(dia => {
                document.getElementById('check_' + dia).checked = true;
                toggleDia(dia);
            });
        }
        
        function deseleccionarTodos() {
            diasSemana.forEach(dia => {
                document.getElementById('check_' + dia).checked = false;
                toggleDia(dia);
            });
        }
        
        function aplicarPlantilla() {
            const plantillaInicio = document.getElementById('plantilla_inicio').value;
            const plantillaFin = document.getElementById('plantilla_fin').value;
            const plantillaCupos = document.getElementById('plantilla_cupos').value;
            
            if (!plantillaInicio || !plantillaFin) {
                mostrarAlerta('Complete todos los campos de la plantilla', 'warning');
                return;
            }
            
            if (plantillaFin <= plantillaInicio) {
                mostrarAlerta('La hora de fin debe ser mayor que la hora de inicio', 'error');
                return;
            }
            
            let diasAplicados = 0;
            
            diasSemana.forEach(dia => {
                const checkbox = document.getElementById('check_' + dia);
                if (checkbox.checked) {
                    document.getElementById('hora_inicio_' + dia).value = plantillaInicio;
                    document.getElementById('hora_fin_' + dia).value = plantillaFin;
                    document.getElementById('cupos_' + dia).value = plantillaCupos;
                    diasAplicados++;
                }
            });
            
            if (diasAplicados > 0) {
                mostrarAlerta(`Plantilla aplicada a ${diasAplicados} día(s)`, 'success');
            } else {
                mostrarAlerta('Seleccione al menos un día para aplicar la plantilla', 'warning');
            }
        }
        
        // Validación del formulario
        document.getElementById('formHorarios').addEventListener('submit', function(e) {
            let hayAlMenosUnDia = false;
            
            diasSemana.forEach(dia => {
                const checkbox = document.getElementById('check_' + dia);
                if (checkbox.checked) {
                    hayAlMenosUnDia = true;
                    
                    const horaInicio = document.getElementById('hora_inicio_' + dia).value;
                    const horaFin = document.getElementById('hora_fin_' + dia).value;
                    
                    if (!horaInicio || !horaFin) {
                        e.preventDefault();
                        mostrarAlerta('Complete las horas para todos los días seleccionados', 'error');
                        return;
                    }
                    
                    if (horaFin <= horaInicio) {
                        e.preventDefault();
                        mostrarAlerta('La hora de fin debe ser mayor que la hora de inicio en todos los días', 'error');
                        return;
                    }
                }
            });
            
            if (!hayAlMenosUnDia) {
                e.preventDefault();
                mostrarAlerta('Debe configurar al menos un día de atención', 'error');
            }
        });
    </script>
</body>
</html>