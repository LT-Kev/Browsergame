<?php
// ============================================================================
// app/Core/Auth.php - COMPLETE WITH RATE LIMITING
// ============================================================================

namespace App\Core;

class Auth {
    private App $app;
    private Logger $logger;
    private RateLimiter $rateLimiter;
    
    public function __construct(App $app) {
        $this->app = $app;
        $this->logger = new Logger('auth');
        $this->rateLimiter = new RateLimiter($app->getDB());
    }
    
    /**
     * Prüft ob User eingeloggt ist
     */
    public function isLoggedIn(): bool {
        // Normale Session prüfen
        if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            return true;
        }
        
        // Remember-Token prüfen
        $playerId = $this->app->getRememberMe()->validateToken();
        if($playerId) {
            $player = $this->app->getPlayer()->getPlayerById($playerId);
            
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
        
        return false;
    }
    
    /**
     * Gibt aktuelle Player ID zurück
     */
    public function getCurrentPlayerId(): ?int {
        if(isset($_SESSION['player_id'])) {
            return (int)$_SESSION['player_id'];
        }
        return null;
    }
    
    /**
     * Login Funktion mit Rate Limiting
     */
    public function login(string $username, string $password, bool $rememberMe = false): bool {
        $ip = RateLimiter::getClientIp();
        
        // Rate Limit prüfen
        if(!$this->rateLimiter->checkLimit($ip, RateLimiter::TYPE_LOGIN, 
            RATE_LIMIT_MAX_ATTEMPTS, RATE_LIMIT_TIME_WINDOW)) {
            
            $remaining = $this->rateLimiter->getRemainingAttempts(
                $ip, 
                RateLimiter::TYPE_LOGIN, 
                RATE_LIMIT_MAX_ATTEMPTS, 
                RATE_LIMIT_TIME_WINDOW
            );
            
            $this->logger->warning("Login rate limit exceeded", [
                'ip' => $ip,
                'username_attempt' => $username,
                'reset_in' => $remaining['reset_at'] - time()
            ]);
            
            return false;
        }
        
        $player = $this->app->getPlayer()->getPlayerByUsername($username);
        
        if(!$player) {
            $this->rateLimiter->recordAttempt($ip, RateLimiter::TYPE_LOGIN);
            $this->logger->warning("Login failed: User not found", [
                'username' => $username, 
                'ip' => $ip
            ]);
            return false;
        }
        
        if(!password_verify($password, $player['password'])) {
            $this->rateLimiter->recordAttempt($ip, RateLimiter::TYPE_LOGIN);
            $this->logger->warning("Login failed: Invalid password", [
                'username' => $username, 
                'ip' => $ip
            ]);
            return false;
        }
        
        // Login erfolgreich - Rate Limit zurücksetzen
        $this->rateLimiter->resetIdentifier($ip, RateLimiter::TYPE_LOGIN);
        
        // Session setzen
        $_SESSION['player_id'] = $player['id'];
        $_SESSION['username'] = $player['username'];
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
        $_SESSION['login_ip'] = $ip;
        
        // Remember Me Token erstellen wenn gewünscht
        if($rememberMe) {
            $_SESSION['logged_via_remember'] = true;
            $this->app->getRememberMe()->createToken($player['id']);
        } else {
            $_SESSION['logged_via_remember'] = false;
        }
        
        // Update last_login
        $this->app->getPlayer()->updateLastLogin($player['id']);
        
        $this->logger->info("User logged in successfully", [
            'username' => $username, 
            'player_id' => $player['id'],
            'remember_me' => $rememberMe,
            'ip' => $ip
        ]);
        
        return true;
    }
    
    /**
     * Logout Funktion
     */
    public function logout(): bool {
        $username = $_SESSION['username'] ?? 'Unknown';
        $playerId = $_SESSION['player_id'] ?? null;
        
        // Remember-Token löschen
        $this->app->getRememberMe()->deleteToken();
        
        // Session löschen
        session_unset();
        session_destroy();
        
        $this->logger->info("User logged out", [
            'username' => $username,
            'player_id' => $playerId
        ]);
        
        return true;
    }
    
