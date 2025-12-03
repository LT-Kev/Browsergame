<?php

// ============================================================================
// app/Services/InventoryService.php
// ============================================================================

class InventoryService {
    private App $app;

    public function __construct(App $app) {
        $this->app = $app;
    }

    public function getPlayerInventory(int $playerId): array {
        $sql = "SELECT i.*, it.name, it.description, it.type, it.attack_bonus, it.defense_bonus, it.hp_bonus, it.price FROM inventory i JOIN items it ON i.item_id = it.id WHERE i.player_id = :player_id ORDER BY it.type, it.name";
        return $this->app->getDb()->select($sql, [':player_id' => $playerId]);
    }

    public function addItem(int $playerId, int $itemId, int $quantity = 1): mixed {
        $sql = "SELECT * FROM inventory WHERE player_id = :player_id AND item_id = :item_id LIMIT 1";
        $existing = $this->app->getDb()->selectOne($sql, [':player_id' => $playerId, ':item_id' => $itemId]);

        if($existing) {
            $sql = "UPDATE inventory SET quantity = quantity + :quantity WHERE player_id = :player_id AND item_id = :item_id";
            return $this->app->getDb()->update($sql, [':quantity' => $quantity, ':player_id' => $playerId, ':item_id' => $itemId]);
        }

        $sql = "INSERT INTO inventory (player_id, item_id, quantity) VALUES (:player_id, :item_id, :quantity)";
        return $this->app->getDb()->insert($sql, [':player_id' => $playerId, ':item_id' => $itemId, ':quantity' => $quantity]);
    }

    public function removeItem(int $playerId, int $itemId, int $quantity = 1): mixed {
        $sql = "SELECT * FROM inventory WHERE player_id = :player_id AND item_id = :item_id LIMIT 1";
        $item = $this->app->getDb()->selectOne($sql, [':player_id' => $playerId, ':item_id' => $itemId]);
        if(!$item) return false;

        if($item['quantity'] <= $quantity) {
            $sql = "DELETE FROM inventory WHERE player_id = :player_id AND item_id = :item_id";
            return $this->app->getDb()->delete($sql, [':player_id' => $playerId, ':item_id' => $itemId]);
        }

        $sql = "UPDATE inventory SET quantity = quantity - :quantity WHERE player_id = :player_id AND item_id = :item_id";
        return $this->app->getDb()->update($sql, [':quantity' => $quantity, ':player_id' => $playerId, ':item_id' => $itemId]);
    }

    public function equipItem(int $playerId, int $inventoryId): bool {
        $sql = "UPDATE inventory SET equipped = 1 WHERE id = :id AND player_id = :player_id";
        return $this->app->getDb()->update($sql, [':id' => $inventoryId, ':player_id' => $playerId]) !== false;
    }

    public function unequipItem(int $playerId, int $inventoryId): bool {
        $sql = "UPDATE inventory SET equipped = 0 WHERE id = :id AND player_id = :player_id";
        return $this->app->getDb()->update($sql, [':id' => $inventoryId, ':player_id' => $playerId]) !== false;
    }

    public function getAllItems(): array {
        $sql = "SELECT * FROM items ORDER BY type, name";
        return $this->app->getDb()->select($sql);
    }

    public function getItemById(int $itemId): ?array {
        $sql = "SELECT * FROM items WHERE id = :id LIMIT 1";
        return $this->app->getDb()->selectOne($sql, [':id' => $itemId]);
    }
}