<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar permisos
if (!hasPermission('gestionar_seguros') && !esAdmin()) {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener seguros
$query = "SELECT s.*, 
          (SELECT COUNT(*) FROM pacientes WHERE seguro_id = s.id AND estado = 'activo') as total_pacientes
          FROM seguros s
          ORDER BY s.nombre";
$stmt = $db->query($query);
$seguros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'crear') {
        $nombre = sanitizeInput($_POST['nombre'] ?? '');
        $tipo = $_POST['tipo'] ?? '';
        $telefono = sanitizeInput($_POST['telefono'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        
        if (empty($nombre) || empty($tipo)) {
            $error = 'El nombre y tipo del seguro son obligatorios';
        } else {
            try {
                // Verificar si ya existe
                $query_check = "SELECT COUNT(*) as total FROM seguros WHERE nombre = :nombre";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([':nombre' => $nombre]);
                $existe = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if ($existe['total'] > 0) {
                    $error = 'Ya existe un seguro con ese nombre';
                } else {
                    $query = "INSERT INTO seguros (nombre, tipo, telefono, email, estado) 
                             VALUES (:nombre, :tipo, :telefono, :email, 'activo')";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':nombre' => $nombre,
                        ':tipo' => $tipo,
                        ':telefono' => $telefono,
                        ':email' => $email
                    ]);
                    
                    $success = 'Seguro creado exitosamente';
                    
                    // Recargar seguros
                    $stmt = $db->query("SELECT s.*, (SELECT COUNT(*) FROM pacientes WHERE seguro_id = s.id AND estado = 'activo') as total_pacientes FROM seguros s ORDER BY s.nombre");
                    $seguros = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                $error = 'Error al crear el seguro: ' . $e->getMessage();
            }
        }
    } elseif ($accion === 'editar') {
        $id = $_POST['id'] ?? '';
        $nombre = sanitizeInput($_POST['nombre'] ?? '');
        $tipo = $_POST['tipo'] ?? '';
        $telefono = sanitizeInput($_POST['telefono'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        
        if (empty($nombre) || empty($tipo)) {
            $error = 'El nombre y tipo del seguro son obligatorios';
        } else {
            try {
                // Verificar si ya existe (excluyendo el actual)
                $query_check = "SELECT COUNT(*) as total FROM seguros WHERE nombre = :nombre AND id != :id";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([':nombre' => $nombre, ':id' => $id]);
                $existe = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if ($existe['total'] > 0) {
                    $error = 'Ya existe otro seguro con ese nombre';
                } else {
                    $query = "UPDATE seguros SET 
                             nombre = :nombre, 
                             tipo = :tipo, 
                             telefono = :telefono, 
                             email = :email
                             WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':nombre' => $nombre,
                        ':tipo' => $tipo,
                        ':telefono' => $telefono,
                        ':email' => $email,
                        ':id' => $id
                    ]);
                    
                    $success = 'Seguro actualizado exitosamente';
                    
                    // Recargar seguros
                    $stmt = $db->query("SELECT s.*, (SELECT COUNT(*) FROM pacientes WHERE seguro_id = s.id AND estado = 'activo') as total_pacientes FROM seguros s ORDER BY s.nombre");
                    $seguros = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                $error = 'Error al actualizar el seguro: ' . $e->getMessage();
            }
        }
    } elseif ($accion === 'cambiar_estado') {
        $id = $_POST['id'] ?? '';
        $estado = $_POST['estado'] ?? '';
        
        try {
            $query = "UPDATE seguros SET estado = :estado WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':estado' => $estado, ':id' => $id]);
            
            $success = 'Estado actualizado correctamente';
            
            // Recargar seguros
            $stmt = $db->query("SELECT s.*, (SELECT COUNT(*) FROM pacientes WHERE seguro_id = s.id AND estado = 'activo') as total_pacientes FROM seguros s ORDER BY s.nombre");
            $seguros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = 'Error al cambiar el estado: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguros Médicos - Sistema Clínico</title>
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
                        <h1 class="page-title">Gestión de Seguros Médicos</h1>
                        <p class="page-subtitle">Administra las compañías de seguros</p>
                    </div>
                    <a href="especialidades.php" class="btn btn-primary">
                        <i class="fas fa-stethoscope"></i> Gestionar Especialidades
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
                        <div class="stat-icon success">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="stat-value"><?php echo count($seguros); ?></div>
                        <div class="stat-label">Total Seguros</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value">
                            <?php echo count(array_filter($seguros, function($s) { return isset($s['estado']) && $s['estado'] === 'activo'; })); ?>
                        </div>
                        <div class="stat-label">Activos</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon info">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value">
                            <?php echo array_sum(array_column($seguros, 'total_pacientes')); ?>
                        </div>
                        <div class="stat-label">Pacientes Asegurados</div>
                    </div>
                </div>

                <!-- Crear nuevo seguro -->
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">Nuevo Seguro Médico</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="accion" value="crear">
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 15px;">
                                <div class="form-group" style="margin: 0;">
                                    <label class="form-label">Nombre *</label>
                                    <input type="text" name="nombre" class="form-control" 
                                           placeholder="Ej: EsSalud" required>
                                </div>
                                
                                <div class="form-group" style="margin: 0;">
                                    <label class="form-label">Tipo *</label>
                                    <select name="tipo" class="form-control" required>
                                        <option value="">Seleccione</option>
                                        <option value="publico">Público</option>
                                        <option value="privado">Privado</option>
                                        <option value="eps">EPS</option>
                                    </select>
                                </div>
                                
                                <div class="form-group" style="margin: 0;">
                                    <label class="form-label">Teléfono</label>
                                    <input type="tel" name="telefono" class="form-control" 
                                           placeholder="01-1234567">
                                </div>
                                
                                <div class="form-group" style="margin: 0;">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" 
                                           placeholder="contacto@seguro.com">
                                </div>
                            </div>
                            
                            <div style="text-align: right;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Crear Seguro
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de seguros -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            Listado de Seguros
                            <span class="badge badge-primary"><?php echo count($seguros); ?></span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($seguros) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Contacto</th>
                                        <th>Pacientes</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($seguros as $seguro): ?>
                                    <tr>
                                        <td><strong>#<?php echo $seguro['id']; ?></strong></td>
                                        <td>
                                            <strong style="color: var(--primary-color);">
                                                <i class="fas fa-shield-alt"></i>
                                                <?php echo htmlspecialchars($seguro['nombre']); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php
                                            $tipo_badges = [
                                                'publico' => '<span class="badge badge-info">Público</span>',
                                                'privado' => '<span class="badge badge-primary">Privado</span>',
                                                'eps' => '<span class="badge badge-success">EPS</span>'
                                            ];
                                            echo isset($seguro['tipo']) && isset($tipo_badges[$seguro['tipo']]) ? $tipo_badges[$seguro['tipo']] : '<span class="badge badge-secondary">-</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (isset($seguro['telefono']) && $seguro['telefono']): ?>
                                                <div style="font-size: 13px;">
                                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($seguro['telefono']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (isset($seguro['email']) && $seguro['email']): ?>
                                                <div style="font-size: 13px; margin-top: 3px;">
                                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($seguro['email']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ((!isset($seguro['telefono']) || !$seguro['telefono']) && (!isset($seguro['email']) || !$seguro['email'])): ?>
                                                <span style="color: var(--text-secondary);">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo isset($seguro['total_pacientes']) ? $seguro['total_pacientes'] : 0; ?> pacientes
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (isset($seguro['estado']) && $seguro['estado'] === 'activo'): ?>
                                                <span class="badge badge-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <button onclick='editarSeguro(<?php echo json_encode($seguro, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' 
                                                        class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <?php if (isset($seguro['estado']) && $seguro['estado'] === 'activo'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="accion" value="cambiar_estado">
                                                    <input type="hidden" name="id" value="<?php echo $seguro['id']; ?>">
                                                    <input type="hidden" name="estado" value="inactivo">
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            onclick="return confirm('¿Desactivar este seguro?')"
                                                            title="Desactivar">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="accion" value="cambiar_estado">
                                                    <input type="hidden" name="id" value="<?php echo $seguro['id']; ?>">
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
                            <i class="fas fa-shield-alt" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                            <p>No hay seguros registrados</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edición -->
    <div id="modalEditar" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 16px; max-width: 600px; width: 90%; padding: 0;">
            <div style="padding: 25px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;">Editar Seguro</h3>
                <button onclick="cerrarModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-secondary);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" style="padding: 25px;">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="edit_id">
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 15px;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo" id="edit_tipo" class="form-control" required>
                            <option value="publico">Público</option>
                            <option value="privado">Privado</option>
                            <option value="eps">EPS</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" id="edit_telefono" class="form-control">
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
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
        function editarSeguro(seguro) {
            document.getElementById('edit_id').value = seguro.id;
            document.getElementById('edit_nombre').value = seguro.nombre;
            document.getElementById('edit_tipo').value = seguro.tipo || 'publico';
            document.getElementById('edit_telefono').value = seguro.telefono || '';
            document.getElementById('edit_email').value = seguro.email || '';
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