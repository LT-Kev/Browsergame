<?php
// pages/admin/admin_delete.php

require_once __DIR__ . '/../../init.php';

use App\Core\App;

$app = App::getInstance();
$playerId = $app->getAuth()->getCurrentPlayerId();
$admin = $app->getAdmin();
$db = $app->getDB();

if (!$playerId || !$admin->hasPermission($playerId, 'delete_admins')) {
    exit('Keine Berechtigung');
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    exit('Ungültige ID');
}

// Admin-Level auf 0 setzen, Account bleibt bestehen
$db->update(
    "players",
    ['admin_level' => 0],
    "id = ?",
    [$id]
);

echo '<p style="color:#2ecc71;">Adminrechte entfernt</p>';
echo "<script>setTimeout(() => loadPage('admin/admins'), 800);</script>";
