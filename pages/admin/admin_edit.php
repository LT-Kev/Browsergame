<?php
// pages/admin/admin_edit.php

require_once __DIR__ . '/../../init.php';

use App\Core\App;

$app = App::getInstance();
$playerId = $app->getAuth()->getCurrentPlayerId();
$admin = $app->getAdmin();
$db = $app->getDB();

// Berechtigung prüfen
if (!$playerId || !$admin->hasPermission($playerId, 'edit_admins')) {
    echo '<p style="color:#e74c3c;">Keine Berechtigung</p>';
    exit;
}

// Admin-ID prüfen
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo '<p style="color:#e74c3c;">Ungültige Admin-ID</p>';
    exit;
}

// Admin laden
$adminUser = $db->selectOne(
    "SELECT * FROM players WHERE id = ? AND admin_level > 0",
    [$id]
);

if (!$adminUser) {
    echo '<p style="color:#e74c3c;">Admin nicht gefunden</p>';
    exit;
}

// Admin-Level Info laden
$adminLevelInfo = $db->selectOne(
    "SELECT * FROM admin_levels WHERE level = ?", 
    [$adminUser['admin_level']]
);

// Formular abgesendet?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $admin_level = intval($_POST['admin_level']);
    if ($admin_level < 0) $admin_level = 0;

    $db->update(
        "players",
        [
            'username' => $username,
            'email' => $email,
            'admin_level' => $admin_level
        ],
        "id = ?",
        [$id]
    );

    echo '<p style="color:#2ecc71;">Admin erfolgreich aktualisiert!</p>';

    $adminUser = $db->selectOne("SELECT * FROM players WHERE id = ?", [$id]);
    $adminLevelInfo = $db->selectOne(
        "SELECT * FROM admin_levels WHERE level = ?", 
        [$adminUser['admin_level']]
    );
}
?>

<h2>🛡️ Admin bearbeiten: <?= htmlspecialchars($adminUser['username']); ?></h2>

<form method="POST"
      action="pages/admin/admin_edit.php?id=<?= $id; ?>"
      style="background:#252525; padding:20px; border-radius:10px; color:#fff; max-width:500px;">

    <label>Username</label><br>
    <input type="text" name="username"
           value="<?= htmlspecialchars($adminUser['username']); ?>"
           style="width:100%; margin-bottom:10px;"><br>

    <label>Email</label><br>
    <input type="email" name="email"
           value="<?= htmlspecialchars($adminUser['email']); ?>"
           style="width:100%; margin-bottom:10px;"><br>

    <label>Admin-Level</label><br>
    <input type="number" name="admin_level"
           value="<?= $adminUser['admin_level']; ?>"
           style="width:100%; margin-bottom:10px;"><br>

    <?php if ($adminLevelInfo): ?>
        <p>
            <strong style="color: <?= htmlspecialchars($adminLevelInfo['color']); ?>;">
                <?= htmlspecialchars($adminLevelInfo['name']); ?>
            </strong><br>
            <small><?= htmlspecialchars($adminLevelInfo['description']); ?></small>
        </p>
    <?php endif; ?>

    <button type="submit"
            style="padding:10px 20px; background:#3498db; color:#fff; border:none; border-radius:5px;">
        Speichern
    </button>

    <button type="button"
            onclick="loadPage('admin/admins')"
            style="padding:10px 20px; background:#e74c3c; color:#fff; border:none; border-radius:5px;">
        Abbrechen
    </button>
</form>
