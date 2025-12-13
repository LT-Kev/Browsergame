<?php
/**
 * POST /api/player/distribute_stat
 * Statuspunkte verteilen
 */

use App\Core\App;
use App\Api\ApiResponse;
use App\Helpers\CSRF;

require_once __DIR__ . '/../../init.php';

$app = App::getInstance();
$api = new ApiResponse($app);

// Nur POST erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $api->error('Method not allowed', 405);
}

// CSRF Check
if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
    $api->error('Invalid CSRF token', 403);
}

$playerId = $app->getAuth()->getCurrentPlayerId();

try {
    $statName = $_POST['stat'] ?? '';
    $amount = filter_var($_POST['amount'] ?? 1, FILTER_VALIDATE_INT);
    
    if (!$amount || $amount < 1) {
        $api->error('Invalid amount', 400);
    }
    
    // Stats-Service verwenden
    $result = $app->getStats()->distributeStatPoint($playerId, $statName, $amount);
    
    if ($result['success']) {
        $response = $api->success([
            'stat' => $statName,
            'amount' => $amount
        ], $result['message']);
        
        $api->sendResponse($response);
    } else {
        $api->error($result['message'], 400);
    }
    
} catch (Exception $e) {
    $api->error($e->getMessage(), 500);
}