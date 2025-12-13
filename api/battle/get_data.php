<?php
// api/battle/get_data.php
use App\Core\App;
use App\Api\ApiResponse;

require_once __DIR__ . '/../../init.php';

$app = App::getInstance();
$api = new ApiResponse($app);

// Beispiel: Battle-Daten mit Validierung
$battleId = $_GET['id'] ?? null;

if (!$battleId) {
    $api->sendResponse(['error' => 'Battle ID required'], 400);
}

// Hole Battle-Daten (angenommen du hast eine Battle-Methode)
$battleData = $app->getCombat()->getBattleById($battleId);

if (!$battleData) {
    $api->sendResponse(['error' => 'Battle not found'], 404);
}

$api->sendResponse($battleData);