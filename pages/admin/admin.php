<?php
// pages/admin/admin.php

require_once __DIR__ . '/../../init.php';

use App\Core\App;

$app = App::getInstance();
$playerId = $app->getAuth()->getCurrentPlayerId();
$admin = $app->getAdmin();
$db = $app->getDB();

// Berechtigung prüfen
if (!$playerId || !$admin->hasPermission($playerId, 'view_admins')) {
    echo '<p style="color:#e74c3c;">Keine Berechtigung</p>';
    exit;
}

// Alle Admins laden mit Level-Infos
$admins = $db->select("
    SELECT p.*, al.name AS level_name, al.description AS level_description, al.color AS level_color
    FROM players p
    LEFT JOIN admin_levels al ON p.admin_level = al.level
    WHERE p.admin_level > 0
    ORDER BY p.admin_level DESC, p.id ASC
");
?>

<h2>🛡️ Admin-Verwaltung</h2>

<table border="1" cellpadding="10" cellspacing="0"
       style="width:100%; border-collapse:collapse; background:#252525; color:#fff;">
    <thead style="background:#1a1a1a;">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Admin-Level</th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($admins as $a): ?>
            <tr>
                <td><?= $a['id']; ?></td>
                <td><?= htmlspecialchars($a['username']); ?></td>
                <td><?= htmlspecialchars($a['email']); ?></td>
                <td>
                    <span style="color: <?= htmlspecialchars($a['level_color']); ?>; font-weight:bold;">
                        <?= htmlspecialchars($a['level_name']); ?>
                    </span><br>
                    <small><?= htmlspecialchars($a['level_description']); ?></small>
                </td>
                <td>
                    <?php if ($admin->hasPermission($playerId, 'edit_admins')): ?>
                        <button class="edit-admin" data-id="<?= $a['id']; ?>">✏️ Bearbeiten</button>
                    <?php endif; ?>

                    <?php if ($admin->hasPermission($playerId, 'delete_admins')): ?>
                        <button
                            onclick="if(confirm('Admin wirklich entfernen?')) loadPage('admin/admin_delete', {id: <?= $a['id']; ?>})"
                            style="background:#e74c3c; color:#fff; padding:5px 10px;">
                            🗑 Entfernen
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
