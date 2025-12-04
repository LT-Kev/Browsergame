<?php
// ajax/get_races.php
require_once '../init.php';

use App\Core\App;

$app = App::getInstance();
$playerId = $app->getAuth()->getCurrentPlayerId();

header('Content-Type: application/json');

try {
    $app = new App();
    $db = $app->getDB();
    
    $sql = "SELECT * FROM races WHERE is_playable = 1 ORDER BY is_hybrid ASC, name ASC";
    $races = $db->select($sql);
    
    if($races === false) {
        throw new Exception('Datenbank-Fehler beim Laden der Rassen');
    }
    
    echo json_encode([
        'success' => true,
        'races' => $races
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Laden der Rassen: ' . $e->getMessage()
    ]);
}
?>