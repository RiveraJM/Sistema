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

$historia_id = $_GET['id'] ?? null;

if (!$historia_id) {
    header("Location: pacientes.php");
    exit();
}

// Obtener datos de la historia clínica
$query = "SELECT hc.*, 
          CONCAT(p.nombre, ' ', p.apellido) as paciente_nombre,
          p.fecha_nacimiento, p.sexo
          FROM historia_clinica hc
          INNER JOIN pacientes p ON hc.paciente_id = p.id
          WHERE hc.id = :id";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $historia_id);
$stmt->execute();
$historia = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$historia) {
    header("Location: pacientes.php");
    exit();
}

$paciente_id = $historia['paciente_id'];

// Calcular edad
$nacimiento = new DateTime($historia['fecha_nacimiento']);
$hoy = new DateTime();
$edad = $hoy->diff($nacimiento)->y;

// Obtener signos vitales
$query_signos = "SELECT * FROM signos_vitales WHERE historia_id = :historia_id";
$stmt_signos = $db->prepare($query_signos);
$stmt_signos->bindParam(':historia_id', $historia_id);
$stmt_signos->execute();
$signos = $stmt_signos->fetch(PDO::FETCH_ASSOC);

// Obtener exámenes físicos
$query_examenes = "SELECT * FROM examenes_fisicos WHERE historia_id = :historia_id";
$stmt_examenes = $db->prepare($query_examenes);
$stmt_examenes->bindParam(':historia_id', $historia_id);
$stmt_examenes->execute();
$examenes = $stmt_examenes->fetchAll(PDO::FETCH_ASSOC);

// Obtener diagnósticos
$query_diagnosticos = "SELECT * FROM diagnosticos WHERE historia_id = :historia_id";
$stmt_diagnosticos = $db->prepare($query_diagnosticos);
$stmt_diagnosticos->bindParam(':historia_id', $historia_id);
$stmt_diagnosticos->execute();
$diagnosticos = $stmt_diagnosticos->fetchAll(PDO::FETCH_ASSOC);

