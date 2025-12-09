<?php
// pages/map.php
require_once __DIR__ . '/../init.php';

use App\Core\App;

$app = App::getInstance();
$auth = $app->getAuth();

$playerId = $auth->getCurrentPlayerId();
if(!$playerId) {
    echo '<p>Nicht eingeloggt</p>';
    exit;
}

$player = $app->getPlayer()->getPlayerById($playerId);

// Spieler-Position (falls noch nicht vorhanden, Standard setzen)
$playerX = $player['world_x'] ?? 500;
$playerY = $player['world_y'] ?? 500;
?>

<style>
    .map-page {
        display: grid;
        grid-template-columns: 300px 1fr 300px;
        gap: 20px;
        height: calc(100vh - 200px);
    }

    .map-sidebar {
        background: linear-gradient(135deg, #1e2a3a 0%, #0f1922 100%);
        border: 2px solid rgba(233, 69, 96, 0.3);
        border-radius: 15px;
        padding: 20px;
        overflow-y: auto;
        max-height: 800px;
    }

    .map-sidebar h3 {
        color: #e94560;
        margin-bottom: 15px;
        font-size: 1.2em;
    }

    .map-main {
        position: relative;
        background: linear-gradient(135deg, #0a0e27 0%, #1a1e3a 100%);
        border: 2px solid rgba(233, 69, 96, 0.3);
        border-radius: 15px;
        overflow: hidden;
        height: 800px;
    }

    .control-group {
        margin-bottom: 15px;
    }

    .control-group label {
        display: block;
        color: #bdc3c7;
        margin-bottom: 5px;
        font-size: 0.9em;
    }

    .control-group input {
        width: 100%;
        padding: 8px;
        background: rgba(0, 0, 0, 0.5);
        border: 2px solid rgba(255, 255, 255, 0.1);
        border-radius: 5px;
        color: #fff;
        font-size: 1em;
    }

    .control-group input:focus {
        outline: none;
        border-color: #e94560;
    }

    .btn {
        padding: 10px 20px;
        background: linear-gradient(135deg, #e94560, #d63251);
        border: none;
        border-radius: 8px;
        color: #fff;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
        width: 100%;
        margin-top: 10px;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(233, 69, 96, 0.5);
    }

    .btn-secondary {
        background: linear-gradient(135deg, #3498db, #2980b9);
    }

    .btn-secondary:hover {
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.5);
    }

    .current-coords {
        background: rgba(46, 204, 113, 0.2);
        padding: 10px;
        border-radius: 8px;
        border-left: 3px solid #2ecc71;
        margin-bottom: 15px;
    }

    .current-coords strong {
        color: #2ecc71;
    }

    .zoom-controls {
        position: absolute;
        bottom: 20px;
        right: 20px;
        display: flex;
        gap: 10px;
        z-index: 10;
    }

    .zoom-btn {
        width: 40px;
        height: 40px;
        background: rgba(15, 20, 35, 0.95);
        border: 2px solid rgba(233, 69, 96, 0.3);
        border-radius: 8px;
        color: #e94560;
        font-size: 1.3em;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .zoom-btn:hover {
        background: rgba(233, 69, 96, 0.2);
        border-color: #e94560;
        transform: scale(1.1);
    }

    #worldCanvas {
        cursor: move;
        image-rendering: pixelated;
    }

    .location-info h4 {
        color: #3498db;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .location-icon {
        font-size: 2em;
    }

    .location-coords {
        color: #95a5a6;
        font-size: 0.9em;
        margin-bottom: 10px;
    }

    .location-description {
        color: #bdc3c7;
        margin-bottom: 15px;
        line-height: 1.6;
        font-size: 0.95em;
    }

    .location-stats {
        background: rgba(0, 0, 0, 0.3);
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 15px;
    }

    .location-stat {
        display: flex;
        justify-content: space-between;
        margin: 6px 0;
        font-size: 0.9em;
    }

    .location-stat-label {
        color: #95a5a6;
    }

    .location-stat-value {
        color: #2ecc71;
        font-weight: bold;
    }

    .minimap {
        width: 100%;
        height: 200px;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 8px;
        margin-bottom: 15px;
        position: relative;
    }

    .minimap canvas {
        width: 100%;
        height: 100%;
        border-radius: 8px;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 8px 0;
        font-size: 0.85em;
    }

    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: 4px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .nearby-locations {
        margin-top: 15px;
    }

    .nearby-locations h4 {
        color: #f39c12;
        margin-bottom: 10px;
        font-size: 1em;
    }

    .nearby-location-item {
        background: rgba(0, 0, 0, 0.3);
        padding: 10px;
        border-radius: 5px;
        margin: 8px 0;
        cursor: pointer;
        transition: all 0.3s;
        border-left: 3px solid #3498db;
    }

    .nearby-location-item:hover {
        background: rgba(52, 152, 219, 0.2);
        transform: translateX(5px);
    }

    .nearby-location-name {
        font-weight: bold;
        color: #3498db;
        margin-bottom: 5px;
        font-size: 0.9em;
    }

    .nearby-location-distance {
        font-size: 0.8em;
        color: #95a5a6;
    }

    .map-sidebar::-webkit-scrollbar {
        width: 8px;
    }

    .map-sidebar::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.3);
        border-radius: 10px;
    }

    .map-sidebar::-webkit-scrollbar-thumb {
        background: rgba(233, 69, 96, 0.5);
        border-radius: 10px;
    }

    .map-sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(233, 69, 96, 0.8);
    }

    .no-location-selected {
        text-align: center;
        padding: 40px 20px;
        color: #95a5a6;
    }

    .no-location-icon {
        font-size: 3em;
        margin-bottom: 10px;
    }
