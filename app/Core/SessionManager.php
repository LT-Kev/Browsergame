<?php
namespace App\Core;

class SessionManager {

    public function start(): void {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
            if(!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
                $_SESSION['created'] = time();
            }

            // Hijacking protection
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        }
    }

    public function validate(): void {
        if($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '') ||
           $_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
            session_unset();
            session_destroy();
            session_start();
        }
    }

    public function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed {
        return $_SESSION[$key] ?? $default;
    }

    public function destroy(): void {
        session_unset();
        session_destroy();
    }
}
