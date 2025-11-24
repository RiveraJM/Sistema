<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

if (getUserRole() !== 'admin') {
    echo '<p>No tiene permisos</p>';
    exit();
}

$usuario_id = $_GET['id'] ?? null;

if (!$usuario_id) {
    echo '<p>Usuario no encontrado</p>';
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener datos del usuario
    $query = "SELECT * FROM usuarios WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $usuario_id);
    $stmt->execute();
    
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        echo '<p>Usuario no encontrado</p>';
        exit();
    }
    
    // Obtener permisos del usuario
    $query_permisos = "SELECT p.nombre, p.descripcion
                       FROM usuario_permisos up
                       INNER JOIN permisos p ON up.permiso_id = p.id
                       WHERE up.usuario_id = :id
                       ORDER BY p.nombre";
    $stmt_permisos = $db->prepare($query_permisos);
    $stmt_permisos->bindParam(':id', $usuario_id);
    $stmt_permisos->execute();
    $permisos = $stmt_permisos->fetchAll(PDO::FETCH_ASSOC);
    
    // Si es médico, obtener datos adicionales
    $datos_medico = null;
    if ($usuario['rol'] === 'medico') {
        $query_medico = "SELECT m.*, e.nombre as especialidad
                        FROM medicos m
                        INNER JOIN especialidades e ON m.especialidad_id = e.id
                        WHERE m.usuario_id = :id";
        $stmt_medico = $db->prepare($query_medico);
        $stmt_medico->bindParam(':id', $usuario_id);
        $stmt_medico->execute();
        $datos_medico = $stmt_medico->fetch(PDO::FETCH_ASSOC);
    }
    
    $rol_icons = [
        'admin' => 'fa-user-shield',
        'medico' => 'fa-user-md',
        'recepcionista' => 'fa-user-tie'
    ];
    
    $rol_colors = [
        'admin' => '#EF4444',
        'medico' => '#00D4D4',
        'recepcionista' => '#F59E0B'
    ];
    
    $icon = $rol_icons[$usuario['rol']] ?? 'fa-user';
    $color = $rol_colors[$usuario['rol']] ?? '#6B7280';
    
    ?>
    <div style="display: grid; gap: 15px;">
        <!-- Información Principal -->
        <div style="padding: 20px; background: linear-gradient(135deg, <?php echo $color; ?>, <?php echo $color; ?>DD); color: white; border-radius: 12px; text-align: center;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 36px;">
                <i class="fas <?php echo $icon; ?>"></i>
            </div>
            <h3 style="margin: 0 0 5px 0; font-size: 22px;">
                <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>
            </h3>
            <p style="margin: 0; opacity: 0.9; font-size: 16px;">
                @<?php echo htmlspecialchars($usuario['username']); ?>
            </p>
        </div>
        
        <!-- Datos Básicos -->
        <div style="padding: 15px; background: var(--background-main); border-radius: 8px;">
            <h4 style="margin: 0 0 15px 0; color: var(--text-primary); border-bottom: 2px solid <?php echo $color; ?>; padding-bottom: 8px;">
                <i class="fas fa-user"></i> Información Personal
            </h4>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 14px;">
                <div>
                    <strong style="color: var(--text-secondary);">Rol:</strong>
                    <div style="margin-top: 5px;">
                        <span class="badge" style="background: <?php echo $color; ?>; color: white;">
                            <?php echo ucfirst($usuario['rol']); ?>
                        </span>
                    </div>
                </div>
                <div>
                    <strong style="color: var(--text-secondary);">Estado:</strong>
                    <div style="margin-top: 5px;">
                        <?php if ($usuario['estado'] === 'activo'): ?>
                            <span class="badge badge-success">Activo</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactivo</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <strong style="color: var(--text-secondary);">Email:</strong>
                    <div><?php echo htmlspecialchars($usuario['email']); ?></div>
                </div>
                <div>
                    <strong style="color: var(--text-secondary);">Teléfono:</strong>
                    <div><?php echo htmlspecialchars($usuario['telefono'] ?: 'No registrado'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Datos de Médico -->
        <?php if ($datos_medico): ?>
        <div style="padding: 15px; background: var(--background-main); border-radius: 8px;">
            <h4 style="margin: 0 0 15px 0; color: var(--text-primary); border-bottom: 2px solid <?php echo $color; ?>; padding-bottom: 8px;">
                <i class="fas fa-user-md"></i> Información Médica
            </h4>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 14px;">
                <div>
                    <strong style="color: var(--text-secondary);">Especialidad:</strong>
                    <div><?php echo htmlspecialchars($datos_medico['especialidad']); ?></div>
                </div>
                <div>
                    <strong style="color: var(--text-secondary);">N° Colegiatura:</strong>
                    <div><?php echo htmlspecialchars($datos_medico['numero_colegiatura']); ?></div>
                </div>
                <div>
                    <strong style="color: var(--text-secondary);">Consultorio:</strong>
                    <div><?php echo htmlspecialchars($datos_medico['consultorio']); ?></div>
                </div>
                <div>
                    <strong style="color: var(--text-secondary);">Duración Consulta:</strong>
                    <div><?php echo $datos_medico['duracion_consulta']; ?> minutos</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Permisos -->
        <div style="padding: 15px; background: var(--background-main); border-radius: 8px;">
            <h4 style="margin: 0 0 15px 0; color: var(--text-primary); border-bottom: 2px solid <?php echo $color; ?>; padding-bottom: 8px;">
                <i class="fas fa-shield-alt"></i> Permisos Asignados
                <span class="badge badge-primary" style="margin-left: 10px;"><?php echo count($permisos); ?></span>
            </h4>
            <?php if (count($permisos) > 0): ?>
            <div style="display: grid; gap: 8px;">
                <?php foreach ($permisos as $permiso): ?>
                <div style="padding: 10px; background: white; border-radius: 6px; border-left: 3px solid <?php echo $color; ?>;">
                    <div style="font-weight: 600; font-size: 13px;">
                        <i class="fas fa-check" style="color: #10B981;"></i>
                        <?php echo htmlspecialchars($permiso['nombre']); ?>
                    </div>
                    <div style="font-size: 12px; color: var(--text-secondary); margin-top: 3px;">
                        <?php echo htmlspecialchars($permiso['descripcion']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 20px; color: var(--text-secondary);">
                <i class="fas fa-info-circle"></i> No tiene permisos asignados
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Actividad -->
            <div style="padding: 15px; background: var(--background-main); border-radius: 8px;">
                <h4 style="margin: 0 0 15px 0; color: var(--text-primary); border-bottom: 2px solid <?php echo $color; ?>; padding-bottom: 8px;">
                    <i class="fas fa-history"></i> Actividad
                </h4>
                <div style="display: grid; gap: 10px; font-size: 14px;">
                    <div>
                        <strong style="color: var(--text-secondary);">ID Usuario:</strong>
                        <div>#<?php echo $usuario['id']; ?></div>
                    </div>
                    <div>
                        <strong style="color: var(--text-secondary);">Último Acceso:</strong>
                        <div>
                            <?php 
                            if ($usuario['ultimo_acceso']) {
                                echo date('d/m/Y H:i', strtotime($usuario['ultimo_acceso']));
                            } else {
                                echo '<span style="color: var(--text-secondary);">Nunca ha accedido</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
     
        <!-- Acciones -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px;">
            <a href="editar-usuario.php?id=<?php echo $usuario_id; ?>" class="btn btn-warning" style="text-align: center;">
                <i class="fas fa-edit"></i> Editar Usuario
            </a>
            <a href="permisos-usuario.php?id=<?php echo $usuario_id; ?>" class="btn btn-success" style="text-align: center;">
                <i class="fas fa-shield-alt"></i> Gestionar Permisos
            </a>
        </div>
    </div>
    <?php
    
} catch (Exception $e) {
    echo '<p style="color: red;">Error al cargar el detalle: ' . $e->getMessage() . '</p>';
}
?>