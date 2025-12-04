<?php
// ajax/get_classes.php
require_once '../init.php';

use App\Core\App;

$app = App::getInstance();
$playerId = $app->getAuth()->getCurrentPlayerId();

header('Content-Type: application/json');

try {
    $app = new App();
    $db = $app->getDB();
    
    $sql = "SELECT * FROM classes WHERE is_starter_class = 1 ORDER BY type, name";
    $classes = $db->select($sql);
    
    if($classes === false) {
        throw new Exception('Datenbank-Fehler beim Laden der Klassen');
    }
    
    echo json_encode([
        'success' => true,
        'classes' => $classes
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Laden der Klassen: ' . $e->getMessage()
    ]);
}
?>