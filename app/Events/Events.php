<?php
// app/Events/Events.php
namespace App\Events;

class Events {
    // Player Events
    const PLAYER_LEVEL_UP = 'player.level_up';
    const PLAYER_CREATED = 'player.created';
    const PLAYER_DIED = 'player.died';
    
    // Quest Events
    const QUEST_COMPLETED = 'quest.completed';
    const QUEST_STARTED = 'quest.started';
    const QUEST_FAILED = 'quest.failed';
    
    // Combat Events
    const COMBAT_STARTED = 'combat.started';
    const COMBAT_ENDED = 'combat.ended';
    const ENEMY_DEFEATED = 'enemy.defeated';
    
    // Resource Events
    const RESOURCE_GAINED = 'resource.gained';
    const RESOURCE_SPENT = 'resource.spent';
    
    // Item Events
    const ITEM_EQUIPPED = 'item.equipped';
    const ITEM_LOOTED = 'item.looted';
}