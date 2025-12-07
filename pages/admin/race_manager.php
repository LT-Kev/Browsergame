<?php
// pages/admin/race_manager.php
require_once __DIR__ . '/../../init.php';

use App\Core\App;

$app = App::getInstance();
$playerId = $app->getAuth()->getCurrentPlayerId();

// Admin-Check
if(!$playerId || !$app->getAdmin()->hasPermission($playerId, 'system_settings')) {
    echo '<p style="color: #e74c3c;">Keine Berechtigung</p>';
    exit;
}

$db = $app->getDB();

// Formular verarbeiten
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_race'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $icon = $_POST['icon'] ?: '👤';
    $lore = trim($_POST['lore']);
    
    // Basis-Stats
    $base_strength = intval($_POST['base_strength']);
    $base_dexterity = intval($_POST['base_dexterity']);
    $base_constitution = intval($_POST['base_constitution']);
    $base_intelligence = intval($_POST['base_intelligence']);
    $base_wisdom = intval($_POST['base_wisdom']);
    $base_charisma = intval($_POST['base_charisma']);
    
    // Stats per Level
    $strength_per_level = floatval($_POST['strength_per_level']);
    $dexterity_per_level = floatval($_POST['dexterity_per_level']);
    $constitution_per_level = floatval($_POST['constitution_per_level']);
    $intelligence_per_level = floatval($_POST['intelligence_per_level']);
    $wisdom_per_level = floatval($_POST['wisdom_per_level']);
    $charisma_per_level = floatval($_POST['charisma_per_level']);
    
    // Modifiers
    $hp_modifier = floatval($_POST['hp_modifier']);
    $mana_modifier = floatval($_POST['mana_modifier']);
    $stamina_modifier = floatval($_POST['stamina_modifier']);
    
    // Boni
    $gold_bonus_percent = intval($_POST['gold_bonus_percent']);
    $food_bonus_percent = intval($_POST['food_bonus_percent']);
    $wood_bonus_percent = intval($_POST['wood_bonus_percent']);
    $stone_bonus_percent = intval($_POST['stone_bonus_percent']);
    
    $melee_damage_bonus = floatval($_POST['melee_damage_bonus']);
    $ranged_damage_bonus = floatval($_POST['ranged_damage_bonus']);
    $magic_damage_bonus = floatval($_POST['magic_damage_bonus']);
    $defense_bonus = floatval($_POST['defense_bonus']);
    
    $is_playable = isset($_POST['is_playable']) ? 1 : 0;
    $is_hybrid = isset($_POST['is_hybrid']) ? 1 : 0;
    $parent_race_1 = $_POST['parent_race_1'] ?: NULL;
    $parent_race_2 = $_POST['parent_race_2'] ?: NULL;
    
    $sql = "INSERT INTO races (
        name, description, icon, lore,
        base_strength, base_dexterity, base_constitution, base_intelligence, base_wisdom, base_charisma,
        strength_per_level, dexterity_per_level, constitution_per_level, 
        intelligence_per_level, wisdom_per_level, charisma_per_level,
        hp_modifier, mana_modifier, stamina_modifier,
        gold_bonus_percent, food_bonus_percent, wood_bonus_percent, stone_bonus_percent,
        melee_damage_bonus, ranged_damage_bonus, magic_damage_bonus, defense_bonus,
        is_playable, is_hybrid, parent_race_1, parent_race_2
    ) VALUES (
        :name, :description, :icon, :lore,
        :base_str, :base_dex, :base_con, :base_int, :base_wis, :base_cha,
        :str_per, :dex_per, :con_per, :int_per, :wis_per, :cha_per,
        :hp_mod, :mana_mod, :stamina_mod,
        :gold_bonus, :food_bonus, :wood_bonus, :stone_bonus,
        :melee_bonus, :ranged_bonus, :magic_bonus, :defense_bonus,
        :is_playable, :is_hybrid, :parent_1, :parent_2
    )";
    
    $result = $db->insert($sql, [
        ':name' => $name,
        ':description' => $description,
        ':icon' => $icon,
        ':lore' => $lore,
        ':base_str' => $base_strength,
        ':base_dex' => $base_dexterity,
        ':base_con' => $base_constitution,
        ':base_int' => $base_intelligence,
        ':base_wis' => $base_wisdom,
        ':base_cha' => $base_charisma,
        ':str_per' => $strength_per_level,
        ':dex_per' => $dexterity_per_level,
        ':con_per' => $constitution_per_level,
        ':int_per' => $intelligence_per_level,
        ':wis_per' => $wisdom_per_level,
        ':cha_per' => $charisma_per_level,
        ':hp_mod' => $hp_modifier,
        ':mana_mod' => $mana_modifier,
        ':stamina_mod' => $stamina_modifier,
        ':gold_bonus' => $gold_bonus_percent,
        ':food_bonus' => $food_bonus_percent,
        ':wood_bonus' => $wood_bonus_percent,
        ':stone_bonus' => $stone_bonus_percent,
        ':melee_bonus' => $melee_damage_bonus,
        ':ranged_bonus' => $ranged_damage_bonus,
        ':magic_bonus' => $magic_damage_bonus,
        ':defense_bonus' => $defense_bonus,
        ':is_playable' => $is_playable,
        ':is_hybrid' => $is_hybrid,
        ':parent_1' => $parent_race_1,
        ':parent_2' => $parent_race_2
    ]);
    
    if($result) {
        $message = '<div class="alert success">✅ Rasse "'.$name.'" erfolgreich erstellt!</div>';
    } else {
        $message = '<div class="alert error">❌ Fehler beim Erstellen der Rasse</div>';
    }
}

