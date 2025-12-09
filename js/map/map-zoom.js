// ============================================================================
// js/map/map-zoom.js - Zoom Controls
// ============================================================================
const mapZoom = {
    in() {
        mapState.camera.zoom = Math.min(MAP_CONFIG.MAX_ZOOM, mapState.camera.zoom + MAP_CONFIG.ZOOM_STEP);
        mapRenderer.draw();
    },
    
    out() {
        mapState.camera.zoom = Math.max(MAP_CONFIG.MIN_ZOOM, mapState.camera.zoom - MAP_CONFIG.ZOOM_STEP);
        mapRenderer.draw();
    },
    
    reset() {
        mapState.camera.zoom = 1;
        mapRenderer.draw();
    }
};