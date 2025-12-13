<?php
// pages/admin/dashboard.php
require_once __DIR__ . '/../../init.php';

use App\Core\App;

$app = App::getInstance();
$playerId = $app->getAuth()->getCurrentPlayerId();
$admin = $app->getAdmin();

if(!$playerId || !$admin->isAdmin($playerId)) {
    echo '<p style="color: #e74c3c;">Keine Berechtigung</p>';
    exit;
}

$adminLevel = $admin->getAdminLevel($playerId);
$adminLevelInfo = $admin->getAdminLevelInfo($adminLevel);
?>

<style>
.admin-menu {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.admin-header {
    text-align: center;
    margin-bottom: 40px;
}

.admin-header h1 {
    color: #e94560;
    font-size: 2.5em;
    margin-bottom: 10px;
}

.admin-level-info {
    display: inline-block;
    background: <?php echo $adminLevelInfo['color']; ?>;
    padding: 8px 20px;
    border-radius: 20px;
    color: #fff;
    font-weight: bold;
}

.tiles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.admin-tile {
    background: linear-gradient(135deg, #1e2a3a 0%, #0f1922 100%);
    border: 2px solid rgba(233, 69, 96, 0.3);
    border-radius: 15px;
    padding: 30px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    min-height: 160px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.admin-tile:hover {
    transform: translateY(-5px);
    border-color: #e94560;
    box-shadow: 0 10px 30px rgba(233, 69, 96, 0.5);
    background: linear-gradient(135deg, #e94560 0%, #d63251 100%);
}

.admin-tile.disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.admin-tile.disabled:hover {
    transform: none;
    border-color: rgba(233, 69, 96, 0.3);
    box-shadow: none;
    background: linear-gradient(135deg, #1e2a3a 0%, #0f1922 100%);
}

.tile-icon {
    font-size: 3.5em;
    line-height: 1;
}

.tile-label {
    color: #fff;
    font-size: 1.1em;
    font-weight: bold;
}

.tile-desc {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.85em;
}

.section-divider {
    color: #e94560;
    font-size: 1.5em;
    font-weight: bold;
    margin: 40px 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid rgba(233, 69, 96, 0.3);
}
</style>

<div class="admin-menu">
    <div class="admin-header">
        <h1>👑 Admin-Bereich</h1>
        <div class="admin-level-info">
            <?php echo $adminLevelInfo['name']; ?> (Level <?php echo $adminLevel; ?>)
        </div>
    </div>
    
    <!-- Hauptverwaltung -->
    <div class="section-divider">📋 Hauptverwaltung</div>
    <div class="tiles-grid">
        <?php if($admin->hasPermission($playerId, 'view_players')): ?>
        <div class="admin-tile" onclick="loadPage('admin/players')">
            <div class="tile-icon">👥</div>
            <div class="tile-label">Spieler</div>
            <div class="tile-desc">Verwalten</div>
        </div>
        <?php endif; ?>
        
        <?php if($admin->hasPermission($playerId, 'manage_admins')): ?>
        <div class="admin-tile" onclick="loadPage('admin/admin')">
            <div class="tile-icon">⚡</div>
            <div class="tile-label">Admins</div>
            <div class="tile-desc">Verwalten</div>
        </div>
        <?php endif; ?>
        
        <?php if($admin->hasPermission($playerId, 'view_logs')): ?>
        <div class="admin-tile" onclick="loadPage('admin/logs')">
            <div class="tile-icon">📜</div>
            <div class="tile-label">Logs</div>
            <div class="tile-desc">System-Logs</div>
        </div>
        <?php endif; ?>
        
        <?php if($admin->hasPermission($playerId, 'system_settings')): ?>
        <div class="admin-tile" onclick="loadPage('admin/settings')">
            <div class="tile-icon">⚙️</div>
            <div class="tile-label">Einstellungen</div>
            <div class="tile-desc">System</div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Spielinhalt -->
    <?php if($admin->hasPermission($playerId, 'system_settings')): ?>
    <div class="section-divider">🎮 Spielinhalt</div>
    <div class="tiles-grid">
        <div class="admin-tile" onclick="loadPage('admin/race_manager')">
            <div class="tile-icon">🧬</div>
            <div class="tile-label">Rassen</div>
            <div class="tile-desc">Manager</div>
        </div>
        
        <div class="admin-tile" onclick="loadPage('admin/class_manager')">
            <div class="tile-icon">⚔️</div>
            <div class="tile-label">Klassen</div>
            <div class="tile-desc">Manager</div>
        </div>
        
        <div class="admin-tile disabled" title="Coming Soon">
            <div class="tile-icon">🗡️</div>
            <div class="tile-label">Items</div>
            <div class="tile-desc">Bald verfügbar</div>
        </div>
        
        <div class="admin-tile disabled" title="Coming Soon">
            <div class="tile-icon">🏰</div>
            <div class="tile-label">Gebäude</div>
            <div class="tile-desc">Bald verfügbar</div>
        </div>
        
        <div class="admin-tile disabled" title="Coming Soon">
            <div class="tile-icon">👹</div>
            <div class="tile-label">Monster</div>
            <div class="tile-desc">Bald verfügbar</div>
        </div>
        
        <div class="admin-tile disabled" title="Coming Soon">
            <div class="tile-icon">📜</div>
            <div class="tile-label">Quests</div>
            <div class="tile-desc">Bald verfügbar</div>
        </div>
        
        <div class="admin-tile disabled" title="Coming Soon">
            <div class="tile-icon">✨</div>
            <div class="tile-label">Skills</div>
            <div class="tile-desc">Bald verfügbar</div>
        </div>
        
        <div class="admin-tile disabled" title="Coming Soon">
            <div class="tile-icon">🗺️</div>
            <div class="tile-label">Locations</div>
            <div class="tile-desc">Bald verfügbar</div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Wirtschaft -->
    <?php if($admin->hasPermission($playerId, 'system_settings')): ?>
    <div class="section-divider">💰 Wirtschaft</div>
    <div class="tiles-grid">
        <div class="admin-tile disabled" title="Coming Soon">
            <div class="tile-icon">🏪</div>
            <div class="tile-label">Shop</div>
            <div class="tile-desc">Bald verfügbar</div>
        </div>
        
        <div class="admin-tile disabled" title="Coming Soon">
            <div class="tile-icon">💎</div>
            <div class="tile-label">Ressourcen</div>
            <div class="tile-desc">Bald verfügbar</div>
        </div>
        
        <div class="admin-tile disabled" title="Coming Soon">
            <div class="tile-icon">📊</div>
            <div class="tile-label">Preise</div>
            <div class="tile-desc">Bald verfügbar</div>
        </div>
        
        <div class="admin-tile disabled" title="Coming Soon">
            <div class="tile-icon">🎁</div>
            <div class="tile-label">Belohnungen</div>
            <div class="tile-desc">Bald verfügbar</div>
        </div>
    </div>
    <?php endif; ?>
</div>