</style>

<h2 style="margin-bottom: 20px;">🗺️ Weltkarte</h2>

<div class="map-page">
    <!-- Linke Sidebar: Navigation -->
    <div class="map-sidebar">
        <h3>🎯 Navigation</h3>
        
        <div class="current-coords">
            <strong>📍 Deine Position:</strong><br>
            X: <span id="playerX"><?php echo $playerX; ?></span> | 
            Y: <span id="playerY"><?php echo $playerY; ?></span>
        </div>

        <div class="control-group">
            <label>🔍 Koordinaten suchen:</label>
            <input type="number" id="searchX" placeholder="X-Koordinate" value="<?php echo $playerX; ?>">
        </div>

        <div class="control-group">
            <input type="number" id="searchY" placeholder="Y-Koordinate" value="<?php echo $playerY; ?>">
        </div>

        <button class="btn" onclick="jumpToCoords()">📍 Zu Koordinaten</button>
        <button class="btn btn-secondary" onclick="centerOnPlayer()">🏠 Meine Position</button>

        <div style="margin-top: 20px;">
            <h4 style="color: #e94560; margin-bottom: 10px;">📋 Legende</h4>
            <div class="legend-item">
                <div class="legend-color" style="background: #2ecc71;"></div>
                <span>Grasland</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #27ae60;"></div>
                <span>Wald</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #95a5a6;"></div>
                <span>Berge</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #3498db;"></div>
                <span>Wasser</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #f39c12;"></div>
                <span>Wüste</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #ecf0f1;"></div>
                <span>Schnee</span>
            </div>
        </div>

        <div class="nearby-locations">
            <h4>🏛️ Orte in der Nähe</h4>
            <div id="nearbyLocationsList"></div>
        </div>
    </div>

    <!-- Mitte: Karte -->
    <div class="map-main">
        <canvas id="worldCanvas"></canvas>

        <div class="zoom-controls">
            <button class="zoom-btn" onclick="zoomIn()" title="Zoom In">+</button>
            <button class="zoom-btn" onclick="resetZoom()" title="Reset">⊙</button>
            <button class="zoom-btn" onclick="zoomOut()" title="Zoom Out">−</button>
        </div>
    </div>

    <!-- Rechte Sidebar: Location Info & Minimap -->
    <div class="map-sidebar">
        <h3>🗺️ Übersicht</h3>
        
        <div class="minimap">
            <canvas id="minimapCanvas"></canvas>
        </div>

        <div id="locationInfo">
            <div id="noLocationSelected" class="no-location-selected">
                <div class="no-location-icon">📍</div>
                <p>Klicke auf einen Ort auf der Karte für Details</p>
            </div>

            <div id="locationDetails" style="display: none;">
                <h4>
                    <span class="location-icon" id="locationIcon">🏛️</span>
                    <span id="locationName">Ort</span>
                </h4>
                <div class="location-coords" id="locationCoords">X: 0 | Y: 0</div>
                <div class="location-description" id="locationDescription"></div>
                
                <div class="location-stats" id="locationStats"></div>

                <button class="btn" onclick="travelToLocation()">🚶 Hierhin reisen</button>
                <button class="btn btn-secondary" onclick="closeLocationInfo()">✕ Schließen</button>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================================================
