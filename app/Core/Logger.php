<?php
// app/Core/Logger.php
namespace App\Core;

class Logger {
    private string $channel;
    private string $logDir;
    private array $buffer = [];
    private int $bufferSize = 10;
    
    const DEBUG = 0;
    const INFO = 1;
    const WARNING = 2;
    const ERROR = 3;
    const CRITICAL = 4;
    
    private int $minLogLevel;
    
    public function __construct(string $channel = 'app') {
        $this->channel = $channel;
        $this->logDir = __DIR__ . '/../../logs/';
        $this->minLogLevel = defined('LOG_LEVEL') ? $this->getLevelValue(LOG_LEVEL) : self::INFO;
        
        $this->ensureLogDirectory();
    }
    
    public function __destruct() {
        $this->flushBuffer();
    }
    
    private function ensureLogDirectory(): void {
        // Basis-Logverzeichnis
        if(!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
            file_put_contents($this->logDir . '.htaccess', "Require all denied\nDeny from all");
            file_put_contents($this->logDir . 'index.php', "<?php http_response_code(403); exit('Access Denied'); ?>");
        }

        // Channel-spezifisches Verzeichnis
        $channelDir = $this->logDir . $this->channel . '/';
        if(!is_dir($channelDir)) {
            mkdir($channelDir, 0755, true);
        }
    }
    
    private function getLevelValue(string $level): int {
        $levels = [
            'DEBUG' => self::DEBUG,
            'INFO' => self::INFO,
            'WARNING' => self::WARNING,
            'ERROR' => self::ERROR,
            'CRITICAL' => self::CRITICAL
        ];
        return $levels[strtoupper($level)] ?? self::INFO;
    }
    
    private function writeLog(int $level, string $levelName, string $message, array $context = []): void {
        if($level < $this->minLogLevel) {
            return;
        }
        
        $logMessage = $this->formatLogMessage($levelName, $message, $context);
        $this->buffer[] = $logMessage;
        
        if(count($this->buffer) >= $this->bufferSize || $level >= self::ERROR) {
            $this->flushBuffer();
        }
    }
    
    private function formatLogMessage(string $level, string $message, array $context = []): string {
        $timestamp = date('Y-m-d H:i:s');
        $microtime = sprintf('%06d', (microtime(true) - floor(microtime(true))) * 1000000);
        $ip = $this->getClientIp();
        $user = $this->getCurrentUser();
        $memory = $this->formatBytes(memory_get_usage(true));
        
        $logMessage = "[{$timestamp}.{$microtime}] [{$level}] ";
        $logMessage .= "[IP: {$ip}] [User: {$user}] [Memory: {$memory}] ";
        $logMessage .= "- {$message}";
        
        if(!empty($context)) {
            $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $logMessage .= " | Context: {$contextJson}";
        }
        
        return $logMessage . PHP_EOL;
    }
    
    private function flushBuffer(): void {
        if(empty($this->buffer)) {
            return;
        }

        try {
            $date = date('Y-m-d');
            $channelDir = $this->logDir . $this->channel . '/';
            $file = $channelDir . $this->channel . '_' . $date . '.log';
            $content = implode('', $this->buffer);

            file_put_contents($file, $content, FILE_APPEND | LOCK_EX);
            $this->buffer = [];
        } catch(\Exception $e) {
            error_log("Logger Exception: " . $e->getMessage());
        }
    }
    
    private function getClientIp(): string {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach($headers as $header) {
            if(!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
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
    
    private function getCurrentUser(): string {
        if(session_status() !== PHP_SESSION_ACTIVE) {
            return 'Guest';
        }
        return $_SESSION['username'] ?? 'Guest';
    }
    
    private function formatBytes(int $bytes, int $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen((string)$bytes) - 1) / 3);
        return sprintf("%.{$precision}f", $bytes / pow(1024, $factor)) . ' ' . ($units[$factor] ?? 'B');
    }
    
    public function debug(string $message, array $context = []): void {
        $this->writeLog(self::DEBUG, 'DEBUG', $message, $context);
    }
    
    public function info(string $message, array $context = []): void {
        $this->writeLog(self::INFO, 'INFO', $message, $context);
    }
    
    public function warning(string $message, array $context = []): void {
        $this->writeLog(self::WARNING, 'WARNING', $message, $context);
    }
    
    public function error(string $message, array $context = []): void {
        $this->writeLog(self::ERROR, 'ERROR', $message, $context);
    }
    
    public function critical(string $message, array $context = []): void {
        $this->writeLog(self::CRITICAL, 'CRITICAL', $message, $context);
        $this->flushBuffer();
    }
}