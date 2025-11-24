<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

// Verificar permisos
if (!hasPermission('editar_historia_clinica')) {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$paciente_id = $_GET['paciente_id'] ?? null;
$cita_id = $_GET['cita_id'] ?? null;

if (!$paciente_id) {
    header("Location: pacientes.php");
    exit();
}

// Obtener datos del paciente
$query = "SELECT * FROM pacientes WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $paciente_id);
$stmt->execute();
$paciente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$paciente) {
    header("Location: pacientes.php");
    exit();
}

// Calcular edad
$nacimiento = new DateTime($paciente['fecha_nacimiento']);
$hoy = new DateTime();
$edad = $hoy->diff($nacimiento)->y;

// Obtener ID del médico (usuario actual)
$usuario_id = getUserId();
$query_medico = "SELECT id FROM medicos WHERE usuario_id = :usuario_id";
$stmt_medico = $db->prepare($query_medico);
$stmt_medico->execute([':usuario_id' => $usuario_id]);
$medico = $stmt_medico->fetch(PDO::FETCH_ASSOC);

if (!$medico) {
    $error = 'Solo los médicos pueden registrar historias clínicas';
}

$medico_id = $medico['id'] ?? null;

// Obtener última historia para prellenar antecedentes
$query_ultima = "SELECT * FROM historia_clinica 
                 WHERE paciente_id = :paciente_id 
                 ORDER BY fecha_consulta DESC, fecha_creacion DESC 
                 LIMIT 1";
$stmt_ultima = $db->prepare($query_ultima);
$stmt_ultima->bindParam(':paciente_id', $paciente_id);
$stmt_ultima->execute();
$ultima_historia = $stmt_ultima->fetch(PDO::FETCH_ASSOC);

// Obtener últimos signos vitales
$signos_previos = null;
if ($ultima_historia) {
    $query_signos = "SELECT * FROM signos_vitales WHERE historia_id = :historia_id ORDER BY fecha_registro DESC LIMIT 1";
    $stmt_signos = $db->prepare($query_signos);
    $stmt_signos->execute([':historia_id' => $ultima_historia['id']]);
    $signos_previos = $stmt_signos->fetch(PDO::FETCH_ASSOC);
}