// WELTKARTE - Koordinatensystem
// ============================================================================

const canvas = document.getElementById('worldCanvas');
const ctx = canvas.getContext('2d');
const minimap = document.getElementById('minimapCanvas');
const minimapCtx = minimap.getContext('2d');

// Welt-Konfiguration
const WORLD_SIZE = 1000;
const TILE_SIZE = 40;
const GRID_COLOR = 'rgba(255, 255, 255, 0.1)';

// Spieler-Position
let playerPos = {
    x: <?php echo $playerX; ?>,
    y: <?php echo $playerY; ?>
};

// Kamera-Position
let camera = {
    x: playerPos.x * TILE_SIZE - 400,
    y: playerPos.y * TILE_SIZE - 400,
    zoom: 1
};

// Drag & Drop
let isDragging = false;
let lastMousePos = { x: 0, y: 0 };

// Canvas Größe setzen
function resizeCanvas() {
    const container = document.querySelector('.map-main');
    canvas.width = container.clientWidth;
    canvas.height = container.clientHeight;
    minimap.width = 260;
    minimap.height = 180;
    draw();
}

// Locations
const locations = [
    {
        id: 1, name: 'Hauptstadt', icon: '🏰', x: 500, y: 500, type: 'city', level: 10,
        description: 'Die große Hauptstadt des Königreichs.',
        features: ['Shop', 'Bank', 'Taverne'], enemyLevel: null
    },
    {
        id: 2, name: 'Dunkler Wald', icon: '🌲', x: 450, y: 480, type: 'dungeon', level: 5,
        description: 'Ein mysteriöser Wald voller Gefahren.',
        features: ['Monster', 'Schätze'], enemyLevel: '5-8'
    },
    {
        id: 3, name: 'Kristallmine', icon: '⛏️', x: 520, y: 490, type: 'resource', level: 3,
        description: 'Eine ertragreiche Mine.',
        features: ['Bergbau'], enemyLevel: null
    },
    {
        id: 4, name: 'Drachenhort', icon: '🐉', x: 550, y: 520, type: 'boss', level: 20,
        description: 'Die Behausung eines mächtigen Drachen.',
        features: ['Boss-Kampf'], enemyLevel: '20'
    },
    {
        id: 5, name: 'Hafen', icon: '⚓', x: 480, y: 530, type: 'city', level: 7,
        description: 'Ein geschäftiger Hafen am Meer.',
        features: ['Handel', 'Schiffsreisen'], enemyLevel: null
    },
    {
        id: 6, name: 'Goblin-Lager', icon: '👹', x: 470, y: 510, type: 'dungeon', level: 3,
        description: 'Ein kleines Lager der Goblins.',
        features: ['Monster'], enemyLevel: '3-5'
    }
];

