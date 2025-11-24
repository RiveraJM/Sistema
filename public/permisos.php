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

// Obtener todos los permisos
$query = "SELECT * FROM permisos ORDER BY nombre";
$stmt = $db->query($query);
$permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas de uso de cada permiso
$query_uso = "SELECT permiso_id, COUNT(*) as total_usuarios 
              FROM usuario_permisos 
              GROUP BY permiso_id";
$stmt_uso = $db->query($query_uso);
$uso_permisos = [];
while ($row = $stmt_uso->fetch(PDO::FETCH_ASSOC)) {
    $uso_permisos[$row['permiso_id']] = $row['total_usuarios'];
}

// Procesar formulario (crear nuevo permiso)
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'crear') {
        $nombre = sanitizeInput($_POST['nombre'] ?? '');
        $descripcion = sanitizeInput($_POST['descripcion'] ?? '');
        
        if (empty($nombre) || empty($descripcion)) {
            $error = 'Complete todos los campos';
        } else {
            try {
                $query = "INSERT INTO permisos (nombre, descripcion) VALUES (:nombre, :descripcion)";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':descripcion' => $descripcion
                ]);
                
                $success = 'Permiso creado exitosamente';
                
                // Recargar permisos
                $stmt = $db->query("SELECT * FROM permisos ORDER BY nombre");
                $permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $error = 'Error al crear el permiso';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Permisos - Sistema Clínico</title>
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
                        <h1 class="page-title">Gestión de Permisos</h1>
                        <p class="page-subtitle">Administra todos los permisos del sistema</p>
                    </div>
                    <a href="usuarios.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a Usuarios
                    </a>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert" style="background: rgba(16, 185, 129, 0.1); color: #059669;">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
                <?php endif; ?>

                <!-- Crear nuevo permiso -->
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">Crear Nuevo Permiso</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 15px; align-items: end;">
                            <input type="hidden" name="accion" value="crear">
                            
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Nombre del Permiso</label>
                                <input type="text" name="nombre" class="form-control" 
                                       placeholder="Ej: ver_reportes" required>
                            </div>
                            
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Descripción</label>
                                <input type="text" name="descripcion" class="form-control" 
                                       placeholder="Ej: Permite ver reportes estadísticos" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Crear
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Lista de permisos -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            Todos los Permisos
                            <span class="badge badge-primary"><?php echo count($permisos); ?> permisos</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Usuarios con este permiso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($permisos as $permiso): ?>
                                    <tr>
                                        <td><strong>#<?php echo $permiso['id']; ?></strong></td>
                                        <td>
                                            <span class="badge badge-primary">
                                                <?php echo htmlspecialchars($permiso['nombre']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($permiso['descripcion']); ?></td>
                                        <td>
                                            <span class="badge badge-success">
                                                <?php echo $uso_permisos[$permiso['id']] ?? 0; ?> usuarios
                                            </span>
                                        </td>                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>