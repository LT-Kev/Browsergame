// js/main.js
$(document).ready(function() {
    console.log("📄 Document ready - main.js geladen");

    // Initial laden
    console.log("🚀 Initial loadPlayerData aufgerufen");
    loadPlayerData();
    
    // Auto-Update alle 30 Sekunden
    setInterval(function() {
        console.log("🔄 Auto-Update loadPlayerData aufgerufen");
        loadPlayerData();
    }, 30000);
    
    // Navigation Handler
    $('.menu-list li').on('click', function() {
        var page = $(this).data('page');
        console.log("📌 Menü geklickt, Seite:", page);
        loadPage(page);
    });
    
    function loadPage(page, params = {}) {
        var url = 'pages/' + page + '.php';

        // Query-Parameter anhängen
        if(Object.keys(params).length > 0) {
            url += '?' + $.param(params);
        }

        console.log("🌐 loadPage aufgerufen mit URL:", url);

        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                console.log("✅ Ajax-Erfolg für URL:", url);
                $('#center-content').html(response);
            },
            error: function(xhr, status, error) {
                console.error("❌ Ajax-Fehler für URL:", url, "Status:", status, "Error:", error);
                $('#center-content').html('<h2>Fehler</h2><p>Seite konnte nicht geladen werden: ' + url + '</p>');
            }
        });
    }

    // Bearbeiten-Button Handler (delegiert, da Tabelle dynamisch geladen wird)
    $(document).on('click', '.edit-player', function() {
        var playerId = $(this).data('id');
        console.log("✏️ Bearbeiten geklickt, Spieler-ID:", playerId);
        loadPage('admin/player_edit', { id: playerId });
    });

    
    function loadPlayerData() {
        console.log("📥 loadPlayerData aufgerufen");
        $.ajax({
            url: 'ajax/get_player_data.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                console.log("📊 Spielerdaten erhalten:", data);
                updatePlayerDisplay(data);
            },
            error: function(xhr, status, error) {
                console.error('❌ Spielerdaten konnten nicht geladen werden', status, error);
            }
        });
    }
    
    function updatePlayerDisplay(data) {
        console.log("🔧 updatePlayerDisplay aufgerufen", data);

        // Topbar aktualisieren
        var goldHtml = '<span class="iconify" data-icon="twemoji:coin" data-width="20" data-height="20"></span> ' + formatNumber(data.gold || 0);
        var foodHtml = '<span class="iconify" data-icon="twemoji:poultry-leg" data-width="20" data-height="20"></span> ' + formatNumber(data.food || 0);
        var woodHtml = '<span class="iconify" data-icon="twemoji:wood" data-width="20" data-height="20"></span> ' + formatNumber(data.wood || 0);
        var stoneHtml = '<span class="iconify" data-icon="twemoji:rock" data-width="20" data-height="20"></span> ' + formatNumber(data.stone || 0);
        var energyHtml = '<span class="iconify" data-icon="twemoji:high-voltage" data-width="20" data-height="20"></span> ' + (data.energy || 0) + '/100';
        
        $('#player-gold').html(goldHtml);
        $('#player-food').html(foodHtml);
        $('#player-wood').html(woodHtml);
        $('#player-stone').html(stoneHtml);
        $('#player-energy').html(energyHtml);
        
        // Tooltips aktualisieren
        $('#player-gold').attr('title', 'Gold: ' + data.gold + ' / ' + data.gold_capacity);
        $('#player-food').attr('title', 'Nahrung: ' + data.food + ' / ' + data.food_capacity);
        $('#player-wood').attr('title', 'Holz: ' + data.wood + ' / ' + data.wood_capacity);
        $('#player-stone').attr('title', 'Stein: ' + data.stone + ' / ' + data.stone_capacity);
        
        // Charakterinfo aktualisieren
        $('#char-level').text(data.level || 1);
        $('#char-exp').text(data.exp || 0);
        $('#char-exp-needed').text(data.exp_needed || 100);
        $('#char-hp').text(data.hp || 100);
        $('#char-max-hp').text(data.max_hp || 100);
        $('#char-attack').text(data.attack || 10);
        $('#char-defense').text(data.defense || 10);
        
        // Lager aktualisieren
        $('#gold-storage').text(formatNumber(data.gold || 0));
        $('#food-storage').text(formatNumber(data.food || 0));
        $('#wood-storage').text(formatNumber(data.wood || 0));
        $('#stone-storage').text(formatNumber(data.stone || 0));

        // Produktion pro Stunde aktualisieren
        $('#prod-gold').text(data.gold_production || 0);
        $('#prod-food').text(data.food_production || 0);
        $('#prod-wood').text(data.wood_production || 0);
        $('#prod-stone').text(data.stone_production || 0);
    }
    
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
});

// Globale Funktion zum neu laden der Spielerdaten (für andere Scripts)
function reloadPlayerData() {
    console.log("🔄 reloadPlayerData aufgerufen");
    $.ajax({
        url: 'ajax/get_player_data.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if(typeof updatePlayerDisplay === 'function') {
                console.log("🔧 updatePlayerDisplay aus reloadPlayerData");
                updatePlayerDisplay(data);
            }
        },
        error: function(xhr, status, error) {
            console.error("❌ Fehler beim Reload der Spielerdaten", status, error);
        }
    });
}
