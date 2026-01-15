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

// Funcție pentru conexiune PDO (pentru funcții SEO și moderne)
function getPDO() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Eroare conexiune PDO: " . $e->getMessage());
        }
    }
    return $pdo;
}
