<div class="left-sidebar">
    <h3>Navigation</h3>
    <ul class="menu-list">
        <li data-page="overview">🏠 Übersicht</li>
        <li data-page="buildings">🏗️ Gebäude</li>
        <li data-page="resources">⛏️ Ressourcen</li>
        <li data-page="combat">⚔️ Kampf</li>
        <li data-page="shop">🏪 Shop</li>
        <li data-page="inventory">🎒 Inventar</li>
        <li data-page="guild">👥 Gilden</li>
        <li data-page="map">🗺️ Weltkarte</li>
        <li data-page="ranking">📊 Rangliste</li>
    </ul>
    
    <?php if($isAdmin): ?>
    <h3 style="margin-top: 30px; color: <?php echo $adminLevelInfo['color']; ?>;">
        👑 Admin
    </h3>
    <ul class="menu-list admin-menu">
        <li data-page="admin/dashboard" style="border-left: 3px solid <?php echo $adminLevelInfo['color']; ?>;">
            📊 Dashboard
        </li>
        
        <?php if($app->getAdmin()->hasPermission($playerId, 'view_players')): ?>
        <li data-page="admin/players" style="border-left: 3px solid <?php echo $adminLevelInfo['color']; ?>;">
            👥 Spieler-Verwaltung
        </li>
        <?php endif; ?>
        
        <?php if($app->getAdmin()->hasPermission($playerId, 'view_logs')): ?>
        <li data-page="admin/logs" style="border-left: 3px solid <?php echo $adminLevelInfo['color']; ?>;">
            📋 Logs
        </li>
        <?php endif; ?>
        
        <?php if($app->getAdmin()->hasPermission($playerId, 'manage_admins')): ?>
        <li data-page="admin/admins" style="border-left: 3px solid <?php echo $adminLevelInfo['color']; ?>;">
            ⚡ Admin-Verwaltung
        </li>
        <?php endif; ?>
        
        <?php if($app->getAdmin()->hasPermission($playerId, 'system_settings')): ?>
        <li data-page="admin/settings" style="border-left: 3px solid <?php echo $adminLevelInfo['color']; ?>;">
            ⚙️ Einstellungen
        </li>
        <?php endif; ?>
    </ul>
    <?php endif; ?>
</div>