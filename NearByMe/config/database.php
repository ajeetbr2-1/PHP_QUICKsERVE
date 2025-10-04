<?php
/**
 * Near By Me - Database Configuration
 * Service Marketplace Platform
 */

class Database {
    private $host = "localhost";
    private $db_name = "nearbyme_db";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            
            if ($this->conn->connect_error) {
                die("Connection failed: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8mb4");
            
        } catch(Exception $e) {
            echo "Connection error: " . $e->getMessage();
        }

        return $this->conn;
    }

    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
