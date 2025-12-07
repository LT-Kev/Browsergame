<?php
// ============================================================================
// ajax/distribute_stat.php
/**
 * Statuspunkt verteilen (während des Spiels)
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


?>