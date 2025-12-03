<?php

// ============================================================================
// app/Services/BuildingService.php
// ============================================================================

class BuildingService {
    private App $app;

    public function __construct(App $app) {
        $this->app = $app;
    }

    public function getAllBuildingTypes(): array {
        $sql = "SELECT * FROM building_types ORDER BY type, name";
        return $this->app->getDb()->select($sql);
    }

    public function getBuildingTypeById(int $buildingTypeId): ?array {
        $sql = "SELECT * FROM building_types WHERE id = :id LIMIT 1";
        return $this->app->getDb()->selectOne($sql, [':id' => $buildingTypeId]);
    }

    public function getPlayerBuildings(int $playerId): array {
        $sql = "SELECT pb.*, bt.* FROM player_buildings pb JOIN building_types bt ON pb.building_type_id = bt.id WHERE pb.player_id = :player_id ORDER BY bt.type, bt.name";
        return $this->app->getDb()->select($sql, [':player_id' => $playerId]);
    }

    public function getPlayerBuilding(int $playerId, int $buildingTypeId): ?array {
        $sql = "SELECT pb.*, bt.* FROM player_buildings pb JOIN building_types bt ON pb.building_type_id = bt.id WHERE pb.player_id = :player_id AND pb.building_type_id = :building_type_id LIMIT 1";
        return $this->app->getDb()->selectOne($sql, [':player_id' => $playerId, ':building_type_id' => $buildingTypeId]);
    }

    public function getUpgradeCost(int $buildingTypeId, int $currentLevel): array {
        $buildingType = $this->getBuildingTypeById($buildingTypeId);
        if(!$buildingType) return [];

        $multiplier = pow($buildingType['cost_multiplier'], $currentLevel);
        return [
            'gold' => ceil($buildingType['base_gold_cost'] * $multiplier),
            'food' => ceil($buildingType['base_food_cost'] * $multiplier),
            'wood' => ceil($buildingType['base_wood_cost'] * $multiplier),
            'stone' => ceil($buildingType['base_stone_cost'] * $multiplier),
            'time' => ceil($buildingType['base_build_time'] * $multiplier)
        ];
    }

    public function upgradeBuilding(int $playerId, int $buildingTypeId): array {
        $building = $this->getPlayerBuilding($playerId, $buildingTypeId);
        if(!$building) return ['success' => false, 'message' => 'Gebäude nicht gefunden'];
        if($building['is_upgrading']) return ['success' => false, 'message' => 'Gebäude wird bereits ausgebaut'];
        if($building['level'] >= $building['max_level']) return ['success' => false, 'message' => 'Maximales Level erreicht'];

        $costs = $this->getUpgradeCost($buildingTypeId, $building['level']);
        if(!$this->app->getPlayer()->hasEnoughResources($playerId, $costs)) {
            return ['success' => false, 'message' => 'Nicht genug Ressourcen'];
        }

        $this->app->getPlayer()->updateResources($playerId, [
            'gold' => -$costs['gold'],
            'food' => -$costs['food'],
            'wood' => -$costs['wood'],
            'stone' => -$costs['stone']
        ]);

        $finishTime = date('Y-m-d H:i:s', time() + $costs['time']);
        $sql = "UPDATE player_buildings SET is_upgrading = 1, upgrade_finish_time = :finish_time WHERE player_id = :player_id AND building_type_id = :building_type_id";
        $this->app->getDb()->update($sql, [':finish_time' => $finishTime, ':player_id' => $playerId, ':building_type_id' => $buildingTypeId]);

        return ['success' => true, 'message' => 'Upgrade gestartet', 'finish_time' => $finishTime, 'duration' => $costs['time']];
    }

    public function checkFinishedUpgrades(int $playerId): int {
        $sql = "SELECT pb.*, uq.target_level, uq.id as queue_id FROM player_buildings pb JOIN upgrade_queue uq ON pb.id = uq.building_id WHERE pb.player_id = :player_id AND pb.is_upgrading = 1 AND uq.finish_time <= NOW()";
        $finishedBuildings = $this->app->getDb()->select($sql, [':player_id' => $playerId]);

        foreach($finishedBuildings as $building) {
            $sql = "UPDATE player_buildings SET level = :level, is_upgrading = 0, upgrade_finish_time = NULL WHERE id = :id";
            $this->app->getDb()->update($sql, [':level' => $building['target_level'], ':id' => $building['id']]);

            $sql = "DELETE FROM upgrade_queue WHERE id = :id";
            $this->app->getDb()->delete($sql, [':id' => $building['queue_id']]);

            $this->updatePlayerProduction($playerId);
        }

        return count($finishedBuildings);
    }

    public function updatePlayerProduction(int $playerId): void {
        $buildings = $this->getPlayerBuildings($playerId);
        $goldProd = 10;
        $foodProd = 10;
        $woodProd = 10;
        $stoneProd = 10;
        $goldCap = 1000;
        $foodCap = 1000;
        $woodCap = 1000;
        $stoneCap = 1000;

        foreach($buildings as $building) {
            $goldProd += $building['produces_gold'] * $building['level'];
            $foodProd += $building['produces_food'] * $building['level'];
            $woodProd += $building['produces_wood'] * $building['level'];
            $stoneProd += $building['produces_stone'] * $building['level'];
            $goldCap += $building['increases_gold_capacity'] * $building['level'];
            $foodCap += $building['increases_food_capacity'] * $building['level'];
            $woodCap += $building['increases_wood_capacity'] * $building['level'];
            $stoneCap += $building['increases_stone_capacity'] * $building['level'];
        }

        $sql = "UPDATE players SET gold_production = :gp, food_production = :fp, wood_production = :wp, stone_production = :sp, gold_capacity = :gc, food_capacity = :fc, wood_capacity = :wc, stone_capacity = :sc WHERE id = :id";
        $this->app->getDb()->update($sql, [
            ':gp' => $goldProd, ':fp' => $foodProd, ':wp' => $woodProd, ':sp' => $stoneProd,
            ':gc' => $goldCap, ':fc' => $foodCap, ':wc' => $woodCap, ':sc' => $stoneCap, ':id' => $playerId
        ]);
    }
}