<?php
// ============================================================================
// app/Services/PlayerService.php - FIXED
// ============================================================================

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

class PlayerService {
    private Database $db;
    private Logger $logger;

    public function __construct(Database $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function getPlayerById(int $playerId): ?array {
        $sql = "SELECT * FROM players WHERE id = :id LIMIT 1";
        return $this->db->selectOne($sql, [':id' => $playerId]);
    }

    public function getPlayerByUsername(string $username): ?array {
        $sql = "SELECT * FROM players WHERE username = :username LIMIT 1";
        return $this->db->selectOne($sql, [':username' => $username]);
    }

    public function getPlayerByEmail(string $email): ?array {
        $sql = "SELECT * FROM players WHERE email = :email LIMIT 1";
        return $this->db->selectOne($sql, [':email' => $email]);
    }

    public function createPlayer(string $username, string $email, string $password): mixed {
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, 
            'time_cost' => 4, 
            'threads' => 2
        ]);
        
        if(!$passwordHash) {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        }

        $sql = "INSERT INTO players (username, email, password, created_at) VALUES (:username, :email, :password, NOW())";
        return $this->db->insert($sql, [':username' => $username, ':email' => $email, ':password' => $passwordHash]);
    }

    public function createPlayerWithBuildings(string $username, string $email, string $password): mixed {
        $playerId = $this->createPlayer($username, $email, $password);
        if(!$playerId) return false;

        $startBuildings = [1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 1, 6 => 1];
        foreach($startBuildings as $buildingTypeId => $level) {
            $sql = "INSERT INTO player_buildings (player_id, building_type_id, level) VALUES (:player_id, :building_type_id, :level)";
            $this->db->insert($sql, [':player_id' => $playerId, ':building_type_id' => $buildingTypeId, ':level' => $level]);
        }

        $this->updateInitialProduction($playerId);
        return $playerId;
    }

    private function updateInitialProduction(int $playerId): void {
        $sql = "UPDATE players SET last_resource_update = NOW() WHERE id = :id";
        $this->db->update($sql, [':id' => $playerId]);
    }

    public function hasEnoughResources(int $playerId, array $costs): bool {
        $player = $this->getPlayerById($playerId);
        if(!$player) return false;
        
        if(($costs['gold'] ?? 0) > $player['gold']) return false;
        if(($costs['food'] ?? 0) > $player['food']) return false;
        if(($costs['wood'] ?? 0) > $player['wood']) return false;
        if(($costs['stone'] ?? 0) > $player['stone']) return false;
        
        return true;
    }

    public function updateResources(int $playerId, array $resources): bool {
        $allowed = ['gold', 'food', 'wood', 'stone'];
        $updates = [];
        $params = [':id' => $playerId];

        foreach($resources as $key => $value) {
            if(in_array($key, $allowed)) {
                $updates[] = "$key = $key + :$key";
                $params[":$key"] = (int)$value;
            }
        }

        if(empty($updates)) return false;

        $sql = "UPDATE players SET " . implode(', ', $updates) . " WHERE id = :id";
        $result = $this->db->update($sql, $params);
        
        if($result !== false) {
            $this->logger->debug('Resources updated', [
                'player_id' => $playerId,
                'changes' => $resources
            ]);
        }
        
        return $result !== false;
    }

    public function updateLastLogin(int $playerId): void {
        $sql = "UPDATE players SET last_login = NOW() WHERE id = :id";
        $this->db->update($sql, [':id' => $playerId]);
    }

    public function addExp(int $playerId, int $exp): void {
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
        $this->db->update($sql, [':exp' => $newExp, ':level' => $newLevel, ':id' => $playerId]);

        if($leveledUp && $player['character_created']) {
            // Stats Service wird von außen aufgerufen (circular dependency vermeiden)
            $this->logger->info('Player leveled up', [
                'player_id' => $playerId,
                'new_level' => $newLevel
            ]);
        }
    }

    public function getExpNeeded(int $level): int {
        return $level * 100;
    }

    public function updateHP(int $playerId, int $amount): void {
        $player = $this->getPlayerById($playerId);
        $newHP = max(0, min($player['hp'] + $amount, $player['max_hp']));
        $sql = "UPDATE players SET hp = :hp WHERE id = :id";
        $this->db->update($sql, [':hp' => $newHP, ':id' => $playerId]);
    }

    public function updateEnergy(int $playerId, int $amount): void {
        $sql = "UPDATE players SET energy = LEAST(GREATEST(energy + :amount, 0), 100) WHERE id = :id";
        $this->db->update($sql, [':amount' => $amount, ':id' => $playerId]);
    }
    
    public function getTopPlayers(int $limit = 10): array {
        $sql = "SELECT id, username, level, exp, gold FROM players 
                ORDER BY level DESC, exp DESC LIMIT :limit";
        return $this->db->select($sql, [':limit' => $limit]);
    }
}