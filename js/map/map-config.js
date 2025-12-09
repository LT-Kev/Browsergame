// ============================================================================
// js/map/map-config.js - Konfiguration & Konstanten
// ============================================================================
const MAP_CONFIG = {
    WORLD_SIZE: 1000,
    TILE_SIZE: 40,
    GRID_COLOR: 'rgba(255, 255, 255, 0.1)',
    MIN_ZOOM: 0.5,
    MAX_ZOOM: 2,
    ZOOM_STEP: 0.2,
    NEARBY_RADIUS: 50
};

const LOCATIONS = [
    {
        id: 1, name: 'Hauptstadt', icon: '🏰', x: 500, y: 500, type: 'city', level: 10,
        description: 'Die große Hauptstadt des Königreichs mit prächtigen Palästen und geschäftigen Märkten.',
        features: ['Shop', 'Bank', 'Taverne', 'Marktplatz'], enemyLevel: null
    },
    {
        id: 2, name: 'Dunkler Wald', icon: '🌲', x: 450, y: 480, type: 'dungeon', level: 5,
        description: 'Ein mysteriöser Wald voller Gefahren. Hier lauern wilde Kreaturen und verborgene Schätze.',
        features: ['Monster', 'Schätze', 'Holzfäller'], enemyLevel: '5-8'
    },
    {
        id: 3, name: 'Kristallmine', icon: '⛏️', x: 520, y: 490, type: 'resource', level: 3,
        description: 'Eine ertragreiche Mine mit glitzernden Kristallen und wertvollen Erzen.',
        features: ['Bergbau', 'Händler'], enemyLevel: null
    },
    {
        id: 4, name: 'Drachenhort', icon: '🐉', x: 550, y: 520, type: 'boss', level: 20,
        description: 'Die Behausung eines uralten, mächtigen Drachen. Nur die Mutigsten wagen sich hierher.',
        features: ['Boss-Kampf', 'Legendäre Beute'], enemyLevel: '20'
    },
    {
        id: 5, name: 'Hafen', icon: '⚓', x: 480, y: 530, type: 'city', level: 7,
        description: 'Ein geschäftiger Hafen am Meer mit exotischen Waren aus fernen Ländern.',
        features: ['Handel', 'Schiffsreisen', 'Fischerei'], enemyLevel: null
    },
    {
        id: 6, name: 'Goblin-Lager', icon: '👹', x: 470, y: 510, type: 'dungeon', level: 3,
        description: 'Ein kleines aber aggressives Lager der Goblins. Gute Beute für Anfänger.',
        features: ['Monster', 'Plünderung'], enemyLevel: '3-5'
    },
    {
        id: 7, name: 'Magierturm', icon: '🔮', x: 530, y: 470, type: 'special', level: 12,
        description: 'Ein mystischer Turm voller magischer Geheimnisse und arkaner Macht.',
        features: ['Magier-Gilde', 'Zaubersprüche', 'Alchemie'], enemyLevel: null
    },
    {
        id: 8, name: 'Verlassene Ruinen', icon: '🏛️', x: 490, y: 450, type: 'dungeon', level: 8,
        description: 'Uralte Ruinen einer vergangenen Zivilisation. Was mag hier wohl verborgen sein?',
        features: ['Geheimnisse', 'Fallen', 'Antike Schätze'], enemyLevel: '7-10'
    },
    {
        id: 9, name: 'Elfendorf', icon: '🧝', x: 440, y: 500, type: 'city', level: 6,
        description: 'Ein friedliches Dorf der Waldelfen, versteckt im tiefen Wald.',
        features: ['Bogenmacher', 'Kräuter', 'Elfenküche'], enemyLevel: null
    },
    {
        id: 10, name: 'Vulkan', icon: '🌋', x: 560, y: 540, type: 'boss', level: 25,
        description: 'Ein aktiver Vulkan mit glühender Lava. Extrem gefährlich!',
        features: ['Boss-Kampf', 'Feuer-Elementare'], enemyLevel: '25+'
    }
];