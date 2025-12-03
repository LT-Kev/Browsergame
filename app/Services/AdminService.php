<?php
// ============================================================================
// app/Services/AdminService.php
// ============================================================================

namespace App\Services;

use App\Core\App;

class AdminService {
    private App $app;

    public function __construct(App $app) {
        $this->app = $app;
    }

    public function isAdmin(int $playerId): bool {
        $player = $this->app->getPlayer()->getPlayerById($playerId);
        return !empty($player['admin_level']);
    }

    public function getAdminLevel(int $playerId): int {
        $player = $this->app->getPlayer()->getPlayerById($playerId);
        return $player['admin_level'] ?? 0;
    }

    public function hasPermission(int $playerId, string $permissionKey): bool {
        $adminLevel = $this->getAdminLevel($playerId);
        if($adminLevel == 0) return false;
        if($adminLevel == 10) return true; // Owner hat alles

        $sql = "SELECT * FROM admin_permissions WHERE admin_level = :admin_level AND permission_key = :permission_key LIMIT 1";
        $permission = $this->app->getDb()->selectOne($sql, [':admin_level' => $adminLevel, ':permission_key' => $permissionKey]);
        return $permission !== null;
    }

    public function getAdminLevelInfo(int $level): ?array {
        $sql = "SELECT * FROM admin_levels WHERE level = :level LIMIT 1";
        return $this->app->getDb()->selectOne($sql, [':level' => $level]);
    }

    public function getAllAdminLevels(): array {
        $sql = "SELECT * FROM admin_levels ORDER BY level ASC";
        return $this->app->getDb()->select($sql);
    }

    public function getPermissionsForLevel(int $level): array {
        $sql = "SELECT * FROM admin_permissions WHERE admin_level = :level ORDER BY permission_name";
        return $this->app->getDb()->select($sql, [':level' => $level]);
    }

    public function getAllAdmins(): array {
        $sql = "SELECT p.id, p.username, p.email, p.admin_level, p.last_login, al.name as level_name, al.color as level_color FROM players p LEFT JOIN admin_levels al ON p.admin_level = al.level WHERE p.admin_level > 0 ORDER BY p.admin_level DESC, p.username ASC";
        return $this->app->getDb()->select($sql);
    }

    public function setAdminLevel(int $adminId, int $targetPlayerId, int $newLevel): array {
        $adminLevel = $this->getAdminLevel($adminId);
        $targetPlayer = $this->app->getPlayer()->getPlayerById($targetPlayerId);

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
        $this->app->getDb()->update($sql, [':level' => $newLevel, ':id' => $targetPlayerId]);

        return ['success' => true, 'message' => 'Admin-Level erfolgreich geändert'];
    }
}