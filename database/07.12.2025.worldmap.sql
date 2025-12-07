-- Spieler-Position speichern
ALTER TABLE players 
ADD COLUMN world_x INT DEFAULT 500,
ADD COLUMN world_y INT DEFAULT 500,
ADD COLUMN is_traveling TINYINT(1) DEFAULT 0,
ADD COLUMN travel_destination_x INT,
ADD COLUMN travel_destination_y INT,
ADD COLUMN travel_arrival_time DATETIME;

-- Locations Tabelle erstellen
CREATE TABLE IF NOT EXISTS world_locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(10) DEFAULT '🏛️',
    x INT NOT NULL,
    y INT NOT NULL,
    type ENUM('city','dungeon','resource','boss','special') DEFAULT 'city',
    level INT DEFAULT 1,
    description TEXT,
    features TEXT, -- JSON Array
    enemy_level VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_coords (x, y)
);