<?php
require_once '../init.php';

use App\Core\App;

$app = App::getInstance();
$playerId = $app->getAuth()->getCurrentPlayerId();

header('Content-Type: application/json; charset=utf-8');

if (!$user->isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$upgradeId = intval($_POST['upgrade_id'] ?? 0);
if ($upgradeId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid upgrade id']);
    exit;
}

// Upgrade laden
$upgrade = $db->selectOne(
    "SELECT * FROM upgrades WHERE id = ? AND user_id = ?",
    [$upgradeId, $user->getId()]
);

if (!$upgrade) {
    echo json_encode(['success' => false, 'error' => 'Upgrade not found']);
    exit;
}

// Ressourcen zurückerstatten
$db->update(
    "UPDATE cities SET 
        wood  = wood  + ?, 
        stone = stone + ?, 
        food  = food  + ?
     WHERE id = ?",
    [
        $upgrade['cost_wood'],
        $upgrade['cost_stone'],
        $upgrade['cost_food'],
        $upgrade['city_id']
    ]
);

// Upgrade löschen
$db->delete("DELETE FROM upgrades WHERE id = ?", [$upgradeId]);

echo json_encode(['success' => true]);
exit;
?>