// Alle Rassen laden
$races = $db->select("SELECT * FROM races ORDER BY is_playable DESC, is_hybrid ASC, name");
$allRaces = $db->select("SELECT id, name FROM races ORDER BY name");
?>

<style>
.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: bold;
}
.alert.success {
    background: rgba(46, 204, 113, 0.2);
    border: 2px solid #2ecc71;
    color: #2ecc71;
}
.alert.error {
    background: rgba(231, 76, 60, 0.2);
    border: 2px solid #e74c3c;
    color: #e74c3c;
}
.race-manager {
    display: grid;
    gap: 30px;
}
.form-section {
    background: linear-gradient(135deg, #1e2a3a 0%, #0f1922 100%);
    border: 2px solid rgba(233, 69, 96, 0.3);
    border-radius: 15px;
    padding: 30px;
}
.form-section h3 {
    color: #e94560;
    margin-bottom: 20px;
    font-size: 1.5em;
}
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}
.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.form-group label {
    color: #bdc3c7;
    font-weight: bold;
    font-size: 0.9em;
}
.form-group input, .form-group select, .form-group textarea {
    padding: 12px;
    background: rgba(0, 0, 0, 0.3);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    color: #fff;
    font-size: 1em;
}
.form-group textarea {
    min-height: 80px;
    resize: vertical;
}
.checkbox-group {
    display: flex;
    align-items: center;
    gap: 10px;
}
.modifier-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}
.modifier-item {
    background: rgba(0, 0, 0, 0.2);
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #3498db;
}
.modifier-item label {
    display: block;
    margin-bottom: 5px;
    font-size: 0.85em;
}
.modifier-item input {
    width: 100%;
}
.btn-create {
    padding: 15px 40px;
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    border: none;
    border-radius: 10px;
    color: #fff;
    font-size: 1.1em;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-create:hover {
    background: linear-gradient(135deg, #27ae60, #229954);
    transform: translateY(-2px);
}
.race-list {
    display: grid;
    gap: 15px;
}
.race-item {
    background: rgba(0, 0, 0, 0.3);
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid #3498db;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.race-item.hybrid {
    border-left-color: #f39c12;
}
.race-info {
    flex: 1;
}
.race-name {
    font-size: 1.3em;
    font-weight: bold;
    color: #3498db;
    margin-bottom: 5px;
}
.race-type {
    display: inline-block;
    padding: 3px 10px;
    background: rgba(52, 152, 219, 0.2);
    border-radius: 10px;
    font-size: 0.8em;
    margin-right: 10px;
}
.preset-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.preset-btn {
    padding: 10px 20px;
    background: linear-gradient(135deg, #9b59b6, #8e44ad);
    border: none;
    border-radius: 8px;
    color: #fff;
    cursor: pointer;
    transition: all 0.3s;
}
.preset-btn:hover {
    transform: scale(1.05);
}
</style>

<h2 style="color: #e94560; margin-bottom: 25px;">🧬 Rassen Manager</h2>

<?php if(isset($message)) echo $message; ?>

<div class="race-manager">
    <!-- Neue Rasse erstellen -->
    <div class="form-section">
        <h3>➕ Neue Rasse erstellen</h3>
        
        <div class="preset-buttons">
            <button class="preset-btn" onclick="loadPreset('human')">👨 Mensch</button>
            <button class="preset-btn" onclick="loadPreset('orc')">🧟 Ork</button>
            <button class="preset-btn" onclick="loadPreset('elf')">🧝 Elf</button>
            <button class="preset-btn" onclick="loadPreset('dwarf')">🧔 Zwerg</button>
        </div>
        
        <form method="POST" id="race-form">
            <!-- Basis-Info -->
            <div class="form-grid">
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Icon (Emoji)</label>
                    <input type="text" name="icon" value="👤" maxlength="10">
                </div>
            </div>
            
            <div class="form-group">
                <label>Beschreibung *</label>
                <textarea name="description" required></textarea>
            </div>
            
            <div class="form-group">
                <label>Lore/Hintergrund</label>
                <textarea name="lore"></textarea>
            </div>
            
            <!-- Basis-Stats -->
            <h4 style="color: #3498db; margin: 30px 0 15px 0;">📊 Basis-Stats (Level 1)</h4>
            <div class="modifier-grid">
                <div class="modifier-item">
                    <label>💪 Stärke</label>
                    <input type="number" name="base_strength" value="10" min="1" max="20">
                </div>
                <div class="modifier-item">
                    <label>🏃 Geschick</label>
                    <input type="number" name="base_dexterity" value="10" min="1" max="20">
                </div>
                <div class="modifier-item">
                    <label>❤️ Konstitution</label>
                    <input type="number" name="base_constitution" value="10" min="1" max="20">
                </div>
                <div class="modifier-item">
                    <label>🧠 Intelligenz</label>
                    <input type="number" name="base_intelligence" value="10" min="1" max="20">
                </div>
                <div class="modifier-item">
                    <label>🦉 Weisheit</label>
                    <input type="number" name="base_wisdom" value="10" min="1" max="20">
                </div>
                <div class="modifier-item">
                    <label>✨ Charisma</label>
                    <input type="number" name="base_charisma" value="10" min="1" max="20">
                </div>
            </div>
            
            <!-- Stats per Level -->
            <h4 style="color: #3498db; margin: 30px 0 15px 0;">📈 Wachstum pro Level</h4>
            <div class="modifier-grid">
                <div class="modifier-item">
                    <label>💪 Stärke</label>
                    <input type="number" name="strength_per_level" step="0.01" value="1.00" min="0" max="3">
                </div>
                <div class="modifier-item">
                    <label>🏃 Geschick</label>
                    <input type="number" name="dexterity_per_level" step="0.01" value="1.00" min="0" max="3">
                </div>
                <div class="modifier-item">
                    <label>❤️ Konstitution</label>
                    <input type="number" name="constitution_per_level" step="0.01" value="1.00" min="0" max="3">
                </div>
                <div class="modifier-item">
                    <label>🧠 Intelligenz</label>
                    <input type="number" name="intelligence_per_level" step="0.01" value="1.00" min="0" max="3">
                </div>
                <div class="modifier-item">
                    <label>🦉 Weisheit</label>
                    <input type="number" name="wisdom_per_level" step="0.01" value="1.00" min="0" max="3">
                </div>
                <div class="modifier-item">
                    <label>✨ Charisma</label>
                    <input type="number" name="charisma_per_level" step="0.01" value="1.00" min="0" max="3">
                </div>
            </div>
            
            <!-- Ressourcen-Modifiers -->
            <h4 style="color: #3498db; margin: 30px 0 15px 0;">💧 Ressourcen-Modifikatoren</h4>
            <div class="modifier-grid">
                <div class="modifier-item">
                    <label>❤️ HP</label>
                    <input type="number" name="hp_modifier" step="0.01" value="1.00" min="0" max="2">
                </div>
                <div class="modifier-item">
                    <label>💙 Mana</label>
                    <input type="number" name="mana_modifier" step="0.01" value="1.00" min="0" max="2">
                </div>
                <div class="modifier-item">
                    <label>⚡ Ausdauer</label>
                    <input type="number" name="stamina_modifier" step="0.01" value="1.00" min="0" max="2">
                </div>
            </div>
            
            <!-- Wirtschafts-Boni -->
            <h4 style="color: #3498db; margin: 30px 0 15px 0;">💰 Wirtschafts-Boni (%)</h4>
            <div class="modifier-grid">
                <div class="modifier-item">
                    <label>💰 Gold</label>
                    <input type="number" name="gold_bonus_percent" value="0" min="0" max="100">
                </div>
                <div class="modifier-item">
                    <label>🍖 Nahrung</label>
                    <input type="number" name="food_bonus_percent" value="0" min="0" max="100">
                </div>
                <div class="modifier-item">
                    <label>🪵 Holz</label>
                    <input type="number" name="wood_bonus_percent" value="0" min="0" max="100">
                </div>
                <div class="modifier-item">
                    <label>🪨 Stein</label>
                    <input type="number" name="stone_bonus_percent" value="0" min="0" max="100">
                </div>
            </div>
            
            <!-- Kampf-Boni -->
            <h4 style="color: #3498db; margin: 30px 0 15px 0;">⚔️ Kampf-Boni</h4>
            <div class="modifier-grid">
                <div class="modifier-item">
                    <label>⚔️ Nahkampf</label>
                    <input type="number" name="melee_damage_bonus" step="0.01" value="0.00" min="0" max="1">
                </div>
                <div class="modifier-item">
                    <label>🏹 Fernkampf</label>
                    <input type="number" name="ranged_damage_bonus" step="0.01" value="0.00" min="0" max="1">
                </div>
                <div class="modifier-item">
                    <label>✨ Magie</label>
                    <input type="number" name="magic_damage_bonus" step="0.01" value="0.00" min="0" max="1">
                </div>
                <div class="modifier-item">
                    <label>🛡️ Verteidigung</label>
                    <input type="number" name="defense_bonus" step="0.01" value="0.00" min="0" max="1">
                </div>
            </div>
            
            <!-- Einstellungen -->
            <h4 style="color: #3498db; margin: 30px 0 15px 0;">⚙️ Einstellungen</h4>
            <div class="form-grid">
                <div class="checkbox-group">
                    <input type="checkbox" name="is_playable" id="is_playable" checked>
                    <label for="is_playable">Spielbar</label>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" name="is_hybrid" id="is_hybrid">
                    <label for="is_hybrid">Hybrid-Rasse</label>
                </div>
                <div class="form-group">
                    <label>Eltern-Rasse 1 (für Hybriden)</label>
                    <select name="parent_race_1">
                        <option value="">Keine</option>
                        <?php foreach($allRaces as $r): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo $r['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Eltern-Rasse 2 (für Hybriden)</label>
                    <select name="parent_race_2">
                        <option value="">Keine</option>
                        <?php foreach($allRaces as $r): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo $r['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <button type="submit" name="create_race" class="btn-create">
                ➕ Rasse erstellen
            </button>
        </form>
    </div>
    
    <!-- Bestehende Rassen -->
    <div class="form-section">
        <h3>📋 Alle Rassen (<?php echo count($races); ?>)</h3>
        
        <div class="race-list">
            <?php foreach($races as $race): ?>
            <div class="race-item <?php echo $race['is_hybrid'] ? 'hybrid' : ''; ?>">
                <div class="race-info">
                    <div class="race-name">
                        <?php echo $race['icon']; ?> <?php echo $race['name']; ?>
                    </div>
                    <div>
                        <?php if($race['is_playable']): ?>
                        <span class="race-type" style="background: rgba(46, 204, 113, 0.2);">Spielbar</span>
                        <?php endif; ?>
                        <?php if($race['is_hybrid']): ?>
                        <span class="race-type" style="background: rgba(243, 156, 18, 0.2);">Hybrid</span>
                        <?php endif; ?>
                    </div>
                    <div style="color: #bdc3c7; font-size: 0.9em; margin-top: 5px;">
                        <?php echo $race['description']; ?>
                    </div>
                </div>
                <div>
                    <button onclick="editRace(<?php echo $race['id']; ?>)" class="btn" style="padding: 8px 15px; background: #3498db;">
                        ✏️ Bearbeiten
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function loadPreset(type) {
    const presets = {
        human: {
            name: 'Neuer Mensch',
            icon: '👨',
            base_strength: 10, base_dexterity: 10, base_constitution: 10,
            base_intelligence: 10, base_wisdom: 10, base_charisma: 12,
            strength_per_level: 1.05, hp_modifier: 1.00, mana_modifier: 1.00
        },
        orc: {
            name: 'Neuer Ork',
            icon: '🧟',
            base_strength: 15, base_dexterity: 8, base_constitution: 14,
            base_intelligence: 6, base_wisdom: 7, base_charisma: 5,
            strength_per_level: 1.20, hp_modifier: 1.25, melee_damage_bonus: 0.15
        },
        elf: {
            name: 'Neuer Elf',
            icon: '🧝',
            base_strength: 7, base_dexterity: 14, base_constitution: 8,
            base_intelligence: 13, base_wisdom: 12, base_charisma: 11,
            dexterity_per_level: 1.20, mana_modifier: 1.30, magic_damage_bonus: 0.20
        },
        dwarf: {
            name: 'Neuer Zwerg',
            icon: '🧔',
            base_strength: 12, base_dexterity: 7, base_constitution: 15,
            base_intelligence: 9, base_wisdom: 11, base_charisma: 8,
            constitution_per_level: 1.25, hp_modifier: 1.30, stone_bonus_percent: 25
        }
    };
    
    const preset = presets[type];
    const form = document.getElementById('race-form');
    
    for(let key in preset) {
        const input = form.querySelector(`[name="${key}"]`);
        if(input) {
            input.value = preset[key];
        }
    }
    
    alert('✅ Preset "' + type + '" geladen!');
}

function editRace(raceId) {
    alert('Edit-Funktion wird noch implementiert. ID: ' + raceId);
}
</script>