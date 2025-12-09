// ============================================================================
// js/map/map-travel.js - Travel System
// ============================================================================
const mapTravel = {
    start() {
        if (!mapPopup.currentLocation) return;
        
        const loc = mapPopup.currentLocation;
        const distance = Math.sqrt(
            Math.pow(loc.x - mapState.playerPos.x, 2) +
            Math.pow(loc.y - mapState.playerPos.y, 2)
        );
        
        if (!confirm(`Zu '${loc.name}' reisen?\n\nEntfernung: ${Math.round(distance)} Felder\nDauer: ~${Math.round(distance / 10)} Minuten\n\nReise starten?`)) {
            return;
        }
        
        $.ajax({
            url: 'ajax/travel_to_location.php',
            type: 'POST',
            data: {
                location_id: loc.id,
                x: loc.x,
                y: loc.y,
                csrf_token: CSRF_TOKEN
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.onTravelSuccess(loc);
                } else {
                    alert('❌ ' + response.message);
                }
            },
            error: () => {
                alert('❌ Fehler beim Reisen. Bitte versuche es erneut.');
            }
        });
    },
    
    onTravelSuccess(location) {
        alert('✅ Reise abgeschlossen!');
        
        // Position aktualisieren
        mapState.playerPos.x = location.x;
        mapState.playerPos.y = location.y;
        
        // UI aktualisieren
        document.getElementById('playerX').textContent = location.x;
        document.getElementById('playerY').textContent = location.y;
        
        // Kamera zentrieren
        centerOnPlayer();
        
        // Popup schließen
        mapPopup.close();
        
        // Nearby Locations aktualisieren
        mapNav.updateNearbyLocations();
        
        // Spielerdaten neu laden (optional)
        if (typeof loadPlayerData === 'function') {
            loadPlayerData();
        }
    }
};