<<<<<<< HEAD
<?php
class Building {
    private $db;
    private $player;
    
    public function __construct($database, $playerClass) {
        $this->db = $database;
        $this->player = $playerClass;
    }
    
    // Alle Gebäude-Typen holen
    public function getAllBuildingTypes() {
        $sql = "SELECT * FROM building_types ORDER BY type, name";
        return $this->db->select($sql);
    }
    
    // Gebäude-Typ nach ID
    public function getBuildingTypeById($buildingTypeId) {
        $sql = "SELECT * FROM building_types WHERE id = :id LIMIT 1";
        return $this->db->selectOne($sql, array(':id' => $buildingTypeId));
    }
    
    // Alle Gebäude eines Spielers
    public function getPlayerBuildings($playerId) {
        $sql = "SELECT pb.*, bt.name, bt.description, bt.type, bt.max_level,
                bt.produces_gold, bt.produces_food, bt.produces_wood, bt.produces_stone,
                bt.increases_gold_capacity, bt.increases_food_capacity, 
                bt.increases_wood_capacity, bt.increases_stone_capacity
                FROM player_buildings pb
                JOIN building_types bt ON pb.building_type_id = bt.id
                WHERE pb.player_id = :player_id
                ORDER BY bt.type, bt.name";
        
        return $this->db->select($sql, array(':player_id' => $playerId));
    }
    
    // Einzelnes Gebäude eines Spielers
    public function getPlayerBuilding($playerId, $buildingTypeId) {
        $sql = "SELECT pb.*, bt.* 
                FROM player_buildings pb
                JOIN building_types bt ON pb.building_type_id = bt.id
                WHERE pb.player_id = :player_id AND pb.building_type_id = :building_type_id
                LIMIT 1";
        
        return $this->db->selectOne($sql, array(
            ':player_id' => $playerId,
            ':building_type_id' => $buildingTypeId
        ));
    }
    
    // Upgrade-Kosten berechnen
    public function getUpgradeCost($buildingTypeId, $currentLevel) {
        $buildingType = $this->getBuildingTypeById($buildingTypeId);
        
        if(!$buildingType) {
            return false;
        }
        
        $nextLevel = $currentLevel + 1;
        $multiplier = pow($buildingType['cost_multiplier'], $currentLevel);
        
        return array(
            'gold' => ceil($buildingType['base_gold_cost'] * $multiplier),
            'food' => ceil($buildingType['base_food_cost'] * $multiplier),
            'wood' => ceil($buildingType['base_wood_cost'] * $multiplier),
            'stone' => ceil($buildingType['base_stone_cost'] * $multiplier),
            'time' => ceil($buildingType['base_build_time'] * $multiplier)
        );
    }
    
