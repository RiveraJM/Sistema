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

$usuario_id = $_GET['id'] ?? null;

if (!$usuario_id) {
    header("Location: usuarios.php");
    exit();
}

// Obtener datos del usuario
$query = "SELECT id, username, CONCAT(nombre, ' ', apellido) as nombre_completo, rol 
          FROM usuarios WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $usuario_id);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: usuarios.php");
    exit();
}

// Obtener todos los permisos disponibles
$query_permisos = "SELECT * FROM permisos ORDER BY nombre";
$stmt_permisos = $db->query($query_permisos);
$todos_permisos = $stmt_permisos->fetchAll(PDO::FETCH_ASSOC);

// Obtener permisos actuales del usuario
$query_usuario_permisos = "SELECT permiso_id FROM usuario_permisos WHERE usuario_id = :id";
$stmt_usuario_permisos = $db->prepare($query_usuario_permisos);
$stmt_usuario_permisos->bindParam(':id', $usuario_id);
$stmt_usuario_permisos->execute();
$permisos_usuario = $stmt_usuario_permisos->fetchAll(PDO::FETCH_COLUMN);

// Procesar formulario
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $permisos_seleccionados = $_POST['permisos'] ?? [];
    
    try {
        $db->beginTransaction();
        
        // Eliminar todos los permisos actuales
        $query_delete = "DELETE FROM usuario_permisos WHERE usuario_id = :usuario_id";
        $stmt_delete = $db->prepare($query_delete);
        $stmt_delete->execute([':usuario_id' => $usuario_id]);
        
        // Insertar los nuevos permisos
        if (count($permisos_seleccionados) > 0) {
            $query_insert = "INSERT INTO usuario_permisos (usuario_id, permiso_id) VALUES (:usuario_id, :permiso_id)";
            $stmt_insert = $db->prepare($query_insert);
            
            foreach ($permisos_seleccionados as $permiso_id) {
                $stmt_insert->execute([
                    ':usuario_id' => $usuario_id,
                    ':permiso_id' => $permiso_id
                ]);
            }
        }
        
        $db->commit();
        
        $success = 'Permisos actualizados correctamente. Total: ' . count($permisos_seleccionados) . ' permisos asignados.';
        
        // Actualizar array de permisos del usuario
        $permisos_usuario = $permisos_seleccionados;
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Error al actualizar los permisos: ' . $e->getMessage();
    }
}

// Agrupar permisos por categoría
$permisos_agrupados = [
    'Dashboard' => [],
    'Citas' => [],
    'Pacientes' => [],
    'Médicos' => [],
    'Historia Clínica' => [],
    'Usuarios' => [],
    'Reportes' => []
];

foreach ($todos_permisos as $permiso) {
    $nombre = $permiso['nombre'];
    
    if (strpos($nombre, 'dashboard') !== false) {
        $permisos_agrupados['Dashboard'][] = $permiso;
    } elseif (strpos($nombre, 'cita') !== false) {
        $permisos_agrupados['Citas'][] = $permiso;
    } elseif (strpos($nombre, 'paciente') !== false) {
        $permisos_agrupados['Pacientes'][] = $permiso;
    } elseif (strpos($nombre, 'medico') !== false) {
        $permisos_agrupados['Médicos'][] = $permiso;
    } elseif (strpos($nombre, 'historia') !== false) {
        $permisos_agrupados['Historia Clínica'][] = $permiso;
    } elseif (strpos($nombre, 'usuario') !== false || strpos($nombre, 'permiso') !== false) {
        $permisos_agrupados['Usuarios'][] = $permiso;
    } elseif (strpos($nombre, 'reporte') !== false) {
        $permisos_agrupados['Reportes'][] = $permiso;
    }
}

