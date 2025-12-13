<?php
// app/Events/Listeners/QuestCompletedListener.php
namespace App\Events;

class QuestCompletedListener {
    public int $playerId;
    public int $questId;
    public array $rewards;
    public \DateTime $completedAt;

    public function __construct(int $playerId, int $questId, array $rewards = []) {
        $this->playerId = $playerId;
        $this->questId = $questId;
        $this->rewards = $rewards;
        $this->completedAt = new \DateTime();
    }
}