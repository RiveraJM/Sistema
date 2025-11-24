<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

// Verificar permisos
if (!hasPermission('ver_historia_clinica')) {
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
          p.dni, p.fecha_nacimiento, p.sexo,
          CONCAT(u.nombre, ' ', u.apellido) as medico_nombre,
          e.nombre as especialidad,
          m.numero_colegiatura
          FROM historia_clinica hc
          INNER JOIN pacientes p ON hc.paciente_id = p.id
          INNER JOIN medicos m ON hc.medico_id = m.id
          INNER JOIN usuarios u ON m.usuario_id = u.id
          INNER JOIN especialidades e ON m.especialidad_id = e.id
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

// Obtener procedimientos
$query_procedimientos = "SELECT * FROM procedimientos WHERE historia_id = :historia_id";
$stmt_procedimientos = $db->prepare($query_procedimientos);
$stmt_procedimientos->bindParam(':historia_id', $historia_id);
$stmt_procedimientos->execute();
$procedimientos = $stmt_procedimientos->fetchAll(PDO::FETCH_ASSOC);

// Obtener evoluciones
$query_evoluciones = "SELECT ev.*, CONCAT(u.nombre, ' ', u.apellido) as medico_nombre
                      FROM evoluciones ev
                      INNER JOIN medicos m ON ev.medico_id = m.id
                      INNER JOIN usuarios u ON m.usuario_id = u.id
                      WHERE ev.historia_id = :historia_id
                      ORDER BY ev.fecha_evolucion DESC";
$stmt_evoluciones = $db->prepare($query_evoluciones);
$stmt_evoluciones->bindParam(':historia_id', $historia_id);
$stmt_evoluciones->execute();
$evoluciones = $stmt_evoluciones->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Historia Clínica - Sistema Clínico</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .section-box {
            padding: 20px;
            background: var(--background-main);
            border-radius: 12px;
            border-left: 4px solid var(--primary-color);
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .info-item {
            padding: 12px;
            background: white;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .info-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 5px;
            font-weight: 500;
        }
        .info-value {
            font-size: 14px;
            color: var(--text-primary);
            font-weight: 600;
        }
        .signos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }
        .signo-card {
            text-align: center;
            padding: 15px;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }
        .signo-valor {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
            margin: 5px 0;
        }
        .signo-label {
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
        }
        .lista-items {
            display: grid;
            gap: 10px;
        }
        .item-card {
            padding: 15px;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            display: grid;
            gap: 8px;
        }
        .item-header {
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            justify-content: space-between;
            align-items: start;
        }
        .item-content {
            color: var(--text-secondary);
            font-size: 14px;
        }
        .evolucion-item {
            padding: 15px;
            background: white;
            border-left: 3px solid var(--primary-color);
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .evolucion-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 13px;
            color: var(--text-secondary);
        }
        .evolucion-content {
            color: var(--text-primary);
            line-height: 1.6;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .card {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="dashboard-content">
                <div class="page-header no-print">
                    <div>
                        <h1 class="page-title">Historia Clínica - Detalle de Consulta</h1>
                        <p class="page-subtitle">
                            Consulta del <?php echo date('d/m/Y', strtotime($historia['fecha_consulta'])); ?>
                        </p>
                    </div>
                    <div class="btn-group">
                        <a href="historia-clinica.php?paciente_id=<?php echo $paciente_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        <?php if (hasPermission('editar_historia_clinica')): ?>
                        <button onclick="agregarEvolucion()" class="btn btn-success">
                            <i class="fas fa-notes-medical"></i> Agregar Evolución
                        </button>
                        <a href="editar-historia.php?id=<?php echo $historia_id; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <?php endif; ?>
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                    </div>
                </div>

                <!-- Encabezado de la Historia -->
                <div class="card mb-2">
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: center;">
                            <div>
                                <h2 style="margin: 0 0 10px 0; color: var(--primary-color);">
                                    <?php echo htmlspecialchars($historia['paciente_nombre']); ?>
                                </h2>
                                <div style="display: flex; gap: 20px; flex-wrap: wrap; color: var(--text-secondary);">
                                    <div><i class="fas fa-id-card"></i> DNI: <?php echo $historia['dni']; ?></div>
                                    <div><i class="fas fa-birthday-cake"></i> <?php echo $edad; ?> años</div>
                                    <div><i class="fas fa-venus-mars"></i> <?php echo $historia['sexo'] === 'M' ? 'Masculino' : 'Femenino'; ?></div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 5px;">Atendido por:</div>
                                <div style="font-weight: 600; color: var(--text-primary);">
                                    Dr(a). <?php echo htmlspecialchars($historia['medico_nombre']); ?>
                                </div>
                                <div style="font-size: 13px; color: var(--text-secondary);">
                                    <?php echo htmlspecialchars($historia['especialidad']); ?>
                                </div>
                                <div style="font-size: 12px; color: var(--text-secondary); margin-top: 5px;">
                                    CMP: <?php echo htmlspecialchars($historia['numero_colegiatura']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: grid; gap: 20px;">
                    <!-- Motivo de Consulta -->
                    <div class="section-box">
                        <div class="section-title">
                            <i class="fas fa-comment-medical"></i> Motivo de Consulta
                        </div>
                        <p style="margin: 0; color: var(--text-primary); line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($historia['motivo_consulta'])); ?>
                        </p>
                    </div>

                    <!-- Antecedentes -->
                    <?php if ($historia['antecedentes_personales'] || $historia['antecedentes_familiares'] || $historia['alergias'] || $historia['medicamentos_actuales']): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Antecedentes Médicos</h3>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <?php if ($historia['antecedentes_personales']): ?>
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-user-check"></i> Antecedentes Personales
                                    </div>
                                    <div style="color: var(--text-primary); margin-top: 5px; font-size: 13px;">
                                        <?php echo nl2br(htmlspecialchars($historia['antecedentes_personales'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($historia['antecedentes_familiares']): ?>
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-users"></i> Antecedentes Familiares
                                    </div>
                                    <div style="color: var(--text-primary); margin-top: 5px; font-size: 13px;">
                                        <?php echo nl2br(htmlspecialchars($historia['antecedentes_familiares'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($historia['alergias']): ?>
                                <div class="info-item" style="border-color: #EF4444;">
                                    <div class="info-label" style="color: #EF4444;">
                                        <i class="fas fa-exclamation-triangle"></i> Alergias
                                    </div>
                                    <div style="color: var(--text-primary); margin-top: 5px; font-size: 13px;">
                                        <?php echo nl2br(htmlspecialchars($historia['alergias'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($historia['medicamentos_actuales']): ?>
                                <div class="info-item" style="border-color: #10B981;">
                                    <div class="info-label" style="color: #10B981;">
                                        <i class="fas fa-pills"></i> Medicamentos Actuales
                                    </div>
                                    <div style="color: var(--text-primary); margin-top: 5px; font-size: 13px;">
                                        <?php echo nl2br(htmlspecialchars($historia['medicamentos_actuales'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Signos Vitales -->
                    <?php if ($signos): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-heartbeat"></i> Signos Vitales
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="signos-grid">
                                <?php if ($signos['presion_arterial']): ?>
                                <div class="signo-card">
                                    <div class="signo-label">Presión Arterial</div>
                                    <div class="signo-valor"><?php echo $signos['presion_arterial']; ?></div>
                                    <div class="signo-label">mmHg</div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($signos['frecuencia_cardiaca']): ?>
                                <div class="signo-card">
                                    <div class="signo-label">Frecuencia Cardíaca</div>
                                    <div class="signo-valor"><?php echo $signos['frecuencia_cardiaca']; ?></div>
                                    <div class="signo-label">lpm</div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($signos['temperatura']): ?>
                                <div class="signo-card">
                                    <div class="signo-valor"><?php echo number_format($signos['temperatura'], 1); ?>°</div>
                                    <div class="signo-label">Temperatura</div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($signos['frecuencia_respiratoria']): ?>
                                <div class="signo-card">
                                    <div class="signo-valor"><?php echo $signos['frecuencia_respiratoria']; ?></div>
                                    <div class="signo-label">Frec. Respiratoria</div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($signos['saturacion_oxigeno']): ?>
                                <div class="signo-card">
                                    <div class="signo-valor"><?php echo $signos['saturacion_oxigeno']; ?>%</div>
                                    <div class="signo-label">Saturación O2</div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($signos['peso']): ?>
                                <div class="signo-card">
                                    <div class="signo-valor"><?php echo number_format($signos['peso'], 1); ?></div>
                                    <div class="signo-label">Peso (kg)</div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($signos['talla']): ?>
                                <div class="signo-card">
                                    <div class="signo-valor"><?php echo number_format($signos['talla'], 2); ?></div>
                                    <div class="signo-label">Talla (m)</div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($signos['imc']): ?>
                                <div class="signo-card">
                                    <div class="signo-valor"><?php echo number_format($signos['imc'], 1); ?></div>
                                    <div class="signo-label">IMC (kg/m²)</div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Examen Físico -->
                    <?php if (count($examenes) > 0): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-stethoscope"></i> Examen Físico
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="lista-items">
                                <?php foreach ($examenes as $examen): ?>
                                <div class="item-card">
                                    <div class="item-header">
                                        <strong><?php echo htmlspecialchars($examen['sistema']); ?></strong>
                                    </div>
                                    <div class="item-content">
                                        <?php echo nl2br(htmlspecialchars($examen['hallazgos'])); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Diagnósticos -->
                    <?php if (count($diagnosticos) > 0): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-diagnoses"></i> Diagnósticos
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="lista-items">
                                <?php foreach ($diagnosticos as $diagnostico): ?>
                                <div class="item-card">
                                    <div class="item-header">
                                        <?php if ($diagnostico['codigo_cie10']): ?>
                                        <span class="badge badge-primary">
                                            CIE-10: <?php echo htmlspecialchars($diagnostico['codigo_cie10']); ?>
                                        </span>
                                        <?php endif; ?>
                                        <span class="badge <?php echo $diagnostico['tipo'] === 'definitivo' ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo ucfirst($diagnostico['tipo']); ?>
                                        </span>
                                    </div>
                                    <div class="item-content" style="margin-top: 8px;">
                                        <?php echo nl2br(htmlspecialchars($diagnostico['diagnostico'])); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Plan de Tratamiento -->
                    <?php if (count($tratamientos) > 0): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-prescription"></i> Plan de Tratamiento
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="lista-items">
                                <?php foreach ($tratamientos as $tratamiento): ?>
                                <div class="item-card">
                                    <div class="item-header">
                                        <strong style="color: var(--primary-color);">
                                            <?php echo htmlspecialchars($tratamiento['medicamento']); ?>
                                        </strong>
                                    </div>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-top: 8px; font-size: 13px;">
                                        <div>
                                            <strong style="color: var(--text-secondary);">Dosis:</strong>
                                            <?php echo htmlspecialchars($tratamiento['dosis']); ?>
                                        </div>
                                        <div>
                                            <strong style="color: var(--text-secondary);">Frecuencia:</strong>
                                            <?php echo htmlspecialchars($tratamiento['frecuencia']); ?>
                                        </div>
                                        <div>
                                            <strong style="color: var(--text-secondary);">Duración:</strong>
                                            <?php echo htmlspecialchars($tratamiento['duracion']); ?>
                                        </div>
                                    </div>
                                    <?php if ($tratamiento['indicaciones']): ?>
                                    <div style="margin-top: 8px; padding: 8px; background: rgba(0, 212, 212, 0.05); border-radius: 4px; font-size: 13px;">
                                        <strong style="color: var(--text-secondary);">Indicaciones:</strong>
                                        <?php echo nl2br(htmlspecialchars($tratamiento['indicaciones'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Procedimientos -->
                    <?php if (count($procedimientos) > 0): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-procedures"></i> Procedimientos
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="lista-items">
                                <?php foreach ($procedimientos as $procedimiento): ?>
                                <div class="item-card">
                                    <div class="item-header">
                                        <strong><?php echo htmlspecialchars($procedimiento['nombre']); ?></strong>
                                        <?php if ($procedimiento['fecha_realizacion']): ?>
                                        <span style="font-size: 12px; color: var(--text-secondary);">
                                            <?php echo date('d/m/Y', strtotime($procedimiento['fecha_realizacion'])); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($procedimiento['descripcion']): ?>
                                    <div class="item-content">
                                        <?php echo nl2br(htmlspecialchars($procedimiento['descripcion'])); ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($procedimiento['resultado']): ?>
                                    <div style="margin-top: 8px; padding: 8px; background: rgba(16, 185, 129, 0.05); border-radius: 4px; font-size: 13px;">
                                        <strong style="color: var(--text-secondary);">Resultado:</strong>
                                        <?php echo nl2br(htmlspecialchars($procedimiento['resultado'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Evoluciones -->
                    <?php if (count($evoluciones) > 0): ?>
                    <div class="card no-print">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history"></i> Evoluciones y Seguimientos
                                <span class="badge badge-primary"><?php echo count($evoluciones); ?></span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php foreach ($evoluciones as $evolucion): ?>
                            <div class="evolucion-item">
                                <div class="evolucion-header">
                                    <span>
                                        <i class="fas fa-user-md"></i>
                                        Dr(a). <?php echo htmlspecialchars($evolucion['medico_nombre']); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($evolucion['fecha_evolucion'])); ?>
                                    </span>
                                </div>
                                <div class="evolucion-content">
                                    <?php echo nl2br(htmlspecialchars($evolucion['nota_evolucion'])); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Información del Registro -->
                    <div class="card">
                        <div class="card-body" style="background: var(--background-main); font-size: 12px; color: var(--text-secondary);">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                <div>
                                    <strong>Fecha de Registro:</strong>
                                    <?php echo date('d/m/Y H:i', strtotime($historia['fecha_creacion'])); ?>
                                </div>
                                <div>
                                    <strong>Última Actualización:</strong>
                                    <?php echo date('d/m/Y H:i', strtotime($historia['fecha_actualizacion'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para agregar evolución -->
    <div id="modalEvolucion" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 16px; max-width: 600px; width: 90%; padding: 0;">
            <div style="padding: 25px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;">Agregar Evolución</h3>
                <button onclick="cerrarModalEvolucion()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-secondary);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="guardar-evolucion.php" style="padding: 25px;">
                <input type="hidden" name="historia_id" value="<?php echo $historia_id; ?>">
                
                <div class="form-group">
                    <label class="form-label">Fecha y Hora *</label>
                    <input type="datetime-local" name="fecha_evolucion" class="form-control" 
                           value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nota de Evolución *</label>
                    <textarea name="nota_evolucion" class="form-control" rows="6" 
                              placeholder="Describa la evolución del paciente..." required></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="cerrarModalEvolucion()" class="btn btn-secondary">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Evolución
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        function agregarEvolucion() {
            document.getElementById('modalEvolucion').style.display = 'flex';
        }
        
        function cerrarModalEvolucion() {
            document.getElementById('modalEvolucion').style.display = 'none';
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modalEvolucion').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalEvolucion();
            }
        });
    </script>
</body>
</html>