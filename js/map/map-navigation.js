// ============================================================================
// js/map/map-navigation.js - Navigation & Bewegung
// ============================================================================
const mapNav = {
    isDragging: false,
    lastMousePos: { x: 0, y: 0 },
    
    init() {
        const canvas = mapRenderer.canvas;
        
        canvas.addEventListener('mousedown', (e) => this.onMouseDown(e));
        canvas.addEventListener('mousemove', (e) => this.onMouseMove(e));
        canvas.addEventListener('mouseup', () => this.onMouseUp());
        canvas.addEventListener('mouseleave', () => this.onMouseUp());
        canvas.addEventListener('click', (e) => this.onClick(e));
        canvas.addEventListener('wheel', (e) => this.onWheel(e));
        
        // Touch Support
        canvas.addEventListener('touchstart', (e) => this.onTouchStart(e));
        canvas.addEventListener('touchmove', (e) => this.onTouchMove(e));
        canvas.addEventListener('touchend', () => this.onTouchEnd());
    },
    
    onMouseDown(e) {
        this.isDragging = true;
        this.lastMousePos = { x: e.clientX, y: e.clientY };
        mapRenderer.canvas.style.cursor = 'grabbing';
    },
    
    onMouseMove(e) {
        if (this.isDragging) {
            const dx = e.clientX - this.lastMousePos.x;
            const dy = e.clientY - this.lastMousePos.y;
            
            mapState.camera.x -= dx / mapState.camera.zoom;
            mapState.camera.y -= dy / mapState.camera.zoom;
            
            this.lastMousePos = { x: e.clientX, y: e.clientY };
            mapRenderer.draw();
        }
    },
    
    onMouseUp() {
        this.isDragging = false;
        mapRenderer.canvas.style.cursor = 'move';
    },
    
    onClick(e) {
        if (this.isDragging) return;
        
        const rect = mapRenderer.canvas.getBoundingClientRect();
        const worldPos = this.screenToWorld(e.clientX - rect.left, e.clientY - rect.top);
        
        const clickedLocation = LOCATIONS.find(loc => 
            loc.x === worldPos.x && loc.y === worldPos.y
        );
        
        if (clickedLocation) {
            mapPopup.show(clickedLocation);
        }
    },
    
    onWheel(e) {
        e.preventDefault();
        const zoomFactor = e.deltaY > 0 ? 0.9 : 1.1;
        mapState.camera.zoom = Math.max(
            MAP_CONFIG.MIN_ZOOM,
            Math.min(MAP_CONFIG.MAX_ZOOM, mapState.camera.zoom * zoomFactor)
        );
        mapRenderer.draw();
    },
    
    onTouchStart(e) {
        if (e.touches.length === 1) {
            this.isDragging = true;
            this.lastMousePos = {
                x: e.touches[0].clientX,
                y: e.touches[0].clientY
            };
        }
    },
    
    onTouchMove(e) {
        e.preventDefault();
        if (this.isDragging && e.touches.length === 1) {
            const dx = e.touches[0].clientX - this.lastMousePos.x;
            const dy = e.touches[0].clientY - this.lastMousePos.y;
            
            mapState.camera.x -= dx / mapState.camera.zoom;
            mapState.camera.y -= dy / mapState.camera.zoom;
            
            this.lastMousePos = {
                x: e.touches[0].clientX,
                y: e.touches[0].clientY
            };
            mapRenderer.draw();
        }
    },
    
    onTouchEnd() {
        this.isDragging = false;
    },
    
    screenToWorld(sx, sy) {
        return {
            x: Math.floor((sx / mapState.camera.zoom + mapState.camera.x) / MAP_CONFIG.TILE_SIZE),
            y: Math.floor((sy / mapState.camera.zoom + mapState.camera.y) / MAP_CONFIG.TILE_SIZE)
        };
    },
    
    jumpToCoords() {
        const x = parseInt(document.getElementById('jumpX').value);
        const y = parseInt(document.getElementById('jumpY').value);
        
        if (isNaN(x) || isNaN(y) || x < 0 || x >= MAP_CONFIG.WORLD_SIZE || y < 0 || y >= MAP_CONFIG.WORLD_SIZE) {
            alert(`Ungültige Koordinaten! (0-${MAP_CONFIG.WORLD_SIZE - 1})`);
            return;
        }
        
        mapState.camera.x = x * MAP_CONFIG.TILE_SIZE - mapRenderer.canvas.width / (2 * mapState.camera.zoom);
        mapState.camera.y = y * MAP_CONFIG.TILE_SIZE - mapRenderer.canvas.height / (2 * mapState.camera.zoom);
        mapRenderer.draw();
    },
    
    updateNearbyLocations() {
        const nearby = LOCATIONS
            .map(loc => ({
                ...loc,
                distance: Math.sqrt(
                    Math.pow(loc.x - mapState.playerPos.x, 2) +
                    Math.pow(loc.y - mapState.playerPos.y, 2)
                )
            }))
            .filter(loc => loc.distance > 0 && loc.distance <= MAP_CONFIG.NEARBY_RADIUS)
            .sort((a, b) => a.distance - b.distance)
            .slice(0, 5);
        
        const listHtml = nearby.map(loc => `
            <div class="nearby-location-item" onclick="jumpToLocation(${loc.x}, ${loc.y})">
                <div class="nearby-location-name">${loc.icon} ${loc.name}</div>
                <div class="nearby-location-distance">📏 ${Math.round(loc.distance)} Felder</div>
            </div>
        `).join('');
        
        document.getElementById('nearbyLocationsList').innerHTML = listHtml ||
            '<p style="color: #95a5a6; font-size: 0.85em; text-align: center;">Keine Orte in der Nähe</p>';
    }
};