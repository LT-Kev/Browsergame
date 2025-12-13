<?php
/**
 * /api/player/get_location.php
 * Liefert Spieler-Daten als JSON
 * Dev-Fallback und Debug werden automatisch über ApiResponse gehandhabt
 */

use App\Core\App;
use App\Api\ApiResponse;

require_once __DIR__ . '/../../init.php';

$app = App::getInstance();
$api = new ApiResponse($app);

// Definiere hier, welche Felder du ausgeben willst
$fields = [
    'username',
    'world_x', 
    'world_y',
    'last_travel'
];

// Spieler-Daten laden und Response senden
$response = $api->getPlayerData($fields);
$api->sendResponse($response);
