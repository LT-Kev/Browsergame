<?php
// ============================================================================
// ajax/learn_skill.php
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

$skillId = filter_var($_POST['skill_id'] ?? 0, FILTER_VALIDATE_INT);

if(!$skillId) {
    echo json_encode(['success' => false, 'message' => 'Ungültige Skill-ID']);
    exit;
}

$db = $app->getDB();
$player = $app->getPlayer()->getPlayerById($playerId);

// Skill laden
$skill = $db->selectOne("SELECT * FROM skills WHERE id = :id", [':id' => $skillId]);

if(!$skill) {
    echo json_encode(['success' => false, 'message' => 'Skill nicht gefunden']);
    exit;
}

// Prüfe ob Spieler den Skill bereits hat
$hasSkill = $db->selectOne(
    "SELECT * FROM player_skills WHERE player_id = :pid AND skill_id = :sid",
    [':pid' => $playerId, ':sid' => $skillId]
);

if($hasSkill) {
    echo json_encode(['success' => false, 'message' => 'Skill bereits gelernt']);
    exit;
}

// Level-Check
if($player['level'] < $skill['required_level']) {
    echo json_encode(['success' => false, 'message' => "Level {$skill['required_level']} erforderlich"]);
    exit;
}

// Skill lernen
$sql = "INSERT INTO player_skills (player_id, skill_id, skill_level)
        VALUES (:player_id, :skill_id, 1)";

$result = $db->insert($sql, [
    ':player_id' => $playerId,
    ':skill_id' => $skillId
]);

if($result) {
    $logger = new Logger('skills');
    $logger->info("Skill learned", [
        'player_id' => $playerId,
        'skill_id' => $skillId,
        'skill_name' => $skill['name']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => "Fähigkeit '{$skill['name']}' gelernt!"
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Fehler beim Lernen']);
}