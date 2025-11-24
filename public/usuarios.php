<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

// Verificar permisos (solo admin)
if (getUserRole() !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Filtros
$busqueda = $_GET['busqueda'] ?? '';
$rol_filtro = $_GET['rol'] ?? '';
$estado_filtro = $_GET['estado'] ?? 'activo';

// Construir query con filtros
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM usuario_permisos WHERE usuario_id = u.id) as total_permisos
          FROM usuarios u
          WHERE 1=1";

$params = [];

if ($busqueda) {
    $query .= " AND (u.nombre LIKE :busqueda OR u.apellido LIKE :busqueda OR u.username LIKE :busqueda OR u.email LIKE :busqueda)";
    $params[':busqueda'] = "%$busqueda%";
}

if ($rol_filtro) {
    $query .= " AND u.rol = :rol";
    $params[':rol'] = $rol_filtro;
}

if ($estado_filtro) {
    $query .= " AND u.estado = :estado";
    $params[':estado'] = $estado_filtro;
}

$query .= " ORDER BY u.id DESC"; // CORREGIDO: Cambiado de fecha_registro a id

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
$query_stats = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN rol = 'admin' THEN 1 ELSE 0 END) as admins,
                SUM(CASE WHEN rol = 'medico' THEN 1 ELSE 0 END) as medicos,
                SUM(CASE WHEN rol = 'recepcionista' THEN 1 ELSE 0 END) as recepcionistas
                FROM usuarios";
