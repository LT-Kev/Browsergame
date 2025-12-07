<?php
require_once __DIR__ . '/../init.php';

// App kommt aus init.php
$playerId = $app->getAuth()->getCurrentPlayerId();

?>

<div class="center-content" id="center-content">

    <?php
    if(!$playerId) {
        echo '<p>Nicht eingeloggt</p>';
    exit;
    }
    ?>


    <h2>Hauptbereich</h2>
    <div class="info-box">
        <h4>Willkommensnachricht</h4>
        <p>Hier wird der Hauptinhalt deines Browsergames angezeigt. Dieser Bereich passt sich dynamisch an und scrollt bei längeren Inhalten.</p>
    </div>
    <div class="info-box">
        <h4>Letzte Aktivitäten</h4>
        <p>• Du hast einen Gegner besiegt<br>
           • Neue Quest verfügbar<br>
           • Level aufgestiegen!</p>
    </div>
    <div class="info-box">
        <h4>Class System Update</h4>
        <p></p>
    </div>
</div>