<?php
/**
 * Configuración de Base de Datos
 */

define('DB_HOST', '5.183.11.230');
define('DB_NAME', 'meritumquest');
define('DB_USER', 'root');
define('DB_PASS', 'Platino5.');
define('DB_CHARSET', 'utf8mb4');

/**
 * Conexión a la base de datos
 */
class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false, // No usar conexiones persistentes para mejor control
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}

// Función helper para obtener la conexión
function getDB() {
    return Database::getInstance()->getConnection();
}

