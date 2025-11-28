<?php
//class/class.database.php

class Database {
    private $host;
    private $dbname;
    private $user;
    private $pass;
    private $charset;
    private $pdo;
    private $error;
    public $instance;
    
    private function __construct() {
        $this->host = DB_HOST;
        $this->dbname = DB_NAME;
        $this->user = DB_USER;
        $this->pass = DB_PASS;
        $this->charset = DB_CHARSET;
        
        $this->connect();

        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true, // ⚡ Performance-Boost
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
        );
    }

    // Singleton Pattern für Connection Reuse
    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
        );
        
        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            die("Datenbankverbindung fehlgeschlagen: " . $this->error);
        }
    }
    
    public function select($sql, $params = array()) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function selectOne($sql, $params = array()) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function insert($sql, $params = array()) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this->pdo->lastInsertId();
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function update($sql, $params = array()) {
        try {
            // Validierung gegen UPDATE ohne WHERE
            if(stripos($sql, 'UPDATE') !== false && stripos($sql, 'WHERE') === false) {
                throw new Exception('UPDATE ohne WHERE-Klausel nicht erlaubt');
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function delete($sql, $params = array()) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function query($sql, $params = array()) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function getError() {
        return $this->error;
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollback();
    }
    private function __clone() {}
    private function __wakeup() {}
}
?>