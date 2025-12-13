<?php
use App\Core\EventManager;
use App\Events\Listeners\PlayerLevelUpListener;
use App\Events\Listeners\QuestCompletedListener;
use App\Events\Listeners\CombatListener;

return function(EventManager $events) {
    PlayerLevelUpListener::register($events);
    QuestCompletedListener::register($events);
    CombatListener::register($events);
};