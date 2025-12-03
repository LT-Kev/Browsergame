<?php

// ============================================================================
// app/Services/RememberMeService.php
// ============================================================================
namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

class RememberMeService {
    private Database $db;
    private string $cookieName = 'remember_token';
    private int $cookieExpire = 2592000; // 30 Tage
    private Logger $logger;
    
    public function __construct(Database $db) {
        $this->db = $db;
        $this->logger = new Logger('auth');
    }
    
    public function createToken(int $playerId): bool {
        try {
            $selector = bin2hex(random_bytes(16));
            $validator = bin2hex(random_bytes(32));
            $hashedValidator = hash('sha256', $validator);
            $deviceHash = $this->getDeviceHash();
            $expires = date('Y-m-d H:i:s', time() + $this->cookieExpire);
            
            $sql = "INSERT INTO remember_tokens (player_id, selector, hashed_validator, device_hash, expires_at, created_at)
                    VALUES (:player_id, :selector, :hashed_validator, :device_hash, :expires_at, NOW())";
            
            $result = $this->db->insert($sql, [
                ':player_id' => $playerId,
                ':selector' => $selector,
                ':hashed_validator' => $hashedValidator,
                ':device_hash' => $deviceHash,
                ':expires_at' => $expires
            ]);
            
            if(!$result) return false;
            
            $cookieValue = $selector . ':' . $validator;
            $cookieOptions = [
                'expires' => time() + $this->cookieExpire,
                'path' => '/',
                'domain' => '',
                'secure' => !DEV_MODE,
                'httponly' => true,
                'samesite' => 'Strict'
            ];
            
            setcookie($this->cookieName, $cookieValue, $cookieOptions);
            $this->logger->info("Remember token created", ['player_id' => $playerId]);
            return true;
            
        } catch(\Exception $e) {
            $this->logger->error("Failed to create remember token", ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    public function validateToken(): int|false {
        if(!isset($_COOKIE[$this->cookieName])) return false;
        
        $cookieValue = $_COOKIE[$this->cookieName];
        $parts = explode(':', $cookieValue);
        if(count($parts) !== 2) {
            $this->deleteToken();
            return false;
        }
        
        [$selector, $validator] = $parts;
        
        $sql = "SELECT * FROM remember_tokens 
                WHERE selector = :selector AND expires_at > NOW() LIMIT 1";
        $token = $this->db->selectOne($sql, [':selector' => $selector]);
        
        if(!$token) {
            $this->deleteToken();
            return false;
        }
        
        $hashedValidator = hash('sha256', $validator);
        if(!hash_equals($token['hashed_validator'], $hashedValidator)) {
            $this->deleteAllTokensForPlayer($token['player_id']);
            $this->deleteToken();
            return false;
        }
        
        $playerId = $token['player_id'];
        
        $sql = "UPDATE remember_tokens SET last_used_at = NOW() WHERE id = :id";
        $this->db->update($sql, [':id' => $token['id']]);
        
        $this->deleteTokenById($token['id']);
        $this->createToken($playerId);
        
        return $playerId;
    }
    
    public function deleteToken(): void {
        if(isset($_COOKIE[$this->cookieName])) {
            $cookieValue = $_COOKIE[$this->cookieName];
            $parts = explode(':', $cookieValue);
            
            if(count($parts) === 2) {
                $selector = $parts[0];
                $sql = "DELETE FROM remember_tokens WHERE selector = :selector";
                $this->db->delete($sql, [':selector' => $selector]);
            }
        }
        
        setcookie($this->cookieName, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => !DEV_MODE,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        
        unset($_COOKIE[$this->cookieName]);
    }
    
    private function deleteTokenById(int $tokenId): void {
        $sql = "DELETE FROM remember_tokens WHERE id = :id";
        $this->db->delete($sql, [':id' => $tokenId]);
    }
    
    public function deleteAllTokensForPlayer(int $playerId): void {
        $sql = "DELETE FROM remember_tokens WHERE player_id = :player_id";
        $this->db->delete($sql, [':player_id' => $playerId]);
    }
    
    private function getDeviceHash(): string {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''
        ];
        return hash('sha256', implode('|', $components));
    }
    
    public function getActiveTokens(int $playerId): array {
        $sql = "SELECT id, created_at, last_used_at, expires_at, device_hash
                FROM remember_tokens 
                WHERE player_id = :player_id AND expires_at > NOW()
                ORDER BY created_at DESC";
        
        $tokens = $this->db->select($sql, [':player_id' => $playerId]);
        
        foreach($tokens as &$token) {
            $token['is_current_device'] = ($token['device_hash'] === $this->getDeviceHash());
        }
        
        return $tokens;
    }
}