// ============================================================================
// js/map/map-popup.js - Location Popup System
// ============================================================================
const mapPopup = {
    currentLocation: null,
    
    show(location) {
        this.currentLocation = location;
        
        document.getElementById('popupIcon').textContent = location.icon;
        document.getElementById('popupName').textContent = location.name;
        document.getElementById('popupCoords').textContent = `X: ${location.x} | Y: ${location.y}`;
        document.getElementById('popupDescription').textContent = location.description;
        
        // Stats
        let statsHtml = '';
        statsHtml += `<div class="location-stat"><span class="location-stat-label">📊 Typ:</span><span class="location-stat-value">${this.getTypeLabel(location.type)}</span></div>`;
        statsHtml += `<div class="location-stat"><span class="location-stat-label">⭐ Level:</span><span class="location-stat-value">${location.level}</span></div>`;
        
        if (location.enemyLevel) {
            statsHtml += `<div class="location-stat"><span class="location-stat-label">👹 Gegner:</span><span class="location-stat-value">Level ${location.enemyLevel}</span></div>`;
        }
        
        statsHtml += `<div class="location-stat"><span class="location-stat-label">📍 Features:</span><span class="location-stat-value">${location.features.join(', ')}</span></div>`;
        
        const distance = Math.sqrt(
            Math.pow(location.x - mapState.playerPos.x, 2) +
            Math.pow(location.y - mapState.playerPos.y, 2)
        );
        statsHtml += `<div class="location-stat"><span class="location-stat-label">📏 Entfernung:</span><span class="location-stat-value">${Math.round(distance)} Felder</span></div>`;
        
        document.getElementById('popupStats').innerHTML = statsHtml;
        document.getElementById('locationPopup').style.display = 'flex';
    },
    
    close() {
        document.getElementById('locationPopup').style.display = 'none';
        this.currentLocation = null;
    },
    
    getTypeLabel(type) {
        const labels = {
            'city': 'Stadt',
            'dungeon': 'Dungeon',
            'resource': 'Ressource',
            'boss': 'Boss',
            'special': 'Spezial'
        };
        return labels[type] || type;
    }
};