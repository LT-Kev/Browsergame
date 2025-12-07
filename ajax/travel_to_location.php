<?php
require_once '../init.php';

use App\Core\App;

$app = App::getInstance();
$playerId = $app->getAuth()->getCurrentPlayerId();

header('Content-Type: application/json');

if(!$playerId) {
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}

if(!isset($_POST['x']) || !isset($_POST['y'])) {
    echo json_encode(['success' => false, 'message' => 'Ungültige Koordinaten']);
    exit;
}

$x = intval($_POST['x']);
$y = intval($_POST['y']);

// Update Spieler-Position
$sql = "UPDATE players SET world_x = :x, world_y = :y WHERE id = :id";
$app->getDB()->update($sql, [':x' => $x, ':y' => $y, ':id' => $playerId]);

echo json_encode([
    'success' => true,
    'message' => 'Erfolgreich gereist'
]);