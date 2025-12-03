<?php

// ============================================================================
// app/Services/QuestService.php
// ============================================================================

class QuestService {
    private App $app;

    public function __construct(App $app) {
        $this->app = $app;
    }

    public function getAllQuests(): array {
        $sql = "SELECT * FROM quests ORDER BY required_level ASC";
        return $this->app->getDb()->select($sql);
    }

    public function getQuestById(int $questId): ?array {
        $sql = "SELECT * FROM quests WHERE id = :id LIMIT 1";
        return $this->app->getDb()->selectOne($sql, [':id' => $questId]);
    }

    public function getPlayerQuests(int $playerId, ?string $status = null): array {
        if($status) {
            $sql = "SELECT pq.*, q.title, q.description, q.reward_gold, q.reward_exp FROM player_quests pq JOIN quests q ON pq.quest_id = q.id WHERE pq.player_id = :player_id AND pq.status = :status ORDER BY pq.started_at DESC";
            return $this->app->getDb()->select($sql, [':player_id' => $playerId, ':status' => $status]);
        }

        $sql = "SELECT pq.*, q.title, q.description, q.reward_gold, q.reward_exp FROM player_quests pq JOIN quests q ON pq.quest_id = q.id WHERE pq.player_id = :player_id ORDER BY pq.started_at DESC";
        return $this->app->getDb()->select($sql, [':player_id' => $playerId]);
    }

    public function startQuest(int $playerId, int $questId): array {
        $sql = "SELECT * FROM player_quests WHERE player_id = :player_id AND quest_id = :quest_id AND status = 'active' LIMIT 1";
        $existing = $this->app->getDb()->selectOne($sql, [':player_id' => $playerId, ':quest_id' => $questId]);
        if($existing) return ['success' => false, 'message' => 'Quest bereits aktiv'];

        $sql = "INSERT INTO player_quests (player_id, quest_id, status, started_at) VALUES (:player_id, :quest_id, 'active', NOW())";
        $result = $this->app->getDb()->insert($sql, [':player_id' => $playerId, ':quest_id' => $questId]);

        return $result ? ['success' => true, 'message' => 'Quest gestartet'] : ['success' => false, 'message' => 'Fehler beim Starten'];
    }

    public function completeQuest(int $playerId, int $questId): array {
        $sql = "UPDATE player_quests SET status = 'completed', completed_at = NOW() WHERE player_id = :player_id AND quest_id = :quest_id AND status = 'active'";
        $result = $this->app->getDb()->update($sql, [':player_id' => $playerId, ':quest_id' => $questId]);

        if($result) {
            $quest = $this->getQuestById($questId);
            return ['success' => true, 'message' => 'Quest abgeschlossen', 'reward_gold' => $quest['reward_gold'], 'reward_exp' => $quest['reward_exp']];
        }

        return ['success' => false, 'message' => 'Fehler beim Abschließen'];
    }
}