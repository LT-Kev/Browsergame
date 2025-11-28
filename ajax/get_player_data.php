<<<<<<< HEAD
<?php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$app = new App();
$auth = $app->getAuth();

$playerId = $auth->getCurrentPlayerId();

if(!$playerId) {
    $playerId = 1;
}

$playerData = $app->getPlayer()->getPlayerById($playerId);

if($playerData) {
    $response = array(
        'username' => $playerData['username'],
        'gold' => $playerData['gold'],
        'food' => $playerData['food'],
        'wood' => $playerData['wood'],
        'stone' => $playerData['stone'],
        'energy' => $playerData['energy'],
        'level' => $playerData['level'],
        'exp' => $playerData['exp'],
        'exp_needed' => $app->getPlayer()->getExpNeeded($playerData['level']),
        'hp' => $playerData['hp'],
        'max_hp' => $playerData['max_hp'],
        'attack' => $playerData['attack'],
        'defense' => $playerData['defense'],
        //Admin-Status
        'admin' => ($playerData['admin'] ?? 0) >= 1 ? true : false,
    );
    
    echo json_encode($response);
} else {
    echo json_encode(array('error' => 'Spieler nicht gefunden'));
}
=======
<?php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$app = new App();
$auth = $app->getAuth();

$playerId = $auth->getCurrentPlayerId();

if(!$playerId) {
    $playerId = 1;
}

$playerData = $app->getPlayer()->getPlayerById($playerId);

if($playerData) {
    $response = array(
        'username' => $playerData['username'],
        'gold' => $playerData['gold'],
        'food' => $playerData['food'],
        'wood' => $playerData['wood'],
        'stone' => $playerData['stone'],
        'energy' => $playerData['energy'],
        'level' => $playerData['level'],
        'exp' => $playerData['exp'],
        'exp_needed' => $app->getPlayer()->getExpNeeded($playerData['level']),
        'hp' => $playerData['hp'],
        'max_hp' => $playerData['max_hp'],
        'attack' => $playerData['attack'],
        'defense' => $playerData['defense'],
        //Admin-Status
        'admin' => ($playerData['admin'] ?? 0) >= 1 ? true : false,
    );
    
    echo json_encode($response);
} else {
    echo json_encode(array('error' => 'Spieler nicht gefunden'));
}
>>>>>>> 971ab47689bd561bd08c6e4d77cea7f516414d66
?>