<?php
// ============================================================================
// api/class/learn.php
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
    $classId = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    
    if (!$classId) {
        $api->error('Invalid class ID', 400);
    }
    
    $result = $app->getRPGClass()->learnClass($playerId, $classId);
    
    if ($result['success']) {
        $response = $api->success([], $result['message']);
        $api->sendResponse($response);
    } else {
        $api->error($result['message'], 400);
    }
    
} catch (Exception $e) {
    $api->error($e->getMessage(), 500);
}