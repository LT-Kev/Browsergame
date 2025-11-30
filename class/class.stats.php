<?php
// class/class.stats.php
/**
 * Stats - Verwaltung von Charakterstatistiken
 * 
 * Features:
 * - Statuspunkte verteilen
 * - Sekundäre Stats berechnen (HP, Mana, Stamina, Angriff, Verteidigung)
 * - Level-Up Mechanik mit automatischen Erhöhungen
 * - Rassen & Klassen-Modifikatoren
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
     * 
     * @param int $playerId Player ID
     * @param string $statName Name des Stats (strength, dexterity, etc.)
     * @param int $amount Anzahl der Punkte (Standard: 1)
     * @return array Result array mit success & message
     */
    public function distributeStatPoint($playerId, $statName, $amount = 1) {
        // Erlaubte Stats
        $allowedStats = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
        
        if(!in_array($statName, $allowedStats)) {
            return ['success' => false, 'message' => 'Ungültiger Stat'];
        }
        
        $player = $this->player->getPlayerById($playerId);
        
        if(!$player) {
            return ['success' => false, 'message' => 'Spieler nicht gefunden'];
        }
        
        // Prüfe ob genug Statuspunkte vorhanden
        if($player['stat_points'] < $amount) {
            return ['success' => false, 'message' => 'Nicht genug Statuspunkte'];
        }
        
        // Validiere Amount
        if($amount < 1 || $amount > 10) {
            return ['success' => false, 'message' => 'Ungültige Menge'];
        }
        
        try {
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
                // Sekundäre Stats neu berechnen (HP, Mana, etc.)
                $this->recalculateSecondaryStats($playerId);
                
                $this->logger->info("Stat point distributed", [
                    'player_id' => $playerId,
                    'stat' => $statName,
                    'amount' => $amount,
                    'remaining_points' => $player['stat_points'] - $amount
                ]);
                
                // Stat-Namen für User-freundliche Nachricht
                $statNames = [
                    'strength' => 'Stärke',
                    'dexterity' => 'Geschicklichkeit',
                    'constitution' => 'Konstitution',
                    'intelligence' => 'Intelligenz',
                    'wisdom' => 'Weisheit',
                    'charisma' => 'Charisma'
                ];
                
                return [
                    'success' => true, 
                    'message' => $statNames[$statName] . " um {$amount} erhöht!",
                    'new_value' => $player[$statName] + $amount,
                    'remaining_points' => $player['stat_points'] - $amount
                ];
            }
            
            return ['success' => false, 'message' => 'Fehler beim Verteilen'];
            
        } catch(Exception $e) {
            $this->logger->error("Failed to distribute stat point", [
                'player_id' => $playerId,
                'stat' => $statName,
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'message' => 'Fehler beim Verteilen'];
        }
    }
    
    /**
     * Sekundäre Stats berechnen (HP, Mana, Stamina, Angriff, Verteidigung)
     * 
     * Wird aufgerufen nach:
     * - Statuspunkt-Verteilung
     * - Level-Up
     * - Klassen-Wechsel
     * 
     * @param int $playerId Player ID
     * @return bool Success
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
            $this->logger->warning("Cannot recalculate stats - missing race or class", [
                'player_id' => $playerId,
                'race_id' => $player['race_id'],
                'class_id' => $player['class_id']
            ]);
            return false;
        }
        
        // ====================================================================
        // HP BERECHNUNG
        // ====================================================================
        // Formel: (Constitution * 10) * race_modifier * class_modifier + level_bonus
        $baseHP = $player['constitution'] * 10;
        $maxHP = round($baseHP * $race['hp_modifier'] * $class['hp_modifier']) + ($player['level'] * 5);
        
        // Aktuelles HP proportional anpassen (nicht über Max)
        $hpPercent = $player['max_hp'] > 0 ? ($player['hp'] / $player['max_hp']) : 1;
        $newHP = min(round($maxHP * $hpPercent), $maxHP);
        
        // ====================================================================
        // MANA BERECHNUNG
        // ====================================================================
        // Formel: (Intelligence * 10) * race_modifier * class_modifier
        $baseMana = $player['intelligence'] * 10;
        $maxMana = round($baseMana * $race['mana_modifier'] * $class['mana_modifier']);
        
        // Aktuelles Mana proportional anpassen
        $manaPercent = $player['max_mana'] > 0 ? ($player['mana'] / $player['max_mana']) : 1;
        $newMana = min(round($maxMana * $manaPercent), $maxMana);
        
        // ====================================================================
        // STAMINA BERECHNUNG
        // ====================================================================
        // Formel: (Constitution * 5 + Dexterity * 5) * race_modifier * class_modifier
        $baseStamina = ($player['constitution'] * 5) + ($player['dexterity'] * 5);
        $maxStamina = round($baseStamina * $race['stamina_modifier'] * $class['stamina_modifier']);
        
        // Aktuelles Stamina proportional anpassen
        $staminaPercent = $player['max_stamina'] > 0 ? ($player['stamina'] / $player['max_stamina']) : 1;
        $newStamina = min(round($maxStamina * $staminaPercent), $maxStamina);
        
        // ====================================================================
        // ANGRIFF BERECHNUNG
        // ====================================================================
        // Formel: Strength + (Dexterity / 2) + class_bonus + race_bonus
        $attack = $player['strength'] + round($player['dexterity'] / 2);
        
        // Klassen-Bonus (prozentual)
        $attack = round($attack * (1 + $class['attack_bonus']));
        
        // Rassen-Bonus für Nahkampf (falls vorhanden)
        if(isset($race['melee_damage_bonus'])) {
            $attack = round($attack * (1 + $race['melee_damage_bonus']));
        }
        
        // ====================================================================
        // VERTEIDIGUNG BERECHNUNG
        // ====================================================================
        // Formel: Constitution + (Dexterity / 3) + class_bonus + race_bonus
        $defense = $player['constitution'] + round($player['dexterity'] / 3);
        
        // Klassen-Bonus (prozentual)
        $defense = round($defense * (1 + $class['defense_bonus']));
        
        // Rassen-Bonus für Verteidigung (falls vorhanden)
        if(isset($race['defense_bonus'])) {
            $defense = round($defense * (1 + $race['defense_bonus']));
        }
        
        // ====================================================================
        // UPDATE PLAYER
        // ====================================================================
        $sql = "UPDATE players SET 
                hp = :hp,
                max_hp = :max_hp,
                mana = :mana,
                max_mana = :max_mana,
                stamina = :stamina,
                max_stamina = :max_stamina,
                attack = :attack,
                defense = :defense
                WHERE id = :id";
        
        $result = $this->db->update($sql, [
            ':hp' => $newHP,
            ':max_hp' => $maxHP,
            ':mana' => $newMana,
            ':max_mana' => $maxMana,
            ':stamina' => $newStamina,
            ':max_stamina' => $maxStamina,
            ':attack' => $attack,
            ':defense' => $defense,
            ':id' => $playerId
        ]);
        
        if($result !== false) {
            $this->logger->debug("Secondary stats recalculated", [
                'player_id' => $playerId,
                'max_hp' => $maxHP,
                'max_mana' => $maxMana,
                'max_stamina' => $maxStamina,
                'attack' => $attack,
                'defense' => $defense
            ]);
        }
        
        return $result !== false;
    }
    
    /**
     * Stats bei Level-Up erhöhen
     * 
     * Automatische Erhöhungen basierend auf:
     * - Rassen-Wachstum (strength_per_level etc.)
     * - Klassen-Modifikatoren (strength_modifier etc.)
     * + Freie Statuspunkte zum Verteilen
     * 
     * @param int $playerId Player ID
     * @return bool Success
     */
    public function onLevelUp($playerId) {
        $player = $this->player->getPlayerById($playerId);
        $race = $this->db->selectOne("SELECT * FROM races WHERE id = :id", [':id' => $player['race_id']]);
        $class = $this->db->selectOne("SELECT * FROM classes WHERE id = :id", [':id' => $player['class_id']]);
        
        if(!$race || !$class) {
            return false;
        }
        
        // ====================================================================
        // FREIE STATUSPUNKTE
        // ====================================================================
        // Spieler erhält 5 freie Punkte pro Level zum selbst Verteilen
        $statPointsGained = 5;
        
        // ====================================================================
        // AUTOMATISCHE STAT-ERHÖHUNGEN
        // ====================================================================
        // Berechnung: Rassen-Wachstum × Klassen-Modifier
        // Beispiel: Ork Krieger
        // - Ork: strength_per_level = 1.20
        // - Krieger: strength_modifier = 1.30
        // - Ergebnis: 1.20 × 1.30 = 1.56 ≈ 2 Stärke pro Level
        
        $autoStats = [
            'strength' => round($race['strength_per_level'] * $class['strength_modifier']),
            'dexterity' => round($race['dexterity_per_level'] * $class['dexterity_modifier']),
            'constitution' => round($race['constitution_per_level'] * $class['constitution_modifier']),
            'intelligence' => round($race['intelligence_per_level'] * $class['intelligence_modifier']),
            'wisdom' => round($race['wisdom_per_level'] * $class['wisdom_modifier']),
            'charisma' => round($race['charisma_per_level'] * $class['charisma_modifier']),
        ];
        
        // Mindestens 1 Punkt garantieren für Primary Stats der Klasse
        if($class['primary_stat_1']) {
            $autoStats[$class['primary_stat_1']] = max(1, $autoStats[$class['primary_stat_1']]);
        }
        if($class['primary_stat_2']) {
            $autoStats[$class['primary_stat_2']] = max(1, $autoStats[$class['primary_stat_2']]);
        }
        
        // ====================================================================
        // UPDATE PLAYER STATS
        // ====================================================================
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
        
        // Sekundäre Stats aktualisieren (HP, Mana, etc.)
        $this->recalculateSecondaryStats($playerId);
        
        // ====================================================================
        // LOGGING
        // ====================================================================
        $this->logger->info("Level up - stats increased", [
            'player_id' => $playerId,
            'new_level' => $player['level'],
            'auto_stats' => $autoStats,
            'stat_points_gained' => $statPointsGained
        ]);
        
        return $result !== false;
    }
    
    /**
     * Alle Stats eines Spielers abrufen
     * 
     * @param int $playerId Player ID
     * @return array|false Stats array oder false
     */
    public function getPlayerStats($playerId) {
        $player = $this->player->getPlayerById($playerId);
        
        if(!$player) {
            return false;
        }
        
        return [
            // Primäre Stats
            'strength' => $player['strength'],
            'dexterity' => $player['dexterity'],
            'constitution' => $player['constitution'],
            'intelligence' => $player['intelligence'],
            'wisdom' => $player['wisdom'],
            'charisma' => $player['charisma'],
            
            // Freie Punkte
            'stat_points' => $player['stat_points'],
            
            // Sekundäre Stats - Ressourcen
            'hp' => $player['hp'],
            'max_hp' => $player['max_hp'],
            'mana' => $player['mana'],
            'max_mana' => $player['max_mana'],
            'stamina' => $player['stamina'],
            'max_stamina' => $player['max_stamina'],
            
            // Sekundäre Stats - Kampf
            'attack' => $player['attack'],
            'defense' => $player['defense'],
        ];
    }
    
    /**
     * Stats zurücksetzen (für Admin/Testing)
     * 
     * @param int $playerId Player ID
     * @return bool Success
     */
    public function resetStats($playerId) {
        $player = $this->player->getPlayerById($playerId);
        $race = $this->db->selectOne("SELECT * FROM races WHERE id = :id", [':id' => $player['race_id']]);
        
        if(!$race) {
            return false;
        }
        
        // Zurück zu Basis-Stats der Rasse
        $sql = "UPDATE players SET 
                strength = :str,
                dexterity = :dex,
                constitution = :con,
                intelligence = :int,
                wisdom = :wis,
                charisma = :cha,
                stat_points = :points
                WHERE id = :id";
        
        // Gebe alle verteilten Punkte zurück
        $totalDistributed = ($player['strength'] - $race['base_strength'])
                          + ($player['dexterity'] - $race['base_dexterity'])
                          + ($player['constitution'] - $race['base_constitution'])
                          + ($player['intelligence'] - $race['base_intelligence'])
                          + ($player['wisdom'] - $race['base_wisdom'])
                          + ($player['charisma'] - $race['base_charisma']);
        
        $result = $this->db->update($sql, [
            ':str' => $race['base_strength'],
            ':dex' => $race['base_dexterity'],
            ':con' => $race['base_constitution'],
            ':int' => $race['base_intelligence'],
            ':wis' => $race['base_wisdom'],
            ':cha' => $race['base_charisma'],
            ':points' => $player['stat_points'] + $totalDistributed,
            ':id' => $playerId
        ]);
        
        if($result !== false) {
            $this->recalculateSecondaryStats($playerId);
            
            $this->logger->info("Stats reset", [
                'player_id' => $playerId,
                'points_refunded' => $totalDistributed
            ]);
        }
        
        return $result !== false;
    }
    
    /**
     * Berechne Stat-Wachstum Vorschau
     * 
     * Zeigt wie sich Stats bei Level-Up entwickeln würden
     * 
     * @param int $playerId Player ID
     * @param int $levels Anzahl Level voraus
     * @return array Vorschau
     */
    public function previewStatGrowth($playerId, $levels = 1) {
        $player = $this->player->getPlayerById($playerId);
        $race = $this->db->selectOne("SELECT * FROM races WHERE id = :id", [':id' => $player['race_id']]);
        $class = $this->db->selectOne("SELECT * FROM classes WHERE id = :id", [':id' => $player['class_id']]);
        
        $preview = [];
        
        for($i = 1; $i <= $levels; $i++) {
            $level = $player['level'] + $i;
            
            $gains = [
                'strength' => round($race['strength_per_level'] * $class['strength_modifier']),
                'dexterity' => round($race['dexterity_per_level'] * $class['dexterity_modifier']),
                'constitution' => round($race['constitution_per_level'] * $class['constitution_modifier']),
                'intelligence' => round($race['intelligence_per_level'] * $class['intelligence_modifier']),
                'wisdom' => round($race['wisdom_per_level'] * $class['wisdom_modifier']),
                'charisma' => round($race['charisma_per_level'] * $class['charisma_modifier']),
                'stat_points' => 5
            ];
            
            $preview[$level] = $gains;
        }
        
        return $preview;
    }
}
?>