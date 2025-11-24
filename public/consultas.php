<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Solo médicos pueden ver esta vista
if (!esMedico()) {
    header("Location: dashboard.php");
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

// Filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d'); // Hoy
$estado = $_GET['estado'] ?? '';

// Obtener consultas del médico
$query = "SELECT 
          c.id,
          c.fecha,
          c.hora,
          c.estado,
          c.tipo_cita,
          c.motivo_consulta,
          c.hora_llegada,
          c.hora_atencion,
          c.hora_finalizacion,
          CONCAT(p.nombre, ' ', p.apellido) as paciente_nombre,
          p.dni as paciente_dni,
          p.id as paciente_id
          FROM citas c
          INNER JOIN pacientes p ON c.paciente_id = p.id
          WHERE c.medico_id = :medico_id
          AND c.fecha BETWEEN :fecha_inicio AND :fecha_fin";

if ($estado) {
    $query .= " AND c.estado = :estado";
}

$query .= " ORDER BY c.fecha DESC, c.hora DESC";

$stmt = $db->prepare($query);
$params = [
    ':medico_id' => $medico_id,
    ':fecha_inicio' => $fecha_inicio,
    ':fecha_fin' => $fecha_fin
];

if ($estado) {
    $params[':estado'] = $estado;
}

$stmt->execute($params);
$consultas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Consultas - Sistema Clínico</title>
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
                    <div>
                        <h1 class="page-title">Mis Consultas</h1>
                        <p class="page-subtitle">Historial de atenciones realizadas</p>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #00D4D4; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="font-size: 24px; font-weight: bold; color: #00D4D4;">
                            <?php echo count($consultas); ?>
                        </div>
                        <div style="font-size: 12px; color: #6B7280;">Total Consultas</div>
                    </div>
                    
                    <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #6366F1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="font-size: 24px; font-weight: bold; color: #6366F1;">
                            <?php echo count(array_filter($consultas, fn($c) => $c['estado'] === 'atendida')); ?>
                        </div>
                        <div style="font-size: 12px; color: #6B7280;">Atendidas</div>
                    </div>
                    
                    <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #3B82F6; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="font-size: 24px; font-weight: bold; color: #3B82F6;">
                            <?php echo count(array_filter($consultas, fn($c) => $c['estado'] === 'en_atencion')); ?>
                        </div>
                        <div style="font-size: 12px; color: #6B7280;">En Atención</div>
                    </div>
                    
                    <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #10B981; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="font-size: 24px; font-weight: bold; color: #10B981;">
                            <?php echo count(array_filter($consultas, fn($c) => $c['estado'] === 'confirmada')); ?>
                        </div>
                        <div style="font-size: 12px; color: #6B7280;">Próximas</div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-2">
                    <div class="card-body">
                        <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Fecha Inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>">
                            </div>
                            
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Fecha Fin</label>
                                <input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>">
                            </div>
                            
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="confirmada" <?php echo $estado === 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                                    <option value="en_atencion" <?php echo $estado === 'en_atencion' ? 'selected' : ''; ?>>En Atención</option>
                                    <option value="atendida" <?php echo $estado === 'atendida' ? 'selected' : ''; ?>>Atendida</option>
                                    <option value="ausente" <?php echo $estado === 'ausente' ? 'selected' : ''; ?>>Ausente</option>
                                </select>
                            </div>
                            
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                                <a href="consultas.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de Consultas -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            Listado de Consultas
                            <span class="badge badge-primary"><?php echo count($consultas); ?> consultas</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($consultas) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Paciente</th>
                                        <th>DNI</th>
                                        <th>Motivo</th>
                                        <th>Estado</th>
                                        <th>Duración</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($consultas as $consulta): ?>
                                    <tr>
                                        <td><strong>#<?php echo $consulta['id']; ?></strong></td>
                                        <td><?php echo date('d/m/Y', strtotime($consulta['fecha'])); ?></td>
                                        <td><?php echo substr($consulta['hora'], 0, 5); ?></td>
                                        <td><?php echo htmlspecialchars($consulta['paciente_nombre']); ?></td>
                                        <td><?php echo $consulta['paciente_dni']; ?></td>
                                        <td>
                                            <?php 
                                            $motivo = $consulta['motivo_consulta'] ?? 'No especificado';
                                            echo htmlspecialchars(substr($motivo, 0, 30)) . (strlen($motivo) > 30 ? '...' : '');
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $badges = [
                                                'confirmada' => 'badge-success',
                                                'en_atencion' => 'badge-primary',
                                                'atendida' => 'badge-info',
                                                'ausente' => 'badge-danger'
                                            ];
                                            $badge_class = $badges[$consulta['estado']] ?? 'badge-secondary';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $consulta['estado'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            if ($consulta['hora_atencion'] && $consulta['hora_finalizacion']) {
                                                $inicio = new DateTime($consulta['hora_atencion']);
                                                $fin = new DateTime($consulta['hora_finalizacion']);
                                                $duracion = $inicio->diff($fin);
                                                echo $duracion->h . 'h ' . $duracion->i . 'm';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <a href="historia-clinica.php?paciente_id=<?php echo $consulta['paciente_id']; ?>" 
                                                   class="btn btn-sm btn-info" 
                                                   title="Ver Historia Clínica">
                                                    <i class="fas fa-file-medical"></i>
                                                </a>
                                                
                                                <?php if ($consulta['estado'] === 'confirmada' || $consulta['estado'] === 'en_atencion'): ?>
                                                <a href="atender-cita.php?cita_id=<?php echo $consulta['id']; ?>" 
                                                   class="btn btn-sm btn-success" 
                                                   title="Atender">
                                                    <i class="fas fa-user-md"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div style="text-align: center; padding: 60px 20px; color: #9CA3AF;">
                            <i class="fas fa-stethoscope" style="font-size: 64px; opacity: 0.3; display: block; margin-bottom: 20px;"></i>
                            <h3 style="color: #6B7280; margin: 0 0 10px 0;">No hay consultas</h3>
                            <p style="margin: 0;">Ajusta los filtros para ver más resultados</p>
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