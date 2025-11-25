<?php
// tests/setup.php
try {
    $pdo = new PDO('mysql:host=db;dbname=sistema_clinico', 'admin', 'adminpass');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Conexión a la base de datos exitosa.\n";

    // Crear tabla usuarios
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            apellido VARCHAR(50) NOT NULL,
            nombre VARCHAR(50) NOT NULL
        );
    ");
    echo "Tabla 'usuarios' creada.\n";

    // Crear tabla pacientes
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pacientes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dni VARCHAR(20) NOT NULL UNIQUE,
            nombre VARCHAR(50) NOT NULL,
            apellido VARCHAR(50) NOT NULL,
            fecha_nacimiento DATE
        );
    ");
    echo "Tabla 'pacientes' creada.\n";

    // Crear tabla medicos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS medicos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL UNIQUE,
            especialidad VARCHAR(50),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        );
    ");
    echo "Tabla 'medicos' creada.\n";

    // Crear tabla citas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS citas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            paciente_id INT NOT NULL,
            medico_id INT NOT NULL,
            fecha DATETIME NOT NULL,
            motivo VARCHAR(255),
            FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
            FOREIGN KEY (medico_id) REFERENCES medicos(id)
        );
    ");
    echo "Tabla 'citas' creada.\n";

    // Insertar datos de prueba en usuarios
    $pdo->exec("
        INSERT INTO usuarios (username, password, apellido, nombre)
        VALUES 
        ('jhenrry', '" . password_hash('1234', PASSWORD_BCRYPT) . "', 'Rivera', 'Jhenrry'),
        ('maria', '" . password_hash('1234', PASSWORD_BCRYPT) . "', 'Lopez', 'Maria')
        ON DUPLICATE KEY UPDATE username=username;
    ");
    echo "Datos de prueba insertados en 'usuarios'.\n";

    // Insertar datos de prueba en pacientes
    $pdo->exec("
        INSERT INTO pacientes (dni, nombre, apellido, fecha_nacimiento)
        VALUES 
        ('12345678', 'Carlos', 'Perez', '1990-05-10'),
        ('87654321', 'Ana', 'Gomez', '1985-11-23')
        ON DUPLICATE KEY UPDATE dni=dni;
    ");
    echo "Datos de prueba insertados en 'pacientes'.\n";

    // Insertar datos de prueba en medicos
    $pdo->exec("
        INSERT INTO medicos (usuario_id, especialidad)
        VALUES 
        ((SELECT id FROM usuarios WHERE username='jhenrry'), 'Cardiologia')
        ON DUPLICATE KEY UPDATE usuario_id=usuario_id;
    ");
    echo "Datos de prueba insertados en 'medicos'.\n";

    echo "Base de datos lista para ejecutar tests.\n";

} catch (PDOException $e) {
    echo "Error de base de datos: " . $e->getMessage() . "\n";
    exit(1);
}
