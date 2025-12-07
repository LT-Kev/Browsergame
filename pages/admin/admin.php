<?php
//pages/admin/admin.php

require_once __DIR__ . '/../../init.php';

use App\Core\App;

$app = App::getInstance();
$playerId = $app->getAuth()->getCurrentPlayerId();
if(!$playerId || !$app->getAdmin()->hasPermission($playerId, 'manage_admins')) {
    echo '<p style="color: #e74c3c;">Keine Berechtigung</p>';
    exit;
}
?>

<h2>⚡ Admin-Verwaltung</h2>
<div class="info-box">
    <h4>Coming Soon</h4>
    <p>Admin-Verwaltung wird noch entwickelt.</p>
</div>