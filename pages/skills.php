<?php
// pages/skills.php
/**
 * Skills & Fähigkeiten Management
 */
require_once __DIR__ . '/../init.php';

use App\Core\App;

$app = App::getInstance();
$playerId = $app->getAuth()->getCurrentPlayerId();
if(!$playerId) {
    echo '<p>Nicht eingeloggt</p>';
    exit;
}

$player = $app->getPlayer()->getPlayerById($playerId);
$db = $app->getDB();

// Aktive Klasse
$activeClass = $db->selectOne("
    SELECT pc.*, c.* 
    FROM player_classes pc
    JOIN classes c ON pc.class_id = c.id
    WHERE pc.player_id = :player_id AND pc.is_active = 1
    LIMIT 1
", [':player_id' => $playerId]);

// Skills der aktiven Klasse
$classSkills = $db->select("
    SELECT s.*, 
           ps.skill_level,
           ps.id as player_skill_id
    FROM skills s
    LEFT JOIN player_skills ps ON s.id = ps.skill_id AND ps.player_id = :player_id
    WHERE s.class_id = :class_id
    ORDER BY s.required_level ASC, s.type, s.name
", [
    ':player_id' => $playerId,
    ':class_id' => $activeClass['class_id']
]);

// Verfügbare Skills (die gelernt werden können)
$availableSkills = array_filter($classSkills, function($skill) use ($player) {
    return $skill['player_skill_id'] === null && $player['level'] >= $skill['required_level'];
});

// Gelernte Skills
$learnedSkills = array_filter($classSkills, function($skill) {
    return $skill['player_skill_id'] !== null;
});
?>

