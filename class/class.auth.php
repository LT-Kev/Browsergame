<?php
class Auth {
    private $db;
    private $player;
    private $logger;
    private $rememberMe;
    
    public function __construct($database, $playerClass) {
        $this->db = $database;
        $this->player = $playerClass;
        $this->logger = new Logger('auth');
        $this->rememberMe = new RememberMe($database);
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Login mit optional Remember Me
     * 
     * @param string $username Username
     * @param string $password Password
     * @param bool $rememberMe Remember me checkbox
     * @return bool Success
     */
    public function login($username, $password, $rememberMe = false) {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $playerData = $this->player->login($username, $password);
        
        if($playerData) {
            // Session setzen
            $_SESSION['player_id'] = $playerData['id'];
            $_SESSION['username'] = $playerData['username'];
            $_SESSION['logged_in'] = true;
            
            // Remember Me Token erstellen wenn gewünscht
            if($rememberMe) {
                $this->rememberMe->createToken($playerData['id']);
            }
            
            $this->logger->loginAttempt($username, true, $ip);
            $this->logger->info("User $username logged in successfully", [
                'remember_me' => $rememberMe
            ]);
            
            return true;
        }
        
        $this->logger->loginAttempt($username, false, $ip);
        $this->logger->warning("Failed login attempt for user: $username from IP: $ip");
        
        return false;
    }
    
    /**
     * Logout - löscht Session und Remember-Token
     * 
     * @return bool Success
     */
    public function logout() {
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown';
        
        // Remember-Token löschen
        $this->rememberMe->deleteToken();
        
        // Session löschen
        session_unset();
        session_destroy();
        
        $this->logger->info("User $username logged out");
        
        return true;
    }
    
    /**
     * Prüft ob User eingeloggt ist (inkl. Remember-Token)
     * 
     * @return bool Is logged in
     */
    public function isLoggedIn() {
        // Normale Session prüfen
        if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            return true;
        }
        
        // Remember-Token prüfen
        $playerId = $this->rememberMe->validateToken();
        
        if($playerId) {
            // Auto-Login durch Remember-Token
            $playerData = $this->player->getPlayerById($playerId);
            
            if($playerData) {
                $_SESSION['player_id'] = $playerData['id'];
                $_SESSION['username'] = $playerData['username'];
                $_SESSION['logged_in'] = true;
                
                $this->logger->info("Auto-login via remember token", [
                    'player_id' => $playerId,
                    'username' => $playerData['username']
                ]);
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Hole aktuelle Player ID
     * 
     * @return int|null Player ID or null
     */
    public function getCurrentPlayerId() {
        // Erst Session prüfen
        if(isset($_SESSION['player_id'])) {
            return $_SESSION['player_id'];
        }
        
        // Dann Remember-Token
        if($this->isLoggedIn()) {
            return $_SESSION['player_id'] ?? null;
        }
        
        return null;
    }
    
    /**
     * Login required - redirect wenn nicht eingeloggt
     * 
     * @param string $redirectUrl Redirect URL
     * @return void
     */
    public function requireLogin($redirectUrl = 'login.php') {
        if(!$this->isLoggedIn()) {
            $this->logger->warning("Unauthorized access attempt", array(
                'requested_url' => $_SERVER['REQUEST_URI'],
                'ip' => $_SERVER['REMOTE_ADDR']
            ));
            header("Location: " . $redirectUrl);
            exit;
        }
    }
    
    /**
     * Registrierung (unverändert)
     */
    public function register($username, $email, $password, $passwordRepeat) {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if(empty($username) || empty($email) || empty($password)) {
            $this->logger->warning("Registration failed: Empty fields", array('username' => $username, 'email' => $email));
            return array('success' => false, 'message' => 'Alle Felder müssen ausgefüllt sein');
        }
        
        if($password !== $passwordRepeat) {
            $this->logger->warning("Registration failed: Password mismatch", array('username' => $username));
            return array('success' => false, 'message' => 'Passwörter stimmen nicht überein');
        }
        
        if(strlen($password) < 6) {
            $this->logger->warning("Registration failed: Password too short", array('username' => $username));
            return array('success' => false, 'message' => 'Passwort muss mindestens 6 Zeichen lang sein');
        }
        
        if($this->player->getPlayerByUsername($username)) {
            $this->logger->warning("Registration failed: Username already exists", array('username' => $username));
            return array('success' => false, 'message' => 'Username bereits vergeben');
        }
        
        if($this->player->getPlayerByEmail($email)) {
            $this->logger->warning("Registration failed: Email already exists", array('email' => $email));
            return array('success' => false, 'message' => 'Email bereits registriert');
        }
        
        $playerId = $this->player->createPlayerWithBuildings($username, $email, $password);
        
        if($playerId) {
            $this->logger->info("New user registered: $username (ID: $playerId) from IP: $ip");
            return array('success' => true, 'message' => 'Registrierung erfolgreich', 'player_id' => $playerId);
        }
        
        $this->logger->error("Registration failed: Database error", array('username' => $username, 'email' => $email));
        return array('success' => false, 'message' => 'Fehler bei der Registrierung');
    }
    
    /**
     * Gibt RememberMe-Instanz zurück (für Settings-Page)
     * 
     * @return RememberMe
     */
    public function getRememberMe() {
        return $this->rememberMe;
    }
}