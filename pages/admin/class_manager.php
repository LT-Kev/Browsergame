<?php
// pages/admin/class_manager.php
require_once __DIR__ . '/../../init.php';

$app = new App();
$auth = $app->getAuth();
$playerId = $auth->getCurrentPlayerId();

// Admin-Check
if(!$playerId || !$app->getAdmin()->hasPermission($playerId, 'system_settings')) {
    echo '<p style="color: #e74c3c;">Keine Berechtigung</p>';
    exit;
}

$db = $app->getDB();

// Formular verarbeiten
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_class'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $type = $_POST['type'];
    $primary_stat_1 = $_POST['primary_stat_1'];
    $primary_stat_2 = $_POST['primary_stat_2'] ?: NULL;
    $icon = $_POST['icon'] ?: '⚔️';
    $lore = trim($_POST['lore']);
    
    // Modifiers
    $strength_modifier = floatval($_POST['strength_modifier']);
    $dexterity_modifier = floatval($_POST['dexterity_modifier']);
    $constitution_modifier = floatval($_POST['constitution_modifier']);
    $intelligence_modifier = floatval($_POST['intelligence_modifier']);
    $wisdom_modifier = floatval($_POST['wisdom_modifier']);
    $charisma_modifier = floatval($_POST['charisma_modifier']);
    
    $hp_modifier = floatval($_POST['hp_modifier']);
    $mana_modifier = floatval($_POST['mana_modifier']);
    $stamina_modifier = floatval($_POST['stamina_modifier']);
    
    $attack_bonus = floatval($_POST['attack_bonus']);
    $defense_bonus = floatval($_POST['defense_bonus']);
    $critical_chance_bonus = intval($_POST['critical_chance_bonus']);
    
    // Crafting & Gathering
    $can_craft = isset($_POST['can_craft']) ? 1 : 0;
    $can_gather = isset($_POST['can_gather']) ? 1 : 0;
    $crafting_speed_bonus = intval($_POST['crafting_speed_bonus']);
    $crafting_quality_bonus = intval($_POST['crafting_quality_bonus']);
    $gathering_speed_bonus = intval($_POST['gathering_speed_bonus']);
    $gathering_amount_bonus = intval($_POST['gathering_amount_bonus']);
    
    // Voraussetzungen
    $required_level = intval($_POST['required_level']);
    $required_class_id = $_POST['required_class_id'] ?: NULL;
    $is_starter_class = isset($_POST['is_starter_class']) ? 1 : 0;
    $is_advanced_class = isset($_POST['is_advanced_class']) ? 1 : 0;
    
    $sql = "INSERT INTO classes (
        name, description, type, primary_stat_1, primary_stat_2,
        strength_modifier, dexterity_modifier, constitution_modifier,
        intelligence_modifier, wisdom_modifier, charisma_modifier,
        hp_modifier, mana_modifier, stamina_modifier,
        attack_bonus, defense_bonus, critical_chance_bonus,
        can_craft, can_gather, crafting_speed_bonus, crafting_quality_bonus,
        gathering_speed_bonus, gathering_amount_bonus,
        required_level, required_class_id, is_starter_class, is_advanced_class,
        icon, lore
    ) VALUES (
        :name, :description, :type, :primary_stat_1, :primary_stat_2,
        :strength_modifier, :dexterity_modifier, :constitution_modifier,
        :intelligence_modifier, :wisdom_modifier, :charisma_modifier,
        :hp_modifier, :mana_modifier, :stamina_modifier,
        :attack_bonus, :defense_bonus, :critical_chance_bonus,
        :can_craft, :can_gather, :crafting_speed_bonus, :crafting_quality_bonus,
        :gathering_speed_bonus, :gathering_amount_bonus,
        :required_level, :required_class_id, :is_starter_class, :is_advanced_class,
        :icon, :lore
    )";
    
    $result = $db->insert($sql, [
        ':name' => $name,
        ':description' => $description,
        ':type' => $type,
        ':primary_stat_1' => $primary_stat_1,
        ':primary_stat_2' => $primary_stat_2,
        ':strength_modifier' => $strength_modifier,
        ':dexterity_modifier' => $dexterity_modifier,
        ':constitution_modifier' => $constitution_modifier,
        ':intelligence_modifier' => $intelligence_modifier,
        ':wisdom_modifier' => $wisdom_modifier,
        ':charisma_modifier' => $charisma_modifier,
        ':hp_modifier' => $hp_modifier,
        ':mana_modifier' => $mana_modifier,
        ':stamina_modifier' => $stamina_modifier,
        ':attack_bonus' => $attack_bonus,
        ':defense_bonus' => $defense_bonus,
        ':critical_chance_bonus' => $critical_chance_bonus,
        ':can_craft' => $can_craft,
        ':can_gather' => $can_gather,
        ':crafting_speed_bonus' => $crafting_speed_bonus,
        ':crafting_quality_bonus' => $crafting_quality_bonus,
        ':gathering_speed_bonus' => $gathering_speed_bonus,
        ':gathering_amount_bonus' => $gathering_amount_bonus,
        ':required_level' => $required_level,
        ':required_class_id' => $required_class_id,
        ':is_starter_class' => $is_starter_class,
        ':is_advanced_class' => $is_advanced_class,
        ':icon' => $icon,
        ':lore' => $lore
    ]);
    
    if($result) {
        $message = '<div class="alert success">✅ Klasse "'.$name.'" erfolgreich erstellt!</div>';
    } else {
        $message = '<div class="alert error">❌ Fehler beim Erstellen der Klasse</div>';
    }
}

