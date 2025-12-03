<?php
namespace App\Core;

use PDO;
use PDOException;

class Database {
    private PDO $pdo;
    private static $instance = null;
    
    private function __construct(array $config) {
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];
        
        try {
            $this->pdo = new PDO($dsn, $config['user'], $config['password'], $options);
        } catch(PDOException $e) {
            die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
        }
    }
    
    public static function getInstance(array $config = null): self {
        if(self::$instance === null) {
            if($config === null) {
                throw new \Exception("Config erforderlich für erste Initialisierung");
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    public function select(string $sql, array $params = []): array {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new \Exception("Select-Fehler: " . $e->getMessage());
        }
    }
    
    public function selectOne(string $sql, array $params = []): ?array {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch(PDOException $e) {
            throw new \Exception("SelectOne-Fehler: " . $e->getMessage());
        }
    }
    
    public function insert(string $sql, array $params = []): mixed {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this->pdo->lastInsertId();
        } catch(PDOException $e) {
            throw new \Exception("Insert-Fehler: " . $e->getMessage());
        }
    }
    
    public function update(string $sql, array $params = []): int {
        try {
            // Sicherheit: UPDATE ohne WHERE verbieten
            if(stripos($sql, 'UPDATE') !== false && stripos($sql, 'WHERE') === false) {
                throw new \Exception('UPDATE ohne WHERE-Klausel nicht erlaubt!');
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch(PDOException $e) {
            throw new \Exception("Update-Fehler: " . $e->getMessage());
        }
    }
    
    public function delete(string $sql, array $params = []): int {
        try {
            // Sicherheit: DELETE ohne WHERE verbieten
            if(stripos($sql, 'DELETE') !== false && stripos($sql, 'WHERE') === false) {
                throw new \Exception('DELETE ohne WHERE-Klausel nicht erlaubt!');
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch(PDOException $e) {
            throw new \Exception("Delete-Fehler: " . $e->getMessage());
        }
    }
    
    public function query(string $sql, array $params = []): \PDOStatement {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            throw new \Exception("Query-Fehler: " . $e->getMessage());
        }
    }
    
    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }
    
    public function commit(): bool {
        return $this->pdo->commit();
    }
    
    public function rollback(): bool {
        return $this->pdo->rollback();
    }
    
    public function getPDO(): PDO {
        return $this->pdo;
    }
    
    // Prevent cloning
    private function __clone() {}
    public function __wakeup() {}
}