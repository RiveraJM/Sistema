<?php
// Archivo: includes/notificaciones.php

if (!isset($db)) {
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
}

$usuario_id = $_SESSION['usuario_id'] ?? null;
$notificaciones = [];
$total_no_leidas = 0;

if ($usuario_id) {
    try {
        // Verificar si existe la tabla de notificaciones
        $check_table = $db->query("SHOW TABLES LIKE 'notificaciones'");
        
        if ($check_table->rowCount() > 0) {
            // Obtener notificaciones no leídas
            $query_notificaciones = "SELECT * FROM notificaciones 
                                    WHERE usuario_id = :usuario_id 
                                    AND leida = 0 
                                    ORDER BY fecha_creacion DESC 
                                    LIMIT 10";
            
            $stmt_notificaciones = $db->prepare($query_notificaciones);
            $stmt_notificaciones->execute([':usuario_id' => $usuario_id]);
            $notificaciones = $stmt_notificaciones->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total no leídas
            $query_count = "SELECT COUNT(*) as total FROM notificaciones 
                           WHERE usuario_id = :usuario_id AND leida = 0";
            $stmt_count = $db->prepare($query_count);
            $stmt_count->execute([':usuario_id' => $usuario_id]);
            $total_no_leidas = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
        }
    } catch (PDOException $e) {
        // Si hay error, simplemente no mostramos notificaciones
        $notificaciones = [];
        $total_no_leidas = 0;
    }
}
?>

<!-- HTML de Notificaciones -->
<div class="notification-wrapper">
    <button class="notification-bell" onclick="toggleNotificaciones()" type="button">
        <i class="fas fa-bell"></i>
        <?php if ($total_no_leidas > 0): ?>
        <span class="notification-badge"><?php echo $total_no_leidas > 9 ? '9+' : $total_no_leidas; ?></span>
        <?php endif; ?>
    </button>
    
    <div id="notificationDropdown" class="notification-dropdown" style="display: none;">
        <div class="notification-header">
            <h4>Notificaciones</h4>
            <?php if ($total_no_leidas > 0): ?>
            <button onclick="marcarTodasLeidas()" class="btn-link">
                Marcar todas como leídas
            </button>
            <?php endif; ?>
        </div>
        
        <div class="notification-list">
            <?php if (count($notificaciones) > 0): ?>
                <?php foreach ($notificaciones as $notif): ?>
                <div class="notification-item <?php echo $notif['leida'] ? 'read' : 'unread'; ?>" 
                     onclick="abrirNotificacion(<?php echo $notif['id']; ?>, '<?php echo $notif['enlace'] ?? '#'; ?>')">
                    <div class="notification-icon notification-<?php echo $notif['tipo']; ?>">
                        <i class="fas <?php 
                            echo $notif['tipo'] === 'cita' ? 'fa-calendar-check' : 
                                ($notif['tipo'] === 'urgente' ? 'fa-exclamation-circle' : 
                                ($notif['tipo'] === 'info' ? 'fa-info-circle' : 'fa-bell')); 
                        ?>"></i>
                    </div>
                    <div class="notification-content">
                        <strong><?php echo htmlspecialchars($notif['titulo']); ?></strong>
                        <p><?php echo htmlspecialchars($notif['mensaje']); ?></p>
                        <small><?php echo tiempo_transcurrido($notif['fecha_creacion']); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="notification-empty">
                <i class="fas fa-bell-slash"></i>
                <p>No tienes notificaciones nuevas</p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (count($notificaciones) > 0): ?>
        <div class="notification-footer">
            <a href="notificaciones.php">Ver todas las notificaciones</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.notification-wrapper {
    position: relative;
}

.notification-bell {
    position: relative;
    background: transparent;
    border: none;
    color: var(--text-primary);
    font-size: 20px;
    cursor: pointer;
    padding: 8px;
    border-radius: 8px;
    transition: var(--transition);
}

.notification-bell:hover {
    background: var(--background-main);
}

.notification-badge {
    position: absolute;
    top: 2px;
    right: 2px;
    background: #EF4444;
    color: white;
    font-size: 10px;
    font-weight: bold;
    padding: 2px 5px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
}

.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 400px;
    max-height: 500px;
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    margin-top: 10px;
    z-index: 1000;
    display: flex;
    flex-direction: column;
}

.notification-header {
    padding: 15px 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-header h4 {
    margin: 0;
    font-size: 16px;
    color: var(--text-primary);
}

.btn-link {
    background: none;
    border: none;
    color: var(--primary-color);
    font-size: 13px;
    cursor: pointer;
    padding: 0;
}

.btn-link:hover {
    text-decoration: underline;
}

.notification-list {
    overflow-y: auto;
    max-height: 400px;
}

.notification-item {
    padding: 15px 20px;
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    display: flex;
    gap: 12px;
    transition: var(--transition);
}

.notification-item:hover {
    background: var(--background-main);
}

.notification-item.unread {
    background: rgba(0, 212, 212, 0.05);
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.notification-cita {
    background: rgba(0, 212, 212, 0.1);
    color: var(--primary-color);
}

.notification-urgente {
    background: rgba(239, 68, 68, 0.1);
    color: #EF4444;
}

.notification-info {
    background: rgba(59, 130, 246, 0.1);
    color: #3B82F6;
}

.notification-content {
    flex: 1;
}

.notification-content strong {
    display: block;
    font-size: 14px;
    color: var(--text-primary);
    margin-bottom: 4px;
}

.notification-content p {
    margin: 0 0 4px 0;
    font-size: 13px;
    color: var(--text-secondary);
    line-height: 1.4;
}

.notification-content small {
    font-size: 11px;
    color: var(--text-secondary);
}

.notification-empty {
    padding: 60px 20px;
    text-align: center;
    color: var(--text-secondary);
}

.notification-empty i {
    font-size: 48px;
    opacity: 0.3;
    margin-bottom: 15px;
}

.notification-footer {
    padding: 12px 20px;
    border-top: 1px solid var(--border-color);
    text-align: center;
}

.notification-footer a {
    color: var(--primary-color);
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
}

.notification-footer a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .notification-dropdown {
        width: 320px;
        right: -20px;
    }
}
</style>

<script>
function toggleNotificaciones() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

function abrirNotificacion(id, enlace) {
    // Marcar como leída
    fetch('api/marcar-notificacion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: id })
    });
    
    // Abrir enlace si existe
    if (enlace && enlace !== '#') {
        window.location.href = enlace;
    }
}

function marcarTodasLeidas() {
    fetch('api/marcar-todas-leidas.php', {
        method: 'POST'
    }).then(() => {
        location.reload();
    });
}

// Cerrar dropdown al hacer clic fuera
document.addEventListener('click', function(e) {
    const wrapper = document.querySelector('.notification-wrapper');
    const dropdown = document.getElementById('notificationDropdown');
    
    if (wrapper && !wrapper.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});
</script>

<?php
function tiempo_transcurrido($fecha) {
    $ahora = new DateTime();
    $tiempo = new DateTime($fecha);
    $diff = $ahora->diff($tiempo);
    
    if ($diff->y > 0) {
        return 'Hace ' . $diff->y . ' año' . ($diff->y > 1 ? 's' : '');
    } elseif ($diff->m > 0) {
        return 'Hace ' . $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '');
    } elseif ($diff->d > 0) {
        return 'Hace ' . $diff->d . ' día' . ($diff->d > 1 ? 's' : '');
    } elseif ($diff->h > 0) {
        return 'Hace ' . $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
    } elseif ($diff->i > 0) {
        return 'Hace ' . $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
    } else {
        return 'Ahora mismo';
    }
}
?>