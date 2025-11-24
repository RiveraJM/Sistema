<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// ========================================
// DASHBOARD PARA MÉDICOS
// ========================================
if (esMedico()) {
    // Obtener ID del médico
    $user_id = getUserId();
    $query_medico = "SELECT m.*, e.nombre as especialidad_nombre 
                     FROM medicos m
                     INNER JOIN especialidades e ON m.especialidad_id = e.id
                     WHERE m.usuario_id = :usuario_id";
    $stmt = $db->prepare($query_medico);
    $stmt->execute([':usuario_id' => $user_id]);
    $medico = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$medico) {
        die("Error: No se encontró información del médico");
    }
    
    $medico_id = $medico['id'];
    $user_name = getUserName();
    
    // Obtener horarios del médico
    $query_horarios = "SELECT * FROM horarios_medicos 
                       WHERE medico_id = :medico_id 
                       ORDER BY FIELD(dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo')";
    $stmt_horarios = $db->prepare($query_horarios);
    $stmt_horarios->execute([':medico_id' => $medico_id]);
    $horarios = $stmt_horarios->fetchAll(PDO::FETCH_ASSOC);
    
    // Estadísticas del médico
    $hoy = date('Y-m-d');
    
    // Citas de hoy
    $query_hoy = "SELECT COUNT(*) as total FROM citas 
                  WHERE medico_id = :medico_id AND fecha = :fecha";
    $stmt = $db->prepare($query_hoy);
    $stmt->execute([':medico_id' => $medico_id, ':fecha' => $hoy]);
    $citas_hoy = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Citas pendientes
    $query_pendientes = "SELECT COUNT(*) as total FROM citas 
                         WHERE medico_id = :medico_id 
                         AND fecha >= :fecha 
                         AND estado IN ('programada', 'confirmada')";
    $stmt = $db->prepare($query_pendientes);
    $stmt->execute([':medico_id' => $medico_id, ':fecha' => $hoy]);
    $citas_pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Citas esta semana
    $inicio_semana = date('Y-m-d', strtotime('monday this week'));
    $fin_semana = date('Y-m-d', strtotime('sunday this week'));
    
    $query_semana = "SELECT COUNT(*) as total FROM citas 
                     WHERE medico_id = :medico_id 
                     AND fecha BETWEEN :inicio AND :fin";
    $stmt = $db->prepare($query_semana);
    $stmt->execute([
        ':medico_id' => $medico_id,
        ':inicio' => $inicio_semana,
        ':fin' => $fin_semana
    ]);
    $citas_semana = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total pacientes atendidos
    $query_pacientes = "SELECT COUNT(DISTINCT paciente_id) as total 
                        FROM citas 
                        WHERE medico_id = :medico_id 
                        AND estado = 'atendida'";
    $stmt = $db->prepare($query_pacientes);
    $stmt->execute([':medico_id' => $medico_id]);
    $total_pacientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Próximas citas de hoy
    $query_proximas = "SELECT c.*, 
                       CONCAT(p.nombre, ' ', p.apellido) as paciente_nombre,
                       p.dni as paciente_dni
                       FROM citas c
                       INNER JOIN pacientes p ON c.paciente_id = p.id
                       WHERE c.medico_id = :medico_id 
                       AND c.fecha = :fecha
                       AND c.estado IN ('programada', 'confirmada', 'en_atencion')
                       ORDER BY c.hora ASC
                       LIMIT 5";
    $stmt = $db->prepare($query_proximas);
    $stmt->execute([':medico_id' => $medico_id, ':fecha' => $hoy]);
    $proximas_citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular días que atiende
    $dias_atiende = count(array_filter($horarios, fn($h) => $h['estado'] === 'activo'));
    
    // Incluir vista del médico
    include 'dashboard-medico.php';
    exit();
}

// ========================================
// DASHBOARD PARA OTROS ROLES (Admin, Recepcionista, etc.)
// ========================================

// Obtener estadísticas
$stats = [];

