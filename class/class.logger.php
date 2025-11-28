<<<<<<< HEAD
<?php
/**
 * Logger Klasse - Verbesserte Version mit Buffer & Performance-Optimierung
 * 
 * Features:
 * - Buffer für bessere Performance
 * - Log-Rotation (automatisches Löschen alter Logs)
 * - Verschiedene Log-Level mit Farbcodes
 * - Kontext-Unterstützung (Arrays werden als JSON geloggt)
 * - Thread-safe File Writing
 * - Memory-efficient
 */
class Logger {
    private $logDir;
    private $logFile;
    private $logType;
    private $buffer = [];
    private $bufferSize = 10; // Anzahl der Logs bevor Buffer geleert wird
    private $maxLogSize = 10485760; // 10MB - danach wird rotiert
    private $maxLogAge = 30; // Tage - danach werden Logs gelöscht
    
    // Log-Levels mit Priority
    const DEBUG = 0;
    const INFO = 1;
    const WARNING = 2;
    const ERROR = 3;
    const CRITICAL = 4;
    
    private $minLogLevel;
    
    /**
     * Constructor
     * 
     * @param string $logType Type of log (general, auth, building, etc.)
     * @param int $bufferSize Number of logs before flushing buffer
     */
    public function __construct($logType = 'general', $bufferSize = 10) {
        $this->logType = $logType;
        $this->bufferSize = $bufferSize;
        $this->logDir = __DIR__ . '/../logs/';
        
        // Min Log Level aus Config oder Default INFO
        $this->minLogLevel = defined('LOG_LEVEL') ? $this->getLevelValue(LOG_LEVEL) : self::INFO;
        
        // Logs-Ordner erstellen falls nicht vorhanden
        $this->ensureLogDirectory();
        
        // Log-Datei nach Typ und Datum
        $date = date('Y-m-d');
        $this->logFile = $this->logDir . $logType . '_' . $date . '.log';
        
        // Log-Rotation durchführen
        $this->rotateLogsIfNeeded();
        
        // Alte Logs bereinigen
        $this->cleanOldLogs();
    }
    
    /**
     * Destructor - Leert Buffer beim Beenden
     */
    public function __destruct() {
        $this->flushBuffer();
    }
    
    /**
     * Stellt sicher dass Log-Verzeichnis existiert
     */
    private function ensureLogDirectory() {
        if(!is_dir($this->logDir)) {
            if(!mkdir($this->logDir, 0755, true)) {
                error_log("Failed to create log directory: {$this->logDir}");
                return;
            }
            
            // .htaccess zum Schutz erstellen
            $htaccess = $this->logDir . '.htaccess';
            if(!file_exists($htaccess)) {
                file_put_contents($htaccess, "Require all denied\nDeny from all");
            }
            
            // index.php als zusätzlicher Schutz
            $index = $this->logDir . 'index.php';
            if(!file_exists($index)) {
                file_put_contents($index, "<?php http_response_code(403); exit('Access Denied'); ?>");
            }
        }
    }
    
    /**
     * Konvertiert Log-Level String zu Wert
     */
    private function getLevelValue($level) {
        $levels = [
            'DEBUG' => self::DEBUG,
            'INFO' => self::INFO,
            'WARNING' => self::WARNING,
            'ERROR' => self::ERROR,
            'CRITICAL' => self::CRITICAL
        ];
        
        return $levels[strtoupper($level)] ?? self::INFO;
    }
    
    /**
     * Haupt-Log-Methode (private)
     * 
     * @param int $level Log-Level
     * @param string $levelName Name des Levels
     * @param string $message Log-Nachricht
     * @param array $context Zusätzliche Kontext-Daten
     */
    private function writeLog($level, $levelName, $message, $context = []) {
        // Prüfe ob Level hoch genug ist
        if($level < $this->minLogLevel) {
            return;
        }
        
        // Formatiere Log-Message
        $logMessage = $this->formatLogMessage($levelName, $message, $context);
        
        // Füge zum Buffer hinzu
        $this->buffer[] = $logMessage;
        
        // Buffer leeren wenn voll oder bei kritischen Fehlern sofort
        if(count($this->buffer) >= $this->bufferSize || $level >= self::ERROR) {
            $this->flushBuffer();
        }
    }
    
