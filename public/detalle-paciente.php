<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$paciente_id = $_GET['id'] ?? null;

if (!$paciente_id) {
    echo '<p>Paciente no encontrado</p>';
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener datos del paciente
    $query = "SELECT p.*, s.nombre as seguro_nombre, s.tipo as seguro_tipo
              FROM pacientes p
              LEFT JOIN seguros s ON p.seguro_id = s.id
              WHERE p.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $paciente_id);
    $stmt->execute();
    
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$paciente) {
        echo '<p>Paciente no encontrado</p>';
        exit();
    }
    
    // Calcular edad
    $nacimiento = new DateTime($paciente['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $hoy->diff($nacimiento)->y;
    
    // Obtener número de citas
    $query_citas = "SELECT COUNT(*) as total FROM citas WHERE paciente_id = :id";
    $stmt_citas = $db->prepare($query_citas);
    $stmt_citas->bindParam(':id', $paciente_id);
    $stmt_citas->execute();
    $total_citas = $stmt_citas->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Obtener última cita
    $query_ultima = "SELECT c.fecha, c.hora, CONCAT(u.nombre, ' ', u.apellido) as medico, e.nombre as especialidad
                     FROM citas c
                     INNER JOIN medicos m ON c.medico_id = m.id
                     INNER JOIN usuarios u ON m.usuario_id = u.id
                     INNER JOIN especialidades e ON m.especialidad_id = e.id
                     WHERE c.paciente_id = :id
                     ORDER BY c.fecha DESC, c.hora DESC
                     LIMIT 1";
    $stmt_ultima = $db->prepare($query_ultima);
    $stmt_ultima->bindParam(':id', $paciente_id);
    $stmt_ultima->execute();
    $ultima_cita = $stmt_ultima->fetch(PDO::FETCH_ASSOC);
    
    ?>
    <div style="display: grid; gap: 15px;">
        <!-- Información Principal -->
        <div style="padding: 20px; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; border-radius: 12px; text-align: center;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 36px;">
                <i class="fas <?php echo $paciente['sexo'] === 'M' ? 'fa-male' : 'fa-female'; ?>"></i>
            </div>
            <h3 style="margin: 0 0 5px 0; font-size: 22px;">
                <?php echo htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellido']); ?>
            </h3>
            <p style="margin: 0; opacity: 0.9;">DNI: <?php echo htmlspecialchars($paciente['dni']); ?></p>
        </div>
        
        <!-- Datos Personales -->
        <div style="padding: 15px; background: var(--background-main); border-radius: 8px;">
            <h4 style="margin: 0 0 15px 0; color: var(--text-primary); border-bottom: 2px solid var(--primary-color); padding-bottom: 8px;">
                <i class="fas fa-user"></i> Datos Personales
            </h4>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 14px;">
                <div>
                    <strong style="color: var(--text-secondary);">Edad:</strong>
                    <div><?php echo $edad; ?> años</div>
                </div>
                <div>
                    <strong style="color: var(--text-secondary);">Fecha Nacimiento:</strong>
                    <div><?php echo date('d/m/Y', strtotime($paciente['fecha_nacimiento'])); ?></div>
                </div>
                <div>
                    <strong style="color: var(--text-secondary);">Sexo:</strong>
                    <div><?php echo $paciente['sexo'] === 'M' ? 'Masculino' : 'Femenino'; ?></div>
                </div>
                <div>
                    <strong style="color: var(--text-secondary);">Estado:</strong>
                    <div>
                        <span class="badge badge-success">
                            <?php echo ucfirst($paciente['estado']); ?>
                        </span>
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
                        <i class="fas fa-phone"></i> Teléfono:
                    </strong>
                    <div><?php echo htmlspecialchars($paciente['telefono'] ?: 'No registrado'); ?></div>
                </div>
                <div>
                    <strong style="color: var(--text-secondary);">
                        <i class="fas fa-envelope"></i> Email:
                    </strong>
                    <div><?php echo htmlspecialchars($paciente['email'] ?: 'No registrado'); ?></div>
                </div>
                <div>
                    <strong style="color: var(--text-secondary);">
                        <i class="fas fa-map-marker-alt"></i> Dirección:
                    </strong>
                    <div><?php echo htmlspecialchars($paciente['direccion'] ?: 'No registrada'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Seguro -->
        <div style="padding: 15px; background: var(--background-main); border-radius: 8px;">
            <h4 style="margin: 0 0 15px 0; color: var(--text-primary); border-bottom: 2px solid var(--primary-color); padding-bottom: 8px;">
                <i class="fas fa-shield-alt"></i> Información del Seguro
            </h4>
            <div style="display: grid; gap: 10px; font-size: 14px;">
                <?php if ($paciente['seguro_nombre']): ?>
                <div>
                    <strong style="color: var(--text-secondary);">Seguro:</strong>
                    <div>
                        <span class="badge badge-success">
                            <?php echo htmlspecialchars($paciente['seguro_nombre']); ?>
                        </span>
                        <span style="font-size: 12px; color: var(--text-secondary);">
                            (<?php echo htmlspecialchars($paciente['seguro_tipo']); ?>)
                        </span>
                    </div>
                </div>
                <?php if ($paciente['numero_poliza']): ?>
                <div>
                    <strong style="color: var(--text-secondary);">Número de Póliza:</strong>
                    <div><?php echo htmlspecialchars($paciente['numero_poliza']); ?></div>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div style="text-align: center; color: var(--text-secondary); padding: 10px;">
                    <i class="fas fa-info-circle"></i> Sin seguro médico
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div style="padding: 15px; background: var(--background-main); border-radius: 8px;">
            <h4 style="margin: 0 0 15px 0; color: var(--text-primary); border-bottom: 2px solid var(--primary-color); padding-bottom: 8px;">
                <i class="fas fa-chart-line"></i> Estadísticas
            </h4>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 32px; font-weight: bold; color: var(--primary-color);">
                        <?php echo $total_citas; ?>
                    </div>
                    <div style="font-size: 12px; color: var(--text-secondary);">Total Citas</div>
                </div>
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 14px; font-weight: bold; color: var(--text-primary);">
                        <?php 
                        if ($ultima_cita) {
                            echo date('d/m/Y', strtotime($ultima_cita['fecha']));
                        } else {
                            echo '-';
                        }
                        ?>
                    </div>
                    <div style="font-size: 12px; color: var(--text-secondary);">Última Cita</div>
                </div>
            </div>
            
            <?php if ($ultima_cita): ?>
            <div style="margin-top: 15px; padding: 12px; background: white; border-radius: 8px; border-left: 4px solid var(--primary-color);">
                <div style="font-size: 13px; color: var(--text-secondary);">Última consulta:</div>
                <div style="font-size: 14px; font-weight: 500; margin-top: 5px;">
                    <?php echo htmlspecialchars($ultima_cita['especialidad']); ?> - 
                    <?php echo htmlspecialchars($ultima_cita['medico']); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Información del Sistema -->
        <div style="padding: 12px; background: var(--background-main); border-radius: 8px; font-size: 12px; color: var(--text-secondary);">
            <div><strong>Registrado:</strong> <?php echo date('d/m/Y H:i', strtotime($paciente['fecha_registro'])); ?></div>
        </div>
        
        <!-- Acciones -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px;">
            <a href="nueva-cita.php?paciente_id=<?php echo $paciente_id; ?>" class="btn btn-primary" style="text-align: center;">
                <i class="fas fa-calendar-plus"></i> Nueva Cita
            </a>
            <a href="editar-paciente.php?id=<?php echo $paciente_id; ?>" class="btn btn-warning" style="text-align: center;">
                <i class="fas fa-edit"></i> Editar Datos
            </a>
        </div>
    </div>
    <?php
    
} catch (Exception $e) {
    echo '<p style="color: red;">Error al cargar el detalle: ' . $e->getMessage() . '</p>';
}
?>