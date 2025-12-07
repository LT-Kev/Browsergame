<?php
// ajax/create_character.php
/**
 * Character Creation - Backend Handler
 */

require_once '../init.php';

use App\Core\App;

$app = App::getInstance();
$playerId = $app->getAuth()->getCurrentPlayerId();


header('Content-Type: application/json');

// Entfernt: $app bereits via getInstance()
$auth = $app->getAuth();

if(!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}

$playerId = $auth->getCurrentPlayerId();
$player = $app->getPlayer()->getPlayerById($playerId);

// Prüfe ob Charakter bereits erstellt
if($player['character_created']) {
    echo json_encode(['success' => false, 'message' => 'Charakter bereits erstellt']);
    exit;
}

// Input validieren
$data = json_decode(file_get_contents('php://input'), true);

$raceId = filter_var($data['race']['id'] ?? 0, FILTER_VALIDATE_INT);
$classId = filter_var($data['class']['id'] ?? 0, FILTER_VALIDATE_INT);

$stats = $data['stats'] ?? [];

// Grundvalidierung
if(!$raceId || !$classId) {
    echo json_encode(['success' => false, 'message' => 'Ungültige Daten']);
    exit;
}

// Statvalidierung (gehärtet)
$allowedStats = ['strength','dexterity','constitution','intelligence','wisdom','charisma'];
$totalBonus = 0;

foreach($allowedStats as $stat) {

    if(!isset($stats[$stat])) {
        echo json_encode(['success' => false, 'message' => 'Fehlender Stat: '.$stat]);
        exit;
    }

    $val = intval($stats[$stat]);

    if($val < 0 || $val > 10) {
        echo json_encode(['success' => false, 'message' => 'Ungültige Punkte für '.$stat]);
        exit;
    }

    $totalBonus += $val;
}

if($totalBonus > 10) {
    echo json_encode(['success' => false, 'message' => 'Zu viele Statuspunkte verteilt']);
    exit;
}

$db = $app->getDB();

// Rasse & Klasse laden
$race = $db->selectOne("SELECT * FROM races WHERE id = :id AND is_playable = 1", [':id' => $raceId]);
$class = $db->selectOne("SELECT * FROM classes WHERE id = :id AND is_starter_class = 1", [':id' => $classId]);

if(!$race || !$class) {
    echo json_encode(['success' => false, 'message' => 'Rasse oder Klasse nicht gefunden']);
    exit;
}

// Fallbacks für NULL vermeiden
$race['hp_modifier']     = $race['hp_modifier']     ?? 1;
$race['mana_modifier']   = $race['mana_modifier']   ?? 1;
$race['stamina_modifier']= $race['stamina_modifier']?? 1;

$class['hp_modifier']    = $class['hp_modifier']    ?? 1;
$class['mana_modifier']  = $class['mana_modifier']  ?? 1;
$class['stamina_modifier']= $class['stamina_modifier']?? 1;

$class['attack_bonus']   = $class['attack_bonus']   ?? 0;
$class['defense_bonus']  = $class['defense_bonus']  ?? 0;

// Finalstats berechnen
$finalStats = [
    'strength'      => $race['base_strength']      + $stats['strength'],
    'dexterity'     => $race['base_dexterity']     + $stats['dexterity'],
    'constitution'  => $race['base_constitution']  + $stats['constitution'],
    'intelligence'  => $race['base_intelligence']  + $stats['intelligence'],
    'wisdom'        => $race['base_wisdom']        + $stats['wisdom'],
    'charisma'      => $race['base_charisma']      + $stats['charisma']
];

// Sekundäre Werte
$maxHP      = round(($finalStats['constitution'] * 10) * $race['hp_modifier'] * $class['hp_modifier']) + 50;
$maxMana    = round(($finalStats['intelligence'] * 10) * $race['mana_modifier'] * $class['mana_modifier']);
$maxStamina = round((($finalStats['constitution'] * 5) + ($finalStats['dexterity'] * 5)) * $race['stamina_modifier'] * $class['stamina_modifier']);

$attack = round(($finalStats['strength'] + ($finalStats['dexterity'] / 2)) * (1 + $class['attack_bonus']));
$defense = round(($finalStats['constitution'] + ($finalStats['dexterity'] / 3)) * (1 + $class['defense_bonus']));

try {

    $db->beginTransaction();

    // Player aktualisieren
    $sql = "UPDATE players SET 
        race_id = :race_id,
        class_id = :class_id,
        strength = :strength,
        dexterity = :dexterity,
        constitution = :constitution,
        intelligence = :intelligence,
        wisdom = :wisdom,
        charisma = :charisma,
        hp = :hp,
        max_hp = :max_hp,
        mana = :mana,
        max_mana = :max_mana,
        stamina = :stamina,
        max_stamina = :max_stamina,
        attack = :attack,
        defense = :defense,
        character_created = 1
        WHERE id = :id";

    $db->update($sql, [
        ':race_id' => $raceId,
        ':class_id' => $classId,
        ':strength' => $finalStats['strength'],
        ':dexterity' => $finalStats['dexterity'],
        ':constitution' => $finalStats['constitution'],
        ':intelligence' => $finalStats['intelligence'],
        ':wisdom' => $finalStats['wisdom'],
        ':charisma' => $finalStats['charisma'],
        ':hp' => $maxHP,
        ':max_hp' => $maxHP,
        ':mana' => $maxMana,
        ':max_mana' => $maxMana,
        ':stamina' => $maxStamina,
        ':max_stamina' => $maxStamina,
        ':attack' => $attack,
        ':defense' => $defense,
        ':id' => $playerId
    ]);

    // Klasse eintragen
    $db->insert("INSERT INTO player_classes (player_id, class_id, is_active, learned_at)
                 VALUES (:player_id, :class_id, 1, NOW())",
                [':player_id'=>$playerId, ':class_id'=>$classId]);

    // Starter-Skills
    $starterSkills = $db->select(
        "SELECT id FROM skills WHERE class_id = :c AND required_level = 1",
        [':c'=>$classId]
    );

    foreach($starterSkills as $skill) {
        $db->insert(
            "INSERT INTO player_skills (player_id, skill_id, skill_level)
             VALUES (:p, :s, 1)",
            [':p'=>$playerId, ':s'=>$skill['id']]
        );
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Charakter erfolgreich erstellt!',
        'character' => [
            'race' => $race['name'],
            'class' => $class['name'],
            'stats' => $finalStats
        ]
    ]);

} catch(Exception $e) {

    $db->rollback();

    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Erstellen des Charakters'
    ]);
}
