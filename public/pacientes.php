<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

// Verificar permisos
if (!hasPermission('ver_pacientes')) {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Filtros
$busqueda = $_GET['busqueda'] ?? '';
$seguro_filtro = $_GET['seguro'] ?? '';
$sexo_filtro = $_GET['sexo'] ?? '';

// Construir query con filtros
$query = "SELECT p.*, s.nombre as seguro_nombre
          FROM pacientes p
          LEFT JOIN seguros s ON p.seguro_id = s.id
          WHERE p.estado = 'activo'";

$params = [];

if ($busqueda) {
    $query .= " AND (p.nombre LIKE :busqueda OR p.apellido LIKE :busqueda OR p.dni LIKE :busqueda OR p.email LIKE :busqueda)";
    $params[':busqueda'] = "%$busqueda%";
}

if ($seguro_filtro) {
    $query .= " AND p.seguro_id = :seguro";
    $params[':seguro'] = $seguro_filtro;
}

if ($sexo_filtro) {
    $query .= " AND p.sexo = :sexo";
    $params[':sexo'] = $sexo_filtro;
}

$query .= " ORDER BY p.nombre, p.apellido";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de seguros para filtro
$query_seguros = "SELECT id, nombre FROM seguros WHERE estado = 'activo' ORDER BY nombre";
$stmt_seguros = $db->query($query_seguros);
$seguros = $stmt_seguros->fetchAll(PDO::FETCH_ASSOC);

// Calcular edad
if (!function_exists('calcularEdad')) {
    function calcularEdad($fecha_nacimiento) {
        $nacimiento = new DateTime($fecha_nacimiento);
        $hoy = new DateTime();
        $edad = $hoy->diff($nacimiento);
        return $edad->y;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pacientes - Sistema Clínico</title>
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
                        <h1 class="page-title">Gestión de Pacientes</h1>
                        <p class="page-subtitle">Administra todos los pacientes registrados</p>
                    </div>
                    <?php if (hasPermission('gestionar_pacientes')): ?>
                    <a href="nuevo-paciente.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Paciente
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Filtros -->
                <div class="card mb-2">
                    <div class="card-body">
                        <form method="GET" action="pacientes.php" class="d-flex gap-2" style="align-items: flex-end; flex-wrap: wrap;">
                            <div class="form-group" style="margin-bottom: 0; flex: 2; min-width: 250px;">
                                <label class="form-label">Buscar</label>
                                <input type="text" name="busqueda" class="form-control" 
                                       placeholder="Nombre, DNI, Email..." 
                                       value="<?php echo htmlspecialchars($busqueda); ?>">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                                <label class="form-label">Seguro</label>
                                <select name="seguro" class="form-control">
                                    <option value="">Todos los seguros</option>
                                    <?php foreach ($seguros as $seguro): ?>
                                    <option value="<?php echo $seguro['id']; ?>" <?php echo $seguro_filtro == $seguro['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($seguro['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
                                <label class="form-label">Sexo</label>
                                <select name="sexo" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="M" <?php echo $sexo_filtro === 'M' ? 'selected' : ''; ?>>Masculino</option>
                                    <option value="F" <?php echo $sexo_filtro === 'F' ? 'selected' : ''; ?>>Femenino</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="margin-bottom: 0;">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            
                            <a href="pacientes.php" class="btn btn-secondary" style="margin-bottom: 0;">
                                <i class="fas fa-redo"></i> Limpiar
                            </a>
                        </form>
                    </div>
                </div>

                <!-- Estadísticas rápidas -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 20px;">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo count($pacientes); ?></div>
                        <div class="stat-label">Pacientes Registrados</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-male"></i>
                        </div>
                        <div class="stat-value">
                            <?php echo count(array_filter($pacientes, function($p) { return $p['sexo'] === 'M'; })); ?>
                        </div>
                        <div class="stat-label">Masculino</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-female"></i>
                        </div>
                        <div class="stat-value">
                            <?php echo count(array_filter($pacientes, function($p) { return $p['sexo'] === 'F'; })); ?>
                        </div>
                        <div class="stat-label">Femenino</div>
                    </div>
                </div>

                <!-- Tabla de Pacientes -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            Listado de Pacientes
                            <span class="badge badge-primary"><?php echo count($pacientes); ?> pacientes</span>
                        </h3>
                        <div class="card-actions">
                            <button onclick="exportarExcel()" class="btn btn-sm btn-success">
                                <i class="fas fa-file-excel"></i> Exportar
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($pacientes) > 0): ?>
                        <div class="table-container">
                            <table id="tablaPacientes">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>DNI</th>
                                        <th>Nombre Completo</th>
                                        <th>Edad</th>
                                        <th>Sexo</th>
                                        <th>Teléfono</th>
                                        <th>Email</th>
                                        <th>Seguro</th>
                                        <th>Fecha Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pacientes as $paciente): ?>
                                    <tr>
                                        <td><strong>#<?php echo $paciente['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($paciente['dni']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellido']); ?></strong>
                                        </td>
                                        <td><?php echo calcularEdad($paciente['fecha_nacimiento']); ?> años</td>
                                        <td>
                                            <?php if ($paciente['sexo'] === 'M'): ?>
                                                <i class="fas fa-male" style="color: #00D4D4;"></i> M
                                            <?php else: ?>
                                                <i class="fas fa-female" style="color: #F59E0B;"></i> F
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($paciente['telefono'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($paciente['email'] ?: '-'); ?></td>
                                        <td>
                                            <?php if ($paciente['seguro_nombre']): ?>
                                                <span class="badge badge-success">
                                                    <?php echo htmlspecialchars($paciente['seguro_nombre']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Sin seguro</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($paciente['fecha_registro'])); ?></td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <button onclick="verPaciente(<?php echo $paciente['id']; ?>)" 
                                                        class="btn btn-sm btn-primary" title="Ver Detalle">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if (hasPermission('gestionar_pacientes')): ?>
                                                <a href="editar-paciente.php?id=<?php echo $paciente['id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <button onclick="eliminarPaciente(<?php echo $paciente['id']; ?>)" 
                                                        class="btn btn-sm btn-danger" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <button onclick="verHistorial(<?php echo $paciente['id']; ?>)" 
                                                        class="btn btn-sm btn-success" title="Historia Clínica">
                                                    <i class="fas fa-file-medical"></i>
                                                </button>
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
                            <p>No se encontraron pacientes con los filtros aplicados</p>
                            <?php if (hasPermission('gestionar_pacientes')): ?>
                            <a href="nuevo-paciente.php" class="btn btn-primary" style="margin-top: 15px;">
                                <i class="fas fa-plus"></i> Registrar Primer Paciente
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
                <h3 style="margin: 0;">Detalle del Paciente</h3>
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
        function verPaciente(pacienteId) {
            const modal = document.getElementById('modalDetalle');
            const contenido = document.getElementById('contenidoModal');
            
            modal.style.display = 'flex';
            contenido.innerHTML = 'Cargando...';
            
            fetch('detalle-paciente.php?id=' + pacienteId)
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
        
        function eliminarPaciente(pacienteId) {
            if (confirm('¿Está seguro de eliminar este paciente? Esta acción no se puede deshacer.')) {
                fetch('eliminar-paciente.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        paciente_id: pacienteId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarAlerta('Paciente eliminado correctamente', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        mostrarAlerta(data.message || 'Error al eliminar', 'error');
                    }
                })
                .catch(error => {
                    mostrarAlerta('Error en la conexión', 'error');
                });
            }
        }
        
        function verHistorial(pacienteId) {
            window.location.href = 'historia-clinica.php?paciente_id=' + pacienteId;
        }
        
        function exportarExcel() {
            // Implementar exportación a Excel
            mostrarAlerta('Función de exportación en desarrollo', 'info');
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