<?php
class App {
    private $db;
    private $player;
    private $auth;
    private $inventory;
    private $quest;
    private $combat;
    private $resources;
    private $building;
    private $admin;
    private $logger;
    private $rememberMe;
    
    public function __construct() {
        $this->initLogger();
        $this->initDatabase();
        $this->initClasses();
        
        if(LOG_ENABLED) {
            $this->logger->info('App initialized');
        }
    }
    
    private function initLogger() {
        $this->logger = new Logger('general');
    }
    
    private function initDatabase() {
        try {
            $this->db = Database::getInstance(); // ✅ Use singleton
        } catch(Exception $e) {
            $this->logger->critical('Database connection failed', array('error' => $e->getMessage()));
            throw $e;
        }
    }
    
    private function initClasses() {
        $this->player = new Player($this->db);
        $this->auth = new Auth($this->db, $this->player);
        $this->inventory = new Inventory($this->db);
        $this->quest = new Quest($this->db);
        $this->combat = new Combat($this->db, $this->player);
        $this->resources = new Resources($this->db, $this->player);
        $this->building = new Building($this->db, $this->player);
        $this->admin = new Admin($this->db, $this->player);
        $this->rememberMe = new RememberMe($this->db);
    }
    
    public function getDB() {
        return $this->db;
    }
    
    public function getPlayer() {
        return $this->player;
    }
    
    public function getAuth() {
        return $this->auth;
    }
    
    public function getInventory() {
        return $this->inventory;
    }
    
    public function getQuest() {
        return $this->quest;
    }
    
    public function getCombat() {
        return $this->combat;
    }
    
    public function getResources() {
        return $this->resources;
    }
    
    public function getBuilding() {
        return $this->building;
    }
    
    public function getAdmin() {
        return $this->admin;
    }
    
    public function getLogger() {
        return $this->logger;
    }

    public function getRememberMe() {
        return $this->rememberMe;
    }
}
?>