<?php
class Database {
    private $host = "localhost";
    private $db_name = "spa_system";  
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->db_name
            );
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            // Set charset to utf8mb4
            if (!$this->conn->set_charset("utf8mb4")) {
                throw new Exception("Error setting charset utf8mb4: " . $this->conn->error);
            }
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }

        return $this->conn;
    }
}
