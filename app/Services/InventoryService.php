<?php
// ============================================================================
// app/Services/InventoryService.php
// ============================================================================
namespace App\Services;

use App\Core\Database;

class InventoryService {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function getPlayerInventory(int $playerId): array {
        $sql = "SELECT i.*, it.name, it.description, it.type, it.attack_bonus, it.defense_bonus, it.hp_bonus, it.price 
                FROM inventory i 
                JOIN items it ON i.item_id = it.id 
                WHERE i.player_id = :player_id 
                ORDER BY it.type, it.name";
        return $this->db->select($sql, [':player_id' => $playerId]);
    }

    public function addItem(int $playerId, int $itemId, int $quantity = 1): mixed {
        $sql = "SELECT * FROM inventory WHERE player_id = :player_id AND item_id = :item_id LIMIT 1";
        $existing = $this->db->selectOne($sql, [':player_id' => $playerId, ':item_id' => $itemId]);

        if($existing) {
            $sql = "UPDATE inventory SET quantity = quantity + :quantity WHERE player_id = :player_id AND item_id = :item_id";
            return $this->db->update($sql, [':quantity' => $quantity, ':player_id' => $playerId, ':item_id' => $itemId]);
        }

        $sql = "INSERT INTO inventory (player_id, item_id, quantity) VALUES (:player_id, :item_id, :quantity)";
        return $this->db->insert($sql, [':player_id' => $playerId, ':item_id' => $itemId, ':quantity' => $quantity]);
    }

    public function removeItem(int $playerId, int $itemId, int $quantity = 1): mixed {
        $sql = "SELECT * FROM inventory WHERE player_id = :player_id AND item_id = :item_id LIMIT 1";
        $item = $this->db->selectOne($sql, [':player_id' => $playerId, ':item_id' => $itemId]);
        if(!$item) return false;

        if($item['quantity'] <= $quantity) {
            $sql = "DELETE FROM inventory WHERE player_id = :player_id AND item_id = :item_id";
            return $this->db->delete($sql, [':player_id' => $playerId, ':item_id' => $itemId]);
        }

        $sql = "UPDATE inventory SET quantity = quantity - :quantity WHERE player_id = :player_id AND item_id = :item_id";
        return $this->db->update($sql, [':quantity' => $quantity, ':player_id' => $playerId, ':item_id' => $itemId]);
    }

    public function equipItem(int $playerId, int $inventoryId): bool {
        $sql = "UPDATE inventory SET equipped = 1 WHERE id = :id AND player_id = :player_id";
        return $this->db->update($sql, [':id' => $inventoryId, ':player_id' => $playerId]) !== false;
    }

    public function unequipItem(int $playerId, int $inventoryId): bool {
        $sql = "UPDATE inventory SET equipped = 0 WHERE id = :id AND player_id = :player_id";
        return $this->db->update($sql, [':id' => $inventoryId, ':player_id' => $playerId]) !== false;
    }
}