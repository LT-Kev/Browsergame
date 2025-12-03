<?php
namespace App\Models;

class Player {
    public int $id;
    public string $name;
    public int $resources;
    public int $admin_level;
    public bool $character_created;

    public function __construct(array $data) {
        $this->id = $data['id'] ?? 0;
        $this->name = $data['name'] ?? '';
        $this->resources = $data['resources'] ?? 0;
        $this->admin_level = $data['admin_level'] ?? 0;
        $this->character_created = $data['character_created'] ?? false;
    }
}
