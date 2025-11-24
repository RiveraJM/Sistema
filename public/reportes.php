<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

// Verificar permisos
if (!hasPermission('ver_reportes') && getUserRole() !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes actual
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d'); // Hoy

// ESTADÍSTICAS GENERALES
// Total de citas en el período
$query_citas = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'programada' THEN 1 ELSE 0 END) as programadas,
                SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
                SUM(CASE WHEN estado = 'atendida' THEN 1 ELSE 0 END) as atendidas,
                SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
                SUM(CASE WHEN estado = 'ausente' THEN 1 ELSE 0 END) as ausentes
                FROM citas 
                WHERE fecha BETWEEN :fecha_inicio AND :fecha_fin";
$stmt_citas = $db->prepare($query_citas);
$stmt_citas->execute([':fecha_inicio' => $fecha_inicio, ':fecha_fin' => $fecha_fin]);
$stats_citas = $stmt_citas->fetch(PDO::FETCH_ASSOC);

// Pacientes atendidos en el período
$query_pacientes = "SELECT COUNT(DISTINCT paciente_id) as total 
                    FROM citas 
                    WHERE fecha BETWEEN :fecha_inicio AND :fecha_fin 
                    AND estado = 'atendida'";
$stmt_pacientes = $db->prepare($query_pacientes);
$stmt_pacientes->execute([':fecha_inicio' => $fecha_inicio, ':fecha_fin' => $fecha_fin]);
$pacientes_atendidos = $stmt_pacientes->fetch(PDO::FETCH_ASSOC)['total'];

// Nuevos pacientes registrados en el período
$query_nuevos = "SELECT COUNT(*) as total 
                 FROM pacientes 
                 WHERE DATE(fecha_registro) BETWEEN :fecha_inicio AND :fecha_fin";
$stmt_nuevos = $db->prepare($query_nuevos);
$stmt_nuevos->execute([':fecha_inicio' => $fecha_inicio, ':fecha_fin' => $fecha_fin]);
$pacientes_nuevos = $stmt_nuevos->fetch(PDO::FETCH_ASSOC)['total'];

// Historias clínicas registradas
$query_historias = "SELECT COUNT(*) as total 
                    FROM historia_clinica 
                    WHERE fecha_consulta BETWEEN :fecha_inicio AND :fecha_fin";
$stmt_historias = $db->prepare($query_historias);
$stmt_historias->execute([':fecha_inicio' => $fecha_inicio, ':fecha_fin' => $fecha_fin]);
$total_historias = $stmt_historias->fetch(PDO::FETCH_ASSOC)['total'];

// CITAS POR DÍA (para gráfico de líneas)
$query_citas_dia = "SELECT DATE(fecha) as fecha, COUNT(*) as total 
                    FROM citas 
                    WHERE fecha BETWEEN :fecha_inicio AND :fecha_fin
                    GROUP BY DATE(fecha)
                    ORDER BY fecha";
$stmt_citas_dia = $db->prepare($query_citas_dia);
$stmt_citas_dia->execute([':fecha_inicio' => $fecha_inicio, ':fecha_fin' => $fecha_fin]);
$citas_por_dia = $stmt_citas_dia->fetchAll(PDO::FETCH_ASSOC);

// CITAS POR ESPECIALIDAD
$query_especialidad = "SELECT e.nombre, COUNT(c.id) as total 
                       FROM citas c
                       INNER JOIN medicos m ON c.medico_id = m.id
                       INNER JOIN especialidades e ON m.especialidad_id = e.id
                       WHERE c.fecha BETWEEN :fecha_inicio AND :fecha_fin
                       GROUP BY e.id, e.nombre
                       ORDER BY total DESC
                       LIMIT 10";
$stmt_especialidad = $db->prepare($query_especialidad);
$stmt_especialidad->execute([':fecha_inicio' => $fecha_inicio, ':fecha_fin' => $fecha_fin]);
$citas_especialidad = $stmt_especialidad->fetchAll(PDO::FETCH_ASSOC);

