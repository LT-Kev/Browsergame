<?php
/**
 * POST /api/player/travel
 * Spieler zu neuer Position bewegen
 */

use App\Core\App;
use App\Api\ApiResponse;
use App\Helpers\CSRF;

require_once __DIR__ . '/../../init.php';

$app = App::getInstance();
$api = new ApiResponse($app);

// Nur POST erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $api->error('Method not allowed', 405);
}

// CSRF Check
if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
    $api->error('Invalid CSRF token', 403);
}

$playerId = $app->getAuth()->getCurrentPlayerId();

try {
    $locationId = filter_input(INPUT_POST, 'location_id', FILTER_VALIDATE_INT);
    $targetX = filter_input(INPUT_POST, 'x', FILTER_VALIDATE_INT);
    $targetY = filter_input(INPUT_POST, 'y', FILTER_VALIDATE_INT);
    
    if ($locationId === false || $targetX === false || $targetY === false) {
        $api->error('Invalid data', 400);
    }
    
    if ($targetX < 0 || $targetX >= 1000 || $targetY < 0 || $targetY >= 1000) {
        $api->error('Coordinates out of bounds', 400);
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
        $api->error('Not enough energy', 400, [
            'required' => $energyCost,
            'available' => $player['energy']
        ]);
    }
    
    // Position & Energie aktualisieren
    $sql = "UPDATE players 
            SET world_x = :x, 
                world_y = :y, 
                energy = energy - :energy_cost,
                last_travel = NOW()
            WHERE id = :player_id";
    
    $db->update($sql, [
        ':x' => $targetX,
        ':y' => $targetY,
        ':energy_cost' => $energyCost,
        ':player_id' => $playerId
    ]);
    
    $response = $api->success([
        'new_x' => $targetX,
        'new_y' => $targetY,
        'energy_cost' => $energyCost,
        'remaining_energy' => $player['energy'] - $energyCost,
        'distance' => round($distance, 2)
    ], 'Travel successful');
    
    $api->sendResponse($response);
    
} catch (Exception $e) {
    $api->error($e->getMessage(), 500);
}