<?php
require_once __DIR__ . '/../init.php';

$app = new App();
$auth = $app->getAuth();

$playerId = $auth->getCurrentPlayerId();
if(!$playerId) {
    echo '<p>Nicht eingeloggt</p>';
    exit;
}

$buildings = $app->getBuilding()->getPlayerBuildings($playerId);
$playerData = $app->getPlayer()->getPlayerById($playerId);
?>

<style>
.buildings-header {
    background: linear-gradient(135deg, #34495e, #2c3e50);
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

.buildings-header h2 {
    margin: 0 0 15px 0;
    color: #fff;
    font-size: 2em;
}

.production-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.prod-item {
    background: rgba(255,255,255,0.1);
    padding: 12px;
    border-radius: 5px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.prod-item .icon {
    font-size: 1.5em;
}

.prod-item .info {
    flex: 1;
}

.prod-item .label {
    font-size: 0.9em;
    color: #bdc3c7;
}

.prod-item .value {
    font-size: 1.2em;
    font-weight: bold;
    color: #2ecc71;
}

.buildings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.building-card {
    background: linear-gradient(135deg, #34495e, #2c3e50);
    border: 2px solid #34495e;
    border-radius: 10px;
    padding: 20px;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.building-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
    border-color: #3498db;
}

.building-card.upgrading {
    border-color: #f39c12;
    background: linear-gradient(135deg, #3d3200, #2c2400);
}

.building-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.building-icon {
    font-size: 2.5em;
}

.building-level {
    background: #3498db;
    color: #fff;
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: bold;
}

.building-card.upgrading .building-level {
    background: #f39c12;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

.building-title {
    font-size: 1.3em;
    color: #fff;
    margin: 10px 0;
}

.building-description {
    color: #bdc3c7;
    font-size: 0.9em;
    margin-bottom: 15px;
    min-height: 40px;
}

.building-stats {
    background: rgba(0,0,0,0.2);
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 15px;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    margin: 5px 0;
    font-size: 0.95em;
}

.stat-label {
    color: #95a5a6;
}

.stat-value {
    color: #2ecc71;
    font-weight: bold;
}

.building-costs {
    background: rgba(231, 76, 60, 0.1);
    border-left: 3px solid #e74c3c;
    padding: 10px;
    border-radius: 3px;
    margin-bottom: 15px;
}

.building-costs.can-afford {
    background: rgba(46, 204, 113, 0.1);
    border-left-color: #2ecc71;
}

.cost-row {
    display: flex;
    justify-content: space-between;
    margin: 3px 0;
    font-size: 0.9em;
}

.cost-row.not-enough {
    color: #e74c3c;
}

.cost-row.enough {
    color: #2ecc71;
}

.upgrade-progress {
    background: #34495e;
    border-radius: 5px;
    padding: 10px;
    margin-bottom: 10px;
}

.progress-bar {
    background: #2c3e50;
    height: 20px;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 8px;
}

.progress-fill {
    background: linear-gradient(90deg, #3498db, #2ecc71);
    height: 100%;
    transition: width 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 0.8em;
    font-weight: bold;
}

.countdown-text {
    text-align: center;
    color: #f39c12;
    font-weight: bold;
}

.btn-upgrade {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #3498db, #2980b9);
    border: none;
    border-radius: 5px;
    color: #fff;
    font-size: 1em;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-upgrade:hover {
    background: linear-gradient(135deg, #2980b9, #21618c);
    transform: scale(1.02);
}

.btn-upgrade:disabled {
    background: #95a5a6;
    cursor: not-allowed;
    transform: none;
}

.btn-cancel {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.btn-cancel:hover {
    background: linear-gradient(135deg, #c0392b, #a93226);
}

.max-level-badge {
    background: linear-gradient(135deg, #f39c12, #e67e22);
    color: #fff;
    padding: 10px;
    border-radius: 5px;
    text-align: center;
    font-weight: bold;
}
</style>

<div class="buildings-header">
    <h2>🏗️ Gebäude</h2>
    
    <div class="production-stats">
        <div class="prod-item">
            <span class="icon">💰</span>
            <div class="info">
                <div class="label">Gold/h</div>
                <div class="value"><?php echo $playerData['gold_production']; ?></div>
            </div>
        </div>
        <div class="prod-item">
            <span class="icon">🍖</span>
            <div class="info">
                <div class="label">Nahrung/h</div>
                <div class="value"><?php echo $playerData['food_production']; ?></div>
            </div>
        </div>
        <div class="prod-item">
            <span class="icon">🪵</span>
            <div class="info">
                <div class="label">Holz/h</div>
                <div class="value"><?php echo $playerData['wood_production']; ?></div>
            </div>
        </div>
        <div class="prod-item">
            <span class="icon">🪨</span>
            <div class="info">
                <div class="label">Stein/h</div>
                <div class="value"><?php echo $playerData['stone_production']; ?></div>
            </div>
        </div>
    </div>
</div>

<div class="buildings-grid">
    <?php foreach($buildings as $building): ?>
        <?php
        if($building['level'] == 0) continue; // Noch nicht gebaute Gebäude überspringen
        
        $costs = $app->getBuilding()->getUpgradeCost($building['building_type_id'], $building['level']);
        $canAfford = $app->getPlayer()->hasEnoughResources($playerId, $costs);
        $isMaxLevel = $building['level'] >= $building['max_level'];
        
        // Icon basierend auf Typ
        $icon = '🏠';
        if($building['type'] == 'resource') $icon = '⛏️';
        if($building['type'] == 'storage') $icon = '📦';
        if($building['type'] == 'military') $icon = '⚔️';
        if($building['type'] == 'special') $icon = '⭐';
        ?>
        
        <div class="building-card <?php echo $building['is_upgrading'] ? 'upgrading' : ''; ?>" data-building-id="<?php echo $building['building_type_id']; ?>">
            <div class="building-header">
                <span class="building-icon"><?php echo $icon; ?></span>
                <span class="building-level">Level <?php echo $building['level']; ?></span>
            </div>
            
            <h3 class="building-title"><?php echo $building['name']; ?></h3>
            <p class="building-description"><?php echo $building['description']; ?></p>
            
            <?php if($building['produces_gold'] > 0 || $building['produces_food'] > 0 || $building['produces_wood'] > 0 || $building['produces_stone'] > 0): ?>
            <div class="building-stats">
                <div style="font-weight: bold; margin-bottom: 5px; color: #3498db;">📊 Produktion (pro Level/h):</div>
                <?php if($building['produces_gold'] > 0): ?>
                <div class="stat-row">
                    <span class="stat-label">💰 Gold:</span>
                    <span class="stat-value">+<?php echo $building['produces_gold'] * $building['level']; ?>/h</span>
                </div>
                <?php endif; ?>
                <?php if($building['produces_food'] > 0): ?>
                <div class="stat-row">
                    <span class="stat-label">🍖 Nahrung:</span>
                    <span class="stat-value">+<?php echo $building['produces_food'] * $building['level']; ?>/h</span>
                </div>
                <?php endif; ?>
                <?php if($building['produces_wood'] > 0): ?>
                <div class="stat-row">
                    <span class="stat-label">🪵 Holz:</span>
                    <span class="stat-value">+<?php echo $building['produces_wood'] * $building['level']; ?>/h</span>
                </div>
                <?php endif; ?>
                <?php if($building['produces_stone'] > 0): ?>
                <div class="stat-row">
                    <span class="stat-label">🪨 Stein:</span>
                    <span class="stat-value">+<?php echo $building['produces_stone'] * $building['level']; ?>/h</span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if($building['increases_gold_capacity'] > 0 || $building['increases_food_capacity'] > 0 || $building['increases_wood_capacity'] > 0 || $building['increases_stone_capacity'] > 0): ?>
            <div class="building-stats">
                <div style="font-weight: bold; margin-bottom: 5px; color: #3498db;">📦 Lagerkapazität (pro Level):</div>
                <?php if($building['increases_gold_capacity'] > 0): ?>
                <div class="stat-row">
                    <span class="stat-label">💰 Gold:</span>
                    <span class="stat-value">+<?php echo $building['increases_gold_capacity'] * $building['level']; ?></span>
                </div>
                <?php endif; ?>
                <?php if($building['increases_food_capacity'] > 0): ?>
                <div class="stat-row">
                    <span class="stat-label">🍖 Nahrung:</span>
                    <span class="stat-value">+<?php echo $building['increases_food_capacity'] * $building['level']; ?></span>
                </div>
                <?php endif; ?>
                <?php if($building['increases_wood_capacity'] > 0): ?>
                <div class="stat-row">
                    <span class="stat-label">🪵 Holz:</span>
                    <span class="stat-value">+<?php echo $building['increases_wood_capacity'] * $building['level']; ?></span>
                </div>
                <?php endif; ?>
                <?php if($building['increases_stone_capacity'] > 0): ?>
                <div class="stat-row">
                    <span class="stat-label">🪨 Stein:</span>
                    <span class="stat-value">+<?php echo $building['increases_stone_capacity'] * $building['level']; ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if($building['is_upgrading']): ?>
                <div class="upgrade-progress">
                    <?php
                    $finishTime = strtotime($building['upgrade_finish_time']);
                    $startTime = time() - $costs['time'];
                    $totalTime = $finishTime - $startTime;
                    $elapsed = time() - $startTime;
                    $progress = min(100, ($elapsed / $totalTime) * 100);
                    ?>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress; ?>%;">
                            <?php echo round($progress); ?>%
                        </div>
                    </div>
                    <div class="countdown-text">
                        ⏱️ Fertig in: <span class="countdown" data-finish="<?php echo $finishTime; ?>"></span>
                    </div>
                </div>
                <button class="btn-upgrade btn-cancel cancel-upgrade-btn" data-building-id="<?php echo $building['building_type_id']; ?>">
                    ❌ Abbrechen (50% Rückerstattung)
                </button>
            <?php elseif($isMaxLevel): ?>
                <div class="max-level-badge">
                    ⭐ Maximales Level erreicht!
                </div>
            <?php else: ?>
                <div class="building-costs <?php echo $canAfford ? 'can-afford' : ''; ?>">
                    <div style="font-weight: bold; margin-bottom: 5px;">💎 Kosten für Level <?php echo $building['level'] + 1; ?>:</div>
                    <div class="cost-row <?php echo $playerData['gold'] >= $costs['gold'] ? 'enough' : 'not-enough'; ?>">
                        <span>💰 Gold:</span>
                        <span><?php echo number_format($costs['gold'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="cost-row <?php echo $playerData['food'] >= $costs['food'] ? 'enough' : 'not-enough'; ?>">
                        <span>🍖 Nahrung:</span>
                        <span><?php echo number_format($costs['food'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="cost-row <?php echo $playerData['wood'] >= $costs['wood'] ? 'enough' : 'not-enough'; ?>">
                        <span>🪵 Holz:</span>
                        <span><?php echo number_format($costs['wood'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="cost-row <?php echo $playerData['stone'] >= $costs['stone'] ? 'enough' : 'not-enough'; ?>">
                        <span>🪨 Stein:</span>
                        <span><?php echo number_format($costs['stone'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="cost-row">
                        <span>⏱️ Bauzeit:</span>
                        <span><?php echo gmdate("H:i:s", $costs['time']); ?></span>
                    </div>
                </div>
                <button class="btn-upgrade upgrade-btn" 
                        data-building-id="<?php echo $building['building_type_id']; ?>"
                        <?php echo !$canAfford ? 'disabled' : ''; ?>>
                    <?php echo $canAfford ? '⬆️ Ausbauen' : '❌ Nicht genug Ressourcen'; ?>
                </button>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
$(document).ready(function() {
    // Countdown-Timer
    function updateCountdowns() {
        $('.countdown').each(function() {
            var finishTime = $(this).data('finish');
            var now = Math.floor(Date.now() / 1000);
            var remaining = finishTime - now;
            
            if(remaining <= 0) {
                $(this).text('Fertig!');
                location.reload();
            } else {
                var hours = Math.floor(remaining / 3600);
                var minutes = Math.floor((remaining % 3600) / 60);
                var seconds = remaining % 60;
                
                $(this).text(
                    (hours < 10 ? '0' : '') + hours + ':' +
                    (minutes < 10 ? '0' : '') + minutes + ':' +
                    (seconds < 10 ? '0' : '') + seconds
                );
            }
        });
    }
    
    setInterval(updateCountdowns, 1000);
    updateCountdowns();
    
    // Upgrade Button
    $('.upgrade-btn').on('click', function() {
        var buildingId = $(this).data('building-id');
        var button = $(this);
        
        button.prop('disabled', true).text('⏳ Wird gestartet...');
        
        $.ajax({
            url: 'ajax/upgrade_building.php',
            type: 'POST',
            data: { building_type_id: buildingId },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert('✅ ' + response.message);
                    loadPage('buildings');
                } else {
                    alert('❌ ' + response.message);
                    button.prop('disabled', false).text('⬆️ Ausbauen');
                }
            },
            error: function() {
                alert('❌ Fehler beim Upgrade');
                button.prop('disabled', false).text('⬆️ Ausbauen');
            }
        });
    });
    
    // Cancel Button
    $('.cancel-upgrade-btn').on('click', function() {
        if(!confirm('Upgrade wirklich abbrechen? Du erhältst 50% der Kosten zurück.')) {
            return;
        }
        
        var buildingId = $(this).data('building-id');
        var button = $(this);
        
        button.prop('disabled', true).text('⏳ Wird abgebrochen...');
        
        $.ajax({
            url: 'ajax/cancel_upgrade.php',
            type: 'POST',
            data: { building_type_id: buildingId },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert('✅ ' + response.message);
                    loadPage('buildings');
                } else {
                    alert('❌ ' + response.message);
                    button.prop('disabled', false).text('❌ Abbrechen');
                }
            },
            error: function() {
                alert('❌ Fehler beim Abbrechen');
                button.prop('disabled', false).text('❌ Abbrechen');
            }
        });
    });
});
</script>