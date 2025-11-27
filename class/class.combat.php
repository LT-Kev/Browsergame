<?php
class Combat {
    private $db;
    private $player;
    
    public function __construct($database, $playerClass) {
        $this->db = $database;
        $this->player = $playerClass;
    }
    
    // Kampf gegen Monster
    public function fight($playerId, $monsterData) {
        $playerData = $this->player->getPlayerById($playerId);
        
        if(!$playerData) {
            return array('success' => false, 'message' => 'Spieler nicht gefunden');
        }
        
        // Energie prüfen
        if($playerData['energy'] < 10) {
            return array('success' => false, 'message' => 'Nicht genug Energie');
        }
        
        // Kampf-Logik
        $playerHP = $playerData['hp'];
        $monsterHP = $monsterData['hp'];
        $rounds = array();
        
        while($playerHP > 0 && $monsterHP > 0) {
            // Spieler greift an
            $playerDamage = max(1, $playerData['attack'] - $monsterData['defense']);
            $monsterHP -= $playerDamage;
            
            $rounds[] = array(
                'attacker' => 'player',
                'damage' => $playerDamage,
                'monster_hp' => max(0, $monsterHP)
            );
            
            if($monsterHP <= 0) break;
            
            // Monster greift an
            $monsterDamage = max(1, $monsterData['attack'] - $playerData['defense']);
            $playerHP -= $monsterDamage;
            
            $rounds[] = array(
                'attacker' => 'monster',
                'damage' => $monsterDamage,
                'player_hp' => max(0, $playerHP)
            );
        }
        
        // Ergebnis
        $victory = $playerHP > 0;
        
        // Spieler updaten
        $this->player->updateHP($playerId, $playerHP - $playerData['hp']);
        $this->player->updateEnergy($playerId, -10);
        
        $result = array(
            'success' => true,
            'victory' => $victory,
            'rounds' => $rounds,
            'player_hp' => max(0, $playerHP),
            'rewards' => array()
        );
        
        // Bei Sieg: Belohnungen
        if($victory) {
            $goldReward = rand($monsterData['gold_min'], $monsterData['gold_max']);
            $expReward = $monsterData['exp'];
            
            $this->player->updateGold($playerId, $goldReward);
            $this->player->addExp($playerId, $expReward);
            
            $result['rewards'] = array(
                'gold' => $goldReward,
                'exp' => $expReward
            );
        }
        
        return $result;
    }
}
?>