// Total de pacientes
$query = "SELECT COUNT(*) as total FROM pacientes WHERE estado = 'activo'";
$stmt = $db->query($query);
$stats['pacientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Citas de hoy
$query = "SELECT COUNT(*) as total FROM citas WHERE DATE(fecha) = CURDATE()";
$stmt = $db->query($query);
$stats['citas_hoy'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Citas pendientes
$query = "SELECT COUNT(*) as total FROM citas WHERE estado IN ('programada', 'confirmada') AND fecha >= CURDATE()";
$stmt = $db->query($query);
$stats['citas_pendientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Médicos activos
$query = "SELECT COUNT(*) as total FROM medicos WHERE estado = 'activo'";
$stmt = $db->query($query);
$stats['medicos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Próximas citas del día
$query = "SELECT c.*, 
          CONCAT(p.nombre, ' ', p.apellido) as paciente_nombre,
          CONCAT(u.nombre, ' ', u.apellido) as medico_nombre,
          e.nombre as especialidad
          FROM citas c
          INNER JOIN pacientes p ON c.paciente_id = p.id
          INNER JOIN medicos m ON c.medico_id = m.id
          INNER JOIN usuarios u ON m.usuario_id = u.id
          INNER JOIN especialidades e ON m.especialidad_id = e.id
          WHERE DATE(c.fecha) = CURDATE()
          AND c.estado IN ('programada', 'confirmada')
          ORDER BY c.hora ASC
          LIMIT 10";
$stmt = $db->query($query);
$proximas_citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Notificaciones no leídas
try {
    $check_table = $db->query("SHOW TABLES LIKE 'notificaciones'")->rowCount();
    
    if ($check_table > 0) {
        $user_id = $_SESSION['user_id'] ?? null;
        
        if ($user_id) {
            $query = "SELECT COUNT(*) as total FROM notificaciones 
                      WHERE usuario_id = :user_id AND leida = 0";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $notificaciones_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        } else {
            $notificaciones_count = 0;
        }
    } else {
        $notificaciones_count = 0;
    }
} catch (Exception $e) {
    $notificaciones_count = 0;
    error_log("Error al obtener notificaciones: " . $e->getMessage());
}

// Configurar fecha en español
$dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$fecha_actual = $dias[date('w')] . ', ' . date('d') . ' de ' . $meses[date('n')-1] . ' de ' . date('Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Clínico</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="main-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="dashboard-content">
                <div class="page-header">
                    <h1 class="page-title">Dashboard</h1>
                    <p class="page-subtitle">Bienvenido, <?php echo getUserName(); ?> - <?php echo $fecha_actual; ?></p>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-icon primary">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['pacientes']); ?></div>
                        <div class="stat-label">Total Pacientes</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i> 12% este mes
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-icon success">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo $stats['citas_hoy']; ?></div>
                        <div class="stat-label">Citas Hoy</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i> 8% vs ayer
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-icon warning">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo $stats['citas_pendientes']; ?></div>
                        <div class="stat-label">Citas Pendientes</div>
                        <div class="stat-change positive">
                            <i class="fas fa-calendar"></i> Próximas
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-icon primary">
                                <i class="fas fa-user-md"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo $stats['medicos']; ?></div>
                        <div class="stat-label">Médicos Activos</div>
                        <div class="stat-change positive">
                            <i class="fas fa-check"></i> Disponibles
                        </div>
                    </div>
                </div>
                
                <!-- Content Grid -->
                <div class="content-grid">
                    <!-- Próximas Citas -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Citas de Hoy</h3>
                            <div class="card-actions">
                                <button class="btn btn-primary btn-sm" onclick="location.href='citas.php'">
                                    Ver Todas
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (count($proximas_citas) > 0): ?>
                                <div class="table-container">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Hora</th>
                                                <th>Paciente</th>
                                                <th>Médico</th>
                                                <th>Especialidad</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($proximas_citas as $cita): ?>
                                            <tr>
                                                <td><strong><?php echo date('H:i', strtotime($cita['hora'])); ?></strong></td>
                                                <td><?php echo htmlspecialchars($cita['paciente_nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($cita['medico_nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($cita['especialidad']); ?></td>
                                                <td>
                                                    <?php
                                                    $badge_class = 'badge-warning';
                                                    if ($cita['estado'] === 'confirmada') $badge_class = 'badge-success';
                                                    elseif ($cita['estado'] === 'en_atencion') $badge_class = 'badge-primary';
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $cita['estado'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="verCita(<?php echo $cita['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                    <i class="fas fa-calendar-times" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                                    <p>No hay citas programadas para hoy</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Panel Lateral -->
                    <div>
                        <!-- Acciones Rápidas -->
                        <div class="card mb-2">
                            <div class="card-header">
                                <h3 class="card-title">Acciones Rápidas</h3>
                            </div>
                            <div class="card-body">
                                <?php if (puedeCrearCitas()): ?>
                                <a href="nueva-cita.php" class="btn btn-primary" style="width: 100%; margin-bottom: 10px;">
                                    <i class="fas fa-plus"></i> Nueva Cita
                                </a>
                                <?php endif; ?>
                                
                                <?php if (hasPermission('crear_paciente')): ?>
                                <a href="nuevo-paciente.php" class="btn btn-success" style="width: 100%; margin-bottom: 10px;">
                                    <i class="fas fa-user-plus"></i> Nuevo Paciente
                                </a>
                                <?php endif; ?>
                                
                                <?php if (esAdmin()): ?>
                                <a href="reportes.php" class="btn btn-secondary" style="width: 100%;">
                                    <i class="fas fa-chart-bar"></i> Ver Reportes
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Resumen del Día -->
                        <div class="card mb-2">
                            <div class="card-header">
                                <h3 class="card-title">Resumen del Día</h3>
                            </div>
                            <div class="card-body">
                                <div style="padding: 15px; background: var(--background-main); border-radius: var(--radius-sm); margin-bottom: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <span style="color: var(--text-secondary); font-size: 14px;">
                                            <i class="fas fa-calendar-check"></i> Citas Confirmadas
                                        </span>
                                        <strong style="font-size: 18px; color: var(--primary-color);">
                                            <?php 
                                            $confirmadas = array_filter($proximas_citas, function($c) { 
                                                return $c['estado'] === 'confirmada'; 
                                            });
                                            echo count($confirmadas);
                                            ?>
                                        </strong>
                                    </div>
                                </div>
                                
                                <div style="padding: 15px; background: var(--background-main); border-radius: var(--radius-sm); margin-bottom: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <span style="color: var(--text-secondary); font-size: 14px;">
                                            <i class="fas fa-clock"></i> Citas Programadas
                                        </span>
                                        <strong style="font-size: 18px; color: #F59E0B;">
                                            <?php 
                                            $programadas = array_filter($proximas_citas, function($c) { 
                                                return $c['estado'] === 'programada'; 
                                            });
                                            echo count($programadas);
                                            ?>
                                        </strong>
                                    </div>
                                </div>
                                
                                <div style="padding: 15px; background: var(--background-main); border-radius: var(--radius-sm);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <span style="color: var(--text-secondary); font-size: 14px;">
                                            <i class="fas fa-stethoscope"></i> En Atención
                                        </span>
                                        <strong style="font-size: 18px; color: #10B981;">
                                            <?php 
                                            $en_atencion = array_filter($proximas_citas, function($c) { 
                                                return $c['estado'] === 'en_atencion'; 
                                            });
                                            echo count($en_atencion);
                                            ?>
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Notificaciones Recientes -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Notificaciones</h3>
                                <?php if ($notificaciones_count > 0): ?>
                                <span class="badge badge-danger"><?php echo $notificaciones_count; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <div style="text-align: center; padding: 20px; color: var(--text-secondary);">
                                    <i class="fas fa-bell" style="font-size: 36px; margin-bottom: 10px; opacity: 0.3;"></i>
                                    <p style="font-size: 14px;">No hay notificaciones nuevas</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/main.js"></script>
    <script>
        function verCita(citaId) {
            window.location.href = 'detalle-cita.php?id=' + citaId;
        }
    </script>
</body>
</html>