<?php
class Logger {
    private $logDir;
    private $logFile;
    
    public function __construct($logType = 'general') {
        $this->logDir = __DIR__ . '/../logs/';
        
        // Prüfen ob Logs-Ordner existiert
        if(!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        
        // Log-Datei nach Typ und Datum
        $date = date('Y-m-d');
        $this->logFile = $this->logDir . $logType . '_' . $date . '.log';
    }
    
    // Allgemeine Log-Methode
    private function writeLog($level, $message, $context = array()) {
        $timestamp = date('Y-m-d H:i:s');
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'CLI';
        $user = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
        
        // Context zu String konvertieren wenn vorhanden
        $contextStr = '';
        if(!empty($context)) {
            $contextStr = ' | Context: ' . json_encode($context);
        }
        
        $logMessage = "[$timestamp] [$level] [IP: $ip] [User: $user] - $message$contextStr" . PHP_EOL;
        
        // In Datei schreiben
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    // Log-Level Methoden
    public function info($message, $context = array()) {
        $this->writeLog('INFO', $message, $context);
    }
    
    public function warning($message, $context = array()) {
        $this->writeLog('WARNING', $message, $context);
    }
    
    public function error($message, $context = array()) {
        $this->writeLog('ERROR', $message, $context);
    }
    
    public function debug($message, $context = array()) {
        $this->writeLog('DEBUG', $message, $context);
    }
    
    public function critical($message, $context = array()) {
        $this->writeLog('CRITICAL', $message, $context);
    }
    
    // Spezielle Logs
    public function loginAttempt($username, $success, $ip = null) {
        $ip = $ip ?: $_SERVER['REMOTE_ADDR'];
        $status = $success ? 'SUCCESS' : 'FAILED';
        $this->writeLog('LOGIN', "Login attempt for user '$username' - $status from IP: $ip");
    }
    
    public function playerAction($action, $details = array()) {
        $this->info("Player action: $action", $details);
    }
    
    public function resourceChange($playerId, $resourceType, $amount, $reason = '') {
        $this->info("Resource change for player $playerId: $resourceType $amount ($reason)");
    }
    
    public function buildingUpgrade($playerId, $buildingName, $level, $status) {
        $this->info("Building upgrade for player $playerId: $buildingName to level $level - $status");
    }
    
    public function securityEvent($event, $details = array()) {
        $this->warning("Security event: $event", $details);
    }
    
    public function databaseError($query, $error) {
        $this->error("Database error", array('query' => $query, 'error' => $error));
    }
}
?>