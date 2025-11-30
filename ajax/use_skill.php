<?php
// ============================================================================
// ajax/use_skill.php
// ============================================================================
require_once __DIR__ . '/../init.php';

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

// Prüfe ob Spieler den Skill hat
$playerSkill = $db->selectOne(
    "SELECT * FROM player_skills WHERE player_id = :pid AND skill_id = :sid",
    [':pid' => $playerId, ':sid' => $skillId]
);

if(!$playerSkill) {
    echo json_encode(['success' => false, 'message' => 'Skill nicht gelernt']);
    exit;
}

// Prüfe Cooldown
if($playerSkill['last_used']) {
    $timeSinceUse = time() - strtotime($playerSkill['last_used']);
    if($timeSinceUse < $skill['cooldown']) {
        $remaining = $skill['cooldown'] - $timeSinceUse;
        echo json_encode([
            'success' => false,
            'message' => "Noch {$remaining} Sekunden Cooldown"
        ]);
        exit;
    }
}

// Prüfe Mana
if($skill['mana_cost'] > 0 && $player['mana'] < $skill['mana_cost']) {
    echo json_encode(['success' => false, 'message' => 'Nicht genug Mana']);
    exit;
}

// Prüfe Stamina
if($skill['stamina_cost'] > 0 && $player['stamina'] < $skill['stamina_cost']) {
    echo json_encode(['success' => false, 'message' => 'Nicht genug Ausdauer']);
    exit;
}

try {
    $db->beginTransaction();
    
    // Ressourcen abziehen
    if($skill['mana_cost'] > 0) {
        $sql = "UPDATE players SET mana = mana - :cost WHERE id = :id";
        $db->update($sql, [':cost' => $skill['mana_cost'], ':id' => $playerId]);
    }
    
    if($skill['stamina_cost'] > 0) {
        $sql = "UPDATE players SET stamina = stamina - :cost WHERE id = :id";
        $db->update($sql, [':cost' => $skill['stamina_cost'], ':id' => $playerId]);
    }
    
    // Last used updaten
    $sql = "UPDATE player_skills SET last_used = NOW() WHERE id = :id";
    $db->update($sql, [':id' => $playerSkill['id']]);
    
    // Skill-Effekt anwenden
    $effectMessage = '';
    
    if($skill['damage'] > 0) {
        // Schaden berechnen (mit Skalierung)
        $scalingStat = $player[$skill['scales_with']] ?? 0;
        $totalDamage = $skill['damage'] + ($scalingStat * $skill['scaling_factor']);
        $effectMessage = "Verursacht {$totalDamage} Schaden!";
        
        // Hier würde der Schaden an ein Target appliziert werden
        // Für jetzt nur Meldung
    }
    
    if($skill['heal'] > 0) {
        // Heilung anwenden
        $sql = "UPDATE players SET hp = LEAST(hp + :heal, max_hp) WHERE id = :id";
        $db->update($sql, [':heal' => $skill['heal'], ':id' => $playerId]);
        $effectMessage = "Heilt {$skill['heal']} HP!";
    }
    
    $db->commit();
    
    $logger = new Logger('skills');
    $logger->info("Skill used", [
        'player_id' => $playerId,
        'skill_name' => $skill['name'],
        'effect' => $effectMessage
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => "'{$skill['name']}' verwendet! {$effectMessage}"
    ]);
    
} catch(Exception $e) {
    $db->rollback();
    
    echo json_encode(['success' => false, 'message' => 'Fehler beim Nutzen']);
}