<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

// Verificar permisos
if (!hasPermission('ver_medicos') && getUserRole() !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Filtros
$busqueda = $_GET['busqueda'] ?? '';
$especialidad_filtro = $_GET['especialidad'] ?? '';
$estado_filtro = $_GET['estado'] ?? 'activo';

// Construir query con filtros
$query = "SELECT m.*, 
          CONCAT(u.nombre, ' ', u.apellido) as nombre_completo,
          u.email, u.telefono,
          e.nombre as especialidad_nombre
          FROM medicos m
          INNER JOIN usuarios u ON m.usuario_id = u.id
          INNER JOIN especialidades e ON m.especialidad_id = e.id
          WHERE 1=1";

$params = [];

if ($busqueda) {
    $query .= " AND (u.nombre LIKE :busqueda OR u.apellido LIKE :busqueda OR m.numero_colegiatura LIKE :busqueda)";
    $params[':busqueda'] = "%$busqueda%";
}

if ($especialidad_filtro) {
    $query .= " AND m.especialidad_id = :especialidad";
    $params[':especialidad'] = $especialidad_filtro;
}

if ($estado_filtro) {
    $query .= " AND m.estado = :estado";
    $params[':estado'] = $estado_filtro;
}

$query .= " ORDER BY u.nombre, u.apellido";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener toda la lista de especialidades para filtro
$query_especialidades = "SELECT id, nombre FROM especialidades WHERE estado = 'activo' ORDER BY nombre";
$stmt_especialidades = $db->query($query_especialidades);
$especialidades = $stmt_especialidades->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Médicos - Sistema Clínico</title>
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
                        <h1 class="page-title">Gestión de Médicos</h1>
                        <p class="page-subtitle">Administra el personal médico y sus horarios</p>
                    </div>
                    <?php if (getUserRole() === 'admin'): ?>
                    <a href="nuevo-medico.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Médico
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Filtros -->
                <div class="card mb-2">
                    <div class="card-body">
                        <form method="GET" action="medicos.php" class="d-flex gap-2" style="align-items: flex-end; flex-wrap: wrap;">
                            <div class="form-group" style="margin-bottom: 0; flex: 2; min-width: 250px;">
                                <label class="form-label">Buscar</label>
                                <input type="text" name="busqueda" class="form-control" 
                                       placeholder="Nombre o N° Colegiatura..." 
                                       value="<?php echo htmlspecialchars($busqueda); ?>">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                                <label class="form-label">Especialidad</label>
                                <select name="especialidad" class="form-control">
                                    <option value="">Todas las especialidades</option>
                                    <?php foreach ($especialidades as $especialidad): ?>
                                    <option value="<?php echo $especialidad['id']; ?>" <?php echo $especialidad_filtro == $especialidad['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($especialidad['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
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
                            
                            <a href="medicos.php" class="btn btn-secondary" style="margin-bottom: 0;">
                                <i class="fas fa-redo"></i> Limpiar
                            </a>
                        </form>
                    </div>
                </div>

                <!-- Estadísticas rápidas -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 20px;">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="stat-value"><?php echo count($medicos); ?></div>
                        <div class="stat-label">Médicos Registrados</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value">
                            <?php echo count(array_filter($medicos, function($m) { return $m['estado'] === 'activo'; })); ?>
                        </div>
                        <div class="stat-label">Activos</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-stethoscope"></i>
                        </div>
                        <div class="stat-value"><?php echo count($especialidades); ?></div>
                        <div class="stat-label">Especialidades</div>
                    </div>
                </div>

                <!-- Tabla de Médicos -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            Listado de Médicos
                            <span class="badge badge-primary"><?php echo count($medicos); ?> médicos</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($medicos) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre Completo</th>
                                        <th>Especialidad</th>
                                        <th>N° Colegiatura</th>
                                        <th>Consultorio</th>
                                        <th>Teléfono</th>
                                        <th>Email</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($medicos as $medico): ?>
                                    <tr>
                                        <td><strong>#<?php echo $medico['id']; ?></strong></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($medico['nombre_completo']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">
                                                <?php echo htmlspecialchars($medico['especialidad_nombre']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($medico['numero_colegiatura']); ?></td>
                                        <td><?php echo htmlspecialchars($medico['consultorio']); ?></td>
                                        <td><?php echo htmlspecialchars($medico['telefono'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($medico['email']); ?></td>
                                        <td>
                                            <?php if ($medico['estado'] === 'activo'): ?>
                                                <span class="badge badge-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <button onclick="verMedico(<?php echo $medico['id']; ?>)" 
                                                        class="btn btn-sm btn-primary" title="Ver Detalle">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <button onclick="verHorarios(<?php echo $medico['id']; ?>)" 
                                                        class="btn btn-sm btn-success" title="Ver Horarios">
                                                    <i class="fas fa-clock"></i>
                                                </button>
                                                
                                                <?php if (getUserRole() === 'admin'): ?>
                                                <a href="editar-medico.php?id=<?php echo $medico['id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <?php if ($medico['estado'] === 'activo'): ?>
                                                <button onclick="cambiarEstadoMedico(<?php echo $medico['id']; ?>, 'inactivo')" 
                                                        class="btn btn-sm btn-danger" title="Desactivar">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                                <?php else: ?>
                                                <button onclick="cambiarEstadoMedico(<?php echo $medico['id']; ?>, 'activo')" 
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
                            <i class="fas fa-user-md" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                            <p>No se encontraron médicos con los filtros aplicados</p>
                            <?php if (getUserRole() === 'admin'): ?>
                            <a href="nuevo-medico.php" class="btn btn-primary" style="margin-top: 15px;">
                                <i class="fas fa-plus"></i> Registrar Primer Médico
                            </a>
                            <?php endif; ?>
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
                <h3 style="margin: 0;">Detalle del Médico</h3>
                <button onclick="cerrarModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-secondary);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="contenidoModal" style="padding: 25px;">
                Cargando...
            </div>
        </div>
    </div>

    <!-- Modal de Horarios -->
    <div id="modalHorarios" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 16px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <div style="padding: 25px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;">Horarios de Atención</h3>
                <button onclick="cerrarModalHorarios()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-secondary);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="contenidoModalHorarios" style="padding: 25px;">
                Cargando...
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        function verMedico(medicoId) {
            const modal = document.getElementById('modalDetalle');
            const contenido = document.getElementById('contenidoModal');
            
            modal.style.display = 'flex';
            contenido.innerHTML = 'Cargando...';
            
            fetch('detalle-medico.php?id=' + medicoId)
                .then(response => response.text())
                .then(data => {
                    contenido.innerHTML = data;
                })
                .catch(error => {
                    contenido.innerHTML = '<p style="color: red;">Error al cargar el detalle</p>';
                });
        }
        
        function verHorarios(medicoId) {
            const modal = document.getElementById('modalHorarios');
            const contenido = document.getElementById('contenidoModalHorarios');
            
            modal.style.display = 'flex';
            contenido.innerHTML = 'Cargando...';
            
            fetch('horarios-medico.php?id=' + medicoId)
                .then(response => response.text())
                .then(data => {
                    contenido.innerHTML = data;
                })
                .catch(error => {
                    contenido.innerHTML = '<p style="color: red;">Error al cargar los horarios</p>';
                });
        }
        
        function cerrarModal() {
            document.getElementById('modalDetalle').style.display = 'none';
        }
        
        function cerrarModalHorarios() {
            document.getElementById('modalHorarios').style.display = 'none';
        }
        
        function cambiarEstadoMedico(medicoId, nuevoEstado) {
            const mensaje = nuevoEstado === 'activo' ? '¿Activar este médico?' : '¿Desactivar este médico?';
            
            if (confirm(mensaje)) {
                fetch('cambiar-estado-medico.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        medico_id: medicoId,
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
        
        // Cerrar modales al hacer clic fuera
        document.getElementById('modalDetalle').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });
        
        document.getElementById('modalHorarios').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalHorarios();
            }
        });
    </script>
</body>
</html>