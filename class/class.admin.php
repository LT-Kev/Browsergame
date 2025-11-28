<?php
//class/class.admin.php
class Admin {
    private $db;
    private $player;
    private $logger;
    
    public function __construct($database, $playerClass) {
        $this->db = $database;
        $this->player = $playerClass;
        $this->logger = new Logger('admin');
    }
    
    // Prüfen ob Spieler Admin ist
    public function isAdmin($playerId) {
        $player = $this->player->getPlayerById($playerId);
        return $player && $player['admin_level'] > 0;
    }
    
    // Admin-Level abrufen
    public function getAdminLevel($playerId) {
        $player = $this->player->getPlayerById($playerId);
        return $player ? (int)$player['admin_level'] : 0;
    }
    
    // Prüfen ob Spieler bestimmte Berechtigung hat
    public function hasPermission($playerId, $permissionKey) {
        $adminLevel = $this->getAdminLevel($playerId);
        
        if($adminLevel == 0) {
            return false;
        }
        
        // Level 10 hat immer alle Rechte
        if($adminLevel == 10) {
            return true;
        }
        
        $sql = "SELECT * FROM admin_permissions 
                WHERE admin_level = :admin_level AND permission_key = :permission_key 
                LIMIT 1";
        
        $permission = $this->db->selectOne($sql, array(
            ':admin_level' => $adminLevel,
            ':permission_key' => $permissionKey
        ));
        
        return $permission !== false;
    }
    
    // Admin-Level Name und Farbe abrufen
    public function getAdminLevelInfo($level) {
        $sql = "SELECT * FROM admin_levels WHERE level = :level LIMIT 1";
        return $this->db->selectOne($sql, array(':level' => $level));
    }
    
    // Alle Admin-Levels abrufen
    public function getAllAdminLevels() {
        $sql = "SELECT * FROM admin_levels ORDER BY level ASC";
        return $this->db->select($sql);
    }
    
    // Berechtigungen für ein Level abrufen
    public function getPermissionsForLevel($level) {
        $sql = "SELECT * FROM admin_permissions WHERE admin_level = :level ORDER BY permission_name";
        return $this->db->select($sql, array(':level' => $level));
    }
    
    // Alle Admins abrufen
    public function getAllAdmins() {
        $sql = "SELECT p.id, p.username, p.email, p.admin_level, p.last_login, al.name as level_name, al.color as level_color
                FROM players p
                LEFT JOIN admin_levels al ON p.admin_level = al.level
                WHERE p.admin_level > 0
                ORDER BY p.admin_level DESC, p.username ASC";
        
        return $this->db->select($sql);
    }
    
    // Spieler zum Admin machen
    public function setAdminLevel($adminId, $targetPlayerId, $newLevel) {
        $adminLevel = $this->getAdminLevel($adminId);
        $targetPlayer = $this->player->getPlayerById($targetPlayerId);
        
        if(!$targetPlayer) {
            return array('success' => false, 'message' => 'Spieler nicht gefunden');
        }
        
        // Level 10 kann alles
        if($adminLevel < 10) {
            // Level 7 kann bis Level 6 ernennen
            if($adminLevel == 7 && $newLevel > 6) {
                return array('success' => false, 'message' => 'Du kannst nur Admins bis Level 6 ernennen');
            }
            
            // Man kann nur Levels unter dem eigenen Level vergeben
            if($newLevel >= $adminLevel) {
                return array('success' => false, 'message' => 'Du kannst kein Level >= deinem eigenen Level vergeben');
            }
        }
        
        $sql = "UPDATE players SET admin_level = :level WHERE id = :id";
        $result = $this->db->update($sql, array(':level' => $newLevel, ':id' => $targetPlayerId));
        
        if($result !== false) {
            $this->logger->info("Admin level changed", array(
                'admin_id' => $adminId,
                'target_player_id' => $targetPlayerId,
                'target_username' => $targetPlayer['username'],
                'new_level' => $newLevel
            ));
            
            return array('success' => true, 'message' => 'Admin-Level erfolgreich geändert');
        }
        
        return array('success' => false, 'message' => 'Fehler beim Ändern des Admin-Levels');
    }
    
    // Spieler-Ressourcen ändern (für GMs)
    public function editPlayerResources($adminId, $targetPlayerId, $resources) {
        if(!$this->hasPermission($adminId, 'edit_players')) {
            return array('success' => false, 'message' => 'Keine Berechtigung');
        }
        
        $result = $this->player->updateResources($targetPlayerId, $resources);
        
        if($result) {
            $this->logger->info("Player resources edited by admin", array(
                'admin_id' => $adminId,
                'target_player_id' => $targetPlayerId,
                'resources' => $resources
            ));
            
            return array('success' => true, 'message' => 'Ressourcen erfolgreich geändert');
        }
        
        return array('success' => false, 'message' => 'Fehler beim Ändern der Ressourcen');
    }
}
?>