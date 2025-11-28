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
    /**
     * Ressourcen sammeln (manuell)
     * 
     * @param int $playerId Player ID
     * @param string $resourceType Ressourcen-Typ (gold, food, wood, stone)
     * @param int $amount Menge
     * @return array Result array
     */
    public function gather($playerId, $resourceType, $amount = 10) {
        $logger = new Logger('resources');
        
        // Validierung
        $allowedTypes = ['gold', 'food', 'wood', 'stone'];
        if(!in_array($resourceType, $allowedTypes)) {
            return array('success' => false, 'message' => 'Ungültige Ressource');
        }
        
        // Spieler-Daten holen
        $playerData = $this->player->getPlayerById($playerId);
        
        if(!$playerData) {
            return array('success' => false, 'message' => 'Spieler nicht gefunden');
        }
        
        // Energie-Kosten (5 Energie pro 10 Ressourcen)
        $energyCost = ceil($amount / 10) * 5;
        
        // Prüfe ob genug Energie vorhanden
        if($playerData['energy'] < $energyCost) {
            return array('success' => false, 'message' => 'Nicht genug Energie');
        }
        
        // Prüfe Kapazitäts-Limit
        $capacityKey = $resourceType . '_capacity';
        $currentAmount = $playerData[$resourceType];
        $maxCapacity = $playerData[$capacityKey];
        
        if($currentAmount >= $maxCapacity) {
            return array('success' => false, 'message' => 'Lager ist voll');
        }
        
        // Berechne tatsächlich gesammelte Menge (nicht über Kapazität)
        $actualAmount = min($amount, $maxCapacity - $currentAmount);
        
        // Ressourcen hinzufügen
        $sql = "UPDATE players 
                SET $resourceType = $resourceType + :amount, 
                    energy = energy - :energy_cost 
                WHERE id = :id";
        
        $result = $this->db->update($sql, array(
            ':amount' => $actualAmount,
            ':energy_cost' => $energyCost,
            ':id' => $playerId
        ));
        
        if($result !== false) {
            $logger->resourceChange($playerId, $resourceType, $actualAmount, 'Manual gathering');
            
            // Ressourcen-Namen für Anzeige
            $resourceNames = [
                'gold' => 'Gold',
                'food' => 'Nahrung',
                'wood' => 'Holz',
                'stone' => 'Stein'
            ];
            
            $message = '+' . $actualAmount . ' ' . $resourceNames[$resourceType] . ' gesammelt (-' . $energyCost . ' Energie)';
            
            if($actualAmount < $amount) {
                $message .= ' (Lager war fast voll)';
            }
            
            return array(
                'success' => true, 
                'message' => $message,
                'gathered' => $actualAmount,
                'energy_cost' => $energyCost
            );
        }
        
        return array('success' => false, 'message' => 'Fehler beim Sammeln');
    }
}
?>