<?php
// ============================================================================
// app/Services/RPGClassService.php - COMPLETE VERSION
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
    
    /**
     * Get classes available for a player to learn
     * 
     * @param int $playerId Player ID
     * @return array Available classes
     */
    public function getAvailableClassesForPlayer(int $playerId): array {
        // Get player's current level
        $player = $this->db->selectOne("SELECT level FROM players WHERE id = :id", [':id' => $playerId]);
        
        if(!$player) {
            return [];
        }
        
        // Get classes the player hasn't learned yet that meet requirements
        $sql = "SELECT c.* 
                FROM classes c
                WHERE c.id NOT IN (
                    SELECT class_id FROM player_classes WHERE player_id = :player_id
                )
                AND c.required_level <= :player_level
                AND (
                    c.required_class_id IS NULL 
                    OR c.required_class_id IN (
                        SELECT class_id FROM player_classes WHERE player_id = :player_id2
                    )
                )
                ORDER BY c.required_level ASC, c.name ASC";
        
        return $this->db->select($sql, [
            ':player_id' => $playerId,
            ':player_id2' => $playerId,
            ':player_level' => $player['level']
        ]);
    }
    
    /**
     * Learn a new class
     * 
     * @param int $playerId Player ID
     * @param int $classId Class ID
     * @return array Result with success status and message
     */
    public function learnClass(int $playerId, int $classId): array {
        // Check if player already has this class
        $hasClass = $this->db->selectOne(
            "SELECT * FROM player_classes WHERE player_id = :pid AND class_id = :cid",
            [':pid' => $playerId, ':cid' => $classId]
        );
        
        if($hasClass) {
            return ['success' => false, 'message' => 'Klasse bereits gelernt'];
        }
        
        // Check if class exists and requirements are met
        $class = $this->getClassById($classId);
        if(!$class) {
            return ['success' => false, 'message' => 'Klasse nicht gefunden'];
        }
        
        $player = $this->db->selectOne("SELECT * FROM players WHERE id = :id", [':id' => $playerId]);
        
        // Level check
        if($player['level'] < $class['required_level']) {
            return ['success' => false, 'message' => "Level {$class['required_level']} erforderlich"];
        }
        
        // Required class check
        if($class['required_class_id']) {
            $hasRequiredClass = $this->db->selectOne(
                "SELECT * FROM player_classes WHERE player_id = :pid AND class_id = :cid",
                [':pid' => $playerId, ':cid' => $class['required_class_id']]
            );
            
            if(!$hasRequiredClass) {
                $requiredClass = $this->getClassById($class['required_class_id']);
                return ['success' => false, 'message' => "Benötigt Klasse: {$requiredClass['name']}"];
            }
        }
        
        // Learn the class
        $sql = "INSERT INTO player_classes (player_id, class_id, is_active, learned_at, class_level)
                VALUES (:player_id, :class_id, 0, NOW(), 1)";
        
        $result = $this->db->insert($sql, [
            ':player_id' => $playerId,
            ':class_id' => $classId
        ]);
        
        if($result) {
            return ['success' => true, 'message' => "Klasse '{$class['name']}' gelernt!"];
        }
        
        return ['success' => false, 'message' => 'Fehler beim Lernen der Klasse'];
    }
    
    /**
     * Switch active class
     * 
     * @param int $playerId Player ID
     * @param int $classId Class ID to switch to
     * @return array Result with success status and message
     */
    public function switchClass(int $playerId, int $classId): array {
        // Check if player has this class
        $hasClass = $this->db->selectOne(
            "SELECT * FROM player_classes WHERE player_id = :pid AND class_id = :cid",
            [':pid' => $playerId, ':cid' => $classId]
        );
        
        if(!$hasClass) {
            return ['success' => false, 'message' => 'Klasse nicht gelernt'];
        }
        
        try {
            $this->db->beginTransaction();
            
            // Deactivate all classes
            $sql = "UPDATE player_classes SET is_active = 0 WHERE player_id = :player_id";
            $this->db->update($sql, [':player_id' => $playerId]);
            
            // Activate selected class
            $sql = "UPDATE player_classes SET is_active = 1 
                    WHERE player_id = :player_id AND class_id = :class_id";
            $this->db->update($sql, [':player_id' => $playerId, ':class_id' => $classId]);
            
            // Update player's class_id
            $sql = "UPDATE players SET class_id = :class_id WHERE id = :id";
            $this->db->update($sql, [':class_id' => $classId, ':id' => $playerId]);
            
            $this->db->commit();
            
            $class = $this->getClassById($classId);
            return ['success' => true, 'message' => "Zu '{$class['name']}' gewechselt!"];
            
        } catch(\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Fehler beim Wechseln der Klasse'];
        }
    }
}