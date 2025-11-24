<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$cita_id = $_GET['id'] ?? null;

if (!$cita_id) {
    echo '<p>Cita no encontrada</p>';
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT c.*, 
              CONCAT(p.nombre, ' ', p.apellido) as paciente_nombre,
              p.dni, p.telefono, p.email,
              CONCAT(u.nombre, ' ', u.apellido) as medico_nombre,
              e.nombre as especialidad,
              m.consultorio,
              s.nombre as seguro,
              CONCAT(ur.nombre, ' ', ur.apellido) as registrado_por
              FROM citas c
              INNER JOIN pacientes p ON c.paciente_id = p.id
              INNER JOIN medicos m ON c.medico_id = m.id
              INNER JOIN usuarios u ON m.usuario_id = u.id
              INNER JOIN especialidades e ON m.especialidad_id = e.id
              LEFT JOIN seguros s ON p.seguro_id = s.id
              LEFT JOIN usuarios ur ON c.usuario_registro_id = ur.id
              WHERE c.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $cita_id);
    $stmt->execute();
    
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cita) {
        echo '<p>Cita no encontrada</p>';
        exit();
    }
    
    // Definir color del estado
    $estado_colors = [
        'programada' => '#F59E0B',
        'confirmada' => '#10B981',
        'en_atencion' => '#00D4D4',
        'atendida' => '#059669',
        'cancelada' => '#EF4444',
        'ausente' => '#DC2626'
    ];
    $color = $estado_colors[$cita['estado']] ?? '#6B7280';
    
    ?>
    <div style="display: grid; gap: 15px;">
        <!-- Estado -->
        <div style="text-align: center; padding: 15px; background: <?php echo $color; ?>; color: white; border-radius: 8px;">
            <h3 style="margin: 0; font-size: 18px;">
                <?php echo ucfirst(str_replace('_', ' ', $cita['estado'])); ?>
            </h3>
        </div>
        
        <!-- Información del Paciente -->
        <div style="padding: 15px; background: var(--background-main); border-radius: 8px;">
            <h4 style="margin: 0 0 10px 0; color: var(--text-primary);">
                <i class="fas fa-user"></i> Información del Paciente
            </h4>
            <div style="display: grid; gap: 8px; font-size: 14px;">
                <div><strong>Nombre:</strong> <?php echo htmlspecialchars($cita['paciente_nombre']); ?></div>
                <div><strong>DNI:</strong> <?php echo htmlspecialchars($cita['dni']); ?></div>
                <div><strong>Teléfono:</strong> <?php echo htmlspecialchars($cita['telefono'] ?: 'No registrado'); ?></div>
                <div><strong>Email:</strong> <?php echo htmlspecialchars($cita['email'] ?: 'No registrado'); ?></div>
                <div><strong>Seguro:</strong> <?php echo htmlspecialchars($cita['seguro'] ?: 'Sin seguro'); ?></div>
            </div>
        </div>
        
        <!-- Información de la Cita -->
        <div style="padding: 15px; background: var(--background-main); border-radius: 8px;">
            <h4 style="margin: 0 0 10px 0; color: var(--text-primary);">
                <i class="fas fa-calendar-alt"></i> Detalles de la Cita
            </h4>
            <div style="display: grid; gap: 8px; font-size: 14px;">
                <div><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></div>
                <div><strong>Hora:</strong> <?php echo date('H:i', strtotime($cita['hora'])); ?></div>
                <div><strong>Médico:</strong> <?php echo htmlspecialchars($cita['medico_nombre']); ?></div>
                <div><strong>Especialidad:</strong> <?php echo htmlspecialchars($cita['especialidad']); ?></div>
                <div><strong>Consultorio:</strong> <?php echo htmlspecialchars($cita['consultorio']); ?></div>
                <div><strong>Tipo:</strong> <?php echo ucfirst($cita['tipo_cita']); ?></div>
            </div>
        </div>
        
        <!-- Motivo y Observaciones -->
        <?php if ($cita['motivo_consulta']): ?>
        <div style="padding: 15px; background: var(--background-main); border-radius: 8px;">
            <h4 style="margin: 0 0 10px 0; color: var(--text-primary);">
                <i class="fas fa-comment-medical"></i> Motivo de Consulta
            </h4>
            <p style="margin: 0; font-size: 14px;"><?php echo nl2br(htmlspecialchars($cita['motivo_consulta'])); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($cita['observaciones']): ?>
        <div style="padding: 15px; background: var(--background-main); border-radius: 8px;">
            <h4 style="margin: 0 0 10px 0; color: var(--text-primary);">
                <i class="fas fa-notes-medical"></i> Observaciones
            </h4>
            <p style="margin: 0; font-size: 14px;"><?php echo nl2br(htmlspecialchars($cita['observaciones'])); ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Información Adicional -->
        <div style="padding: 15px; background: var(--background-main); border-radius: 8px; font-size: 12px; color: var(--text-secondary);">
            <div><strong>Registrado por:</strong> <?php echo htmlspecialchars($cita['registrado_por'] ?: 'Sistema'); ?></div>
            <div><strong>Fecha de registro:</strong> <?php echo date('d/m/Y H:i', strtotime($cita['fecha_registro'])); ?></div>
        </div>
    </div>
    <?php
    
} catch (Exception $e) {
    echo '<p style="color: red;">Error al cargar el detalle</p>';
}
?>