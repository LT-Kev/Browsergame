<?php
// ============================================================================
// 5. UPDATE: ajax/get_player_data.php - RPG Stats hinzufügen
// ============================================================================
?>
<?php
require_once '../init.php';

use App\Core\App;

$app = App::getInstance();
$playerId = $app->getAuth()->getCurrentPlayerId();

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
        
        // Basis-Ressourcen
        'gold' => $playerData['gold'],
        'food' => $playerData['food'],
        'wood' => $playerData['wood'],
        'stone' => $playerData['stone'],
        'energy' => $playerData['energy'],
        
        // Level & EXP
        'level' => $playerData['level'],
        'exp' => $playerData['exp'],
        'exp_needed' => $app->getPlayer()->getExpNeeded($playerData['level']),
        
        // Kampfwerte
        'hp' => $playerData['hp'],
        'max_hp' => $playerData['max_hp'],
        'attack' => $playerData['attack'],
        'defense' => $playerData['defense'],
        
        // RPG Stats (NEU)
        'mana' => $playerData['mana'] ?? 100,
        'max_mana' => $playerData['max_mana'] ?? 100,
        'stamina' => $playerData['stamina'] ?? 100,
        'max_stamina' => $playerData['max_stamina'] ?? 100,
        
        'strength' => $playerData['strength'] ?? 10,
        'dexterity' => $playerData['dexterity'] ?? 10,
        'constitution' => $playerData['constitution'] ?? 10,
        'intelligence' => $playerData['intelligence'] ?? 10,
        'wisdom' => $playerData['wisdom'] ?? 10,
        'charisma' => $playerData['charisma'] ?? 10,
        
        'stat_points' => $playerData['stat_points'] ?? 0,
        'character_created' => $playerData['character_created'] ?? 0,
        
        // Rasse & Klasse
        'race_id' => $playerData['race_id'] ?? null,
        'class_id' => $playerData['class_id'] ?? null,
        
        // Admin-Status
        'admin' => ($playerData['admin'] ?? 0) >= 1 ? true : false,
    );
    
    echo json_encode($response);
} else {
    echo json_encode(array('error' => 'Spieler nicht gefunden'));
}
?>