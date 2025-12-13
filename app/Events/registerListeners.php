<?php
use App\Core\EventManager;
use App\Events\Listeners\PlayerLevelUpListener;

return function(EventManager $events) {
    PlayerLevelUpListener::register($events);
};
