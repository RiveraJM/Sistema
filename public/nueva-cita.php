<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar permisos
if (!puedeCrearCitas()) {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener especialidades
$query_especialidades = "SELECT * FROM especialidades ORDER BY nombre";
$stmt_especialidades = $db->prepare($query_especialidades);
$stmt_especialidades->execute();
$especialidades = $stmt_especialidades->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paciente_id = $_POST['paciente_id'] ?? null;
    $medico_id = $_POST['medico_id'] ?? null;
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $tipo_cita = $_POST['tipo_cita'] ?? 'consulta';
    $motivo = sanitizeInput($_POST['motivo'] ?? '');
    $observaciones = sanitizeInput($_POST['observaciones'] ?? '');
    
    // Validaciones
    if (empty($paciente_id) || empty($medico_id) || empty($fecha) || empty($hora)) {
        $error = 'Complete todos los campos obligatorios';
    } else {
        try {
            // Verificar disponibilidad
            $query_check = "SELECT COUNT(*) as total FROM citas 
                           WHERE medico_id = :medico_id 
                           AND fecha = :fecha 
                           AND hora = :hora 
                           AND estado NOT IN ('cancelada')";
            $stmt_check = $db->prepare($query_check);
            $stmt_check->execute([
                ':medico_id' => $medico_id,
                ':fecha' => $fecha,
                ':hora' => $hora
            ]);
            $existe = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($existe['total'] > 0) {
                $error = 'Ya existe una cita para ese médico en ese horario';
            } else {
                // Insertar cita
                $query = "INSERT INTO citas 
                         (paciente_id, medico_id, fecha, hora, tipo_cita, estado, motivo_consulta, observaciones) 
                         VALUES (:paciente_id, :medico_id, :fecha, :hora, :tipo_cita, 'programada', :motivo, :observaciones)";
                
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':paciente_id' => $paciente_id,
                    ':medico_id' => $medico_id,
                    ':fecha' => $fecha,
                    ':hora' => $hora,
                    ':tipo_cita' => $tipo_cita,
                    ':motivo' => $motivo,
                    ':observaciones' => $observaciones
                ]);
                
                $cita_id = $db->lastInsertId();
                
                $success = 'Cita registrada exitosamente';
                
                // Limpiar campos
                $_POST = [];
            }
        } catch (Exception $e) {
            $error = 'Error al registrar la cita: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Cita - Sistema Clínico</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        #dni_buscar:focus {
            border-color: #00D4D4;
            box-shadow: 0 0 0 3px rgba(0,212,212,0.1);
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        #resultado-paciente {
            animation: slideDown 0.3s ease;
        }
        
        .horario-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .horario-btn {
            padding: 10px;
            border: 2px solid var(--border-color);
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            font-weight: 500;
        }
        
        .horario-btn:hover {
            border-color: var(--primary-color);
            background: rgba(0, 212, 212, 0.1);
        }
        
        .horario-btn.selected {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .horario-btn.ocupado {
            background: #f5f5f5;
            color: #999;
            cursor: not-allowed;
            border-color: #e0e0e0;
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
                        <h1 class="page-title">Nueva Cita</h1>
                        <p class="page-subtitle">Registrar una nueva cita médica</p>
                    </div>
                    <a href="<?php echo esMedico() ? 'mis-citas.php' : 'citas.php'; ?>" class="btn btn-secondary">
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

                <form method="POST" id="formCita">
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user"></i> Datos del Paciente
                            </h3>
                        </div>
                        <div class="card-body">
                            <!-- BUSCADOR DE PACIENTE POR DNI -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-id-card"></i> DNI del Paciente *
                                </label>
                                <div style="display: flex; gap: 10px; align-items: start;">
                                    <div style="flex: 1;">
                                        <input type="text" 
                                               id="dni_buscar" 
                                               class="form-control" 
                                               placeholder="12345678"
                                               maxlength="8"
                                               pattern="[0-9]{8}">
                                        <input type="hidden" id="paciente_id" name="paciente_id" required>
                                    </div>
                                    <button type="button" onclick="buscarPaciente()" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                </div>
                                
                                <!-- NUEVO: Enlace para registrar paciente -->
                                <div style="margin-top: 8px;">
                                    <small style="color: #6B7280;">
                                        ¿Paciente nuevo? 
                                        <a href="nuevo-paciente.php" 
                                           style="color: #00D4D4; text-decoration: underline; font-weight: 500;"
                                           target="_blank">
                                            Registrar paciente
                                        </a>
                                    </small>
                                </div>
                            </div>

                            <!-- Resultado de la búsqueda -->
                            <div id="resultado-paciente" style="display: none; margin-top: 15px;">
                                <div style="padding: 20px; background: linear-gradient(135deg, rgba(0,212,212,0.1), rgba(16,185,129,0.1)); border-radius: 12px; border-left: 4px solid #00D4D4;">
                                    <div style="display: grid; grid-template-columns: 80px 1fr; gap: 15px; align-items: center;">
                                        <!-- Avatar -->
                                        <div style="width: 70px; height: 70px; border-radius: 50%; background: linear-gradient(135deg, #00D4D4, #00A0A0); display: flex; align-items: center; justify-content: center; color: white; font-size: 28px; font-weight: bold; box-shadow: 0 4px 12px rgba(0,212,212,0.3);">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        
                                        <!-- Datos del paciente -->
                                        <div>
                                            <h3 style="margin: 0 0 8px 0; color: var(--primary-color); font-size: 20px;">
                                                <i class="fas fa-check-circle" style="color: #10B981;"></i>
                                                <span id="paciente-nombre"></span>
                                            </h3>
                                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; font-size: 14px; color: var(--text-secondary);">
                                                <div>
                                                    <i class="fas fa-id-card"></i> DNI: <strong id="paciente-dni"></strong>
                                                </div>
                                                <div>
                                                    <i class="fas fa-birthday-cake"></i> Edad: <strong id="paciente-edad"></strong>
                                                </div>
                                                <div>
                                                    <i class="fas fa-venus-mars"></i> Sexo: <strong id="paciente-sexo"></strong>
                                                </div>
                                                <div>
                                                    <i class="fas fa-phone"></i> Tel: <strong id="paciente-telefono"></strong>
                                                </div>
                                                <div>
                                                    <i class="fas fa-envelope"></i> Email: <strong id="paciente-email"></strong>
                                                </div>
                                                <div id="paciente-seguro-container">
                                                    <i class="fas fa-shield-alt"></i> Seguro: <strong id="paciente-seguro"></strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Botón para cambiar paciente -->
                                    <button type="button" onclick="limpiarPaciente()" class="btn btn-sm btn-secondary" style="margin-top: 15px;">
                                        <i class="fas fa-times"></i> Buscar otro paciente
                                    </button>
                                </div>
                            </div>

                            <!-- Mensaje de error -->
                            <div id="error-paciente" style="display: none; margin-top: 10px; padding: 15px; background: rgba(239,68,68,0.1); border-left: 4px solid #EF4444; border-radius: 8px; color: #DC2626;">
                                <i class="fas fa-exclamation-circle"></i>
                                <span id="error-mensaje"></span>
                                <div style="margin-top: 10px;">
                                    <a href="nuevo-paciente.php" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="fas fa-user-plus"></i> Registrar nuevo paciente
                                    </a>
                                </div>
                            </div>

                            <!-- Loading -->
                            <div id="loading-paciente" style="display: none; margin-top: 10px; text-align: center; padding: 20px; color: var(--text-secondary);">
                                <i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i>
                                <p style="margin: 10px 0 0 0;">Buscando paciente...</p>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user-md"></i> Datos de la Cita
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-stethoscope"></i> Especialidad *
                                    </label>
                                    <select id="especialidad_id" class="form-control" required onchange="cargarMedicos()">
                                        <option value="">Seleccione especialidad</option>
                                        <?php foreach ($especialidades as $esp): ?>
                                        <option value="<?php echo $esp['id']; ?>">
                                            <?php echo htmlspecialchars($esp['nombre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user-md"></i> Médico *
                                    </label>
                                    <select id="medico_id" name="medico_id" class="form-control" required disabled onchange="verificarDisponibilidad()">
                                        <option value="">Primero seleccione especialidad</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-calendar"></i> Fecha *
                                    </label>
                                    <input type="date" 
                                           id="fecha" 
                                           name="fecha" 
                                           class="form-control" 
                                           min="<?php echo date('Y-m-d'); ?>"
                                           required
                                           onchange="verificarDisponibilidad()">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-clock"></i> Hora *
                                    </label>
                                    <input type="time" 
                                           id="hora" 
                                           name="hora" 
                                           class="form-control" 
                                           required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-list"></i> Tipo de Cita
                                    </label>
                                    <select name="tipo_cita" class="form-control">
                                        <option value="consulta">Consulta</option>
                                        <option value="control">Control</option>
                                        <option value="emergencia">Emergencia</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Horarios disponibles -->
                            <div id="horarios-disponibles" style="display: none; margin-top: 20px;">
                                <label class="form-label">
                                    <i class="fas fa-clock"></i> Horarios Disponibles
                                </label>
                                <div id="horarios-grid" class="horario-grid">
                                    <!-- Los horarios se cargarán aquí -->
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-comment"></i> Motivo de Consulta
                                </label>
                                <textarea name="motivo" class="form-control" rows="3" placeholder="Describa el motivo de la consulta..."></textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-notes-medical"></i> Observaciones
                                </label>
                                <textarea name="observaciones" class="form-control" rows="2" placeholder="Observaciones adicionales (opcional)"></textarea>
                            </div>
                        </div>
                        
                        <div class="card-body" style="border-top: 1px solid var(--border-color); display: flex; gap: 10px; justify-content: flex-end;">
                            <a href="<?php echo esMedico() ? 'mis-citas.php' : 'citas.php'; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cita
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // ==========================================
        // FUNCIONES DEL BUSCADOR DE PACIENTES
        // ==========================================
        
        function buscarPaciente() {
            const dni = document.getElementById('dni_buscar').value.trim();
            
            if (dni === '') {
                mostrarError('Por favor ingrese un DNI');
                return;
            }
            
            if (dni.length !== 8 || !/^\d+$/.test(dni)) {
                mostrarError('El DNI debe tener 8 dígitos numéricos');
                return;
            }
            
            document.getElementById('resultado-paciente').style.display = 'none';
            document.getElementById('error-paciente').style.display = 'none';
            document.getElementById('loading-paciente').style.display = 'block';
            
            fetch(`api/buscar-paciente.php?dni=${dni}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading-paciente').style.display = 'none';
                    
                    if (data.success) {
                        mostrarPaciente(data.paciente);
                    } else {
                        mostrarError(data.message || 'Paciente no encontrado');
                    }
                })
                .catch(error => {
                    document.getElementById('loading-paciente').style.display = 'none';
                    mostrarError('Error al buscar el paciente. Intente nuevamente.');
                    console.error('Error:', error);
                });
        }

        function mostrarPaciente(paciente) {
            document.getElementById('paciente_id').value = paciente.id;
            document.getElementById('paciente-nombre').textContent = `${paciente.nombre} ${paciente.apellido}`;
            document.getElementById('paciente-dni').textContent = paciente.dni;
            document.getElementById('paciente-edad').textContent = paciente.edad + ' años';
            document.getElementById('paciente-sexo').textContent = paciente.sexo === 'M' ? 'Masculino' : 'Femenino';
            document.getElementById('paciente-telefono').textContent = paciente.telefono || 'No registrado';
            document.getElementById('paciente-email').textContent = paciente.email || 'No registrado';
            
            if (paciente.seguro) {
                document.getElementById('paciente-seguro').textContent = paciente.seguro;
                document.getElementById('paciente-seguro-container').style.display = 'block';
            } else {
                document.getElementById('paciente-seguro-container').style.display = 'none';
            }
            
            document.getElementById('resultado-paciente').style.display = 'block';
            document.getElementById('error-paciente').style.display = 'none';
            document.getElementById('dni_buscar').disabled = true;
        }

        function mostrarError(mensaje) {
            document.getElementById('error-mensaje').textContent = mensaje;
            document.getElementById('error-paciente').style.display = 'block';
            document.getElementById('resultado-paciente').style.display = 'none';
            document.getElementById('paciente_id').value = '';
        }

        function limpiarPaciente() {
            document.getElementById('dni_buscar').value = '';
            document.getElementById('dni_buscar').disabled = false;
            document.getElementById('dni_buscar').focus();
            document.getElementById('paciente_id').value = '';
            document.getElementById('resultado-paciente').style.display = 'none';
            document.getElementById('error-paciente').style.display = 'none';
        }

        // Enter para buscar
        document.getElementById('dni_buscar')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarPaciente();
            }
        });

        // Solo números
        document.getElementById('dni_buscar')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // ==========================================
        // FUNCIONES DE MÉDICOS Y HORARIOS
        // ==========================================

        function cargarMedicos() {
            const especialidadId = document.getElementById('especialidad_id').value;
            const medicoSelect = document.getElementById('medico_id');
            
            medicoSelect.innerHTML = '<option value="">Cargando...</option>';
            medicoSelect.disabled = true;
            
            if (!especialidadId) {
                medicoSelect.innerHTML = '<option value="">Primero seleccione especialidad</option>';
                return;
            }
            
            fetch(`api/obtener-medicos.php?especialidad_id=${especialidadId}`)
                .then(response => response.json())
                .then(data => {
                    medicoSelect.innerHTML = '<option value="">Seleccione médico</option>';
                    
                    if (data.success && data.medicos.length > 0) {
                        data.medicos.forEach(medico => {
                            const option = document.createElement('option');
                            option.value = medico.id;
                            option.textContent = `Dr(a). ${medico.nombre} - ${medico.consultorio}`;
                            medicoSelect.appendChild(option);
                        });
                        medicoSelect.disabled = false;
                    } else {
                        medicoSelect.innerHTML = '<option value="">No hay médicos disponibles</option>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    medicoSelect.innerHTML = '<option value="">Error al cargar médicos</option>';
                });
        }

        function verificarDisponibilidad() {
            const medicoId = document.getElementById('medico_id').value;
            const fecha = document.getElementById('fecha').value;
            
            if (!medicoId || !fecha) return;
            
            fetch(`api/verificar-disponibilidad.php?medico_id=${medicoId}&fecha=${fecha}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarHorariosDisponibles(data.horarios);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function mostrarHorariosDisponibles(horarios) {
            const container = document.getElementById('horarios-disponibles');
            const grid = document.getElementById('horarios-grid');
            
            grid.innerHTML = '';
            
            horarios.forEach(horario => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'horario-btn' + (horario.disponible ? '' : ' ocupado');
                btn.textContent = horario.hora;
                btn.disabled = !horario.disponible;
                
                if (horario.disponible) {
                    btn.onclick = function() {
                        document.querySelectorAll('.horario-btn').forEach(b => b.classList.remove('selected'));
                        this.classList.add('selected');
                        document.getElementById('hora').value = horario.hora;
                    };
                }
                
                grid.appendChild(btn);
            });
            
            container.style.display = 'block';
        }

        // Validar formulario
        document.getElementById('formCita').addEventListener('submit', function(e) {
            const pacienteId = document.getElementById('paciente_id').value;
            
            if (!pacienteId) {
                e.preventDefault();
                alert('Por favor busque y seleccione un paciente primero');
                document.getElementById('dni_buscar').focus();
                return false;
            }
        });
    </script>
</body>
</html>