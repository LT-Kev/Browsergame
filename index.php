<<<<<<< HEAD
<?php
require_once __DIR__ . '/init.php';


$app = new App();

// Login-Check
$auth = $app->getAuth();
if(!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$playerId = $auth->getCurrentPlayerId();

// Ressourcen und fertige Upgrades automatisch updaten
$app->getResources()->updateResources($playerId);
$app->getBuilding()->checkFinishedUpgrades($playerId);

// Spielerdaten für Templates laden
$playerData = $app->getPlayer()->getPlayerById($playerId);

// Admin-Level prüfen
$isAdmin = $app->getAdmin()->isAdmin($playerId);
$adminLevel = $app->getAdmin()->getAdminLevel($playerId);
$adminLevelInfo = $isAdmin ? $app->getAdmin()->getAdminLevelInfo($adminLevel) : null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.iconify.design/2/2.2.1/iconify.min.js"></script>
</head>
<body>
    <?php include 'template/topbar.php'; ?>
    
    <div class="content-wrapper">
        <?php include 'template/header.php'; ?>
        
        <div class="main-container">
            <?php include 'template/left.php'; ?>
            <?php include 'template/center.php'; ?>
            <?php include 'template/right.php'; ?>
        </div>
    </div>
    
    <?php include 'template/footer.php'; ?>
    
    <script type="module" src="js/main.js"></script>
</body>
=======
<?php
require_once __DIR__ . '/init.php';


$app = new App();

// Login-Check
$auth = $app->getAuth();
if(!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$playerId = $auth->getCurrentPlayerId();

// Ressourcen und fertige Upgrades automatisch updaten
$app->getResources()->updateResources($playerId);
$app->getBuilding()->checkFinishedUpgrades($playerId);

// Spielerdaten für Templates laden
$playerData = $app->getPlayer()->getPlayerById($playerId);

// Admin-Level prüfen
$isAdmin = $app->getAdmin()->isAdmin($playerId);
$adminLevel = $app->getAdmin()->getAdminLevel($playerId);
$adminLevelInfo = $isAdmin ? $app->getAdmin()->getAdminLevelInfo($adminLevel) : null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.iconify.design/2/2.2.1/iconify.min.js"></script>
</head>
<body>
    <?php include 'template/topbar.php'; ?>
    
    <div class="content-wrapper">
        <?php include 'template/header.php'; ?>
        
        <div class="main-container">
            <?php include 'template/left.php'; ?>
            <?php include 'template/center.php'; ?>
            <?php include 'template/right.php'; ?>
        </div>
    </div>
    
    <?php include 'template/footer.php'; ?>
    
    <script type="module" src="js/main.js"></script>
</body>
>>>>>>> 971ab47689bd561bd08c6e4d77cea7f516414d66
</html>