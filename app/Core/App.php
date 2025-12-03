<?php
namespace App\Core;

use App\Services\PlayerService;
use App\Services\ResourceService;
use App\Services\BuildingService;
use App\Services\AdminService;

class App {
    private SessionManager $session;
    private Logger $logger;

    private PlayerService $playerService;
    private ResourceService $resourceService;
    private BuildingService $buildingService;
    private AdminService $adminService;

    public function __construct(SessionManager $session, Logger $logger) {
        $this->session = $session;
        $this->logger = $logger;

        $this->playerService = new PlayerService($this);
        $this->resourceService = new ResourceService($this);
        $this->buildingService = new BuildingService($this);
        $this->adminService = new AdminService($this);
    }

    public function getSession(): SessionManager {
        return $this->session;
    }

    public function getLogger(): Logger {
        return $this->logger;
    }

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

    public function getAuth(): Auth {
        return new Auth($this);
    }
}
