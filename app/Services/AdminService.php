<?php
// ============================================================================
// app/Services/AdminService.php - FIXED
// ============================================================================

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

class AdminService {
    private Database $db;
    private PlayerService $player;
    private Logger $logger;

    public function __construct(Database $db, PlayerService $player) {
        $this->db = $db;
        $this->player = $player;
        $this->logger = new Logger('admin');
    }

    public function isAdmin(int $playerId): bool {
        $player = $this->player->getPlayerById($playerId);
        return !empty($player['admin_level']);
    }

    public function getAdminLevel(int $playerId): int {
        $player = $this->player->getPlayerById($playerId);
        return $player['admin_level'] ?? 0;
    }

    public function hasPermission(int $playerId, string $permissionKey): bool {
        $adminLevel = $this->getAdminLevel($playerId);
        if($adminLevel == 0) return false;
        if($adminLevel == 10) return true; // Owner hat alles

        $sql = "SELECT * FROM admin_permissions WHERE admin_level = :admin_level AND permission_key = :permission_key LIMIT 1";
        $permission = $this->db->selectOne($sql, [':admin_level' => $adminLevel, ':permission_key' => $permissionKey]);
        return $permission !== null;
    }

    public function getAdminLevelInfo(int $level): ?array {
        $sql = "SELECT * FROM admin_levels WHERE level = :level LIMIT 1";
        return $this->db->selectOne($sql, [':level' => $level]);
    }

    public function getAllAdminLevels(): array {
        $sql = "SELECT * FROM admin_levels ORDER BY level ASC";
        return $this->db->select($sql);
    }

    public function getPermissionsForLevel(int $level): array {
        $sql = "SELECT * FROM admin_permissions WHERE admin_level = :level ORDER BY permission_name";
        return $this->db->select($sql, [':level' => $level]);
    }

    public function getAllAdmins(): array {
        $sql = "SELECT p.id, p.username, p.email, p.admin_level, p.last_login, 
                al.name as level_name, al.color as level_color 
                FROM players p
                LEFT JOIN admin_levels al ON p.admin_level = al.level
                WHERE p.admin_level > 0
                ORDER BY p.admin_level DESC, p.username ASC";
        return $this->db->select($sql);
    }

    public function setAdminLevel(int $adminId, int $targetPlayerId, int $newLevel): array {
        $adminLevel = $this->getAdminLevel($adminId);
        $targetPlayer = $this->player->getPlayerById($targetPlayerId);

        if(!$targetPlayer) return ['success' => false, 'message' => 'Spieler nicht gefunden'];

        if($adminLevel < 10) {
            if($adminLevel == 7 && $newLevel > 6) {
                return ['success' => false, 'message' => 'Du kannst nur Admins bis Level 6 ernennen'];
            }
            if($newLevel >= $adminLevel) {
                return ['success' => false, 'message' => 'Du kannst kein Level >= deinem eigenen Level vergeben'];
            }
        }

        $sql = "UPDATE players SET admin_level = :level WHERE id = :id";
        $result = $this->db->update($sql, [':level' => $newLevel, ':id' => $targetPlayerId]);
        
        if($result !== false) {
            $this->logger->info("Admin level changed", [
                'admin_id' => $adminId,
                'target_player_id' => $targetPlayerId,
                'target_username' => $targetPlayer['username'],
                'new_level' => $newLevel
            ]);
            
            return ['success' => true, 'message' => 'Admin-Level erfolgreich geändert'];
        }

        return ['success' => false, 'message' => 'Fehler beim Ändern des Admin-Levels'];
    }
    
    public function editPlayerResources(int $adminId, int $targetPlayerId, array $resources): array {
        if(!$this->hasPermission($adminId, 'edit_players')) {
            return ['success' => false, 'message' => 'Keine Berechtigung'];
        }
        
        $result = $this->player->updateResources($targetPlayerId, $resources);
        
        if($result) {
            $this->logger->info("Player resources edited by admin", [
                'admin_id' => $adminId,
                'target_player_id' => $targetPlayerId,
                'resources' => $resources
            ]);
            
            return ['success' => true, 'message' => 'Ressourcen erfolgreich geändert'];
        }
        
        return ['success' => false, 'message' => 'Fehler beim Ändern der Ressourcen'];
    }
}