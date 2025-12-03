<?php
// ============================================================================
// app/Services/RaceService.php
// ============================================================================
namespace App\Services;

use App\Core\Database;

class RaceService {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function getAllPlayableRaces(): array {
        $sql = "SELECT * FROM races WHERE is_playable = 1 ORDER BY is_hybrid ASC, name ASC";
        return $this->db->select($sql);
    }

    public function getRaceById(int $raceId): ?array {
        $sql = "SELECT * FROM races WHERE id = :id LIMIT 1";
        return $this->db->selectOne($sql, [':id' => $raceId]);
    }
}

// ============================================================================
// app/Services/RPGClassService.php
// ============================================================================
namespace App\Services;

use App\Core\Database;

class RPGClassService {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function getAllStarterClasses(): array {
        $sql = "SELECT * FROM classes WHERE is_starter_class = 1 ORDER BY type, name";
        return $this->db->select($sql);
    }

    public function getClassById(int $classId): ?array {
        $sql = "SELECT * FROM classes WHERE id = :id LIMIT 1";
        return $this->db->selectOne($sql, [':id' => $classId]);
    }

    public function getActiveClass(int $playerId): ?array {
        $sql = "SELECT pc.*, c.* FROM player_classes pc
                JOIN classes c ON pc.class_id = c.id
                WHERE pc.player_id = :player_id AND pc.is_active = 1 LIMIT 1";
        return $this->db->selectOne($sql, [':player_id' => $playerId]);
    }

    public function getPlayerClasses(int $playerId): array {
        $sql = "SELECT pc.*, c.name, c.description, c.type, c.icon
                FROM player_classes pc
                JOIN classes c ON pc.class_id = c.id
                WHERE pc.player_id = :player_id
                ORDER BY pc.is_active DESC, c.name";
        return $this->db->select($sql, [':player_id' => $playerId]);
    }
}