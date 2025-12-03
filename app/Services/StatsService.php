<?php

// ============================================================================
// app/Services/StatsService.php
// ============================================================================
namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

class StatsService {
    private Database $db;
    private PlayerService $player;
    private Logger $logger;
    
    public function __construct(Database $db, PlayerService $player) {
        $this->db = $db;
        $this->player = $player;
        $this->logger = new Logger('stats');
    }
    
    public function distributeStatPoint(int $playerId, string $statName, int $amount = 1): array {
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
            $this->recalculateSecondaryStats($playerId);
            $this->logger->info("Stat point distributed", ['player_id' => $playerId, 'stat' => $statName]);
            return ['success' => true, 'message' => "$statName erhöht!"];
        }
        
        return ['success' => false, 'message' => 'Fehler beim Verteilen'];
    }
    
    public function recalculateSecondaryStats(int $playerId): bool {
        $player = $this->player->getPlayerById($playerId);
        if(!$player) return false;
        
        $race = $this->db->selectOne("SELECT * FROM races WHERE id = :id", [':id' => $player['race_id']]);
        $class = $this->db->selectOne("SELECT * FROM classes WHERE id = :id", [':id' => $player['class_id']]);
        
        if(!$race || !$class) return false;
        
        $baseHP = $player['constitution'] * 10;
        $maxHP = round($baseHP * $race['hp_modifier'] * $class['hp_modifier']) + ($player['level'] * 5);
        
        $baseMana = $player['intelligence'] * 10;
        $maxMana = round($baseMana * $race['mana_modifier'] * $class['mana_modifier']);
        
        $baseStamina = ($player['constitution'] * 5) + ($player['dexterity'] * 5);
        $maxStamina = round($baseStamina * $race['stamina_modifier'] * $class['stamina_modifier']);
        
        $attack = $player['strength'] + round($player['dexterity'] / 2);
        $attack = round($attack * (1 + $class['attack_bonus']));
        
        $defense = $player['constitution'] + round($player['dexterity'] / 3);
        $defense = round($defense * (1 + $class['defense_bonus']));
        
        $sql = "UPDATE players SET 
                max_hp = :max_hp, max_mana = :max_mana, max_stamina = :max_stamina,
                attack = :attack, defense = :defense
                WHERE id = :id";
        
        return $this->db->update($sql, [
            ':max_hp' => $maxHP, ':max_mana' => $maxMana, ':max_stamina' => $maxStamina,
            ':attack' => $attack, ':defense' => $defense, ':id' => $playerId
        ]) !== false;
    }
    
    public function onLevelUp(int $playerId): bool {
        $player = $this->player->getPlayerById($playerId);
        $statPointsGained = 5;
        
        $sql = "UPDATE players SET stat_points = stat_points + :stat_points WHERE id = :id";
        $result = $this->db->update($sql, [':stat_points' => $statPointsGained, ':id' => $playerId]);
        
        $this->recalculateSecondaryStats($playerId);
        return $result !== false;
    }
}