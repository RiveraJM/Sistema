<?php
/**
 * Script CRON para enviar recordatorios automáticos
 * Ejecutar cada hora: 0 * * * * php /ruta/cron/enviar-recordatorios.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/crear-notificacion.php';

$database = new Database();
$db = $database->getConnection();

$log = [];
$log[] = "=== Inicio de envío de recordatorios: " . date('Y-m-d H:i:s') . " ===\n";

try {
    // Buscar citas que serán en las próximas 24 horas
    $manana = date('Y-m-d', strtotime('+1 day'));
    $hoy = date('Y-m-d');
    
    $query = "SELECT 
              c.id as cita_id,
              c.fecha,
              c.hora,
              c.motivo,
              c.recordatorio_enviado,
              p.nombre as paciente_nombre,
              p.apellido as paciente_apellido,
              p.email as paciente_email,
              p.telefono as paciente_telefono,
              CONCAT(um.nombre, ' ', um.apellido) as medico_nombre,
              m.id as medico_id,
              m.usuario_id as medico_usuario_id,
              e.nombre as especialidad,
              m.consultorio
              FROM citas c
              INNER JOIN pacientes p ON c.paciente_id = p.id
              INNER JOIN medicos m ON c.medico_id = m.id
              INNER JOIN usuarios um ON m.usuario_id = um.id
              INNER JOIN especialidades e ON m.especialidad_id = e.id
              WHERE c.fecha = :manana
              AND c.estado IN ('programada', 'confirmada')
              AND (c.recordatorio_enviado IS NULL OR c.recordatorio_enviado = 0)
              ORDER BY c.hora";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':manana' => $manana]);
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $log[] = "Citas encontradas para mañana ($manana): " . count($citas) . "\n";
    
    foreach ($citas as $cita) {
        $hora_formato = date('H:i', strtotime($cita['hora']));
        $fecha_formato = date('d/m/Y', strtotime($cita['fecha']));
        
        $paciente_completo = $cita['paciente_nombre'] . ' ' . $cita['paciente_apellido'];
        
        // 1. NOTIFICAR AL MÉDICO (campana en el sistema)
        $notif_medico = crearNotificacion(
            $cita['medico_usuario_id'],
            'recordatorio',
            'Recordatorio: Cita mañana',
            "Tienes una cita con $paciente_completo mañana $fecha_formato a las $hora_formato",
            'citas.php?fecha=' . $cita['fecha']
        );
        
        if ($notif_medico) {
            $log[] = "✓ Notificación creada para médico: " . $cita['medico_nombre'] . "\n";
        }
        
        // 2. ENVIAR EMAIL AL PACIENTE
        if (!empty($cita['paciente_email'])) {
            $email_enviado = enviarEmailRecordatorio(
                $cita['paciente_email'],
                $paciente_completo,
                $cita['medico_nombre'],
                $cita['especialidad'],
                $fecha_formato,
                $hora_formato,
                $cita['consultorio'],
                $cita['motivo']
            );
            
            if ($email_enviado) {
                $log[] = "✓ Email enviado a: " . $cita['paciente_email'] . "\n";
            } else {
                $log[] = "✗ Error al enviar email a: " . $cita['paciente_email'] . "\n";
            }
        }
        
        // 3. ENVIAR WHATSAPP AL PACIENTE
        if (!empty($cita['paciente_telefono'])) {
            $whatsapp_enviado = enviarWhatsAppRecordatorio(
                $cita['paciente_telefono'],
                $paciente_completo,
                $cita['medico_nombre'],
                $cita['especialidad'],
                $fecha_formato,
                $hora_formato,
                $cita['consultorio']
            );
            
            if ($whatsapp_enviado) {
                $log[] = "✓ WhatsApp enviado a: " . $cita['paciente_telefono'] . "\n";
            } else {
                $log[] = "✗ Error al enviar WhatsApp a: " . $cita['paciente_telefono'] . "\n";
            }
        }
        
        // 4. MARCAR COMO RECORDATORIO ENVIADO
        $update = "UPDATE citas SET recordatorio_enviado = 1 WHERE id = :cita_id";
        $stmt_update = $db->prepare($update);
        $stmt_update->execute([':cita_id' => $cita['cita_id']]);
        
        $log[] = "--- Procesada cita ID: " . $cita['cita_id'] . " ---\n";
    }
    
    $log[] = "\n=== Finalizado exitosamente ===\n";
    
} catch (Exception $e) {
    $log[] = "ERROR: " . $e->getMessage() . "\n";
}

// Guardar log
$log_content = implode("", $log);
file_put_contents(__DIR__ . '/logs/recordatorios_' . date('Y-m-d') . '.log', $log_content, FILE_APPEND);

echo $log_content;

/**
 * Función para enviar email de recordatorio
 */
