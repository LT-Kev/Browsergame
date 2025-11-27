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

    $('#player-gold').html('<span class="iconify" data-icon="twemoji:coin" data-width="20" data-height="20"></span> ' + formatNumber(data.gold || 0));
    $('#player-food').html('<span class="iconify" data-icon="twemoji:poultry-leg" data-width="20" data-height="20"></span> ' + formatNumber(data.food || 0));
    $('#player-wood').html('<span class="iconify" data-icon="twemoji:wood" data-width="20" data-height="20"></span> ' + formatNumber(data.wood || 0));
    $('#player-stone').html('<span class="iconify" data-icon="twemoji:rock" data-width="20" data-height="20"></span> ' + formatNumber(data.stone || 0));
    $('#player-energy').html('<span class="iconify" data-icon="twemoji:high-voltage" data-width="20" data-height="20"></span> ' + (data.energy || 0) + '/100');

    $('#player-gold').attr('title', 'Gold: ' + data.gold + ' / ' + data.gold_capacity);
    $('#player-food').attr('title', 'Nahrung: ' + data.food + ' / ' + data.food_capacity);
    $('#player-wood').attr('title', 'Holz: ' + data.wood + ' / ' + data.wood_capacity);
    $('#player-stone').attr('title', 'Stein: ' + data.stone + ' / ' + data.stone_capacity);

    $('#char-level').text(data.level || 1);
    $('#char-exp').text(data.exp || 0);
    $('#char-exp-needed').text(data.exp_needed || 100);
    $('#char-hp').text(data.hp || 100);
    $('#char-max-hp').text(data.max_hp || 100);
    $('#char-attack').text(data.attack || 10);
    $('#char-defense').text(data.defense || 10);

    $('#gold-storage').text(formatNumber(data.gold || 0));
    $('#food-storage').text(formatNumber(data.food || 0));
    $('#wood-storage').text(formatNumber(data.wood || 0));
    $('#stone-storage').text(formatNumber(data.stone || 0));

    $('#prod-gold').text(data.gold_production || 0);
    $('#prod-food').text(data.food_production || 0);
    $('#prod-wood').text(data.wood_production || 0);
    $('#prod-stone').text(data.stone_production || 0);
}

export function reloadPlayerData() {
    loadPlayerData();
}