// Obtener tratamientos
$query_tratamientos = "SELECT * FROM tratamientos WHERE historia_id = :historia_id";
$stmt_tratamientos = $db->prepare($query_tratamientos);
$stmt_tratamientos->bindParam(':historia_id', $historia_id);
$stmt_tratamientos->execute();
$tratamientos = $stmt_tratamientos->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha_consulta = $_POST['fecha_consulta'] ?? '';
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
    
    // Calcular IMC
    $imc = null;
    if ($peso && $talla) {
        $imc = round($peso / ($talla * $talla), 2);
    }
    
    // Arrays
    $examenes_fisicos = $_POST['examenes_fisicos'] ?? [];
    $diagnosticos_array = $_POST['diagnosticos'] ?? [];
    $tratamientos_array = $_POST['tratamientos'] ?? [];
    
    if (empty($motivo_consulta)) {
        $error = 'El motivo de consulta es obligatorio';
    } else {
        try {
            $db->beginTransaction();
            
            // Actualizar historia clínica
            $query = "UPDATE historia_clinica SET 
                     fecha_consulta = :fecha_consulta,
                     motivo_consulta = :motivo_consulta,
                     antecedentes_personales = :antecedentes_personales,
                     antecedentes_familiares = :antecedentes_familiares,
                     alergias = :alergias,
                     medicamentos_actuales = :medicamentos_actuales
                     WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':fecha_consulta' => $fecha_consulta,
                ':motivo_consulta' => $motivo_consulta,
                ':antecedentes_personales' => $antecedentes_personales,
                ':antecedentes_familiares' => $antecedentes_familiares,
                ':alergias' => $alergias,
                ':medicamentos_actuales' => $medicamentos_actuales,
                ':id' => $historia_id
            ]);
            
            // Actualizar signos vitales
            if ($signos) {
                $query_signos = "UPDATE signos_vitales SET 
                                presion_arterial = :presion_arterial,
                                frecuencia_cardiaca = :frecuencia_cardiaca,
                                temperatura = :temperatura,
                                frecuencia_respiratoria = :frecuencia_respiratoria,
                                saturacion_oxigeno = :saturacion_oxigeno,
                                peso = :peso,
                                talla = :talla,
                                imc = :imc
                                WHERE historia_id = :historia_id";
            } else {
                $query_signos = "INSERT INTO signos_vitales 
                                (historia_id, presion_arterial, frecuencia_cardiaca, temperatura, 
                                 frecuencia_respiratoria, saturacion_oxigeno, peso, talla, imc) 
                                VALUES (:historia_id, :presion_arterial, :frecuencia_cardiaca, :temperatura,
                                        :frecuencia_respiratoria, :saturacion_oxigeno, :peso, :talla, :imc)";
            }
            
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
            
            // Eliminar y reinsertar exámenes físicos
            $db->prepare("DELETE FROM examenes_fisicos WHERE historia_id = :historia_id")->execute([':historia_id' => $historia_id]);
            
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
            
            // Eliminar y reinsertar diagnósticos
            $db->prepare("DELETE FROM diagnosticos WHERE historia_id = :historia_id")->execute([':historia_id' => $historia_id]);
            
            if (!empty($diagnosticos_array)) {
                $query_diagnostico = "INSERT INTO diagnosticos (historia_id, codigo_cie10, diagnostico, tipo) 
                                     VALUES (:historia_id, :codigo_cie10, :diagnostico, :tipo)";
                $stmt_diagnostico = $db->prepare($query_diagnostico);
                
                foreach ($diagnosticos_array as $diagnostico) {
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
            
            // Eliminar y reinsertar tratamientos
            $db->prepare("DELETE FROM tratamientos WHERE historia_id = :historia_id")->execute([':historia_id' => $historia_id]);
            
            if (!empty($tratamientos_array)) {
                $query_tratamiento = "INSERT INTO tratamientos 
                                     (historia_id, medicamento, dosis, frecuencia, duracion, indicaciones) 
                                     VALUES (:historia_id, :medicamento, :dosis, :frecuencia, :duracion, :indicaciones)";
                $stmt_tratamiento = $db->prepare($query_tratamiento);
                
                foreach ($tratamientos_array as $tratamiento) {
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
            
            $db->commit();
            
            $success = 'Historia clínica actualizada exitosamente';
            
            // Recargar datos
            $stmt->execute([':id' => $historia_id]);
            $historia = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Redirigir después de 2 segundos
            header("refresh:2;url=detalle-historia.php?id=$historia_id");
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Error al actualizar la historia clínica: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Historia Clínica - Sistema Clínico</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
                        <h1 class="page-title">Editar Historia Clínica</h1>
                        <p class="page-subtitle">
                            Paciente: <strong><?php echo htmlspecialchars($historia['paciente_nombre']); ?></strong>
                            - <?php echo $edad; ?> años
                        </p>
                    </div>
                    <a href="detalle-historia.php?id=<?php echo $historia_id; ?>" class="btn btn-secondary">
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

                <form method="POST">
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
                                           value="<?php echo $historia['fecha_consulta']; ?>" 
                                           max="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-comment-medical"></i> Motivo de Consulta *
                                </label>
                                <textarea name="motivo_consulta" class="form-control" rows="3" required><?php echo htmlspecialchars($historia['motivo_consulta']); ?></textarea>
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
                                    <textarea name="antecedentes_personales" class="form-control" rows="3"><?php echo htmlspecialchars($historia['antecedentes_personales']); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-users"></i> Antecedentes Familiares
                                    </label>
                                    <textarea name="antecedentes_familiares" class="form-control" rows="3"><?php echo htmlspecialchars($historia['antecedentes_familiares']); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" style="color: #EF4444;">
                                        <i class="fas fa-exclamation-triangle"></i> Alergias
                                    </label>
                                    <textarea name="alergias" class="form-control" rows="2"><?php echo htmlspecialchars($historia['alergias']); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-pills"></i> Medicamentos Actuales
                                    </label>
                                    <textarea name="medicamentos_actuales" class="form-control" rows="2"><?php echo htmlspecialchars($historia['medicamentos_actuales']); ?></textarea>
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
                                           value="<?php echo htmlspecialchars($signos['presion_arterial'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Frecuencia Cardíaca</label>
                                    <input type="number" name="frecuencia_cardiaca" class="form-control" 
                                           value="<?php echo $signos['frecuencia_cardiaca'] ?? ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Temperatura</label>
                                    <input type="number" name="temperatura" class="form-control" step="0.1"
                                           value="<?php echo $signos['temperatura'] ?? ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Frec. Respiratoria</label>
                                    <input type="number" name="frecuencia_respiratoria" class="form-control" 
                                           value="<?php echo $signos['frecuencia_respiratoria'] ?? ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Saturación O2</label>
                                    <input type="number" name="saturacion_oxigeno" class="form-control" 
                                           value="<?php echo $signos['saturacion_oxigeno'] ?? ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Peso</label>
                                    <input type="number" name="peso" id="peso" class="form-control" step="0.1"
                                           value="<?php echo $signos['peso'] ?? ''; ?>" onchange="calcularIMC()">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Talla</label>
                                    <input type="number" name="talla" id="talla" class="form-control" step="0.01"
                                           value="<?php echo $signos['talla'] ?? ''; ?>" onchange="calcularIMC()">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">IMC</label>
                                    <input type="text" id="imc_display" class="form-control" readonly 
                                           style="background: var(--background-main);">
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
                                <?php if (count($examenes) > 0): ?>
                                    <?php foreach ($examenes as $index => $examen): ?>
                                    <div class="dynamic-item" data-index="<?php echo $index; ?>">
                                        <?php if ($index > 0): ?>
                                        <button type="button" class="btn-remove-item" onclick="this.parentElement.remove()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 10px;">
                                            <div class="form-group" style="margin: 0;">
                                                <label class="form-label">Sistema/Región</label>
                                                <input type="text" name="examenes_fisicos[<?php echo $index; ?>][sistema]" class="form-control" 
                                                       value="<?php echo htmlspecialchars($examen['sistema']); ?>">
                                            </div>
                                            <div class="form-group" style="margin: 0;">
                                                <label class="form-label">Hallazgos</label>
                                                <input type="text" name="examenes_fisicos[<?php echo $index; ?>][hallazgos]" class="form-control" 
                                                       value="<?php echo htmlspecialchars($examen['hallazgos']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <div class="dynamic-item" data-index="0">
                                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 10px;">
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">Sistema/Región</label>
                                            <input type="text" name="examenes_fisicos[0][sistema]" class="form-control">
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">Hallazgos</label>
                                            <input type="text" name="examenes_fisicos[0][hallazgos]" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
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
                                <?php if (count($diagnosticos) > 0): ?>
                                    <?php foreach ($diagnosticos as $index => $diagnostico): ?>
                                    <div class="dynamic-item" data-index="<?php echo $index; ?>">
                                        <?php if ($index > 0): ?>
                                        <button type="button" class="btn-remove-item" onclick="this.parentElement.remove()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                        <div style="display: grid; grid-template-columns: 1fr 3fr 1fr; gap: 10px;">
                                            <div class="form-group" style="margin: 0;">
                                                <label class="form-label">CIE-10</label>
                                                <input type="text" name="diagnosticos[<?php echo $index; ?>][codigo_cie10]" class="form-control" 
                                                       value="<?php echo htmlspecialchars($diagnostico['codigo_cie10']); ?>">
                                            </div>
                                            <div class="form-group" style="margin: 0;">
                                                <label class="form-label">Diagnóstico</label>
                                                <input type="text" name="diagnosticos[<?php echo $index; ?>][diagnostico]" class="form-control" 
                                                       value="<?php echo htmlspecialchars($diagnostico['diagnostico']); ?>">
                                            </div>
                                            <div class="form-group" style="margin: 0;">
                                                <label class="form-label">Tipo</label>
                                                <select name="diagnosticos[<?php echo $index; ?>][tipo]" class="form-control">
                                                    <option value="presuntivo" <?php echo $diagnostico['tipo'] === 'presuntivo' ? 'selected' : ''; ?>>Presuntivo</option>
                                                    <option value="definitivo" <?php echo $diagnostico['tipo'] === 'definitivo' ? 'selected' : ''; ?>>Definitivo</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <div class="dynamic-item" data-index="0">
                                    <div style="display: grid; grid-template-columns: 1fr 3fr 1fr; gap: 10px;">
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">CIE-10</label>
                                            <input type="text" name="diagnosticos[0][codigo_cie10]" class="form-control">
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">Diagnóstico</label>
                                            <input type="text" name="diagnosticos[0][diagnostico]" class="form-control">
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
                                <?php endif; ?>
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
                                <?php if (count($tratamientos) > 0): ?>
                                    <?php foreach ($tratamientos as $index => $tratamiento): ?>
                                    <div class="dynamic-item" data-index="<?php echo $index; ?>">
                                        <?php if ($index > 0): ?>
                                        <button type="button" class="btn-remove-item" onclick="this.parentElement.remove()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                            <div class="form-group" style="margin: 0;">
                                                <label class="form-label">Medicamento</label>
                                                <input type="text" name="tratamientos[<?php echo $index; ?>][medicamento]" class="form-control" 
                                                       value="<?php echo htmlspecialchars($tratamiento['medicamento']); ?>">
                                            </div>
                                            <div class="form-group" style="margin: 0;">
                                                <label class="form-label">Dosis</label>
                                                <input type="text" name="tratamientos[<?php echo $index; ?>][dosis]" class="form-control" 
                                                       value="<?php echo htmlspecialchars($tratamiento['dosis']); ?>">
                                            </div>
                                            <div class="form-group" style="margin: 0;">
                                                <label class="form-label">Frecuencia</label>
                                                <input type="text" name="tratamientos[<?php echo $index; ?>][frecuencia]" class="form-control" 
                                                       value="<?php echo htmlspecialchars($tratamiento['frecuencia']); ?>">
                                            </div>
                                            <div class="form-group" style="margin: 0;">
                                                <label class="form-label">Duración</label>
                                                <input type="text" name="tratamientos[<?php echo $index; ?>][duracion]" class="form-control" 
                                                       value="<?php echo htmlspecialchars($tratamiento['duracion']); ?>">
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">Indicaciones</label>
                                            <input type="text" name="tratamientos[<?php echo $index; ?>][indicaciones]" class="form-control" 
                                                   value="<?php echo htmlspecialchars($tratamiento['indicaciones']); ?>">
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <div class="dynamic-item" data-index="0">
                                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">Medicamento</label>
                                            <input type="text" name="tratamientos[0][medicamento]" class="form-control">
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">Dosis</label>
                                            <input type="text" name="tratamientos[0][dosis]" class="form-control">
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">Frecuencia</label>
                                            <input type="text" name="tratamientos[0][frecuencia]" class="form-control">
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label">Duración</label>
                                            <input type="text" name="tratamientos[0][duracion]" class="form-control">
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin: 0;">
                                        <label class="form-label">Indicaciones</label>
                                        <input type="text" name="tratamientos[0][indicaciones]" class="form-control">
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" onclick="agregarTratamiento()" class="btn btn-sm btn-secondary" style="margin-top: 10px;">
                                <i class="fas fa-plus"></i> Agregar Medicamento
                            </button>
                        </div>
                        
                        <div class="card-body" style="border-top: 1px solid var(--border-color); display: flex; gap: 10px; justify-content: flex-end;">
                            <a href="detalle-historia.php?id=<?php echo $historia_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Historia
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        let examenIndex = <?php echo count($examenes); ?>;
        let diagnosticoIndex = <?php echo count($diagnosticos); ?>;
        let tratamientoIndex = <?php echo count($tratamientos); ?>;
        
        function calcularIMC() {
            const peso = parseFloat(document.getElementById('peso').value);
            const talla = parseFloat(document.getElementById('talla').value);
            const imcDisplay = document.getElementById('imc_display');
            
            if (peso && talla && talla > 0) {
                const imc = (peso / (talla * talla)).toFixed(2);
                imcDisplay.value = imc;
            } else {
                imcDisplay.value = '';
            }
        }
        
        function agregarExamen() {
            const lista = document.getElementById('examenesLista');
            const item = document.createElement('div');
            item.className = 'dynamic-item';
            item.innerHTML = `
                <button type="button" class="btn-remove-item" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 10px;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Sistema/Región</label>
                        <input type="text" name="examenes_fisicos[${examenIndex}][sistema]" class="form-control">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Hallazgos</label>
                        <input type="text" name="examenes_fisicos[${examenIndex}][hallazgos]" class="form-control">
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
            item.innerHTML = `
                <button type="button" class="btn-remove-item" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
                <div style="display: grid; grid-template-columns: 1fr 3fr 1fr; gap: 10px;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">CIE-10</label>
                        <input type="text" name="diagnosticos[${diagnosticoIndex}][codigo_cie10]" class="form-control">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Diagnóstico</label>
                        <input type="text" name="diagnosticos[${diagnosticoIndex}][diagnostico]" class="form-control">
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
            item.innerHTML = `
                <button type="button" class="btn-remove-item" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Medicamento</label>
                        <input type="text" name="tratamientos[${tratamientoIndex}][medicamento]" class="form-control">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Dosis</label>
                        <input type="text" name="tratamientos[${tratamientoIndex}][dosis]" class="form-control">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Frecuencia</label>
                        <input type="text" name="tratamientos[${tratamientoIndex}][frecuencia]" class="form-control">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Duración</label>
                        <input type="text" name="tratamientos[${tratamientoIndex}][duracion]" class="form-control">
                    </div>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Indicaciones</label>
                    <input type="text" name="tratamientos[${tratamientoIndex}][indicaciones]" class="form-control">
                </div>
            `;
            lista.appendChild(item);
            tratamientoIndex++;
        }
        
        window.onload = function() {
            calcularIMC();
        };
    </script>
</body>
</html>