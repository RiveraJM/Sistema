<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

$usuario_id = getUserId();
$filtro = $_GET['filtro'] ?? 'todas'; // todas, no_leidas, leidas

// Construir query según filtro
$where_clause = "usuario_id = :usuario_id";
if ($filtro === 'no_leidas') {
    $where_clause .= " AND leida = 0";
} elseif ($filtro === 'leidas') {
    $where_clause .= " AND leida = 1";
}

$query = "SELECT * FROM notificaciones 
          WHERE $where_clause 
          ORDER BY fecha_creacion DESC 
          LIMIT 50";

$stmt = $db->prepare($query);
$stmt->execute([':usuario_id' => $usuario_id]);
$notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar por tipo
$query_stats = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN leida = 0 THEN 1 ELSE 0 END) as no_leidas,
                SUM(CASE WHEN leida = 1 THEN 1 ELSE 0 END) as leidas
                FROM notificaciones
                WHERE usuario_id = :usuario_id";
$stmt_stats = $db->prepare($query_stats);
$stmt_stats->execute([':usuario_id' => $usuario_id]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones - Sistema Clínico</title>
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
                        <h1 class="page-title">Notificaciones</h1>
                        <p class="page-subtitle">Gestiona todas tus notificaciones</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="padding: 0;">
                        <div style="display: flex; border-bottom: 2px solid var(--border-color);">
                            <a href="notificaciones.php?filtro=todas" 
                               class="filter-tab <?php echo $filtro === 'todas' ? 'active' : ''; ?>"
                               style="flex: 1; padding: 15px; text-align: center; text-decoration: none; color: var(--text-primary); border-bottom: 3px solid <?php echo $filtro === 'todas' ? 'var(--primary-color)' : 'transparent'; ?>;">
                                Todas (<?php echo $stats['total']; ?>)
                            </a>
                            <a href="notificaciones.php?filtro=no_leidas" 
                               class="filter-tab <?php echo $filtro === 'no_leidas' ? 'active' : ''; ?>"
                               style="flex: 1; padding: 15px; text-align: center; text-decoration: none; color: var(--text-primary); border-bottom: 3px solid <?php echo $filtro === 'no_leidas' ? 'var(--primary-color)' : 'transparent'; ?>;">
                                No leídas (<?php echo $stats['no_leidas']; ?>)
                            </a>
                            <a href="notificaciones.php?filtro=leidas" 
                               class="filter-tab <?php echo $filtro === 'leidas' ? 'active' : ''; ?>"
                               style="flex: 1; padding: 15px; text-align: center; text-decoration: none; color: var(--text-primary); border-bottom: 3px solid <?php echo $filtro === 'leidas' ? 'var(--primary-color)' : 'transparent'; ?>;">
                                Leídas (<?php echo $stats['leidas']; ?>)
                            </a>
                        </div>

                        <div style="padding: 20px;">
                            <?php if (count($notificaciones) > 0): ?>
                                <?php foreach ($notificaciones as $notif): ?>
                                <div class="notification-item <?php echo $notif['leida'] ? 'read' : 'unread'; ?>" 
                                     style="margin-bottom: 10px; padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; <?php echo !$notif['leida'] ? 'background: rgba(0,212,212,0.05);' : ''; ?>">
                                    <div style="display: flex; gap: 15px;">
                                        <div class="notification-icon notification-<?php echo $notif['tipo']; ?>" 
                                             style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                            <i class="fas <?php echo $notif['tipo'] === 'cita' ? 'fa-calendar-check' : 'fa-bell'; ?>" style="font-size: 20px;"></i>
                                        </div>
                                        <div style="flex: 1;">
                                            <strong style="display: block; margin-bottom: 5px;"><?php echo htmlspecialchars($notif['titulo']); ?></strong>
                                            <p style="margin: 0 0 8px 0; color: var(--text-secondary);"><?php echo htmlspecialchars($notif['mensaje']); ?></p>
                                            <small style="color: var(--text-secondary);"><?php echo date('d/m/Y H:i', strtotime($notif['fecha_creacion'])); ?></small>
                                        </div>
                                        <?php if (!$notif['leida']): ?>
                                        <button onclick="marcarLeida(<?php echo $notif['id']; ?>)" class="btn btn-sm btn-primary">
                                            Marcar como leída
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                                <i class="fas fa-bell-slash" style="font-size: 64px; opacity: 0.3; margin-bottom: 20px;"></i>
                                <h3>No hay notificaciones</h3>
                                <p>No tienes notificaciones en esta categoría</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        function marcarLeida(id) {
            fetch('api/marcar-notificacion.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: id })
            }).then(() => location.reload());
        }
    </script>
</body>
</html>