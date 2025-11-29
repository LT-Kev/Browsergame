<?php
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