<?php
namespace App\Services\Player;

use App\Core\Database;
use App\Core\Logger;
use App\Core\EventManager;

class InventoryService {
    private Database $db;
    private Logger $logger;
    private EventManager $events;

    public function __construct(Database $db, Logger $logger, EventManager $events) {
        $this->db = $db;
        $this->logger = $logger;
        $this->events = $events;
    }

    public function addItem(int $playerId, int $itemId, int $amount = 1): bool {
        $sql = "INSERT INTO player_inventory (player_id, item_id, amount) 
                VALUES (:player_id, :item_id, :amount)
                ON DUPLICATE KEY UPDATE amount = amount + :amount";
        $this->db->update($sql, [':player_id'=>$playerId, ':item_id'=>$itemId, ':amount'=>$amount]);
        $this->events->emit('player.inventory_updated', ['playerId'=>$playerId, 'itemId'=>$itemId, 'amount'=>$amount]);
        return true;
    }
}
