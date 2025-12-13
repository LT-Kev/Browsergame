<?php
// ============================================================================
// api/skill/upgrade.php
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
    $playerSkillId = filter_input(INPUT_POST, 'player_skill_id', FILTER_VALIDATE_INT);
    
    if (!$playerSkillId) {
        $api->error('Invalid player skill ID', 400);
    }
    
    $player = $app->getPlayer()->getPlayerById($playerId);
    
    $playerSkill = $db->selectOne(
        "SELECT ps.*, s.name 
         FROM player_skills ps
         JOIN skills s ON ps.skill_id = s.id
         WHERE ps.id = :id AND ps.player_id = :player_id",
        [':id' => $playerSkillId, ':player_id' => $playerId]
    );
    
    if (!$playerSkill) {
        $api->error('Skill not found', 404);
    }
    
    if ($playerSkill['skill_level'] >= 10) {
        $api->error('Max level reached', 400);
    }
    
    $upgradeCost = $playerSkill['skill_level'] * 100;
    
    if ($player['gold'] < $upgradeCost) {
        $api->error("Requires {$upgradeCost} gold", 400);
    }
    
    $db->beginTransaction();
    
    $sql = "UPDATE players SET gold = gold - :cost WHERE id = :id";
    $db->update($sql, [':cost' => $upgradeCost, ':id' => $playerId]);
    
    $sql = "UPDATE player_skills SET skill_level = skill_level + 1 WHERE id = :id";
    $db->update($sql, [':id' => $playerSkillId]);
    
    $db->commit();
    
    $newLevel = $playerSkill['skill_level'] + 1;
    
    $response = $api->success(
        ['new_level' => $newLevel],
        "'{$playerSkill['name']}' upgraded to level {$newLevel}"
    );
    $api->sendResponse($response);
    
} catch (Exception $e) {
    $db->rollback();
    $api->error($e->getMessage(), 500);
}