<?php
/**
 * Funciones de Sesión y Seguridad
 */

// Iniciar sesión solo si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verificar si el usuario está autenticado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Requerir login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../auth/login.php");
        exit();
    }
}

/**
 * Verificar permisos - CORREGIDO
 */
function hasPermission($permission) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $role = getUserRole();
    
    // Si no hay rol, denegar
    if (!$role) {
        return false;
    }
    
    // Definir permisos por rol
    $permissions = [
        'admin' => ['*'], // Admin tiene todos los permisos
        
        'medico' => [
            'ver_dashboard',
            'ver_citas',
            'crear_cita',
            'editar_cita',
            'cancelar_cita',
            'atender_cita',
            'ver_pacientes',
            'ver_historia_clinica',
            'crear_historia_clinica',
            'editar_historia_clinica',
            'ver_reportes'
        ],
        
        'recepcionista' => [
            'ver_dashboard',
            'ver_citas',
            'crear_cita',
            'editar_cita',
            'cancelar_cita',
            'confirmar_cita',
            'ver_pacientes',
            'crear_paciente',
            'editar_paciente',
            'ver_especialidades',
            'ver_medicos'
        ],
        
        'enfermero' => [
            'ver_dashboard',
            'ver_citas',
            'ver_pacientes',
            'ver_historia_clinica'
        ]
    ];
    
    // Admin tiene acceso a todo
    if ($role === 'admin') {
        return true;
    }
    
    // Verificar si el rol existe
    if (!isset($permissions[$role])) {
        return false;
    }
    
    // Verificar permiso específico
    return in_array($permission, $permissions[$role]);
}

/**
 * Obtener rol del usuario
 */
function getUserRole() {
    return $_SESSION['rol'] ?? null;
}

/**
 * Obtener ID del usuario
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Obtener nombre del usuario
 */
function getUserName() {
    return $_SESSION['nombre'] ?? 'Usuario';
}

/**
 * Limpiar datos de entrada
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Generar token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validar token CSRF
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Convertir fecha a "hace X tiempo"
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Hace unos segundos';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return 'Hace ' . $minutes . ' minuto' . ($minutes > 1 ? 's' : '');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return 'Hace ' . $hours . ' hora' . ($hours > 1 ? 's' : '');
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return 'Hace ' . $days . ' día' . ($days > 1 ? 's' : '');
    } else {
        return date('d/m/Y H:i', $time);
    }
}

/**
 * Crear una notificación
 */
function crearNotificacion($usuario_id, $tipo, $titulo, $mensaje, $enlace = null, $icono = 'fa-bell', $color = 'primary') {
    try {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        // Verificar si la tabla existe
        $check_table = $db->query("SHOW TABLES LIKE 'notificaciones'");
        
        if ($check_table->rowCount() > 0) {
            $query = "INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, enlace, icono, color, leida, fecha_creacion) 
                     VALUES (:usuario_id, :tipo, :titulo, :mensaje, :enlace, :icono, :color, 0, NOW())";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':usuario_id' => $usuario_id,
                ':tipo' => $tipo,
                ':titulo' => $titulo,
                ':mensaje' => $mensaje,
                ':enlace' => $enlace,
                ':icono' => $icono,
                ':color' => $color
            ]);
            
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error al crear notificación: " . $e->getMessage());
        return false;
    }
}

/**
 * Verificar si el usuario puede crear citas
 */
function puedeCrearCitas() {
    $role = getUserRole();
    return in_array($role, ['admin', 'recepcionista', 'medico']);
}

/**
 * Verificar si el usuario puede editar pacientes
 */
function puedeEditarPacientes() {
    $role = getUserRole();
    return in_array($role, ['admin', 'recepcionista']);
}

/**
 * Verificar si el usuario puede ver historias clínicas
 */
function puedeVerHistorias() {
    $role = getUserRole();
    return in_array($role, ['admin', 'medico', 'enfermero']);
}

/**
 * Verificar si el usuario puede editar historias clínicas
 */
function puedeEditarHistorias() {
    $role = getUserRole();
    return in_array($role, ['admin', 'medico']);
}

/**
 * Verificar si el usuario es administrador
 */
function esAdmin() {
    return getUserRole() === 'admin';
}

/**
 * Verificar si el usuario es médico
 */
function esMedico() {
    return getUserRole() === 'medico';
}

/**
 * Verificar si el usuario es recepcionista
 */
function esRecepcionista() {
    return getUserRole() === 'recepcionista';
}

/**
 * Formatear fecha a formato español
 */
function formatearFecha($fecha, $formato = 'd/m/Y') {
    if (!$fecha) return '';
    
    $timestamp = is_numeric($fecha) ? $fecha : strtotime($fecha);
    return date($formato, $timestamp);
}

/**
 * Formatear fecha y hora a formato español
 */
function formatearFechaHora($fecha) {
    return formatearFecha($fecha, 'd/m/Y H:i');
}

/**
 * Calcular edad a partir de fecha de nacimiento
 */
/**
 * Calcular edad a partir de fecha de nacimiento
 */
if (!function_exists('calcularEdad')) {
    function calcularEdad($fecha_nacimiento) {
        $nacimiento = new DateTime($fecha_nacimiento);
        $hoy = new DateTime();
        $edad = $hoy->diff($nacimiento);
        return $edad->y;
    }
}

/**
 * Validar formato de email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar DNI peruano (8 dígitos)
 */
function validarDNI($dni) {
    return preg_match('/^[0-9]{8}$/', $dni);
}

/**
 * Validar teléfono peruano
 */
function validarTelefono($telefono) {
    // Acepta formatos: 999999999, +51999999999, 01234567
    return preg_match('/^(\+51)?[0-9]{7,9}$/', $telefono);
}
?>