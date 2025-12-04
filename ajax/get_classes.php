<?php
// ============================================================================
// ajax/get_classes.php
/**
 * Lade alle Starter-Klassen
 */
require_once '../init.php';

use App\Core\App;

$app = App::getInstance();
$playerId = $app->getAuth()->getCurrentPlayerId();

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

