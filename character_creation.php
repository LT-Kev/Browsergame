<?php
// ============================================================================
// 4. NEU: character_creation.php - Standalone Character Creation Page
// ============================================================================
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Character Creation - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/main.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Character Creation Styles hier einfügen aus dem Artefakt */
    </style>
</head>
<body>
    <?php
    require_once __DIR__ . '/init.php';
    
    $app = new App();
    $auth = $app->getAuth();
    
    if(!$auth->isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    
    $playerId = $auth->getCurrentPlayerId();
    $player = $app->getPlayer()->getPlayerById($playerId);
    
    // Wenn Character bereits erstellt, zurück zu index
    if($player['character_created']) {
        header('Location: index.php');
        exit;
    }
    ?>
    
    <div class="creation-container">
        <!-- Character Creation HTML hier einfügen -->
    </div>
    
    <script>
        // Character Creation JavaScript hier
        // WICHTIG: Ajax-Call zu ajax/create_character.php anpassen
        
        function submitCharacter() {
            fetch('ajax/create_character.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(characterData)
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('✅ Charakter erfolgreich erstellt!');
                    window.location.href = 'index.php';
                } else {
                    alert('❌ Fehler: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Fehler beim Erstellen: ' + error);
            });
        }
        
        // Lade Rassen & Klassen via AJAX
        fetch('ajax/get_races.php')
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    races = data.races;
                    renderRaces();
                }
            });
        
        fetch('ajax/get_classes.php')
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    classes = data.classes;
                }
            });
    </script>
</body>
</html>