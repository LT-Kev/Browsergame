<?php
// ============================================================================
// api/character/get_races.php
// ============================================================================
use App\Core\App;
use App\Api\ApiResponse;

require_once __DIR__ . '/../../init.php';

$app = App::getInstance();
$api = new ApiResponse($app);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $api->error('Method not allowed', 405);
}

try {
    $races = $app->getRace()->getAllPlayableRaces();
    $response = $api->success(['races' => $races]);
    $api->sendResponse($response);
} catch (Exception $e) {
    $api->error($e->getMessage(), 500);
}