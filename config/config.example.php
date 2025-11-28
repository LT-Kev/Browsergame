<?php
// Datenbank Konfiguration
define('DB_HOST', ''); //Change this
define('DB_NAME', ''); //Change this
define('DB_USER', ''); //Change this
define('DB_PASS', ''); //Change this
define('DB_CHARSET', 'utf8mb4');

// Weitere Konfigurationen
define('SITE_URL', ''); //Change this
define('SITE_NAME', ''); //Change this

// Zeitzone
date_default_timezone_set('Europe/Berlin');
define('TIMEZONE', 'Europe/Berlin');

// Session Lifetime (in Sekunden)
define('SESSION_LIFETIME', 7200); // 2 Stunden

// CSRF Token Lifetime (in Sekunden)
define('CSRF_TOKEN_LIFETIME', 3600); // 1 Stunde

// Rate Limiting
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_MAX_ATTEMPTS', 5);
define('RATE_LIMIT_TIME_WINDOW', 300); // 5 Minuten

// Performance Monitoring
define('PERFORMANCE_MONITORING', true);
define('SLOW_QUERY_THRESHOLD', 1000); // ms

// Logging Einstellungen
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR, CRITICAL
define('LOG_DIR', __DIR__ . '/../logs/');
define('LOG_BUFFER_SIZE', 10); // Anzahl Logs vor flush
define('LOG_MAX_SIZE', 10485760); // 10MB
define('LOG_MAX_AGE', 30); // Tage

// Entwicklungsmodus
define('DEV_MODE', true); // Auf false setzen in Produktion
define('DEBUG_MODE', true); // Debug-Modus für detaillierte Logs

// Error Reporting
if(DEV_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// PHP Error Log
ini_set('log_errors', 1);
ini_set('error_log', LOG_DIR . 'php_errors.log');
?>