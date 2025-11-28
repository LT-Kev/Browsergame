<?php
// Spielerdaten sind bereits in $playerData aus index.php geladen
?>
<div class="topbar">
    <div class="topbar-left">
        <span class="iconify" data-icon="twemoji:video-game" data-width="24" data-height="24"></span>
        <?php echo SITE_NAME; ?>
    </div>

    <div class="topbar-right">
        <span id="player-name" title="Spielername">
            <span class="iconify" data-icon="twemoji:bust-in-silhouette" data-width="20" data-height="20"></span>
            <?php echo htmlspecialchars($playerData['username']); ?>
            <?php if($isAdmin): ?>
                <span class="admin-badge" style="background: <?php echo $adminLevelInfo['color']; ?>;">
                    <?php echo $adminLevelInfo['name']; ?>
                </span>
            <?php endif; ?>
        </span>

        <span id="player-gold" title="Gold: <?php echo $playerData['gold']; ?> / <?php echo $playerData['gold_capacity']; ?>">
            <span class="iconify" data-icon="twemoji:coin" data-width="20" data-height="20"></span>
            <?php echo number_format($playerData['gold'], 0, ',', '.'); ?>
        </span>

        <span id="player-food" title="Nahrung: <?php echo $playerData['food']; ?> / <?php echo $playerData['food_capacity']; ?>">
            <span class="iconify" data-icon="twemoji:poultry-leg" data-width="20" data-height="20"></span>
            <?php echo number_format($playerData['food'], 0, ',', '.'); ?>
        </span>

        <span id="player-wood" title="Holz: <?php echo $playerData['wood']; ?> / <?php echo $playerData['wood_capacity']; ?>">
            <span class="iconify" data-icon="twemoji:wood" data-width="20" data-height="20"></span>
            <?php echo number_format($playerData['wood'], 0, ',', '.'); ?>
        </span>

        <span id="player-stone" title="Stein: <?php echo $playerData['stone']; ?> / <?php echo $playerData['stone_capacity']; ?>">
            <span class="iconify" data-icon="twemoji:rock" data-width="20" data-height="20"></span>
            <?php echo number_format($playerData['stone'], 0, ',', '.'); ?>
        </span>

        <span id="player-energy">
            <span class="iconify" data-icon="twemoji:high-voltage" data-width="20" data-height="20"></span>
            <?php echo $playerData['energy']; ?>/100
        </span>

        <a href="logout.php">
            <span class="iconify" data-icon="twemoji:door" data-width="20" data-height="20"></span>
            Logout
        </a>
    </div>
</div>