// In ajax/gather_resource.php:
<?php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

try {
    $app = new App();
    $auth = $app->getAuth();
    
    if(!$auth->isLoggedIn()) {
        throw new Exception('Nicht eingeloggt');
    }
    
    $playerId = $auth->getCurrentPlayerId();
    
    // CSRF-Validierung
    if(!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        throw new Exception('CSRF-Token ungültig');
    }
    
    // Input-Validierung
    $resourceType = $_POST['resource'] ?? '';
    $allowedResources = ['gold', 'food', 'wood', 'stone'];
    
    if(!in_array($resourceType, $allowedResources)) {
        throw new Exception('Ungültige Ressource');
    }
    
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 100]
    ]);
    
    if($amount === false) {
        throw new Exception('Ungültige Menge');
    }
    
    $result = $app->getResources()->gather($playerId, $resourceType, $amount);
    echo json_encode($result);
    
} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}