// MÉDICOS MÁS ACTIVOS
$query_medicos = "SELECT CONCAT(u.nombre, ' ', u.apellido) as medico, 
                  e.nombre as especialidad,
                  COUNT(c.id) as total_citas
                  FROM citas c
                  INNER JOIN medicos m ON c.medico_id = m.id
                  INNER JOIN usuarios u ON m.usuario_id = u.id
                  INNER JOIN especialidades e ON m.especialidad_id = e.id
                  WHERE c.fecha BETWEEN :fecha_inicio AND :fecha_fin
                  AND c.estado = 'atendida'
                  GROUP BY c.medico_id, u.nombre, u.apellido, e.nombre
                  ORDER BY total_citas DESC
                  LIMIT 10";
$stmt_medicos = $db->prepare($query_medicos);
$stmt_medicos->execute([':fecha_inicio' => $fecha_inicio, ':fecha_fin' => $fecha_fin]);
$medicos_activos = $stmt_medicos->fetchAll(PDO::FETCH_ASSOC);

// DIAGNÓSTICOS MÁS FRECUENTES
$query_diagnosticos = "SELECT d.diagnostico, COUNT(*) as total
                       FROM diagnosticos d
                       INNER JOIN historia_clinica hc ON d.historia_id = hc.id
                       WHERE hc.fecha_consulta BETWEEN :fecha_inicio AND :fecha_fin
                       GROUP BY d.diagnostico
                       ORDER BY total DESC
                       LIMIT 10";
$stmt_diagnosticos = $db->prepare($query_diagnosticos);
$stmt_diagnosticos->execute([':fecha_inicio' => $fecha_inicio, ':fecha_fin' => $fecha_fin]);
$diagnosticos_frecuentes = $stmt_diagnosticos->fetchAll(PDO::FETCH_ASSOC);

// TASA DE AUSENTISMO
$tasa_ausentismo = $stats_citas['total'] > 0 ? 
    round(($stats_citas['ausentes'] / $stats_citas['total']) * 100, 2) : 0;

// TASA DE CANCELACIÓN
$tasa_cancelacion = $stats_citas['total'] > 0 ? 
    round(($stats_citas['canceladas'] / $stats_citas['total']) * 100, 2) : 0;

