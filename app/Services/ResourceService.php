<?php
namespace App\Services;

use App\Core\App;

class ResourceService {
    private App $app;

    public function __construct(App $app) {
        $this->app = $app;
    }

    public function updateResources(int $playerId): void {
        $db = $this->app->getDb();
        // Beispiel: Ressourcen alle 5 Minuten updaten
        $player = $this->app->getPlayer()->getPlayerById($playerId);
        $now = time();
        $diff = $now - ($player['last_resource_update'] ?? $now);
        if($diff > 300) { 
            $newResources = $player['resources'] + floor($diff * $player['production_rate']);
            $db->execute("UPDATE players SET resources = :res, last_resource_update = :now WHERE id = :id", [
                'res' => $newResources,
                'now' => $now,
                'id' => $playerId
            ]);
        }
    }
}
