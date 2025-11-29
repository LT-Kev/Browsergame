<?php
// class/class.race.php
/**
 * Race Class - Verwaltung von Rassen
 */
class Race {
    private $db;
    private $logger;
    
    public function __construct($database) {
        $this->db = $database;
        $this->logger = new Logger('race');
    }
    
    /**
     * Alle spielbaren Rassen abrufen
     */
    public function getAllPlayableRaces() {
        $sql = "SELECT * FROM races WHERE is_playable = 1 ORDER BY is_hybrid ASC, name ASC";
        return $this->db->select($sql);
    }
    
    /**
     * Rasse nach ID
     */
    public function getRaceById($raceId) {
        $sql = "SELECT * FROM races WHERE id = :id LIMIT 1";
        return $this->db->selectOne($sql, [':id' => $raceId]);
    }
    
    /**
     * Hybrid-Rasse erstellen
     */
    public function createHybridRace($name, $parentRace1Id, $parentRace2Id) {
        $parent1 = $this->getRaceById($parentRace1Id);
        $parent2 = $this->getRaceById($parentRace2Id);
        
        if(!$parent1 || !$parent2) {
            return false;
        }
        
        // Durchschnittswerte berechnen
        $hybridData = [
            'name' => $name,
            'description' => "Hybrid aus {$parent1['name']} und {$parent2['name']}",
            'is_hybrid' => 1,
            'parent_race_1' => $parentRace1Id,
            'parent_race_2' => $parentRace2Id,
            
            // Stats als Durchschnitt
            'base_strength' => round(($parent1['base_strength'] + $parent2['base_strength']) / 2),
            'base_dexterity' => round(($parent1['base_dexterity'] + $parent2['base_dexterity']) / 2),
            'base_constitution' => round(($parent1['base_constitution'] + $parent2['base_constitution']) / 2),
            'base_intelligence' => round(($parent1['base_intelligence'] + $parent2['base_intelligence']) / 2),
            'base_wisdom' => round(($parent1['base_wisdom'] + $parent2['base_wisdom']) / 2),
            'base_charisma' => round(($parent1['base_charisma'] + $parent2['base_charisma']) / 2),
            
            // Modifiers
            'hp_modifier' => ($parent1['hp_modifier'] + $parent2['hp_modifier']) / 2,
            'mana_modifier' => ($parent1['mana_modifier'] + $parent2['mana_modifier']) / 2,
            'stamina_modifier' => ($parent1['stamina_modifier'] + $parent2['stamina_modifier']) / 2,
        ];
        
        // Insert in DB (vereinfacht, sollte alle Felder enthalten)
        $sql = "INSERT INTO races (name, description, is_hybrid, parent_race_1, parent_race_2) 
                VALUES (:name, :description, 1, :parent1, :parent2)";
        
        return $this->db->insert($sql, [
            ':name' => $name,
            ':description' => $hybridData['description'],
            ':parent1' => $parentRace1Id,
            ':parent2' => $parentRace2Id
        ]);
    }
    
    /**
     * Berechne Stats für einen Spieler basierend auf Rasse und Level
     */
    public function calculateRaceStats($raceId, $level) {
        $race = $this->getRaceById($raceId);
        
        if(!$race) {
            return false;
        }
        
        $stats = [
            'strength' => $race['base_strength'] + ($race['strength_per_level'] * ($level - 1)),
            'dexterity' => $race['base_dexterity'] + ($race['dexterity_per_level'] * ($level - 1)),
            'constitution' => $race['base_constitution'] + ($race['constitution_per_level'] * ($level - 1)),
            'intelligence' => $race['base_intelligence'] + ($race['intelligence_per_level'] * ($level - 1)),
            'wisdom' => $race['base_wisdom'] + ($race['wisdom_per_level'] * ($level - 1)),
            'charisma' => $race['base_charisma'] + ($race['charisma_per_level'] * ($level - 1)),
        ];
        
        return array_map('round', $stats);
    }
}

// ============================================================================
// class/class.rpgclass.php
/**
 * RPGClass - Verwaltung von Charakterklassen
 */
class RPGClass {
    private $db;
    private $logger;
    
    public function __construct($database) {
        $this->db = $database;
        $this->logger = new Logger('class');
    }
    
    /**
     * Alle Starter-Klassen
     */
    public function getAllStarterClasses() {
        $sql = "SELECT * FROM classes WHERE is_starter_class = 1 ORDER BY type, name";
        return $this->db->select($sql);
    }
    
