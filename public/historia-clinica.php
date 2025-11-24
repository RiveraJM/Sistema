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

$paciente_id = $_GET['paciente_id'] ?? null;

if (!$paciente_id) {
    header("Location: pacientes.php");
    exit();
}

// Obtener datos del paciente
$query = "SELECT p.*, s.nombre as seguro_nombre 
          FROM pacientes p
          LEFT JOIN seguros s ON p.seguro_id = s.id
          WHERE p.id = :id";
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

// Obtener historial completo
$query_historia = "SELECT hc.*, 
                   CONCAT(u.nombre, ' ', u.apellido) as medico_nombre,
                   e.nombre as especialidad
                   FROM historia_clinica hc
                   INNER JOIN medicos m ON hc.medico_id = m.id
                   INNER JOIN usuarios u ON m.usuario_id = u.id
                   INNER JOIN especialidades e ON m.especialidad_id = e.id
                   WHERE hc.paciente_id = :paciente_id
                   ORDER BY hc.fecha_consulta DESC, hc.fecha_creacion DESC";
$stmt_historia = $db->prepare($query_historia);
$stmt_historia->bindParam(':paciente_id', $paciente_id);
$stmt_historia->execute();
$historias = $stmt_historia->fetchAll(PDO::FETCH_ASSOC);

// Obtener resumen de antecedentes (última historia)
$antecedentes = null;
if (count($historias) > 0) {
    foreach ($historias as $h) {
        if (!empty($h['antecedentes_personales']) || !empty($h['antecedentes_familiares']) || !empty($h['alergias']) || !empty($h['medicamentos_actuales'])) {
            $antecedentes = $h;
            break;
        }
    }
}

// Obtener últimos signos vitales
$query_signos = "SELECT sv.* FROM signos_vitales sv
                 INNER JOIN historia_clinica hc ON sv.historia_id = hc.id
                 WHERE hc.paciente_id = :paciente_id
                 ORDER BY sv.fecha_registro DESC
                 LIMIT 1";
