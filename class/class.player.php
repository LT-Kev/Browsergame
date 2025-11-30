<?php
class Player {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function getPlayerById($playerId) {
        $sql = "SELECT * FROM players WHERE id = :id LIMIT 1";
        return $this->db->selectOne($sql, array(':id' => $playerId));
    }
    
    public function getPlayerByUsername($username) {
        $sql = "SELECT * FROM players WHERE username = :username LIMIT 1";
        return $this->db->selectOne($sql, array(':username' => $username));
    }
    
    public function getPlayerByEmail($email) {
        $sql = "SELECT * FROM players WHERE email = :email LIMIT 1";
        return $this->db->selectOne($sql, array(':email' => $email));
    }
    
    public function createPlayer($username, $email, $password) {
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 2
        ]);
        
        // Fallback wenn Argon2 nicht verfügbar:
        if(!$passwordHash) {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        }
        
        $sql = "INSERT INTO players (username, email, password, created_at) 
                VALUES (:username, :email, :password, NOW())";
        
        $params = array(
            ':username' => $username,
            ':email' => $email,
            ':password' => $passwordHash
        );
        
        return $this->db->insert($sql, $params);
    }
    
    public function updatePlayerStats($playerId, $stats) {
        $sql = "UPDATE players SET 
                gold = :gold,
                energy = :energy,
                level = :level,
                exp = :exp,
                hp = :hp,
                attack = :attack,
                defense = :defense,
                last_login = NOW()
                WHERE id = :id";
        
        $params = array(
            ':gold' => $stats['gold'],
            ':energy' => $stats['energy'],
            ':level' => $stats['level'],
            ':exp' => $stats['exp'],
            ':hp' => $stats['hp'],
            ':attack' => $stats['attack'],
            ':defense' => $stats['defense'],
            ':id' => $playerId
        );
        
        return $this->db->update($sql, $params);
    }
    // Prüfen ob genug Ressourcen vorhanden sind
    public function hasEnoughResources($playerId, $costs) {
        $player = $this->getPlayerById($playerId);
        
        if(!$player) {
            return false;
        }
        
        if(isset($costs['gold']) && $player['gold'] < $costs['gold']) {
            return false;
        }
        if(isset($costs['food']) && $player['food'] < $costs['food']) {
            return false;
        }
        if(isset($costs['wood']) && $player['wood'] < $costs['wood']) {
            return false;
        }
        if(isset($costs['stone']) && $player['stone'] < $costs['stone']) {
            return false;
        }
        
        return true;
    }

    /**
     * Ressourcen eines Spielers aktualisieren (addieren/subtrahieren)
     * 
     * @param int $playerId Player ID
     * @param array $resources Associative array ['gold' => amount, 'food' => amount, ...]
     * @return bool Success
     */
    public function updateResources($playerId, $resources) {
        // Validierung: Nur erlaubte Ressourcen
        $allowed = ['gold', 'food', 'wood', 'stone'];
        $updates = [];
        $params = [':id' => $playerId];
        
        foreach($resources as $key => $value) {
            if(in_array($key, $allowed)) {
                // Ressource kann positiv (hinzufügen) oder negativ (abziehen) sein
                $updates[] = "$key = $key + :$key";
                $params[":$key"] = (int)$value;
            }
        }
        
        if(empty($updates)) {
            return false;
        }
        
        // SQL bauen und ausführen
        $sql = "UPDATE players SET " . implode(', ', $updates) . " WHERE id = :id";
        
        $result = $this->db->update($sql, $params);
        
        // Optional: Logging
        if($result !== false && class_exists('Logger')) {
            $logger = new Logger('resources');
            $logger->resourceChange($playerId, implode(',', array_keys($resources)), 
                                array_sum($resources), 'Manual resource update');
        }
        
        return $result !== false;
    }
    
    public function updateGold($playerId, $amount) {
        $sql = "UPDATE players SET gold = gold + :amount WHERE id = :id";
        return $this->db->update($sql, array(':amount' => $amount, ':id' => $playerId));
    }
    
    public function updateEnergy($playerId, $amount) {
        $sql = "UPDATE players SET energy = LEAST(energy + :amount, 100) WHERE id = :id";
        return $this->db->update($sql, array(':amount' => $amount, ':id' => $playerId));
    }
    
    public function addExp($playerId, $exp) {
        $player = $this->getPlayerById($playerId);
        $newExp = $player['exp'] + $exp;
        $newLevel = $player['level'];
        $expNeeded = $this->getExpNeeded($newLevel);
        
        $leveledUp = false;
        
        while($newExp >= $expNeeded) {
            $newExp -= $expNeeded;
            $newLevel++;
            $expNeeded = $this->getExpNeeded($newLevel);
            $leveledUp = true;
        }
        
        $sql = "UPDATE players SET exp = :exp, level = :level WHERE id = :id";
        $params = array(':exp' => $newExp, ':level' => $newLevel, ':id' => $playerId);
        
        $result = $this->db->update($sql, $params);
        
        // Wenn Level-Up UND Character erstellt, dann Stats erhöhen
        if($leveledUp && $player['character_created']) {
            // Stats-Klasse verwenden für Level-Up
            require_once __DIR__ . '/class.stats.php';
            $stats = new Stats($this->db, $this);
            $stats->onLevelUp($playerId);
            
            $logger = new Logger('level');
            $logger->info("Player leveled up", [
                'player_id' => $playerId,
                'new_level' => $newLevel,
                'username' => $player['username']
            ]);
        }
        
        return $result;
    }
    
    public function getExpNeeded($level) {
        return $level * 100;
    }
    
    public function login($username, $password) {
        $player = $this->getPlayerByUsername($username);
        
        if($player && password_verify($password, $player['password'])) {
            $sql = "UPDATE players SET last_login = NOW() WHERE id = :id";
            $this->db->update($sql, array(':id' => $player['id']));
            
            return $player;
        }
        
        return false;
    }
    
    public function getTopPlayers($limit = 10) {
        $sql = "SELECT id, username, level, exp, gold 
                FROM players 
                ORDER BY level DESC, exp DESC 
                LIMIT :limit";
        
        return $this->db->select($sql, array(':limit' => $limit));
    }
    
    public function updateHP($playerId, $amount) {
        $player = $this->getPlayerById($playerId);
        $newHP = max(0, min($player['hp'] + $amount, $player['max_hp']));
        
        $sql = "UPDATE players SET hp = :hp WHERE id = :id";
        return $this->db->update($sql, array(':hp' => $newHP, ':id' => $playerId));
    }

    public function createPlayerWithBuildings($username, $email, $password) {
        

        // Spieler erstellen
        $playerId = $this->createPlayer($username, $email, $password);
        
        if(!$playerId) {
            return false;
        }
        
        // Startgebäude hinzufügen
        $startBuildings = [
            1 => 1,  // Hauptgebäude Level 1
            2 => 1,  // Goldmine Level 1
            3 => 1,  // Bauernhof Level 1
            4 => 1,  // Holzfäller Level 1
            5 => 1,  // Steinbruch Level 1
            6 => 1   // Lager Level 1
        ];
        
        foreach($startBuildings as $buildingTypeId => $level) {
            $sql = "INSERT INTO player_buildings (player_id, building_type_id, level) 
                    VALUES (:player_id, :building_type_id, :level)";
            
            $this->db->insert($sql, array(
                ':player_id' => $playerId,
                ':building_type_id' => $buildingTypeId,
                ':level' => $level
            ));
        }
        
        // Produktion initial berechnen
        $this->updateInitialProduction($playerId);
        
        return $playerId;
    }

    private function updateInitialProduction($playerId) {
        // Basis-Produktion + Gebäude
        $sql = "UPDATE players SET 
                gold_production = 10 + (SELECT COALESCE(SUM(bt.produces_gold * pb.level), 0) FROM player_buildings pb JOIN building_types bt ON pb.building_type_id = bt.id WHERE pb.player_id = :player_id),
                food_production = 10 + (SELECT COALESCE(SUM(bt.produces_food * pb.level), 0) FROM player_buildings pb JOIN building_types bt ON pb.building_type_id = bt.id WHERE pb.player_id = :player_id2),
                wood_production = 10 + (SELECT COALESCE(SUM(bt.produces_wood * pb.level), 0) FROM player_buildings pb JOIN building_types bt ON pb.building_type_id = bt.id WHERE pb.player_id = :player_id3),
                stone_production = 10 + (SELECT COALESCE(SUM(bt.produces_stone * pb.level), 0) FROM player_buildings pb JOIN building_types bt ON pb.building_type_id = bt.id WHERE pb.player_id = :player_id4),
                last_resource_update = NOW()
                WHERE id = :player_id5";
        
        $this->db->update($sql, array(
            ':player_id' => $playerId,
            ':player_id2' => $playerId,
            ':player_id3' => $playerId,
            ':player_id4' => $playerId,
            ':player_id5' => $playerId
        ));
    }
    
}
?>