<?php
require_once __DIR__ . '/init.php';


$app = new App();
$auth = $app->getAuth();

$auth->logout();

header('Location: login.php');
exit;
?>