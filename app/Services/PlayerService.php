<?php
// ============================================================================
// app/Services/PlayerService.php
// ============================================================================

namespace App\Services;

use App\Core\App;

class PlayerService {
    private App $app;

    public function __construct(App $app) {
        $this->app = $app;
    }

    public function getPlayerById(int $playerId): ?array {
        $sql = "SELECT * FROM players WHERE id = :id LIMIT 1";
        return $this->app->getDb()->selectOne($sql, [':id' => $playerId]);
    }

    public function getPlayerByUsername(string $username): ?array {
        $sql = "SELECT * FROM players WHERE username = :username LIMIT 1";
        return $this->app->getDb()->selectOne($sql, [':username' => $username]);
    }

    public function getPlayerByEmail(string $email): ?array {
        $sql = "SELECT * FROM players WHERE email = :email LIMIT 1";
        return $this->app->getDb()->selectOne($sql, [':email' => $email]);
    }

    public function createPlayer(string $username, string $email, string $password): mixed {
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2]);
        if(!$passwordHash) {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        }

        $sql = "INSERT INTO players (username, email, password, created_at) VALUES (:username, :email, :password, NOW())";
        return $this->app->getDb()->insert($sql, [':username' => $username, ':email' => $email, ':password' => $passwordHash]);
    }

    public function createPlayerWithBuildings(string $username, string $email, string $password): mixed {
        $playerId = $this->createPlayer($username, $email, $password);
        if(!$playerId) return false;

        $startBuildings = [1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 1, 6 => 1];
        foreach($startBuildings as $buildingTypeId => $level) {
            $sql = "INSERT INTO player_buildings (player_id, building_type_id, level) VALUES (:player_id, :building_type_id, :level)";
            $this->app->getDb()->insert($sql, [':player_id' => $playerId, ':building_type_id' => $buildingTypeId, ':level' => $level]);
        }

        $this->updateInitialProduction($playerId);
        return $playerId;
    }

    private function updateInitialProduction(int $playerId): void {
        $sql = "UPDATE players SET last_resource_update = NOW() WHERE id = :id";
        $this->app->getDb()->update($sql, [':id' => $playerId]);
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
        return $this->app->getDb()->update($sql, $params) !== false;
    }

    public function updateLastLogin(int $playerId): void {
        $sql = "UPDATE players SET last_login = NOW() WHERE id = :id";
        $this->app->getDb()->update($sql, [':id' => $playerId]);
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
        $this->app->getDb()->update($sql, [':exp' => $newExp, ':level' => $newLevel, ':id' => $playerId]);

        if($leveledUp && $player['character_created']) {
            $this->app->getStats()->onLevelUp($playerId);
        }
    }

    public function getExpNeeded(int $level): int {
        return $level * 100;
    }

    public function updateHP(int $playerId, int $amount): void {
        $player = $this->getPlayerById($playerId);
        $newHP = max(0, min($player['hp'] + $amount, $player['max_hp']));
        $sql = "UPDATE players SET hp = :hp WHERE id = :id";
        $this->app->getDb()->update($sql, [':hp' => $newHP, ':id' => $playerId]);
    }

    public function updateEnergy(int $playerId, int $amount): void {
        $sql = "UPDATE players SET energy = LEAST(energy + :amount, 100) WHERE id = :id";
        $this->app->getDb()->update($sql, [':amount' => $amount, ':id' => $playerId]);
    }
}