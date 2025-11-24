<?php
if (!isset($_SESSION)) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$user_role = getUserRole();
$user_name = getUserName();
$user_apellido = $_SESSION['apellido'] ?? '';
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fas fa-heartbeat"></i>
        </div>
        <span class="sidebar-title">CLINICA RODRIGUEZ</span>
    </div>
    
    <div class="user-profile">
        <div class="user-avatar">
            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
        </div>
        <div class="user-info">
            <h4><?php echo htmlspecialchars($user_name . ' ' . $user_apellido); ?></h4>
            <p><?php echo ucfirst($user_role); ?></p>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <!-- SECCIÓN PRINCIPAL -->
        <div class="nav-section">
            <div class="nav-section-title">MAIN</div>
            <a href="dashboard.php" class="nav-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
        </div>
        
        <!-- SECCIÓN GESTIÓN -->
        <div class="nav-section">
            <div class="nav-section-title">GESTIÓN</div>
            
            <!-- Citas - CORREGIDO para médicos -->
            <?php if (hasPermission('ver_citas')): ?>
            <a href="<?php echo esMedico() ? 'mis-citas.php' : 'citas.php'; ?>" 
               class="nav-item <?php echo ($current_page === 'citas.php' || $current_page === 'mis-citas.php') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Citas</span>
            </a>
            <?php endif; ?>
            
            <!-- Pacientes -->
            <?php if (hasPermission('ver_pacientes')): ?>
            <a href="pacientes.php" class="nav-item <?php echo $current_page === 'pacientes.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Pacientes</span>
            </a>
            <?php endif; ?>
            
            <!-- Médicos (Admin y Recepcionista) -->
            <?php if (esAdmin() || esRecepcionista()): ?>
            <a href="medicos.php" class="nav-item <?php echo $current_page === 'medicos.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-md"></i>
                <span>Médicos</span>
            </a>
            <?php endif; ?>
            
            <!-- Consultas (Solo Médicos) -->
            <?php if (esMedico()): ?>
            <a href="consultas.php" class="nav-item <?php echo $current_page === 'consultas.php' ? 'active' : ''; ?>">
                <i class="fas fa-stethoscope"></i>
                <span>Consultas</span>
            </a>
            <?php endif; ?>
            
            <!-- Historia Clínica -->
            <?php if (puedeVerHistorias()): ?>
            <a href="historia-clinica.php" class="nav-item <?php echo $current_page === 'historia-clinica.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-medical"></i>
                <span>Historia Clínica</span>
            </a>
            <?php endif; ?>
        </div>
        
        <!-- SECCIÓN ADMINISTRACIÓN (Solo Admin) -->
        <?php if (esAdmin()): ?>
        <div class="nav-section">
            <div class="nav-section-title">ADMINISTRACIÓN</div>
            
            <a href="usuarios.php" class="nav-item <?php echo $current_page === 'usuarios.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-cog"></i>
                <span>Usuarios</span>
            </a>
            
            <a href="especialidades.php" class="nav-item <?php echo $current_page === 'especialidades.php' ? 'active' : ''; ?>">
                <i class="fas fa-briefcase-medical"></i>
                <span>Especialidades</span>
            </a>
            
            <a href="seguros.php" class="nav-item <?php echo $current_page === 'seguros.php' ? 'active' : ''; ?>">
                <i class="fas fa-shield-alt"></i>
                <span>Seguros</span>
            </a>
        </div>
        <?php endif; ?>
        
            <!-- SECCIÓN REPORTES (Solo Admin) -->
            <?php if (esAdmin()): ?>
            <div class="nav-section">
                <div class="nav-section-title">REPORTES</div>
                
                <a href="reportes.php" class="nav-item <?php echo $current_page === 'reportes.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reportes</span>
                </a>
            </div>
            <?php endif; ?>
        
        <!-- SECCIÓN EXTRA -->
        <div class="nav-section">
            <div class="nav-section-title">EXTRA</div>
            
            <a href="perfil.php" class="nav-item <?php echo $current_page === 'perfil.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>Mi Perfil</span>
            </a>
            
            <!-- CERRAR SESIÓN - CORREGIDO -->
            <a href="logout.php" class="nav-item" onclick="return confirm('¿Seguro que deseas cerrar sesión?');">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </nav>
</aside>

<script>
// Toggle sidebar en móvil
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
}

// Cerrar sidebar al hacer clic fuera en móvil
document.addEventListener('click', function(e) {
    if (window.innerWidth <= 768) {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.querySelector('.sidebar-toggle');
        
        if (sidebar && !sidebar.contains(e.target) && toggleBtn && !toggleBtn.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    }
});
</script>