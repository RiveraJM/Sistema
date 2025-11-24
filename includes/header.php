<?php
if (!isset($_SESSION)) {
    session_start();
}

// Cargar notificaciones
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$usuario_id = $_SESSION['usuario_id'] ?? null;
$notificaciones = [];
$total_no_leidas = 0;

if ($usuario_id) {
    try {
        // Verificar si existe la tabla de notificaciones
        $check_table = $db->query("SHOW TABLES LIKE 'notificaciones'");
        
        if ($check_table->rowCount() > 0) {
            // Obtener notificaciones no leídas (últimas 10)
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
        $notificaciones = [];
        $total_no_leidas = 0;
    }
}

function tiempo_transcurrido($fecha) {
    $ahora = new DateTime();
    $tiempo = new DateTime($fecha);
    $diff = $ahora->diff($tiempo);
    
    if ($diff->y > 0) return 'Hace ' . $diff->y . ' año' . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0) return 'Hace ' . $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '');
    if ($diff->d > 0) return 'Hace ' . $diff->d . ' día' . ($diff->d > 1 ? 's' : '');
    if ($diff->h > 0) return 'Hace ' . $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
    if ($diff->i > 0) return 'Hace ' . $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
    return 'Ahora mismo';
}
?>

<style>
/* Estilos para el header y notificaciones */
.top-header {
    background: linear-gradient(135deg, #00D4D4, #00A0A0);
    padding: 15px 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 100;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 20px;
    flex: 1;
}

.header-search {
    position: relative;
    flex: 1;
    max-width: 500px;
}

.header-search input {
    width: 100%;
    padding: 10px 40px 10px 15px;
    border: none;
    border-radius: 25px;
    font-size: 14px;
    background: rgba(255,255,255,0.9);
}

.header-search button {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #00D4D4;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 50%;
    transition: all 0.3s;
}

.header-search button:hover {
    background: rgba(0,212,212,0.1);
}

.header-right {
    display: flex;
    align-items: center;
    gap: 15px;
}

.header-icon-btn {
    position: relative;
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    transition: all 0.3s;
}

.header-icon-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: scale(1.05);
}

.notification-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    background: #EF4444;
    color: white;
    font-size: 11px;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 20px;
    text-align: center;
    border: 2px solid #00D4D4;
}

.user-menu {
    position: relative;
}

.user-menu-button {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 8px 15px;
    border-radius: 25px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
}

