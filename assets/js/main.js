// main.js
import { loadPlayerData } from './player.js';
import { initNavigation } from './navigation.js';
import { initAdminForms } from './admin.js';

$(document).ready(function() {
    // Initial Player Data
    loadPlayerData();

    // Auto-Update alle 30 Sekunden
    setInterval(loadPlayerData, 30000);

    // Navigation
    initNavigation();

    // Admin Formulare
    initAdminForms();
});
