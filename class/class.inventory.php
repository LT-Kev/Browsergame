<?php
class Inventory {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // Inventar eines Spielers holen
    public function getPlayerInventory($playerId) {
        $sql = "SELECT i.*, it.name, it.description, it.type, it.attack_bonus, 
                it.defense_bonus, it.hp_bonus, it.price 
                FROM inventory i 
                JOIN items it ON i.item_id = it.id 
                WHERE i.player_id = :player_id 
                ORDER BY it.type, it.name";
        
        return $this->db->select($sql, array(':player_id' => $playerId));
    }
    
    // Item zum Inventar hinzufügen
    public function addItem($playerId, $itemId, $quantity = 1) {
        // Prüfen ob Item bereits vorhanden
        $sql = "SELECT * FROM inventory WHERE player_id = :player_id AND item_id = :item_id LIMIT 1";
        $existing = $this->db->selectOne($sql, array(':player_id' => $playerId, ':item_id' => $itemId));
        
        if($existing) {
            // Menge erhöhen
            $sql = "UPDATE inventory SET quantity = quantity + :quantity 
                    WHERE player_id = :player_id AND item_id = :item_id";
            return $this->db->update($sql, array(
                ':quantity' => $quantity,
                ':player_id' => $playerId,
                ':item_id' => $itemId
            ));
        } else {
            // Neues Item hinzufügen
            $sql = "INSERT INTO inventory (player_id, item_id, quantity) 
                    VALUES (:player_id, :item_id, :quantity)";
            return $this->db->insert($sql, array(
                ':player_id' => $playerId,
                ':item_id' => $itemId,
                ':quantity' => $quantity
            ));
        }
    }
    
    // Item aus Inventar entfernen
    public function removeItem($playerId, $itemId, $quantity = 1) {
        $sql = "SELECT * FROM inventory WHERE player_id = :player_id AND item_id = :item_id LIMIT 1";
        $item = $this->db->selectOne($sql, array(':player_id' => $playerId, ':item_id' => $itemId));
        
        if(!$item) {
            return false;
        }
        
        if($item['quantity'] <= $quantity) {
            // Item komplett entfernen
            $sql = "DELETE FROM inventory WHERE player_id = :player_id AND item_id = :item_id";
            return $this->db->delete($sql, array(':player_id' => $playerId, ':item_id' => $itemId));
        } else {
            // Menge reduzieren
            $sql = "UPDATE inventory SET quantity = quantity - :quantity 
                    WHERE player_id = :player_id AND item_id = :item_id";
            return $this->db->update($sql, array(
                ':quantity' => $quantity,
                ':player_id' => $playerId,
                ':item_id' => $itemId
            ));
        }
    }
    
    // Item anlegen/ablegen
    public function equipItem($playerId, $inventoryId) {
        $sql = "UPDATE inventory SET equipped = 1 WHERE id = :id AND player_id = :player_id";
        return $this->db->update($sql, array(':id' => $inventoryId, ':player_id' => $playerId));
    }
    
    public function unequipItem($playerId, $inventoryId) {
        $sql = "UPDATE inventory SET equipped = 0 WHERE id = :id AND player_id = :player_id";
        return $this->db->update($sql, array(':id' => $inventoryId, ':player_id' => $playerId));
    }
    
    // Alle Items holen
    public function getAllItems() {
        $sql = "SELECT * FROM items ORDER BY type, name";
        return $this->db->select($sql);
    }
    
    // Item nach ID holen
    public function getItemById($itemId) {
        $sql = "SELECT * FROM items WHERE id = :id LIMIT 1";
        return $this->db->selectOne($sql, array(':id' => $itemId));
    }
}
?>