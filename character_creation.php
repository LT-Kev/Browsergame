<?php
// character_creation.php - VOLLSTÄNDIG & KORRIGIERT
require_once __DIR__ . '/init.php';

$app = new App();
$auth = $app->getAuth();

// Login-Check
if(!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$playerId = $auth->getCurrentPlayerId();
$player = $app->getPlayer()->getPlayerById($playerId);

// Wenn Character bereits erstellt, zurück zu index
if($player['character_created']) {
    header('Location: index.php');
    exit;
}

// SITE_NAME aus Config oder Fallback
$siteName = defined('SITE_NAME') ? SITE_NAME : 'Browser Game';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Character Creation - <?php echo $siteName; ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #ecf0f1;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .creation-container {
            max-width: 1200px;
            width: 100%;
            background: linear-gradient(135deg, #1e2a3a 0%, #0f1922 100%);
            border-radius: 20px;
            border: 2px solid rgba(233, 69, 96, 0.3);
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }
        
        .creation-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .creation-header h1 {
            font-size: 3em;
            color: #e94560;
            margin-bottom: 10px;
            text-shadow: 0 0 20px rgba(233, 69, 96, 0.5);
        }
        
        .creation-header p {
            color: #bdc3c7;
            font-size: 1.2em;
        }
        
        .creation-steps {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .step {
            padding: 15px 30px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        
        .step.active {
            background: rgba(233, 69, 96, 0.2);
            border-color: #e94560;
        }
        
        .step.completed {
            background: rgba(46, 204, 113, 0.2);
            border-color: #2ecc71;
        }
        
        .creation-content {
            min-height: 500px;
        }
        
        .step-content {
            display: none;
        }
        
        .step-content.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Race Selection */
        .race-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .race-card {
            background: linear-gradient(135deg, #0f3460 0%, #16213e 100%);
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        
        .race-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(233, 69, 96, 0.3);
            border-color: rgba(233, 69, 96, 0.5);
        }
        
        .race-card.selected {
            border-color: #e94560;
            background: linear-gradient(135deg, #2d1b3d 0%, #1e1330 100%);
            box-shadow: 0 0 30px rgba(233, 69, 96, 0.5);
        }
        
        .race-card.hybrid {
            background: linear-gradient(135deg, #3d2d1b 0%, #2d1e13 100%);
        }
        
        .race-icon {
            font-size: 4em;
            text-align: center;
            margin-bottom: 15px;
        }
        
        .race-name {
            font-size: 1.5em;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
            color: #e94560;
        }
        
        .race-card.hybrid .race-name {
            color: #f39c12;
        }
        
        .race-description {
            color: #bdc3c7;
            font-size: 0.9em;
            text-align: center;
            margin-bottom: 15px;
            min-height: 60px;
        }
        
        .race-stats {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .stat-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 0.9em;
        }
        
        .stat-label {
            color: #95a5a6;
        }
        
        .stat-value {
            color: #2ecc71;
            font-weight: bold;
        }
        
        .stat-value.high {
            color: #2ecc71;
        }
        
        .stat-value.medium {
            color: #f39c12;
        }
        
        .stat-value.low {
            color: #e74c3c;
        }
        
        /* Class Selection */
        .class-type-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .class-type-tab {
            padding: 12px 25px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1em;
            color: #ecf0f1;
        }
        
        .class-type-tab:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .class-type-tab.active {
            background: rgba(233, 69, 96, 0.2);
            border-color: #e94560;
            color: #e94560;
        }
        
        .class-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .class-card {
            background: linear-gradient(135deg, #0f3460 0%, #16213e 100%);
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(52, 152, 219, 0.3);
        }
        
        .class-card.selected {
            border-color: #3498db;
            background: linear-gradient(135deg, #1b2d3d 0%, #13201e 100%);
            box-shadow: 0 0 30px rgba(52, 152, 219, 0.5);
        }
        
        .class-icon {
            font-size: 3em;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .class-name {
            font-size: 1.3em;
            font-weight: bold;
            text-align: center;
            margin-bottom: 8px;
            color: #3498db;
        }
        
        .class-type-badge {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(52, 152, 219, 0.2);
            border-radius: 12px;
            font-size: 0.8em;
            margin-bottom: 10px;
        }
        
        .class-description {
            color: #bdc3c7;
            font-size: 0.85em;
            text-align: center;
            margin-bottom: 10px;
        }
        
        /* Stats Distribution */
        .stats-container {
            background: linear-gradient(135deg, #0f3460 0%, #16213e 100%);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .stat-points-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .stat-points-available {
            font-size: 2em;
            color: #e94560;
            font-weight: bold;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .stat-item:hover {
            background: rgba(0, 0, 0, 0.4);
        }
        
        .stat-info {
            flex: 1;
        }
        
        .stat-name {
            font-size: 1.2em;
            font-weight: bold;
            color: #ecf0f1;
            margin-bottom: 5px;
        }
        
        .stat-effect {
            font-size: 0.9em;
            color: #95a5a6;
        }
        
        .stat-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, #e94560, #d63251);
            color: #fff;
            font-size: 1.5em;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .stat-button:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(233, 69, 96, 0.5);
        }
        
        .stat-button:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
        }
        
        .stat-current {
            font-size: 2em;
            font-weight: bold;
            color: #2ecc71;
            min-width: 50px;
            text-align: center;
        }
        
        /* Confirmation */
        .confirmation-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: linear-gradient(135deg, #0f3460 0%, #16213e 100%);
            border: 2px solid rgba(233, 69, 96, 0.3);
            border-radius: 15px;
            padding: 25px;
        }
        
        .summary-title {
            font-size: 1.5em;
            color: #e94560;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .summary-content {
            text-align: center;
        }
        
        .summary-icon {
            font-size: 4em;
            margin-bottom: 15px;
        }
        
        .summary-name {
            font-size: 1.3em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        /* Buttons */
        .button-group {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #e94560, #d63251);
            color: #fff;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #d63251, #b2243e);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(233, 69, 96, 0.5);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #2980b9, #21618c);
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            font-size: 1.2em;
            color: #bdc3c7;
        }
    </style>
</head>
<body>
    <div class="creation-container">
        <div class="creation-header">
            <h1>⚔️ Character Creation</h1>
            <p>Erschaffe deinen einzigartigen Charakter</p>
        </div>
        
        <div class="creation-steps">
            <div class="step active" data-step="1">
                <strong>1.</strong> Rasse wählen
            </div>
            <div class="step" data-step="2">
                <strong>2.</strong> Klasse wählen
            </div>
            <div class="step" data-step="3">
                <strong>3.</strong> Stats verteilen
            </div>
            <div class="step" data-step="4">
                <strong>4.</strong> Bestätigen
            </div>
        </div>
        
        <div class="creation-content">
            <!-- Step 1: Race Selection -->
            <div class="step-content active" id="step-1">
                <h2 style="text-align: center; margin-bottom: 30px; color: #e94560;">Wähle deine Rasse</h2>
                
                <div class="loading" id="races-loading">Lade Rassen...</div>
                <div class="race-grid" id="race-grid" style="display: none;"></div>
            </div>
            
            <!-- Step 2: Class Selection -->
            <div class="step-content" id="step-2">
                <h2 style="text-align: center; margin-bottom: 30px; color: #3498db;">Wähle deine Klasse</h2>
                
                <div class="class-type-tabs" id="class-tabs">
                    <div class="class-type-tab active" data-type="all">Alle</div>
                    <div class="class-type-tab" data-type="combat">Kampf</div>
                    <div class="class-type-tab" data-type="magic">Magie</div>
                    <div class="class-type-tab" data-type="craft">Handwerk</div>
                    <div class="class-type-tab" data-type="gather">Sammeln</div>
                </div>
                
                <div class="loading" id="classes-loading">Lade Klassen...</div>
                <div class="class-grid" id="class-grid" style="display: none;"></div>
            </div>
            
            <!-- Step 3: Stats Distribution -->
            <div class="step-content" id="step-3">
                <h2 style="text-align: center; margin-bottom: 30px; color: #2ecc71;">Verteile deine Statuspunkte</h2>
                
                <div class="stats-container">
                    <div class="stat-points-header">
                        <p>Verfügbare Punkte:</p>
                        <div class="stat-points-available" id="points-available">10</div>
                    </div>
                    
                    <div id="stats-list"></div>
                </div>
            </div>
            
            <!-- Step 4: Confirmation -->
            <div class="step-content" id="step-4">
                <h2 style="text-align: center; margin-bottom: 30px; color: #f39c12;">Bestätige deinen Charakter</h2>
                
                <div class="confirmation-summary" id="summary"></div>
            </div>
        </div>
        
        <div class="button-group">
            <button class="btn btn-secondary" id="btn-prev" style="display: none;">← Zurück</button>
            <button class="btn btn-primary" id="btn-next">Weiter →</button>
        </div>
    </div>
    
    <script>
        // Character Creation Data
        let characterData = {
            race: null,
            class: null,
            stats: {
                strength: 0,
                dexterity: 0,
                constitution: 0,
                intelligence: 0,
                wisdom: 0,
                charisma: 0
            },
            availablePoints: 10
        };
        
        let currentStep = 1;
        let races = [];
        let classes = [];
        
        // Lade Rassen beim Start
        loadRaces();
        loadClasses();
        
        function loadRaces() {
            fetch('ajax/get_races.php')
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        races = data.races;
                        document.getElementById('races-loading').style.display = 'none';
                        document.getElementById('race-grid').style.display = 'grid';
                        renderRaces();
                    } else {
                        alert('Fehler beim Laden der Rassen: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Fehler beim Laden der Rassen');
                });
        }
        
        function loadClasses() {
            fetch('ajax/get_classes.php')
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        classes = data.classes;
                        document.getElementById('classes-loading').style.display = 'none';
                        document.getElementById('class-grid').style.display = 'grid';
                    } else {
                        alert('Fehler beim Laden der Klassen: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Fehler beim Laden der Klassen');
                });
        }
        
        // Render Races
        function renderRaces() {
            const grid = document.getElementById('race-grid');
            grid.innerHTML = '';
            
            races.forEach(race => {
                const card = document.createElement('div');
                card.className = 'race-card' + (race.is_hybrid == 1 ? ' hybrid' : '');
                card.onclick = () => selectRace(race, card);
                
                card.innerHTML = `
                    <div class="race-icon">${race.icon}</div>
                    <div class="race-name">${race.name}${race.is_hybrid == 1 ? ' (Hybrid)' : ''}</div>
                    <div class="race-description">${race.description}</div>
                    <div class="race-stats">
                        <div class="stat-row">
                            <span class="stat-label">💪 Stärke:</span>
                            <span class="stat-value ${getStatClass(race.base_strength)}">${race.base_strength}</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">🏃 Geschick:</span>
                            <span class="stat-value ${getStatClass(race.base_dexterity)}">${race.base_dexterity}</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">❤️ Konstitution:</span>
                            <span class="stat-value ${getStatClass(race.base_constitution)}">${race.base_constitution}</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">🧠 Intelligenz:</span>
                            <span class="stat-value ${getStatClass(race.base_intelligence)}">${race.base_intelligence}</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">🦉 Weisheit:</span>
                            <span class="stat-value ${getStatClass(race.base_wisdom)}">${race.base_wisdom}</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">✨ Charisma:</span>
                            <span class="stat-value ${getStatClass(race.base_charisma)}">${race.base_charisma}</span>
                        </div>
                    </div>
                `;
                
                grid.appendChild(card);
            });
        }
        
        function getStatClass(value) {
            if(value >= 13) return 'high';
            if(value >= 10) return 'medium';
            return 'low';
        }
        
        function selectRace(race, cardElement) {
            characterData.race = race;
            document.querySelectorAll('.race-card').forEach(c => c.classList.remove('selected'));
            cardElement.classList.add('selected');
        }
        
        // Render Classes
        function renderClasses(type = 'all') {
            const grid = document.getElementById('class-grid');
            grid.innerHTML = '';
            
            const filtered = type === 'all' ? classes : classes.filter(c => c.type === type);
            
            filtered.forEach(cls => {
                const card = document.createElement('div');
                card.className = 'class-card';
                card.onclick = () => selectClass(cls, card);
                
                card.innerHTML = `
                    <div class="class-icon">${cls.icon}</div>
                    <div class="class-name">${cls.name}</div>
                    <div style="text-align: center;">
                        <span class="class-type-badge">${cls.type}</span>
                    </div>
                    <div class="class-description">${cls.description}</div>
                `;
                
                grid.appendChild(card);
            });
        }
        
        function selectClass(cls, cardElement) {
            characterData.class = cls;
            document.querySelectorAll('.class-card').forEach(c => c.classList.remove('selected'));
            cardElement.classList.add('selected');
        }
        
        // Class Type Tabs
        document.querySelectorAll('.class-type-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.class-type-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                renderClasses(this.dataset.type);
            });
        });
        
        // Stats Distribution
        function renderStats() {
            const container = document.getElementById('stats-list');
            const stats = [
                { key: 'strength', name: 'Stärke', icon: '💪', effect: 'Erhöht Nahkampfschaden und Traglast' },
                { key: 'dexterity', name: 'Geschicklichkeit', icon: '🏃', effect: 'Erhöht Fernkampfschaden und Ausweichen' },
                { key: 'constitution', name: 'Konstitution', icon: '❤️', effect: 'Erhöht HP und Ausdauer' },
                { key: 'intelligence', name: 'Intelligenz', icon: '🧠', effect: 'Erhöht Mana und Zauberschaden' },
                { key: 'wisdom', name: 'Weisheit', icon: '🦉', effect: 'Erhöht Mana-Regeneration und Widerstand' },
                { key: 'charisma', name: 'Charisma', icon: '✨', effect: 'Erhöht Handelspreise und Gruppenbonus' }
            ];
            
            container.innerHTML = '';
            
            stats.forEach(stat => {
                const baseValue = characterData.race ? parseInt(characterData.race[`base_${stat.key}`]) : 10;
                const currentValue = baseValue + characterData.stats[stat.key];
                
                const item = document.createElement('div');
                item.className = 'stat-item';
                item.innerHTML = `
                    <div class="stat-info">
                        <div class="stat-name">${stat.icon} ${stat.name}</div>
                        <div class="stat-effect">${stat.effect}</div>
                        <div style="color: #95a5a6; font-size: 0.85em; margin-top: 5px;">
                            Basis: ${baseValue} + Bonus: ${characterData.stats[stat.key]}
                        </div>
                    </div>
                    <div class="stat-controls">
                        <button class="stat-button" onclick="changeStat('${stat.key}', -1)" ${characterData.stats[stat.key] <= 0 ? 'disabled' : ''}>-</button>
                        <div class="stat-current">${currentValue}</div>
                        <button class="stat-button" onclick="changeStat('${stat.key}', 1)" ${characterData.availablePoints <= 0 ? 'disabled' : ''}>+</button>
                    </div>
                `;
                container.appendChild(item);
            });
            
            document.getElementById('points-available').textContent = characterData.availablePoints;
        }
        
        function changeStat(statKey, change) {
            if(change > 0 && characterData.availablePoints <= 0) return;
            if(change < 0 && characterData.stats[statKey] <= 0) return;
            
            characterData.stats[statKey] += change;
            characterData.availablePoints -= change;
            
            renderStats();
        }
        
        // Confirmation Summary
        function renderSummary() {
            const container = document.getElementById('summary');
            container.innerHTML = '';
            
            // Race Summary
            const raceSummary = document.createElement('div');
            raceSummary.className = 'summary-card';
            raceSummary.innerHTML = `
                <div class="summary-title">Rasse</div>
                <div class="summary-content">
                    <div class="summary-icon">${characterData.race.icon}</div>
                    <div class="summary-name">${characterData.race.name}</div>
                    <div style="color: #95a5a6;">${characterData.race.description}</div>
                </div>
            `;
            container.appendChild(raceSummary);
            
            // Class Summary
            const classSummary = document.createElement('div');
            classSummary.className = 'summary-card';
            classSummary.innerHTML = `
                <div class="summary-title">Klasse</div>
                <div class="summary-content">
                    <div class="summary-icon">${characterData.class.icon}</div>
                    <div class="summary-name">${characterData.class.name}</div>
                    <div style="color: #95a5a6;">${characterData.class.description}</div>
                </div>
            `;
            container.appendChild(classSummary);
            
            // Stats Summary
            const statsSummary = document.createElement('div');
            statsSummary.className = 'summary-card';
            
            let statsHtml = '<div class="summary-title">Finale Stats</div><div class="summary-content">';
            const statNames = {
                strength: '💪 Stärke',
                dexterity: '🏃 Geschick',
                constitution: '❤️ Konstitution',
                intelligence: '🧠 Intelligenz',
                wisdom: '🦉 Weisheit',
                charisma: '✨ Charisma'
            };
            
            for(let key in characterData.stats) {
                const baseValue = parseInt(characterData.race[`base_${key}`]);
                const bonus = characterData.stats[key];
                const total = baseValue + bonus;
                statsHtml += `
                    <div class="stat-row" style="margin: 10px 0;">
                        <span class="stat-label">${statNames[key]}:</span>
                        <span class="stat-value high">${total} (${baseValue} + ${bonus})</span>
                    </div>
                `;
            }
            statsHtml += '</div>';
            statsSummary.innerHTML = statsHtml;
            container.appendChild(statsSummary);
        }
        
        // Navigation
        document.getElementById('btn-next').addEventListener('click', function() {
            if(currentStep === 1) {
                if(!characterData.race) {
                    alert('Bitte wähle eine Rasse!');
                    return;
                }
                renderClasses();
            } else if(currentStep === 2) {
                if(!characterData.class) {
                    alert('Bitte wähle eine Klasse!');
                    return;
                }
                renderStats();
            } else if(currentStep === 3) {
                if(characterData.availablePoints > 0) {
                    if(!confirm('Du hast noch ' + characterData.availablePoints + ' unverteilte Punkte. Trotzdem fortfahren?')) {
                        return;
                    }
                }
                renderSummary();
            } else if(currentStep === 4) {
                // Final submission
                submitCharacter();
                return;
            }
            
            currentStep++;
            updateStepDisplay();
        });
        
        document.getElementById('btn-prev').addEventListener('click', function() {
            currentStep--;
            updateStepDisplay();
        });
        
        function updateStepDisplay() {
            // Hide all steps
            document.querySelectorAll('.step-content').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.step').forEach(s => {
                s.classList.remove('active', 'completed');
            });
            
            // Show current step
            document.getElementById('step-' + currentStep).classList.add('active');
            document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('active');
            
            // Mark completed steps
            for(let i = 1; i < currentStep; i++) {
                document.querySelector(`.step[data-step="${i}"]`).classList.add('completed');
            }
            
            // Button visibility
            document.getElementById('btn-prev').style.display = currentStep === 1 ? 'none' : 'inline-block';
            document.getElementById('btn-next').textContent = currentStep === 4 ? '✅ Charakter erstellen' : 'Weiter →';
        }
        
        function submitCharacter() {
            const submitBtn = document.getElementById('btn-next');
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Erstelle Charakter...';
            
            fetch('ajax/create_character.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(characterData)
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('✅ Charakter erfolgreich erstellt!\n\n' +
                          'Rasse: ' + characterData.race.name + '\n' +
                          'Klasse: ' + characterData.class.name);
                    window.location.href = 'index.php';
                } else {
                    alert('❌ Fehler: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = '✅ Charakter erstellen';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Fehler beim Erstellen des Charakters: ' + error);
                submitBtn.disabled = false;
                submitBtn.textContent = '✅ Charakter erstellen';
            });
        }
    </script>
</body>
</html>