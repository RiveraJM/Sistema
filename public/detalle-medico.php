<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$medico_id = $_GET['id'] ?? null;

if (!$medico_id) {
    echo '<p>Médico no encontrado</p>';
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener datos del médico
    $query = "SELECT m.*, 
              CONCAT(u.nombre, ' ', u.apellido) as nombre_completo,
              u.email, u.telefono, u.username,
              e.nombre as especialidad_nombre
              FROM medicos m
              INNER JOIN usuarios u ON m.usuario_id = u.id
              INNER JOIN especialidades e ON m.especialidad_id = e.id
              WHERE m.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $medico_id);
    $stmt->execute();
    
    $medico = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$medico) {
        echo '<p>Médico no encontrado</p>';
        exit();
    }
    
    // Obtener estadísticas
    $query_citas = "SELECT COUNT(*) as total FROM citas WHERE medico_id = :id";
    $stmt_citas = $db->prepare($query_citas);
    $stmt_citas->bindParam(':id', $medico_id);
    $stmt_citas->execute();
    $total_citas = $stmt_citas->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Citas de hoy
    $query_hoy = "SELECT COUNT(*) as total FROM citas WHERE medico_id = :id AND DATE(fecha) = CURDATE()";
    $stmt_hoy = $db->prepare($query_hoy);
    $stmt_hoy->bindParam(':id', $medico_id);
    $stmt_hoy->execute();
    $citas_hoy = $stmt_hoy->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Horarios configurados
    $query_horarios = "SELECT COUNT(DISTINCT dia_semana) as dias FROM horarios_medicos WHERE medico_id = :id AND estado = 'activo'";
    $stmt_horarios = $db->prepare($query_horarios);
    $stmt_horarios->bindParam(':id', $medico_id);
    $stmt_horarios->execute();
    $dias_atencion = $stmt_horarios->fetch(PDO::FETCH_ASSOC)['dias'];
    
    ?>
    <div style="display: grid; gap: 15px;">
        <!-- Información Principal -->
        <div style="padding: 20px; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; border-radius: 12px; text-align: center;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 36px;">
                <i class="fas fa-user-md"></i>
            </div>
            <h3 style="margin: 0 0 5px 0; font-size: 22px;">
                Dr(a). <?php echo htmlspecialchars($medico['nombre_completo']); ?>
            </h3>
            <p style="margin: 0; opacity: 0.9; font-size: 16px;">
                <?php echo htmlspecialchars($medico['especialidad_nombre']); ?>
            </p>
        </div>
        
        <!-- Datos Profesionales -->
        <div style="padding: 15px; background: var(--background-main); border-radius: 8px;">
            <h4 style="margin: 0 0 15px 0; color: var(--text-primary); border-bottom: 2px solid var(--primary-color); padding-bottom: 8px;">
                <i class="fas fa-id-card"></i> Información Profesional
            </h4>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 14px;">
                <div>
                    <strong style="color: var(--text-secondary);">N° Colegiatura:</strong>
                    <div><?php echo htmlspecialchars($medico['numero_colegiatura']); ?></div>
                </div>
                <div>
                    <strong style="color: var(--text-secondary);">Consultorio:</strong>
                    <div><?php echo htmlspecialchars($medico['consultorio']); ?></div>
                </div>
                <div>
                    <strong style="color: var(--text-secondary);">Duración Consulta:</strong>
                    <div><?php echo $medico['duracion_consulta']; ?> minutos</div>
                </div>
                <div>
                    <strong style="color: var(--text-secondary);">Estado:</strong>
                    <div>
                        <?php if ($medico['estado'] === 'activo'): ?>
                            <span class="badge badge-success">Activo</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactivo</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contacto -->
        <div style="padding: 15px; background: var(--background-main); border-radius: 8px;">
            <h4 style="margin: 0 0 15px 0; color: var(--text-primary); border-bottom: 2px solid var(--primary-color); padding-bottom: 8px;">
                <i class="fas fa-address-book"></i> Información de Contacto
            </h4>
            <div style="display: grid; gap: 10px; font-size: 14px;">
                <div>
                    <strong style="color: var(--text-secondary);">
                        <i class="fas fa-envelope"></i> Email:
                    </strong>
                    <div><?php echo htmlspecialchars($medico['email']); ?></div>
                </div>
                <div>
                    <strong style="color: var(--text-secondary);">
                        <i class="fas fa-phone"></i> Teléfono:
                    </strong>
                    <div><?php echo htmlspecialchars($medico['telefono'] ?: 'No registrado'); ?></div>
                </div>
                <div>
                    <strong style="color: var(--text-secondary);">
                        <i class="fas fa-user"></i> Usuario:
                    </strong>
                    <div><?php echo htmlspecialchars($medico['username']); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div style="padding: 15px; background: var(--background-main); border-radius: 8px;">
            <h4 style="margin: 0 0 15px 0; color: var(--text-primary); border-bottom: 2px solid var(--primary-color); padding-bottom: 8px;">
                <i class="fas fa-chart-line"></i> Estadísticas
            </h4>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 28px; font-weight: bold; color: var(--primary-color);">
                        <?php echo $total_citas; ?>
                    </div>
                    <div style="font-size: 12px; color: var(--text-secondary);">Total Citas</div>
                </div>
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 28px; font-weight: bold; color: #10B981;">
                        <?php echo $citas_hoy; ?>
                    </div>
                    <div style="font-size: 12px; color: var(--text-secondary);">Citas Hoy</div>
                </div>
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 28px; font-weight: bold; color: #F59E0B;">
                        <?php echo $dias_atencion; ?>
                    </div>
                    <div style="font-size: 12px; color: var(--text-secondary);">Días/Semana</div>
                </div>
            </div>
        </div>
        
        <!-- Acciones -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px;">
            <button onclick="verHorarios(<?php echo $medico_id; ?>); cerrarModal();" class="btn btn-primary" style="text-align: center;">
                <i class="fas fa-clock"></i> Ver Horarios
            </button>
            <?php if (getUserRole() === 'admin'): ?>
            <a href="editar-medico.php?id=<?php echo $medico_id; ?>" class="btn btn-warning" style="text-align: center;">
                <i class="fas fa-edit"></i> Editar
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    
} catch (Exception $e) {
    echo '<p style="color: red;">Error al cargar el detalle: ' . $e->getMessage() . '</p>';
}
?>