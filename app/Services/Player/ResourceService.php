<?php
namespace App\Services\Player;

use App\Core\Database;
use App\Core\Logger;
use App\Core\EventManager;

class ResourceService {
    private Database $db;
    private Logger $logger;
    private EventManager $events;

    public function __construct(Database $db, Logger $logger, EventManager $events) {
        $this->db = $db;
        $this->logger = $logger;
        $this->events = $events;
    }

    public function updateResources(int $playerId, array $resources): bool {
        $allowed = ['gold','food','wood','stone'];
        $updates = [];
        $params = [':id' => $playerId];

        foreach($resources as $key => $value){
            if(in_array($key, $allowed)){
                $updates[] = "$key = $key + :$key";
                $params[":$key"] = (int)$value;
            }
        }

        if(empty($updates)) return false;

        $this->db->update("UPDATE players SET ".implode(',', $updates)." WHERE id = :id", $params);
        $this->events->emit('player.resources_updated', ['playerId'=>$playerId, 'changes'=>$resources]);
        $this->logger->debug("Player resources updated", ['player_id'=>$playerId, 'changes'=>$resources]);

        return true;
    }

    public function hasEnoughResources(int $playerId, array $costs): bool {
        $player = $this->db->selectOne("SELECT gold, food, wood, stone FROM players WHERE id = :id", [':id'=>$playerId]);
        if(!$player) return false;

        foreach ($costs as $key => $value) {
            if (($player[$key] ?? 0) < $value) return false;
        }

        return true;
    }
}
