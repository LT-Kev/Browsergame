<?php
namespace App\Models;

class Building {
    public int $id;
    public int $level;
    public int $player_id;

    public function __construct(array $data) {
        $this->id = $data['id'] ?? 0;
        $this->level = $data['level'] ?? 0;
        $this->player_id = $data['player_id'] ?? 0;
    }
}
