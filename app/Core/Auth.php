<?php
// app/Core/Auth.php
namespace App\Core;

class Auth {
    private App $app;
    private Logger $logger;
    
    public function __construct(App $app) {
        $this->app = $app;
        $this->logger = new Logger('auth');
    }
    
    public function isLoggedIn(): bool {
        // Normale Session prüfen
        if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            return true;
        }
        
        // Remember-Token prüfen
        $playerId = $this->app->getRememberMe()->validateToken();
        if($playerId) {
            $player = $this->app->getPlayer()->getPlayerById($playerId);
            $this->logger->info("Remember-Me token valid, auto-login", ['player_id'=>$playerId]);
            if($player) {
                $_SESSION['player_id'] = $player['id'];
                $_SESSION['username'] = $player['username'];
                $_SESSION['logged_in'] = true;
                $_SESSION['logged_via_remember'] = true;
                $_SESSION['last_activity'] = time();
                
                $this->logger->info('Auto-login via remember token', ['player_id' => $playerId]);
                return true;
            }
        }
        else{
            $this->logger->info('Remember-Me token invalid or expired', ['cookie'=>$_COOKIE['remember_token'] ?? null]);
        }
        
        return false;
    }
    
    public function getCurrentPlayerId(): ?int {
        if(isset($_SESSION['player_id'])) {
            return (int)$_SESSION['player_id'];
        }
        return null;
    }
    
    public function login(string $username, string $password, bool $rememberMe = false): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $player = $this->app->getPlayer()->getPlayerByUsername($username);
        
        if(!$player) {
            $this->logger->warning("Login failed: User not found", ['username' => $username, 'ip' => $ip]);
            return false;
        }
        
        if(!password_verify($password, $player['password'])) {
            $this->logger->warning("Login failed: Invalid password", ['username' => $username, 'ip' => $ip]);
            return false;
        }
        
        // Session setzen
        $_SESSION['player_id'] = $player['id'];
        $_SESSION['username'] = $player['username'];
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
        
        // Remember Me Token erstellen wenn gewünscht
        if($rememberMe) {
            $_SESSION['logged_via_remember'] = true;
            $this->app->getRememberMe()->createToken($player['id']);
        } else {
            $_SESSION['logged_via_remember'] = false;
        }
        
        // Update last_login
        $this->app->getPlayer()->updateLastLogin($player['id']);
        
        $this->logger->info("User logged in successfully", ['username' => $username, 'remember_me' => $rememberMe]);
        return true;
    }
    
    public function logout(): bool {
        $username = $_SESSION['username'] ?? 'Unknown';
        
        // Remember-Token löschen
        $this->app->getRememberMe()->deleteToken();
        
        // Session löschen
        session_unset();
        session_destroy();
        
        $this->logger->info("User logged out", ['username' => $username]);
        return true;
    }
    
    public function register(string $username, string $email, string $password, string $passwordRepeat): array {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        
        // Validierungen
        if(empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Alle Felder müssen ausgefüllt sein'];
        }
        
        if($password !== $passwordRepeat) {
            $this->logger->warning("Registration failed: Password mismatch", ['username' => $username]);
            return ['success' => false, 'message' => 'Passwörter stimmen nicht überein'];
        }
        
        if(strlen($password) < 6) {
            return ['success' => false, 'message' => 'Passwort muss mindestens 6 Zeichen lang sein'];
        }
        
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Ungültige E-Mail-Adresse'];
        }
        
        if($this->app->getPlayer()->getPlayerByUsername($username)) {
            $this->logger->warning("Registration failed: Username already exists", ['username' => $username]);
            return ['success' => false, 'message' => 'Username bereits vergeben'];
        }
        
        if($this->app->getPlayer()->getPlayerByEmail($email)) {
            $this->logger->warning("Registration failed: Email already exists", ['email' => $email]);
            return ['success' => false, 'message' => 'E-Mail bereits registriert'];
        }
        
        // Spieler erstellen
        $playerId = $this->app->getPlayer()->createPlayerWithBuildings($username, $email, $password);
        
        if($playerId) {
            $this->logger->info("New user registered", ['username' => $username, 'email' => $email, 'player_id' => $playerId, 'ip' => $ip]);
            return ['success' => true, 'message' => 'Registrierung erfolgreich', 'player_id' => $playerId];
        }
        
        $this->logger->error("Registration failed: Database error", ['username' => $username, 'email' => $email]);
        return ['success' => false, 'message' => 'Fehler bei der Registrierung'];
    }
    
    public function requireLogin(string $redirectUrl = '/login.php'): void {
        if(!$this->isLoggedIn()) {
            $this->logger->warning("Unauthorized access attempt", [
                'requested_url' => $_SERVER['REQUEST_URI'] ?? '',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            header("Location: {$redirectUrl}");
            exit;
        }
    }
}