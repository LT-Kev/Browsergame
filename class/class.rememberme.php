<?php
/**
 * Remember Me - Sichere "Angemeldet bleiben" Funktionalität
 * 
 * Verwendet Tokens statt direkter Credentials
 * - Token-basiert (nicht Passwort in Cookie!)
 * - Auto-Rotation bei jedem Login
 * - Verfallsdatum
 * - Device-Binding
 */
class RememberMe {
    private $db;
    private $cookieName = 'remember_token';
    private $cookieExpire = 2592000; // 30 Tage in Sekunden
    private $logger;
    
    public function __construct($database) {
        $this->db = $database;
        $this->logger = new Logger('auth');
    }
    
    /**
     * Erstellt ein Remember-Me Token
     * 
     * @param int $playerId Player ID
     * @return bool Success
     */
    public function createToken($playerId) {
        try {
            // Generiere sicheren Token
            $selector = bin2hex(random_bytes(16)); // 32 Zeichen
            $validator = bin2hex(random_bytes(32)); // 64 Zeichen
            $hashedValidator = hash('sha256', $validator);
            
            // Device Fingerprint für zusätzliche Sicherheit
            $deviceHash = $this->getDeviceHash();
            
            // Expires in 30 Tagen
            $expires = date('Y-m-d H:i:s', time() + $this->cookieExpire);
            
            // In Datenbank speichern
            $sql = "INSERT INTO remember_tokens (player_id, selector, hashed_validator, device_hash, expires_at, created_at)
                    VALUES (:player_id, :selector, :hashed_validator, :device_hash, :expires_at, NOW())";
            
            $result = $this->db->insert($sql, [
                ':player_id' => $playerId,
                ':selector' => $selector,
                ':hashed_validator' => $hashedValidator,
                ':device_hash' => $deviceHash,
                ':expires_at' => $expires
            ]);
            
            if(!$result) {
                return false;
            }
            
            // Cookie setzen (selector:validator)
            $cookieValue = $selector . ':' . $validator;
            
            $cookieOptions = [
                'expires' => time() + $this->cookieExpire,
                'path' => '/',
                'domain' => '', // Leer = current domain
                'secure' => !DEV_MODE, // Nur HTTPS in Production
                'httponly' => true, // Kein JavaScript-Zugriff
                'samesite' => 'Strict' // CSRF Protection
            ];
            
            setcookie($this->cookieName, $cookieValue, $cookieOptions);
            
            $this->logger->info("Remember token created", [
                'player_id' => $playerId,
                'selector' => $selector,
                'expires' => $expires
            ]);
            
            return true;
            
        } catch(Exception $e) {
            $this->logger->error("Failed to create remember token", [
                'error' => $e->getMessage(),
                'player_id' => $playerId
            ]);
            return false;
        }
    }
    
    /**
     * Validiert Remember-Me Token und loggt User ein
     * 
     * @return int|false Player ID bei Erfolg, false bei Fehler
     */
    public function validateToken() {
        // Prüfe ob Cookie existiert
        if(!isset($_COOKIE[$this->cookieName])) {
            return false;
        }
        
        $cookieValue = $_COOKIE[$this->cookieName];
        
        // Parse Cookie (selector:validator)
        $parts = explode(':', $cookieValue);
        if(count($parts) !== 2) {
            $this->deleteToken();
            return false;
        }
        
        [$selector, $validator] = $parts;
        
        // Hole Token aus DB
        $sql = "SELECT * FROM remember_tokens 
                WHERE selector = :selector 
                AND expires_at > NOW() 
                LIMIT 1";
        
        $token = $this->db->selectOne($sql, [':selector' => $selector]);
        
        if(!$token) {
            $this->logger->warning("Remember token not found or expired", [
                'selector' => $selector
            ]);
            $this->deleteToken();
            return false;
        }
        
        // Validiere Validator (timing-safe)
        $hashedValidator = hash('sha256', $validator);
        if(!hash_equals($token['hashed_validator'], $hashedValidator)) {
            $this->logger->securityEvent("Remember token validator mismatch - possible theft!", [
                'selector' => $selector,
                'player_id' => $token['player_id']
            ]);
            
            // WICHTIG: Bei Mismatch ALLE Tokens des Users löschen (Token-Theft!)
            $this->deleteAllTokensForPlayer($token['player_id']);
            $this->deleteToken();
            return false;
        }
        
        // Prüfe Device-Hash (optional, kann zu false-positives führen)
        $currentDeviceHash = $this->getDeviceHash();
        if($token['device_hash'] !== $currentDeviceHash) {
            $this->logger->warning("Remember token device mismatch", [
                'selector' => $selector,
                'player_id' => $token['player_id']
            ]);
            // Optional: Könnte hier abbrechen, aber User-Agent kann sich ändern
            // return false;
        }
        
        // Token ist valid! 
        $playerId = $token['player_id'];
        
        // Update last_used
        $sql = "UPDATE remember_tokens 
                SET last_used_at = NOW() 
                WHERE id = :id";
        $this->db->update($sql, [':id' => $token['id']]);
        
        // Token rotieren (neuen erstellen, alten löschen) für bessere Sicherheit
        $this->deleteTokenById($token['id']);
        $this->createToken($playerId);
        
        $this->logger->info("Remember token validated successfully", [
            'player_id' => $playerId,
            'selector' => $selector
        ]);
        
        return $playerId;
    }
    
