<?php
// ============================================================================
// api/skill/use.php
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
    
    $playerSkill = $db->selectOne(
        "SELECT * FROM player_skills WHERE player_id = :pid AND skill_id = :sid",
        [':pid' => $playerId, ':sid' => $skillId]
    );
    
    if (!$playerSkill) {
        $api->error('Skill not learned', 400);
    }
    
    // Cooldown Check
    if ($playerSkill['last_used']) {
        $timeSinceUse = time() - strtotime($playerSkill['last_used']);
        if ($timeSinceUse < $skill['cooldown']) {
            $remaining = $skill['cooldown'] - $timeSinceUse;
            $api->error("Cooldown: {$remaining} seconds remaining", 400, [
                'cooldown_remaining' => $remaining
            ]);
        }
    }
    
    // Ressourcen-Checks
    if ($skill['mana_cost'] > 0 && $player['mana'] < $skill['mana_cost']) {
        $api->error('Not enough mana', 400);
    }
    
    if ($skill['stamina_cost'] > 0 && $player['stamina'] < $skill['stamina_cost']) {
        $api->error('Not enough stamina', 400);
    }
    
    $db->beginTransaction();
    
    // Ressourcen abziehen
    if ($skill['mana_cost'] > 0) {
        $sql = "UPDATE players SET mana = mana - :cost WHERE id = :id";
        $db->update($sql, [':cost' => $skill['mana_cost'], ':id' => $playerId]);
    }
    
    if ($skill['stamina_cost'] > 0) {
        $sql = "UPDATE players SET stamina = stamina - :cost WHERE id = :id";
        $db->update($sql, [':cost' => $skill['stamina_cost'], ':id' => $playerId]);
    }
    
    // Last used updaten
    $sql = "UPDATE player_skills SET last_used = NOW() WHERE id = :id";
    $db->update($sql, [':id' => $playerSkill['id']]);
    
    // Skill-Effekt
    $effectData = [];
    
    if ($skill['damage'] > 0) {
        $scalingStat = $player[$skill['scales_with']] ?? 0;
        $totalDamage = $skill['damage'] + ($scalingStat * $skill['scaling_factor']);
        $effectData['damage'] = $totalDamage;
    }
    
    if ($skill['heal'] > 0) {
        $sql = "UPDATE players SET hp = LEAST(hp + :heal, max_hp) WHERE id = :id";
        $db->update($sql, [':heal' => $skill['heal'], ':id' => $playerId]);
        $effectData['heal'] = $skill['heal'];
    }
    
    $db->commit();
    
    $response = $api->success($effectData, "'{$skill['name']}' used successfully");
    $api->sendResponse($response);
    
} catch (Exception $e) {
    $db->rollback();
    $api->error($e->getMessage(), 500);
}