    /**
     * Registrierung mit Rate Limiting
     */
    public function register(string $username, string $email, string $password, string $passwordRepeat): array {
        $ip = RateLimiter::getClientIp();
        
        // Rate Limit prüfen
        if(!$this->rateLimiter->checkLimit($ip, RateLimiter::TYPE_REGISTRATION, 
            REGISTRATION_RATE_LIMIT, REGISTRATION_RATE_WINDOW)) {
            
            $remaining = $this->rateLimiter->getRemainingAttempts(
                $ip, 
                RateLimiter::TYPE_REGISTRATION, 
                REGISTRATION_RATE_LIMIT, 
                REGISTRATION_RATE_WINDOW
            );
            
            $this->logger->warning("Registration rate limit exceeded", [
                'ip' => $ip,
                'reset_in' => $remaining['reset_at'] - time()
            ]);
            
            return [
                'success' => false, 
                'message' => 'Zu viele Registrierungsversuche. Bitte warte einen Moment.'
            ];
        }
        
        // Validierungen
        if(empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Alle Felder müssen ausgefüllt sein'];
        }
        
        if($password !== $passwordRepeat) {
            $this->logger->warning("Registration failed: Password mismatch", [
                'username' => $username,
                'ip' => $ip
            ]);
            return ['success' => false, 'message' => 'Passwörter stimmen nicht überein'];
        }
        
        if(strlen($password) < 6) {
            return ['success' => false, 'message' => 'Passwort muss mindestens 6 Zeichen lang sein'];
        }
        
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Ungültige E-Mail-Adresse'];
        }
        
        if($this->app->getPlayer()->getPlayerByUsername($username)) {
            $this->rateLimiter->recordAttempt($ip, RateLimiter::TYPE_REGISTRATION);
            $this->logger->warning("Registration failed: Username already exists", [
                'username' => $username,
                'ip' => $ip
            ]);
            return ['success' => false, 'message' => 'Username bereits vergeben'];
        }
        
        if($this->app->getPlayer()->getPlayerByEmail($email)) {
            $this->rateLimiter->recordAttempt($ip, RateLimiter::TYPE_REGISTRATION);
            $this->logger->warning("Registration failed: Email already exists", [
                'email' => $email,
                'ip' => $ip
            ]);
            return ['success' => false, 'message' => 'E-Mail bereits registriert'];
        }
        
        // Spieler erstellen
        $playerId = $this->app->getPlayer()->createPlayerWithBuildings($username, $email, $password);
        
        if($playerId) {
            // Erfolgreiche Registrierung - Rate Limit zurücksetzen
            $this->rateLimiter->resetIdentifier($ip, RateLimiter::TYPE_REGISTRATION);
            
            $this->logger->info("New user registered", [
                'username' => $username, 
                'email' => $email, 
                'player_id' => $playerId, 
                'ip' => $ip
            ]);
            
            return [
                'success' => true, 
                'message' => 'Registrierung erfolgreich', 
                'player_id' => $playerId
            ];
        }
        
        $this->logger->error("Registration failed: Database error", [
            'username' => $username, 
            'email' => $email,
            'ip' => $ip
        ]);
        