    /**
     * Löscht aktuelles Remember-Token
     * 
     * @return void
     */
    public function deleteToken() {
        if(isset($_COOKIE[$this->cookieName])) {
            $cookieValue = $_COOKIE[$this->cookieName];
            $parts = explode(':', $cookieValue);
            
            if(count($parts) === 2) {
                $selector = $parts[0];
                
                // Aus DB löschen
                $sql = "DELETE FROM remember_tokens WHERE selector = :selector";
                $this->db->delete($sql, [':selector' => $selector]);
            }
        }
        
        // Cookie löschen
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
    
    /**
     * Löscht Token by ID
     * 
     * @param int $tokenId Token ID
     * @return void
     */
    private function deleteTokenById($tokenId) {
        $sql = "DELETE FROM remember_tokens WHERE id = :id";
        $this->db->delete($sql, [':id' => $tokenId]);
    }
    
    /**
     * Löscht ALLE Tokens eines Spielers (bei Sicherheitsvorfall)
     * 
     * @param int $playerId Player ID
     * @return void
     */
    public function deleteAllTokensForPlayer($playerId) {
        $sql = "DELETE FROM remember_tokens WHERE player_id = :player_id";
        $this->db->delete($sql, [':player_id' => $playerId]);
        
        $this->logger->warning("All remember tokens deleted for player", [
            'player_id' => $playerId
        ]);
    }
    
    /**
     * Bereinigt abgelaufene Tokens (Cronjob)
     * 
     * @return int Anzahl gelöschter Tokens
     */
    public function cleanExpiredTokens() {
        $sql = "DELETE FROM remember_tokens WHERE expires_at < NOW()";
        $deleted = $this->db->delete($sql);
        
        if($deleted > 0) {
            $this->logger->info("Cleaned expired remember tokens", [
                'count' => $deleted
            ]);
        }
        
        return $deleted;
    }
    
    /**
     * Erstellt Device-Hash für zusätzliche Sicherheit
     * 
     * @return string Device hash
     */
    private function getDeviceHash() {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            // IP-Adresse NICHT verwenden (ändert sich bei Mobilgeräten oft)
        ];
        
        return hash('sha256', implode('|', $components));
    }
    
    /**
     * Gibt alle aktiven Tokens eines Spielers zurück (für User-Interface)
     * 
     * @param int $playerId Player ID
     * @return array Array of tokens
     */
    public function getActiveTokens($playerId) {
        $sql = "SELECT id, created_at, last_used_at, expires_at, device_hash
                FROM remember_tokens 
                WHERE player_id = :player_id 
                AND expires_at > NOW()
                ORDER BY created_at DESC";
        
        $tokens = $this->db->select($sql, [':player_id' => $playerId]);
        
        // Device-Info anreichern
        foreach($tokens as &$token) {
            $token['is_current_device'] = ($token['device_hash'] === $this->getDeviceHash());
        }
        
        return $tokens;
    }
}