// Eliminar categorías vacías
$permisos_agrupados = array_filter($permisos_agrupados, function($permisos) {
    return count($permisos) > 0;
});
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permisos de Usuario - Sistema Clínico</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .permiso-categoria {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
        }
        .permiso-categoria h3 {
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .permiso-item {
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .permiso-item:hover {
            border-color: var(--primary-color);
            background: rgba(0, 212, 212, 0.03);
        }
        .permiso-item.selected {
            border-color: var(--primary-color);
            background: rgba(0, 212, 212, 0.05);
        }
        .permiso-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .permiso-info {
            flex: 1;
        }
        .permiso-nombre {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 3px;
        }
        .permiso-descripcion {
            font-size: 13px;
            color: var(--text-secondary);
        }
        .btn-categoria {
            font-size: 12px;
            padding: 4px 12px;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="dashboard-content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Gestionar Permisos</h1>
                        <p class="page-subtitle">
                            Usuario: <strong><?php echo htmlspecialchars($usuario['nombre_completo']); ?></strong>
                            (@<?php echo htmlspecialchars($usuario['username']); ?>)
                            - Rol: 
                            <span class="badge badge-primary"><?php echo ucfirst($usuario['rol']); ?></span>
                        </p>
                    </div>
                    <a href="usuarios.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
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

                <!-- Acciones rápidas -->
                <div class="card mb-2">
                    <div class="card-body">
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button type="button" onclick="seleccionarTodos()" class="btn btn-sm btn-success">
                                <i class="fas fa-check-double"></i> Seleccionar Todos
                            </button>
                            <button type="button" onclick="deseleccionarTodos()" class="btn btn-sm btn-secondary">
                                <i class="fas fa-times"></i> Deseleccionar Todos
                            </button>
                            <button type="button" onclick="aplicarPlantilla('admin')" class="btn btn-sm btn-danger">
                                <i class="fas fa-user-shield"></i> Plantilla Admin
                            </button>
                            <button type="button" onclick="aplicarPlantilla('medico')" class="btn btn-sm btn-primary">
                                <i class="fas fa-user-md"></i> Plantilla Médico
                            </button>
                            <button type="button" onclick="aplicarPlantilla('recepcionista')" class="btn btn-sm btn-warning">
                                <i class="fas fa-user-tie"></i> Plantilla Recepcionista
                            </button>
                        </div>
                    </div>
                </div>

                <form method="POST" action="permisos-usuario.php?id=<?php echo $usuario_id; ?>" id="formPermisos">
                    <!-- Permisos por categoría -->
                    <?php foreach ($permisos_agrupados as $categoria => $permisos): ?>
                    <div class="permiso-categoria">
                        <h3>
                            <span>
                                <?php
                                $icons = [
                                    'Dashboard' => 'fa-tachometer-alt',
                                    'Citas' => 'fa-calendar-alt',
                                    'Pacientes' => 'fa-users',
                                    'Médicos' => 'fa-user-md',
                                    'Historia Clínica' => 'fa-file-medical',
                                    'Usuarios' => 'fa-user-shield',
                                    'Reportes' => 'fa-chart-bar'
                                ];
                                $icon = $icons[$categoria] ?? 'fa-cog';
                                ?>
                                <i class="fas <?php echo $icon; ?>"></i>
                                <?php echo $categoria; ?>
                            </span>
                            <div>
                                <button type="button" onclick="seleccionarCategoria('<?php echo $categoria; ?>')" 
                                        class="btn btn-sm btn-success btn-categoria">
                                    Todos
                                </button>
                                <button type="button" onclick="deseleccionarCategoria('<?php echo $categoria; ?>')" 
                                        class="btn btn-sm btn-secondary btn-categoria">
                                    Ninguno
                                </button>
                            </div>
                        </h3>
                        
                        <?php foreach ($permisos as $permiso): ?>
                        <?php $checked = in_array($permiso['id'], $permisos_usuario); ?>
                        <label class="permiso-item <?php echo $checked ? 'selected' : ''; ?>" 
                               data-categoria="<?php echo $categoria; ?>"
                               onclick="togglePermiso(this, event)">
                            <input type="checkbox" 
                                   name="permisos[]" 
                                   value="<?php echo $permiso['id']; ?>"
                                   <?php echo $checked ? 'checked' : ''; ?>
                                   onchange="updateItemStyle(this)">
                            <div class="permiso-info">
                                <div class="permiso-nombre">
                                    <?php echo htmlspecialchars($permiso['nombre']); ?>
                                </div>
                                <div class="permiso-descripcion">
                                    <?php echo htmlspecialchars($permiso['descripcion']); ?>
                                </div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>

                    <!-- Resumen -->
                    <div class="card">
                        <div class="card-body">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong>Permisos seleccionados:</strong>
                                    <span id="contador-permisos" class="badge badge-primary" style="font-size: 16px; margin-left: 10px;">
                                        <?php echo count($permisos_usuario); ?>
                                    </span>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <a href="usuarios.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar Permisos
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Plantillas de permisos por rol
        const plantillas = {
            admin: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
            medico: [1, 3, 5, 10, 11],
            recepcionista: [1, 2, 3, 4, 5, 6]
        };
        
        function togglePermiso(label, event) {
            if (event.target.type !== 'checkbox') {
                const checkbox = label.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
                updateItemStyle(checkbox);
                actualizarContador();
            }
        }
        
        function updateItemStyle(checkbox) {
            const label = checkbox.closest('.permiso-item');
            if (checkbox.checked) {
                label.classList.add('selected');
            } else {
                label.classList.remove('selected');
            }
            actualizarContador();
        }
        
        function actualizarContador() {
            const checkboxes = document.querySelectorAll('input[name="permisos[]"]:checked');
            document.getElementById('contador-permisos').textContent = checkboxes.length;
        }
        
        function seleccionarTodos() {
            document.querySelectorAll('input[name="permisos[]"]').forEach(checkbox => {
                checkbox.checked = true;
                updateItemStyle(checkbox);
            });
        }
        
        function deseleccionarTodos() {
            document.querySelectorAll('input[name="permisos[]"]').forEach(checkbox => {
                checkbox.checked = false;
                updateItemStyle(checkbox);
            });
        }
        
        function seleccionarCategoria(categoria) {
            document.querySelectorAll(`.permiso-item[data-categoria="${categoria}"] input`).forEach(checkbox => {
                checkbox.checked = true;
                updateItemStyle(checkbox);
            });
        }
        
        function deseleccionarCategoria(categoria) {
            document.querySelectorAll(`.permiso-item[data-categoria="${categoria}"] input`).forEach(checkbox => {
                checkbox.checked = false;
                updateItemStyle(checkbox);
            });
        }
        
        function aplicarPlantilla(rol) {
            const permisos = plantillas[rol] || [];
            
            // Deseleccionar todos primero
            document.querySelectorAll('input[name="permisos[]"]').forEach(checkbox => {
                checkbox.checked = false;
                updateItemStyle(checkbox);
            });
            
            // Seleccionar según plantilla
            permisos.forEach(permisoId => {
                const checkbox = document.querySelector(`input[name="permisos[]"][value="${permisoId}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    updateItemStyle(checkbox);
                }
            });
            
            mostrarAlerta(`Plantilla de ${rol} aplicada`, 'success');
        }
        
        // Inicializar contador
        actualizarContador();
    </script>
</body>
</html>