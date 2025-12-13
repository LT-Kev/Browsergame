<?php
// ───────────────────────────────
// API Router mit Auth
// ───────────────────────────────

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../init.php';

use App\Core\App;

// App-Instanz
$app = App::getInstance();

// Auth-Instanz
$auth = $app->getAuth();

// Aktion aus URL
$action = $_GET['action'] ?? '';
$action = trim($action, '/');

// Endpunkt-Datei auflösen
$actionFile = __DIR__ . '/' . $action . '.php';

// Prüfen, ob Datei existiert
if (!$action || !file_exists($actionFile)) {
    http_response_code(404);
    echo json_encode([
        'error' => 'Unknown API endpoint',
        'action' => $action
    ]);
    exit;
}

// Optional: Prüfen ob Endpunkt Auth benötigt
// z.B. alle unter /player/ oder /battle/ erfordern Login
$requiresAuth = preg_match('#^(player|battle|inventory)/#', $action);

if ($requiresAuth && !$auth->isLoggedIn()) {
    http_response_code(401); // Unauthorized
    echo json_encode([
        'error' => 'Unauthorized',
        'message' => 'Login required'
    ]);
    exit;
}

// Datei laden
require $actionFile;
exit;
