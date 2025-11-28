<?php
session_start();
require_once __DIR__ . '/../../init.php';

$playerId = $_SESSION['player_id'] ?? 0;
$db = new Database();
$playerObj = new Player($db);
$player = $playerObj->getPlayerById($playerId);

if (!$player || ($player['admin'] ?? 0) < 1) {
    die('Zugriff verweigert');
}
?>
<h1>🛠️ Admin Dashboard</h1>
<p>Willkommen, <?= htmlspecialchars($player['username']) ?>!</p>
