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
        // Load database config from PHP file (reliable on shared hosting like InfinityFree)
        // Falls back to environment variables or XAMPP defaults for local development
        $configFile = __DIR__ . '/../../dbconfig.php';
        if (file_exists($configFile)) {
            require_once $configFile;
        }

        $this->host     = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'localhost');
        $this->dbName   = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'driveease_db');
        $this->username = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'root');
        $this->password = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
        $this->port     = defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ?: '3306');
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
