<?php
namespace App\Models;

class Resources {
    public int $player_id;
    public int $amount;
    public int $production_rate;

    public function __construct(array $data) {
        $this->player_id = $data['player_id'] ?? 0;
        $this->amount = $data['amount'] ?? 0;
        $this->production_rate = $data['production_rate'] ?? 0;
    }
}