    // Gebäude upgraden
    public function upgradeBuilding($playerId, $buildingTypeId) {
        $logger = new Logger('building');
        
        $building = $this->getPlayerBuilding($playerId, $buildingTypeId);
        
        if(!$building) {
            $logger->warning("Upgrade failed: Building not found", array('player_id' => $playerId, 'building_type_id' => $buildingTypeId));
            return array('success' => false, 'message' => 'Gebäude nicht gefunden');
        }
        
        if($building['is_upgrading']) {
            $logger->warning("Upgrade failed: Already upgrading", array('player_id' => $playerId, 'building' => $building['name']));
            return array('success' => false, 'message' => 'Gebäude wird bereits ausgebaut');
        }
        
        if($building['level'] >= $building['max_level']) {
            $logger->info("Upgrade failed: Max level reached", array('player_id' => $playerId, 'building' => $building['name']));
            return array('success' => false, 'message' => 'Maximales Level erreicht');
        }
        
        if($buildingTypeId != 1) {
            $mainBuilding = $this->getPlayerBuilding($playerId, 1);
            if($building['level'] >= $mainBuilding['level']) {
                $logger->warning("Upgrade failed: Main building level too low", array('player_id' => $playerId, 'building' => $building['name']));
                return array('success' => false, 'message' => 'Hauptgebäude muss zuerst ausgebaut werden');
            }
        }
        
        $costs = $this->getUpgradeCost($buildingTypeId, $building['level']);
        
        if(!$this->player->hasEnoughResources($playerId, $costs)) {
            $logger->warning("Upgrade failed: Not enough resources", array(
                'player_id' => $playerId, 
                'building' => $building['name'],
                'costs' => $costs
            ));
            return array('success' => false, 'message' => 'Nicht genug Ressourcen');
        }
        
        $this->player->updateResources($playerId, array(
            'gold' => -$costs['gold'],
            'food' => -$costs['food'],
            'wood' => -$costs['wood'],
            'stone' => -$costs['stone']
        ));
        
        $finishTime = date('Y-m-d H:i:s', time() + $costs['time']);
        
        $sql = "UPDATE player_buildings 
                SET is_upgrading = 1, upgrade_finish_time = :finish_time 
                WHERE player_id = :player_id AND building_type_id = :building_type_id";
        
        $this->db->update($sql, array(
            ':finish_time' => $finishTime,
            ':player_id' => $playerId,
            ':building_type_id' => $buildingTypeId
        ));
        
        $sql = "INSERT INTO upgrade_queue (player_id, building_id, start_time, finish_time, target_level)
                VALUES (:player_id, :building_id, NOW(), :finish_time, :target_level)";
        
        $this->db->insert($sql, array(
            ':player_id' => $playerId,
            ':building_id' => $building['id'],
            ':finish_time' => $finishTime,
            ':target_level' => $building['level'] + 1
        ));
        
        $logger->buildingUpgrade($playerId, $building['name'], $building['level'] + 1, 'started');
        $logger->info("Upgrade started", array(
            'player_id' => $playerId,
            'building' => $building['name'],
            'target_level' => $building['level'] + 1,
            'costs' => $costs,
            'finish_time' => $finishTime
        ));
        
        return array(
            'success' => true, 
            'message' => 'Upgrade gestartet',
            'finish_time' => $finishTime,
            'duration' => $costs['time']
        );
    }
    
    // Fertige Upgrades abschließen
    public function checkFinishedUpgrades($playerId) {
        $sql = "SELECT pb.*, uq.target_level, uq.id as queue_id
                FROM player_buildings pb
                JOIN upgrade_queue uq ON pb.id = uq.building_id
                WHERE pb.player_id = :player_id 
                AND pb.is_upgrading = 1 
                AND uq.finish_time <= NOW()";
        
        $finishedBuildings = $this->db->select($sql, array(':player_id' => $playerId));
        
        foreach($finishedBuildings as $building) {
            // Level erhöhen
            $sql = "UPDATE player_buildings 
                    SET level = :level, is_upgrading = 0, upgrade_finish_time = NULL 
                    WHERE id = :id";
            
            $this->db->update($sql, array(
                ':level' => $building['target_level'],
                ':id' => $building['id']
            ));
            
            // Aus Warteschlange entfernen
            $sql = "DELETE FROM upgrade_queue WHERE id = :id";
            $this->db->delete($sql, array(':id' => $building['queue_id']));
            
            // Produktion neu berechnen
            $this->updatePlayerProduction($playerId);
        }
        
        return count($finishedBuildings);
    }
    
    // Spieler-Produktion basierend auf Gebäuden neu berechnen
    public function updatePlayerProduction($playerId) {
        $buildings = $this->getPlayerBuildings($playerId);
        
        $goldProd = 10; // Basis-Produktion
        $foodProd = 10;
        $woodProd = 10;
        $stoneProd = 10;
        
        $goldCap = 1000;
        $foodCap = 1000;
        $woodCap = 1000;
        $stoneCap = 1000;
        
        foreach($buildings as $building) {
            // Produktion addieren (pro Level)
            $goldProd += $building['produces_gold'] * $building['level'];
            $foodProd += $building['produces_food'] * $building['level'];
            $woodProd += $building['produces_wood'] * $building['level'];
            $stoneProd += $building['produces_stone'] * $building['level'];
            
            // Kapazität addieren (pro Level)
            $goldCap += $building['increases_gold_capacity'] * $building['level'];
            $foodCap += $building['increases_food_capacity'] * $building['level'];
            $woodCap += $building['increases_wood_capacity'] * $building['level'];
            $stoneCap += $building['increases_stone_capacity'] * $building['level'];
        }
        
        // Spieler updaten
        $sql = "UPDATE players SET 
                gold_production = :gold_prod,
                food_production = :food_prod,
                wood_production = :wood_prod,
                stone_production = :stone_prod,
                gold_capacity = :gold_cap,
                food_capacity = :food_cap,
                wood_capacity = :wood_cap,
                stone_capacity = :stone_cap
                WHERE id = :player_id";
        
        return $this->db->update($sql, array(
            ':gold_prod' => $goldProd,
            ':food_prod' => $foodProd,
            ':wood_prod' => $woodProd,
            ':stone_prod' => $stoneProd,
            ':gold_cap' => $goldCap,
            ':food_cap' => $foodCap,
            ':wood_cap' => $woodCap,
            ':stone_cap' => $stoneCap,
            ':player_id' => $playerId
        ));
    }
    
