<?php
namespace App\Models;

class Admin {
    public int $player_id;
    public int $level;

    public function __construct(array $data) {
        $this->player_id = $data['player_id'] ?? 0;
        $this->level = $data['level'] ?? 0;
    }
}
