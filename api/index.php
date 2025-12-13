<?php
// ============================================================================
// api/index.php - API ROUTER MIT ERWEITERTEN AUTH-GRUPPEN
// ============================================================================

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
        'success' => false,
        'error' => 'Unknown API endpoint',
        'action' => $action,
        'available_endpoints' => [
            'player' => [
                'GET /api/player/get_data',
                'GET /api/player/get_stats',
                'GET /api/player/get_location',
                'GET /api/player/get_admin',
                'GET /api/player/get_race',
                'GET /api/player/get_class',
                'POST /api/player/travel',
                'POST /api/player/distribute_stat'
            ],
            'character' => [
                'POST /api/character/create',
                'GET /api/character/get_races',
                'GET /api/character/get_classes'
            ],
            'resource' => [
                'POST /api/resource/gather'
            ],
            'building' => [
                'POST /api/building/upgrade',
                'POST /api/building/cancel_upgrade'
            ],
            'class' => [
                'POST /api/class/learn',
                'POST /api/class/switch'
            ],
            'skill' => [
                'POST /api/skill/learn',
                'POST /api/skill/upgrade',
                'POST /api/skill/use'
            ],
            'battle' => [
                'GET /api/battle/get_data?id=X'
            ]
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

// Auth-Gruppen definieren (alle diese Gruppen benötigen Login)
$authRequiredGroups = [
    'player',
    'battle',
    'inventory',
    'character',
    'resource',
    'building',
    'class',
    'skill'
];

// Prüfen ob Endpunkt Auth benötigt
$requiresAuth = false;
foreach ($authRequiredGroups as $group) {
    if (str_starts_with($action, $group . '/')) {
        $requiresAuth = true;
        break;
    }
}

// Auth-Check
if ($requiresAuth && !$auth->isLoggedIn()) {
    http_response_code(401); // Unauthorized
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized',
        'message' => 'Login required',
        'timestamp' => time()
    ]);
    exit;
}

// Session Timeout Check (nur für eingeloggte User)
if ($auth->isLoggedIn()) {
    if ($auth->checkSessionTimeout()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Session timeout',
            'message' => 'Your session has expired',
            'timestamp' => time()
        ]);
        exit;
    }
    
    // Session validieren (Hijacking Protection)
    if (!$auth->validateSession()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Session invalid',
            'message' => 'Session validation failed',
            'timestamp' => time()
        ]);
        exit;
    }
}

// Logging (optional)
if (LOG_ENABLED && defined('LOG_LEVEL') && LOG_LEVEL === 'DEBUG') {
    $logger = new \App\Core\Logger('api_router');
    $logger->debug("API Request", [
        'endpoint' => $action,
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
        'player_id' => $auth->getCurrentPlayerId() ?? null,
        'ip' => \App\Core\RateLimiter::getClientIp()
    ]);
}

// Datei laden
require $actionFile;
exit;