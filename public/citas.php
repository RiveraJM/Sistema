<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar permisos
if (!hasPermission('ver_citas') && !esRecepcionista()) {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Filtros - CORREGIDO: No filtrar por fecha por defecto
$fecha_filtro = $_GET['fecha'] ?? '';
$estado_filtro = $_GET['estado'] ?? '';
$medico_filtro = $_GET['medico'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Construir query con filtros
$query = "SELECT c.*, 
          CONCAT(p.nombre, ' ', p.apellido) as paciente_nombre,
          p.dni as paciente_dni,
          CONCAT(u.nombre, ' ', u.apellido) as medico_nombre,
          e.nombre as especialidad,
          m.consultorio
          FROM citas c
          INNER JOIN pacientes p ON c.paciente_id = p.id
          INNER JOIN medicos m ON c.medico_id = m.id
          INNER JOIN usuarios u ON m.usuario_id = u.id
          INNER JOIN especialidades e ON m.especialidad_id = e.id
          WHERE 1=1";

$params = [];

// Solo filtrar por fecha si se especifica
if ($fecha_filtro) {
    $query .= " AND DATE(c.fecha) = :fecha";
    $params[':fecha'] = $fecha_filtro;
}

if ($estado_filtro) {
    $query .= " AND c.estado = :estado";
    $params[':estado'] = $estado_filtro;
}

if ($medico_filtro) {
    $query .= " AND c.medico_id = :medico";
    $params[':medico'] = $medico_filtro;
}

if ($busqueda) {
    $query .= " AND (p.nombre LIKE :busqueda OR p.apellido LIKE :busqueda OR p.dni LIKE :busqueda)";
    $params[':busqueda'] = "%$busqueda%";
}

$query .= " ORDER BY c.fecha DESC, c.hora DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de médicos para filtro
$query_medicos = "SELECT m.id, CONCAT(u.nombre, ' ', u.apellido) as nombre
                  FROM medicos m
                  INNER JOIN usuarios u ON m.usuario_id = u.id
                  ORDER BY u.nombre";
$stmt_medicos = $db->query($query_medicos);
$medicos = $stmt_medicos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Citas - Sistema Clínico</title>
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
                        <h1 class="page-title">Gestión de Citas</h1>
                        <p class="page-subtitle">Administra todas las citas médicas</p>
                    </div>
                    <?php if (puedeCrearCitas()): ?>
                    <a href="nueva-cita.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Cita
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Filtros -->
                <div class="card mb-2">
                    <div class="card-body">
                        <form method="GET" action="citas.php" class="d-flex gap-2" style="align-items: flex-end; flex-wrap: wrap;">
                            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                                <label class="form-label">Fecha</label>
                                <input type="date" name="fecha" class="form-control" value="<?php echo $fecha_filtro; ?>" placeholder="Todas las fechas">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="">Todos los estados</option>
                                    <option value="programada" <?php echo $estado_filtro === 'programada' ? 'selected' : ''; ?>>Programada</option>
                                    <option value="confirmada" <?php echo $estado_filtro === 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                                    <option value="en_atencion" <?php echo $estado_filtro === 'en_atencion' ? 'selected' : ''; ?>>En Atención</option>
                                    <option value="atendida" <?php echo $estado_filtro === 'atendida' ? 'selected' : ''; ?>>Atendida</option>
                                    <option value="cancelada" <?php echo $estado_filtro === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                    <option value="ausente" <?php echo $estado_filtro === 'ausente' ? 'selected' : ''; ?>>Ausente</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                                <label class="form-label">Médico</label>
                                <select name="medico" class="form-control">
                                    <option value="">Todos los médicos</option>
                                    <?php foreach ($medicos as $medico): ?>
                                    <option value="<?php echo $medico['id']; ?>" <?php echo $medico_filtro == $medico['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($medico['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                                <label class="form-label">Buscar Paciente</label>
                                <input type="text" name="busqueda" class="form-control" placeholder="Nombre o DNI..." value="<?php echo htmlspecialchars($busqueda); ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="margin-bottom: 0;">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            
                            <a href="citas.php" class="btn btn-secondary" style="margin-bottom: 0;">
                                <i class="fas fa-redo"></i> Limpiar
                            </a>
                        </form>
                    </div>
                </div>

                <!-- Resumen rápido -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #F59E0B; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="font-size: 24px; font-weight: bold; color: #F59E0B;">
                            <?php echo count(array_filter($citas, fn($c) => $c['estado'] === 'programada')); ?>
                        </div>
                        <div style="font-size: 12px; color: #6B7280;">Programadas</div>
                    </div>
                    
                    <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #10B981; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="font-size: 24px; font-weight: bold; color: #10B981;">
                            <?php echo count(array_filter($citas, fn($c) => $c['estado'] === 'confirmada')); ?>
                        </div>
                        <div style="font-size: 12px; color: #6B7280;">Confirmadas</div>
                    </div>
                    
                    <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #6366F1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="font-size: 24px; font-weight: bold; color: #6366F1;">
                            <?php echo count(array_filter($citas, fn($c) => $c['estado'] === 'atendida')); ?>
                        </div>
                        <div style="font-size: 12px; color: #6B7280;">Atendidas</div>
                    </div>
                    
                    <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #00D4D4; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="font-size: 24px; font-weight: bold; color: #00D4D4;">
                            <?php echo count($citas); ?>
                        </div>
                        <div style="font-size: 12px; color: #6B7280;">Total</div>
                    </div>
                </div>

                <!-- Tabla de Citas -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            Listado de Citas 
                            <span class="badge badge-primary"><?php echo count($citas); ?> citas</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($citas) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha y Hora</th>
                                        <th>Paciente</th>
                                        <th>DNI</th>
                                        <th>Médico</th>
                                        <th>Especialidad</th>
                                        <th>Consultorio</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($citas as $cita): ?>
                                    <tr>
                                        <td><strong>#<?php echo $cita['id']; ?></strong></td>
                                        <td>
                                            <div><?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></div>
                                            <small style="color: var(--text-secondary);"><?php echo date('H:i', strtotime($cita['hora'])); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($cita['paciente_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($cita['paciente_dni']); ?></td>
                                        <td><?php echo htmlspecialchars($cita['medico_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($cita['especialidad']); ?></td>
                                        <td><?php echo htmlspecialchars($cita['consultorio']); ?></td>
                                        <td>
                                            <span class="badge badge-primary">
                                                <?php echo ucfirst($cita['tipo_cita']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $badge_class = 'badge-secondary';
                                            switch($cita['estado']) {
                                                case 'programada': $badge_class = 'badge-warning'; break;
                                                case 'confirmada': $badge_class = 'badge-success'; break;
                                                case 'en_atencion': $badge_class = 'badge-primary'; break;
                                                case 'atendida': $badge_class = 'badge-success'; break;
                                                case 'cancelada': $badge_class = 'badge-danger'; break;
                                                case 'ausente': $badge_class = 'badge-danger'; break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $cita['estado'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <button onclick="verDetalle(<?php echo $cita['id']; ?>)" class="btn btn-sm btn-primary" title="Ver Detalle">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if (hasPermission('editar_cita') && !in_array($cita['estado'], ['atendida', 'cancelada'])): ?>
                                                <a href="editar-cita.php?id=<?php echo $cita['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <?php if (in_array($cita['estado'], ['programada', 'confirmada'])): ?>
                                                <button onclick="cambiarEstado(<?php echo $cita['id']; ?>, 'cancelada')" class="btn btn-sm btn-danger" title="Cancelar">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php endif; ?>
                                                
                                                <?php if (esMedico() && $cita['estado'] === 'confirmada'): ?>
                                                <button onclick="cambiarEstado(<?php echo $cita['id']; ?>, 'en_atencion')" class="btn btn-sm btn-success" title="Iniciar Atención">
                                                    <i class="fas fa-play"></i>
                                                </button>
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
                            <i class="fas fa-calendar-times" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                            <p>No se encontraron citas con los filtros aplicados</p>
                            <a href="citas.php" class="btn btn-secondary" style="margin-top: 15px;">
                                <i class="fas fa-redo"></i> Ver todas las citas
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalle -->
    <div id="modalDetalle" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 16px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <div style="padding: 25px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;">Detalle de Cita</h3>
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
        function verDetalle(citaId) {
            const modal = document.getElementById('modalDetalle');
            const contenido = document.getElementById('contenidoModal');
            
            modal.style.display = 'flex';
            contenido.innerHTML = 'Cargando...';
            
            fetch('detalle-cita.php?id=' + citaId)
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
        
        function cambiarEstado(citaId, nuevoEstado) {
            const mensajes = {
                'cancelada': '¿Está seguro de cancelar esta cita?',
                'en_atencion': '¿Iniciar atención de esta cita?',
                'confirmada': '¿Confirmar esta cita?'
            };
            
            if (confirm(mensajes[nuevoEstado] || '¿Está seguro?')) {
                fetch('cambiar-estado-cita.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        cita_id: citaId,
                        estado: nuevoEstado
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Estado actualizado correctamente');
                        location.reload();
                    } else {
                        alert(data.message || 'Error al actualizar');
                    }
                })
                .catch(error => {
                    alert('Error en la conexión');
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