.user-menu-button:hover {
    background: rgba(255,255,255,0.3);
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: white;
    color: #00D4D4;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* Dropdown de notificaciones */
.notification-dropdown {
    position: absolute;
    top: 55px;
    right: 0;
    width: 400px;
    max-height: 500px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    overflow: hidden;
    z-index: 1000;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notification-header {
    padding: 15px 20px;
    border-bottom: 1px solid #E5E7EB;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #F9FAFB;
}

.notification-header h4 {
    margin: 0;
    font-size: 16px;
    color: #1F2937;
    font-weight: 600;
}

.btn-link {
    background: none;
    border: none;
    color: #00D4D4;
    font-size: 13px;
    cursor: pointer;
    padding: 0;
    font-weight: 500;
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
    border-bottom: 1px solid #E5E7EB;
    cursor: pointer;
    display: flex;
    gap: 12px;
    transition: all 0.2s;
}

.notification-item:hover {
    background: #F9FAFB;
}

.notification-item.unread {
    background: rgba(0, 212, 212, 0.05);
    border-left: 3px solid #00D4D4;
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 18px;
}

.notification-cita {
    background: rgba(0, 212, 212, 0.1);
    color: #00D4D4;
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
    min-width: 0;
}

.notification-content strong {
    display: block;
    font-size: 14px;
    color: #1F2937;
    margin-bottom: 4px;
    font-weight: 600;
}

.notification-content p {
    margin: 0 0 4px 0;
    font-size: 13px;
    color: #6B7280;
    line-height: 1.4;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.notification-content small {
    font-size: 11px;
    color: #9CA3AF;
}

.notification-empty {
    padding: 60px 20px;
    text-align: center;
    color: #9CA3AF;
}

.notification-empty i {
    font-size: 48px;
    opacity: 0.3;
    margin-bottom: 15px;
    display: block;
}

.notification-footer {
    padding: 12px 20px;
    border-top: 1px solid #E5E7EB;
    text-align: center;
    background: #F9FAFB;
}

.notification-footer a {
    color: #00D4D4;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
}

.notification-footer a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .notification-dropdown {
        width: 90vw;
        right: -50px;
    }
    
    .header-search {
        max-width: 200px;
    }
}
</style>

<header class="top-header">
    <div class="header-left">
        <!-- Búsqueda -->
        <div class="header-search">
            <form method="GET" action="buscar.php">
                <input type="text" 
                       name="q" 
                       placeholder="Buscar pacientes, citas, médicos..." 
                       autocomplete="off">
                <button type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>

    <div class="header-right">
        <!-- Botón Crear Nuevo (si tiene permisos) -->
        <?php if (hasPermission('crear_cita')): ?>
        <button class="header-icon-btn" onclick="window.location.href='nueva-cita.php'" title="Nueva Cita">
            <i class="fas fa-plus"></i>
        </button>
        <?php endif; ?>

        <!-- Notificaciones -->
        <div style="position: relative;">
            <button class="header-icon-btn" onclick="toggleNotificaciones()" id="btnNotificaciones" type="button" title="Notificaciones">
                <i class="fas fa-bell"></i>
                <?php if ($total_no_leidas > 0): ?>
                <span class="notification-badge"><?php echo $total_no_leidas > 9 ? '9+' : $total_no_leidas; ?></span>
                <?php endif; ?>
            </button>
            
            <!-- Dropdown de Notificaciones -->
            <div id="notificationDropdown" class="notification-dropdown" style="display: none;">
                <div class="notification-header">
                    <h4>Notificaciones</h4>
                    <?php if ($total_no_leidas > 0): ?>
                    <button onclick="marcarTodasLeidas()" class="btn-link" type="button">
                        Marcar todas como leídas
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="notification-list">
                    <?php if (count($notificaciones) > 0): ?>
                        <?php foreach ($notificaciones as $notif): ?>
                        <div class="notification-item <?php echo $notif['leida'] ? 'read' : 'unread'; ?>" 
                             onclick="abrirNotificacion(<?php echo $notif['id']; ?>, '<?php echo htmlspecialchars($notif['enlace'] ?? '#'); ?>')">
                            <div class="notification-icon notification-<?php echo htmlspecialchars($notif['tipo']); ?>">
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

        <!-- Configuración (solo admin) -->
        <?php if (getUserRole() === 'admin'): ?>
        <button class="header-icon-btn" onclick="window.location.href='configuracion.php'" title="Configuración">
            <i class="fas fa-cog"></i>
        </button>
        <?php endif; ?>

        <!-- Usuario -->
        <div class="user-menu">
            <button class="user-menu-button" onclick="toggleUserMenu()" type="button">
                <div class="user-avatar">
                    <?php 
                    $nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
                    echo strtoupper(substr($nombre, 0, 1)); 
                    ?>
                </div>
                <span><?php echo htmlspecialchars(explode(' ', $nombre)[0]); ?></span>
                <i class="fas fa-chevron-down" style="font-size: 12px;"></i>
            </button>
            
            <!-- Dropdown del usuario (crear en el siguiente paso si quieres) -->
        </div>
    </div>
</header>

<script>
// Toggle notificaciones
function toggleNotificaciones() {
    const dropdown = document.getElementById('notificationDropdown');
    const isVisible = dropdown.style.display === 'block';
    
    // Cerrar todos los dropdowns primero
    document.querySelectorAll('.notification-dropdown, .user-dropdown').forEach(el => {
        el.style.display = 'none';
    });
    
    // Toggle el dropdown de notificaciones
    dropdown.style.display = isVisible ? 'none' : 'block';
}

// Marcar notificación como leída y abrir enlace
function abrirNotificacion(id, enlace) {
    // Marcar como leída
    fetch('api/marcar-notificacion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: id })
    }).then(() => {
        // Actualizar badge
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            let count = parseInt(badge.textContent);
            if (count > 1) {
                badge.textContent = count - 1;
            } else {
                badge.remove();
            }
        }
    });
    
    // Abrir enlace si existe
    if (enlace && enlace !== '#' && enlace !== '') {
        window.location.href = enlace;
    } else {
        // Si no hay enlace, solo cerrar el dropdown
        document.getElementById('notificationDropdown').style.display = 'none';
    }
}

// Marcar todas como leídas
function marcarTodasLeidas() {
    fetch('api/marcar-todas-leidas.php', {
        method: 'POST'
    }).then(() => {
        location.reload();
    });
}

// Toggle menú de usuario
function toggleUserMenu() {
    // Implementar si quieres dropdown de usuario
    window.location.href = 'perfil.php';
}

// Cerrar dropdowns al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.header-icon-btn') && 
        !e.target.closest('.notification-dropdown') &&
        !e.target.closest('.user-menu-button')) {
        document.querySelectorAll('.notification-dropdown, .user-dropdown').forEach(el => {
            el.style.display = 'none';
        });
    }
});

// Prevenir que los clics dentro del dropdown lo cierren
document.getElementById('notificationDropdown')?.addEventListener('click', function(e) {
    e.stopPropagation();
});
</script>