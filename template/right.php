<?php
// ============================================================================
// 2. UPDATE: template/right.php - Stats mit RPG-System erweitern
// ============================================================================
?>
<div class="right-sidebar">
    <h3>Charakterinfo</h3>
    <div class="info-box">
        <?php 
        // Rasse & Klasse anzeigen wenn vorhanden
        if($playerData['race_id'] && $playerData['class_id']):
            $race = $app->getDB()->selectOne("SELECT * FROM races WHERE id = :id", [':id' => $playerData['race_id']]);
            $class = $app->getDB()->selectOne("SELECT * FROM classes WHERE id = :id", [':id' => $playerData['class_id']]);
        ?>
        <p style="font-size: 0.9em; color: #bdc3c7; margin-bottom: 10px;">
            <strong><?php echo $race['icon']; ?> <?php echo $race['name']; ?></strong><br>
            <strong><?php echo $class['icon']; ?> <?php echo $class['name']; ?></strong>
        </p>
        <?php endif; ?>
        
        <p>
            <span class="iconify" data-icon="twemoji:military-medal" data-width="20"></span>
            <strong>Level:</strong>
            <span id="char-level"><?php echo $playerData['level']; ?></span>
        </p>

        <p>
            <span class="iconify" data-icon="twemoji:star" data-width="20"></span>
            <strong>EP:</strong>
            <span id="char-exp"><?php echo $playerData['exp']; ?></span> /
            <span id="char-exp-needed"><?php echo $app->getPlayer()->getExpNeeded($playerData['level']); ?></span>
        </p>

        <p>
            <span class="iconify" data-icon="twemoji:red-heart" data-width="20"></span>
            <strong>HP:</strong>
            <span id="char-hp"><?php echo $playerData['hp']; ?></span> /
            <span id="char-max-hp"><?php echo $playerData['max_hp']; ?></span>
        </p>
        
        <?php if($playerData['character_created']): ?>
        <p>
            <span class="iconify" data-icon="twemoji:blue-heart" data-width="20"></span>
            <strong>Mana:</strong>
            <span id="char-mana"><?php echo $playerData['mana']; ?></span> /
            <span id="char-max-mana"><?php echo $playerData['max_mana']; ?></span>
        </p>
        
        <p>
            <span class="iconify" data-icon="twemoji:zap" data-width="20"></span>
            <strong>Ausdauer:</strong>
            <span id="char-stamina"><?php echo $playerData['stamina']; ?></span> /
            <span id="char-max-stamina"><?php echo $playerData['max_stamina']; ?></span>
        </p>
        <?php endif; ?>

        <p>
            <span class="iconify" data-icon="twemoji:crossed-swords" data-width="20"></span>
            <strong>Angriff:</strong>
            <span id="char-attack"><?php echo $playerData['attack']; ?></span>
        </p>

        <p>
            <span class="iconify" data-icon="twemoji:shield" data-width="20"></span>
            <strong>Verteidigung:</strong>
            <span id="char-defense"><?php echo $playerData['defense']; ?></span>
        </p>
        
        <?php if($playerData['stat_points'] > 0): ?>
        <div style="margin-top: 10px; padding: 10px; background: rgba(46, 204, 113, 0.2); border-radius: 5px; text-align: center;">
            <strong style="color: #2ecc71;">
                <?php echo $playerData['stat_points']; ?> Statuspunkte verfügbar!
            </strong>
            <button onclick="loadPage('character')" style="margin-top: 5px; padding: 5px 10px; background: #2ecc71; border: none; border-radius: 3px; color: #fff; cursor: pointer; width: 100%;">
                Jetzt verteilen
            </button>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if($playerData['character_created']): ?>
    <h3 style="margin-top: 20px;">💪 Primäre Stats</h3>
    <div class="info-box">
        <p>
            <span>💪 Stärke:</span>
            <strong id="char-strength"><?php echo $playerData['strength']; ?></strong>
        </p>
        <p>
            <span>🏃 Geschick:</span>
            <strong id="char-dexterity"><?php echo $playerData['dexterity']; ?></strong>
        </p>
        <p>
            <span>❤️ Konstitution:</span>
            <strong id="char-constitution"><?php echo $playerData['constitution']; ?></strong>
        </p>
        <p>
            <span>🧠 Intelligenz:</span>
            <strong id="char-intelligence"><?php echo $playerData['intelligence']; ?></strong>
        </p>
        <p>
            <span>🦉 Weisheit:</span>
            <strong id="char-wisdom"><?php echo $playerData['wisdom']; ?></strong>
        </p>
        <p>
            <span>✨ Charisma:</span>
            <strong id="char-charisma"><?php echo $playerData['charisma']; ?></strong>
        </p>
    </div>
    <?php endif; ?>

    <h3 style="margin-top: 20px;">Produktion/h</h3>
    <div class="info-box">
        <p>
            <span class="iconify" data-icon="twemoji:coin" data-width="20"></span>
            Gold: <strong id="prod-gold"><?php echo $playerData['gold_production']; ?></strong>
        </p>

        <p>
            <span class="iconify" data-icon="twemoji:poultry-leg" data-width="20"></span>
            Nahrung: <strong id="prod-food"><?php echo $playerData['food_production']; ?></strong>
        </p>

        <p>
            <span class="iconify" data-icon="twemoji:wood" data-width="20"></span>
            Holz: <strong id="prod-wood"><?php echo $playerData['wood_production']; ?></strong>
        </p>

        <p>
            <span class="iconify" data-icon="twemoji:rock" data-width="20"></span>
            Stein: <strong id="prod-stone"><?php echo $playerData['stone_production']; ?></strong>
        </p>
    </div>

    <h3 style="margin-top: 20px;">Lager</h3>
    <div class="info-box">
        <p>
            <span class="iconify" data-icon="twemoji:coin" data-width="20"></span>
            <span id="gold-storage"><?php echo number_format($playerData['gold'], 0, ',', '.'); ?></span>
            / <?php echo number_format($playerData['gold_capacity'], 0, ',', '.'); ?>
        </p>

        <p>
            <span class="iconify" data-icon="twemoji:poultry-leg" data-width="20"></span>
            <span id="food-storage"><?php echo number_format($playerData['food'], 0, ',', '.'); ?></span>
            / <?php echo number_format($playerData['food_capacity'], 0, ',', '.'); ?>
        </p>

        <p>
            <span class="iconify" data-icon="twemoji:wood" data-width="20"></span>
            <span id="wood-storage"><?php echo number_format($playerData['wood'], 0, ',', '.'); ?></span>
            / <?php echo number_format($playerData['wood_capacity'], 0, ',', '.'); ?>
        </p>

        <p>
            <span class="iconify" data-icon="twemoji:rock" data-width="20"></span>
            <span id="stone-storage"><?php echo number_format($playerData['stone'], 0, ',', '.'); ?></span>
            / <?php echo number_format($playerData['stone_capacity'], 0, ',', '.'); ?>
        </p>
    </div>
</div>