<style>
.skills-header {
    background: linear-gradient(135deg, #9b59b6, #8e44ad);
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 8px 25px rgba(155, 89, 182, 0.3);
}

.skills-header h2 {
    color: #fff;
    font-size: 2.5em;
    margin-bottom: 10px;
}

.skills-header p {
    color: #ecf0f1;
    font-size: 1.1em;
}

.skills-tabs {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.skill-tab {
    padding: 12px 25px;
    background: linear-gradient(135deg, #0f3460, #16213e);
    border: 2px solid transparent;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
    font-weight: bold;
}

.skill-tab:hover {
    background: linear-gradient(135deg, #16213e, #0f3460);
    transform: translateY(-2px);
}

.skill-tab.active {
    border-color: #9b59b6;
    background: linear-gradient(135deg, #9b59b6, #8e44ad);
    box-shadow: 0 5px 20px rgba(155, 89, 182, 0.4);
}

.skills-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.skill-card {
    background: linear-gradient(135deg, #1e2a3a 0%, #0f1922 100%);
    border: 2px solid rgba(155, 89, 182, 0.3);
    border-radius: 15px;
    padding: 25px;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.skill-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #9b59b6, #3498db, #2ecc71);
}

.skill-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(155, 89, 182, 0.4);
    border-color: #9b59b6;
}

.skill-card.locked {
    opacity: 0.6;
    border-color: rgba(149, 165, 166, 0.3);
}

.skill-card.locked::before {
    background: #95a5a6;
}

.skill-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.skill-icon {
    font-size: 3em;
}

.skill-info {
    flex: 1;
}

.skill-name {
    font-size: 1.4em;
    font-weight: bold;
    color: #9b59b6;
    margin-bottom: 5px;
}

.skill-type {
    display: inline-block;
    padding: 4px 12px;
    background: rgba(155, 89, 182, 0.2);
    border-radius: 12px;
    font-size: 0.8em;
    color: #9b59b6;
}

.skill-description {
    color: #bdc3c7;
    margin-bottom: 15px;
    line-height: 1.6;
}

.skill-stats {
    background: rgba(0, 0, 0, 0.3);
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.skill-stat-row {
    display: flex;
    justify-content: space-between;
    margin: 8px 0;
    font-size: 0.9em;
}

.skill-stat-label {
    color: #95a5a6;
}

.skill-stat-value {
    font-weight: bold;
}

.skill-stat-value.mana {
    color: #3498db;
}

.skill-stat-value.stamina {
    color: #f39c12;
}

.skill-stat-value.cooldown {
    color: #e74c3c;
}

.skill-stat-value.damage {
    color: #e74c3c;
}

.skill-stat-value.heal {
    color: #2ecc71;
}

.skill-scaling {
    background: rgba(52, 152, 219, 0.1);
    border-left: 3px solid #3498db;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
    font-size: 0.9em;
}

.skill-level {
    background: rgba(155, 89, 182, 0.2);
    padding: 8px 15px;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 15px;
    font-weight: bold;
}

.skill-level-bar {
    background: #2c3e50;
    height: 10px;
    border-radius: 5px;
    overflow: hidden;
    margin-top: 5px;
}

.skill-level-fill {
    background: linear-gradient(90deg, #9b59b6, #3498db);
    height: 100%;
    transition: width 0.3s;
}

.skill-requirements {
    background: rgba(231, 76, 60, 0.1);
    border-left: 3px solid #e74c3c;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
    font-size: 0.9em;
    color: #e74c3c;
}

.btn-learn-skill {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    border: none;
    border-radius: 8px;
    color: #fff;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-learn-skill:hover {
    background: linear-gradient(135deg, #27ae60, #229954);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(46, 204, 113, 0.5);
}

.btn-use-skill {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #9b59b6, #8e44ad);
    border: none;
    border-radius: 8px;
    color: #fff;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-use-skill:hover {
    background: linear-gradient(135deg, #8e44ad, #7d3c98);
    transform: translateY(-2px);
}

.btn-use-skill:disabled {
    background: #95a5a6;
    cursor: not-allowed;
    transform: none;
}

.btn-upgrade-skill {
    width: 100%;
    padding: 10px;
    background: linear-gradient(135deg, #3498db, #2980b9);
    border: none;
    border-radius: 8px;
    color: #fff;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 10px;
}

.btn-upgrade-skill:hover {
    background: linear-gradient(135deg, #2980b9, #21618c);
    transform: scale(1.02);
}

.no-skills {
    text-align: center;
    padding: 60px 20px;
    color: #95a5a6;
}

.no-skills-icon {
    font-size: 5em;
    margin-bottom: 20px;
}

.cooldown-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 15px;
    z-index: 10;
}

.cooldown-timer {
    font-size: 2em;
    font-weight: bold;
    color: #e74c3c;
}
</style>

<div class="skills-header">
    <h2>✨ Fähigkeiten</h2>
    <p><?php echo $activeClass['icon']; ?> <?php echo $activeClass['name']; ?> - Level <?php echo $activeClass['class_level']; ?></p>
</div>

<div class="skills-tabs">
    <div class="skill-tab active" data-type="all">Alle Fähigkeiten</div>
    <div class="skill-tab" data-type="active">Aktive Skills</div>
    <div class="skill-tab" data-type="passive">Passive Skills</div>
    <div class="skill-tab" data-type="crafting">Handwerk</div>
    <div class="skill-tab" data-type="gathering">Sammeln</div>
</div>

<!-- Gelernte Skills -->
<?php if(!empty($learnedSkills)): ?>
<h3 style="color: #2ecc71; margin-bottom: 20px;">📚 Gelernte Fähigkeiten</h3>
<div class="skills-grid" id="learned-skills">
    <?php foreach($learnedSkills as $skill): ?>
    <div class="skill-card" data-skill-type="<?php echo $skill['type']; ?>">
        <div class="skill-header">
            <div class="skill-icon"><?php echo $skill['icon']; ?></div>
            <div class="skill-info">
                <div class="skill-name"><?php echo $skill['name']; ?></div>
                <span class="skill-type"><?php echo ucfirst($skill['type']); ?></span>
            </div>
        </div>
        
        <div class="skill-description">
            <?php echo $skill['description']; ?>
        </div>
        
        <div class="skill-level">
            Skill-Level: <?php echo $skill['skill_level']; ?> / 10
            <div class="skill-level-bar">
                <div class="skill-level-fill" style="width: <?php echo ($skill['skill_level'] / 10) * 100; ?>%;"></div>
            </div>
        </div>
        
        <div class="skill-stats">
            <?php if($skill['mana_cost'] > 0): ?>
            <div class="skill-stat-row">
                <span class="skill-stat-label">💙 Mana-Kosten:</span>
                <span class="skill-stat-value mana"><?php echo $skill['mana_cost']; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if($skill['stamina_cost'] > 0): ?>
            <div class="skill-stat-row">
                <span class="skill-stat-label">⚡ Ausdauer-Kosten:</span>
                <span class="skill-stat-value stamina"><?php echo $skill['stamina_cost']; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if($skill['cooldown'] > 0): ?>
            <div class="skill-stat-row">
                <span class="skill-stat-label">⏱️ Abklingzeit:</span>
                <span class="skill-stat-value cooldown"><?php echo $skill['cooldown']; ?>s</span>
            </div>
            <?php endif; ?>
            
            <?php if($skill['damage'] > 0): ?>
            <div class="skill-stat-row">
                <span class="skill-stat-label">⚔️ Schaden:</span>
                <span class="skill-stat-value damage"><?php echo $skill['damage']; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if($skill['heal'] > 0): ?>
            <div class="skill-stat-row">
                <span class="skill-stat-label">❤️ Heilung:</span>
                <span class="skill-stat-value heal"><?php echo $skill['heal']; ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if($skill['scales_with']): ?>
        <div class="skill-scaling">
            📊 Skaliert mit: <strong><?php echo ucfirst($skill['scales_with']); ?></strong> (×<?php echo $skill['scaling_factor']; ?>)
        </div>
        <?php endif; ?>
        
        <?php if($skill['type'] === 'active'): ?>
        <button class="btn-use-skill" onclick="useSkill(<?php echo $skill['id']; ?>)">
            ✨ Fähigkeit nutzen
        </button>
        <?php endif; ?>
        
        <?php if($skill['skill_level'] < 10): ?>
        <button class="btn-upgrade-skill" onclick="upgradeSkill(<?php echo $skill['player_skill_id']; ?>)">
            ⬆️ Skill upgraden (1 Skill-Punkt)
        </button>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Verfügbare Skills -->
<?php if(!empty($availableSkills)): ?>
<h3 style="color: #f39c12; margin-top: 40px; margin-bottom: 20px;">🔓 Verfügbare Fähigkeiten</h3>
<div class="skills-grid" id="available-skills">
    <?php foreach($availableSkills as $skill): ?>
    <div class="skill-card" data-skill-type="<?php echo $skill['type']; ?>">
        <div class="skill-header">
            <div class="skill-icon"><?php echo $skill['icon']; ?></div>
            <div class="skill-info">
                <div class="skill-name"><?php echo $skill['name']; ?></div>
                <span class="skill-type"><?php echo ucfirst($skill['type']); ?></span>
            </div>
        </div>
        
        <div class="skill-description">
            <?php echo $skill['description']; ?>
        </div>
        
        <div class="skill-stats">
            <?php if($skill['mana_cost'] > 0): ?>
            <div class="skill-stat-row">
                <span class="skill-stat-label">💙 Mana-Kosten:</span>
                <span class="skill-stat-value mana"><?php echo $skill['mana_cost']; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if($skill['damage'] > 0): ?>
            <div class="skill-stat-row">
                <span class="skill-stat-label">⚔️ Schaden:</span>
                <span class="skill-stat-value damage"><?php echo $skill['damage']; ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <button class="btn-learn-skill" onclick="learnSkill(<?php echo $skill['id']; ?>)">
            📖 Fähigkeit lernen
        </button>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Gesperrte Skills -->
<h3 style="color: #95a5a6; margin-top: 40px; margin-bottom: 20px;">🔒 Gesperrte Fähigkeiten</h3>
<div class="skills-grid" id="locked-skills">
    <?php
    $lockedSkills = array_filter($classSkills, function($skill) use ($player) {
        return $skill['player_skill_id'] === null && $player['level'] < $skill['required_level'];
    });
    
    if(empty($lockedSkills)):
    ?>
    <div class="no-skills">
        <div class="no-skills-icon">🎉</div>
        <p>Alle Fähigkeiten freigeschaltet!</p>
    </div>
    <?php else: ?>
    <?php foreach($lockedSkills as $skill): ?>
    <div class="skill-card locked" data-skill-type="<?php echo $skill['type']; ?>">
        <div class="skill-header">
            <div class="skill-icon" style="filter: grayscale(100%);"><?php echo $skill['icon']; ?></div>
            <div class="skill-info">
                <div class="skill-name" style="color: #95a5a6;"><?php echo $skill['name']; ?></div>
                <span class="skill-type" style="background: rgba(149, 165, 166, 0.2); color: #95a5a6;">
                    <?php echo ucfirst($skill['type']); ?>
                </span>
            </div>
        </div>
        
        <div class="skill-description">
            <?php echo $skill['description']; ?>
        </div>
        
        <div class="skill-requirements">
            🔒 Benötigt Level <?php echo $skill['required_level']; ?> 
            (Aktuell: Level <?php echo $player['level']; ?>)
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
// Skill-Tabs
document.querySelectorAll('.skill-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.skill-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        const type = this.dataset.type;
        filterSkills(type);
    });
});

function filterSkills(type) {
    document.querySelectorAll('.skill-card').forEach(card => {
        if(type === 'all' || card.dataset.skillType === type) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function learnSkill(skillId) {
    if(!confirm('Diese Fähigkeit wirklich lernen?')) {
        return;
    }
    
    $.ajax({
        url: 'ajax/learn_skill.php',
        type: 'POST',
        data: {
            skill_id: skillId,
            csrf_token: '<?php echo CSRF::generateToken(); ?>'
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                alert('✅ ' + response.message);
                loadPage('skills');
            } else {
                alert('❌ ' + response.message);
            }
        },
        error: function() {
            alert('❌ Fehler beim Lernen der Fähigkeit');
        }
    });
}

function useSkill(skillId) {
    $.ajax({
        url: 'ajax/use_skill.php',
        type: 'POST',
        data: {
            skill_id: skillId,
            csrf_token: '<?php echo CSRF::generateToken(); ?>'
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                alert('✨ ' + response.message);
                loadPlayerData();
            } else {
                alert('❌ ' + response.message);
            }
        },
        error: function() {
            alert('❌ Fehler beim Nutzen der Fähigkeit');
        }
    });
}

function upgradeSkill(playerSkillId) {
    if(!confirm('Skill upgraden für 1 Skill-Punkt?')) {
        return;
    }
    
    $.ajax({
        url: 'ajax/upgrade_skill.php',
        type: 'POST',
        data: {
            player_skill_id: playerSkillId,
            csrf_token: '<?php echo CSRF::generateToken(); ?>'
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                alert('⬆️ ' + response.message);
                loadPage('skills');
            } else {
                alert('❌ ' + response.message);
            }
        },
        error: function() {
            alert('❌ Fehler beim Upgraden');
        }
    });
}
</script>