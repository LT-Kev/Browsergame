<?php
// ============================================================================
// ajax/travel_to_location.php - Travel AJAX Handler
// ============================================================================

require_once __DIR__ . '/../init.php';

use App\Core\App;
use App\Helpers\CSRF;

header('Content-Type: application/json');

// CSRF Check
if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
    echo json_encode([
        'success' => false,
        'message' => 'Ungültiges Token'
    ]);
    exit;
}

$app = App::getInstance();
$auth = $app->getAuth();
$playerId = $auth->getCurrentPlayerId();

if (!$playerId) {
    echo json_encode([
        'success' => false,
        'message' => 'Nicht eingeloggt'
    ]);
    exit;
}

try {
    $locationId = filter_input(INPUT_POST, 'location_id', FILTER_VALIDATE_INT);
    $targetX = filter_input(INPUT_POST, 'x', FILTER_VALIDATE_INT);
    $targetY = filter_input(INPUT_POST, 'y', FILTER_VALIDATE_INT);
    
    if ($locationId === false || $targetX === false || $targetY === false) {
        throw new Exception('Ungültige Daten');
    }
    
    if ($targetX < 0 || $targetX >= 1000 || $targetY < 0 || $targetY >= 1000) {
        throw new Exception('Koordinaten außerhalb der Welt');
    }
    
    $db = $app->getDB();
    $player = $app->getPlayer()->getPlayerById($playerId);
    
    // Entfernung berechnen
    $distance = sqrt(
        pow($targetX - $player['world_x'], 2) + 
        pow($targetY - $player['world_y'], 2)
    );
    
    // Energie-Kosten (1 Energie pro 10 Felder, minimum 1)
    $energyCost = max(1, ceil($distance / 10));
    
    if ($player['energy'] < $energyCost) {
        throw new Exception('Nicht genug Energie für die Reise');
    }
    
    // Position & Energie aktualisieren
    $stmt = $db->prepare("
        UPDATE players 
        SET world_x = :x, 
            world_y = :y, 
            energy = energy - :energy_cost,
            last_travel = NOW()
        WHERE id = :player_id
    ");
    
    $stmt->execute([
        ':x' => $targetX,
        ':y' => $targetY,
        ':energy_cost' => $energyCost,
        ':player_id' => $playerId
    ]);
    
    // Log erstellen
    if (LOG_ENABLED) {
        $logger = new App\Core\Logger('travel');
        $logger->info("Player traveled to location", [
            'player_id' => $playerId,
            'location_id' => $locationId,
            'from' => [$player['world_x'], $player['world_y']],
            'to' => [$targetX, $targetY],
            'distance' => round($distance, 2),
            'energy_cost' => $energyCost
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Reise erfolgreich',
        'data' => [
            'new_x' => $targetX,
            'new_y' => $targetY,
            'energy_cost' => $energyCost,
            'remaining_energy' => $player['energy'] - $energyCost
        ]
    ]);
    
} catch (Exception $e) {
    if (LOG_ENABLED) {
        $logger = new App\Core\Logger('travel');
        $logger->error("Travel failed", [
            'player_id' => $playerId ?? null,
            'error' => $e->getMessage()
        ]);
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}