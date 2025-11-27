<?php
class Auth {
    private $db;
    private $player;
    private $logger;
    
    public function __construct($database, $playerClass) {
        $this->db = $database;
        $this->player = $playerClass;
        $this->logger = new Logger('auth');
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function login($username, $password) {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $playerData = $this->player->login($username, $password);
        
        if($playerData) {
            $_SESSION['player_id'] = $playerData['id'];
            $_SESSION['username'] = $playerData['username'];
            $_SESSION['logged_in'] = true;
            
            $this->logger->loginAttempt($username, true, $ip);
            $this->logger->info("User $username logged in successfully");
            
            return true;
        }
        
        $this->logger->loginAttempt($username, false, $ip);
        $this->logger->warning("Failed login attempt for user: $username from IP: $ip");
        
        return false;
    }
    
    public function logout() {
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown';
        
        session_unset();
        session_destroy();
        
        $this->logger->info("User $username logged out");
        
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function getCurrentPlayerId() {
        return isset($_SESSION['player_id']) ? $_SESSION['player_id'] : null;
    }
    
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
        
    // $playerId = $this->player->createPlayer($username, $email, $password);
    $playerId = $this->player->createPlayerWithBuildings($username, $email, $password);
        
        if($playerId) {
            $this->logger->info("New user registered: $username (ID: $playerId) from IP: $ip");
            return array('success' => true, 'message' => 'Registrierung erfolgreich', 'player_id' => $playerId);
        }
        
        $this->logger->error("Registration failed: Database error", array('username' => $username, 'email' => $email));
        return array('success' => false, 'message' => 'Fehler bei der Registrierung');
    }
}
?>