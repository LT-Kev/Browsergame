<?php
namespace App\Events\Listeners;

use App\Core\EventManager;
use App\Events\PlayerLevelUpEvent;

class PlayerLevelUpListener {
    public static function register(EventManager $events): void {
        $events->on(PlayerLevelUpEvent::class, function(PlayerLevelUpEvent $event){
            echo "Spieler {$event->playerId} ist auf Level {$event->newLevel} aufgestiegen!";
            // z.B. AchievementService::grant($event->playerId, 'LevelUp');
        });
    }
}