// Terrain
function getTerrainType(x, y) {
    const noise = Math.sin(x * 0.1) * Math.cos(y * 0.1);
    if (noise > 0.6) return { color: '#ecf0f1', name: 'Schnee' };
    if (noise > 0.3) return { color: '#95a5a6', name: 'Berge' };
    if (noise > 0) return { color: '#27ae60', name: 'Wald' };
    if (noise > -0.3) return { color: '#2ecc71', name: 'Grasland' };
    if (noise > -0.6) return { color: '#f39c12', name: 'Wüste' };
    return { color: '#3498db', name: 'Wasser' };
}

function worldToScreen(wx, wy) {
    return {
        x: (wx * TILE_SIZE - camera.x) * camera.zoom,
        y: (wy * TILE_SIZE - camera.y) * camera.zoom
    };
}

function screenToWorld(sx, sy) {
    return {
        x: Math.floor((sx / camera.zoom + camera.x) / TILE_SIZE),
        y: Math.floor((sy / camera.zoom + camera.y) / TILE_SIZE)
    };
}

function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    const startX = Math.floor(camera.x / TILE_SIZE) - 1;
    const startY = Math.floor(camera.y / TILE_SIZE) - 1;
    const endX = Math.ceil((camera.x + canvas.width / camera.zoom) / TILE_SIZE) + 1;
    const endY = Math.ceil((camera.y + canvas.height / camera.zoom) / TILE_SIZE) + 1;
    
    // Terrain
    for (let x = Math.max(0, startX); x < Math.min(WORLD_SIZE, endX); x++) {
        for (let y = Math.max(0, startY); y < Math.min(WORLD_SIZE, endY); y++) {
            const terrain = getTerrainType(x, y);
            const pos = worldToScreen(x, y);
            
            ctx.fillStyle = terrain.color;
            ctx.fillRect(pos.x, pos.y, TILE_SIZE * camera.zoom, TILE_SIZE * camera.zoom);
            
            ctx.strokeStyle = GRID_COLOR;
            ctx.lineWidth = 1;
            ctx.strokeRect(pos.x, pos.y, TILE_SIZE * camera.zoom, TILE_SIZE * camera.zoom);
        }
    }
    
    // Locations
    locations.forEach(loc => {
        if (loc.x >= startX && loc.x <= endX && loc.y >= startY && loc.y <= endY) {
            const pos = worldToScreen(loc.x, loc.y);
            
            ctx.font = `${Math.floor(30 * camera.zoom)}px Arial`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(loc.icon, pos.x + (TILE_SIZE * camera.zoom) / 2, pos.y + (TILE_SIZE * camera.zoom) / 2);
            
            if (camera.zoom > 0.7) {
                ctx.font = `${Math.floor(11 * camera.zoom)}px Arial`;
                ctx.fillStyle = '#fff';
                ctx.strokeStyle = '#000';
                ctx.lineWidth = 3;
                ctx.strokeText(loc.name, pos.x + (TILE_SIZE * camera.zoom) / 2, pos.y + TILE_SIZE * camera.zoom + 8);
                ctx.fillText(loc.name, pos.x + (TILE_SIZE * camera.zoom) / 2, pos.y + TILE_SIZE * camera.zoom + 8);
            }
        }
    });
    
    // Spieler
    const playerScreen = worldToScreen(playerPos.x, playerPos.y);
    ctx.fillStyle = '#e94560';
    ctx.beginPath();
    ctx.arc(
        playerScreen.x + (TILE_SIZE * camera.zoom) / 2,
        playerScreen.y + (TILE_SIZE * camera.zoom) / 2,
        10 * camera.zoom,
        0,
        Math.PI * 2
    );
    ctx.fill();
    
    ctx.font = `${Math.floor(20 * camera.zoom)}px Arial`;
    ctx.fillStyle = '#fff';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('👤', playerScreen.x + (TILE_SIZE * camera.zoom) / 2, playerScreen.y + (TILE_SIZE * camera.zoom) / 2);
    
    drawMinimap();
}

