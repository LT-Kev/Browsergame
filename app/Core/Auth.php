<?php
namespace App\Core;

class Auth {
    private App $app;

    public function __construct(App $app) {
        $this->app = $app;
    }

    public function isLoggedIn(): bool {
        return $this->app->getSession()->get('logged_in', false) === true;
    }

    public function getCurrentPlayerId(): ?int {
        return $this->app->getSession()->get('player_id');
    }

    public function login(int $playerId): void {
        $this->app->getSession()->set('logged_in', true);
        $this->app->getSession()->set('player_id', $playerId);
        $this->app->getSession()->set('last_activity', time());
    }

    public function logout(): void {
        $this->app->getSession()->destroy();
    }
}
