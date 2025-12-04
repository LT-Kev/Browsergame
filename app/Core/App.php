<?php
// ============================================================================
// app/Core/App.php - COMPLETE VERSION
// ============================================================================

namespace App\Core;

use App\Services\PlayerService;
use App\Services\ResourceService;
use App\Services\BuildingService;
use App\Services\AdminService;
use App\Services\CombatService;
use App\Services\InventoryService;
use App\Services\QuestService;
use App\Services\RememberMeService;
use App\Services\RaceService;
use App\Services\RPGClassService;
use App\Services\StatsService;

class App {
    private static ?App $instance = null;
    
    private Database $db;
    private SessionManager $session;
    private Logger $logger;

    // Services
    private PlayerService $playerService;
    private ResourceService $resourceService;
    private BuildingService $buildingService;
    private AdminService $adminService;
    private CombatService $combatService;
    private InventoryService $inventoryService;
    private QuestService $questService;
    private RememberMeService $rememberMeService;
    private RaceService $raceService;
    private RPGClassService $rpgClassService;
    private StatsService $statsService;
    
    private Auth $auth;

    private function __construct() {
        $this->initCore();
        $this->initServices();
    }
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initCore(): void {
        // Database mit Config
        $config = [
            'host' => DB_HOST,
            'dbname' => DB_NAME,
            'user' => DB_USER,
            'password' => DB_PASS
        ];
        $this->db = Database::getInstance($config);
        
        // Session Manager
        $this->session = new SessionManager();
        $this->session->start();
        
        // Logger
        $this->logger = new Logger('app');
        
        if(LOG_ENABLED) {
            $this->logger->info('App Core initialized');
        }
    }
    
    private function initServices(): void {
        // Player Service (benötigt von vielen anderen)
        $this->playerService = new PlayerService($this->db, $this->logger);
        
        // Core Services
        $this->resourceService = new ResourceService($this->db, $this->playerService);
        $this->buildingService = new BuildingService($this->db, $this->playerService);
        $this->adminService = new AdminService($this->db, $this->playerService);
        
        // Extended Services
        $this->combatService = new CombatService($this->db, $this->playerService);
        $this->inventoryService = new InventoryService($this->db);
        $this->questService = new QuestService($this->db);
        $this->rememberMeService = new RememberMeService($this->db);
        
        // RPG System Services
        $this->raceService = new RaceService($this->db);
        $this->rpgClassService = new RPGClassService($this->db);
        $this->statsService = new StatsService($this->db, $this->playerService);
        
        // Auth (needs other services)
        $this->auth = new Auth($this);
        
        if(LOG_ENABLED) {
            $this->logger->info('All Services initialized');
        }
    }

    // ========================================================================
    // GETTERS - Core
    // ========================================================================
    
    public function getDB(): Database {
        return $this->db;
    }

    public function getSession(): SessionManager {
        return $this->session;
    }

    public function getLogger(): Logger {
        return $this->logger;
    }

    public function getAuth(): Auth {
        return $this->auth;
    }

    // ========================================================================
    // GETTERS - Services
    // ========================================================================

    public function getPlayer(): PlayerService {
        return $this->playerService;
    }

    public function getResources(): ResourceService {
        return $this->resourceService;
    }

    public function getBuilding(): BuildingService {
        return $this->buildingService;
    }

    public function getAdmin(): AdminService {
        return $this->adminService;
    }

    public function getCombat(): CombatService {
        return $this->combatService;
    }

    public function getInventory(): InventoryService {
        return $this->inventoryService;
    }

    public function getQuest(): QuestService {
        return $this->questService;
    }

    public function getRememberMe(): RememberMeService {
        return $this->rememberMeService;
    }

    public function getRace(): RaceService {
        return $this->raceService;
    }

    public function getRPGClass(): RPGClassService {
        return $this->rpgClassService;
    }

    public function getStats(): StatsService {
        return $this->statsService;
    }
    
    // ========================================================================
    // SINGLETON PROTECTION
    // ========================================================================
    
    private function __clone() {}
    
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}