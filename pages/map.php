<?php
// ============================================================================
// pages/map.php - Hauptseite (minimalistisch)
// ============================================================================
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

$playerX = $player['world_x'] ?? 500;
$playerY = $player['world_y'] ?? 500;
?>

<style>
    /* Override content-wrapper für Map */
    body {
        overflow: hidden !important;
    }
    
    .content-wrapper {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .main-container {
        display: none !important; /* Verstecke Sidebars */
    }
</style>

<link rel="stylesheet" href="assets/css/map.css">

<div class="map-fullscreen">
    <!-- Top Bar -->
    <div class="map-topbar">
        <button class="topbar-btn" id="toggleNavBtn">
            <span class="iconify" data-icon="heroicons:bars-3" data-width="24"></span>
            Navigation
        </button>
        
        <div class="topbar-title">🗺️ Weltkarte</div>
        
        <div class="topbar-search">
            <input type="text" id="locationSearch" placeholder="Ort oder Koordinaten suchen...">
        </div>

        <button class="topbar-btn" id="toggleFilterBtn">
            <span class="iconify" data-icon="heroicons:funnel" data-width="24"></span>
        </button>
        
        <button class="topbar-btn" id="toggleMinimapBtn">
            <span class="iconify" data-icon="heroicons:map" data-width="24"></span>
        </button>
    </div>

    <!-- Main Canvas -->
    <div class="map-canvas-container">
        <canvas id="worldCanvas"></canvas>
    </div>

    <!-- Position Badge -->
    <div class="position-badge" id="positionBadge" onclick="centerOnPlayer()">
        <div class="position-badge-title">📍 Deine Position</div>
        <div class="position-badge-coords">
            X: <span id="playerX"><?php echo $playerX; ?></span> | 
            Y: <span id="playerY"><?php echo $playerY; ?></span>
        </div>
    </div>

    <!-- Zoom Controls -->
    <div class="zoom-controls">
        <button class="zoom-btn" onclick="mapZoom.in()" title="Zoom In">+</button>
        <button class="zoom-btn" onclick="mapZoom.reset()" title="Reset">⊙</button>
        <button class="zoom-btn" onclick="mapZoom.out()" title="Zoom Out">−</button>
    </div>

    <!-- Navigation Overlay (Slide-in) -->
    <div class="nav-overlay" id="navOverlay">
        <div class="nav-overlay-header">
            <h3>🎯 Navigation</h3>
            <button class="close-btn" onclick="toggleNav()">✕</button>
        </div>

        <div class="nav-section">
            <h4>📍 Schnellsprung</h4>
            <div class="control-group">
                <label>X-Koordinate:</label>
                <input type="number" id="jumpX" placeholder="0-999" min="0" max="999" value="<?php echo $playerX; ?>">
            </div>
            <div class="control-group">
                <label>Y-Koordinate:</label>
                <input type="number" id="jumpY" placeholder="0-999" min="0" max="999" value="<?php echo $playerY; ?>">
            </div>
            <button class="btn" onclick="mapNav.jumpToCoords()">
                <span class="iconify" data-icon="heroicons:map-pin"></span>
                Zu Koordinaten
            </button>
            <button class="btn btn-secondary" onclick="centerOnPlayer()">
                <span class="iconify" data-icon="heroicons:home"></span>
                Meine Position
            </button>
        </div>

        <div class="nav-section">
            <h4>📋 Legende</h4>
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

        <div class="nav-section">
            <h4>🏛️ Orte in der Nähe</h4>
            <div id="nearbyLocationsList"></div>
        </div>

        <div class="nav-section">
            <h4>🔧 Einstellungen</h4>
            <label class="checkbox-label">
                <input type="checkbox" id="showGridCheckbox" checked onchange="mapRenderer.toggleGrid()">
                Raster anzeigen
            </label>
            <label class="checkbox-label">
                <input type="checkbox" id="showLabelsCheckbox" checked onchange="mapRenderer.toggleLabels()">
                Ortsnamen anzeigen
            </label>
        </div>
    </div>

    <!-- Location Popup (Modal) -->
    <div class="location-popup" id="locationPopup" style="display: none;">
        <div class="popup-content">
            <div class="popup-header">
                <h3>
                    <span class="location-icon" id="popupIcon">🏛️</span>
                    <span id="popupName">Ort</span>
                </h3>
                <button class="close-btn" onclick="mapPopup.close()">✕</button>
            </div>

            <div class="popup-body">
                <div class="location-coords" id="popupCoords">X: 0 | Y: 0</div>
                <div class="location-description" id="popupDescription"></div>
                
                <div class="location-stats" id="popupStats"></div>
            </div>

            <div class="popup-footer">
                <button class="btn" onclick="mapTravel.start()">
                    <span class="iconify" data-icon="heroicons:arrow-right"></span>
                    Hierhin reisen
                </button>
                <button class="btn btn-secondary" onclick="mapPopup.close()">Schließen</button>
            </div>
        </div>
    </div>

    <!-- Floating Minimap -->
    <div class="minimap-float" id="minimapFloat">
        <div class="minimap-header">
            <span>Übersicht</span>
            <button class="minimap-close" onclick="toggleMinimap()">✕</button>
        </div>
        <canvas id="minimapCanvas"></canvas>
    </div>
</div>

<script src="js/map/map-config.js"></script>
<script src="js/map/map-renderer.js"></script>
<script src="js/map/map-navigation.js"></script>
<script src="js/map/map-popup.js"></script>
<script src="js/map/map-travel.js"></script>
<script src="js/map/map-zoom.js"></script>
<script src="js/map/map-main.js"></script>

<script>
// Globale Variablen für PHP-Daten
const PLAYER_START_X = <?php echo $playerX; ?>;
const PLAYER_START_Y = <?php echo $playerY; ?>;
const CSRF_TOKEN = '<?php echo \App\Helpers\CSRF::generateToken(); ?>';
</script>