<?php
// pages/character.php
/**
 * Character Stats & Klassen Management
 */
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
$race = $app->getRace()->getRaceById($player['race_id']);
$activeClass = $app->getRPGClass()->getActiveClass($playerId);
$playerClasses = $app->getRPGClass()->getPlayerClasses($playerId);
$availableClasses = $app->getRPGClass()->getAvailableClassesForPlayer($playerId);
?>

<style>
.character-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.character-card {
    background: linear-gradient(135deg, #1e2a3a 0%, #0f1922 100%);
    border: 2px solid rgba(233, 69, 96, 0.3);
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.character-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 2px solid rgba(233, 69, 96, 0.3);
}

.character-icon {
    font-size: 5em;
}

.character-info {
    flex: 1;
}

.character-name {
    font-size: 2em;
    font-weight: bold;
    color: #e94560;
    margin-bottom: 5px;
}

.character-title {
    color: #bdc3c7;
    font-size: 1.1em;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-top: 20px;
}

.stat-item {
    background: rgba(0, 0, 0, 0.3);
    padding: 15px;
    border-radius: 10px;
    border-left: 4px solid #e94560;
}

.stat-name {
    font-size: 0.9em;
    color: #95a5a6;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 2em;
    font-weight: bold;
    color: #2ecc71;
}

.stat-distribute {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(0, 0, 0, 0.3);
    padding: 15px;
    border-radius: 10px;
    margin-top: 10px;
}

.distribute-btn {
    padding: 8px 15px;
    background: linear-gradient(135deg, #e94560, #d63251);
    border: none;
    border-radius: 5px;
    color: #fff;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s;
}

.distribute-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(233, 69, 96, 0.5);
}

.distribute-btn:disabled {
    background: #95a5a6;
    cursor: not-allowed;
    transform: none;
}

.secondary-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-top: 20px;
}

.secondary-stat {
    text-align: center;
    background: rgba(0, 0, 0, 0.3);
    padding: 15px;
    border-radius: 10px;
}

.secondary-stat-label {
    font-size: 0.85em;
    color: #95a5a6;
    margin-bottom: 5px;
}

.secondary-stat-value {
    font-size: 1.5em;
    font-weight: bold;
    color: #3498db;
}

.progress-bar {
    background: #2c3e50;
    height: 20px;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 5px;
}

.progress-fill {
    background: linear-gradient(90deg, #2ecc71, #27ae60);
    height: 100%;
    transition: width 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 0.75em;
    font-weight: bold;
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
    transition: all 0.3s;
    cursor: pointer;
}

.class-item:hover {
    background: rgba(0, 0, 0, 0.5);
    transform: translateX(5px);
}

.class-item.active {
    border-left-color: #2ecc71;
    background: rgba(46, 204, 113, 0.1);
}

.class-item-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 10px;
}

.class-item-icon {
    font-size: 2.5em;
}

.class-item-info {
    flex: 1;
}

.class-item-name {
    font-size: 1.3em;
    font-weight: bold;
    color: #3498db;
}

.class-item.active .class-item-name {
    color: #2ecc71;
}

.class-item-type {
    font-size: 0.85em;
    color: #95a5a6;
}

.class-item-level {
    background: rgba(52, 152, 219, 0.2);
    padding: 5px 15px;
    border-radius: 15px;
    font-weight: bold;
}

.class-available {
    background: rgba(0, 0, 0, 0.3);
    padding: 15px;
    border-radius: 10px;
    border: 2px dashed rgba(233, 69, 96, 0.5);
    margin-bottom: 15px;
    text-align: center;
    transition: all 0.3s;
    cursor: pointer;
}

.class-available:hover {
    border-color: #e94560;
    background: rgba(233, 69, 96, 0.1);
    transform: scale(1.02);
}

.learn-btn {
    padding: 10px 20px;
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    border: none;
    border-radius: 5px;
    color: #fff;
    font-weight: bold;
    cursor: pointer;
    margin-top: 10px;
    transition: all 0.3s;
}

.learn-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(46, 204, 113, 0.5);
}

