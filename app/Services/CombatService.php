<?php

// ============================================================================
// app/Services/CombatService.php
// ============================================================================

class CombatService {
    private App $app;

    public function __construct(App $app) {
        $this->app = $app;
    }

    public function fight(int $playerId, array $monsterData): array {
        $player = $this->app->getPlayer()->getPlayerById($playerId);
        if(!$player) return ['success' => false, 'message' => 'Spieler nicht gefunden'];
        if($player['energy'] < 10) return ['success' => false, 'message' => 'Nicht genug Energie'];

        $playerHP = $player['hp'];
        $monsterHP = $monsterData['hp'];
        $rounds = [];

        while($playerHP > 0 && $monsterHP > 0) {
            $playerDamage = max(1, $player['attack'] - $monsterData['defense']);
            $monsterHP -= $playerDamage;
            $rounds[] = ['attacker' => 'player', 'damage' => $playerDamage, 'monster_hp' => max(0, $monsterHP)];

            if($monsterHP <= 0) break;

            $monsterDamage = max(1, $monsterData['attack'] - $player['defense']);
            $playerHP -= $monsterDamage;
            $rounds[] = ['attacker' => 'monster', 'damage' => $monsterDamage, 'player_hp' => max(0, $playerHP)];
        }

        $victory = $playerHP > 0;
        $this->app->getPlayer()->updateHP($playerId, $playerHP - $player['hp']);
        $this->app->getPlayer()->updateEnergy($playerId, -10);

        $result = ['success' => true, 'victory' => $victory, 'rounds' => $rounds, 'player_hp' => max(0, $playerHP), 'rewards' => []];

        if($victory) {
            $goldReward = rand($monsterData['gold_min'], $monsterData['gold_max']);
            $expReward = $monsterData['exp'];
            $this->app->getPlayer()->updateResources($playerId, ['gold' => $goldReward]);
            $this->app->getPlayer()->addExp($playerId, $expReward);
            $result['rewards'] = ['gold' => $goldReward, 'exp' => $expReward];
        }

        return $result;
    }
}