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

    /**
     * Get all playable races
     * 
     * @return array All playable races
     */
    public function getAllPlayableRaces(): array {
        $sql = "SELECT * FROM races WHERE is_playable = 1 ORDER BY is_hybrid ASC, name ASC";
        return $this->db->select($sql);
    }

    /**
     * Get race by ID
     * 
     * @param int $raceId Race ID
     * @return array|null Race data or null
     */
    public function getRaceById(int $raceId): ?array {
        $sql = "SELECT * FROM races WHERE id = :id LIMIT 1";
        return $this->db->selectOne($sql, [':id' => $raceId]);
    }
}