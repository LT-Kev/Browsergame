<?php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$app = new App();
$auth = $app->getAuth();

$playerId = $auth->getCurrentPlayerId();
if(!$playerId) {
    $playerId = 1; // Test
}

$resourceType = isset($_POST['resource']) ? $_POST['resource'] : '';
$amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 10;

if(empty($resourceType)) {
    echo json_encode(array('success' => false, 'message' => 'Keine Ressource angegeben'));
    exit;
}

$result = $app->getResources()->gather($playerId, $resourceType, $amount);
echo json_encode($result);
?>