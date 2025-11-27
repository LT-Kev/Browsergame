<?php
// init.php – zentrale Initialisierung

// Config laden
require_once __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Autoloader für alle Klassen
spl_autoload_register(function($className) {
    $file = __DIR__ . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class.' . strtolower($className) . '.php';
    if(file_exists($file)) {
        require_once $file;
    } else {
        if(defined('DEV_MODE') && DEV_MODE) {
            error_log("Klasse nicht gefunden: $className -> $file");
        }
    }
});

// App initialisieren
$app = new App();
$auth = $app->getAuth();

// Globale Funktion zum Laden von Seiten
function loadPage($page) {
    global $app;
    $url = __DIR__ . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . $page . '.php';
    if(file_exists($url)) {
        include $url;
    } else {
        echo "<p style='color: red;'>Seite nicht gefunden: $page</p>";
    }
}

// Globale Funktion zum Reload der Spielerdaten (AJAX)
function reloadPlayerData() {
    global $app;
    $playerId = $app->getAuth()->getCurrentPlayerId();
    return $app->getPlayer()->getPlayerData($playerId);
}
?>
