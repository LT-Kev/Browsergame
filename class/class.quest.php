<?php
class Quest {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // Alle verfügbaren Quests
    public function getAllQuests() {
        $sql = "SELECT * FROM quests ORDER BY required_level ASC";
        return $this->db->select($sql);
    }
    
    // Quest nach ID
    public function getQuestById($questId) {
        $sql = "SELECT * FROM quests WHERE id = :id LIMIT 1";
        return $this->db->selectOne($sql, array(':id' => $questId));
    }
    
    // Spieler Quests
    public function getPlayerQuests($playerId, $status = null) {
        if($status) {
            $sql = "SELECT pq.*, q.title, q.description, q.reward_gold, q.reward_exp 
                    FROM player_quests pq 
                    JOIN quests q ON pq.quest_id = q.id 
                    WHERE pq.player_id = :player_id AND pq.status = :status 
                    ORDER BY pq.started_at DESC";
            return $this->db->select($sql, array(':player_id' => $playerId, ':status' => $status));
        } else {
            $sql = "SELECT pq.*, q.title, q.description, q.reward_gold, q.reward_exp 
                    FROM player_quests pq 
                    JOIN quests q ON pq.quest_id = q.id 
                    WHERE pq.player_id = :player_id 
                    ORDER BY pq.started_at DESC";
            return $this->db->select($sql, array(':player_id' => $playerId));
        }
    }
    
    // Quest starten
    public function startQuest($playerId, $questId) {
        // Prüfen ob Quest bereits aktiv
        $sql = "SELECT * FROM player_quests 
                WHERE player_id = :player_id AND quest_id = :quest_id AND status = 'active' 
                LIMIT 1";
        $existing = $this->db->selectOne($sql, array(':player_id' => $playerId, ':quest_id' => $questId));
        
        if($existing) {
            return array('success' => false, 'message' => 'Quest bereits aktiv');
        }
        
        $sql = "INSERT INTO player_quests (player_id, quest_id, status, started_at) 
                VALUES (:player_id, :quest_id, 'active', NOW())";
        
        $result = $this->db->insert($sql, array(':player_id' => $playerId, ':quest_id' => $questId));
        
        if($result) {
            return array('success' => true, 'message' => 'Quest gestartet');
        }
        
        return array('success' => false, 'message' => 'Fehler beim Starten der Quest');
    }
    
    // Quest abschließen
    public function completeQuest($playerId, $questId) {
        $sql = "UPDATE player_quests 
                SET status = 'completed', completed_at = NOW() 
                WHERE player_id = :player_id AND quest_id = :quest_id AND status = 'active'";
        
        $result = $this->db->update($sql, array(':player_id' => $playerId, ':quest_id' => $questId));
        
        if($result) {
            // Belohnung geben
            $quest = $this->getQuestById($questId);
            return array(
                'success' => true, 
                'message' => 'Quest abgeschlossen',
                'reward_gold' => $quest['reward_gold'],
                'reward_exp' => $quest['reward_exp']
            );
        }
        
        return array('success' => false, 'message' => 'Fehler beim Abschließen der Quest');
    }
}
?>