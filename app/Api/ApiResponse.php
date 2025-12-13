<?php
// app/Api/ApiResponse.php
namespace App\Api;

use App\Core\App;

class ApiResponse {
    private App $app;
    private bool $API_DEV;
    private bool $useDevFallback;
    private array $debug = [];

    public function __construct(App $app) {
        $this->app = $app;

        // Konstanten aus config nutzen
        $this->API_DEV = defined('API_DEV') ? API_DEV : false;
        $this->useDevFallback = defined('DEV_USE_DEFAULT_PLAYER') ? DEV_USE_DEFAULT_PLAYER : false;
    }

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
        }

        if (!$playerId) {
            http_response_code(401);
            $this->debug['error'] = 'Unauthorized';
            $this->sendResponse($this->debug);
        }

        $playerData = $this->app->getPlayer()->getPlayerById($playerId, $fields);
        $this->debug['player_data_exists'] = $playerData ? true : false;

        if (!$playerData) {
            http_response_code(404);
            $this->debug['error'] = 'Player not found';
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

        if ($this->API_DEV) {
            $response['_debug'] = $this->debug;
        }

        return $response;
    }

    public function sendResponse(array $data, int $statusCode = 200): void {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
