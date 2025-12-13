<?php
namespace App\Services\Player;

use App\Core\Database;
use App\Core\Logger;
use App\Core\EventManager;

class StatsService {
    private Database $db;
    private Logger $logger;
    private EventManager $events;

    public function __construct(Database $db, Logger $logger, EventManager $events) {
        $this->db = $db;
        $this->logger = $logger;
        $this->events = $events;
    }

    public function addExp(int $playerId, int $exp): bool {
        $player = $this->playerService->getPlayerById($playerId);
        if (!$player) return false;

        $newExp = $player['exp'] + $exp;
        $leveledUp = false;
        $newLevel = $player['level'];

        // Level-Up Check
        while ($newExp >= $player['exp_needed']) {
            $newExp -= $player['exp_needed'];
            $newLevel++;
            $leveledUp = true;
        }

        // Update DB
        $this->db->update('players', [
            'exp' => $newExp,
            'level' => $newLevel
        ], ['id' => $playerId]);

        // EVENT FEUERN
        if ($leveledUp) {
            $app = \App\Core\App::getInstance();
            $event = new \App\Events\PlayerLevelUpEvent($playerId, $newLevel);
            $app->getEventManager()->emit(\App\Events\Events::PLAYER_LEVEL_UP, $event);
        }

        return true;
    }

    private function expNeeded(int $level): int {
        return $level * 100;
    }
}