    // Upgrade abbrechen
    public function cancelUpgrade($playerId, $buildingTypeId) {
        $building = $this->getPlayerBuilding($playerId, $buildingTypeId);
        
        if(!$building || !$building['is_upgrading']) {
            return array('success' => false, 'message' => 'Kein aktives Upgrade');
        }
        
        // Kosten zurückgeben (50%)
        $costs = $this->getUpgradeCost($buildingTypeId, $building['level']);
        
        $this->player->updateResources($playerId, array(
            'gold' => ceil($costs['gold'] * 0.5),
            'food' => ceil($costs['food'] * 0.5),
            'wood' => ceil($costs['wood'] * 0.5),
            'stone' => ceil($costs['stone'] * 0.5)
        ));
        
        // Upgrade zurücksetzen
        $sql = "UPDATE player_buildings 
                SET is_upgrading = 0, upgrade_finish_time = NULL 
                WHERE player_id = :player_id AND building_type_id = :building_type_id";
        
        $this->db->update($sql, array(
            ':player_id' => $playerId,
            ':building_type_id' => $buildingTypeId
        ));
        
        // Aus Warteschlange entfernen
        $sql = "DELETE FROM upgrade_queue WHERE building_id = :building_id";
        $this->db->delete($sql, array(':building_id' => $building['id']));
        
        return array('success' => true, 'message' => 'Upgrade abgebrochen, 50% der Ressourcen zurückerstattet');
    }
}
=======
<?php
class Building {
    private $db;
    private $player;
    
    public function __construct($database, $playerClass) {
        $this->db = $database;
        $this->player = $playerClass;
    }
    
    // Alle Gebäude-Typen holen
    public function getAllBuildingTypes() {
        $sql = "SELECT * FROM building_types ORDER BY type, name";
        return $this->db->select($sql);
    }
    
    // Gebäude-Typ nach ID
    public function getBuildingTypeById($buildingTypeId) {
        $sql = "SELECT * FROM building_types WHERE id = :id LIMIT 1";
        return $this->db->selectOne($sql, array(':id' => $buildingTypeId));
    }
    
    // Alle Gebäude eines Spielers
    public function getPlayerBuildings($playerId) {
        $sql = "SELECT pb.*, bt.name, bt.description, bt.type, bt.max_level,
                bt.produces_gold, bt.produces_food, bt.produces_wood, bt.produces_stone,
                bt.increases_gold_capacity, bt.increases_food_capacity, 
                bt.increases_wood_capacity, bt.increases_stone_capacity
                FROM player_buildings pb
                JOIN building_types bt ON pb.building_type_id = bt.id
                WHERE pb.player_id = :player_id
                ORDER BY bt.type, bt.name";
        
        return $this->db->select($sql, array(':player_id' => $playerId));
    }
    
    // Einzelnes Gebäude eines Spielers
    public function getPlayerBuilding($playerId, $buildingTypeId) {
        $sql = "SELECT pb.*, bt.* 
                FROM player_buildings pb
                JOIN building_types bt ON pb.building_type_id = bt.id
                WHERE pb.player_id = :player_id AND pb.building_type_id = :building_type_id
                LIMIT 1";
        
        return $this->db->selectOne($sql, array(
            ':player_id' => $playerId,
            ':building_type_id' => $buildingTypeId
        ));
    }
    