    /**
     * Alle Klassen nach Typ
     */
    public function getClassesByType($type) {
        $sql = "SELECT * FROM classes WHERE type = :type ORDER BY required_level ASC, name";
        return $this->db->select($sql, [':type' => $type]);
    }
    
    /**
     * Klasse nach ID
     */
    public function getClassById($classId) {
        $sql = "SELECT * FROM classes WHERE id = :id LIMIT 1";
        return $this->db->selectOne($sql, [':id' => $classId]);
    }
    
    /**
     * Verfügbare Klassen für Spieler (basierend auf Level & Voraussetzungen)
     */
    public function getAvailableClassesForPlayer($playerId) {
        $sql = "SELECT c.* FROM classes c
                LEFT JOIN player_classes pc ON c.id = pc.class_id AND pc.player_id = :player_id
                WHERE pc.id IS NULL
                AND (c.required_level <= (SELECT level FROM players WHERE id = :player_id2))
                AND (c.required_class_id IS NULL 
                     OR c.required_class_id IN (SELECT class_id FROM player_classes WHERE player_id = :player_id3))
                ORDER BY c.is_starter_class DESC, c.required_level ASC";
        
        return $this->db->select($sql, [
            ':player_id' => $playerId,
            ':player_id2' => $playerId,
            ':player_id3' => $playerId
        ]);
    }
    
    /**
     * Spieler lernt neue Klasse
     */
    public function learnClass($playerId, $classId) {
        // Prüfe ob Spieler Voraussetzungen erfüllt
        $class = $this->getClassById($classId);
        $player = $this->db->selectOne("SELECT * FROM players WHERE id = :id", [':id' => $playerId]);
        
        if(!$class || !$player) {
            return ['success' => false, 'message' => 'Klasse oder Spieler nicht gefunden'];
        }
        
        // Level-Check
        if($player['level'] < $class['required_level']) {
            return ['success' => false, 'message' => "Level {$class['required_level']} erforderlich"];
        }
        
        // Voraussetzungs-Klasse Check
        if($class['required_class_id']) {
            $hasRequired = $this->db->selectOne(
                "SELECT * FROM player_classes WHERE player_id = :pid AND class_id = :cid",
                [':pid' => $playerId, ':cid' => $class['required_class_id']]
            );
            
            if(!$hasRequired) {
                $reqClass = $this->getClassById($class['required_class_id']);
                return ['success' => false, 'message' => "Benötigt: {$reqClass['name']}"];
            }
        }
        
        // Prüfe ob bereits gelernt
        $alreadyHas = $this->db->selectOne(
            "SELECT * FROM player_classes WHERE player_id = :pid AND class_id = :cid",
            [':pid' => $playerId, ':cid' => $classId]
        );
        
        if($alreadyHas) {
            return ['success' => false, 'message' => 'Klasse bereits gelernt'];
        }
        
        // Alte aktive Klasse deaktivieren
        $sql = "UPDATE player_classes SET is_active = 0 WHERE player_id = :id";
        $this->db->update($sql, [':id' => $playerId]);
        
        // Neue Klasse hinzufügen
        $sql = "INSERT INTO player_classes (player_id, class_id, is_active, learned_at)
                VALUES (:player_id, :class_id, 1, NOW())";
        
        $result = $this->db->insert($sql, [
            ':player_id' => $playerId,
            ':class_id' => $classId
        ]);
        
        if($result) {
            // Update player class_id
            $sql = "UPDATE players SET class_id = :class_id WHERE id = :id";
            $this->db->update($sql, [':class_id' => $classId, ':id' => $playerId]);
            
            $this->logger->info("Player learned new class", [
                'player_id' => $playerId,
                'class_id' => $classId,
                'class_name' => $class['name']
            ]);
            
            return ['success' => true, 'message' => "Klasse {$class['name']} gelernt!"];
        }
        
        return ['success' => false, 'message' => 'Fehler beim Lernen der Klasse'];
    }
    