    /**
     * Formatiert eine Log-Nachricht
     * 
     * @param string $level Log-Level
     * @param string $message Nachricht
     * @param array $context Kontext-Daten
     * @return string Formatierte Log-Nachricht
     */
    private function formatLogMessage($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $microtime = sprintf('%06d', (microtime(true) - floor(microtime(true))) * 1000000);
        $ip = $this->getClientIp();
        $user = $this->getCurrentUser();
        $memory = $this->formatBytes(memory_get_usage(true));
        
        // Basis-Message
        $logMessage = "[{$timestamp}.{$microtime}] [{$level}] ";
        $logMessage .= "[IP: {$ip}] [User: {$user}] [Memory: {$memory}] ";
        $logMessage .= "- {$message}";
        
        // Context anhängen wenn vorhanden
        if(!empty($context)) {
            $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $logMessage .= " | Context: {$contextJson}";
        }
        
        return $logMessage . PHP_EOL;
    }
    
    /**
     * Leert den Buffer und schreibt in Datei
     */
    private function flushBuffer() {
        if(empty($this->buffer)) {
            return;
        }
        
        try {
            // Thread-safe schreiben mit LOCK_EX
            $content = implode('', $this->buffer);
            
            if(file_put_contents($this->logFile, $content, FILE_APPEND | LOCK_EX) === false) {
                error_log("Failed to write to log file: {$this->logFile}");
            }
            
            // Buffer leeren
            $this->buffer = [];
            
        } catch(Exception $e) {
            error_log("Logger exception: " . $e->getMessage());
        }
    }
    
    /**
     * Log-Rotation wenn Datei zu groß wird
     */
    private function rotateLogsIfNeeded() {
        if(!file_exists($this->logFile)) {
            return;
        }
        
        $fileSize = filesize($this->logFile);
        
        if($fileSize > $this->maxLogSize) {
            $timestamp = date('Y-m-d_His');
            $rotatedFile = str_replace('.log', "_{$timestamp}.log", $this->logFile);
            
            rename($this->logFile, $rotatedFile);
            
            // Optional: Komprimieren
            if(function_exists('gzencode')) {
                $content = file_get_contents($rotatedFile);
                $compressed = gzencode($content, 9);
                file_put_contents($rotatedFile . '.gz', $compressed);
                unlink($rotatedFile);
            }
        }
    }
    
