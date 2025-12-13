<?php
// ============================================================================
// app/Api/ApiResponse.php - MIT RATE LIMITING
// ============================================================================
namespace App\Api;

use App\Core\App;
use App\Core\Logger;
use App\Core\RateLimiter;

class ApiResponse {
    private App $app;
    private bool $API_DEV;
    private bool $useDevFallback;
    private array $debug = [];
    private Logger $logger;
    private RateLimiter $rateLimiter;

    public function __construct(App $app) {
        $this->app = $app;

        // Konstanten aus config nutzen
        $this->API_DEV = defined('API_DEV') ? API_DEV : false;
        $this->useDevFallback = defined('DEV_USE_DEFAULT_PLAYER') ? DEV_USE_DEFAULT_PLAYER : false;

        // Logger und RateLimiter initialisieren
        $this->logger = new Logger('api');
        $this->rateLimiter = new RateLimiter($app->getDB());
        
        // Rate Limiting prüfen
        $this->checkRateLimit();
    }
    
    /**
     * Rate Limiting für API-Requests
     */
    private function checkRateLimit(): void {
        if(!defined('API_RATE_LIMIT_ENABLED') || !API_RATE_LIMIT_ENABLED) {
            return;
        }
        
        $identifier = $this->getRateLimitIdentifier();
        $maxRequests = defined('API_RATE_LIMIT_MAX') ? API_RATE_LIMIT_MAX : 100;
        $timeWindow = defined('API_RATE_LIMIT_WINDOW') ? API_RATE_LIMIT_WINDOW : 60;
        
        if(!$this->rateLimiter->checkLimit($identifier, RateLimiter::TYPE_API, $maxRequests, $timeWindow)) {
            $remaining = $this->rateLimiter->getRemainingAttempts($identifier, RateLimiter::TYPE_API, $maxRequests, $timeWindow);
            
            $this->logger->warning("API rate limit exceeded", [
                'identifier' => $identifier,
                'endpoint' => $_SERVER['REQUEST_URI'] ?? '',
                'reset_at' => date('Y-m-d H:i:s', $remaining['reset_at'])
            ]);
            
            http_response_code(429); // Too Many Requests
            $this->sendResponse([
                'success' => false,
                'error' => 'Rate limit exceeded',
                'message' => 'Zu viele Anfragen. Bitte warte einen Moment.',
                'retry_after' => $remaining['reset_at'] - time(),
                'reset_at' => $remaining['reset_at']
            ], 429);
        }
    }
    
    /**
     * Identifier für Rate Limiting erstellen
     * Kombiniert IP + User ID für bessere Kontrolle
     */
    private function getRateLimitIdentifier(): string {
        $ip = RateLimiter::getClientIp();
        $auth = $this->app->getAuth();
        $playerId = $auth->getCurrentPlayerId();
        
        // Wenn eingeloggt: User-basiert, sonst IP-basiert
        return $playerId ? "user_{$playerId}" : "ip_{$ip}";
    }

    /**
     * Spieler-Daten holen und optional nur ausgewählte Felder zurückgeben
     */
    public function getPlayerData(array $fields = null): array {
        $auth = $this->app->getAuth();
        $playerId = $auth->getCurrentPlayerId();
        $this->debug['initial_player_id'] = $playerId;
        $this->debug['session'] = $_SESSION ?? [];

        // Dev-Fallback
        if (!$playerId && $this->useDevFallback) {
            $playerId = 1;
            $_SESSION['player_id'] = $playerId;
            $_SESSION['logged_in'] = true;
            $this->debug['dev_fallback'] = true;
            $this->logger->info("Dev-Fallback aktiviert, Spieler 1 verwendet", ['player_id' => $playerId]);
        }

        if (!$playerId) {
            http_response_code(401);
            $this->debug['error'] = 'Unauthorized';
            $this->logger->warning("Unauthorized Zugriff auf API", ['debug' => $this->debug]);
            $this->sendResponse($this->debug);
        }

        $playerData = $this->app->getPlayer()->getPlayerById($playerId);
        $this->debug['player_data_exists'] = $playerData ? true : false;

        if (!$playerData) {
            http_response_code(404);
            $this->debug['error'] = 'Player not found';
            $this->logger->warning("Spieler nicht gefunden", ['player_id' => $playerId]);
            $this->sendResponse($this->debug);
        }

        $response = [];
        if ($fields === null) {
            $response = $playerData;
        } else {
            foreach ($fields as $f) {
                $response[$f] = $playerData[$f] ?? null;
            }
        }

        $this->logger->info("Spieler-Daten abgerufen", [
            'player_id' => $playerId, 
            'fields' => $fields ?? 'alle',
            'endpoint' => $_SERVER['REQUEST_URI'] ?? ''
        ]);

        if ($this->API_DEV) {
            $response['_debug'] = $this->debug;
            
            // Rate Limit Info im Debug Mode
            $identifier = $this->getRateLimitIdentifier();
            $response['_rate_limit'] = $this->rateLimiter->getRemainingAttempts($identifier);
        }

        return $response;
    }

    /**
     * Erfolgreiche Response mit Metadaten
     */
    public function success(array $data = [], string $message = null): array {
        $response = ['success' => true];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if (!empty($data)) {
            $response['data'] = $data;
        }
        
        // Timestamp hinzufügen
        $response['timestamp'] = time();
        
        if ($this->API_DEV) {
            $response['_debug'] = $this->debug;
            
            // Rate Limit Info
            $identifier = $this->getRateLimitIdentifier();
            $response['_rate_limit'] = $this->rateLimiter->getRemainingAttempts($identifier);
        }
        
        return $response;
    }

    /**
     * Error Response mit Details
     */
    public function error(string $message, int $code = 400, array $details = []): void {
        $response = [
            'success' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => time()
        ];
        
        if (!empty($details)) {
            $response['details'] = $details;
        }
        
        if ($this->API_DEV) {
            $response['_debug'] = $this->debug;
        }
        
        $this->logger->error("API Error", [
            'message' => $message,
            'code' => $code,
            'details' => $details,
            'endpoint' => $_SERVER['REQUEST_URI'] ?? ''
        ]);
        
        $this->sendResponse($response, $code);
    }

    /**
     * JSON Response senden und Script beenden
     */
    public function sendResponse(array $data, int $statusCode = 200): void {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
            
            // CORS Headers (falls benötigt)
            if(defined('API_CORS_ENABLED') && API_CORS_ENABLED) {
                header('Access-Control-Allow-Origin: ' . (API_CORS_ORIGIN ?? '*'));
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization');
            }
            
            // Cache Control
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }
}