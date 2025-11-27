-- Datenbank erstellen
CREATE DATABASE IF NOT EXISTS browsergame CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE browsergame;

-- ====================================================================
-- SPIELER TABELLE
-- ====================================================================
CREATE TABLE IF NOT EXISTS players (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    
    -- Ressourcen
    gold INT(11) DEFAULT 500,
    food INT(11) DEFAULT 500,
    wood INT(11) DEFAULT 500,
    stone INT(11) DEFAULT 500,
    
    -- Lagerkapazität
    gold_capacity INT(11) DEFAULT 1000,
    food_capacity INT(11) DEFAULT 1000,
    wood_capacity INT(11) DEFAULT 1000,
    stone_capacity INT(11) DEFAULT 1000,
    
    -- Produktion pro Stunde
    gold_production INT(11) DEFAULT 10,
    food_production INT(11) DEFAULT 10,
    wood_production INT(11) DEFAULT 10,
    stone_production INT(11) DEFAULT 10,
    
    -- Letzte Ressourcen-Update Zeit
    last_resource_update DATETIME DEFAULT NULL,
    
    -- Spieler Stats
    energy INT(11) DEFAULT 100,
    level INT(11) DEFAULT 1,
    exp INT(11) DEFAULT 0,
    hp INT(11) DEFAULT 100,
    max_hp INT(11) DEFAULT 100,
    attack INT(11) DEFAULT 10,
    defense INT(11) DEFAULT 10,
    
    created_at DATETIME NOT NULL,
    last_login DATETIME DEFAULT NULL,
    
    INDEX idx_username (username),
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- GEBÄUDE-TYPEN TABELLE
-- ====================================================================
CREATE TABLE IF NOT EXISTS building_types (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    
    -- Basis-Baukosten (Level 1)
    base_gold_cost INT(11) DEFAULT 0,
    base_food_cost INT(11) DEFAULT 0,
    base_wood_cost INT(11) DEFAULT 0,
    base_stone_cost INT(11) DEFAULT 0,
    
    -- Basis-Bauzeit in Sekunden
    base_build_time INT(11) DEFAULT 60,
    
    -- Kosten-Multiplikator pro Level
    cost_multiplier DECIMAL(3,2) DEFAULT 1.5,
    
    -- Was produziert das Gebäude (pro Level pro Stunde)
    produces_gold INT(11) DEFAULT 0,
    produces_food INT(11) DEFAULT 0,
    produces_wood INT(11) DEFAULT 0,
    produces_stone INT(11) DEFAULT 0,
    
    -- Erhöht Lagerkapazität (pro Level)
    increases_gold_capacity INT(11) DEFAULT 0,
    increases_food_capacity INT(11) DEFAULT 0,
    increases_wood_capacity INT(11) DEFAULT 0,
    increases_stone_capacity INT(11) DEFAULT 0,
    
    -- Gebäude-Typ
    type ENUM('resource', 'storage', 'military', 'special') DEFAULT 'resource',
    
    max_level INT(11) DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- SPIELER GEBÄUDE
-- ====================================================================
CREATE TABLE IF NOT EXISTS player_buildings (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    player_id INT(11) UNSIGNED NOT NULL,
    building_type_id INT(11) UNSIGNED NOT NULL,
    level INT(11) DEFAULT 0,
    
    -- Upgrade-Status
    is_upgrading TINYINT(1) DEFAULT 0,
    upgrade_finish_time DATETIME DEFAULT NULL,
    
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (building_type_id) REFERENCES building_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_player_building (player_id, building_type_id),
    INDEX idx_player (player_id),
    INDEX idx_upgrading (is_upgrading)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- UPGRADE-WARTESCHLANGE
-- ====================================================================
CREATE TABLE IF NOT EXISTS upgrade_queue (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    player_id INT(11) UNSIGNED NOT NULL,
    building_id INT(11) UNSIGNED NOT NULL,
    start_time DATETIME NOT NULL,
    finish_time DATETIME NOT NULL,
    target_level INT(11) NOT NULL,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (building_id) REFERENCES player_buildings(id) ON DELETE CASCADE,
    INDEX idx_player (player_id),
    INDEX idx_finish (finish_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- ITEMS TABELLE
-- ====================================================================
CREATE TABLE IF NOT EXISTS items (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('weapon', 'armor', 'potion', 'quest', 'misc') DEFAULT 'misc',
    attack_bonus INT(11) DEFAULT 0,
    defense_bonus INT(11) DEFAULT 0,
    hp_bonus INT(11) DEFAULT 0,
    price INT(11) DEFAULT 0,
    sellable TINYINT(1) DEFAULT 1,
    usable TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- INVENTAR TABELLE
-- ====================================================================
CREATE TABLE IF NOT EXISTS inventory (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    player_id INT(11) UNSIGNED NOT NULL,
    item_id INT(11) UNSIGNED NOT NULL,
    quantity INT(11) DEFAULT 1,
    equipped TINYINT(1) DEFAULT 0,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    INDEX idx_player (player_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- QUESTS TABELLE
-- ====================================================================
CREATE TABLE IF NOT EXISTS quests (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    reward_gold INT(11) DEFAULT 0,
    reward_exp INT(11) DEFAULT 0,
    required_level INT(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- SPIELER QUESTS TABELLE
-- ====================================================================
CREATE TABLE IF NOT EXISTS player_quests (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    player_id INT(11) UNSIGNED NOT NULL,
    quest_id INT(11) UNSIGNED NOT NULL,
    status ENUM('active', 'completed', 'failed') DEFAULT 'active',
    started_at DATETIME NOT NULL,
    completed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
    INDEX idx_player (player_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- GEBÄUDE-TYPEN EINFÜGEN
-- ====================================================================

-- Hauptgebäude
INSERT INTO building_types (name, description, base_gold_cost, base_food_cost, base_wood_cost, base_stone_cost, base_build_time, cost_multiplier, type, max_level) VALUES
('Hauptgebäude', 'Das Hauptgebäude ist zentral für alle Upgrades. Je höher das Level, desto höhere Gebäude können gebaut werden.', 100, 80, 150, 100, 120, 1.5, 'special', 30);

-- Ressourcen-Gebäude
INSERT INTO building_types (name, description, base_gold_cost, base_food_cost, base_wood_cost, base_stone_cost, base_build_time, cost_multiplier, produces_gold, type, max_level) VALUES
('Goldmine', 'Produziert Gold pro Stunde. Je höher das Level, desto mehr Gold wird produziert.', 50, 30, 40, 60, 60, 1.5, 5, 'resource', 30);

INSERT INTO building_types (name, description, base_gold_cost, base_food_cost, base_wood_cost, base_stone_cost, base_build_time, cost_multiplier, produces_food, type, max_level) VALUES
('Bauernhof', 'Produziert Nahrung pro Stunde. Wichtig für die Versorgung deiner Truppen.', 30, 50, 40, 20, 60, 1.5, 5, 'resource', 30);

INSERT INTO building_types (name, description, base_gold_cost, base_food_cost, base_wood_cost, base_stone_cost, base_build_time, cost_multiplier, produces_wood, type, max_level) VALUES
('Holzfäller', 'Produziert Holz pro Stunde. Holz wird für viele Gebäude benötigt.', 40, 30, 50, 30, 60, 1.5, 5, 'resource', 30);

INSERT INTO building_types (name, description, base_gold_cost, base_food_cost, base_wood_cost, base_stone_cost, base_build_time, cost_multiplier, produces_stone, type, max_level) VALUES
('Steinbruch', 'Produziert Stein pro Stunde. Stein wird für Befestigungen benötigt.', 50, 30, 40, 50, 60, 1.5, 5, 'resource', 30);

-- Lager
INSERT INTO building_types (name, description, base_gold_cost, base_food_cost, base_wood_cost, base_stone_cost, base_build_time, cost_multiplier, increases_gold_capacity, increases_food_capacity, increases_wood_capacity, increases_stone_capacity, type, max_level) VALUES
('Lager', 'Erhöht die Lagerkapazität für alle Ressourcen. Ohne genug Lagerplatz gehen Ressourcen verloren!', 60, 50, 80, 70, 90, 1.5, 200, 200, 200, 200, 'storage', 30);

-- Militär
INSERT INTO building_types (name, description, base_gold_cost, base_food_cost, base_wood_cost, base_stone_cost, base_build_time, cost_multiplier, type, max_level) VALUES
('Kaserne', 'Ermöglicht das Rekrutieren von Truppen. Höheres Level = stärkere Truppen verfügbar.', 100, 150, 120, 80, 180, 1.5, 'military', 25);

INSERT INTO building_types (name, description, base_gold_cost, base_food_cost, base_wood_cost, base_stone_cost, base_build_time, cost_multiplier, type, max_level) VALUES
('Schmiede', 'Ermöglicht das Upgraden von Waffen und Rüstungen. Verbessert Angriffs- und Verteidigungswerte.', 120, 80, 100, 150, 200, 1.5, 'military', 20);

INSERT INTO building_types (name, description, base_gold_cost, base_food_cost, base_wood_cost, base_stone_cost, base_build_time, cost_multiplier, type, max_level) VALUES
('Stadtmauer', 'Erhöht die Verteidigung deines Dorfes gegen Angriffe. Je höher, desto besser geschützt.', 80, 60, 100, 200, 150, 1.5, 'military', 20);

-- ====================================================================
-- ITEMS EINFÜGEN
-- ====================================================================
INSERT INTO items (name, description, type, attack_bonus, price, sellable) VALUES
('Holzschwert', 'Ein einfaches Holzschwert für Anfänger', 'weapon', 5, 50, 1),
('Eisenschwert', 'Ein solides Eisenschwert', 'weapon', 15, 200, 1),
('Stahlschwert', 'Ein hochwertiges Stahlschwert', 'weapon', 25, 500, 1);

INSERT INTO items (name, description, type, defense_bonus, price, sellable) VALUES
('Lederrüstung', 'Leichte Rüstung aus Leder', 'armor', 5, 100, 1),
('Kettenrüstung', 'Mittelschwere Kettenrüstung', 'armor', 15, 300, 1),
('Plattenrüstung', 'Schwere Plattenrüstung', 'armor', 30, 800, 1);

INSERT INTO items (name, description, type, hp_bonus, price, sellable, usable) VALUES
('Kleiner Heiltrank', 'Stellt 25 HP wieder her', 'potion', 25, 20, 1, 1),
('Heiltrank', 'Stellt 50 HP wieder her', 'potion', 50, 40, 1, 1),
('Großer Heiltrank', 'Stellt 100 HP wieder her', 'potion', 100, 80, 1, 1);

-- ====================================================================
-- QUESTS EINFÜGEN
-- ====================================================================
INSERT INTO quests (title, description, reward_gold, reward_exp, required_level) VALUES
('Erste Schritte', 'Willkommen! Besiege 5 Goblins um dich mit dem Kampfsystem vertraut zu machen.', 100, 50, 1),
('Der Ork-Anführer', 'Ein mächtiger Ork-Anführer terrorisiert die Umgebung. Besiege ihn!', 500, 250, 5),
('Schatzsuche', 'Gerüchten zufolge gibt es einen versteckten Schatz in den Bergen. Finde ihn!', 1000, 500, 10),
('Baumeister', 'Baue dein Hauptgebäude auf Level 5 aus.', 300, 200, 1),
('Rohstoffsammler', 'Sammle insgesamt 5000 Ressourcen (beliebig).', 400, 300, 1);

-- ====================================================================
-- TEST-SPIELER ERSTELLEN
-- ====================================================================
-- Passwort ist: test123
INSERT INTO players (username, email, password, gold, food, wood, stone, last_resource_update, created_at) VALUES
('TestSpieler', 'test@test.de', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 500, 500, 500, 500, NOW(), NOW());

-- ====================================================================
-- TEST-SPIELER STARTGEBÄUDE
-- ====================================================================
INSERT INTO player_buildings (player_id, building_type_id, level) VALUES
(1, 1, 1),  -- Hauptgebäude Level 1
(1, 2, 1),  -- Goldmine Level 1
(1, 3, 1),  -- Bauernhof Level 1
(1, 4, 1),  -- Holzfäller Level 1
(1, 5, 1),  -- Steinbruch Level 1
(1, 6, 1),  -- Lager Level 1
(1, 7, 0),  -- Kaserne Level 0 (noch nicht gebaut)
(1, 8, 0),  -- Schmiede Level 0 (noch nicht gebaut)
(1, 9, 0);  -- Stadtmauer Level 0 (noch nicht gebaut)

-- ====================================================================
-- FERTIG!
-- ====================================================================
-- Die Datenbank ist jetzt vollständig eingerichtet.
-- 
-- Login-Daten für Test:
-- Username: TestSpieler
-- Passwort: test123
-- 
-- Das Spiel kann jetzt gestartet werden!