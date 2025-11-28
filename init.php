<?php
/**
 * init.php – Zentrale Initialisierung
 * 
 * Diese Datei wird von allen Seiten eingebunden und initialisiert:
 * - Configuration
 * - Security Settings
 * - Session Management
 * - Autoloader
 * - Global Functions
 * - Error Handling
 * 
 * @version 2.0
 * @author Your Name
 */

// ============================================================================
// PREVENT DIRECT ACCESS
// ============================================================================
if(!defined('INIT_LOADED')) {
    define('INIT_LOADED', true);
}

// ============================================================================
// BASE PATHS
// ============================================================================
define('BASE_PATH', __DIR__);
define('CLASS_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'class');
define('CONFIG_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'config');
define('PAGES_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'pages');
define('AJAX_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'ajax');

// ============================================================================
// LOAD CONFIGURATION
// ============================================================================
$configFile = CONFIG_PATH . DIRECTORY_SEPARATOR . 'config.php';
if(!file_exists($configFile)) {
    die('Configuration file not found. Please create config/config.php');
}
require_once $configFile;

// ============================================================================
// ERROR HANDLING
// ============================================================================
if(DEV_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    
    // Custom Error Handler für Produktion
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        $logger = new Logger('error');
        $logger->error("PHP Error: {$errstr}", [
            'errno' => $errno,
            'file' => $errfile,
            'line' => $errline,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ]);
        
        // User-freundliche Fehlermeldung
        if(!headers_sent()) {
            http_response_code(500);
        }
        echo "Ein Fehler ist aufgetreten. Bitte versuche es später erneut.";
        exit;
    });
    
    // Exception Handler
    set_exception_handler(function($exception) {
        $logger = new Logger('error');
        $logger->critical("Uncaught Exception: " . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        if(!headers_sent()) {
            http_response_code(500);
        }
        echo "Ein schwerwiegender Fehler ist aufgetreten.";
        exit;
    });
    
    // Fatal Error Handler
    register_shutdown_function(function() {
        $error = error_get_last();
        if($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $logger = new Logger('error');
            $logger->critical("Fatal Error: {$error['message']}", [
                'file' => $error['file'],
                'line' => $error['line']
            ]);
        }
    });
}

// PHP Error Log
ini_set('log_errors', 1);
ini_set('error_log', LOG_DIR . 'php_errors.log');

// ============================================================================
// SECURITY SETTINGS
// ============================================================================

// Session Security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', !DEV_MODE); // Nur HTTPS in Produktion
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.sid_length', 48);
ini_set('session.sid_bits_per_character', 6);

// Session Name ändern (versteckt PHP)
ini_set('session.name', 'GAMESESSION');

// Session Lifetime
ini_set('session.gc_maxlifetime', 7200); // 2 Stunden
ini_set('session.cookie_lifetime', 0); // Bis Browser geschlossen

// Expose PHP deaktivieren
if(function_exists('header_remove')) {
    header_remove('X-Powered-By');
}
ini_set('expose_php', 0);

// Upload Limits (Sicherheit)
ini_set('upload_max_filesize', '5M');
ini_set('post_max_size', '6M');
ini_set('max_file_uploads', 3);

// Memory Limit
ini_set('memory_limit', '128M');

// Execution Time
ini_set('max_execution_time', 30);

// ============================================================================
// SESSION START WITH VALIDATION - FIXED VERSION
// ============================================================================
if(session_status() === PHP_SESSION_NONE) {
    session_start();
    
    // Session Fixation Protection
    if(!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
        $_SESSION['created'] = time();
    }
    
    // Session Hijacking Protection
    if(!isset($_SESSION['user_agent'])) {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    } else {
        // Validiere User Agent & IP
        if($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            session_unset();
            session_destroy();
            session_start();
            
            if(class_exists('Logger')) {
                $logger = new Logger('security');
                $logger->securityEvent('Session hijacking attempt detected', [
                    'expected_ua' => $_SESSION['user_agent'],
                    'actual_ua' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
            }
        }
    }
    
    // ============================================================================
    // FIXED: Session Timeout NUR wenn NICHT via Remember-Me eingeloggt
    // ============================================================================
    if(!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
    } else {
        $inactiveTime = time() - $_SESSION['last_activity'];
        
        // Timeout nur wenn > 2 Stunden inaktiv UND kein Remember-Token vorhanden
        if($inactiveTime > 7200) {
            // Prüfe ob Remember-Me-Token existiert
            $hasRememberToken = isset($_COOKIE['remember_token']) && !empty($_COOKIE['remember_token']);
            
            if(!$hasRememberToken) {
                // Kein Remember-Token -> Session beenden
                session_unset();
                session_destroy();
                session_start();
                
                if(class_exists('Logger')) {
                    $logger = new Logger('auth');
                    $logger->info('Session expired due to inactivity', [
                        'inactive_time' => $inactiveTime
                    ]);
                }
            } else {
                // Remember-Token vorhanden -> Session verlängern
                $_SESSION['last_activity'] = time();
                
                if(class_exists('Logger') && LOG_ENABLED) {
                    $logger = new Logger('auth');
                    $logger->debug('Session kept alive via remember token', [
                        'inactive_time' => $inactiveTime
                    ]);
                }
            }
        } else {
            // Normale Aktivität -> Update timestamp
            $_SESSION['last_activity'] = time();
        }
    }
    
    // Session Regeneration alle 30 Minuten (nur wenn aktiv eingeloggt)
    if(!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        if(time() - $_SESSION['last_regeneration'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

// ============================================================================
// AUTOLOADER
// ============================================================================
spl_autoload_register(function($className) {
    // Class-Datei Pfad
    $classFile = CLASS_PATH . DIRECTORY_SEPARATOR . 'class.' . strtolower($className) . '.php';
    
    if(file_exists($classFile)) {
        require_once $classFile;
    } else {
        // Logging nur wenn Logger bereits geladen
        if(class_exists('Logger', false)) {
            $logger = new Logger('system');
            $logger->warning("Class not found: {$className}", [
                'expected_path' => $classFile,
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
            ]);
        }
        
        if(DEV_MODE) {
            trigger_error("Class not found: {$className} in {$classFile}", E_USER_WARNING);
        }
    }
});

// ============================================================================
// INITIALIZE APP
// ============================================================================
try {
    $app = new App();
    $auth = $app->getAuth();
    
    if(LOG_ENABLED) {
        $logger = $app->getLogger();
        $logger->debug('Init completed', [
            'session_id' => session_id(),
            'memory' => memory_get_usage(true)
        ]);
    }
    
} catch(Exception $e) {
    if(class_exists('Logger')) {
        $logger = new Logger('critical');
        $logger->critical('Failed to initialize App', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    if(DEV_MODE) {
        die("Critical Error: " . $e->getMessage());
    } else {
        die("System initialization failed. Please contact administrator.");
    }
}

// ============================================================================
// GLOBAL HELPER FUNCTIONS
// ============================================================================

/**
 * HTML Escape für XSS-Schutz
 * 
 * @param string $string String to escape
 * @param bool $doubleEncode Ob bereits encodete Entities nochmal encoded werden
 * @return string Escaped string
 */
function e($string, $doubleEncode = true) {
    if($string === null) return '';
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8', $doubleEncode);
}

/**
 * JSON Escape für JavaScript-Kontext
 * 
 * @param mixed $data Data to encode
 * @return string JSON encoded string
 */
function je($data) {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

/**
 * URL Escape
 * 
 * @param string $string String to encode
 * @return string URL encoded string
 */
function ue($string) {
    return urlencode($string);
}

/**
 * Lädt eine Seite dynamisch
 * 
 * @param string $page Page name ohne .php
 * @param array $data Optionale Daten die an die Seite übergeben werden
 * @return void
 */
function loadPage($page, $data = []) {
    global $app;
    
    // Security: Verhindere Directory Traversal
    $page = str_replace(['..', '/', '\\'], '', $page);
    
    $pagePath = PAGES_PATH . DIRECTORY_SEPARATOR . $page . '.php';
    
    if(file_exists($pagePath)) {
        // Daten extrahieren für die Page
        extract($data, EXTR_SKIP);
        
        // Buffer starten für Error Handling
        ob_start();
        try {
            include $pagePath;
            $content = ob_get_clean();
            echo $content;
        } catch(Exception $e) {
            ob_end_clean();
            
            if(LOG_ENABLED && class_exists('Logger')) {
                $logger = new Logger('pages');
                $logger->error("Error loading page: {$page}", [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
            
            echo '<div class="error-box">';
            echo '<h3>Fehler beim Laden der Seite</h3>';
            if(DEV_MODE) {
                echo '<p>' . e($e->getMessage()) . '</p>';
                echo '<pre>' . e($e->getTraceAsString()) . '</pre>';
            } else {
                echo '<p>Die Seite konnte nicht geladen werden.</p>';
            }
            echo '</div>';
        }
    } else {
        if(LOG_ENABLED && class_exists('Logger')) {
            $logger = new Logger('pages');
            $logger->warning("Page not found: {$page}", [
                'expected_path' => $pagePath,
                'referrer' => $_SERVER['HTTP_REFERER'] ?? 'none'
            ]);
        }
        
        echo '<div class="error-box">';
        echo '<h3>Seite nicht gefunden</h3>';
        echo '<p>Die angeforderte Seite existiert nicht.</p>';
        if(DEV_MODE) {
            echo '<p>Gesuchter Pfad: ' . e($pagePath) . '</p>';
        }
        echo '</div>';
    }
}

/**
 * Lädt Spielerdaten neu (für AJAX)
 * 
 * @return array|false Player data or false on error
 */
function reloadPlayerData() {
    global $app;
    
    try {
        $playerId = $app->getAuth()->getCurrentPlayerId();
        if(!$playerId) {
            return false;
        }
        
        return $app->getPlayer()->getPlayerById($playerId);
        
    } catch(Exception $e) {
        if(LOG_ENABLED) {
            $logger = new Logger('player');
            $logger->error('Failed to reload player data', [
                'error' => $e->getMessage()
            ]);
        }
        return false;
    }
}

/**
 * Formatiert Zahlen mit Tausender-Trennung
 * 
 * @param int|float $number Number to format
 * @param int $decimals Decimal places
 * @return string Formatted number
 */
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals, ',', '.');
}

/**
 * Formatiert Bytes in lesbare Größe
 * 
 * @param int $bytes Bytes
 * @param int $precision Decimal places
 * @return string Formatted size
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Zeitdifferenz in lesbarem Format
 * 
 * @param int $timestamp Unix timestamp
 * @return string Readable time difference
 */
function timeAgo($timestamp) {
    $diff = time() - $timestamp;
    
    if($diff < 60) return 'gerade eben';
    if($diff < 3600) return floor($diff / 60) . ' Minuten';
    if($diff < 86400) return floor($diff / 3600) . ' Stunden';
    if($diff < 604800) return floor($diff / 86400) . ' Tage';
    
    return date('d.m.Y H:i', $timestamp);
}

/**
 * Sicherer Redirect
 * 
 * @param string $url URL to redirect to
 * @param int $statusCode HTTP status code
 * @return void
 */
function redirect($url, $statusCode = 302) {
    // Verhindere Header Injection
    $url = str_replace(["\r", "\n"], '', $url);
    
    // Validiere URL
    if(!filter_var($url, FILTER_VALIDATE_URL) && strpos($url, '/') !== 0) {
        $url = '/';
    }
    
    if(!headers_sent()) {
        header("Location: {$url}", true, $statusCode);
        exit;
    }
    
    // Fallback wenn Headers schon gesendet
    echo '<script>window.location.href="' . e($url) . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . e($url) . '"></noscript>';
    exit;
}

/**
 * Gibt JSON-Response zurück (für AJAX)
 * 
 * @param mixed $data Data to return
 * @param int $statusCode HTTP status code
 * @return void
 */
function jsonResponse($data, $statusCode = 200) {
    if(!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
    }
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Prüft ob Request ein AJAX-Request ist
 * 
 * @return bool
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Prüft ob Request eine POST-Request ist
 * 
 * @return bool
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Prüft ob Request eine GET-Request ist
 * 
 * @return bool
 */
function isGet() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Holt sicher einen GET-Parameter
 * 
 * @param string $key Parameter key
 * @param mixed $default Default value
 * @param string $filter Filter type (int, string, email, url)
 * @return mixed Filtered value
 */
function getParam($key, $default = null, $filter = 'string') {
    if(!isset($_GET[$key])) {
        return $default;
    }
    
    $value = $_GET[$key];
    
    switch($filter) {
        case 'int':
            return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int)$value : $default;
        case 'float':
            return filter_var($value, FILTER_VALIDATE_FLOAT) !== false ? (float)$value : $default;
        case 'email':
            return filter_var($value, FILTER_VALIDATE_EMAIL) ?: $default;
        case 'url':
            return filter_var($value, FILTER_VALIDATE_URL) ?: $default;
        case 'bool':
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
        case 'string':
        default:
            return is_string($value) ? trim($value) : $default;
    }
}

/**
 * Holt sicher einen POST-Parameter
 * 
 * @param string $key Parameter key
 * @param mixed $default Default value
 * @param string $filter Filter type
 * @return mixed Filtered value
 */
function postParam($key, $default = null, $filter = 'string') {
    if(!isset($_POST[$key])) {
        return $default;
    }
    
    $value = $_POST[$key];
    
    switch($filter) {
        case 'int':
            return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int)$value : $default;
        case 'float':
            return filter_var($value, FILTER_VALIDATE_FLOAT) !== false ? (float)$value : $default;
        case 'email':
            return filter_var($value, FILTER_VALIDATE_EMAIL) ?: $default;
        case 'url':
            return filter_var($value, FILTER_VALIDATE_URL) ?: $default;
        case 'bool':
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
        case 'string':
        default:
            return is_string($value) ? trim($value) : $default;
    }
}

/**
 * Generiert einen zufälligen Token
 * 
 * @param int $length Length in bytes (will be doubled in hex)
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Prüft ob String ein gültiger Token ist
 * 
 * @param string $token Token to validate
 * @return bool
 */
function isValidToken($token) {
    return is_string($token) && preg_match('/^[a-f0-9]{64,}$/', $token);
}

/**
 * Debug-Funktion (nur in DEV_MODE)
 * 
 * @param mixed $data Data to debug
 * @param bool $die Stop execution
 * @return void
 */
function dd($data, $die = true) {
    if(!DEV_MODE) return;
    
    echo '<pre style="background:#1a1a1a;color:#0f0;padding:20px;border:2px solid #0f0;margin:20px;">';
    echo '<strong>DEBUG:</strong>' . PHP_EOL;
    print_r($data);
    echo '</pre>';
    
    if($die) exit;
}

// ============================================================================
// PERFORMANCE MONITORING (nur in DEV_MODE)
// ============================================================================
if(DEV_MODE && LOG_ENABLED) {
    // Start Time für Performance-Messung
    if(!defined('APP_START_TIME')) {
        define('APP_START_TIME', microtime(true));
    }
    if(!defined('APP_START_MEMORY')) {
        define('APP_START_MEMORY', memory_get_usage());
    }
    
    // Bei Script-Ende Performance loggen
    register_shutdown_function(function() {
        if(!class_exists('Logger')) return;
        
        $executionTime = (microtime(true) - APP_START_TIME) * 1000; // in ms
        $memoryUsed = memory_get_usage() - APP_START_MEMORY;
        $peakMemory = memory_get_peak_usage(true);
        
        if($executionTime > 1000) { // Warnung wenn > 1 Sekunde
            $logger = new Logger('performance');
            $logger->performanceWarning('Page load', $executionTime, 1000);
        }
        
        $logger = new Logger('performance');
        $logger->debug('Request completed', [
            'execution_time_ms' => round($executionTime, 2),
            'memory_used' => formatBytes($memoryUsed),
            'peak_memory' => formatBytes($peakMemory),
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
        ]);
    });
}

// ============================================================================
// TIMEZONE
// ============================================================================
if(defined('TIMEZONE')) {
    date_default_timezone_set(TIMEZONE);
}

// ============================================================================
// READY
// ============================================================================
if(LOG_ENABLED && DEBUG_MODE) {
    $logger = new Logger('init');
    $logger->debug('Init file loaded successfully', [
        'php_version' => PHP_VERSION,
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time')
    ]);
}