function drawMinimap() {
    minimapCtx.fillStyle = '#0a0e27';
    minimapCtx.fillRect(0, 0, 260, 180);
    
    locations.forEach(loc => {
        const x = (loc.x / WORLD_SIZE) * 260;
        const y = (loc.y / WORLD_SIZE) * 180;
        minimapCtx.fillStyle = '#3498db';
        minimapCtx.fillRect(x - 2, y - 2, 4, 4);
    });
    
    const px = (playerPos.x / WORLD_SIZE) * 260;
    const py = (playerPos.y / WORLD_SIZE) * 180;
    minimapCtx.fillStyle = '#e94560';
    minimapCtx.beginPath();
    minimapCtx.arc(px, py, 4, 0, Math.PI * 2);
    minimapCtx.fill();
}

// Events
canvas.addEventListener('mousedown', (e) => {
    isDragging = true;
    lastMousePos = { x: e.clientX, y: e.clientY };
});

canvas.addEventListener('mousemove', (e) => {
    if (isDragging) {
        const dx = e.clientX - lastMousePos.x;
        const dy = e.clientY - lastMousePos.y;
        camera.x -= dx / camera.zoom;
        camera.y -= dy / camera.zoom;
        lastMousePos = { x: e.clientX, y: e.clientY };
        draw();
    }
});

canvas.addEventListener('mouseup', () => {
    isDragging = false;
});

canvas.addEventListener('mouseleave', () => {
    isDragging = false;
});

canvas.addEventListener('click', (e) => {
    if (isDragging) return;
    
    const rect = canvas.getBoundingClientRect();
    const worldPos = screenToWorld(e.clientX - rect.left, e.clientY - rect.top);
    
    const clickedLocation = locations.find(loc => 
        loc.x === worldPos.x && loc.y === worldPos.y
    );
    
    if (clickedLocation) {
        showLocationInfo(clickedLocation);
    }
});

canvas.addEventListener('wheel', (e) => {
    e.preventDefault();
    const zoomFactor = e.deltaY > 0 ? 0.9 : 1.1;
    camera.zoom = Math.max(0.5, Math.min(2, camera.zoom * zoomFactor));
    draw();
});

// Funktionen
function zoomIn() {
    camera.zoom = Math.min(2, camera.zoom * 1.2);
    draw();
}

function zoomOut() {
    camera.zoom = Math.max(0.5, camera.zoom / 1.2);
    draw();
}

function resetZoom() {
    camera.zoom = 1;
    draw();
}

function centerOnPlayer() {
    camera.x = playerPos.x * TILE_SIZE - canvas.width / (2 * camera.zoom);
    camera.y = playerPos.y * TILE_SIZE - canvas.height / (2 * camera.zoom);
    draw();
}

function jumpToCoords() {
    const x = parseInt(document.getElementById('searchX').value);
    const y = parseInt(document.getElementById('searchY').value);
    
    if (isNaN(x) || isNaN(y) || x < 0 || x >= WORLD_SIZE || y < 0 || y >= WORLD_SIZE) {
        alert('Ungültige Koordinaten! (0-' + (WORLD_SIZE - 1) + ')');
        return;
    }
    
    camera.x = x * TILE_SIZE - canvas.width / (2 * camera.zoom);
    camera.y = y * TILE_SIZE - canvas.height / (2 * camera.zoom);
    draw();
}

