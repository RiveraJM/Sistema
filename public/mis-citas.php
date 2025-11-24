<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Solo médicos pueden ver esta vista
if (!esMedico()) {
    header("Location: citas.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener ID del médico
$user_id = getUserId();
$query_medico = "SELECT id FROM medicos WHERE usuario_id = :usuario_id";
$stmt_medico = $db->prepare($query_medico);
$stmt_medico->execute([':usuario_id' => $user_id]);
$medico_data = $stmt_medico->fetch(PDO::FETCH_ASSOC);

if (!$medico_data) {
    die("Error: No se encontró el médico asociado a este usuario");
}

$medico_id = $medico_data['id'];

// ========== INICIALIZACIÓN DE FECHAS (ANTES DE USARLAS) ==========
$semana_offset = isset($_GET['semana']) ? (int)$_GET['semana'] : 0;
$fecha_base = new DateTime();
if ($semana_offset != 0) {
    $fecha_base->modify(($semana_offset * 7) . ' days');
}

// Calcular inicio y fin de semana (Lunes a Domingo)
$inicio_semana = clone $fecha_base;
$inicio_semana->modify('monday this week');
$fin_semana = clone $inicio_semana;
$fin_semana->modify('+6 days');

// Obtener citas de la semana
$query_citas = "SELECT 
                c.*,
                CONCAT(p.nombre, ' ', p.apellido) as paciente_nombre,
                p.dni as paciente_dni,
                p.telefono as paciente_telefono,
                e.nombre as especialidad
                FROM citas c
                INNER JOIN pacientes p ON c.paciente_id = p.id
                INNER JOIN medicos m ON c.medico_id = m.id
                INNER JOIN especialidades e ON m.especialidad_id = e.id
                WHERE c.medico_id = :medico_id
                AND c.fecha BETWEEN :inicio AND :fin
                ORDER BY c.fecha, c.hora";

$stmt_citas = $db->prepare($query_citas);
$stmt_citas->execute([
    ':medico_id' => $medico_id,
    ':inicio' => $inicio_semana->format('Y-m-d'),
    ':fin' => $fin_semana->format('Y-m-d')
]);
$citas = $stmt_citas->fetchAll(PDO::FETCH_ASSOC);

// Organizar citas por día y hora
$citas_semana = [];
foreach ($citas as $cita) {
    $dia = date('N', strtotime($cita['fecha'])); // 1=Lunes, 7=Domingo
    $hora = substr($cita['hora'], 0, 5); // HH:MM
    
    if (!isset($citas_semana[$dia])) {
        $citas_semana[$dia] = [];
    }
    
    if (!isset($citas_semana[$dia][$hora])) {
        $citas_semana[$dia][$hora] = [];
    }
    
    $citas_semana[$dia][$hora][] = $cita;
}

// Horarios disponibles (de 8 AM a 8 PM, cada 30 min)
$horarios = [];
for ($h = 8; $h < 20; $h++) {
    $horarios[] = sprintf('%02d:00', $h);
    $horarios[] = sprintf('%02d:30', $h);
}

// Colores por estado
$colores_estado = [
    'programada' => '#FEF3C7',
    'confirmada' => '#86EFAC',
    'en_atencion' => '#93C5FD',
    'atendida' => '#D1D5DB',
    'cancelada' => '#FECACA',
    'ausente' => '#FEE2E2'
];

$colores_borde = [
    'programada' => '#F59E0B',
    'confirmada' => '#10B981',
    'en_atencion' => '#3B82F6',
    'atendida' => '#6B7280',
    'cancelada' => '#EF4444',
    'ausente' => '#DC2626'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Citas - Sistema Clínico</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .vista-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
            margin-top: 20px;
        }
        
        @media (max-width: 1200px) {
            .vista-container {
                grid-template-columns: 1fr;
            }
        }
        
        .horario-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .lista-citas-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-height: 800px;
            overflow-y: auto;
        }
        
        .semana-navegacion {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 15px;
            background: linear-gradient(135deg, #00D4D4, #00A0A0);
            border-radius: 12px;
            color: white;
        }
        
        .semana-navegacion h2 {
            margin: 0;
            font-size: 20px;
        }
        
        .nav-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-nav {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-nav:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .horario-grid {
            display: grid;
            grid-template-columns: 60px repeat(7, 1fr);
            gap: 1px;
            background: #E5E7EB;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .horario-header {
            background: #F9FAFB;
            padding: 12px 5px;
            text-align: center;
            font-weight: 600;
            color: #1F2937;
            font-size: 11px;
        }
        
        .horario-header.dia-actual {
            background: #00D4D4;
            color: white;
        }
        
        .hora-label {
            background: #F9FAFB;
            padding: 5px;
            text-align: center;
            font-size: 10px;
            font-weight: 600;
            color: #6B7280;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .celda-horario {
            background: white;
            min-height: 50px;
            padding: 3px;
            position: relative;
        }
        
        .cita-bloque {
            padding: 4px 6px;
            border-radius: 4px;
            margin-bottom: 3px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 10px;
            border-left: 3px solid;
        }
        
        .cita-bloque:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .cita-bloque .paciente-nombre {
            font-weight: 600;
            color: #1F2937;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Lista de citas */
        .lista-citas-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #E5E7EB;
        }
        
        .lista-citas-header h3 {
            margin: 0 0 5px 0;
            font-size: 18px;
            color: #1F2937;
        }
        
        .cita-item {
            padding: 15px;
            margin-bottom: 10px;
            background: #F9FAFB;
            border-radius: 8px;
            border-left: 4px solid;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .cita-item:hover {
            background: #F3F4F6;
            transform: translateX(5px);
        }
        
        .cita-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .cita-item-paciente {
            font-weight: 600;
            font-size: 14px;
            color: #1F2937;
        }
        
        .cita-item-hora {
            font-size: 13px;
            color: #6B7280;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .cita-item-info {
            font-size: 12px;
            color: #6B7280;
            margin-bottom: 5px;
        }
        
        .badge-mini {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            color: white;
        }
        
        .estadisticas-mini {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .stat-mini {
            background: #F9FAFB;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-mini-numero {
            font-size: 20px;
            font-weight: bold;
            color: #00D4D4;
        }
        
        .stat-mini-label {
            font-size: 10px;
            color: #6B7280;
            margin-top: 3px;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #E5E7EB;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6B7280;
        }
        
        .detalle-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .detalle-item {
            padding: 12px;
            background: #F9FAFB;
            border-radius: 8px;
        }
        
        .detalle-item label {
            font-size: 11px;
            color: #6B7280;
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .detalle-item .valor {
            font-size: 14px;
            font-weight: 600;
            color: #1F2937;
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
                        <h1 class="page-title">Mis Citas</h1>
                        <p class="page-subtitle">Horario semanal y listado de consultas</p>
                    </div>
                </div>

                <!-- Estadísticas mini -->
                <div class="estadisticas-mini">
                    <div class="stat-mini">
                        <div class="stat-mini-numero"><?php echo count($citas); ?></div>
                        <div class="stat-mini-label">Total Semana</div>
                    </div>
                    <div class="stat-mini">
                        <div class="stat-mini-numero" style="color: #10B981;">
                            <?php echo count(array_filter($citas, fn($c) => $c['estado'] === 'confirmada')); ?>
                        </div>
                        <div class="stat-mini-label">Confirmadas</div>
                    </div>
                    <div class="stat-mini">
                        <div class="stat-mini-numero" style="color: #F59E0B;">
                            <?php echo count(array_filter($citas, fn($c) => $c['estado'] === 'programada')); ?>
                        </div>
                        <div class="stat-mini-label">Pendientes</div>
                    </div>
                    <div class="stat-mini">
                        <div class="stat-mini-numero" style="color: #6B7280;">
                            <?php echo count(array_filter($citas, fn($c) => $c['estado'] === 'atendida')); ?>
                        </div>
                        <div class="stat-mini-label">Atendidas</div>
                    </div>
                </div>

                <!-- Navegación de semana -->
                <div class="semana-navegacion">
                    <div class="nav-buttons">
                        <a href="?semana=<?php echo $semana_offset - 1; ?>" class="btn-nav">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>
                        <a href="?semana=0" class="btn-nav">
                            <i class="fas fa-calendar-day"></i> Hoy
                        </a>
                    </div>
                    
                    <div style="text-align: center;">
                        <h2>
                            Semana del <?php echo $inicio_semana->format('d/m/Y'); ?> 
                            al <?php echo $fin_semana->format('d/m/Y'); ?>
                        </h2>
                    </div>
                    
                    <div class="nav-buttons">
                        <a href="?semana=<?php echo $semana_offset + 1; ?>" class="btn-nav">
                            Siguiente <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Vista combinada: Horario + Lista -->
                <div class="vista-container">
                    <!-- Horario Semanal -->
                    <div class="horario-container">
                        <h3 style="margin: 0 0 15px 0; font-size: 16px; color: #1F2937;">
                            <i class="fas fa-calendar-week"></i> Horario Semanal
                        </h3>
                        
                        <div style="overflow-x: auto;">
                            <div class="horario-grid">
                                <!-- Header -->
                                <div class="horario-header">Hora</div>
                                <?php
                                $dias_semana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
                                $fecha_actual = clone $inicio_semana;
                                $hoy = date('Y-m-d');
                                
                                for ($i = 0; $i < 7; $i++):
                                    $es_hoy = $fecha_actual->format('Y-m-d') === $hoy;
                                ?>
                                <div class="horario-header <?php echo $es_hoy ? 'dia-actual' : ''; ?>">
                                    <?php echo $dias_semana[$i]; ?><br>
                                    <strong><?php echo $fecha_actual->format('d'); ?></strong>
                                </div>
                                <?php
                                    $fecha_actual->modify('+1 day');
                                endfor;
                                ?>

                                <!-- Filas de horarios -->
                                <?php foreach ($horarios as $hora): ?>
                                    <div class="hora-label"><?php echo $hora; ?></div>
                                    
                                    <?php for ($dia = 1; $dia <= 7; $dia++): ?>
                                        <div class="celda-horario">
                                            <?php
                                            if (isset($citas_semana[$dia][$hora])) {
                                                foreach ($citas_semana[$dia][$hora] as $cita) {
                                                    $color_fondo = $colores_estado[$cita['estado']] ?? '#F3F4F6';
                                                    $color_borde = $colores_borde[$cita['estado']] ?? '#9CA3AF';
                                                    ?>
                                                    <div class="cita-bloque" 
                                                         style="background: <?php echo $color_fondo; ?>; border-left-color: <?php echo $color_borde; ?>;"
                                                         onclick='verDetalleCita(<?php echo json_encode($cita, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                                        <div class="paciente-nombre">
                                                            <?php echo htmlspecialchars($cita['paciente_nombre']); ?>
                                                        </div>
                                                    </div>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </div>
                                    <?php endfor; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Citas -->
                    <div class="lista-citas-container">
                        <div class="lista-citas-header">
                            <h3><i class="fas fa-list"></i> Lista de Citas</h3>
                            <p style="font-size: 12px; color: #6B7280; margin: 5px 0 0 0;">
                                <?php echo count($citas); ?> citas esta semana
                            </p>
                        </div>

                        <?php if (count($citas) > 0): ?>
                            <?php foreach ($citas as $cita): 
                                $color_borde = $colores_borde[$cita['estado']] ?? '#9CA3AF';
                            ?>
                            <div class="cita-item" 
                                 style="border-left-color: <?php echo $color_borde; ?>;"
                                 onclick='verDetalleCita(<?php echo json_encode($cita, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                <div class="cita-item-header">
                                    <div class="cita-item-paciente">
                                        <?php echo htmlspecialchars($cita['paciente_nombre']); ?>
                                    </div>
                                    <span class="badge-mini" style="background: <?php echo $color_borde; ?>;">
                                        <?php echo ucfirst($cita['estado']); ?>
                                    </span>
                                </div>
                                <div class="cita-item-info">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d/m/Y', strtotime($cita['fecha'])); ?>
                                </div>
                                <div class="cita-item-hora">
                                    <i class="fas fa-clock"></i>
                                    <?php echo substr($cita['hora'], 0, 5); ?>
                                </div>
                                <?php if ($cita['motivo_consulta']): ?>
                                <div class="cita-item-info" style="margin-top: 5px; font-style: italic;">
                                    "<?php echo htmlspecialchars(substr($cita['motivo_consulta'], 0, 40)) . (strlen($cita['motivo_consulta']) > 40 ? '...' : ''); ?>"
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px 20px; color: #9CA3AF;">
                                <i class="fas fa-calendar-times" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 15px;"></i>
                                <p>No tienes citas esta semana</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detalle -->
    <div id="modalDetalle" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalle de Cita</h2>
                <button class="modal-close" onclick="cerrarModal()">×</button>
            </div>
            
            <div class="detalle-grid">
                <div class="detalle-item">
                    <label>Paciente</label>
                    <div class="valor" id="detallePaciente"></div>
                </div>
                <div class="detalle-item">
                    <label>DNI</label>
                    <div class="valor" id="detalleDNI"></div>
                </div>
                <div class="detalle-item">
                    <label>Fecha</label>
                    <div class="valor" id="detalleFecha"></div>
                </div>
                <div class="detalle-item">
                    <label>Hora</label>
                    <div class="valor" id="detalleHora"></div>
                </div>
                <div class="detalle-item">
                    <label>Teléfono</label>
                    <div class="valor" id="detalleTelefono"></div>
                </div>
                <div class="detalle-item">
                    <label>Estado</label>
                    <div class="valor" id="detalleEstado"></div>
                </div>
            </div>
            
            <div class="detalle-item" style="margin-bottom: 15px;">
                <label>Motivo de Consulta</label>
                <div class="valor" id="detalleMotivo"></div>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <a id="btnVerHistoria" href="#" class="btn btn-primary" style="flex: 1;" target="_blank">
                    <i class="fas fa-file-medical"></i> Ver Historia
                </a>
                <a id="btnAtender" href="#" class="btn btn-success" style="flex: 1;">
                    <i class="fas fa-user-md"></i> Atender
                </a>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        function verDetalleCita(cita) {
            document.getElementById('detallePaciente').textContent = cita.paciente_nombre;
            document.getElementById('detalleDNI').textContent = cita.paciente_dni;
            document.getElementById('detalleFecha').textContent = formatearFecha(cita.fecha);
            document.getElementById('detalleHora').textContent = cita.hora.substring(0, 5);
            document.getElementById('detalleTelefono').textContent = cita.paciente_telefono || 'No registrado';
            document.getElementById('detalleEstado').textContent = cita.estado.toUpperCase();
            document.getElementById('detalleMotivo').textContent = cita.motivo_consulta || 'No especificado';
            
            document.getElementById('btnVerHistoria').href = 'historia-clinica.php?paciente_id=' + cita.paciente_id;
            document.getElementById('btnAtender').href = 'atender-cita.php?cita_id=' + cita.id;
            
            document.getElementById('modalDetalle').classList.add('active');
        }
        
        function cerrarModal() {
            document.getElementById('modalDetalle').classList.remove('active');
        }
        
        function formatearFecha(fecha) {
            const d = new Date(fecha + 'T00:00:00');
            const opciones = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
            return d.toLocaleDateString('es-PE', opciones);
        }
        
        document.getElementById('modalDetalle').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });
    </script>
</body>
</html>