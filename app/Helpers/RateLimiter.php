<?php
// ============================================================================
// app/Helpers/RateLimiter.php
// ============================================================================

namespace App\Helpers;

use App\Core\Database;

class RateLimiter {
    private Database $db;
    
    public function __construct(Database $db) {
        $this->db = $db;
    }
    
    /**
     * Prüft ob Rate-Limit erreicht ist
     * 
     * @param string $identifier Identifier (z.B. IP-Adresse)
     * @param int $maxAttempts Max Versuche
     * @param int $timeWindow Zeitfenster in Sekunden
     * @return bool True wenn unter Limit, False wenn Limit erreicht
     */
    public function checkLimit(string $identifier, int $maxAttempts = 5, int $timeWindow = 300): bool {
        $sql = "SELECT COUNT(*) as attempts FROM login_attempts 
                WHERE identifier = :identifier 
                AND attempt_time > DATE_SUB(NOW(), INTERVAL :window SECOND)";
        
        $result = $this->db->selectOne($sql, [
            ':identifier' => $identifier,
            ':window' => $timeWindow
        ]);
        
        return ($result['attempts'] ?? 0) < $maxAttempts;
    }
    
    /**
     * Zeichnet einen Versuch auf
     * 
     * @param string $identifier Identifier
     * @return void
     */
    public function recordAttempt(string $identifier): void {
        $sql = "INSERT INTO login_attempts (identifier, attempt_time) VALUES (:identifier, NOW())";
        $this->db->insert($sql, [':identifier' => $identifier]);
    }
    
    /**
     * Löscht alte Versuche (für Cleanup)
     * 
     * @param int $olderThanSeconds Ältere als X Sekunden
     * @return int Anzahl gelöschter Einträge
     */
    public function cleanupOldAttempts(int $olderThanSeconds = 3600): int {
        $sql = "DELETE FROM login_attempts 
                WHERE attempt_time < DATE_SUB(NOW(), INTERVAL :seconds SECOND)";
        
        return $this->db->delete($sql, [':seconds' => $olderThanSeconds]);
    }
    
    /**
     * Reset für einen Identifier
     * 
     * @param string $identifier Identifier
     * @return void
     */
    public function resetIdentifier(string $identifier): void {
        $sql = "DELETE FROM login_attempts WHERE identifier = :identifier";
        $this->db->delete($sql, [':identifier' => $identifier]);
    }
}