<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private $host;
    private $dbName;
    private $username;
    private $password;
    private $port;
    private $conn;

    public function __construct() {
        // Read database configuration from environment variables (useful for cloud hosting like Render/Railway)
        // Fall back to standard XAMPP defaults if environment variables are not set
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->dbName = getenv('DB_NAME') ?: 'driveease_db';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
        $this->port = getenv('DB_PORT') ?: '3306';
    }

    public function getConnection(): ?PDO {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->dbName . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            // Set error mode to exception to catch SQL Injection attempts or logic errors
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $exception) {
            // Write connection failure details to logs (lecturer rubric checklist)
            error_log("Database connection error: " . $exception->getMessage());
            throw new PDOException("Database connection failed. Please check credentials and server availability.");
        }

        return $this->conn;
    }
}
