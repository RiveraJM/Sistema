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
    $query = "SELECT CONCAT(u.nombre, ' ', u.apellido) as nombre_completo, e.nombre as especialidad
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
    
    // Obtener horarios
    $query_horarios = "SELECT * FROM horarios_medicos 
                       WHERE medico_id = :id 
                       ORDER BY FIELD(dia_semana, 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo')";
    
    $stmt_horarios = $db->prepare($query_horarios);
    $stmt_horarios->bindParam(':id', $medico_id);
    $stmt_horarios->execute();
    
    $horarios = $stmt_horarios->fetchAll(PDO::FETCH_ASSOC);
    
    $dias_español = [
        'lunes' => 'Lunes',
        'martes' => 'Martes',
        'miercoles' => 'Miércoles',
        'jueves' => 'Jueves',
        'viernes' => 'Viernes',
        'sabado' => 'Sábado',
        'domingo' => 'Domingo'
    ];
    
    ?>
    <div style="display: grid; gap: 15px;">
        <!-- Información del médico -->
        <div style="padding: 15px; background: var(--background-main); border-radius: 8px; text-align: center;">
            <h4 style="margin: 0 0 5px 0;">Dr(a). <?php echo htmlspecialchars($medico['nombre_completo']); ?></h4>
            <p style="margin: 0; color: var(--text-secondary); font-size: 14px;">
                <?php echo htmlspecialchars($medico['especialidad']); ?>
            </p>
        </div>
        
        <?php if (count($horarios) > 0): ?>
        <!-- Tabla de horarios -->
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--background-main);">
                        <th style="padding: 12px; text-align: left; font-size: 13px; font-weight: 600; color: var(--text-secondary);">Día</th>
                        <th style="padding: 12px; text-align: left; font-size: 13px; font-weight: 600; color: var(--text-secondary);">Hora Inicio</th>
                        <th style="padding: 12px; text-align: left; font-size: 13px; font-weight: 600; color: var(--text-secondary);">Hora Fin</th>
                        <th style="padding: 12px; text-align: center; font-size: 13px; font-weight: 600; color: var(--text-secondary);">Cupos/Hora</th>
                        <th style="padding: 12px; text-align: center; font-size: 13px; font-weight: 600; color: var(--text-secondary);">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($horarios as $horario): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px; font-weight: 500;">
                            <i class="fas fa-calendar-day" style="color: var(--primary-color); margin-right: 8px;"></i>
                            <?php echo $dias_español[$horario['dia_semana']]; ?>
                        </td>
                        <td style="padding: 12px;">
                            <i class="fas fa-clock" style="color: var(--text-secondary); margin-right: 5px;"></i>
                            <?php echo date('H:i', strtotime($horario['hora_inicio'])); ?>
                        </td>
                        <td style="padding: 12px;">
                            <i class="fas fa-clock" style="color: var(--text-secondary); margin-right: 5px;"></i>
                            <?php echo date('H:i', strtotime($horario['hora_fin'])); ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <span style="background: rgba(0, 212, 212, 0.1); color: var(--primary-dark); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                <?php echo $horario['cupos_por_hora']; ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php if ($horario['estado'] === 'activo'): ?>
                                <span class="badge badge-success">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Resumen -->
        <div style="padding: 15px; background: rgba(0, 212, 212, 0.05); border-left: 4px solid var(--primary-color); border-radius: 8px;">
            <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 5px;">
                <i class="fas fa-info-circle"></i> Resumen de Atención
            </div>
            <div style="font-size: 14px; font-weight: 500;">
                Atiende <?php echo count($horarios); ?> días a la semana
            </div>
        </div>
        
        <?php else: ?>
        <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
            <i class="fas fa-calendar-times" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
            <p>No hay horarios configurados para este médico</p>
        </div>
        <?php endif; ?>
        
        <!-- Acciones -->
        <?php if (getUserRole() === 'admin'): ?>
        <div style="margin-top: 10px;">
            <a href="gestionar-horarios.php?medico_id=<?php echo $medico_id; ?>" class="btn btn-primary" style="width: 100%; text-align: center;">
                <i class="fas fa-cog"></i> Gestionar Horarios
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php
    
} catch (Exception $e) {
    echo '<p style="color: red;">Error al cargar los horarios: ' . $e->getMessage() . '</p>';
}
?>