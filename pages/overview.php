<<<<<<< HEAD
<?php
require_once __DIR__ . '/../init.php';

$app = new App();
$auth = $app->getAuth();

$playerId = $auth->getCurrentPlayerId();
if(!$playerId) {
    echo '<p>Nicht eingeloggt</p>';
    exit;
}
?>

<h2>Übersicht</h2>
<div class="info-box">
    <h4>Willkommen zurück!</h4>
    <p>Dies ist die Übersichtsseite. Hier siehst du alle wichtigen Informationen auf einen Blick.</p>
</div>
<div class="info-box">
    <h4>Tägliche Quests</h4>
    <p>• Besiege 5 Gegner (3/5)<br>
       • Sammle 100 Gold (100/100) ✓<br>
       • Besuche den Shop (0/1)</p>
=======
<?php
require_once __DIR__ . '/../init.php';

$app = new App();
$auth = $app->getAuth();

$playerId = $auth->getCurrentPlayerId();
if(!$playerId) {
    echo '<p>Nicht eingeloggt</p>';
    exit;
}
?>

<h2>Übersicht</h2>
<div class="info-box">
    <h4>Willkommen zurück!</h4>
    <p>Dies ist die Übersichtsseite. Hier siehst du alle wichtigen Informationen auf einen Blick.</p>
</div>
<div class="info-box">
    <h4>Tägliche Quests</h4>
    <p>• Besiege 5 Gegner (3/5)<br>
       • Sammle 100 Gold (100/100) ✓<br>
       • Besuche den Shop (0/1)</p>
>>>>>>> 971ab47689bd561bd08c6e4d77cea7f516414d66
</div>