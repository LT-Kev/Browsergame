<?php
namespace App\Services\Player;

use App\Core\Database;
use App\Core\Logger;
use App\Core\EventManager;

class PlayerService {
    public StatsService $stats;
    public ResourceService $resources;
    public InventoryService $inventory;
    public QuestService $quests;

    private EventManager $events;

    public function __construct(Database $db, Logger $logger, EventManager $events) {
        $this->events = $events;

        $this->stats = new StatsService($db, $logger, $events);
        $this->resources = new ResourceService($db, $logger, $events);
        $this->inventory = new InventoryService($db, $logger, $events);
        $this->quests = new QuestService($db, $logger, $events);
    }

    // Beispiel: zentraler Level-Up-Aufruf
    public function addExp(int $playerId, int $exp): void {
        $newLevel = $this->stats->addExp($playerId, $exp);
        if ($newLevel > 0) {
            $this->events->emit('player.level_up', [
                'playerId' => $playerId,
                'newLevel' => $newLevel
            ]);
        }
    }

    // Optional: weitere zentrale Methoden, die mehrere Sub-Services kombinieren
}
