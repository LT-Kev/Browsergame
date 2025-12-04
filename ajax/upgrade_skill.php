<?php
// ============================================================================
// ajax/upgrade_skill.php
// ============================================================================
require_once '../init.php';

use App\Core\App;

$app = App::getInstance();
$playerId = $app->getAuth()->getCurrentPlayerId();

header('Content-Type: application/json');

$app = new App();
$auth = $app->getAuth();

if(!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}

$playerId = $auth->getCurrentPlayerId();

// CSRF-Token prüfen
if(!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'CSRF-Token ungültig']);
    exit;
}

$playerSkillId = filter_var($_POST['player_skill_id'] ?? 0, FILTER_VALIDATE_INT);

if(!$playerSkillId) {
    echo json_encode(['success' => false, 'message' => 'Ungültige ID']);
    exit;
}

$db = $app->getDB();
$player = $app->getPlayer()->getPlayerById($playerId);

// Player Skill laden
$playerSkill = $db->selectOne(
    "SELECT ps.*, s.name 
     FROM player_skills ps
     JOIN skills s ON ps.skill_id = s.id
     WHERE ps.id = :id AND ps.player_id = :player_id",
    [':id' => $playerSkillId, ':player_id' => $playerId]
);

if(!$playerSkill) {
    echo json_encode(['success' => false, 'message' => 'Skill nicht gefunden']);
    exit;
}

// Max Level Check
if($playerSkill['skill_level'] >= 10) {
    echo json_encode(['success' => false, 'message' => 'Maximales Level erreicht']);
    exit;
}

// Skill-Punkte Check (könnte man noch implementieren)
// Für jetzt: Kostet Gold
$upgradeCost = $playerSkill['skill_level'] * 100;

if($player['gold'] < $upgradeCost) {
    echo json_encode(['success' => false, 'message' => "Benötigt {$upgradeCost} Gold"]);
    exit;
}

try {
    $db->beginTransaction();
    
    // Gold abziehen
    $sql = "UPDATE players SET gold = gold - :cost WHERE id = :id";
    $db->update($sql, [':cost' => $upgradeCost, ':id' => $playerId]);
    
    // Skill Level erhöhen
    $sql = "UPDATE player_skills SET skill_level = skill_level + 1 WHERE id = :id";
    $db->update($sql, [':id' => $playerSkillId]);
    
    $db->commit();
    
    $newLevel = $playerSkill['skill_level'] + 1;
    
    $logger = new Logger('skills');
    $logger->info("Skill upgraded", [
        'player_id' => $playerId,
        'skill_name' => $playerSkill['name'],
        'new_level' => $newLevel
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => "'{$playerSkill['name']}' auf Level {$newLevel} verbessert!"
    ]);
    
} catch(Exception $e) {
    $db->rollback();
    
    echo json_encode(['success' => false, 'message' => 'Fehler beim Upgraden']);
}
?>