// TASA DE ATENCIÓN
$tasa_atencion = $stats_citas['total'] > 0 ? 
    round(($stats_citas['atendidas'] / $stats_citas['total']) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes y Estadísticas - Sistema Clínico</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        .metric-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            text-align: center;
        }
        .metric-value {
            font-size: 36px;
            font-weight: bold;
            color: var(--primary-color);
            margin: 10px 0;
        }
        .metric-label {
            font-size: 14px;
            color: var(--text-secondary);
            text-transform: uppercase;
        }
        .metric-change {
            font-size: 12px;
            margin-top: 8px;
        }
        .metric-change.positive {
            color: #10B981;
        }
        .metric-change.negative {
            color: #EF4444;
        }
        .ranking-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: var(--background-main);
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .ranking-position {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        .ranking-info {
            flex: 1;
            margin-left: 15px;
        }
        .ranking-value {
            font-weight: 600;
            color: var(--primary-color);
        }
        @media print {
            .no-print {
                display: none !important;
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
                <div class="page-header no-print">
                    <div>
                        <h1 class="page-title">Reportes y Estadísticas</h1>
                        <p class="page-subtitle">Análisis detallado del desempeño de la clínica</p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button onclick="exportarPDF()" class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> Exportar PDF
                        </button>
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-2 no-print">
                    <div class="card-body">
                        <form method="GET" action="reportes.php" style="display: flex; gap: 15px; align-items: end;">
                            <div class="form-group" style="margin: 0; flex: 1;">
                                <label class="form-label">Fecha Inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control" 
                                       value="<?php echo $fecha_inicio; ?>">
                            </div>
                            
                            <div class="form-group" style="margin: 0; flex: 1;">
                                <label class="form-label">Fecha Fin</label>
                                <input type="date" name="fecha_fin" class="form-control" 
                                       value="<?php echo $fecha_fin; ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                            
                            <button type="button" onclick="aplicarRangoRapido('hoy')" class="btn btn-sm btn-secondary">
                                Hoy
                            </button>
                            <button type="button" onclick="aplicarRangoRapido('semana')" class="btn btn-sm btn-secondary">
                                Esta Semana
                            </button>
                            <button type="button" onclick="aplicarRangoRapido('mes')" class="btn btn-sm btn-secondary">
                                Este Mes
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Período seleccionado -->
                <div style="text-align: center; margin-bottom: 20px; color: var(--text-secondary);">
                    <strong>Período analizado:</strong> 
                    <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> - 
                    <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
                </div>

                <!-- Métricas Principales -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div class="metric-card">
                        <div class="metric-label">Total Citas</div>
                        <div class="metric-value"><?php echo $stats_citas['total']; ?></div>
                        <div class="metric-change">
                            <i class="fas fa-calendar-check"></i> En el período
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-label">Citas Atendidas</div>
                        <div class="metric-value"><?php echo $stats_citas['atendidas']; ?></div>
                        <div class="metric-change positive">
                            <i class="fas fa-arrow-up"></i> <?php echo $tasa_atencion; ?>% del total
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-label">Pacientes Atendidos</div>
                        <div class="metric-value"><?php echo $pacientes_atendidos; ?></div>
                        <div class="metric-change">
                            <i class="fas fa-users"></i> Pacientes únicos
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-label">Nuevos Pacientes</div>
                        <div class="metric-value"><?php echo $pacientes_nuevos; ?></div>
                        <div class="metric-change positive">
                            <i class="fas fa-user-plus"></i> Registrados
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-label">Historias Clínicas</div>
                        <div class="metric-value"><?php echo $total_historias; ?></div>
                        <div class="metric-change">
                            <i class="fas fa-file-medical"></i> Registradas
                        </div>
                    </div>
                </div>

                <!-- KPIs de Eficiencia -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div class="card">
                        <div class="card-body" style="text-align: center;">
                            <h4 style="margin: 0 0 15px 0; color: var(--text-secondary); font-size: 14px;">TASA DE ATENCIÓN</h4>
                            <div style="font-size: 48px; font-weight: bold; color: #10B981;">
                                <?php echo $tasa_atencion; ?>%
                            </div>
                            <div style="margin-top: 10px; padding: 8px; background: rgba(16, 185, 129, 0.1); border-radius: 6px;">
                                <?php echo $stats_citas['atendidas']; ?> de <?php echo $stats_citas['total']; ?> citas
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body" style="text-align: center;">
                            <h4 style="margin: 0 0 15px 0; color: var(--text-secondary); font-size: 14px;">TASA DE AUSENTISMO</h4>
                            <div style="font-size: 48px; font-weight: bold; color: #EF4444;">
                                <?php echo $tasa_ausentismo; ?>%
                            </div>
                            <div style="margin-top: 10px; padding: 8px; background: rgba(239, 68, 68, 0.1); border-radius: 6px;">
                                <?php echo $stats_citas['ausentes']; ?> ausentes
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body" style="text-align: center;">
                            <h4 style="margin: 0 0 15px 0; color: var(--text-secondary); font-size: 14px;">TASA DE CANCELACIÓN</h4>
                            <div style="font-size: 48px; font-weight: bold; color: #F59E0B;">
                                <?php echo $tasa_cancelacion; ?>%
                            </div>
                            <div style="margin-top: 10px; padding: 8px; background: rgba(245, 158, 11, 0.1); border-radius: 6px;">
                                <?php echo $stats_citas['canceladas']; ?> canceladas
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 20px;">
                    <!-- Gráfico de Citas por Día -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line"></i> Evolución de Citas
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartCitasDia"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico de Estado de Citas -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie"></i> Distribución por Estado
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartEstadoCitas"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico de Citas por Especialidad -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar"></i> Citas por Especialidad
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartEspecialidad"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Top Diagnósticos -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-diagnoses"></i> Diagnósticos Más Frecuentes
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($diagnosticos_frecuentes) > 0): ?>
                            <?php foreach ($diagnosticos_frecuentes as $index => $diagnostico): ?>
                            <div class="ranking-item">
                                <div class="ranking-position"><?php echo $index + 1; ?></div>
                                <div class="ranking-info">
                                    <div style="font-weight: 500; color: var(--text-primary);">
                                        <?php echo htmlspecialchars(substr($diagnostico['diagnostico'], 0, 60)); ?>
                                        <?php echo strlen($diagnostico['diagnostico']) > 60 ? '...' : ''; ?>
                                    </div>
                                </div>
                                <div class="ranking-value">
                                    <?php echo $diagnostico['total']; ?> casos
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <p style="text-align: center; color: var(--text-secondary); padding: 40px;">
                                No hay diagnósticos registrados en este período
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Médicos Más Activos -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-md"></i> Médicos Más Activos
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($medicos_activos) > 0): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
                            <?php foreach ($medicos_activos as $index => $medico): ?>
                            <div class="ranking-item">
                                <div class="ranking-position"><?php echo $index + 1; ?></div>
                                <div class="ranking-info">
                                    <div style="font-weight: 600; color: var(--text-primary);">
                                        Dr(a). <?php echo htmlspecialchars($medico['medico']); ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-secondary);">
                                        <?php echo htmlspecialchars($medico['especialidad']); ?>
                                    </div>
                                </div>
                                <div class="ranking-value">
                                    <?php echo $medico['total_citas']; ?> consultas
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p style="text-align: center; color: var(--text-secondary); padding: 40px;">
                            No hay datos de médicos en este período
                        </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Resumen Final -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-body" style="background: var(--background-main);">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; font-size: 13px;">
                            <div>
                                <strong>Fecha de generación:</strong><br>
                                <?php echo date('d/m/Y H:i'); ?>
                            </div>
                            <div>
                                <strong>Generado por:</strong><br>
                                <?php echo htmlspecialchars(getUserName()); ?>
                            </div>
                            <div>
                                <strong>Período:</strong><br>
                                <?php 
                                $dias = (strtotime($fecha_fin) - strtotime($fecha_inicio)) / 86400 + 1;
                                echo round($dias) . ' días';
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Datos para gráficos
        const citasPorDia = <?php echo json_encode($citas_por_dia); ?>;
        const estadoCitas = <?php echo json_encode($stats_citas); ?>;
        const citasEspecialidad = <?php echo json_encode($citas_especialidad); ?>;

        // Gráfico de Citas por Día (Línea)
        const ctxDia = document.getElementById('chartCitasDia').getContext('2d');
        new Chart(ctxDia, {
            type: 'line',
            data: {
                labels: citasPorDia.map(item => {
                    const fecha = new Date(item.fecha + 'T00:00:00');
                    return fecha.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
                }),
                datasets: [{
                    label: 'Citas por Día',
                    data: citasPorDia.map(item => item.total),
                    borderColor: '#00D4D4',
                    backgroundColor: 'rgba(0, 212, 212, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Gráfico de Estado de Citas (Dona)
        const ctxEstado = document.getElementById('chartEstadoCitas').getContext('2d');
        new Chart(ctxEstado, {
            type: 'doughnut',
            data: {
                labels: ['Atendidas', 'Programadas', 'Confirmadas', 'Canceladas', 'Ausentes'],
                datasets: [{
                    data: [
                        estadoCitas.atendidas,
                        estadoCitas.programadas,
                        estadoCitas.confirmadas,
                        estadoCitas.canceladas,
                        estadoCitas.ausentes
                    ],
                    backgroundColor: [
                        '#10B981',
                        '#F59E0B',
                        '#00D4D4',
                        '#EF4444',
                        '#DC2626'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfico de Citas por Especialidad (Barras)
        const ctxEspecialidad = document.getElementById('chartEspecialidad').getContext('2d');
        new Chart(ctxEspecialidad, {
            type: 'bar',
            data: {
                labels: citasEspecialidad.map(item => item.nombre),
                datasets: [{
                    label: 'Citas',
                    data: citasEspecialidad.map(item => item.total),
                    backgroundColor: '#00D4D4'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        function aplicarRangoRapido(rango) {
            const hoy = new Date();
            let fechaInicio, fechaFin;
            
            switch(rango) {
                case 'hoy':
                    fechaInicio = fechaFin = hoy.toISOString().split('T')[0];
                    break;
                case 'semana':
                    const primerDia = new Date(hoy);
                    primerDia.setDate(hoy.getDate() - hoy.getDay());
                    fechaInicio = primerDia.toISOString().split('T')[0];
                    fechaFin = hoy.toISOString().split('T')[0];
                    break;
                case 'mes':
                    fechaInicio = new Date(hoy.getFullYear(), hoy.getMonth(), 1).toISOString().split('T')[0];
                    fechaFin = hoy.toISOString().split('T')[0];
                    break;
            }
            
            window.location.href = `reportes.php?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
        }

        function exportarPDF() {
            window.print();
        }
    </script>
</body>
</html>