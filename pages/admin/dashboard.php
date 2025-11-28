<?php
// pages/admin/dashboard.php

require_once __DIR__ . '/../../init.php';


$app = new App();
$auth = $app->getAuth();

$playerId = $auth->getCurrentPlayerId();
if(!$playerId || !$app->getAdmin()->isAdmin($playerId)) {
    echo '<p style="color: #e74c3c;">Keine Berechtigung</p>';
    exit;
}

// Statistiken laden
$db = $app->getDB();

$totalPlayers = $db->selectOne("SELECT COUNT(*) as count FROM players");
$totalAdmins = $db->selectOne("SELECT COUNT(*) as count FROM players WHERE admin_level > 0");
$onlineToday = $db->selectOne("SELECT COUNT(*) as count FROM players WHERE last_login >= CURDATE()");
$totalResources = $db->selectOne("SELECT SUM(gold + food + wood + stone) as total FROM players");

$adminLevel = $app->getAdmin()->getAdminLevel($playerId);
$adminLevelInfo = $app->getAdmin()->getAdminLevelInfo($adminLevel);
?>

<style>
.admin-dashboard {
    padding: 20px;
}

.dashboard-header {
    background: linear-gradient(135deg, <?php echo $adminLevelInfo['color']; ?>, <?php echo $adminLevelInfo['color']; ?>dd);
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    text-align: center;
}

.dashboard-header h1 {
    color: #fff;
    font-size: 2.5em;
    margin-bottom: 10px;
}

.admin-level-badge {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    padding: 8px 20px;
    border-radius: 20px;
    font-size: 1.1em;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #0f3460 0%, #16213e 100%);
    border: 2px solid rgba(233, 69, 96, 0.3);
    border-radius: 15px;
    padding: 25px;
    text-align: center;
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(233, 69, 96, 0.3);
    border-color: #e94560;
}

.stat-icon {
    font-size: 3em;
    margin-bottom: 15px;
}

.stat-value {
    font-size: 2.5em;
    font-weight: bold;
    color: #e94560;
    margin-bottom: 10px;
}

.stat-label {
    color: #bdc3c7;
    font-size: 1.1em;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.action-btn {
    background: linear-gradient(135deg, #e94560 0%, #d63251 100%);
    border: none;
    border-radius: 10px;
    padding: 20px;
    color: #fff;
    font-size: 1.1em;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.action-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(233, 69, 96, 0.4);
}
</style>

<div class="admin-dashboard">
    <div class="dashboard-header">
        <h1>👑 Admin Dashboard</h1>
        <div class="admin-level-badge">
            <?php echo $adminLevelInfo['name']; ?> (Level <?php echo $adminLevel; ?>)
        </div>
    </div>
    
    <h2 style="margin-bottom: 20px;">📊 Statistiken</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-value"><?php echo number_format($totalPlayers['count'], 0, ',', '.'); ?></div>
            <div class="stat-label">Gesamt Spieler</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⚡</div>
            <div class="stat-value"><?php echo number_format($totalAdmins['count'], 0, ',', '.'); ?></div>
            <div class="stat-label">Admins</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🟢</div>
            <div class="stat-value"><?php echo number_format($onlineToday['count'], 0, ',', '.'); ?></div>
            <div class="stat-label">Heute Online</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💎</div>
            <div class="stat-value"><?php echo number_format($totalResources['total'], 0, ',', '.'); ?></div>
            <div class="stat-label">Gesamt Ressourcen</div>
        </div>
    </div>
    
    <h2 style="margin-bottom: 20px;">⚡ Schnellaktionen</h2>
    <div class="quick-actions">
        <?php if($app->getAdmin()->hasPermission($playerId, 'view_players')): ?>
        <button class="action-btn" onclick="loadPage('admin/players')">
            <span class="iconify" data-icon="twemoji:busts-in-silhouette" data-width="24"></span>
            Spieler verwalten
        </button>
        <?php endif; ?>
        
        <?php if($app->getAdmin()->hasPermission($playerId, 'view_logs')): ?>
        <button class="action-btn" onclick="loadPage('admin/logs')">
            <span class="iconify" data-icon="twemoji:scroll" data-width="24"></span>
            Logs ansehen
        </button>
        <?php endif; ?>
        
        <?php if($app->getAdmin()->hasPermission($playerId, 'manage_admins')): ?>
        <button class="action-btn" onclick="loadPage('admin/admins')">
            <span class="iconify" data-icon="twemoji:crown" data-width="24"></span>
            Admins verwalten
        </button>
        <?php endif; ?>
        
        <?php if($app->getAdmin()->hasPermission($playerId, 'system_settings')): ?>
        <button class="action-btn" onclick="loadPage('admin/settings')">
            <span class="iconify" data-icon="twemoji:gear" data-width="24"></span>
            Einstellungen
        </button>
        <?php endif; ?>
    </div>
    
    <div class="info-box" style="margin-top: 30px;">
        <h4>ℹ️ Deine Berechtigungen</h4>
        <p><?php echo $adminLevelInfo['description']; ?></p>
    </div>
</div>