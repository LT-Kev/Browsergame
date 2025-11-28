<?php
// pages/admin/players.php

require_once __DIR__ . '/../../init.php';

$app = new App();
$auth = $app->getAuth();
$playerId = $auth->getCurrentPlayerId();
$admin = $app->getAdmin();
$db = $app->getDB();

// Berechtigung prüfen
if(!$playerId || !$admin->hasPermission($playerId, 'view_players')) {
    echo '<p style="color: #e74c3c;">Keine Berechtigung</p>';
    exit;
}

// Alle Spieler laden
$players = $db->select("SELECT * FROM players ORDER BY id ASC");
?>

<h2>👥 Spieler-Verwaltung</h2>

<table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse:collapse; background:#252525; color:#fff;">
    <thead style="background:#1a1a1a;">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Gold</th>
            <th>Food</th>
            <th>Wood</th>
            <th>Stone</th>
            <th>Admin-Level</th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($players as $p): ?>
        <tr>
            <td><?php echo $p['id']; ?></td>
            <td><?php echo htmlspecialchars($p['username']); ?></td>
            <td><?php echo htmlspecialchars($p['email']); ?></td>
            <td><?php echo number_format($p['gold'], 0, ',', '.'); ?></td>
            <td><?php echo number_format($p['food'], 0, ',', '.'); ?></td>
            <td><?php echo number_format($p['wood'], 0, ',', '.'); ?></td>
            <td><?php echo number_format($p['stone'], 0, ',', '.'); ?></td>
            <td><?php echo $p['admin_level']; ?></td>
            <td>
                <?php if($admin->hasPermission($playerId, 'edit_players')): ?>
                    <button class="edit-player" data-id="<?php echo $p['id']; ?>">✏️ Bearbeiten</button>
                <?php endif; ?>
                <?php if($admin->hasPermission($playerId, 'delete_players')): ?>
                    <button onclick="if(confirm('Spieler wirklich löschen?')) loadPage('admin/player_delete?id=<?php echo $p['id']; ?>')" style="padding:5px 10px; background:#e74c3c; color:#fff;">🗑 Löschen</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
