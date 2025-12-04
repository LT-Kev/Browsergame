<?php
/**
 * init.php – Zentrale Initialisierung
 * 
 * Diese Datei wird von allen Seiten eingebunden und initialisiert:
 * - Configuration
 * - Security Settings
 * - Session Management
 * - Autoloader (PSR-4 für app/)
 * - Global Functions
 * - Error Handling
 * 
 * @version 3.0 - Migriert zu app/ Struktur
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
define('APP_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'app');
define('CLASS_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'class'); // Legacy Support
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
        if(class_exists('App\Core\Logger')) {
            $logger = new App\Core\Logger('error');
            $logger->error("PHP Error: {$errstr}", [
                'errno' => $errno,
                'file' => $errfile,
                'line' => $errline,
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ]);
        }
        
        if(!headers_sent()) {
            http_response_code(500);
        }
        echo "Ein Fehler ist aufgetreten. Bitte versuche es später erneut.";
        exit;
    });
    
    // Exception Handler
    set_exception_handler(function($exception) {
        if(class_exists('App\Core\Logger')) {
            $logger = new App\Core\Logger('error');
            $logger->critical("Uncaught Exception: " . $exception->getMessage(), [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]);
        }
        
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
            if(class_exists('App\Core\Logger')) {
                $logger = new App\Core\Logger('error');
                $logger->critical("Fatal Error: {$error['message']}", [
                    'file' => $error['file'],
                    'line' => $error['line']
                ]);
            }
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
ini_set('session.cookie_secure', !DEV_MODE);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.sid_length', 48);
ini_set('session.sid_bits_per_character', 6);

// Session Name ändern
ini_set('session.name', 'GAMESESSION');

// Session Lifetime
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 0);

// Expose PHP deaktivieren
if(function_exists('header_remove')) {
    header_remove('X-Powered-By');
}
ini_set('expose_php', 0);

// Upload Limits
ini_set('upload_max_filesize', '5M');
ini_set('post_max_size', '6M');
ini_set('max_file_uploads', 3);

// Memory Limit
ini_set('memory_limit', '128M');

// Execution Time
ini_set('max_execution_time', 30);

// ============================================================================
// PSR-4 AUTOLOADER für app/ 
// ============================================================================
spl_autoload_register(function($className) {
    // PSR-4: App\ Namespace → app/ Ordner
    if(strpos($className, 'App\\') === 0) {
        // Namespace zu Pfad: App\Core\Database → app/Core/Database.php
        $classPath = APP_PATH . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, substr($className, 4)) . '.php';
        
        if(file_exists($classPath)) {
            require_once $classPath;
            return;
        }
        
        // Logging wenn Klasse nicht gefunden
        if(DEV_MODE) {
            trigger_error("Class not found: {$className} (expected: {$classPath})", E_USER_WARNING);
        }
        
        return;
    }
});

// ============================================================================
// SESSION START WITH VALIDATION
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
        if($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            session_unset();
            session_destroy();
            session_start();
            
            if(class_exists('App\Core\Logger')) {
                $logger = new App\Core\Logger('security');
                $logger->warning('Session hijacking attempt detected', [
                    'expected_ua' => $_SESSION['user_agent'],
                    'actual_ua' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
            }
        }
    }
    
    // Session Timeout mit Remember-Me Support
    if(!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
    } else {
        $inactiveTime = time() - $_SESSION['last_activity'];
        
        if($inactiveTime > 7200) {
            $hasRememberToken = isset($_COOKIE['remember_token']) && !empty($_COOKIE['remember_token']);
            
            if(!$hasRememberToken) {
                session_unset();
                session_destroy();
                session_start();
                
                if(class_exists('App\Core\Logger') && LOG_ENABLED) {
                    $logger = new App\Core\Logger('auth');
                    $logger->info('Session expired due to inactivity', [
                        'inactive_time' => $inactiveTime
                    ]);
                }
            } else {
                $_SESSION['last_activity'] = time();
            }
        } else {
            $_SESSION['last_activity'] = time();
        }
    }
    
    // Session Regeneration alle 30 Minuten
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
// INITIALIZE APP (neue Struktur)
// ============================================================================
try {
    // Verwende neue App-Struktur
    $app = App\Core\App::getInstance();
    $auth = $app->getAuth();
    
    if(LOG_ENABLED) {
        $logger = $app->getLogger();
        $logger->debug('Init completed', [
            'session_id' => session_id(),
            'memory' => memory_get_usage(true)
        ]);
    }
    
} catch(Exception $e) {
    if(class_exists('App\Core\Logger')) {
        $logger = new App\Core\Logger('critical');
        $logger->critical('Failed to initialize App', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    if(DEV_MODE) {
        die("Critical Error: " . $e->getMessage() . "<br><pre>" . $e->getTraceAsString() . "</pre>");
    } else {
        die("System initialization failed. Please contact administrator.");
    }
}

// ============================================================================
// GLOBAL HELPER FUNCTIONS
// ============================================================================

function e($string, $doubleEncode = true) {
    if($string === null) return '';
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8', $doubleEncode);
}

function je($data) {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

function ue($string) {
    return urlencode($string);
}

function loadPage($page, $data = []) {
    global $app;
    
    $page = str_replace(['..', '/', '\\'], '', $page);
    $pagePath = PAGES_PATH . DIRECTORY_SEPARATOR . $page . '.php';
    
    if(file_exists($pagePath)) {
        extract($data, EXTR_SKIP);
        
        ob_start();
        try {
            include $pagePath;
            $content = ob_get_clean();
            echo $content;
        } catch(Exception $e) {
            ob_end_clean();
            
            if(LOG_ENABLED && class_exists('App\Core\Logger')) {
                $logger = new App\Core\Logger('pages');
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
        if(LOG_ENABLED && class_exists('App\Core\Logger')) {
            $logger = new App\Core\Logger('pages');
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
            $logger = new App\Core\Logger('player');
            $logger->error('Failed to reload player data', [
                'error' => $e->getMessage()
            ]);
        }
        return false;
    }
}

function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals, ',', '.');
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

function timeAgo($timestamp) {
    $diff = time() - $timestamp;
    
    if($diff < 60) return 'gerade eben';
    if($diff < 3600) return floor($diff / 60) . ' Minuten';
    if($diff < 86400) return floor($diff / 3600) . ' Stunden';
    if($diff < 604800) return floor($diff / 86400) . ' Tage';
    
    return date('d.m.Y H:i', $timestamp);
}

function redirect($url, $statusCode = 302) {
    $url = str_replace(["\r", "\n"], '', $url);
    
    if(!filter_var($url, FILTER_VALIDATE_URL) && strpos($url, '/') !== 0) {
        $url = '/';
    }
    
    if(!headers_sent()) {
        header("Location: {$url}", true, $statusCode);
        exit;
    }
    
    echo '<script>window.location.href="' . e($url) . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . e($url) . '"></noscript>';
    exit;
}

function jsonResponse($data, $statusCode = 200) {
    if(!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
    }
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function isGet() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

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

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function isValidToken($token) {
    return is_string($token) && preg_match('/^[a-f0-9]{64,}$/', $token);
}

function dd($data, $die = true) {
    if(!DEV_MODE) return;
    
    echo '<pre style="background:#1a1a1a;color:#0f0;padding:20px;border:2px solid #0f0;margin:20px;">';
    echo '<strong>DEBUG:</strong>' . PHP_EOL;
    print_r($data);
    echo '</pre>';
    
    if($die) exit;
}

// ============================================================================
// PERFORMANCE MONITORING
// ============================================================================
if(DEV_MODE && LOG_ENABLED) {
    if(!defined('APP_START_TIME')) {
        define('APP_START_TIME', microtime(true));
    }
    if(!defined('APP_START_MEMORY')) {
        define('APP_START_MEMORY', memory_get_usage());
    }
    
    register_shutdown_function(function() {
        if(!class_exists('App\Core\Logger')) return;
        
        $executionTime = (microtime(true) - APP_START_TIME) * 1000;
        $memoryUsed = memory_get_usage() - APP_START_MEMORY;
        $peakMemory = memory_get_peak_usage(true);
        
        if($executionTime > 1000) {
            $logger = new App\Core\Logger('performance');
            $logger->warning("Slow page load: {$executionTime}ms", [
                'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);
        }
        
        $logger = new App\Core\Logger('performance');
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
if(LOG_ENABLED && DEV_MODE) {
    $logger = new App\Core\Logger('init');
    $logger->debug('Init file loaded successfully', [
        'php_version' => PHP_VERSION,
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'app_structure' => 'new (app/)'
    ]);
}