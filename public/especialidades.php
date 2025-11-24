<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

// Verificar permisos
if (!hasPermission('gestionar_especialidades') && getUserRole() !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener especialidades
$query = "SELECT e.*, 
          (SELECT COUNT(*) FROM medicos WHERE especialidad_id = e.id AND estado = 'activo') as total_medicos
          FROM especialidades e
          ORDER BY e.nombre";
$stmt = $db->query($query);
$especialidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'crear') {
        $nombre = sanitizeInput($_POST['nombre'] ?? '');
        $descripcion = sanitizeInput($_POST['descripcion'] ?? '');
        
        if (empty($nombre)) {
            $error = 'El nombre de la especialidad es obligatorio';
        } else {
            try {
                // Verificar si ya existe
                $query_check = "SELECT COUNT(*) as total FROM especialidades WHERE nombre = :nombre";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([':nombre' => $nombre]);
                $existe = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if ($existe['total'] > 0) {
                    $error = 'Ya existe una especialidad con ese nombre';
                } else {
                    $query = "INSERT INTO especialidades (nombre, descripcion, estado) 
                             VALUES (:nombre, :descripcion, 'activo')";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':nombre' => $nombre,
                        ':descripcion' => $descripcion
                    ]);
                    
                    $success = 'Especialidad creada exitosamente';
                    
                    // Recargar especialidades
                    $stmt = $db->query("SELECT e.*, (SELECT COUNT(*) FROM medicos WHERE especialidad_id = e.id AND estado = 'activo') as total_medicos FROM especialidades e ORDER BY e.nombre");
                    $especialidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                $error = 'Error al crear la especialidad';
            }
        }
    } elseif ($accion === 'editar') {
        $id = $_POST['id'] ?? '';
        $nombre = sanitizeInput($_POST['nombre'] ?? '');
        $descripcion = sanitizeInput($_POST['descripcion'] ?? '');
        
        if (empty($nombre)) {
            $error = 'El nombre de la especialidad es obligatorio';
        } else {
            try {
                // Verificar si ya existe (excluyendo el actual)
                $query_check = "SELECT COUNT(*) as total FROM especialidades WHERE nombre = :nombre AND id != :id";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([':nombre' => $nombre, ':id' => $id]);
                $existe = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if ($existe['total'] > 0) {
                    $error = 'Ya existe otra especialidad con ese nombre';
                } else {
                    $query = "UPDATE especialidades SET nombre = :nombre, descripcion = :descripcion WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':nombre' => $nombre,
                        ':descripcion' => $descripcion,
                        ':id' => $id
                    ]);
                    
                    $success = 'Especialidad actualizada exitosamente';
                    
                    // Recargar especialidades
                    $stmt = $db->query("SELECT e.*, (SELECT COUNT(*) FROM medicos WHERE especialidad_id = e.id AND estado = 'activo') as total_medicos FROM especialidades e ORDER BY e.nombre");
                    $especialidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                $error = 'Error al actualizar la especialidad';
            }
        }
    } elseif ($accion === 'cambiar_estado') {
        $id = $_POST['id'] ?? '';
        $estado = $_POST['estado'] ?? '';
        
        try {
            $query = "UPDATE especialidades SET estado = :estado WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':estado' => $estado, ':id' => $id]);
            
            $success = 'Estado actualizado correctamente';
            
            // Recargar especialidades
            $stmt = $db->query("SELECT e.*, (SELECT COUNT(*) FROM medicos WHERE especialidad_id = e.id AND estado = 'activo') as total_medicos FROM especialidades e ORDER BY e.nombre");
            $especialidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = 'Error al cambiar el estado';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Especialidades - Sistema Clínico</title>
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
                        <h1 class="page-title">Gestión de Especialidades</h1>
                        <p class="page-subtitle">Administra las especialidades médicas</p>
                    </div>
                    <a href="seguros.php" class="btn btn-success">
                        <i class="fas fa-shield-alt"></i> Gestionar Seguros
                    </a>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert" style="background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 20px;">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-stethoscope"></i>
                        </div>
                        <div class="stat-value"><?php echo count($especialidades); ?></div>
                        <div class="stat-label">Total Especialidades</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value">
                            <?php echo count(array_filter($especialidades, function($e) { return $e['estado'] === 'activo'; })); ?>
                        </div>
                        <div class="stat-label">Activas</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon info">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="stat-value">
                            <?php echo array_sum(array_column($especialidades, 'total_medicos')); ?>
                        </div>
                        <div class="stat-label">Médicos Totales</div>
                    </div>
                </div>

                <!-- Crear nueva especialidad -->
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">Nueva Especialidad</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 15px; align-items: end;">
                            <input type="hidden" name="accion" value="crear">
                            
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nombre" class="form-control" 
                                       placeholder="Ej: Cardiología" required>
                            </div>
                            
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Descripción</label>
                                <input type="text" name="descripcion" class="form-control" 
                                       placeholder="Descripción breve (opcional)">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Crear
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Lista de especialidades -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            Listado de Especialidades
                            <span class="badge badge-primary"><?php echo count($especialidades); ?></span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($especialidades) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Médicos Activos</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($especialidades as $especialidad): ?>
                                    <tr>
                                        <td><strong>#<?php echo $especialidad['id']; ?></strong></td>
                                        <td>
                                            <strong style="color: var(--primary-color);">
                                                <i class="fas fa-stethoscope"></i>
                                                <?php echo htmlspecialchars($especialidad['nombre']); ?>
                                            </strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($especialidad['descripcion'] ?: '-'); ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo $especialidad['total_medicos']; ?> médicos
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($especialidad['estado'] === 'activo'): ?>
                                                <span class="badge badge-success">Activa</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactiva</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <button onclick="editarEspecialidad(<?php echo htmlspecialchars(json_encode($especialidad)); ?>)" 
                                                        class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <?php if ($especialidad['estado'] === 'activo'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="accion" value="cambiar_estado">
                                                    <input type="hidden" name="id" value="<?php echo $especialidad['id']; ?>">
                                                    <input type="hidden" name="estado" value="inactivo">
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            onclick="return confirm('¿Desactivar esta especialidad?')"
                                                            title="Desactivar">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="accion" value="cambiar_estado">
                                                    <input type="hidden" name="id" value="<?php echo $especialidad['id']; ?>">
                                                    <input type="hidden" name="estado" value="activo">
                                                    <button type="submit" class="btn btn-sm btn-success" 
                                                            title="Activar">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
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
                            <i class="fas fa-stethoscope" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                            <p>No hay especialidades registradas</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edición -->
    <div id="modalEditar" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 16px; max-width: 500px; width: 90%; padding: 0;">
            <div style="padding: 25px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;">Editar Especialidad</h3>
                <button onclick="cerrarModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-secondary);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" style="padding: 25px;">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="3"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="cerrarModal()" class="btn btn-secondary">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        function editarEspecialidad(especialidad) {
            document.getElementById('edit_id').value = especialidad.id;
            document.getElementById('edit_nombre').value = especialidad.nombre;
            document.getElementById('edit_descripcion').value = especialidad.descripcion || '';
            document.getElementById('modalEditar').style.display = 'flex';
        }
        
        function cerrarModal() {
            document.getElementById('modalEditar').style.display = 'none';
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modalEditar').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });
    </script>
</body>
</html>