    /**
     * Löscht alte Log-Dateien
     */
    private function cleanOldLogs() {
        $files = glob($this->logDir . '*.log*');
        $cutoffTime = time() - ($this->maxLogAge * 86400);
        
        foreach($files as $file) {
            if(filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }
    
    /**
     * Holt die Client-IP (auch hinter Proxies)
     */
    private function getClientIp() {
        $headers = [
            'HTTP_CF_CONNECTING_IP',  // CloudFlare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach($headers as $header) {
            if(!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Bei X-Forwarded-For kann es mehrere IPs geben
                if(strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // Validiere IP
                if(filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return 'CLI';
    }
    
    /**
     * Holt aktuellen Usernamen
     */
    private function getCurrentUser() {
        if(session_status() !== PHP_SESSION_ACTIVE) {
            return 'Guest';
        }
        
        return $_SESSION['username'] ?? 'Guest';
    }
    
    /**
     * Formatiert Bytes in lesbare Form
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . $units[$i];
    }
    
    // ============================================================================
    // PUBLIC LOG-METHODEN
    // ============================================================================
    
    /**
     * Debug-Log (nur in Entwicklung sichtbar)
     */
    public function debug($message, $context = []) {
        $this->writeLog(self::DEBUG, 'DEBUG', $message, $context);
    }
    
    /**
     * Info-Log (normale Informationen)
     */
    public function info($message, $context = []) {
        $this->writeLog(self::INFO, 'INFO', $message, $context);
    }
    
    /**
     * Warning-Log (Warnungen, keine Fehler)
     */
    public function warning($message, $context = []) {
        $this->writeLog(self::WARNING, 'WARNING', $message, $context);
    }
    
    /**
     * Error-Log (Fehler die behandelt werden können)
     */
    public function error($message, $context = []) {
        $this->writeLog(self::ERROR, 'ERROR', $message, $context);
    }
    
    /**
     * Critical-Log (Kritische Fehler, sofortiges Handeln nötig)
     */
    public function critical($message, $context = []) {
        $this->writeLog(self::CRITICAL, 'CRITICAL', $message, $context);
        $this->flushBuffer(); // Sofort schreiben bei critical
    }
    
    // ============================================================================
    // SPEZIELLE LOG-METHODEN
    // ============================================================================
    
    /**
     * Login-Versuch loggen
     */
    public function loginAttempt($username, $success, $ip = null) {
        $ip = $ip ?: $this->getClientIp();
        $status = $success ? 'SUCCESS' : 'FAILED';
        
        $this->writeLog(
            $success ? self::INFO : self::WARNING,
            'LOGIN',
            "Login attempt for user '{$username}' - {$status}",
            ['username' => $username, 'ip' => $ip, 'success' => $success]
        );
    }
    
    /**
     * Spieler-Aktion loggen
     */
    public function playerAction($action, $details = []) {
        $this->info("Player action: {$action}", $details);
    }
    
    /**
     * Ressourcen-Änderung loggen
     */
    public function resourceChange($playerId, $resourceType, $amount, $reason = '') {
        $this->info("Resource change", [
            'player_id' => $playerId,
            'resource' => $resourceType,
            'amount' => $amount,
            'reason' => $reason
        ]);
    }
    
    /**
     * Gebäude-Upgrade loggen
     */
    public function buildingUpgrade($playerId, $buildingName, $level, $status) {
        $this->info("Building upgrade", [
            'player_id' => $playerId,
            'building' => $buildingName,
            'level' => $level,
            'status' => $status
        ]);
    }
    
    /**
     * Sicherheits-Event loggen
     */
    public function securityEvent($event, $details = []) {
        $this->warning("Security event: {$event}", array_merge($details, [
            'ip' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]));
    }
    
    /**
     * Datenbank-Fehler loggen
     */
    public function databaseError($query, $error) {
        $this->error("Database error", [
            'query' => $query,
            'error' => $error,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);
    }
    
    /**
     * API-Request loggen
     */
    public function apiRequest($endpoint, $method, $responseCode, $duration = null) {
        $this->info("API Request", [
            'endpoint' => $endpoint,
            'method' => $method,
            'response_code' => $responseCode,
            'duration_ms' => $duration
        ]);
    }
    
    /**
     * Performance-Warnung loggen
     */
    public function performanceWarning($operation, $duration, $threshold) {
        $this->warning("Performance warning: {$operation} took {$duration}ms (threshold: {$threshold}ms)", [
            'operation' => $operation,
            'duration' => $duration,
            'threshold' => $threshold
        ]);
    }
    
    // ============================================================================
    // UTILITY-METHODEN
    // ============================================================================
    
    /**
     * Erzwingt sofortiges Schreiben des Buffers
     */
    public function forceFlush() {
        $this->flushBuffer();
    }
    
    /**
     * Setzt Buffer-Size (für Performance-Tuning)
     */
    public function setBufferSize($size) {
        $this->bufferSize = max(1, (int)$size);
    }
    
    /**
     * Gibt Log-Statistiken zurück
     */
    public function getStats() {
        $files = glob($this->logDir . $this->logType . '_*.log*');
        $totalSize = 0;
        $fileCount = count($files);
        
        foreach($files as $file) {
            $totalSize += filesize($file);
        }
        
        return [
            'type' => $this->logType,
            'file_count' => $fileCount,
            'total_size' => $this->formatBytes($totalSize),
            'current_file' => basename($this->logFile),
            'buffer_size' => count($this->buffer)
        ];
    }
    
    /**
     * Liest die letzten N Zeilen aus dem aktuellen Log
     */
    public function tail($lines = 100) {
        if(!file_exists($this->logFile)) {
            return [];
        }
        
        $file = new SplFileObject($this->logFile);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        $startLine = max(0, $totalLines - $lines);
        $result = [];
        
        $file->seek($startLine);
        while(!$file->eof()) {
            $line = trim($file->current());
            if($line !== '') {
                $result[] = $line;
            }
            $file->next();
        }
        
        return $result;
    }
}
=======
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
>>>>>>> 971ab47689bd561bd08c6e4d77cea7f516414d66
?>