$stmt_signos = $db->prepare($query_signos);
$stmt_signos->bindParam(':paciente_id', $paciente_id);
$stmt_signos->execute();
$signos = $stmt_signos->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Clínica - <?php echo htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellido']); ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .historia-timeline {
            position: relative;
            padding-left: 30px;
        }
        .historia-timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border-color);
        }
        .historia-item {
            position: relative;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            transition: var(--transition);
        }
        .historia-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        .historia-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 25px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 3px solid white;
            box-shadow: 0 0 0 2px var(--border-color);
        }
        .historia-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        .historia-fecha {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 16px;
        }
        .historia-medico {
            font-size: 14px;
            color: var(--text-secondary);
        }
        .antecedentes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .antecedente-box {
            padding: 15px;
            background: var(--background-main);
            border-radius: 8px;
            border-left: 3px solid var(--primary-color);
        }
        .antecedente-box h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: var(--text-primary);
        }
        .antecedente-box p {
            margin: 0;
            font-size: 13px;
            color: var(--text-secondary);
        }
        .signos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
            font-size: 12px;
            color: var(--text-secondary);
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
                        <h1 class="page-title">Historia Clínica</h1>
                        <p class="page-subtitle">
                            <strong><?php echo htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellido']); ?></strong>
                            - <?php echo $edad; ?> años - DNI: <?php echo htmlspecialchars($paciente['dni']); ?>
                        </p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <a href="pacientes.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        <?php if (hasPermission('editar_historia_clinica')): ?>
                        <a href="nueva-historia.php?paciente_id=<?php echo $paciente_id; ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva Consulta
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Resumen del Paciente -->
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user"></i> Información del Paciente
                        </h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                            <div>
                                <strong style="color: var(--text-secondary); font-size: 12px;">Fecha de Nacimiento</strong>
                                <div><?php echo date('d/m/Y', strtotime($paciente['fecha_nacimiento'])); ?></div>
                            </div>
                            <div>
                                <strong style="color: var(--text-secondary); font-size: 12px;">Edad</strong>
                                <div><?php echo $edad; ?> años</div>
                            </div>
                            <div>
                                <strong style="color: var(--text-secondary); font-size: 12px;">Sexo</strong>
                                <div><?php echo $paciente['sexo'] === 'M' ? 'Masculino' : 'Femenino'; ?></div>
                            </div>
                            <div>
                                <strong style="color: var(--text-secondary); font-size: 12px;">Seguro</strong>
                                <div><?php echo htmlspecialchars($paciente['seguro_nombre'] ?: 'Sin seguro'); ?></div>
                            </div>
                            <div>
                                <strong style="color: var(--text-secondary); font-size: 12px;">Teléfono</strong>
                                <div><?php echo htmlspecialchars($paciente['telefono'] ?: '-'); ?></div>
                            </div>
                            <div>
                                <strong style="color: var(--text-secondary); font-size: 12px;">Email</strong>
                                <div><?php echo htmlspecialchars($paciente['email'] ?: '-'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Últimos Signos Vitales -->
                <?php if ($signos): ?>
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-heartbeat"></i> Últimos Signos Vitales
                            <small style="font-weight: normal; color: var(--text-secondary); margin-left: 10px;">
                                (<?php echo date('d/m/Y H:i', strtotime($signos['fecha_registro'])); ?>)
                            </small>
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
                                <div class="signo-label">Temperatura</div>
                                <div class="signo-valor"><?php echo number_format($signos['temperatura'], 1); ?>°</div>
                                <div class="signo-label">Celsius</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($signos['saturacion_oxigeno']): ?>
                            <div class="signo-card">
                                <div class="signo-label">Saturación O2</div>
                                <div class="signo-valor"><?php echo $signos['saturacion_oxigeno']; ?>%</div>
                                <div class="signo-label">SpO2</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($signos['peso']): ?>
                            <div class="signo-card">
                                <div class="signo-label">Peso</div>
                                <div class="signo-valor"><?php echo number_format($signos['peso'], 1); ?></div>
                                <div class="signo-label">kg</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($signos['talla']): ?>
                            <div class="signo-card">
                                <div class="signo-label">Talla</div>
                                <div class="signo-valor"><?php echo number_format($signos['talla'], 2); ?></div>
                                <div class="signo-label">m</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($signos['imc']): ?>
                            <div class="signo-card">
                                <div class="signo-label">IMC</div>
                                <div class="signo-valor"><?php echo number_format($signos['imc'], 1); ?></div>
                                <div class="signo-label">kg/m²</div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Antecedentes -->
                <?php if ($antecedentes): ?>
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-notes-medical"></i> Antecedentes Médicos
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="antecedentes-grid">
                            <?php if ($antecedentes['antecedentes_personales']): ?>
                            <div class="antecedente-box">
                                <h4><i class="fas fa-user-check"></i> Antecedentes Personales</h4>
                                <p><?php echo nl2br(htmlspecialchars($antecedentes['antecedentes_personales'])); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($antecedentes['antecedentes_familiares']): ?>
                            <div class="antecedente-box">
                                <h4><i class="fas fa-users"></i> Antecedentes Familiares</h4>
                                <p><?php echo nl2br(htmlspecialchars($antecedentes['antecedentes_familiares'])); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($antecedentes['alergias']): ?>
                            <div class="antecedente-box" style="border-left-color: #EF4444;">
                                <h4><i class="fas fa-exclamation-triangle"></i> Alergias</h4>
                                <p><?php echo nl2br(htmlspecialchars($antecedentes['alergias'])); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($antecedentes['medicamentos_actuales']): ?>
                            <div class="antecedente-box" style="border-left-color: #10B981;">
                                <h4><i class="fas fa-pills"></i> Medicamentos Actuales</h4>
                                <p><?php echo nl2br(htmlspecialchars($antecedentes['medicamentos_actuales'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Historial de Consultas -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history"></i> Historial de Consultas
                            <span class="badge badge-primary"><?php echo count($historias); ?> consultas</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($historias) > 0): ?>
                        <div class="historia-timeline">
                            <?php foreach ($historias as $historia): ?>
                            <div class="historia-item" onclick="window.location.href='detalle-historia.php?id=<?php echo $historia['id']; ?>'">
                                <div class="historia-header">
                                    <div>
                                        <div class="historia-fecha">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('d/m/Y', strtotime($historia['fecha_consulta'])); ?>
                                        </div>
                                        <div class="historia-medico">
                                            <i class="fas fa-user-md"></i>
                                            <?php echo htmlspecialchars($historia['medico_nombre']); ?> - 
                                            <?php echo htmlspecialchars($historia['especialidad']); ?>
                                        </div>
                                    </div>
                                    <span class="badge badge-primary">
                                        <i class="fas fa-eye"></i> Ver Detalles
                                    </span>
                                </div>
                                <div>
                                    <strong style="color: var(--text-primary);">Motivo:</strong>
                                    <span style="color: var(--text-secondary);">
                                        <?php echo htmlspecialchars(substr($historia['motivo_consulta'], 0, 150)); ?>
                                        <?php echo strlen($historia['motivo_consulta']) > 150 ? '...' : ''; ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                            <i class="fas fa-file-medical" style="font-size: 64px; opacity: 0.3; margin-bottom: 20px;"></i>
                            <h3 style="margin: 0 0 10px 0;">Sin Historial Médico</h3>
                            <p>Este paciente aún no tiene consultas registradas</p>
                            <?php if (hasPermission('editar_historia_clinica')): ?>
                            <a href="nueva-historia.php?paciente_id=<?php echo $paciente_id; ?>" class="btn btn-primary" style="margin-top: 20px;">
                                <i class="fas fa-plus"></i> Registrar Primera Consulta
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>