.switch-btn {
    padding: 8px 15px;
    background: linear-gradient(135deg, #f39c12, #e67e22);
    border: none;
    border-radius: 5px;
    color: #fff;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.switch-btn:hover {
    transform: scale(1.05);
}
</style>

<h2 style="color: #e94560; margin-bottom: 25px;">👤 Charakter</h2>

<div class="character-container">
    <!-- Basic Info Card -->
    <div class="character-card">
        <div class="character-header">
            <div class="character-icon"><?php echo $race['icon'] ?? '👤'; ?></div>
            <div class="character-info">
                <div class="character-name"><?php echo htmlspecialchars($player['username']); ?></div>
                <div class="character-title">
                    Level <?php echo $player['level']; ?> <?php echo $race['name'] ?? 'Unbekannt'; ?> <?php echo $activeClass['name'] ?? 'Keine Klasse'; ?>
                </div>
            </div>
        </div>
        
        <div style="margin-bottom: 15px;">
            <strong style="color: #e94560;">Rasse:</strong> <?php echo $race['name'] ?? 'Unbekannt'; ?><br>
            <strong style="color: #3498db;">Klasse:</strong> <?php echo $activeClass['name'] ?? 'Keine'; ?><br>
            <strong style="color: #f39c12;">Level:</strong> <?php echo $player['level']; ?>
        </div>
        
        <div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span>Erfahrung:</span>
                <span><?php echo number_format($player['exp']); ?> / <?php echo number_format($app->getPlayer()->getExpNeeded($player['level'])); ?></span>
            </div>
            <div class="progress-bar">
                <?php 
                $expPercent = ($player['exp'] / $app->getPlayer()->getExpNeeded($player['level'])) * 100; 
                ?>
                <div class="progress-fill" style="width: <?php echo $expPercent; ?>%;">
                    <?php echo round($expPercent); ?>%
                </div>
            </div>
        </div>
    </div>
    
    <!-- Secondary Stats Card -->
    <div class="character-card">
        <h3 style="color: #3498db; margin-bottom: 20px;">⚔️ Kampfwerte</h3>
        
        <div class="secondary-stats">
            <div class="secondary-stat">
                <div class="secondary-stat-label">❤️ Leben</div>
                <div class="secondary-stat-value" style="color: #e74c3c;">
                    <?php echo $player['hp']; ?> / <?php echo $player['max_hp']; ?>
                </div>
                <div class="progress-bar" style="height: 10px;">
                    <div class="progress-fill" style="width: <?php echo ($player['hp'] / $player['max_hp']) * 100; ?>%; background: linear-gradient(90deg, #e74c3c, #c0392b);"></div>
                </div>
            </div>
            
            <div class="secondary-stat">
                <div class="secondary-stat-label">💙 Mana</div>
                <div class="secondary-stat-value">
                    <?php echo $player['mana']; ?> / <?php echo $player['max_mana']; ?>
                </div>
                <div class="progress-bar" style="height: 10px;">
                    <div class="progress-fill" style="width: <?php echo ($player['mana'] / $player['max_mana']) * 100; ?>%;"></div>
                </div>
            </div>
            
            <div class="secondary-stat">
                <div class="secondary-stat-label">⚡ Ausdauer</div>
                <div class="secondary-stat-value" style="color: #f39c12;">
                    <?php echo $player['stamina']; ?> / <?php echo $player['max_stamina']; ?>
                </div>
                <div class="progress-bar" style="height: 10px;">
                    <div class="progress-fill" style="width: <?php echo ($player['stamina'] / $player['max_stamina']) * 100; ?>%; background: linear-gradient(90deg, #f39c12, #e67e22);"></div>
                </div>
            </div>
        </div>
        
        <div class="secondary-stats" style="margin-top: 20px;">
            <div class="secondary-stat">
                <div class="secondary-stat-label">⚔️ Angriff</div>
                <div class="secondary-stat-value" style="color: #e74c3c;">
                    <?php echo $player['attack']; ?>
                </div>
            </div>
            
            <div class="secondary-stat">
                <div class="secondary-stat-label">🛡️ Verteidigung</div>
                <div class="secondary-stat-value" style="color: #3498db;">
                    <?php echo $player['defense']; ?>
                </div>
            </div>
            
            <div class="secondary-stat">
                <div class="secondary-stat-label">⚡ Energie</div>
                <div class="secondary-stat-value" style="color: #f39c12;">
                    <?php echo $player['energy']; ?> / 100
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Primary Stats -->
<div class="character-card" style="margin-bottom: 25px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="color: #2ecc71;">📊 Primäre Attribute</h3>
        <?php if($player['stat_points'] > 0): ?>
        <div style="background: rgba(46, 204, 113, 0.2); padding: 10px 20px; border-radius: 10px;">
            <strong style="color: #2ecc71; font-size: 1.2em;">
                <?php echo $player['stat_points']; ?> Punkte verfügbar
            </strong>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="stats-grid">
        <?php
        $stats = [
            'strength' => ['name' => 'Stärke', 'icon' => '💪', 'effect' => 'Erhöht Nahkampfschaden'],
            'dexterity' => ['name' => 'Geschick', 'icon' => '🏃', 'effect' => 'Erhöht Fernkampfschaden'],
            'constitution' => ['name' => 'Konstitution', 'icon' => '❤️', 'effect' => 'Erhöht HP und Ausdauer'],
            'intelligence' => ['name' => 'Intelligenz', 'icon' => '🧠', 'effect' => 'Erhöht Mana und Zauberschaden'],
            'wisdom' => ['name' => 'Weisheit', 'icon' => '🦉', 'effect' => 'Erhöht Mana-Regeneration'],
            'charisma' => ['name' => 'Charisma', 'icon' => '✨', 'effect' => 'Erhöht Handelspreise']
        ];
        
        foreach($stats as $key => $stat):
        ?>
        <div class="stat-item">
            <div class="stat-name"><?php echo $stat['icon']; ?> <?php echo $stat['name']; ?></div>
            <div class="stat-value"><?php echo $player[$key]; ?></div>
            <div style="font-size: 0.85em; color: #95a5a6; margin-top: 5px;">
                <?php echo $stat['effect']; ?>
            </div>
            
            <?php if($player['stat_points'] > 0): ?>
            <div class="stat-distribute">
                <span style="color: #95a5a6;">Punkte hinzufügen:</span>
                <button class="distribute-btn" onclick="distributeStat('<?php echo $key; ?>', 1)">
                    +1
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Classes -->
<div class="character-card">
    <h3 style="color: #3498db; margin-bottom: 20px;">🎓 Klassen</h3>
    
    <?php if(!empty($playerClasses)): ?>
    <div class="class-list">
        <?php foreach($playerClasses as $pClass): ?>
        <div class="class-item <?php echo $pClass['is_active'] ? 'active' : ''; ?>">
            <div class="class-item-header">
                <div class="class-item-icon"><?php echo $pClass['icon']; ?></div>
                <div class="class-item-info">
                    <div class="class-item-name">
                        <?php echo $pClass['name']; ?>
                        <?php if($pClass['is_active']): ?>
                        <span style="color: #2ecc71; font-size: 0.8em;">✓ Aktiv</span>
                        <?php endif; ?>
                    </div>
                    <div class="class-item-type"><?php echo ucfirst($pClass['type']); ?></div>
                </div>
                <div class="class-item-level">
                    Level <?php echo $pClass['class_level']; ?>
                </div>
            </div>
            
            <div style="color: #bdc3c7; font-size: 0.9em; margin-bottom: 10px;">
                <?php echo $pClass['description']; ?>
            </div>
            
            <?php if(!$pClass['is_active']): ?>
            <button class="switch-btn" onclick="switchClass(<?php echo $pClass['class_id']; ?>)">
                ⚡ Als aktiv setzen
            </button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p style="text-align: center; color: #95a5a6; padding: 20px;">
        Noch keine Klassen gelernt
    </p>
    <?php endif; ?>
    
    <?php if(!empty($availableClasses)): ?>
    <h4 style="color: #f39c12; margin-top: 30px; margin-bottom: 15px;">📚 Verfügbare Klassen</h4>
    
    <?php foreach($availableClasses as $avClass): ?>
    <div class="class-available" onclick="showLearnClassModal(<?php echo $avClass['id']; ?>)">
        <div style="display: flex; align-items: center; gap: 15px; justify-content: center;">
            <span style="font-size: 2em;"><?php echo $avClass['icon']; ?></span>
            <div>
                <div style="font-size: 1.2em; font-weight: bold; color: #e94560;">
                    <?php echo $avClass['name']; ?>
                </div>
                <div style="font-size: 0.9em; color: #bdc3c7;">
                    <?php echo $avClass['description']; ?>
                </div>
            </div>
        </div>
        <button class="learn-btn" onclick="learnClass(<?php echo $avClass['id']; ?>); event.stopPropagation();">
            📖 Klasse lernen
        </button>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function distributeStat(statName, amount) {
    if(!confirm(`${amount} Punkt auf ${statName} verteilen?`)) {
        return;
    }
    
    $.ajax({
        url: 'ajax/distribute_stat.php',
        type: 'POST',
        data: {
            stat: statName,
            amount: amount,
            csrf_token: '<?php echo \App\Helpers\CSRF::generateToken(); ?>'
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                alert('✅ ' + response.message);
                loadPage('character');
                loadPlayerData();
            } else {
                alert('❌ ' + response.message);
            }
        },
        error: function() {
            alert('❌ Fehler beim Verteilen');
        }
    });
}

function learnClass(classId) {
    if(!confirm('Diese Klasse wirklich lernen?')) {
        return;
    }
    
    $.ajax({
        url: 'ajax/learn_class.php',
        type: 'POST',
        data: {
            class_id: classId,
            csrf_token: '<?php echo \App\Helpers\CSRF::generateToken(); ?>'
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                alert('✅ ' + response.message);
                loadPage('character');
            } else {
                alert('❌ ' + response.message);
            }
        },
        error: function() {
            alert('❌ Fehler beim Lernen');
        }
    });
}

function switchClass(classId) {
    if(!confirm('Zu dieser Klasse wechseln?')) {
        return;
    }
    
    $.ajax({
        url: 'ajax/switch_class.php',
        type: 'POST',
        data: {
            class_id: classId,
            csrf_token: '<?php echo \App\Helpers\CSRF::generateToken(); ?>'
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                alert('✅ ' + response.message);
                loadPage('character');
                loadPlayerData();
            } else {
                alert('❌ ' + response.message);
            }
        },
        error: function() {
            alert('❌ Fehler beim Wechseln');
        }
    });
}

function showLearnClassModal(classId) {
    // Optional: Show detailed modal
    console.log('Show class details for ID:', classId);
}
</script>