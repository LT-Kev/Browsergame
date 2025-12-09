// ============================================================================
// js/map/map-main.js - Hauptinitialisierung
// ============================================================================

// Global State
const mapState = {
    playerPos: {
        x: PLAYER_START_X || 500,
        y: PLAYER_START_Y || 500
    },
    camera: {
        x: 0,
        y: 0,
        zoom: 1
    }
};

// Initialisierung
$(document).ready(function() {
    console.log('🗺️ Map wird initialisiert...');
    
    // Renderer initialisieren
    mapRenderer.init();
    
    // Navigation initialisieren
    mapNav.init();
    
    // Kamera auf Spieler zentrieren
    centerOnPlayer();
    
    // Nearby Locations laden
    mapNav.updateNearbyLocations();
    
    // Search Handler
    initSearch();
    
    // Animation Loop für Pulse-Effekt
    startAnimationLoop();
    
    console.log('✅ Map initialisiert');
});

// ============================================================================
// Helper Functions
// ============================================================================

function centerOnPlayer() {
    mapState.camera.x = mapState.playerPos.x * MAP_CONFIG.TILE_SIZE - mapRenderer.canvas.width / (2 * mapState.camera.zoom);
    mapState.camera.y = mapState.playerPos.y * MAP_CONFIG.TILE_SIZE - mapRenderer.canvas.height / (2 * mapState.camera.zoom);
    mapRenderer.draw();
}

function jumpToLocation(x, y) {
    mapState.camera.x = x * MAP_CONFIG.TILE_SIZE - mapRenderer.canvas.width / (2 * mapState.camera.zoom);
    mapState.camera.y = y * MAP_CONFIG.TILE_SIZE - mapRenderer.canvas.height / (2 * mapState.camera.zoom);
    mapRenderer.draw();
    
    const loc = LOCATIONS.find(l => l.x === x && l.y === y);
    if (loc) {
        mapPopup.show(loc);
    }
}

function toggleNav() {
    const overlay = document.getElementById('navOverlay');
    overlay.classList.toggle('open');
}

function toggleMinimap() {
    const minimap = document.getElementById('minimapFloat');
    minimap.classList.toggle('hidden');
}

// ============================================================================
// UI Event Handlers
// ============================================================================

document.getElementById('toggleNavBtn').addEventListener('click', toggleNav);

document.getElementById('toggleMinimapBtn').addEventListener('click', toggleMinimap);

// Close popup on background click
document.getElementById('locationPopup').addEventListener('click', function(e) {
    if (e.target === this) {
        mapPopup.close();
    }
});

// ESC Key Handler
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('locationPopup').style.display === 'flex') {
            mapPopup.close();
        } else if (document.getElementById('navOverlay').classList.contains('open')) {
            toggleNav();
        }
    }
    
    // Keyboard Navigation
    if (!document.activeElement || document.activeElement.tagName !== 'INPUT') {
        const moveSpeed = 50;
        switch(e.key) {
            case 'ArrowUp':
            case 'w':
            case 'W':
                mapState.camera.y -= moveSpeed;
                mapRenderer.draw();
                e.preventDefault();
                break;
            case 'ArrowDown':
            case 's':
            case 'S':
                mapState.camera.y += moveSpeed;
                mapRenderer.draw();
                e.preventDefault();
                break;
            case 'ArrowLeft':
            case 'a':
            case 'A':
                mapState.camera.x -= moveSpeed;
                mapRenderer.draw();
                e.preventDefault();
                break;
            case 'ArrowRight':
            case 'd':
            case 'D':
                mapState.camera.x += moveSpeed;
                mapRenderer.draw();
                e.preventDefault();
                break;
            case 'h':
            case 'H':
                centerOnPlayer();
                e.preventDefault();
                break;
            case '+':
            case '=':
                mapZoom.in();
                e.preventDefault();
                break;
            case '-':
            case '_':
                mapZoom.out();
                e.preventDefault();
                break;
        }
    }
});

// ============================================================================
// Search Functionality
// ============================================================================
function initSearch() {
    const searchInput = document.getElementById('locationSearch');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const query = this.value.toLowerCase().trim();
            
            if (query === '') return;
            
            // Check if it's coordinates (e.g., "500,500" or "500 500")
            const coordMatch = query.match(/(\d+)[,\s]+(\d+)/);
            if (coordMatch) {
                const x = parseInt(coordMatch[1]);
                const y = parseInt(coordMatch[2]);
                if (x >= 0 && x < MAP_CONFIG.WORLD_SIZE && y >= 0 && y < MAP_CONFIG.WORLD_SIZE) {
                    jumpToLocation(x, y);
                    return;
                }
            }
            
            // Search locations
            const found = LOCATIONS.find(loc => 
                loc.name.toLowerCase().includes(query) ||
                loc.type.toLowerCase().includes(query)
            );
            
            if (found) {
                jumpToLocation(found.x, found.y);
            }
        }, 500);
    });
    
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            clearTimeout(searchTimeout);
            this.dispatchEvent(new Event('input'));
        }
    });
}

// ============================================================================
// Animation Loop
// ============================================================================
function startAnimationLoop() {
    let lastTime = 0;
    
    function animate(currentTime) {
        const deltaTime = currentTime - lastTime;
        
        // Nur alle 50ms neu zeichnen (20 FPS für Pulse-Effekt)
        if (deltaTime > 50) {
            mapRenderer.draw();
            lastTime = currentTime;
        }
        
        requestAnimationFrame(animate);
    }
    
    requestAnimationFrame(animate);
}

// ============================================================================
// Mobile Menu Toggle Helper
// ============================================================================
if (window.innerWidth <= 768) {
    // Auto-close navigation after selection on mobile
    document.querySelectorAll('.nearby-location-item').forEach(item => {
        item.addEventListener('click', function() {
            setTimeout(() => toggleNav(), 500);
        });
    });
}

// ============================================================================
// Performance Monitor (Dev Mode)
// ============================================================================
if (typeof DEV_MODE !== 'undefined' && DEV_MODE) {
    let frameCount = 0;
    let lastFpsUpdate = Date.now();
    
    setInterval(() => {
        const now = Date.now();
        const fps = Math.round(frameCount / ((now - lastFpsUpdate) / 1000));
        console.log(`🎮 Map FPS: ${fps}`);
        frameCount = 0;
        lastFpsUpdate = now;
    }, 1000);
    
    // Count frames
    const originalDraw = mapRenderer.draw.bind(mapRenderer);
    mapRenderer.draw = function() {
        frameCount++;
        originalDraw();
    };
}