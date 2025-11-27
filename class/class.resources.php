<?php
class Resources {
    private $db;
    private $player;
    
    public function __construct($database, $playerClass) {
        $this->db = $database;
        $this->player = $playerClass;
    }
    
    // Ressourcen über Zeit generieren
    public function updateResources($playerId) {
        $playerData = $this->player->getPlayerById($playerId);
        
        if(!$playerData) {
            return false;
        }
        
        // Wenn noch nie geupdated wurde, jetzt setzen
        if(!$playerData['last_resource_update']) {
            $sql = "UPDATE players SET last_resource_update = NOW() WHERE id = :id";
            $this->db->update($sql, array(':id' => $playerId));
            return true;
        }
        
        // Zeit seit letztem Update berechnen (in Stunden)
        $lastUpdate = strtotime($playerData['last_resource_update']);
        $now = time();
        $hoursElapsed = ($now - $lastUpdate) / 3600;
        
        if($hoursElapsed < 0.01) { // Weniger als ~36 Sekunden
            return false; // Zu kurze Zeit
        }
        
        // Ressourcen berechnen
        $goldGained = floor($playerData['gold_production'] * $hoursElapsed);
        $foodGained = floor($playerData['food_production'] * $hoursElapsed);
        $woodGained = floor($playerData['wood_production'] * $hoursElapsed);
        $stoneGained = floor($playerData['stone_production'] * $hoursElapsed);
        
        // Neue Ressourcen (mit Kapazitäts-Limit)
        $newGold = min($playerData['gold'] + $goldGained, $playerData['gold_capacity']);
        $newFood = min($playerData['food'] + $foodGained, $playerData['food_capacity']);
        $newWood = min($playerData['wood'] + $woodGained, $playerData['wood_capacity']);
        $newStone = min($playerData['stone'] + $stoneGained, $playerData['stone_capacity']);
        
        // Update durchführen
        $sql = "UPDATE players SET 
                gold = :gold,
                food = :food,
                wood = :wood,
                stone = :stone,
                last_resource_update = NOW()
                WHERE id = :id";
        
        $this->db->update($sql, array(
            ':gold' => $newGold,
            ':food' => $newFood,
            ':wood' => $newWood,
            ':stone' => $newStone,
            ':id' => $playerId
        ));
        
        return array(
            'gold_gained' => $goldGained,
            'food_gained' => $foodGained,
            'wood_gained' => $woodGained,
            'stone_gained' => $stoneGained,
            'hours_elapsed' => round($hoursElapsed, 2)
        );
    }
    
    // Alle Ressourcen eines Spielers abrufen (mit Update)
    public function getPlayerResources($playerId) {
        // Erst Ressourcen updaten
        $this->updateResources($playerId);
        
        $player = $this->player->getPlayerById($playerId);
        
        if(!$player) {
            return false;
        }
        
        return array(
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
        );
    }
}
?>