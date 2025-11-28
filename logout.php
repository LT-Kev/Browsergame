<<<<<<< HEAD
<?php
require_once __DIR__ . '/init.php';


$app = new App();
$auth = $app->getAuth();

$auth->logout();

header('Location: login.php');
exit;
=======
<?php
require_once __DIR__ . '/init.php';


$app = new App();
$auth = $app->getAuth();

$auth->logout();

header('Location: login.php');
exit;
>>>>>>> 971ab47689bd561bd08c6e4d77cea7f516414d66
?>