<?php
/**
 * Conexiune bază de date
 * Gestionează conexiunea la MySQL folosind MySQLi
 */

require_once 'config.php';

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->connection->connect_error) {
            die("Eroare conexiune la baza de date: " . $this->connection->connect_error);
        }
        
        $this->connection->set_charset("utf8mb4");
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Previne clonarea obiectului
    private function __clone() {}
    
    // Previne deserializarea obiectului
    public function __wakeup() {
        throw new Exception("Nu se poate deserializa singleton");
    }
}

// Funcție helper pentru a obține conexiunea
function getDB() {
    return Database::getInstance()->getConnection();
}
?>
