<?php
namespace App\Events;

class PlayerLevelUpEvent {
    public int $playerId;
    public int $newLevel;

    public function __construct(int $playerId, int $newLevel) {
        $this->playerId = $playerId;
        $this->newLevel = $newLevel;
    }
}
