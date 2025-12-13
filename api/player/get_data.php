<?php
/**
 * /api/player/get_data.php
 * Liefert Spieler-Daten als JSON
 * Dev-Fallback und Debug werden automatisch über ApiResponse gehandhabt
 */

use App\Core\App;
use App\Api\ApiResponse;

require_once __DIR__ . '/../../init.php';

$app = App::getInstance();
$api = new ApiResponse($app);

// Hier definierst du die Felder, die zurückgegeben werden sollen
$fields = [
    'username',
    'gold',
    'food',
    'wood',
    'stone',
    'energy',
    'level',
    'exp',
    'exp_needed',
    'hp',
    'max_hp',
    'attack',
    'defense',
    'mana',
    'max_mana',
    'stamina',
    'max_stamina',
    'strength',
    'dexterity',
    'constitution',
    'intelligence',
    'wisdom',
    'charisma',
    'stat_points',
    'character_created',
    'race_id',
    'class_id',
    'admin_level'
];

// Daten holen
$response = $api->getPlayerData($fields);

// Response senden
$api->sendResponse($response);