    /**
     * Klasse wechseln
     */
    public function switchClass($playerId, $classId) {
        // Prüfe ob Spieler diese Klasse hat
        $hasClass = $this->db->selectOne(
            "SELECT * FROM player_classes WHERE player_id = :pid AND class_id = :cid",
            [':pid' => $playerId, ':cid' => $classId]
        );
        
        if(!$hasClass) {
            return ['success' => false, 'message' => 'Du hast diese Klasse nicht gelernt'];
        }
        
        // Alle inaktiv setzen
        $sql = "UPDATE player_classes SET is_active = 0 WHERE player_id = :id";
        $this->db->update($sql, [':id' => $playerId]);
        
        // Gewählte aktiv setzen
        $sql = "UPDATE player_classes SET is_active = 1 
                WHERE player_id = :pid AND class_id = :cid";
        $this->db->update($sql, [':pid' => $playerId, ':cid' => $classId]);
        
        // Update player class_id
        $sql = "UPDATE players SET class_id = :class_id WHERE id = :id";
        $this->db->update($sql, [':class_id' => $classId, ':id' => $playerId]);
        
        $class = $this->getClassById($classId);
        
        return ['success' => true, 'message' => "Gewechselt zu: {$class['name']}"];
    }
    
    /**
     * Alle Klassen eines Spielers
     */
    public function getPlayerClasses($playerId) {
        $sql = "SELECT pc.*, c.name, c.description, c.type, c.icon
                FROM player_classes pc
                JOIN classes c ON pc.class_id = c.id
                WHERE pc.player_id = :player_id
                ORDER BY pc.is_active DESC, c.name";
        
        return $this->db->select($sql, [':player_id' => $playerId]);
    }
    
    /**
     * Aktive Klasse eines Spielers
     */
    public function getActiveClass($playerId) {
        $sql = "SELECT pc.*, c.*
                FROM player_classes pc
                JOIN classes c ON pc.class_id = c.id
                WHERE pc.player_id = :player_id AND pc.is_active = 1
                LIMIT 1";
        
        return $this->db->selectOne($sql, [':player_id' => $playerId]);
    }
}

// ============================================================================
// class/class.stats.php
/**
 * Stats - Verwaltung von Charakterstatistiken
 */
class Stats {
    private $db;
    private $player;
    private $logger;
    
    public function __construct($database, $playerClass) {
        $this->db = $database;
        $this->player = $playerClass;
        $this->logger = new Logger('stats');
    }
    
    /**
     * Statuspunkt verteilen
     */
    public function distributeStatPoint($playerId, $statName, $amount = 1) {
        $allowedStats = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
        
        if(!in_array($statName, $allowedStats)) {
            return ['success' => false, 'message' => 'Ungültiger Stat'];
        }
        
        $player = $this->player->getPlayerById($playerId);
        
        if(!$player) {
            return ['success' => false, 'message' => 'Spieler nicht gefunden'];
        }
        
        if($player['stat_points'] < $amount) {
            return ['success' => false, 'message' => 'Nicht genug Statuspunkte'];
        }
        
        // Update Stat
        $sql = "UPDATE players 
                SET $statName = $statName + :amount, 
                    stat_points = stat_points - :amount2
                WHERE id = :id";
        
        $result = $this->db->update($sql, [
            ':amount' => $amount,
            ':amount2' => $amount,
            ':id' => $playerId
        ]);
        
        if($result !== false) {
            // Sekundäre Stats neu berechnen
            $this->recalculateSecondaryStats($playerId);
            
            $this->logger->info("Stat point distributed", [
                'player_id' => $playerId,
                'stat' => $statName,
                'amount' => $amount
            ]);
            
            return ['success' => true, 'message' => "$statName erhöht!"];
        }
        
        return ['success' => false, 'message' => 'Fehler beim Verteilen'];
    }
    
