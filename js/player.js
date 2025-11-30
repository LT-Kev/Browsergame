// player.js
import { formatNumber } from './utils.js';

export function loadPlayerData() {
    console.log("📥 loadPlayerData aufgerufen");
    $.ajax({
        url: 'ajax/get_player_data.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.error) {
                console.error('❌ ' + data.error);
                return;
            }
            updatePlayerDisplay(data);
        },
        error: function(xhr, status, error) {
            console.error('❌ Spielerdaten konnten nicht geladen werden', status, error);
        }
    });
}

export function updatePlayerDisplay(data) {
    console.log("🔧 updatePlayerDisplay aufgerufen", data);

    // Topbar - Ressourcen
    $('#player-gold').html('<span class="iconify" data-icon="twemoji:coin" data-width="20" data-height="20"></span> ' + formatNumber(data.gold || 0));
    $('#player-food').html('<span class="iconify" data-icon="twemoji:poultry-leg" data-width="20" data-height="20"></span> ' + formatNumber(data.food || 0));
    $('#player-wood').html('<span class="iconify" data-icon="twemoji:wood" data-width="20" data-height="20"></span> ' + formatNumber(data.wood || 0));
    $('#player-stone').html('<span class="iconify" data-icon="twemoji:rock" data-width="20" data-height="20"></span> ' + formatNumber(data.stone || 0));
    $('#player-energy').html('<span class="iconify" data-icon="twemoji:high-voltage" data-width="20" data-height="20"></span> ' + (data.energy || 0) + '/100');

    // Charakterinfo - Basis
    $('#char-level').text(data.level || 1);
    $('#char-exp').text(data.exp || 0);
    $('#char-exp-needed').text(data.exp_needed || 100);
    $('#char-hp').text(data.hp || 100);
    $('#char-max-hp').text(data.max_hp || 100);
    $('#char-attack').text(data.attack || 10);
    $('#char-defense').text(data.defense || 10);
    
    // RPG Stats (NEU)
    if(data.character_created) {
        $('#char-mana').text(data.mana || 100);
        $('#char-max-mana').text(data.max_mana || 100);
        $('#char-stamina').text(data.stamina || 100);
        $('#char-max-stamina').text(data.max_stamina || 100);
        
        $('#char-strength').text(data.strength || 10);
        $('#char-dexterity').text(data.dexterity || 10);
        $('#char-constitution').text(data.constitution || 10);
        $('#char-intelligence').text(data.intelligence || 10);
        $('#char-wisdom').text(data.wisdom || 10);
        $('#char-charisma').text(data.charisma || 10);
    }

    // Lager
    $('#gold-storage').text(formatNumber(data.gold || 0));
    $('#food-storage').text(formatNumber(data.food || 0));
    $('#wood-storage').text(formatNumber(data.wood || 0));
    $('#stone-storage').text(formatNumber(data.stone || 0));

    // Produktion
    $('#prod-gold').text(data.gold_production || 0);
    $('#prod-food').text(data.food_production || 0);
    $('#prod-wood').text(data.wood_production || 0);
    $('#prod-stone').text(data.stone_production || 0);
}

export function reloadPlayerData() {
    loadPlayerData();
}