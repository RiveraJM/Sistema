<?php
class Database {
    // Configuración de conexión
    private $host = "db";               // Nombre del servicio MySQL en docker-compose
    private $port = "3306";             // Puerto del contenedor MySQL
    private $db_name = "sistema_clinico";
    private $username = "admin";
    private $password = "adminpass";
    private $conn;

    // Obtener conexión de base de datos
    public function getConnection() {
        $this->conn = null;
        try {
            // DSN con ssl-mode deshabilitado para evitar errores de certificado en desarrollo
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4;ssl-mode=DISABLED";
            
            // Opciones de PDO
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);

        } catch(PDOException $exception) {
            // Registrar error y mostrar mensaje genérico
            error_log("Error de conexión a la base de datos: " . $exception->getMessage());
            die("No se pudo conectar a la base de datos. Contacte al administrador.");
        }
        return $this->conn;
    }
}
?>