// Alle Klassen laden
$classes = $db->select("SELECT * FROM classes ORDER BY is_starter_class DESC, type, name");
$allClasses = $db->select("SELECT id, name FROM classes ORDER BY name");
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

.class-manager {
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

.form-group input,
.form-group select,
.form-group textarea {
    padding: 12px;
    background: rgba(0, 0, 0, 0.3);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    color: #fff;
    font-size: 1em;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #e94560;
}

.form-group textarea {
    min-height: 80px;
    resize: vertical;
}

.form-group input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #e94560;
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
    box-shadow: 0 5px 20px rgba(46, 204, 113, 0.5);
}

.class-list {
    display: grid;
    gap: 15px;
}

.class-item {
    background: rgba(0, 0, 0, 0.3);
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid #3498db;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.class-item.starter {
    border-left-color: #2ecc71;
}

.class-item.advanced {
    border-left-color: #f39c12;
}

.class-info {
    flex: 1;
}

.class-name {
    font-size: 1.3em;
    font-weight: bold;
    color: #3498db;
    margin-bottom: 5px;
}

.class-type {
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

<h2 style="color: #e94560; margin-bottom: 25px;">⚔️ Klassen Manager</h2>

<?php if(isset($message)) echo $message; ?>

<div class="class-manager">
    <!-- Neue Klasse erstellen -->
    <div class="form-section">
        <h3>➕ Neue Klasse erstellen</h3>
        
        <div class="preset-buttons">
            <button class="preset-btn" onclick="loadPreset('warrior')">⚔️ Krieger Preset</button>
            <button class="preset-btn" onclick="loadPreset('mage')">🧙 Magier Preset</button>
            <button class="preset-btn" onclick="loadPreset('rogue')">🗡️ Schurken Preset</button>
            <button class="preset-btn" onclick="loadPreset('crafter')">⚒️ Handwerker Preset</button>
            <button class="preset-btn" onclick="loadPreset('gatherer')">⛏️ Sammler Preset</button>
        </div>
        
        <form method="POST" id="class-form">
            <!-- Basis-Informationen -->
            <div class="form-grid">
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Icon (Emoji)</label>
                    <input type="text" name="icon" value="⚔️" maxlength="10">
                </div>
                
                <div class="form-group">
                    <label>Typ *</label>
                    <select name="type" required>
                        <option value="combat">Kampf</option>
                        <option value="magic">Magie</option>
                        <option value="craft">Handwerk</option>
                        <option value="gather">Sammeln</option>
                        <option value="support">Unterstützung</option>
                        <option value="hybrid">Hybrid</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Primär-Stat 1 *</label>
                    <select name="primary_stat_1" required>
                        <option value="strength">Stärke</option>
                        <option value="dexterity">Geschicklichkeit</option>
                        <option value="constitution">Konstitution</option>
                        <option value="intelligence">Intelligenz</option>
                        <option value="wisdom">Weisheit</option>
                        <option value="charisma">Charisma</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Primär-Stat 2 (optional)</label>
                    <select name="primary_stat_2">
                        <option value="">Keine</option>
                        <option value="strength">Stärke</option>
                        <option value="dexterity">Geschicklichkeit</option>
                        <option value="constitution">Konstitution</option>
                        <option value="intelligence">Intelligenz</option>
                        <option value="wisdom">Weisheit</option>
                        <option value="charisma">Charisma</option>
                    </select>
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
            
            <!-- Stat Modifiers -->
            <h4 style="color: #3498db; margin: 30px 0 15px 0;">📊 Stat-Modifikatoren (1.00 = normal)</h4>
            <div class="modifier-grid">
                <div class="modifier-item">
                    <label>💪 Stärke</label>
                    <input type="number" name="strength_modifier" step="0.01" value="1.00" min="0" max="2">
                </div>
                <div class="modifier-item">
                    <label>🏃 Geschick</label>
                    <input type="number" name="dexterity_modifier" step="0.01" value="1.00" min="0" max="2">
                </div>
                <div class="modifier-item">
                    <label>❤️ Konstitution</label>
                    <input type="number" name="constitution_modifier" step="0.01" value="1.00" min="0" max="2">
                </div>
                <div class="modifier-item">
                    <label>🧠 Intelligenz</label>
                    <input type="number" name="intelligence_modifier" step="0.01" value="1.00" min="0" max="2">
                </div>
                <div class="modifier-item">
                    <label>🦉 Weisheit</label>
                    <input type="number" name="wisdom_modifier" step="0.01" value="1.00" min="0" max="2">
                </div>
                <div class="modifier-item">
                    <label>✨ Charisma</label>
                    <input type="number" name="charisma_modifier" step="0.01" value="1.00" min="0" max="2">
                </div>
            </div>
            
            <!-- Resource Modifiers -->
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
            
            <!-- Combat Bonuses -->
            <h4 style="color: #3498db; margin: 30px 0 15px 0;">⚔️ Kampf-Boni</h4>
            <div class="modifier-grid">
                <div class="modifier-item">
                    <label>⚔️ Angriff (0.00 = +0%)</label>
                    <input type="number" name="attack_bonus" step="0.01" value="0.00" min="0" max="1">
                </div>
                <div class="modifier-item">
                    <label>🛡️ Verteidigung (0.00 = +0%)</label>
                    <input type="number" name="defense_bonus" step="0.01" value="0.00" min="0" max="1">
                </div>
                <div class="modifier-item">
                    <label>💥 Kritische Chance (%)</label>
                    <input type="number" name="critical_chance_bonus" value="0" min="0" max="100">
                </div>
            </div>
            
            <!-- Crafting & Gathering -->
            <h4 style="color: #3498db; margin: 30px 0 15px 0;">🛠️ Handwerk & Sammeln</h4>
            <div class="form-grid">
                <div class="checkbox-group">
                    <input type="checkbox" name="can_craft" id="can_craft">
                    <label for="can_craft">Kann Handwerken</label>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" name="can_gather" id="can_gather">
                    <label for="can_gather">Kann Sammeln</label>
                </div>
            </div>
            
            <div class="modifier-grid">
                <div class="modifier-item">
                    <label>🔨 Handwerk Speed (%)</label>
                    <input type="number" name="crafting_speed_bonus" value="0" min="0" max="100">
                </div>
                <div class="modifier-item">
                    <label>✨ Handwerk Qualität (%)</label>
                    <input type="number" name="crafting_quality_bonus" value="0" min="0" max="100">
                </div>
                <div class="modifier-item">
                    <label>⛏️ Sammel Speed (%)</label>
                    <input type="number" name="gathering_speed_bonus" value="0" min="0" max="100">
                </div>
                <div class="modifier-item">
                    <label>📦 Sammel Menge (%)</label>
                    <input type="number" name="gathering_amount_bonus" value="0" min="0" max="100">
                </div>
            </div>
            
            <!-- Voraussetzungen -->
            <h4 style="color: #3498db; margin: 30px 0 15px 0;">🔒 Voraussetzungen</h4>
            <div class="form-grid">
                <div class="form-group">
                    <label>Benötigtes Level</label>
                    <input type="number" name="required_level" value="1" min="1" max="100">
                </div>
                
                <div class="form-group">
                    <label>Benötigte Klasse (optional)</label>
                    <select name="required_class_id">
                        <option value="">Keine</option>
                        <?php foreach($allClasses as $cls): ?>
                        <option value="<?php echo $cls['id']; ?>"><?php echo $cls['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="is_starter_class" id="is_starter" checked>
                    <label for="is_starter">Starter-Klasse</label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="is_advanced_class" id="is_advanced">
                    <label for="is_advanced">Erweiterte Klasse</label>
                </div>
            </div>
            
            <button type="submit" name="create_class" class="btn-create">
                ➕ Klasse erstellen
            </button>
        </form>
    </div>
    
    <!-- Bestehende Klassen -->
    <div class="form-section">
        <h3>📋 Alle Klassen (<?php echo count($classes); ?>)</h3>
        
        <div class="class-list">
            <?php foreach($classes as $class): ?>
            <div class="class-item <?php echo $class['is_starter_class'] ? 'starter' : ($class['is_advanced_class'] ? 'advanced' : ''); ?>">
                <div class="class-info">
                    <div class="class-name">
                        <?php echo $class['icon']; ?> <?php echo $class['name']; ?>
                    </div>
                    <div>
                        <span class="class-type"><?php echo ucfirst($class['type']); ?></span>
                        <?php if($class['is_starter_class']): ?>
                        <span class="class-type" style="background: rgba(46, 204, 113, 0.2);">Starter</span>
                        <?php endif; ?>
                        <?php if($class['is_advanced_class']): ?>
                        <span class="class-type" style="background: rgba(243, 156, 18, 0.2);">Advanced</span>
                        <?php endif; ?>
                        <span style="color: #95a5a6; font-size: 0.9em;">Level <?php echo $class['required_level']; ?>+</span>
                    </div>
                    <div style="color: #bdc3c7; font-size: 0.9em; margin-top: 5px;">
                        <?php echo $class['description']; ?>
                    </div>
                </div>
                <div>
                    <button onclick="editClass(<?php echo $class['id']; ?>)" class="btn" style="padding: 8px 15px; background: #3498db;">
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
        warrior: {
            name: 'Neuer Krieger',
            type: 'combat',
            primary_stat_1: 'strength',
            primary_stat_2: 'constitution',
            strength_modifier: 1.30,
            constitution_modifier: 1.20,
            hp_modifier: 1.30,
            attack_bonus: 0.20,
            defense_bonus: 0.15
        },
        mage: {
            name: 'Neuer Magier',
            type: 'magic',
            primary_stat_1: 'intelligence',
            primary_stat_2: 'wisdom',
            intelligence_modifier: 1.40,
            wisdom_modifier: 1.20,
            mana_modifier: 1.50,
            attack_bonus: 0.10
        },
        rogue: {
            name: 'Neuer Schurke',
            type: 'combat',
            primary_stat_1: 'dexterity',
            primary_stat_2: 'intelligence',
            dexterity_modifier: 1.35,
            intelligence_modifier: 1.10,
            stamina_modifier: 1.30,
            attack_bonus: 0.15,
            critical_chance_bonus: 20
        },
        crafter: {
            name: 'Neuer Handwerker',
            type: 'craft',
            primary_stat_1: 'intelligence',
            primary_stat_2: 'dexterity',
            intelligence_modifier: 1.20,
            dexterity_modifier: 1.15,
            can_craft: true,
            crafting_speed_bonus: 30,
            crafting_quality_bonus: 25
        },
        gatherer: {
            name: 'Neuer Sammler',
            type: 'gather',
            primary_stat_1: 'constitution',
            primary_stat_2: 'dexterity',
            constitution_modifier: 1.15,
            dexterity_modifier: 1.10,
            stamina_modifier: 1.20,
            can_gather: true,
            gathering_speed_bonus: 35,
            gathering_amount_bonus: 30
        }
    };
    
    const preset = presets[type];
    const form = document.getElementById('class-form');
    
    for(let key in preset) {
        const input = form.querySelector(`[name="${key}"]`);
        if(input) {
            if(input.type === 'checkbox') {
                input.checked = preset[key];
            } else {
                input.value = preset[key];
            }
        }
    }
    
    alert('✅ Preset "' + type + '" geladen! Bitte passe die Werte an.');
}

function editClass(classId) {
    // TODO: Edit-Funktion
    alert('Edit-Funktion wird noch implementiert. ID: ' + classId);
}
</script>