<?php
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

    public function getAdminLevelInfo(int $level): ?array {
        // Beispiel: Admin-Level-Konfiguration
        $levels = [
            1 => ['name' => 'Moderator', 'permissions' => ['edit', 'delete']],
            2 => ['name' => 'Admin', 'permissions' => ['edit', 'delete', 'ban']],
        ];
        return $levels[$level] ?? null;
    }
}
