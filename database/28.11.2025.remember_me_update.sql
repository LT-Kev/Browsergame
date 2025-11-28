-- Remember Me Tokens Tabelle
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    player_id INT(11) UNSIGNED NOT NULL,
    selector VARCHAR(32) NOT NULL UNIQUE,
    hashed_validator VARCHAR(64) NOT NULL,
    device_hash VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    last_used_at DATETIME DEFAULT NULL,
    
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    INDEX idx_selector (selector),
    INDEX idx_player_expires (player_id, expires_at),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;