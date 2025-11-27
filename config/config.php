<?php
// Datenbank Konfiguration
define('DB_HOST', 'localhost');
define('DB_NAME', 'browsergame');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Weitere Konfigurationen
define('SITE_URL', 'http://server/Game/');
define('SITE_NAME', 'Mein Browsergame');

// Zeitzone
date_default_timezone_set('Europe/Berlin');

// Logging Einstellungen
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'INFO'); // INFO, WARNING, ERROR, DEBUG, CRITICAL
define('LOG_DIR', __DIR__ . '/../logs/');

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