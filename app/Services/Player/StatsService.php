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

    public function addExp(int $playerId, int $exp): int {
        $player = $this->db->selectOne("SELECT level, exp, character_created FROM players WHERE id = :id", [':id' => $playerId]);
        if (!$player) return 0;

        $newExp = $player['exp'] + $exp;
        $level = $player['level'];
        $leveledUp = false;

        while ($newExp >= $this->expNeeded($level)) {
            $newExp -= $this->expNeeded($level);
            $level++;
            $leveledUp = true;
        }

        $this->db->update(
            "UPDATE players SET exp = :exp, level = :level WHERE id = :id",
            [':exp' => $newExp, ':level' => $level, ':id' => $playerId]
        );

        if ($leveledUp && $player['character_created']) {
            $this->logger->info("Player leveled up", ['player_id' => $playerId, 'new_level' => $level]);
        }

        return $leveledUp ? $level : 0;
    }

    private function expNeeded(int $level): int {
        return $level * 100;
    }
}
