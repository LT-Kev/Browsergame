<?php
require_once '../init.php';

use App\Core\App;

$app = App::getInstance();
$playerId = $app->getAuth()->getCurrentPlayerId();_DIR__ . '/../init.php';

header('Content-Type: application/json; charset=utf-8');

if (!$user->isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$cityId   = intval($_POST['city_id'] ?? 0);
$building = trim($_POST['building'] ?? '');
$slot     = intval($_POST['slot'] ?? 0);

if ($cityId <= 0 || $building === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// Stadt prüfen
$city = $db->selectOne(
    "SELECT * FROM cities WHERE id = ? AND user_id = ?",
    [$cityId, $user->getId()]
);

if (!$city) {
    echo json_encode(['success' => false, 'error' => 'City not found']);
    exit;
}

// Beispiel-Kosten (anpassen!)
$costs = [
    'sawmill' => ['wood' => 100, 'stone' => 50, 'food' => 20, 'time' => 3600],
    'stone_mine' => ['wood' => 80, 'stone' => 120, 'food' => 30, 'time' => 3600],
];

if (!isset($costs[$building])) {
    echo json_encode(['success' => false, 'error' => 'Unknown building']);
    exit;
}

$c = $costs[$building];

// Ressourcen prüfen
if ($city['wood'] < $c['wood'] || $city['stone'] < $c['stone'] || $city['food'] < $c['food']) {
    echo json_encode(['success' => false, 'error' => 'Not enough resources']);
    exit;
}

// Ressourcen abziehen
$db->update(
    "UPDATE cities SET
        wood  = wood  - ?,
        stone = stone - ?,
        food  = food  - ?
     WHERE id = ?",
    [$c['wood'], $c['stone'], $c['food'], $cityId]
);

// Upgrade eintragen
$finishTime = time() + $c['time'];

$db->insert(
    "INSERT INTO upgrades (user_id, city_id, building, slot, cost_wood, cost_stone, cost_food, finish_time)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
    [
        $user->getId(), $cityId, $building, $slot,
        $c['wood'], $c['stone'], $c['food'], $finishTime
    ]
);

echo json_encode([
    'success' => true,
    'finish_time' => $finishTime
]);
exit;
?>