function enviarEmailRecordatorio($email, $paciente, $medico, $especialidad, $fecha, $hora, $consultorio, $motivo) {
    $asunto = "Recordatorio: Cita médica mañana";
    
    $mensaje = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #00D4D4, #00A0A0); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .info-box { background: white; padding: 20px; border-left: 4px solid #00D4D4; margin: 20px 0; border-radius: 5px; }
            .info-item { margin: 10px 0; }
            .label { font-weight: bold; color: #00D4D4; }
            .button { display: inline-block; padding: 12px 30px; background: #00D4D4; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
            .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🏥 Recordatorio de Cita</h1>
            </div>
            <div class='content'>
                <p>Estimado/a <strong>$paciente</strong>,</p>
                <p>Le recordamos que tiene una cita médica programada para mañana:</p>
                
                <div class='info-box'>
                    <div class='info-item'>
                        <span class='label'>📅 Fecha:</span> $fecha
                    </div>
                    <div class='info-item'>
                        <span class='label'>🕐 Hora:</span> $hora
                    </div>
                    <div class='info-item'>
                        <span class='label'>👨‍⚕️ Médico:</span> Dr(a). $medico
                    </div>
                    <div class='info-item'>
                        <span class='label'>🏥 Especialidad:</span> $especialidad
                    </div>
                    <div class='info-item'>
                        <span class='label'>🚪 Consultorio:</span> $consultorio
                    </div>
                    <div class='info-item'>
                        <span class='label'>📋 Motivo:</span> $motivo
                    </div>
                </div>
                
                <p><strong>Recomendaciones:</strong></p>
                <ul>
                    <li>Llegue 15 minutos antes de su cita</li>
                    <li>Traiga su DNI y carnet de seguro (si aplica)</li>
                    <li>Si tiene exámenes previos, tráigalos consigo</li>
                </ul>
                
                <p>Si necesita cancelar o reprogramar, por favor contáctenos con anticipación.</p>
                
                <div class='footer'>
                    <p>Este es un mensaje automático, por favor no responda a este email.</p>
                    <p>© " . date('Y') . " Sistema Clínico - Todos los derechos reservados</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Sistema Clínico <noreply@sistemaclinico.com>\r\n";
    
    return mail($email, $asunto, $mensaje, $headers);
}

/**
 * Función para enviar WhatsApp
 * Usando API de WhatsApp Business (Twilio, Meta, etc)
 */
function enviarWhatsAppRecordatorio($telefono, $paciente, $medico, $especialidad, $fecha, $hora, $consultorio) {
    // OPCIÓN 1: Twilio WhatsApp API
    // Configura tus credenciales de Twilio
    $account_sid = 'TU_ACCOUNT_SID';
    $auth_token = 'TU_AUTH_TOKEN';
    $twilio_whatsapp = 'whatsapp:+14155238886'; // Número de Twilio
    
    // Limpiar teléfono (agregar código de país si es necesario)
    $telefono_limpio = preg_replace('/[^0-9]/', '', $telefono);
    if (strlen($telefono_limpio) == 9) {
        $telefono_limpio = '51' . $telefono_limpio; // +51 para Perú
    }
    
    $mensaje = "🏥 *RECORDATORIO DE CITA*\n\n";
    $mensaje .= "Hola *$paciente*,\n\n";
    $mensaje .= "Le recordamos su cita médica:\n\n";
    $mensaje .= "📅 *Fecha:* $fecha\n";
    $mensaje .= "🕐 *Hora:* $hora\n";
    $mensaje .= "👨‍⚕️ *Médico:* Dr(a). $medico\n";
    $mensaje .= "🏥 *Especialidad:* $especialidad\n";
    $mensaje .= "🚪 *Consultorio:* $consultorio\n\n";
    $mensaje .= "Por favor llegue 15 minutos antes.\n\n";
    $mensaje .= "Si necesita cancelar, contáctenos lo antes posible.";
    
    // Llamar a API de Twilio
    $url = "https://api.twilio.com/2010-04-01/Accounts/$account_sid/Messages.json";
    
    $data = [
        'From' => $twilio_whatsapp,
        'To' => 'whatsapp:+' . $telefono_limpio,
        'Body' => $mensaje
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_USERPWD, "$account_sid:$auth_token");
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code == 201;
    
    // OPCIÓN 2: API gratuita usando WhatsApp Web (menos confiable)
    // return enviarWhatsAppGratis($telefono_limpio, $mensaje);
}

/**
 * Alternativa: Enviar WhatsApp gratis usando wa.me (abre WhatsApp Web)
 * Nota: Esto NO envía el mensaje automáticamente, solo genera el enlace
 */
function enviarWhatsAppGratis($telefono, $mensaje) {
    // Esta función solo genera el link, no envía automáticamente
    $mensaje_encoded = urlencode($mensaje);
    $link = "https://wa.me/$telefono?text=$mensaje_encoded";
    
    // Podrías guardar este link en la base de datos o enviarlo por email
    // para que el personal de la clínica lo use manualmente
    return false; // No es automático
}
?>