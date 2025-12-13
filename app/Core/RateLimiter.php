<?php
// ============================================================================
// app/Core/RateLimiter.php - UNIVERSAL RATE LIMITER
// ============================================================================

namespace App\Core;

class RateLimiter {
    private Database $db;
    private Logger $logger;
    
    // Verschiedene Limit-Typen
    const TYPE_LOGIN = 'login';
    const TYPE_API = 'api';
    const TYPE_REGISTRATION = 'registration';
    const TYPE_PASSWORD_RESET = 'password_reset';
    
    public function __construct(Database $db) {
        $this->db = $db;
        $this->logger = new Logger('ratelimit');
    }
    
    /**
     * Prüft ob Rate-Limit erreicht ist
     * 
     * @param string $identifier Identifier (z.B. IP-Adresse, User-ID)
     * @param string $type Limit-Typ (login, api, etc.)
     * @param int $maxAttempts Max Versuche
     * @param int $timeWindow Zeitfenster in Sekunden
     * @return bool True wenn unter Limit, False wenn Limit erreicht
     */
    public function checkLimit(
        string $identifier, 
        string $type = self::TYPE_API,
        int $maxAttempts = 100, 
        int $timeWindow = 60
    ): bool {
        // Für Session-basierte Limits (z.B. API)
        if($type === self::TYPE_API) {
            return $this->checkSessionLimit($identifier, $maxAttempts, $timeWindow);
        }
        
        // Für DB-basierte Limits (z.B. Login)
        return $this->checkDatabaseLimit($identifier, $type, $maxAttempts, $timeWindow);
    }
    
    /**
     * Session-basierte Rate-Limiting (schnell, temporär)
     */
    private function checkSessionLimit(string $identifier, int $maxAttempts, int $timeWindow): bool {
        if(session_status() !== PHP_SESSION_ACTIVE) {
            return true; // Keine Session = kein Limit
        }
        
        $key = "rate_limit:$identifier";
        $current = $_SESSION[$key] ?? ['count' => 0, 'reset' => time() + $timeWindow];
        
        // Reset wenn Zeitfenster abgelaufen
        if (time() > $current['reset']) {
            $current = ['count' => 0, 'reset' => time() + $timeWindow];
        }
        
        $current['count']++;
        $_SESSION[$key] = $current;
        
        $underLimit = $current['count'] <= $maxAttempts;
        
        if(!$underLimit) {
            $this->logger->warning("Session rate limit exceeded", [
                'identifier' => $identifier,
                'attempts' => $current['count'],
                'max' => $maxAttempts
            ]);
        }
        
        return $underLimit;
    }
    
    /**
     * Datenbank-basierte Rate-Limiting (persistent, für kritische Aktionen)
     */
    private function checkDatabaseLimit(
        string $identifier, 
        string $type,
        int $maxAttempts, 
        int $timeWindow
    ): bool {
        $sql = "SELECT COUNT(*) as attempts FROM rate_limits 
                WHERE identifier = :identifier 
                AND type = :type
                AND attempt_time > DATE_SUB(NOW(), INTERVAL :window SECOND)";
        
        $result = $this->db->selectOne($sql, [
            ':identifier' => $identifier,
            ':type' => $type,
            ':window' => $timeWindow
        ]);
        
        $attempts = $result['attempts'] ?? 0;
        $underLimit = $attempts < $maxAttempts;
        
        if(!$underLimit) {
            $this->logger->warning("Database rate limit exceeded", [
                'identifier' => $identifier,
                'type' => $type,
                'attempts' => $attempts,
                'max' => $maxAttempts
            ]);
        }
        
        return $underLimit;
    }
    
    /**
     * Zeichnet einen Versuch auf (nur für DB-Limits)
     * 
     * @param string $identifier Identifier
     * @param string $type Limit-Typ
     * @return void
     */
    public function recordAttempt(string $identifier, string $type = self::TYPE_API): void {
        // Nur für DB-basierte Limits aufzeichnen
        if($type === self::TYPE_API) {
            return; // Session-Limits zeichnen sich selbst auf
        }
        
        $sql = "INSERT INTO rate_limits (identifier, type, attempt_time) 
                VALUES (:identifier, :type, NOW())";
        
        $this->db->insert($sql, [
            ':identifier' => $identifier,
            ':type' => $type
        ]);
    }
    
    /**
     * Löscht alte Versuche (für Cleanup)
     * 
     * @param int $olderThanSeconds Ältere als X Sekunden
     * @return int Anzahl gelöschter Einträge
     */
    public function cleanupOldAttempts(int $olderThanSeconds = 3600): int {
        $sql = "DELETE FROM rate_limits 
                WHERE attempt_time < DATE_SUB(NOW(), INTERVAL :seconds SECOND)";
        
        return $this->db->delete($sql, [':seconds' => $olderThanSeconds]);
    }
    
    /**
     * Reset für einen Identifier
     * 
     * @param string $identifier Identifier
     * @param string $type Optional: nur bestimmten Typ resetten
     * @return void
     */
    public function resetIdentifier(string $identifier, ?string $type = null): void {
        // Session-Limit reset
        if($type === self::TYPE_API || $type === null) {
            $key = "rate_limit:$identifier";
            unset($_SESSION[$key]);
        }
        
        // DB-Limit reset
        if($type !== self::TYPE_API) {
            if($type === null) {
                $sql = "DELETE FROM rate_limits WHERE identifier = :identifier";
                $this->db->delete($sql, [':identifier' => $identifier]);
            } else {
                $sql = "DELETE FROM rate_limits WHERE identifier = :identifier AND type = :type";
                $this->db->delete($sql, [
                    ':identifier' => $identifier,
                    ':type' => $type
                ]);
            }
        }
    }
    
    /**
     * Gibt verbleibende Versuche zurück
     * 
     * @param string $identifier
     * @param string $type
     * @param int $maxAttempts
     * @param int $timeWindow
     * @return array ['remaining' => int, 'reset_at' => timestamp]
     */
    public function getRemainingAttempts(
        string $identifier,
        string $type = self::TYPE_API,
        int $maxAttempts = 100,
        int $timeWindow = 60
    ): array {
        if($type === self::TYPE_API) {
            $key = "rate_limit:$identifier";
            $current = $_SESSION[$key] ?? ['count' => 0, 'reset' => time() + $timeWindow];
            
            return [
                'remaining' => max(0, $maxAttempts - $current['count']),
                'reset_at' => $current['reset'],
                'used' => $current['count']
            ];
        }
        
        // DB-basiert
        $sql = "SELECT COUNT(*) as attempts FROM rate_limits 
                WHERE identifier = :identifier 
                AND type = :type
                AND attempt_time > DATE_SUB(NOW(), INTERVAL :window SECOND)";
        
        $result = $this->db->selectOne($sql, [
            ':identifier' => $identifier,
            ':type' => $type,
            ':window' => $timeWindow
        ]);
        
        $attempts = $result['attempts'] ?? 0;
        
        return [
            'remaining' => max(0, $maxAttempts - $attempts),
            'reset_at' => time() + $timeWindow,
            'used' => $attempts
        ];
    }
    
    /**
     * Helper: IP-Adresse des Clients holen
     */
    public static function getClientIp(): string {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach($headers as $header) {
            if(!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Bei Proxy-Liste ersten Wert nehmen
                if(strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                if(filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return 'CLI';
    }
}