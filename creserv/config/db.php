<?php

class Database {
  
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "church";
    private $charset = "utf8mb4";
    
    protected $conn;

    private $debug = false;
  
    public function __construct($config = null) {
        if ($config) {
            $this->host = $config['host'] ?? $this->host;
            $this->username = $config['username'] ?? $this->username;
            $this->password = $config['password'] ?? $this->password;
            $this->dbname = $config['dbname'] ?? $this->dbname;
            $this->charset = $config['charset'] ?? $this->charset;
            $this->debug = $config['debug'] ?? $this->debug;
        }
    }
    
 
    public function connect() {
     
        if ($this->conn !== null) {
            return $this->conn;
        }
        
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            if ($this->debug) {
                error_log("Database connection established successfully");
            }
            
            return $this->conn;
            
        } catch (PDOException $e) {
           
            error_log("Database Connection Error: " . $e->getMessage());
            
        
            if ($this->debug) {
                die("Database connection failed: " . $e->getMessage());
            } else {
                die("Database connection failed. Please contact the administrator.");
            }
        }
    }
    
    
    public function testConnection() {
        try {
            $this->connect();
            $stmt = $this->conn->query("SELECT 1");
            return $stmt !== false;
        } catch (PDOException $e) {
            error_log("Database test failed: " . $e->getMessage());
            return false;
        }
    }
    
  
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    

    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
  
    public function commit() {
        return $this->conn->commit();
    }
    

    public function rollback() {
        return $this->conn->rollBack();
    }
    
   
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage());
            if ($this->debug) {
                throw $e;
            }
            return false;
        }
    }
    
    
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Execute error: " . $e->getMessage());
            if ($this->debug) {
                throw $e;
            }
            return false;
        }
    }
    
    
    public function fetchOne($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Fetch error: " . $e->getMessage());
            return false;
        }
    }
    
    
    public function fetchColumn($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Fetch column error: " . $e->getMessage());
            return false;
        }
    }
    
    
    public function checkSchema() {
        $requiredTables = ['users', 'event', 'change_requests', 'notifications'];
        $status = [];
        
        try {
            foreach ($requiredTables as $table) {
                $sql = "SHOW TABLES LIKE ?";
                $result = $this->fetchColumn($sql, [$table]);
                $status[$table] = ($result !== false);
            }
            return $status;
        } catch (PDOException $e) {
            error_log("Schema check error: " . $e->getMessage());
            return [];
        }
    }
    
 
    public function close() {
        $this->conn = null;
    }
    

    public function __destruct() {
        $this->close();
    }
}

?>