    // Upgrade-Kosten berechnen
    public function getUpgradeCost($buildingTypeId, $currentLevel) {
        $buildingType = $this->getBuildingTypeById($buildingTypeId);
        
        if(!$buildingType) {
            return false;
        }
        
        $nextLevel = $currentLevel + 1;
        $multiplier = pow($buildingType['cost_multiplier'], $currentLevel);
        
        return array(
            'gold' => ceil($buildingType['base_gold_cost'] * $multiplier),
            'food' => ceil($buildingType['base_food_cost'] * $multiplier),
            'wood' => ceil($buildingType['base_wood_cost'] * $multiplier),
            'stone' => ceil($buildingType['base_stone_cost'] * $multiplier),
            'time' => ceil($buildingType['base_build_time'] * $multiplier)
        );
    }
    
    // Gebäude upgraden
    public function upgradeBuilding($playerId, $buildingTypeId) {
        $logger = new Logger('building');
        
        $building = $this->getPlayerBuilding($playerId, $buildingTypeId);
        
        if(!$building) {
            $logger->warning("Upgrade failed: Building not found", array('player_id' => $playerId, 'building_type_id' => $buildingTypeId));
            return array('success' => false, 'message' => 'Gebäude nicht gefunden');
        }
        
        if($building['is_upgrading']) {
            $logger->warning("Upgrade failed: Already upgrading", array('player_id' => $playerId, 'building' => $building['name']));
            return array('success' => false, 'message' => 'Gebäude wird bereits ausgebaut');
        }
        
        if($building['level'] >= $building['max_level']) {
            $logger->info("Upgrade failed: Max level reached", array('player_id' => $playerId, 'building' => $building['name']));
            return array('success' => false, 'message' => 'Maximales Level erreicht');
        }
        
        if($buildingTypeId != 1) {
            $mainBuilding = $this->getPlayerBuilding($playerId, 1);
            if($building['level'] >= $mainBuilding['level']) {
                $logger->warning("Upgrade failed: Main building level too low", array('player_id' => $playerId, 'building' => $building['name']));
                return array('success' => false, 'message' => 'Hauptgebäude muss zuerst ausgebaut werden');
            }
        }
        
        $costs = $this->getUpgradeCost($buildingTypeId, $building['level']);
        
        if(!$this->player->hasEnoughResources($playerId, $costs)) {
            $logger->warning("Upgrade failed: Not enough resources", array(
                'player_id' => $playerId, 
                'building' => $building['name'],
                'costs' => $costs
            ));
            return array('success' => false, 'message' => 'Nicht genug Ressourcen');
        }
        
        $this->player->updateResources($playerId, array(
            'gold' => -$costs['gold'],
            'food' => -$costs['food'],
            'wood' => -$costs['wood'],
            'stone' => -$costs['stone']
        ));
        
        $finishTime = date('Y-m-d H:i:s', time() + $costs['time']);
        
        $sql = "UPDATE player_buildings 
                SET is_upgrading = 1, upgrade_finish_time = :finish_time 
                WHERE player_id = :player_id AND building_type_id = :building_type_id";
        
        $this->db->update($sql, array(
            ':finish_time' => $finishTime,
            ':player_id' => $playerId,
            ':building_type_id' => $buildingTypeId
        ));
        
        $sql = "INSERT INTO upgrade_queue (player_id, building_id, start_time, finish_time, target_level)
                VALUES (:player_id, :building_id, NOW(), :finish_time, :target_level)";
        
        $this->db->insert($sql, array(
            ':player_id' => $playerId,
            ':building_id' => $building['id'],
            ':finish_time' => $finishTime,
            ':target_level' => $building['level'] + 1
        ));
        
        $logger->buildingUpgrade($playerId, $building['name'], $building['level'] + 1, 'started');
        $logger->info("Upgrade started", array(
            'player_id' => $playerId,
            'building' => $building['name'],
            'target_level' => $building['level'] + 1,
            'costs' => $costs,
            'finish_time' => $finishTime
        ));
        
        return array(
            'success' => true, 
            'message' => 'Upgrade gestartet',
            'finish_time' => $finishTime,
            'duration' => $costs['time']
        );
    }
    
