<?php ?>
<div class="right-sidebar">

    <h3>Charakterinfo</h3>
    <div class="info-box">
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
    </div>

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
