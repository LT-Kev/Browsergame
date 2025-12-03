<?php
// ============================================================================
// app/Services/CombatService.php
// ============================================================================
namespace App\Services;

use App\Core\Database;

class CombatService {
    private Database $db;
    private PlayerService $player;

    public function __construct(Database $db, PlayerService $player) {
        $this->db = $db;
        $this->player = $player;
    }

    public function fight(int $playerId, array $monsterData): array {
        $playerData = $this->player->getPlayerById($playerId);
        if(!$playerData) {
            return ['success' => false, 'message' => 'Spieler nicht gefunden'];
        }

        if($playerData['energy'] < 10) {
            return ['success' => false, 'message' => 'Nicht genug Energie'];
        }

        $playerHP = $playerData['hp'];
        $monsterHP = $monsterData['hp'];
        $rounds = [];

        while($playerHP > 0 && $monsterHP > 0) {
            $playerDamage = max(1, $playerData['attack'] - $monsterData['defense']);
            $monsterHP -= $playerDamage;
            $rounds[] = ['attacker' => 'player', 'damage' => $playerDamage, 'monster_hp' => max(0, $monsterHP)];

            if($monsterHP <= 0) break;

            $monsterDamage = max(1, $monsterData['attack'] - $playerData['defense']);
            $playerHP -= $monsterDamage;
            $rounds[] = ['attacker' => 'monster', 'damage' => $monsterDamage, 'player_hp' => max(0, $playerHP)];
        }

        $victory = $playerHP > 0;
        $this->player->updateHP($playerId, $playerHP - $playerData['hp']);
        $this->player->updateEnergy($playerId, -10);

        $result = ['success' => true, 'victory' => $victory, 'rounds' => $rounds, 'player_hp' => max(0, $playerHP), 'rewards' => []];

        if($victory) {
            $goldReward = rand($monsterData['gold_min'], $monsterData['gold_max']);
            $expReward = $monsterData['exp'];
            $this->player->updateResources($playerId, ['gold' => $goldReward]);
            $this->player->addExp($playerId, $expReward);
            $result['rewards'] = ['gold' => $goldReward, 'exp' => $expReward];
        }

        return $result;
    }
}
