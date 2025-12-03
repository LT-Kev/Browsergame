<?php
namespace App\Services;

use App\Core\App;

class PlayerService {
    private App $app;

    public function __construct(App $app) {
        $this->app = $app;
    }

    public function getPlayerById(int $playerId): ?array {
        $db = $this->app->getDb();
        $result = $db->query("SELECT * FROM players WHERE id = :id", ['id' => $playerId]);
        return $result[0] ?? null;
    }

    public function reloadPlayerData(): ?array {
        $playerId = $this->app->getAuth()->getCurrentPlayerId();
        if(!$playerId) return null;

        return $this->getPlayerById($playerId);
    }

    public function characterCreated(int $playerId): bool {
        $player = $this->getPlayerById($playerId);
        return $player['character_created'] ?? false;
    }
}
