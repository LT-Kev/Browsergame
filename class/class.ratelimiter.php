// Neue Datei: class/class.ratelimiter.php
<?php
class RateLimiter {
    private $db;
    
    public function checkLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
        $sql = "SELECT COUNT(*) as attempts FROM login_attempts 
                WHERE identifier = :identifier 
                AND attempt_time > DATE_SUB(NOW(), INTERVAL :window SECOND)";
        
        $result = $this->db->selectOne($sql, [
            ':identifier' => $identifier,
            ':window' => $timeWindow
        ]);
        
        return $result['attempts'] < $maxAttempts;
    }
    
    public function recordAttempt($identifier) {
        $sql = "INSERT INTO login_attempts (identifier, attempt_time) VALUES (:identifier, NOW())";
        $this->db->insert($sql, [':identifier' => $identifier]);
    }
}
?>