<?php
// ============================================================================
// api/building/upgrade.php
// ============================================================================
use App\Core\App;
use App\Api\ApiResponse;
use App\Helpers\CSRF;

require_once __DIR__ . '/../../init.php';

$app = App::getInstance();
$api = new ApiResponse($app);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $api->error('Method not allowed', 405);
}

if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
    $api->error('Invalid CSRF token', 403);
}

$playerId = $app->getAuth()->getCurrentPlayerId();

try {
    $buildingTypeId = filter_input(INPUT_POST, 'building_type_id', FILTER_VALIDATE_INT);
    
    if (!$buildingTypeId) {
        $api->error('Invalid building type', 400);
    }
    
    $result = $app->getBuilding()->upgradeBuilding($playerId, $buildingTypeId);
    
    if ($result['success']) {
        $response = $api->success($result);
        $api->sendResponse($response);
    } else {
        $api->error($result['message'], 400);
    }
    
} catch (Exception $e) {
    $api->error($e->getMessage(), 500);
}