<?php
require_once __DIR__ . '/../../init.php';

use App\Core\App;

$app = App::getInstance();
$playerId = $app->getAuth()->getCurrentPlayerId();
if(!$playerId || !$app->getAdmin()->hasPermission($playerId, 'system_settings')) {
    echo '<p style="color: #e74c3c;">Keine Berechtigung</p>';
    exit;
}
?>

<h2>⚙️ System-Einstellungen</h2>
<div class="info-box">
    <h4>Coming Soon</h4>
    <p>System-Einstellungen werden noch entwickelt.</p>
</div>