    /**
     * Sekundäre Stats berechnen (HP, Mana, etc.)
     */
    public function recalculateSecondaryStats($playerId) {
        $player = $this->player->getPlayerById($playerId);
        
        if(!$player) {
            return false;
        }
        
        // Hole Rasse und Klasse für Modifikatoren
        $race = $this->db->selectOne("SELECT * FROM races WHERE id = :id", [':id' => $player['race_id']]);
        $class = $this->db->selectOne("SELECT * FROM classes WHERE id = :id", [':id' => $player['class_id']]);
        
        if(!$race || !$class) {
            return false;
        }
        
        // HP = (Constitution * 10) * race_modifier * class_modifier + level_bonus
        $baseHP = $player['constitution'] * 10;
        $maxHP = round($baseHP * $race['hp_modifier'] * $class['hp_modifier']) + ($player['level'] * 5);
        
        // Mana = (Intelligence * 10) * race_modifier * class_modifier
        $baseMana = $player['intelligence'] * 10;
        $maxMana = round($baseMana * $race['mana_modifier'] * $class['mana_modifier']);
        
        // Stamina = (Constitution * 5 + Dexterity * 5) * race_modifier * class_modifier
        $baseStamina = ($player['constitution'] * 5) + ($player['dexterity'] * 5);
        $maxStamina = round($baseStamina * $race['stamina_modifier'] * $class['stamina_modifier']);
        
        // Angriff = Strength + (Dexterity / 2) + class_bonus
        $attack = $player['strength'] + round($player['dexterity'] / 2);
        $attack = round($attack * (1 + $class['attack_bonus']));
        
        // Verteidigung = Constitution + (Dexterity / 3) + class_bonus
        $defense = $player['constitution'] + round($player['dexterity'] / 3);
        $defense = round($defense * (1 + $class['defense_bonus']));
        
        // Update Player
        $sql = "UPDATE players SET 
                max_hp = :max_hp,
                max_mana = :max_mana,
                max_stamina = :max_stamina,
                attack = :attack,
                defense = :defense
                WHERE id = :id";
        
        return $this->db->update($sql, [
            ':max_hp' => $maxHP,
            ':max_mana' => $maxMana,
            ':max_stamina' => $maxStamina,
            ':attack' => $attack,
            ':defense' => $defense,
            ':id' => $playerId
        ]);
    }
    
    /**
     * Stats bei Level-Up
     */
    public function onLevelUp($playerId) {
        $player = $this->player->getPlayerById($playerId);
        $race = $this->db->selectOne("SELECT * FROM races WHERE id = :id", [':id' => $player['race_id']]);
        $class = $this->db->selectOne("SELECT * FROM classes WHERE id = :id", [':id' => $player['class_id']]);
        
        // Gebe Statuspunkte (5 pro Level)
        $statPointsGained = 5;
        
        // Automatische Stat-Erhöhungen durch Rasse/Klasse
        $autoStats = [
            'strength' => round($race['strength_per_level'] * $class['strength_modifier']),
            'dexterity' => round($race['dexterity_per_level'] * $class['dexterity_modifier']),
            'constitution' => round($race['constitution_per_level'] * $class['constitution_modifier']),
            'intelligence' => round($race['intelligence_per_level'] * $class['intelligence_modifier']),
            'wisdom' => round($race['wisdom_per_level'] * $class['wisdom_modifier']),
            'charisma' => round($race['charisma_per_level'] * $class['charisma_modifier']),
        ];
        
        $sql = "UPDATE players SET 
                strength = strength + :str,
                dexterity = dexterity + :dex,
                constitution = constitution + :con,
                intelligence = intelligence + :int,
                wisdom = wisdom + :wis,
                charisma = charisma + :cha,
                stat_points = stat_points + :stat_points
                WHERE id = :id";
        
        $result = $this->db->update($sql, [
            ':str' => $autoStats['strength'],
            ':dex' => $autoStats['dexterity'],
            ':con' => $autoStats['constitution'],
            ':int' => $autoStats['intelligence'],
            ':wis' => $autoStats['wisdom'],
            ':cha' => $autoStats['charisma'],
            ':stat_points' => $statPointsGained,
            ':id' => $playerId
        ]);
        
        // Sekundäre Stats aktualisieren
        $this->recalculateSecondaryStats($playerId);
        
        $this->logger->info("Level up - stats increased", [
            'player_id' => $playerId,
            'auto_stats' => $autoStats,
            'stat_points' => $statPointsGained
        ]);
        
        return $result;
    }
    
    /**
     * Alle Stats eines Spielers
     */
    public function getPlayerStats($playerId) {
        $player = $this->player->getPlayerById($playerId);
        
        if(!$player) {
            return false;
        }
        
        return [
            'strength' => $player['strength'],
            'dexterity' => $player['dexterity'],
            'constitution' => $player['constitution'],
            'intelligence' => $player['intelligence'],
            'wisdom' => $player['wisdom'],
            'charisma' => $player['charisma'],
            'stat_points' => $player['stat_points'],
            'hp' => $player['hp'],
            'max_hp' => $player['max_hp'],
            'mana' => $player['mana'],
            'max_mana' => $player['max_mana'],
            'stamina' => $player['stamina'],
            'max_stamina' => $player['max_stamina'],
            'attack' => $player['attack'],
            'defense' => $player['defense'],
        ];
    }
}
?>