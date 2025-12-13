<?php
namespace App\Services\Player;

use App\Core\Database;
use App\Core\Logger;
use App\Core\EventManager;

class QuestService {
    private Database $db;
    private Logger $logger;
    private EventManager $events;

    public function __construct(Database $db, Logger $logger, EventManager $events) {
        $this->db = $db;
        $this->logger = $logger;
        $this->events = $events;
    }

    public function completeQuest(int $playerId, int $questId): bool {
        $sql = "UPDATE player_quests SET completed = 1, completed_at = NOW() 
                WHERE player_id = :player_id AND quest_id = :quest_id";
        $this->db->update($sql, [':player_id'=>$playerId, ':quest_id'=>$questId]);
        $this->events->emit('player.quest_completed', ['playerId'=>$playerId, 'questId'=>$questId]);
        return true;
    }
}
