<?php
namespace App\Core;

class Logger {
    private string $channel;

    public function __construct(string $channel = 'app') {
        $this->channel = $channel;
    }

    public function debug(string $message, array $context = []): void {
        $this->log('DEBUG', $message, $context);
    }

    public function info(string $message, array $context = []): void {
        $this->log('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void {
        $this->log('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void {
        $this->log('ERROR', $message, $context);
    }

    private function log(string $level, string $message, array $context = []): void {
        $logDir = __DIR__ . '/../../logs/log_files/';
        if(!is_dir($logDir)) mkdir($logDir, 0777, true);
        $file = $logDir . date('Y-m-d') . ".log";
        $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        file_put_contents($file, "[".date('H:i:s')."][".$level."][".$this->channel."] $message $contextStr\n", FILE_APPEND);
    }
}
