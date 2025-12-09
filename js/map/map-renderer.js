// ============================================================================
// js/map/map-renderer.js - Rendering Engine
// ============================================================================
const mapRenderer = {
    canvas: null,
    ctx: null,
    minimapCanvas: null,
    minimapCtx: null,
    
    showGrid: true,
    showLabels: true,
    
    init() {
        this.canvas = document.getElementById('worldCanvas');
        this.ctx = this.canvas.getContext('2d');
        this.minimapCanvas = document.getElementById('minimapCanvas');
        this.minimapCtx = this.minimapCanvas.getContext('2d');
        
        this.resizeCanvas();
        window.addEventListener('resize', () => this.resizeCanvas());
    },
    
    resizeCanvas() {
        const container = this.canvas.parentElement;
        this.canvas.width = container.clientWidth;
        this.canvas.height = container.clientHeight;
        
        this.minimapCanvas.width = 200;
        this.minimapCanvas.height = 140;
        
        this.draw();
    },
    
    getTerrainType(x, y) {
        const noise = Math.sin(x * 0.1) * Math.cos(y * 0.1);
        if (noise > 0.6) return { color: '#ecf0f1', name: 'Schnee' };
        if (noise > 0.3) return { color: '#95a5a6', name: 'Berge' };
        if (noise > 0) return { color: '#27ae60', name: 'Wald' };
        if (noise > -0.3) return { color: '#2ecc71', name: 'Grasland' };
        if (noise > -0.6) return { color: '#f39c12', name: 'Wüste' };
        return { color: '#3498db', name: 'Wasser' };
    },
    
    worldToScreen(wx, wy) {
        return {
            x: (wx * MAP_CONFIG.TILE_SIZE - mapState.camera.x) * mapState.camera.zoom,
            y: (wy * MAP_CONFIG.TILE_SIZE - mapState.camera.y) * mapState.camera.zoom
        };
    },
    
    draw() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        const startX = Math.floor(mapState.camera.x / MAP_CONFIG.TILE_SIZE) - 1;
        const startY = Math.floor(mapState.camera.y / MAP_CONFIG.TILE_SIZE) - 1;
        const endX = Math.ceil((mapState.camera.x + this.canvas.width / mapState.camera.zoom) / MAP_CONFIG.TILE_SIZE) + 1;
        const endY = Math.ceil((mapState.camera.y + this.canvas.height / mapState.camera.zoom) / MAP_CONFIG.TILE_SIZE) + 1;
        
        // Terrain zeichnen
        for (let x = Math.max(0, startX); x < Math.min(MAP_CONFIG.WORLD_SIZE, endX); x++) {
            for (let y = Math.max(0, startY); y < Math.min(MAP_CONFIG.WORLD_SIZE, endY); y++) {
                const terrain = this.getTerrainType(x, y);
                const pos = this.worldToScreen(x, y);
                
                this.ctx.fillStyle = terrain.color;
                this.ctx.fillRect(pos.x, pos.y, MAP_CONFIG.TILE_SIZE * mapState.camera.zoom, MAP_CONFIG.TILE_SIZE * mapState.camera.zoom);
                
                if (this.showGrid) {
                    this.ctx.strokeStyle = MAP_CONFIG.GRID_COLOR;
                    this.ctx.lineWidth = 1;
                    this.ctx.strokeRect(pos.x, pos.y, MAP_CONFIG.TILE_SIZE * mapState.camera.zoom, MAP_CONFIG.TILE_SIZE * mapState.camera.zoom);
                }
            }
        }
        
        // Locations zeichnen
        LOCATIONS.forEach(loc => {
            if (loc.x >= startX && loc.x <= endX && loc.y >= startY && loc.y <= endY) {
                const pos = this.worldToScreen(loc.x, loc.y);
                
                // Icon
                this.ctx.font = `${Math.floor(30 * mapState.camera.zoom)}px Arial`;
                this.ctx.textAlign = 'center';
                this.ctx.textBaseline = 'middle';
                this.ctx.fillText(
                    loc.icon,
                    pos.x + (MAP_CONFIG.TILE_SIZE * mapState.camera.zoom) / 2,
                    pos.y + (MAP_CONFIG.TILE_SIZE * mapState.camera.zoom) / 2
                );
                
                // Name
                if (this.showLabels && mapState.camera.zoom > 0.7) {
                    this.ctx.font = `${Math.floor(11 * mapState.camera.zoom)}px Arial`;
                    this.ctx.fillStyle = '#fff';
                    this.ctx.strokeStyle = '#000';
                    this.ctx.lineWidth = 3;
                    this.ctx.strokeText(
                        loc.name,
                        pos.x + (MAP_CONFIG.TILE_SIZE * mapState.camera.zoom) / 2,
                        pos.y + MAP_CONFIG.TILE_SIZE * mapState.camera.zoom + 10
                    );
                    this.ctx.fillText(
                        loc.name,
                        pos.x + (MAP_CONFIG.TILE_SIZE * mapState.camera.zoom) / 2,
                        pos.y + MAP_CONFIG.TILE_SIZE * mapState.camera.zoom + 10
                    );
                }
            }
        });
        
        // Spieler zeichnen
        const playerScreen = this.worldToScreen(mapState.playerPos.x, mapState.playerPos.y);
        
        // Pulse Effect
        const pulse = Math.sin(Date.now() * 0.005) * 3 + 10;
        this.ctx.fillStyle = 'rgba(233, 69, 96, 0.3)';
        this.ctx.beginPath();
        this.ctx.arc(
            playerScreen.x + (MAP_CONFIG.TILE_SIZE * mapState.camera.zoom) / 2,
            playerScreen.y + (MAP_CONFIG.TILE_SIZE * mapState.camera.zoom) / 2,
            pulse * mapState.camera.zoom,
            0,
            Math.PI * 2
        );
        this.ctx.fill();
        
        // Spieler Icon
        this.ctx.fillStyle = '#e94560';
        this.ctx.beginPath();
        this.ctx.arc(
            playerScreen.x + (MAP_CONFIG.TILE_SIZE * mapState.camera.zoom) / 2,
            playerScreen.y + (MAP_CONFIG.TILE_SIZE * mapState.camera.zoom) / 2,
            8 * mapState.camera.zoom,
            0,
            Math.PI * 2
        );
        this.ctx.fill();
        
        this.ctx.font = `${Math.floor(20 * mapState.camera.zoom)}px Arial`;
        this.ctx.fillStyle = '#fff';
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';
        this.ctx.fillText(
            '👤',
            playerScreen.x + (MAP_CONFIG.TILE_SIZE * mapState.camera.zoom) / 2,
            playerScreen.y + (MAP_CONFIG.TILE_SIZE * mapState.camera.zoom) / 2
        );
        
        this.drawMinimap();
    },
    
    drawMinimap() {
        this.minimapCtx.fillStyle = '#0a0e27';
        this.minimapCtx.fillRect(0, 0, 200, 140);
        
        // Locations
        LOCATIONS.forEach(loc => {
            const x = (loc.x / MAP_CONFIG.WORLD_SIZE) * 200;
            const y = (loc.y / MAP_CONFIG.WORLD_SIZE) * 140;
            this.minimapCtx.fillStyle = '#3498db';
            this.minimapCtx.fillRect(x - 2, y - 2, 4, 4);
        });
        
        // Spieler
        const px = (mapState.playerPos.x / MAP_CONFIG.WORLD_SIZE) * 200;
        const py = (mapState.playerPos.y / MAP_CONFIG.WORLD_SIZE) * 140;
        this.minimapCtx.fillStyle = '#e94560';
        this.minimapCtx.beginPath();
        this.minimapCtx.arc(px, py, 4, 0, Math.PI * 2);
        this.minimapCtx.fill();
        
        // View Rectangle
        const viewX = (mapState.camera.x / MAP_CONFIG.TILE_SIZE / MAP_CONFIG.WORLD_SIZE) * 200;
        const viewY = (mapState.camera.y / MAP_CONFIG.TILE_SIZE / MAP_CONFIG.WORLD_SIZE) * 140;
        const viewW = (mapRenderer.canvas.width / mapState.camera.zoom / MAP_CONFIG.TILE_SIZE / MAP_CONFIG.WORLD_SIZE) * 200;
        const viewH = (mapRenderer.canvas.height / mapState.camera.zoom / MAP_CONFIG.TILE_SIZE / MAP_CONFIG.WORLD_SIZE) * 140;
        
        this.minimapCtx.strokeStyle = '#e94560';
        this.minimapCtx.lineWidth = 2;
        this.minimapCtx.strokeRect(viewX, viewY, viewW, viewH);
    },
    
    toggleGrid() {
        this.showGrid = !this.showGrid;
        this.draw();
    },
    
    toggleLabels() {
        this.showLabels = !this.showLabels;
        this.draw();
    }
};