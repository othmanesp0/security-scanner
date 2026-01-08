<?php
/**
 * Database Configuration
 * Educational Security Scanner Dashboard
 * 
 * WARNING: This tool is for educational purposes only.
 * Only use in controlled environments with proper authorization.
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'security_scanner';
    private $username = 'scanner_user';
    private $password = 'othmane';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>
