<?php
namespace App\Controllers;

use App\Core\App;

class IndexController {
    private App $app;

    public function __construct(App $app) {
        $this->app = $app;
    }

    public function render(): void {
        $auth = $this->app->getAuth();
        if(!$auth->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }

        $playerId = $auth->getCurrentPlayerId();
        $player = $this->app->getPlayer()->getPlayerById($playerId);

        include __DIR__ . '/../../pages/overview.php';
    }
}