    // Fertige Upgrades abschließen
    public function checkFinishedUpgrades($playerId) {
        $sql = "SELECT pb.*, uq.target_level, uq.id as queue_id
                FROM player_buildings pb
                JOIN upgrade_queue uq ON pb.id = uq.building_id
                WHERE pb.player_id = :player_id 
                AND pb.is_upgrading = 1 
                AND uq.finish_time <= NOW()";
        
        $finishedBuildings = $this->db->select($sql, array(':player_id' => $playerId));
        
        foreach($finishedBuildings as $building) {
            // Level erhöhen
            $sql = "UPDATE player_buildings 
                    SET level = :level, is_upgrading = 0, upgrade_finish_time = NULL 
                    WHERE id = :id";
            
            $this->db->update($sql, array(
                ':level' => $building['target_level'],
                ':id' => $building['id']
            ));
            
            // Aus Warteschlange entfernen
            $sql = "DELETE FROM upgrade_queue WHERE id = :id";
            $this->db->delete($sql, array(':id' => $building['queue_id']));
            
            // Produktion neu berechnen
            $this->updatePlayerProduction($playerId);
        }
        
        return count($finishedBuildings);
    }
    
    // Spieler-Produktion basierend auf Gebäuden neu berechnen
    public function updatePlayerProduction($playerId) {
        $buildings = $this->getPlayerBuildings($playerId);
        
        $goldProd = 10; // Basis-Produktion
        $foodProd = 10;
        $woodProd = 10;
        $stoneProd = 10;
        
        $goldCap = 1000;
        $foodCap = 1000;
        $woodCap = 1000;
        $stoneCap = 1000;
        
        foreach($buildings as $building) {
            // Produktion addieren (pro Level)
            $goldProd += $building['produces_gold'] * $building['level'];
            $foodProd += $building['produces_food'] * $building['level'];
            $woodProd += $building['produces_wood'] * $building['level'];
            $stoneProd += $building['produces_stone'] * $building['level'];
            
            // Kapazität addieren (pro Level)
            $goldCap += $building['increases_gold_capacity'] * $building['level'];
            $foodCap += $building['increases_food_capacity'] * $building['level'];
            $woodCap += $building['increases_wood_capacity'] * $building['level'];
            $stoneCap += $building['increases_stone_capacity'] * $building['level'];
        }
        
        // Spieler updaten
        $sql = "UPDATE players SET 
                gold_production = :gold_prod,
                food_production = :food_prod,
                wood_production = :wood_prod,
                stone_production = :stone_prod,
                gold_capacity = :gold_cap,
                food_capacity = :food_cap,
                wood_capacity = :wood_cap,
                stone_capacity = :stone_cap
                WHERE id = :player_id";
        
        return $this->db->update($sql, array(
            ':gold_prod' => $goldProd,
            ':food_prod' => $foodProd,
            ':wood_prod' => $woodProd,
            ':stone_prod' => $stoneProd,
            ':gold_cap' => $goldCap,
            ':food_cap' => $foodCap,
            ':wood_cap' => $woodCap,
            ':stone_cap' => $stoneCap,
            ':player_id' => $playerId
        ));
    }
    
    // Upgrade abbrechen
    public function cancelUpgrade($playerId, $buildingTypeId) {
        $building = $this->getPlayerBuilding($playerId, $buildingTypeId);
        
        if(!$building || !$building['is_upgrading']) {
            return array('success' => false, 'message' => 'Kein aktives Upgrade');
        }
        
        // Kosten zurückgeben (50%)
        $costs = $this->getUpgradeCost($buildingTypeId, $building['level']);
        
        $this->player->updateResources($playerId, array(
            'gold' => ceil($costs['gold'] * 0.5),
            'food' => ceil($costs['food'] * 0.5),
            'wood' => ceil($costs['wood'] * 0.5),
            'stone' => ceil($costs['stone'] * 0.5)
        ));
        
        // Upgrade zurücksetzen
        $sql = "UPDATE player_buildings 
                SET is_upgrading = 0, upgrade_finish_time = NULL 
                WHERE player_id = :player_id AND building_type_id = :building_type_id";
        
        $this->db->update($sql, array(
            ':player_id' => $playerId,
            ':building_type_id' => $buildingTypeId
        ));
        
        // Aus Warteschlange entfernen
        $sql = "DELETE FROM upgrade_queue WHERE building_id = :building_id";
        $this->db->delete($sql, array(':building_id' => $building['id']));
        
        return array('success' => true, 'message' => 'Upgrade abgebrochen, 50% der Ressourcen zurückerstattet');
    }
}
>>>>>>> 971ab47689bd561bd08c6e4d77cea7f516414d66
?>