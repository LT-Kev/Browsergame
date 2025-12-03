<?php
namespace App\Services;

use App\Core\App;

class BuildingService {
    private App $app;

    public function __construct(App $app) {
        $this->app = $app;
    }

    public function checkFinishedUpgrades(int $playerId): void {
        $db = $this->app->getDb();
        $now = time();
        $upgrades = $db->query("SELECT * FROM building_upgrades WHERE player_id = :id AND finished_at <= :now", [
            'id' => $playerId,
            'now' => $now
        ]);
        foreach($upgrades as $upgrade) {
            $db->execute("UPDATE buildings SET level = level + 1 WHERE id = :bid", ['bid' => $upgrade['building_id']]);
            $db->execute("DELETE FROM building_upgrades WHERE id = :id", ['id' => $upgrade['id']]);
        }
    }
}