function showLocationInfo(location) {
    document.getElementById('noLocationSelected').style.display = 'none';
    document.getElementById('locationDetails').style.display = 'block';
    
    document.getElementById('locationIcon').textContent = location.icon;
    document.getElementById('locationName').textContent = location.name;
    document.getElementById('locationCoords').textContent = `X: ${location.x} | Y: ${location.y}`;
    document.getElementById('locationDescription').textContent = location.description;
    
    let statsHtml = '';
    statsHtml += `<div class="location-stat"><span class="location-stat-label">📊 Typ:</span><span class="location-stat-value">${location.type}</span></div>`;
    statsHtml += `<div class="location-stat"><span class="location-stat-label">⭐ Level:</span><span class="location-stat-value">${location.level}</span></div>`;
    if (location.enemyLevel) {
        statsHtml += `<div class="location-stat"><span class="location-stat-label">👹 Gegner:</span><span class="location-stat-value">Level ${location.enemyLevel}</span></div>`;
    }
    statsHtml += `<div class="location-stat"><span class="location-stat-label">📍 Features:</span><span class="location-stat-value">${location.features.join(', ')}</span></div>`;
    
    const distance = Math.sqrt(Math.pow(location.x - playerPos.x, 2) + Math.pow(location.y - playerPos.y, 2));
    statsHtml += `<div class="location-stat"><span class="location-stat-label">📏 Entfernung:</span><span class="location-stat-value">${Math.round(distance)} Felder</span></div>`;
    
    document.getElementById('locationStats').innerHTML = statsHtml;
    window.selectedLocation = location;
}

function closeLocationInfo() {
    document.getElementById('noLocationSelected').style.display = 'block';
    document.getElementById('locationDetails').style.display = 'none';
}

function travelToLocation() {
    if (!window.selectedLocation) return;
    
    const loc = window.selectedLocation;
    const distance = Math.sqrt(Math.pow(loc.x - playerPos.x, 2) + Math.pow(loc.y - playerPos.y, 2));
    
    if (!confirm(`Zu '${loc.name}' reisen?\n\nEntfernung: ${Math.round(distance)} Felder\n\nReise starten?`)) {
        return;
    }
    
    $.ajax({
        url: 'ajax/travel_to_location.php',
        type: 'POST',
        data: {
            location_id: loc.id,
            x: loc.x,
            y: loc.y,
            csrf_token: '<?php echo \App\Helpers\CSRF::generateToken(); ?>'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('✅ Reise abgeschlossen!');
                playerPos.x = loc.x;
                playerPos.y = loc.y;
                document.getElementById('playerX').textContent = loc.x;
                document.getElementById('playerY').textContent = loc.y;
                centerOnPlayer();
                closeLocationInfo();
                updateNearbyLocations();
            } else {
                alert('❌ ' + response.message);
            }
        },
        error: function() {
            alert('❌ Fehler beim Reisen');
        }
    });
}

function updateNearbyLocations() {
    const nearby = locations
        .map(loc => ({
            ...loc,
            distance: Math.sqrt(Math.pow(loc.x - playerPos.x, 2) + Math.pow(loc.y - playerPos.y, 2))
        }))
        .filter(loc => loc.distance > 0 && loc.distance <= 50)
        .sort((a, b) => a.distance - b.distance)
        .slice(0, 5);
    
    const listHtml = nearby.map(loc => `
        <div class="nearby-location-item" onclick="jumpToLocation(${loc.x}, ${loc.y})">
            <div class="nearby-location-name">${loc.icon} ${loc.name}</div>
            <div class="nearby-location-distance">📏 ${Math.round(loc.distance)} Felder</div>
        </div>
    `).join('');
    
    document.getElementById('nearbyLocationsList').innerHTML = listHtml || '<p style="color: #95a5a6; font-size: 0.85em;">Keine Orte in der Nähe</p>';
}

function jumpToLocation(x, y) {
    camera.x = x * TILE_SIZE - canvas.width / (2 * camera.zoom);
    camera.y = y * TILE_SIZE - canvas.height / (2 * camera.zoom);
    draw();
    
    const loc = locations.find(l => l.x === x && l.y === y);
    if (loc) {
        showLocationInfo(loc);
    }
}

// Init
resizeCanvas();
window.addEventListener('resize', resizeCanvas);
centerOnPlayer();
updateNearbyLocations();
</script>