        return ['success' => false, 'message' => 'Fehler bei der Registrierung'];
    }
    
    /**
     * Fordert Login an, sonst Redirect
     */
    public function requireLogin(string $redirectUrl = '/login.php'): void {
        if(!$this->isLoggedIn()) {
            $this->logger->warning("Unauthorized access attempt", [
                'requested_url' => $_SERVER['REQUEST_URI'] ?? '',
                'ip' => RateLimiter::getClientIp()
            ]);
            
            header("Location: {$redirectUrl}");
            exit;
        }
    }
    
    /**
     * Prüft ob User Admin ist
     */
    public function isAdmin(): bool {
        if(!$this->isLoggedIn()) {
            return false;
        }
        
        $playerId = $this->getCurrentPlayerId();
        if(!$playerId) {
            return false;
        }
        
        return $this->app->getAdmin()->isAdmin($playerId);
    }
    
    /**
     * Prüft ob User ein bestimmtes Admin-Level hat
     */
    public function hasAdminLevel(int $requiredLevel): bool {
        if(!$this->isLoggedIn()) {
            return false;
        }
        
        $playerId = $this->getCurrentPlayerId();
        if(!$playerId) {
            return false;
        }
        
        $adminLevel = $this->app->getAdmin()->getAdminLevel($playerId);
        return $adminLevel >= $requiredLevel;
    }
    
    /**
     * Fordert Admin-Rechte an, sonst Redirect
     */
    public function requireAdmin(int $minLevel = 1, string $redirectUrl = '/index.php'): void {
        if(!$this->isLoggedIn()) {
            $this->logger->warning("Unauthorized admin access attempt (not logged in)", [
                'requested_url' => $_SERVER['REQUEST_URI'] ?? '',
                'ip' => RateLimiter::getClientIp()
            ]);
            
            header("Location: /login.php");
            exit;
        }
        
        if(!$this->hasAdminLevel($minLevel)) {
            $playerId = $this->getCurrentPlayerId();
            $currentLevel = $this->app->getAdmin()->getAdminLevel($playerId);
            
            $this->logger->warning("Unauthorized admin access attempt (insufficient level)", [
                'player_id' => $playerId,
                'current_level' => $currentLevel,
                'required_level' => $minLevel,
                'requested_url' => $_SERVER['REQUEST_URI'] ?? '',
                'ip' => RateLimiter::getClientIp()
            ]);
            
            header("Location: {$redirectUrl}");
            exit;
        }
    }
    
    /**
     * Session-Timeout prüfen
     */
    public function checkSessionTimeout(): bool {
        if(!isset($_SESSION['last_activity'])) {
            return false;
        }
        
        $inactive = time() - $_SESSION['last_activity'];
        
        if($inactive > SESSION_LIFETIME) {
            $this->logger->info("Session timeout", [
                'username' => $_SESSION['username'] ?? 'Unknown',
                'inactive_seconds' => $inactive
            ]);
            
            $this->logout();
            return true;
        }
        
        $_SESSION['last_activity'] = time();
        return false;
    }
    
    /**
     * Session Hijacking Protection
     */
    public function validateSession(): bool {
        // User Agent prüfen
        if(isset($_SESSION['user_agent'])) {
            $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            if($_SESSION['user_agent'] !== $currentUserAgent) {
                $this->logger->warning("Session hijacking attempt detected (User Agent mismatch)", [
                    'username' => $_SESSION['username'] ?? 'Unknown',
                    'session_ua' => $_SESSION['user_agent'],
                    'current_ua' => $currentUserAgent,
                    'ip' => RateLimiter::getClientIp()
                ]);
                
                $this->logout();
                return false;
            }
        } else {
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }
        
        // IP-Adresse prüfen (optional, kann bei mobilen Nutzern problematisch sein)
        if(isset($_SESSION['login_ip']) && defined('SESSION_IP_CHECK') && SESSION_IP_CHECK) {
            $currentIp = RateLimiter::getClientIp();
            
            if($_SESSION['login_ip'] !== $currentIp) {
                $this->logger->warning("Session hijacking attempt detected (IP mismatch)", [
                    'username' => $_SESSION['username'] ?? 'Unknown',
                    'session_ip' => $_SESSION['login_ip'],
                    'current_ip' => $currentIp
                ]);
                
                // Optional: Logout oder nur warnen
                // $this->logout();
                // return false;
            }
        }
        
        return true;
    }
    
    /**
     * Password Reset mit Rate Limiting
     */
    public function requestPasswordReset(string $email): array {
        $ip = RateLimiter::getClientIp();
        
        // Rate Limit prüfen
        if(!$this->rateLimiter->checkLimit($ip, RateLimiter::TYPE_PASSWORD_RESET, 
            PASSWORD_RESET_RATE_LIMIT, PASSWORD_RESET_RATE_WINDOW)) {
            
            $remaining = $this->rateLimiter->getRemainingAttempts(
                $ip, 
                RateLimiter::TYPE_PASSWORD_RESET, 
                PASSWORD_RESET_RATE_LIMIT, 
                PASSWORD_RESET_RATE_WINDOW
            );
            
            $this->logger->warning("Password reset rate limit exceeded", [
                'ip' => $ip,
                'email' => $email,
                'reset_in' => $remaining['reset_at'] - time()
            ]);
            
            return [
                'success' => false, 
                'message' => 'Zu viele Anfragen. Bitte warte einen Moment.'
            ];
        }
        
        $player = $this->app->getPlayer()->getPlayerByEmail($email);
        
        if(!$player) {
            // Aus Sicherheitsgründen trotzdem Success zurückgeben
            // (verhindert User-Enumeration)
            $this->rateLimiter->recordAttempt($ip, RateLimiter::TYPE_PASSWORD_RESET);
            
            $this->logger->info("Password reset requested for non-existent email", [
                'email' => $email,
                'ip' => $ip
            ]);
            
            return [
                'success' => true, 
                'message' => 'Falls die E-Mail existiert, wurde ein Reset-Link gesendet.'
            ];
        }
        
        // TODO: Token generieren und E-Mail senden
        // Hier würde die tatsächliche Reset-Logik kommen
        
        $this->logger->info("Password reset requested", [
            'player_id' => $player['id'],
            'email' => $email,
            'ip' => $ip
        ]);
        
        return [
            'success' => true, 
            'message' => 'Falls die E-Mail existiert, wurde ein Reset-Link gesendet.'
        ];
    }
}