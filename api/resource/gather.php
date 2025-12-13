<?php
// ============================================================================
// api/resource/gather.php
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
    $resourceType = $_POST['resource'] ?? '';
    $allowedResources = ['gold', 'food', 'wood', 'stone'];
    
    if (!in_array($resourceType, $allowedResources)) {
        $api->error('Invalid resource type', 400);
    }
    
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 100]
    ]);
    
    if ($amount === false) {
        $api->error('Invalid amount', 400);
    }
    
    $result = $app->getResources()->gather($playerId, $resourceType, $amount);
    
    if ($result['success']) {
        $response = $api->success($result);
        $api->sendResponse($response);
    } else {
        $api->error($result['message'], 400);
    }
    
} catch (Exception $e) {
    $api->error($e->getMessage(), 500);
}