// Procesar formulario
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $medico_id) {
    $fecha_consulta = $_POST['fecha_consulta'] ?? date('Y-m-d');
    $motivo_consulta = sanitizeInput($_POST['motivo_consulta'] ?? '');
    $antecedentes_personales = sanitizeInput($_POST['antecedentes_personales'] ?? '');
    $antecedentes_familiares = sanitizeInput($_POST['antecedentes_familiares'] ?? '');
    $alergias = sanitizeInput($_POST['alergias'] ?? '');
    $medicamentos_actuales = sanitizeInput($_POST['medicamentos_actuales'] ?? '');
    
    // Signos vitales
    $presion_arterial = sanitizeInput($_POST['presion_arterial'] ?? '');
    $frecuencia_cardiaca = $_POST['frecuencia_cardiaca'] ?? null;
    $temperatura = $_POST['temperatura'] ?? null;
    $frecuencia_respiratoria = $_POST['frecuencia_respiratoria'] ?? null;
    $saturacion_oxigeno = $_POST['saturacion_oxigeno'] ?? null;
    $peso = $_POST['peso'] ?? null;
    $talla = $_POST['talla'] ?? null;
    
    // Calcular IMC si hay peso y talla
    $imc = null;
    if ($peso && $talla) {
        $imc = round($peso / ($talla * $talla), 2);
    }
    
    // Exámenes físicos
    $examenes_fisicos = $_POST['examenes_fisicos'] ?? [];
    
    // Diagnósticos
    $diagnosticos = $_POST['diagnosticos'] ?? [];
    
    // Tratamientos
    $tratamientos = $_POST['tratamientos'] ?? [];
    
    // Validaciones
    if (empty($motivo_consulta)) {
        $error = 'El motivo de consulta es obligatorio';
    } else {
        try {
            $db->beginTransaction();
            
            // Insertar historia clínica
            $query = "INSERT INTO historia_clinica 
                     (paciente_id, cita_id, medico_id, fecha_consulta, motivo_consulta, 
                      antecedentes_personales, antecedentes_familiares, alergias, medicamentos_actuales) 
                     VALUES (:paciente_id, :cita_id, :medico_id, :fecha_consulta, :motivo_consulta,
                             :antecedentes_personales, :antecedentes_familiares, :alergias, :medicamentos_actuales)";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':paciente_id' => $paciente_id,
                ':cita_id' => $cita_id,
                ':medico_id' => $medico_id,
                ':fecha_consulta' => $fecha_consulta,
                ':motivo_consulta' => $motivo_consulta,
                ':antecedentes_personales' => $antecedentes_personales,
                ':antecedentes_familiares' => $antecedentes_familiares,
                ':alergias' => $alergias,
                ':medicamentos_actuales' => $medicamentos_actuales
            ]);
            
            $historia_id = $db->lastInsertId();
            
            // Insertar signos vitales
            $query_signos = "INSERT INTO signos_vitales 
                            (historia_id, presion_arterial, frecuencia_cardiaca, temperatura, 
                             frecuencia_respiratoria, saturacion_oxigeno, peso, talla, imc) 
                            VALUES (:historia_id, :presion_arterial, :frecuencia_cardiaca, :temperatura,
                                    :frecuencia_respiratoria, :saturacion_oxigeno, :peso, :talla, :imc)";
            
            $stmt_signos = $db->prepare($query_signos);
            $stmt_signos->execute([
                ':historia_id' => $historia_id,
                ':presion_arterial' => $presion_arterial ?: null,
                ':frecuencia_cardiaca' => $frecuencia_cardiaca ?: null,
                ':temperatura' => $temperatura ?: null,
                ':frecuencia_respiratoria' => $frecuencia_respiratoria ?: null,
                ':saturacion_oxigeno' => $saturacion_oxigeno ?: null,
                ':peso' => $peso ?: null,
                ':talla' => $talla ?: null,
                ':imc' => $imc
            ]);
            
            // Insertar exámenes físicos
            if (!empty($examenes_fisicos)) {
                $query_examen = "INSERT INTO examenes_fisicos (historia_id, sistema, hallazgos) 
                                VALUES (:historia_id, :sistema, :hallazgos)";
                $stmt_examen = $db->prepare($query_examen);
                
                foreach ($examenes_fisicos as $examen) {
                    if (!empty($examen['sistema']) && !empty($examen['hallazgos'])) {
                        $stmt_examen->execute([
                            ':historia_id' => $historia_id,
                            ':sistema' => sanitizeInput($examen['sistema']),
                            ':hallazgos' => sanitizeInput($examen['hallazgos'])
                        ]);
                    }
                }
            }
            
            // Insertar diagnósticos
            if (!empty($diagnosticos)) {
                $query_diagnostico = "INSERT INTO diagnosticos (historia_id, codigo_cie10, diagnostico, tipo) 
                                     VALUES (:historia_id, :codigo_cie10, :diagnostico, :tipo)";
                $stmt_diagnostico = $db->prepare($query_diagnostico);
                
                foreach ($diagnosticos as $diagnostico) {
                    if (!empty($diagnostico['diagnostico'])) {
                        $stmt_diagnostico->execute([
                            ':historia_id' => $historia_id,
                            ':codigo_cie10' => sanitizeInput($diagnostico['codigo_cie10'] ?? ''),
                            ':diagnostico' => sanitizeInput($diagnostico['diagnostico']),
                            ':tipo' => $diagnostico['tipo'] ?? 'presuntivo'
                        ]);
                    }
                }
            }
            
            // Insertar tratamientos
            if (!empty($tratamientos)) {
                $query_tratamiento = "INSERT INTO tratamientos 
                                     (historia_id, medicamento, dosis, frecuencia, duracion, indicaciones) 
                                     VALUES (:historia_id, :medicamento, :dosis, :frecuencia, :duracion, :indicaciones)";
                $stmt_tratamiento = $db->prepare($query_tratamiento);
                
                foreach ($tratamientos as $tratamiento) {
                    if (!empty($tratamiento['medicamento'])) {
                        $stmt_tratamiento->execute([
                            ':historia_id' => $historia_id,
                            ':medicamento' => sanitizeInput($tratamiento['medicamento']),
                            ':dosis' => sanitizeInput($tratamiento['dosis'] ?? ''),
                            ':frecuencia' => sanitizeInput($tratamiento['frecuencia'] ?? ''),
                            ':duracion' => sanitizeInput($tratamiento['duracion'] ?? ''),
                            ':indicaciones' => sanitizeInput($tratamiento['indicaciones'] ?? '')
                        ]);
                    }
                }
            }
            
            // Si viene de una cita, actualizar estado
            if ($cita_id) {
                $query_cita = "UPDATE citas SET estado = 'atendida' WHERE id = :cita_id";
                $stmt_cita = $db->prepare($query_cita);
                $stmt_cita->execute([':cita_id' => $cita_id]);
            }
            
            // Crear notificación para el paciente (si tiene usuario)
            // TODO: Implementar cuando tengamos módulo de pacientes con usuarios
            
            $db->commit();
            
            $success = 'Historia clínica registrada exitosamente';
            
            // Redirigir después de 2 segundos
            header("refresh:2;url=detalle-historia.php?id=$historia_id");
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Error al registrar la historia clínica: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Consulta - Sistema Clínico</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-section {
            margin-bottom: 30px;
        }
        .form-section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .dynamic-list {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            background: var(--background-main);
        }
        .dynamic-item {
            display: grid;
            gap: 10px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            margin-bottom: 10px;
            position: relative;
        }
        .btn-remove-item {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #EF4444;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .signos-vitales-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
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
                        <h1 class="page-title">Nueva Consulta Médica</h1>
                        <p class="page-subtitle">
                            Paciente: <strong><?php echo htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellido']); ?></strong>
                            - <?php echo $edad; ?> años
                        </p>
                    </div>
                    <a href="historia-clinica.php?paciente_id=<?php echo $paciente_id; ?>" class="btn btn-secondary">
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

                <?php if (!$medico_id): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Solo los médicos pueden registrar historias clínicas
                </div>
                <?php else: ?>

                <form method="POST" id="formHistoria">
                    <!-- DATOS BÁSICOS -->
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title">Datos de la Consulta</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-calendar"></i> Fecha de Consulta *
                                    </label>
                                    <input type="date" name="fecha_consulta" class="form-control" 
                                           value="<?php echo date('Y-m-d'); ?>" 
                                           max="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-comment-medical"></i> Motivo de Consulta *
                                </label>
                                <textarea name="motivo_consulta" class="form-control" rows="3" 
                                          placeholder="Describa el motivo de la consulta..." required></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- ANTECEDENTES -->
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title">Antecedentes Médicos</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user-check"></i> Antecedentes Personales
                                    </label>
                                    <textarea name="antecedentes_personales" class="form-control" rows="3"
                                              placeholder="Enfermedades previas, cirugías, hospitalizaciones..."><?php echo htmlspecialchars($ultima_historia['antecedentes_personales'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-users"></i> Antecedentes Familiares
                                    </label>
                                    <textarea name="antecedentes_familiares" class="form-control" rows="3"
                                              placeholder="Enfermedades familiares..."><?php echo htmlspecialchars($ultima_historia['antecedentes_familiares'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" style="color: #EF4444;">
                                        <i class="fas fa-exclamation-triangle"></i> Alergias
                                    </label>
                                    <textarea name="alergias" class="form-control" rows="2"
                                              placeholder="Alergias a medicamentos, alimentos, etc..."><?php echo htmlspecialchars($ultima_historia['alergias'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-pills"></i> Medicamentos Actuales
                                    </label>
                                    <textarea name="medicamentos_actuales" class="form-control" rows="2"
                                              placeholder="Medicamentos que el paciente está tomando..."><?php echo htmlspecialchars($ultima_historia['medicamentos_actuales'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SIGNOS VITALES -->
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title">Signos Vitales</h3>
                        </div>
                        <div class="card-body">
                            <div class="signos-vitales-grid">
                                <div class="form-group">
                                    <label class="form-label">Presión Arterial</label>
                                    <input type="text" name="presion_arterial" class="form-control" 
                                           placeholder="120/80" 
                                           value="<?php echo $signos_previos['presion_arterial'] ?? ''; ?>">
                                    <small style="color: var(--text-secondary);">mmHg</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Frecuencia Cardíaca</label>
                                    <input type="number" name="frecuencia_cardiaca" class="form-control" 
                                           placeholder="70" min="30" max="200"
                                           value="<?php echo $signos_previos['frecuencia_cardiaca'] ?? ''; ?>">
                                    <small style="color: var(--text-secondary);">lpm</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Temperatura</label>
                                    <input type="number" name="temperatura" class="form-control" 
                                           placeholder="36.5" step="0.1" min="35" max="42"
                                           value="<?php echo $signos_previos['temperatura'] ?? ''; ?>">
                                    <small style="color: var(--text-secondary);">°C</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Frec. Respiratoria</label>
                                    <input type="number" name="frecuencia_respiratoria" class="form-control" 
                                           placeholder="16" min="10" max="60"
                                           value="<?php echo $signos_previos['frecuencia_respiratoria'] ?? ''; ?>">
                                    <small style="color: var(--text-secondary);">rpm</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Saturación O2</label>
                                    <input type="number" name="saturacion_oxigeno" class="form-control" 
                                           placeholder="98" min="70" max="100"
                                           value="<?php echo $signos_previos['saturacion_oxigeno'] ?? ''; ?>">
                                    <small style="color: var(--text-secondary);">%</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Peso</label>
                                    <input type="number" name="peso" id="peso" class="form-control" 
                                           placeholder="70" step="0.1" min="1" max="300"
                                           value="<?php echo $signos_previos['peso'] ?? ''; ?>"
                                           onchange="calcularIMC()">
                                    <small style="color: var(--text-secondary);">kg</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Talla</label>
                                    <input type="number" name="talla" id="talla" class="form-control" 
                                           placeholder="1.70" step="0.01" min="0.5" max="2.5"
                                           value="<?php echo $signos_previos['talla'] ?? ''; ?>"
                                           onchange="calcularIMC()">
                                    <small style="color: var(--text-secondary);">m</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">IMC</label>
                                    <input type="text" id="imc_display" class="form-control" 
                                           placeholder="Automático" readonly 
                                           style="background: var(--background-main);">
                                    <small id="imc_categoria" style="color: var(--text-secondary);"></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- EXAMEN FÍSICO -->
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title">Examen Físico</h3>
                        </div>
                        <div class="card-body">
                            <div class="dynamic-list" id="examenesLista">
                                <div class="dynamic-item" data-index="0">
                                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 10px;">
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">Sistema/Región</label>
                                            <input type="text" name="examenes_fisicos[0][sistema]" class="form-control" 
                                                   placeholder="Ej: Cardiovascular">
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">Hallazgos</label>
                                            <input type="text" name="examenes_fisicos[0][hallazgos]" class="form-control" 
                                                   placeholder="Ej: Ruidos cardíacos rítmicos, no soplos">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" onclick="agregarExamen()" class="btn btn-sm btn-secondary" style="margin-top: 10px;">
                                <i class="fas fa-plus"></i> Agregar Sistema
                            </button>
                        </div>
                    </div>

                    <!-- DIAGNÓSTICOS -->
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title">Diagnósticos</h3>
                        </div>
                        <div class="card-body">
                            <div class="dynamic-list" id="diagnosticosLista">
                                <div class="dynamic-item" data-index="0">
                                    <div style="display: grid; grid-template-columns: 1fr 3fr 1fr; gap: 10px;">
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">CIE-10</label>
                                            <input type="text" name="diagnosticos[0][codigo_cie10]" class="form-control" 
                                                   placeholder="Ej: J00">
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">Diagnóstico</label>
                                            <input type="text" name="diagnosticos[0][diagnostico]" class="form-control" 
                                                   placeholder="Ej: Rinofaringitis aguda">
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">Tipo</label>
                                            <select name="diagnosticos[0][tipo]" class="form-control">
                                                <option value="presuntivo">Presuntivo</option>
                                                <option value="definitivo">Definitivo</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" onclick="agregarDiagnostico()" class="btn btn-sm btn-secondary" style="margin-top: 10px;">
                                <i class="fas fa-plus"></i> Agregar Diagnóstico
                            </button>
                        </div>
                    </div>

                    <!-- TRATAMIENTO -->
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title">Plan de Tratamiento</h3>
                        </div>
                        <div class="card-body">
                            <div class="dynamic-list" id="tratamientosLista">
                                <div class="dynamic-item" data-index="0">
                                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">Medicamento</label>
                                            <input type="text" name="tratamientos[0][medicamento]" class="form-control" 
                                                   placeholder="Ej: Paracetamol">
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">Dosis</label>
                                            <input type="text" name="tratamientos[0][dosis]" class="form-control" 
                                                   placeholder="Ej: 500mg">
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">Frecuencia</label>
                                            <input type="text" name="tratamientos[0][frecuencia]" class="form-control" 
                                                   placeholder="Ej: c/8h">
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">Duración</label>
                                            <input type="text" name="tratamientos[0][duracion]" class="form-control" 
                                                   placeholder="Ej: 7 días">
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin: 0;">
                                        <label class="form-label">Indicaciones</label>
                                        <input type="text" name="tratamientos[0][indicaciones]" class="form-control" 
                                               placeholder="Indicaciones especiales para la toma del medicamento">
                                    </div>
                                </div>
                            </div>
                            <button type="button" onclick="agregarTratamiento()" class="btn btn-sm btn-secondary" style="margin-top: 10px;">
                                <i class="fas fa-plus"></i> Agregar Medicamento
                            </button>
                        </div>
                        
                        <div class="card-body" style="border-top: 1px solid var(--border-color); display: flex; gap: 10px; justify-content: flex-end;">
                            <a href="historia-clinica.php?paciente_id=<?php echo $paciente_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Historia Clínica
                            </button>
                        </div>
                    </div>
                </form>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        let examenIndex = 1;
        let diagnosticoIndex = 1;
        let tratamientoIndex = 1;
        
        function calcularIMC() {
            const peso = parseFloat(document.getElementById('peso').value);
            const talla = parseFloat(document.getElementById('talla').value);
            const imcDisplay = document.getElementById('imc_display');
            const imcCategoria = document.getElementById('imc_categoria');
            
            if (peso && talla && talla > 0) {
                const imc = (peso / (talla * talla)).toFixed(2);
                imcDisplay.value = imc;
                
                // Clasificación IMC
                if (imc < 18.5) {
                    imcCategoria.textContent = 'Bajo peso';
                    imcCategoria.style.color = '#F59E0B';
                } else if (imc < 25) {
                    imcCategoria.textContent = 'Normal';
                    imcCategoria.style.color = '#10B981';
                } else if (imc < 30) {
                    imcCategoria.textContent = 'Sobrepeso';
                    imcCategoria.style.color = '#F59E0B';
                } else {
                    imcCategoria.textContent = 'Obesidad';
                    imcCategoria.style.color = '#EF4444';
                }
            } else {
                imcDisplay.value = '';
                imcCategoria.textContent = '';
            }
        }
        
        function agregarExamen() {
            const lista = document.getElementById('examenesLista');
            const item = document.createElement('div');
            item.className = 'dynamic-item';
            item.setAttribute('data-index', examenIndex);
            item.innerHTML = `
                <button type="button" class="btn-remove-item" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 10px;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Sistema/Región</label>
                        <input type="text" name="examenes_fisicos[${examenIndex}][sistema]" class="form-control" 
                               placeholder="Ej: Respiratorio">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Hallazgos</label>
                        <input type="text" name="examenes_fisicos[${examenIndex}][hallazgos]" class="form-control" 
                               placeholder="Ej: Murmullo vesicular conservado">
                    </div>
                </div>
            `;
            lista.appendChild(item);
            examenIndex++;
        }
        
        function agregarDiagnostico() {
            const lista = document.getElementById('diagnosticosLista');
            const item = document.createElement('div');
            item.className = 'dynamic-item';
            item.setAttribute('data-index', diagnosticoIndex);
            item.innerHTML = `
                <button type="button" class="btn-remove-item" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
                <div style="display: grid; grid-template-columns: 1fr 3fr 1fr; gap: 10px;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">CIE-10</label>
                        <input type="text" name="diagnosticos[${diagnosticoIndex}][codigo_cie10]" class="form-control" 
                               placeholder="Ej: K29">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Diagnóstico</label>
                        <input type="text" name="diagnosticos[${diagnosticoIndex}][diagnostico]" class="form-control" 
                               placeholder="Descripción del diagnóstico">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Tipo</label>
                        <select name="diagnosticos[${diagnosticoIndex}][tipo]" class="form-control">
                            <option value="presuntivo">Presuntivo</option>
                            <option value="definitivo">Definitivo</option>
                        </select>
                    </div>
                </div>
            `;
            lista.appendChild(item);
            diagnosticoIndex++;
        }
        
        function agregarTratamiento() {
            const lista = document.getElementById('tratamientosLista');
            const item = document.createElement('div');
            item.className = 'dynamic-item';
            item.setAttribute('data-index', tratamientoIndex);
            item.innerHTML = `
                <button type="button" class="btn-remove-item" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Medicamento</label>
                        <input type="text" name="tratamientos[${tratamientoIndex}][medicamento]" class="form-control" 
                               placeholder="Nombre del medicamento">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Dosis</label>
                        <input type="text" name="tratamientos[${tratamientoIndex}][dosis]" class="form-control" 
                               placeholder="Ej: 100mg">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Frecuencia</label>
                        <input type="text" name="tratamientos[${tratamientoIndex}][frecuencia]" class="form-control" 
                               placeholder="Ej: c/12h">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Duración</label>
                        <input type="text" name="tratamientos[${tratamientoIndex}][duracion]" class="form-control" 
                               placeholder="Ej: 10 días">
                    </div>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Indicaciones</label>
                    <input type="text" name="tratamientos[${tratamientoIndex}][indicaciones]" class="form-control" 
                           placeholder="Indicaciones especiales">
                </div>
            `;
            lista.appendChild(item);
            tratamientoIndex++;
        }
        
        // Calcular IMC si hay datos previos
        window.onload = function() {
            calcularIMC();
        };
    </script>
</body>
</html>