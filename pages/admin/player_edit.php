<<<<<<< HEAD
<?php
//pages/admin/player_edit.php

require_once __DIR__ . '/../../init.php';

$app = new App();
$auth = $app->getAuth();
$playerId = $auth->getCurrentPlayerId();
$admin = $app->getAdmin();
$db = $app->getDB();

// Berechtigungen prüfen
if(!$playerId || !$admin->hasPermission($playerId, 'edit_players')) {
    echo '<p style="color: #e74c3c;">Keine Berechtigung</p>';
    exit;
}

// Spieler-ID prüfen
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($id <= 0) {
    var_dump($id);
    echo '<p style="color: #e74c3c;">Ungültige Spieler-ID</p>';
    exit;
}

// Spieler laden
$player = $db->selectOne("SELECT * FROM players WHERE id = ?", [$id]);
if(!$player) {
    echo '<p style="color: #e74c3c;">Spieler nicht gefunden</p>';
    exit;
}

// Formular abgesendet?
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $gold = intval($_POST['gold']);
    $food = intval($_POST['food']);
    $wood = intval($_POST['wood']);
    $stone = intval($_POST['stone']);
    $admin_level = intval($_POST['admin_level']);

    $db->update("players", [
        'username' => $username,
        'email' => $email,
        'gold' => $gold,
        'food' => $food,
        'wood' => $wood,
        'stone' => $stone,
        'admin_level' => $admin_level
    ], "id = ?", [$id]);

    echo '<p style="color: #2ecc71;">Spieler erfolgreich aktualisiert!</p>';

    // Spieler neu laden
    $player = $db->selectOne("SELECT * FROM players WHERE id = ?", [$id]);
}
?>

<h2>✏️ Spieler bearbeiten: <?php echo htmlspecialchars($player['username']); ?></h2>

<form id="player-edit-form" action="pages/admin/player_edit.php?id=<?php echo $id; ?>"method="POST" style="background:#252525; padding:20px; border-radius:10px; color:#fff; max-width:600px;">
    <div id="edit-message"></div>
    <label>Username:</label><br>
    <input type="text" name="username" value="<?php echo htmlspecialchars($player['username']); ?>" style="width:100%; margin-bottom:10px;"><br>

    <label>Email:</label><br>
    <input type="email" name="email" value="<?php echo htmlspecialchars($player['email']); ?>" style="width:100%; margin-bottom:10px;"><br>

    <label>Gold:</label><br>
    <input type="number" name="gold" value="<?php echo $player['gold']; ?>" style="width:100%; margin-bottom:10px;"><br>

    <label>Food:</label><br>
    <input type="number" name="food" value="<?php echo $player['food']; ?>" style="width:100%; margin-bottom:10px;"><br>

    <label>Wood:</label><br>
    <input type="number" name="wood" value="<?php echo $player['wood']; ?>" style="width:100%; margin-bottom:10px;"><br>

    <label>Stone:</label><br>
    <input type="number" name="stone" value="<?php echo $player['stone']; ?>" style="width:100%; margin-bottom:10px;"><br>

    <label>Admin-Level:</label><br>
    <input type="number" name="admin_level" value="<?php echo $player['admin_level']; ?>" style="width:100%; margin-bottom:10px;"><br>

    <button type="submit" style="padding:10px 20px; background:#3498db; color:#fff; border:none; border-radius:5px;">Speichern</button>
    <button type="button" onclick="loadPage('admin/players')" style="padding:10px 20px; background:#e74c3c; color:#fff; border:none; border-radius:5px;">Abbrechen</button>
</form>
=======
<?php
//pages/admin/player_edit.php

require_once __DIR__ . '/../../init.php';

$app = new App();
$auth = $app->getAuth();
$playerId = $auth->getCurrentPlayerId();
$admin = $app->getAdmin();
$db = $app->getDB();

// Berechtigungen prüfen
if(!$playerId || !$admin->hasPermission($playerId, 'edit_players')) {
    echo '<p style="color: #e74c3c;">Keine Berechtigung</p>';
    exit;
}

// Spieler-ID prüfen
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($id <= 0) {
    var_dump($id);
    echo '<p style="color: #e74c3c;">Ungültige Spieler-ID</p>';
    exit;
}

// Spieler laden
$player = $db->selectOne("SELECT * FROM players WHERE id = ?", [$id]);
if(!$player) {
    echo '<p style="color: #e74c3c;">Spieler nicht gefunden</p>';
    exit;
}

// Formular abgesendet?
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $gold = intval($_POST['gold']);
    $food = intval($_POST['food']);
    $wood = intval($_POST['wood']);
    $stone = intval($_POST['stone']);
    $admin_level = intval($_POST['admin_level']);

    $db->update("players", [
        'username' => $username,
        'email' => $email,
        'gold' => $gold,
        'food' => $food,
        'wood' => $wood,
        'stone' => $stone,
        'admin_level' => $admin_level
    ], "id = ?", [$id]);

    echo '<p style="color: #2ecc71;">Spieler erfolgreich aktualisiert!</p>';

    // Spieler neu laden
    $player = $db->selectOne("SELECT * FROM players WHERE id = ?", [$id]);
}
?>

<h2>✏️ Spieler bearbeiten: <?php echo htmlspecialchars($player['username']); ?></h2>

<form id="player-edit-form" action="pages/admin/player_edit.php?id=<?php echo $id; ?>"method="POST" style="background:#252525; padding:20px; border-radius:10px; color:#fff; max-width:600px;">
    <div id="edit-message"></div>
    <label>Username:</label><br>
    <input type="text" name="username" value="<?php echo htmlspecialchars($player['username']); ?>" style="width:100%; margin-bottom:10px;"><br>

    <label>Email:</label><br>
    <input type="email" name="email" value="<?php echo htmlspecialchars($player['email']); ?>" style="width:100%; margin-bottom:10px;"><br>

    <label>Gold:</label><br>
    <input type="number" name="gold" value="<?php echo $player['gold']; ?>" style="width:100%; margin-bottom:10px;"><br>

    <label>Food:</label><br>
    <input type="number" name="food" value="<?php echo $player['food']; ?>" style="width:100%; margin-bottom:10px;"><br>

    <label>Wood:</label><br>
    <input type="number" name="wood" value="<?php echo $player['wood']; ?>" style="width:100%; margin-bottom:10px;"><br>

    <label>Stone:</label><br>
    <input type="number" name="stone" value="<?php echo $player['stone']; ?>" style="width:100%; margin-bottom:10px;"><br>

    <label>Admin-Level:</label><br>
    <input type="number" name="admin_level" value="<?php echo $player['admin_level']; ?>" style="width:100%; margin-bottom:10px;"><br>

    <button type="submit" style="padding:10px 20px; background:#3498db; color:#fff; border:none; border-radius:5px;">Speichern</button>
    <button type="button" onclick="loadPage('admin/players')" style="padding:10px 20px; background:#e74c3c; color:#fff; border:none; border-radius:5px;">Abbrechen</button>
</form>
>>>>>>> 971ab47689bd561bd08c6e4d77cea7f516414d66