$stmt_stats = $db->query($query_stats);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Sistema Clínico</title>
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
                        <h1 class="page-title">Gestión de Usuarios</h1>
                        <p class="page-subtitle">Administra todos los usuarios del sistema</p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <a href="permisos.php" class="btn btn-success">
                            <i class="fas fa-shield-alt"></i> Gestionar Permisos
                        </a>
                        <a href="nuevo-usuario.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Usuario
                        </a>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-2">
                    <div class="card-body">
                        <form method="GET" action="usuarios.php" class="d-flex gap-2" style="align-items: flex-end; flex-wrap: wrap;">
                            <div class="form-group" style="margin-bottom: 0; flex: 2; min-width: 250px;">
                                <label class="form-label">Buscar</label>
                                <input type="text" name="busqueda" class="form-control" 
                                       placeholder="Nombre, usuario, email..." 
                                       value="<?php echo htmlspecialchars($busqueda); ?>">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
                                <label class="form-label">Rol</label>
                                <select name="rol" class="form-control">
                                    <option value="">Todos los roles</option>
                                    <option value="admin" <?php echo $rol_filtro === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="medico" <?php echo $rol_filtro === 'medico' ? 'selected' : ''; ?>>Médico</option>
                                    <option value="recepcionista" <?php echo $rol_filtro === 'recepcionista' ? 'selected' : ''; ?>>Recepcionista</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="activo" <?php echo $estado_filtro === 'activo' ? 'selected' : ''; ?>>Activo</option>
                                    <option value="inactivo" <?php echo $estado_filtro === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="margin-bottom: 0;">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            
                            <a href="usuarios.php" class="btn btn-secondary" style="margin-bottom: 0;">
                                <i class="fas fa-redo"></i> Limpiar
                            </a>
                        </form>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px;">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total Usuarios</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['activos']; ?></div>
                        <div class="stat-label">Activos</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon danger">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['admins']; ?></div>
                        <div class="stat-label">Administradores</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon info">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['medicos']; ?></div>
                        <div class="stat-label">Médicos</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['recepcionistas']; ?></div>
                        <div class="stat-label">Recepcionistas</div>
                    </div>
                </div>

                <!-- Tabla de Usuarios -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            Listado de Usuarios
                            <span class="badge badge-primary"><?php echo count($usuarios); ?> usuarios</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($usuarios) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuario</th>
                                        <th>Nombre Completo</th>
                                        <th>Email</th>
                                        <th>Teléfono</th>
                                        <th>Rol</th>
                                        <th>Permisos</th>
                                        <th>Estado</th>
                                        <th>Último Acceso</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td><strong>#<?php echo $usuario['id']; ?></strong></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($usuario['username']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['telefono'] ?: '-'); ?></td>
                                        <td>
                                            <?php
                                            $rol_badges = [
                                                'admin' => '<span class="badge badge-danger"><i class="fas fa-user-shield"></i> Admin</span>',
                                                'medico' => '<span class="badge badge-primary"><i class="fas fa-user-md"></i> Médico</span>',
                                                'recepcionista' => '<span class="badge badge-warning"><i class="fas fa-user-tie"></i> Recepcionista</span>'
                                            ];
                                            echo $rol_badges[$usuario['rol']] ?? '<span class="badge badge-secondary">Usuario</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo $usuario['total_permisos']; ?> permisos
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($usuario['estado'] === 'activo'): ?>
                                                <span class="badge badge-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($usuario['ultimo_acceso']) {
                                                echo date('d/m/Y H:i', strtotime($usuario['ultimo_acceso']));
                                            } else {
                                                echo '<span style="color: var(--text-secondary);">Nunca</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <button onclick="verUsuario(<?php echo $usuario['id']; ?>)" 
                                                        class="btn btn-sm btn-primary" title="Ver Detalle">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <a href="editar-usuario.php?id=<?php echo $usuario['id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <a href="permisos-usuario.php?id=<?php echo $usuario['id']; ?>" 
                                                   class="btn btn-sm btn-success" title="Gestionar Permisos">
                                                    <i class="fas fa-shield-alt"></i>
                                                </a>
                                                
                                                <?php if ($usuario['id'] != getUserId()): ?>
                                                    <?php if ($usuario['estado'] === 'activo'): ?>
                                                    <button onclick="cambiarEstadoUsuario(<?php echo $usuario['id']; ?>, 'inactivo')" 
                                                            class="btn btn-sm btn-danger" title="Desactivar">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                    <?php else: ?>
                                                    <button onclick="cambiarEstadoUsuario(<?php echo $usuario['id']; ?>, 'activo')" 
                                                            class="btn btn-sm btn-success" title="Activar">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                            <i class="fas fa-users" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                            <p>No se encontraron usuarios con los filtros aplicados</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalle -->
    <div id="modalDetalle" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 16px; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <div style="padding: 25px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;">Detalle del Usuario</h3>
                <button onclick="cerrarModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-secondary);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="contenidoModal" style="padding: 25px;">
                Cargando...
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        function verUsuario(usuarioId) {
            const modal = document.getElementById('modalDetalle');
            const contenido = document.getElementById('contenidoModal');
            
            modal.style.display = 'flex';
            contenido.innerHTML = 'Cargando...';
            
            fetch('detalle-usuario.php?id=' + usuarioId)
                .then(response => response.text())
                .then(data => {
                    contenido.innerHTML = data;
                })
                .catch(error => {
                    contenido.innerHTML = '<p style="color: red;">Error al cargar el detalle</p>';
                });
        }
        
        function cerrarModal() {
            document.getElementById('modalDetalle').style.display = 'none';
        }
        
        function cambiarEstadoUsuario(usuarioId, nuevoEstado) {
            const mensaje = nuevoEstado === 'activo' ? '¿Activar este usuario?' : '¿Desactivar este usuario?';
            
            if (confirm(mensaje)) {
                fetch('cambiar-estado-usuario.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        usuario_id: usuarioId,
                        estado: nuevoEstado
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarAlerta('Estado actualizado correctamente', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        mostrarAlerta(data.message || 'Error al actualizar', 'error');
                    }
                })
                .catch(error => {
                    mostrarAlerta('Error en la conexión', 'error');
                });
            }
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modalDetalle').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });
    </script>
</body>
</html>