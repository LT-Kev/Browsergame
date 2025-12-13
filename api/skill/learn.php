<?php
// ============================================================================
// api/skill/learn.php
// ============================================================================
use App\Core\App;
use App\Api\ApiResponse;
use App\Helpers\CSRF;

require_once __DIR__ . '/../../init.php';

$app = App::getInstance();
$api = new ApiResponse($app);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $api->error('Method not allowed', 405);
}

if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
    $api->error('Invalid CSRF token', 403);
}

$playerId = $app->getAuth()->getCurrentPlayerId();
$db = $app->getDB();

try {
    $skillId = filter_input(INPUT_POST, 'skill_id', FILTER_VALIDATE_INT);
    
    if (!$skillId) {
        $api->error('Invalid skill ID', 400);
    }
    
    $player = $app->getPlayer()->getPlayerById($playerId);
    $skill = $db->selectOne("SELECT * FROM skills WHERE id = :id", [':id' => $skillId]);
    
    if (!$skill) {
        $api->error('Skill not found', 404);
    }
    
    // Prüfe ob bereits gelernt
    $hasSkill = $db->selectOne(
        "SELECT * FROM player_skills WHERE player_id = :pid AND skill_id = :sid",
        [':pid' => $playerId, ':sid' => $skillId]
    );
    
    if ($hasSkill) {
        $api->error('Skill already learned', 400);
    }
    
    // Level-Check
    if ($player['level'] < $skill['required_level']) {
        $api->error("Level {$skill['required_level']} required", 400);
    }
    
    // Skill lernen
    $sql = "INSERT INTO player_skills (player_id, skill_id, skill_level)
            VALUES (:player_id, :skill_id, 1)";
    
    $db->insert($sql, [
        ':player_id' => $playerId,
        ':skill_id' => $skillId
    ]);
    
    $response = $api->success(
        ['skill_name' => $skill['name']], 
        "Skill '{$skill['name']}' learned"
    );
    $api->sendResponse($response);
    
} catch (Exception $e) {
    $api->error($e->getMessage(), 500);
}