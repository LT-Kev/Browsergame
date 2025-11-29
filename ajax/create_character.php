<?php
// ajax/create_character.php
/**
 * Character Creation - Backend Handler
 */
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$app = new App();
$auth = $app->getAuth();

if(!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}

$playerId = $auth->getCurrentPlayerId();
$player = $app->getPlayer()->getPlayerById($playerId);

// Prüfe ob Character bereits erstellt
if($player['character_created']) {
    echo json_encode(['success' => false, 'message' => 'Charakter bereits erstellt']);
    exit;
}

// Input validieren
$data = json_decode(file_get_contents('php://input'), true);

$raceId = filter_var($data['race']['id'] ?? 0, FILTER_VALIDATE_INT);
$classId = filter_var($data['class']['id'] ?? 0, FILTER_VALIDATE_INT);
$stats = $data['stats'] ?? [];

if(!$raceId || !$classId) {
    echo json_encode(['success' => false, 'message' => 'Ungültige Daten']);
    exit;
}

// Validiere Stats
$allowedStats = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
$totalBonusPoints = 0;

foreach($stats as $stat => $value) {
    if(!in_array($stat, $allowedStats)) {
        echo json_encode(['success' => false, 'message' => 'Ungültiger Stat: ' . $stat]);
        exit;
    }
    
    $totalBonusPoints += intval($value);
}

// Max 10 Bonus-Punkte erlaubt
if($totalBonusPoints > 10) {
    echo json_encode(['success' => false, 'message' => 'Zu viele Statuspunkte verteilt']);
    exit;
}

// Lade Rasse & Klasse
$db = $app->getDB();
$race = $db->selectOne("SELECT * FROM races WHERE id = :id AND is_playable = 1", [':id' => $raceId]);
$class = $db->selectOne("SELECT * FROM classes WHERE id = :id AND is_starter_class = 1", [':id' => $classId]);

if(!$race || !$class) {
    echo json_encode(['success' => false, 'message' => 'Rasse oder Klasse nicht gefunden']);
    exit;
}

// Berechne finale Stats
$finalStats = [
    'strength' => $race['base_strength'] + intval($stats['strength'] ?? 0),
    'dexterity' => $race['base_dexterity'] + intval($stats['dexterity'] ?? 0),
    'constitution' => $race['base_constitution'] + intval($stats['constitution'] ?? 0),
    'intelligence' => $race['base_intelligence'] + intval($stats['intelligence'] ?? 0),
    'wisdom' => $race['base_wisdom'] + intval($stats['wisdom'] ?? 0),
    'charisma' => $race['base_charisma'] + intval($stats['charisma'] ?? 0),
];

// Berechne sekundäre Stats
$maxHP = round(($finalStats['constitution'] * 10) * $race['hp_modifier'] * $class['hp_modifier']) + 50;
$maxMana = round(($finalStats['intelligence'] * 10) * $race['mana_modifier'] * $class['mana_modifier']);
$maxStamina = round((($finalStats['constitution'] * 5) + ($finalStats['dexterity'] * 5)) * $race['stamina_modifier'] * $class['stamina_modifier']);

$attack = $finalStats['strength'] + round($finalStats['dexterity'] / 2);
$attack = round($attack * (1 + $class['attack_bonus']));

$defense = $finalStats['constitution'] + round($finalStats['dexterity'] / 3);
$defense = round($defense * (1 + $class['defense_bonus']));

try {
    $db->beginTransaction();
    
    // Update Player
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
    
    // Füge Klasse zu player_classes hinzu
    $sql = "INSERT INTO player_classes (player_id, class_id, is_active, learned_at)
            VALUES (:player_id, :class_id, 1, NOW())";
    
    $db->insert($sql, [
        ':player_id' => $playerId,
        ':class_id' => $classId
    ]);
    
    // Starter-Skills hinzufügen (optional)
    $starterSkills = $db->select("SELECT * FROM skills WHERE class_id = :class_id AND required_level = 1", 
                                  [':class_id' => $classId]);
    
    foreach($starterSkills as $skill) {
        $sql = "INSERT INTO player_skills (player_id, skill_id, skill_level)
                VALUES (:player_id, :skill_id, 1)";
        $db->insert($sql, [
            ':player_id' => $playerId,
            ':skill_id' => $skill['id']
        ]);
    }
    
    $db->commit();
    
    $logger = new Logger('character');
    $logger->info("Character created", [
        'player_id' => $playerId,
        'race' => $race['name'],
        'class' => $class['name'],
        'stats' => $finalStats
    ]);
    
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
    
    $logger = new Logger('character');
    $logger->error("Character creation failed", [
        'player_id' => $playerId,
        'error' => $e->getMessage()
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Erstellen des Charakters'
    ]);
}

// ============================================================================
// ajax/get_races.php
/**
 * Lade alle spielbaren Rassen
 */
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$app = new App();
$db = $app->getDB();

try {
    $sql = "SELECT * FROM races WHERE is_playable = 1 ORDER BY is_hybrid ASC, name ASC";
    $races = $db->select($sql);
    
    echo json_encode([
        'success' => true,
        'races' => $races
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Laden der Rassen'
    ]);
}

// ============================================================================
// ajax/get_classes.php
/**
 * Lade alle Starter-Klassen
 */
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$app = new App();
$db = $app->getDB();

try {
    $sql = "SELECT * FROM classes WHERE is_starter_class = 1 ORDER BY type, name";
    $classes = $db->select($sql);
    
    echo json_encode([
        'success' => true,
        'classes' => $classes
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Laden der Klassen'
    ]);
}

// ============================================================================
// ajax/distribute_stat.php
/**
 * Statuspunkt verteilen (während des Spiels)
 */
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

$statName = $_POST['stat'] ?? '';
$amount = filter_var($_POST['amount'] ?? 1, FILTER_VALIDATE_INT);

if(!$amount || $amount < 1) {
    echo json_encode(['success' => false, 'message' => 'Ungültige Menge']);
    exit;
}

// Stats-Klasse verwenden
$stats = new Stats($app->getDB(), $app->getPlayer());
$result = $stats->distributeStatPoint($playerId, $statName, $amount);

echo json_encode($result);

// ============================================================================
// ajax/learn_class.php
/**
 * Neue Klasse lernen
 */
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

$classId = filter_var($_POST['class_id'] ?? 0, FILTER_VALIDATE_INT);

if(!$classId) {
    echo json_encode(['success' => false, 'message' => 'Ungültige Klassen-ID']);
    exit;
}

// RPGClass verwenden
$rpgClass = new RPGClass($app->getDB());
$result = $rpgClass->learnClass($playerId, $classId);

echo json_encode($result);

// ============================================================================
// ajax/switch_class.php
/**
 * Aktive Klasse wechseln
 */
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

$classId = filter_var($_POST['class_id'] ?? 0, FILTER_VALIDATE_INT);

if(!$classId) {
    echo json_encode(['success' => false, 'message' => 'Ungültige Klassen-ID']);
    exit;
}

// RPGClass verwenden
//$rpgClass = new RPGClass($app->getDB());
//$result = $rpgClass->switchClass($playerId, $classId);

//echo json_encode($result);
?>