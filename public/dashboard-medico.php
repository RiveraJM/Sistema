<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Clínico</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-medico {
            display: grid;
            gap: 20px;
        }
        
        .bienvenida-card {
            background: linear-gradient(135deg, #00D4D4 0%, #00A0A0 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 212, 212, 0.3);
        }
        
        .bienvenida-card h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        
        .bienvenida-card p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }
        
        .stats-grid-medico {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card-medico {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        
        .stat-card-medico:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .stat-card-medico .numero {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .stat-card-medico .label {
            color: #6B7280;
            font-size: 14px;
        }
        
        .horarios-container {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        
        .horarios-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #E5E7EB;
        }
        
        .horarios-header h3 {
            margin: 0;
            font-size: 22px;
            color: #1F2937;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .horarios-grid {
            display: grid;
            gap: 15px;
        }
        
        .horario-item {
            display: grid;
            grid-template-columns: 120px 1fr 1fr 100px 80px;
            align-items: center;
            padding: 18px 20px;
            background: #F9FAFB;
            border-radius: 12px;
            border-left: 4px solid #00D4D4;
            transition: all 0.3s;
        }
        
        .horario-item:hover {
            background: #F3F4F6;
            transform: translateX(5px);
        }
        
        .horario-item.inactivo {
            opacity: 0.5;
            border-left-color: #D1D5DB;
        }
        
        .dia-nombre {
            font-weight: 600;
            font-size: 15px;
            color: #1F2937;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .horario-tiempo {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6B7280;
            font-size: 14px;
        }
        
        .cupos-badge {
            background: #DBEAFE;
            color: #1E40AF;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
        }
        
        .estado-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }
        
        .estado-badge.activo {
            background: #D1FAE5;
            color: #065F46;
        }
        
        .estado-badge.inactivo {
            background: #FEE2E2;
            color: #991B1B;
        }
        
        .resumen-atencion {
            background: #F0FDFA;
            padding: 20px;
            border-radius: 12px;
            border: 2px dashed #00D4D4;
            margin-top: 20px;
        }
        
        .resumen-atencion p {
            margin: 0;
            color: #1F2937;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .resumen-atencion strong {
            color: #00A0A0;
        }
        
        .proximas-citas-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        
        .cita-item-dashboard {
            padding: 15px;
            background: #F9FAFB;
            border-radius: 10px;
            margin-bottom: 12px;
            border-left: 4px solid #F59E0B;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .cita-item-dashboard:hover {
            background: #F3F4F6;
            transform: translateX(5px);
        }
        
        .cita-item-dashboard.confirmada {
            border-left-color: #10B981;
        }
        
        .cita-item-dashboard.en_atencion {
            border-left-color: #3B82F6;
        }
        
        .cita-hora {
            font-size: 18px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 5px;
        }
        
        .cita-paciente {
            font-size: 14px;
            color: #6B7280;
            margin-bottom: 3px;
        }
        
        .dos-columnas {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        @media (max-width: 1024px) {
            .dos-columnas {
                grid-template-columns: 1fr;
            }
            
            .horario-item {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="dashboard-content">
                <div class="dashboard-medico">
                    <!-- Bienvenida -->
                    <div class="bienvenida-card">
                        <h2>¡Bienvenido, Dr(a). <?php echo htmlspecialchars($user_name); ?>!</h2>
                        <p>
                            <i class="fas fa-stethoscope"></i> 
                            <?php echo htmlspecialchars($medico['especialidad_nombre']); ?> • 
                            Consultorio <?php echo htmlspecialchars($medico['consultorio']); ?>
                        </p>
                    </div>

                    <!-- Estadísticas -->
                    <div class="stats-grid-medico">
                        <div class="stat-card-medico" style="border-left-color: #F59E0B;">
                            <div class="numero" style="color: #F59E0B;"><?php echo $citas_hoy; ?></div>
                            <div class="label">Citas Hoy</div>
                        </div>
                        
                        <div class="stat-card-medico" style="border-left-color: #10B981;">
                            <div class="numero" style="color: #10B981;"><?php echo $citas_pendientes; ?></div>
                            <div class="label">Citas Pendientes</div>
                        </div>
                        
                        <div class="stat-card-medico" style="border-left-color: #3B82F6;">
                            <div class="numero" style="color: #3B82F6;"><?php echo $citas_semana; ?></div>
                            <div class="label">Citas Esta Semana</div>
                        </div>
                        
                        <div class="stat-card-medico" style="border-left-color: #6366F1;">
                            <div class="numero" style="color: #6366F1;"><?php echo $total_pacientes; ?></div>
                            <div class="label">Pacientes Atendidos</div>
                        </div>
                    </div>

                    <!-- Dos columnas: Horarios + Próximas Citas -->
                    <div class="dos-columnas">
                        <!-- Horarios de Atención -->
                        <div class="horarios-container">
                            <div class="horarios-header">
                                <h3>
                                    <i class="fas fa-calendar-week" style="color: #00D4D4;"></i>
                                    Horarios de Atención
                                </h3>
                                <a href="gestionar-horarios.php?medico_id=<?php echo $medico_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-cog"></i> Gestionar Horarios
                                </a>
                            </div>

                            <?php if (count($horarios) > 0): ?>
                            <div class="horarios-grid">
                                <?php 
                                // Mapeo de días en español para coincidir con la BD
                                $dias_map = [
                                    'lunes' => 'Lunes',
                                    'martes' => 'Martes',
                                    'miercoles' => 'Miércoles',
                                    'jueves' => 'Jueves',
                                    'viernes' => 'Viernes',
                                    'sabado' => 'Sábado',
                                    'domingo' => 'Domingo'
                                ];
                                
                                foreach ($horarios as $horario): 
                                    $dia_mostrar = $dias_map[$horario['dia_semana']] ?? ucfirst($horario['dia_semana']);
                                ?>
                                <div class="horario-item <?php echo $horario['estado'] === 'inactivo' ? 'inactivo' : ''; ?>">
                                    <div class="dia-nombre">
                                        <i class="fas fa-calendar-day" style="color: #00D4D4;"></i>
                                        <?php echo $dia_mostrar; ?>
                                    </div>
                                    
                                    <div class="horario-tiempo">
                                        <i class="fas fa-clock"></i>
                                        <?php echo substr($horario['hora_inicio'], 0, 5); ?>
                                    </div>
                                    
                                    <div class="horario-tiempo">
                                        <i class="fas fa-arrow-right"></i>
                                        <?php echo substr($horario['hora_fin'], 0, 5); ?>
                                    </div>
                                    
                                    <div class="cupos-badge">
                                        <?php echo $horario['cupos_por_hora']; ?> cupos/h
                                    </div>
                                    
                                    <div class="estado-badge <?php echo $horario['estado']; ?>">
                                        <?php echo ucfirst($horario['estado']); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="resumen-atencion">
                                <p>
                                    <i class="fas fa-info-circle" style="color: #00D4D4;"></i>
                                    <strong>Resumen de Atención:</strong> 
                                    Atiende <strong><?php echo $dias_atiende; ?> días</strong> a la semana
                                </p>
                            </div>
                            <?php else: ?>
                            <div style="text-align: center; padding: 40px; color: #9CA3AF;">
                                <i class="fas fa-calendar-times" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 15px;"></i>
                                <p>No tienes horarios configurados</p>
                                <p style="font-size: 13px; margin: 10px 0 20px 0;">
                                    Contacta con el administrador para que configure tus horarios de atención
                                </p>
                                <a href="gestionar-horarios.php?medico_id=<?php echo $medico_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Ver Configuración
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Próximas Citas de Hoy -->
                        <div class="proximas-citas-card">
                            <h3 style="margin: 0 0 20px 0; font-size: 20px; color: #1F2937; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-clock" style="color: #F59E0B;"></i>
                                Próximas Citas de Hoy
                            </h3>

                            <?php if (count($proximas_citas) > 0): ?>
                                <?php foreach ($proximas_citas as $cita): ?>
                                <div class="cita-item-dashboard <?php echo $cita['estado']; ?>" onclick="window.location.href='detalle-cita.php?id=<?php echo $cita['id']; ?>'">
                                    <div class="cita-hora">
                                        <i class="fas fa-clock"></i>
                                        <?php echo substr($cita['hora'], 0, 5); ?>
                                    </div>
                                    <div class="cita-paciente">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($cita['paciente_nombre']); ?>
                                    </div>
                                    <div style="font-size: 12px; color: #9CA3AF;">
                                        DNI: <?php echo $cita['paciente_dni']; ?>
                                    </div>
                                    <?php if (isset($cita['motivo_consulta']) && $cita['motivo_consulta']): ?>
                                    <div style="font-size: 12px; color: #6B7280; margin-top: 5px; font-style: italic;">
                                        "<?php echo htmlspecialchars(substr($cita['motivo_consulta'], 0, 40)) . (strlen($cita['motivo_consulta']) > 40 ? '...' : ''); ?>"
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                                
                                <a href="mis-citas.php" class="btn btn-secondary" style="width: 100%; margin-top: 15px;">
                                    <i class="fas fa-calendar-alt"></i> Ver Todas las Citas
                                </a>
                            <?php else: ?>
                            <div style="text-align: center; padding: 40px; color: #9CA3AF;">
                                <i class="fas fa-calendar-check" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 15px;"></i>
                                <p>No tienes citas programadas para hoy</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Accesos Rápidos -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <a href="mis-citas.php" class="btn btn-primary" style="padding: 20px; display: flex; flex-direction: column; align-items: center; gap: 10px;">
                            <i class="fas fa-calendar-alt" style="font-size: 32px;"></i>
                            <span>Ver Mis Citas</span>
                        </a>
                        
                        <a href="consultas.php" class="btn btn-success" style="padding: 20px; display: flex; flex-direction: column; align-items: center; gap: 10px;">
                            <i class="fas fa-stethoscope" style="font-size: 32px;"></i>
                            <span>Mis Consultas</span>
                        </a>
                        
                        <a href="pacientes.php" class="btn btn-info" style="padding: 20px; display: flex; flex-direction: column; align-items: center; gap: 10px;">
                            <i class="fas fa-users" style="font-size: 32px;"></i>
                            <span>Mis Pacientes</span>
                        </a>
                        
                        <a href="perfil.php" class="btn btn-secondary" style="padding: 20px; display: flex; flex-direction: column; align-items: center; gap: 10px;">
                            <i class="fas fa-user-cog" style="font-size: 32px;"></i>
                            <span>Mi Perfil</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>