<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Clínico</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-container">
        <div class="login-left">
            <div class="login-brand">
                <div class="brand-logo">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <h1>Sistema Clínico</h1>
                <p>Gestión integral de pacientes y consultas médicas</p>
            </div>
            
            <div class="login-features">
                <div class="feature-item">
                    <i class="fas fa-calendar-check"></i>
                    <div>
                        <h3>Gestión de Citas</h3>
                        <p>Sistema inteligente de agendamiento</p>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-user-md"></i>
                    <div>
                        <h3>Historia Clínica</h3>
                        <p>Registro electrónico completo</p>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-chart-line"></i>
                    <div>
                        <h3>Reportes</h3>
                        <p>Análisis y estadísticas en tiempo real</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="login-right">
            <div class="login-form-container">
                <h2>Iniciar Sesión</h2>
                <p class="login-subtitle">Ingresa tus credenciales para acceder</p>
                
                <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Credenciales incorrectas. Por favor, intenta nuevamente.</span>
                </div>
                <?php endif; ?>
                
                <form action="login_process.php" method="POST" class="login-form">
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-user"></i> Usuario
                        </label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Ingresa tu usuario" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Contraseña
                        </label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" class="form-control" placeholder="Ingresa tu contraseña" required>
                            <button type="button" class="toggle-password" onclick="togglePassword()">
                                <i class="fas fa-eye" id="eye-icon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember">
                            <span>Recordarme</span>
                        </label>
                        <a href="#" class="forgot-password">¿Olvidaste tu contraseña?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
}
window.addEventListener('load', function() {
    document.querySelector('.login-container').classList.add('fade-in');
});
</script>
</body>
</html>
