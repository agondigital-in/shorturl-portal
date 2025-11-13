<?php
// db_connection.php - Database connection utility

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        // Load environment variables
        $env = parse_ini_file('.env');
        
        // Database configuration
        $host = $env['DB_HOST'];
        $port = $env['DB_PORT'];
        $dbname = $env['DB_DATABASE'];
        $username = $env['DB_USERNAME'];
        $password = $env['DB_PASSWORD'];
        
        try {
            $this->connection = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
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
}
?>