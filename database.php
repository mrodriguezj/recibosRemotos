<?php
// config/Database.php

// Usamos dirname(__DIR__) para construir la ruta relativa de forma segura
// Asume que env_loader.php está en la misma carpeta que Database.php (config/)
//require_once __DIR__ . '/../env_loader.php'; // Ajustado para que apunte a la raíz si env_loader.php está allí

require_once __DIR__ . '/env_loader.php';

class Database {
    private $conn;

    // Métodos para obtener las credenciales de forma encapsulada (opcional, pero buena práctica)
    private function getDbHost() { return getenv('DB_HOST'); }
    private function getDbName() { return getenv('DB_NAME'); }
    private function getDbUser() { return getenv('DB_USER'); }
    private function getDbPass() { return getenv('DB_PASS'); }
    private function getDbCharset() { return getenv('DB_CHARSET') ?: 'utf8mb4'; } // Default charset

    public function getConnection() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host=" . $this->getDbHost() . ";dbname=" . $this->getDbName() . ";charset=" . $this->getDbCharset();
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];

                $this->conn = new PDO($dsn, $this->getDbUser(), $this->getDbPass(), $options);
            } catch (PDOException $exception) {
                // MODIFICACIÓN CRÍTICA AQUÍ:
                // Lanzar la excepción en lugar de hacer die().
                // Esto permite que el script que usa Database.php maneje el error.
                // En producción, aquí se LOGGEARÍA el error completo y se lanzaría una excepción más genérica.
                throw new Exception("Error de conexión a la base de datos: " . $exception->getMessage());
            }
        }
        return $this->conn;
    }

    // Opcional: Cerrar la conexión explícitamente (PDO lo hace al destruir el objeto)
    public function closeConnection() {
        $this->conn = null;
    }
}