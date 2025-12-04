<?php
// ============================================================================
// app/Services/ResourceService.php
// ============================================================================

namespace App\Services;

use App\Core\Database;

class ResourceService {
    private Database $db;
    private PlayerService $player;

    public function __construct(Database $db, PlayerService $player) {
        $this->db = $db;
        $this->player = $player;
    }

    public function updateResources(int $playerId): void {
        $player = $this->player->getPlayerById($playerId);
        if(!$player) return;

        if(!$player['last_resource_update']) {
            $sql = "UPDATE players SET last_resource_update = NOW() WHERE id = :id";
            $this->db->update($sql, [':id' => $playerId]);
            return;
        }

        $lastUpdate = strtotime($player['last_resource_update']);
        $now = time();
        $hoursElapsed = ($now - $lastUpdate) / 3600;

        if($hoursElapsed < 0.01) return;

        $goldGained = floor($player['gold_production'] * $hoursElapsed);
        $foodGained = floor($player['food_production'] * $hoursElapsed);
        $woodGained = floor($player['wood_production'] * $hoursElapsed);
        $stoneGained = floor($player['stone_production'] * $hoursElapsed);

        $newGold = min($player['gold'] + $goldGained, $player['gold_capacity']);
        $newFood = min($player['food'] + $foodGained, $player['food_capacity']);
        $newWood = min($player['wood'] + $woodGained, $player['wood_capacity']);
        $newStone = min($player['stone'] + $stoneGained, $player['stone_capacity']);

        $sql = "UPDATE players SET gold = :gold, food = :food, wood = :wood, stone = :stone, last_resource_update = NOW() WHERE id = :id";
        $this->db->update($sql, [':gold' => $newGold, ':food' => $newFood, ':wood' => $newWood, ':stone' => $newStone, ':id' => $playerId]);
    }

    public function getPlayerResources(int $playerId): ?array {
        $this->updateResources($playerId);
        
        $player = $this->player->getPlayerById($playerId);
        
        if(!$player) {
            return null;
        }
        
        return [
            'gold' => $player['gold'],
            'food' => $player['food'],
            'wood' => $player['wood'],
            'stone' => $player['stone'],
            'gold_capacity' => $player['gold_capacity'],
            'food_capacity' => $player['food_capacity'],
            'wood_capacity' => $player['wood_capacity'],
            'stone_capacity' => $player['stone_capacity'],
            'gold_production' => $player['gold_production'],
            'food_production' => $player['food_production'],
            'wood_production' => $player['wood_production'],
            'stone_production' => $player['stone_production']
        ];
    }

    public function gather(int $playerId, string $resourceType, int $amount = 10): array {
        $allowedResources = ['gold', 'food', 'wood', 'stone'];
        if(!in_array($resourceType, $allowedResources)) {
            return ['success' => false, 'message' => 'Ungültige Ressource'];
        }

        $player = $this->player->getPlayerById($playerId);
        if(!$player) return ['success' => false, 'message' => 'Spieler nicht gefunden'];

        $energyCost = ceil($amount / 10) * 5;
        if($player['energy'] < $energyCost) {
            return ['success' => false, 'message' => 'Nicht genug Energie'];
        }

        $capacityKey = $resourceType . '_capacity';
        if($player[$resourceType] >= $player[$capacityKey]) {
            return ['success' => false, 'message' => 'Lager ist voll'];
        }

        $actualAmount = min($amount, $player[$capacityKey] - $player[$resourceType]);

        $sql = "UPDATE players SET $resourceType = $resourceType + :amount, energy = energy - :energy_cost WHERE id = :id";
        $this->db->update($sql, [':amount' => $actualAmount, ':energy_cost' => $energyCost, ':id' => $playerId]);

        $message = '+' . $actualAmount . ' ' . $resourceType . ' gesammelt (-' . $energyCost . ' Energie)';
        return ['success' => true, 'message' => $message, 'gathered' => $actualAmount, 'energy_cost' => $energyCost];
    }
}