-- ====================================================================
-- RPG SYSTEM - RASSEN, KLASSEN & STATS
-- Database Update für erweiterte RPG-Funktionalität
-- ====================================================================

-- ====================================================================
-- RASSEN TABELLE
-- ====================================================================
CREATE TABLE IF NOT EXISTS races (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    
    -- Basis-Stats (werden beim Character-Erstellen gegeben)
    base_strength INT(11) DEFAULT 10,
    base_dexterity INT(11) DEFAULT 10,
    base_constitution INT(11) DEFAULT 10,
    base_intelligence INT(11) DEFAULT 10,
    base_wisdom INT(11) DEFAULT 10,
    base_charisma INT(11) DEFAULT 10,
    
    -- Stats pro Level-Up
    strength_per_level DECIMAL(3,2) DEFAULT 1.00,
    dexterity_per_level DECIMAL(3,2) DEFAULT 1.00,
    constitution_per_level DECIMAL(3,2) DEFAULT 1.00,
    intelligence_per_level DECIMAL(3,2) DEFAULT 1.00,
    wisdom_per_level DECIMAL(3,2) DEFAULT 1.00,
    charisma_per_level DECIMAL(3,2) DEFAULT 1.00,
    
    -- Spezial-Boni
    hp_modifier DECIMAL(3,2) DEFAULT 1.00, -- Multiplikator für HP
    mana_modifier DECIMAL(3,2) DEFAULT 1.00, -- Multiplikator für Mana
    stamina_modifier DECIMAL(3,2) DEFAULT 1.00, -- Multiplikator für Stamina
    
    -- Ressourcen-Boni (für Wirtschaft)
    gold_bonus_percent INT(11) DEFAULT 0,
    food_bonus_percent INT(11) DEFAULT 0,
    wood_bonus_percent INT(11) DEFAULT 0,
    stone_bonus_percent INT(11) DEFAULT 0,
    
    -- Kampf-Modifikatoren
    melee_damage_bonus DECIMAL(3,2) DEFAULT 0.00,
    ranged_damage_bonus DECIMAL(3,2) DEFAULT 0.00,
    magic_damage_bonus DECIMAL(3,2) DEFAULT 0.00,
    defense_bonus DECIMAL(3,2) DEFAULT 0.00,
    
    -- Grafik & Flavor
    icon VARCHAR(100) DEFAULT '👤',
    lore TEXT,
    
    is_playable TINYINT(1) DEFAULT 1,
    is_hybrid TINYINT(1) DEFAULT 0, -- Für Hybrid-Rassen
    parent_race_1 INT(11) UNSIGNED DEFAULT NULL, -- Erste Elternrasse
    parent_race_2 INT(11) UNSIGNED DEFAULT NULL, -- Zweite Elternrasse
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_race_1) REFERENCES races(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_race_2) REFERENCES races(id) ON DELETE SET NULL,
    INDEX idx_playable (is_playable)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- KLASSEN TABELLE
-- ====================================================================
CREATE TABLE IF NOT EXISTS classes (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    type ENUM('combat', 'magic', 'craft', 'gather', 'support', 'hybrid') DEFAULT 'combat',
    
    -- Primär-Stats (welche Stats sind wichtig für diese Klasse)
    primary_stat_1 VARCHAR(20) DEFAULT 'strength', -- strength, dexterity, constitution, intelligence, wisdom, charisma
    primary_stat_2 VARCHAR(20) DEFAULT NULL,
    
    -- Stats-Modifikatoren pro Level
    strength_modifier DECIMAL(3,2) DEFAULT 1.00,
    dexterity_modifier DECIMAL(3,2) DEFAULT 1.00,
    constitution_modifier DECIMAL(3,2) DEFAULT 1.00,
    intelligence_modifier DECIMAL(3,2) DEFAULT 1.00,
    wisdom_modifier DECIMAL(3,2) DEFAULT 1.00,
    charisma_modifier DECIMAL(3,2) DEFAULT 1.00,
    
    -- Resource-Modifikatoren
    hp_modifier DECIMAL(3,2) DEFAULT 1.00,
    mana_modifier DECIMAL(3,2) DEFAULT 1.00,
    stamina_modifier DECIMAL(3,2) DEFAULT 1.00,
    
    -- Spezial-Fähigkeiten
    can_craft TINYINT(1) DEFAULT 0,
    can_gather TINYINT(1) DEFAULT 0,
    can_trade TINYINT(1) DEFAULT 0,
    
    -- Crafting-Boni (für Handwerker)
    crafting_speed_bonus INT(11) DEFAULT 0, -- Prozent
    crafting_quality_bonus INT(11) DEFAULT 0, -- Prozent
    
    -- Gathering-Boni (für Sammler)
    gathering_speed_bonus INT(11) DEFAULT 0,
    gathering_amount_bonus INT(11) DEFAULT 0,
    
    -- Kampf-Boni
    attack_bonus DECIMAL(3,2) DEFAULT 0.00,
    defense_bonus DECIMAL(3,2) DEFAULT 0.00,
    critical_chance_bonus INT(11) DEFAULT 0, -- Prozent
    
    -- Anforderungen
    required_level INT(11) DEFAULT 1,
    required_class_id INT(11) UNSIGNED DEFAULT NULL, -- Voraussetzung: andere Klasse
    
    -- Grafik & Flavor
    icon VARCHAR(100) DEFAULT '⚔️',
    lore TEXT,
    
    is_starter_class TINYINT(1) DEFAULT 1,
    is_advanced_class TINYINT(1) DEFAULT 0,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (required_class_id) REFERENCES classes(id) ON DELETE SET NULL,
    INDEX idx_type (type),
    INDEX idx_starter (is_starter_class)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- PLAYERS TABELLE ERWEITERN
-- ====================================================================
ALTER TABLE players 
ADD COLUMN race_id INT(11) UNSIGNED DEFAULT NULL AFTER admin_level,
ADD COLUMN class_id INT(11) UNSIGNED DEFAULT NULL AFTER race_id,

-- RPG Stats
ADD COLUMN strength INT(11) DEFAULT 10 AFTER class_id,
ADD COLUMN dexterity INT(11) DEFAULT 10 AFTER strength,
ADD COLUMN constitution INT(11) DEFAULT 10 AFTER dexterity,
ADD COLUMN intelligence INT(11) DEFAULT 10 AFTER constitution,
ADD COLUMN wisdom INT(11) DEFAULT 10 AFTER intelligence,
ADD COLUMN charisma INT(11) DEFAULT 10 AFTER wisdom,

-- Freie Statuspunkte
ADD COLUMN stat_points INT(11) DEFAULT 0 AFTER charisma,

-- Zusätzliche Ressourcen
ADD COLUMN mana INT(11) DEFAULT 100 AFTER hp,
ADD COLUMN max_mana INT(11) DEFAULT 100 AFTER mana,
ADD COLUMN stamina INT(11) DEFAULT 100 AFTER max_mana,
ADD COLUMN max_stamina INT(11) DEFAULT 100 AFTER stamina,

-- Character Creation
ADD COLUMN character_created TINYINT(1) DEFAULT 0 AFTER max_stamina,

-- Foreign Keys
ADD FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE SET NULL,
ADD FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL;

-- ====================================================================
-- SPIELER KLASSEN HISTORIE (Multi-Klassen System)
-- ====================================================================
CREATE TABLE IF NOT EXISTS player_classes (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    player_id INT(11) UNSIGNED NOT NULL,
    class_id INT(11) UNSIGNED NOT NULL,
    class_level INT(11) DEFAULT 1,
    class_exp INT(11) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1, -- Aktuell aktive Klasse
    learned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_player_class (player_id, class_id),
    INDEX idx_player (player_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- SKILLS/FÄHIGKEITEN TABELLE
-- ====================================================================
CREATE TABLE IF NOT EXISTS skills (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('active', 'passive', 'crafting', 'gathering') DEFAULT 'active',
    
    -- Klassenbindung
    class_id INT(11) UNSIGNED DEFAULT NULL,
    required_level INT(11) DEFAULT 1,
    
    -- Kosten
    mana_cost INT(11) DEFAULT 0,
    stamina_cost INT(11) DEFAULT 0,
    cooldown INT(11) DEFAULT 0, -- Sekunden
    
    -- Effekte
    damage INT(11) DEFAULT 0,
    heal INT(11) DEFAULT 0,
    buff_duration INT(11) DEFAULT 0,
    
    -- Skalierung (welcher Stat beeinflusst diese Fähigkeit)
    scales_with VARCHAR(20) DEFAULT NULL, -- strength, intelligence, etc.
    scaling_factor DECIMAL(3,2) DEFAULT 1.00,
    
    icon VARCHAR(100) DEFAULT '✨',
    
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    INDEX idx_class (class_id),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- SPIELER SKILLS
-- ====================================================================
CREATE TABLE IF NOT EXISTS player_skills (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    player_id INT(11) UNSIGNED NOT NULL,
    skill_id INT(11) UNSIGNED NOT NULL,
    skill_level INT(11) DEFAULT 1,
    last_used DATETIME DEFAULT NULL,
    
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
    UNIQUE KEY unique_player_skill (player_id, skill_id),
    INDEX idx_player (player_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- RASSEN EINFÜGEN
-- ====================================================================

-- Menschen (Allrounder)
INSERT INTO races (name, description, 
    base_strength, base_dexterity, base_constitution, base_intelligence, base_wisdom, base_charisma,
    strength_per_level, dexterity_per_level, constitution_per_level, intelligence_per_level, wisdom_per_level, charisma_per_level,
    hp_modifier, mana_modifier, stamina_modifier,
    icon, lore) VALUES
('Mensch', 'Menschen sind vielseitig und anpassungsfähig. Sie haben keine extremen Stärken oder Schwächen, dafür aber Bonus-Erfahrung.',
    10, 10, 10, 10, 10, 12,
    1.05, 1.05, 1.05, 1.05, 1.05, 1.10,
    1.00, 1.00, 1.00,
    '👨', 'Menschen sind die häufigste Rasse und bekannt für ihre Anpassungsfähigkeit und ihren Ehrgeiz.');

-- Orks (Stärke & Ausdauer)
INSERT INTO races (name, description,
    base_strength, base_dexterity, base_constitution, base_intelligence, base_wisdom, base_charisma,
    strength_per_level, dexterity_per_level, constitution_per_level, intelligence_per_level, wisdom_per_level, charisma_per_level,
    hp_modifier, mana_modifier, stamina_modifier,
    melee_damage_bonus, defense_bonus,
    icon, lore) VALUES
('Ork', 'Orks sind brutale Krieger mit enormer Stärke und Ausdauer. Schwach in Magie und Diplomatie.',
    15, 8, 14, 6, 7, 5,
    1.20, 0.80, 1.15, 0.70, 0.75, 0.60,
    1.25, 0.70, 1.20,
    0.15, 0.10,
    '🧟', 'Orks sind kriegerische Wesen, die für ihre Brutalität und Überlebenskraft bekannt sind.');

-- Elfen (Geschicklichkeit & Magie)
INSERT INTO races (name, description,
    base_strength, base_dexterity, base_constitution, base_intelligence, base_wisdom, base_charisma,
    strength_per_level, dexterity_per_level, constitution_per_level, intelligence_per_level, wisdom_per_level, charisma_per_level,
    hp_modifier, mana_modifier, stamina_modifier,
    ranged_damage_bonus, magic_damage_bonus,
    icon, lore) VALUES
('Elf', 'Elfen sind agile und magisch begabte Wesen. Schwach im Nahkampf, aber Meister der Magie und des Bogens.',
    7, 14, 8, 13, 12, 11,
    0.75, 1.20, 0.85, 1.15, 1.10, 1.05,
    0.80, 1.30, 0.90,
    0.15, 0.20,
    '🧝', 'Elfen leben in Harmonie mit der Natur und besitzen eine natürliche Affinität zur Magie.');

-- Zwerge (Ausdauer & Handwerk)
INSERT INTO races (name, description,
    base_strength, base_dexterity, base_constitution, base_intelligence, base_wisdom, base_charisma,
    strength_per_level, dexterity_per_level, constitution_per_level, intelligence_per_level, wisdom_per_level, charisma_per_level,
    hp_modifier, mana_modifier, stamina_modifier,
    defense_bonus, stone_bonus_percent,
    icon, lore) VALUES
('Zwerg', 'Zwerge sind robust und meisterhafte Handwerker. Stark in Verteidigung und Bergbau, langsam aber zäh.',
    12, 7, 15, 9, 11, 8,
    1.10, 0.70, 1.25, 0.90, 1.05, 0.80,
    1.30, 0.80, 1.10,
    0.20, 25,
    '🧔', 'Zwerge sind stolze Bergleute und Schmiede, die tief in den Bergen leben.');

-- ====================================================================
-- HYBRID-RASSEN (Beispiele)
-- ====================================================================

-- Halb-Ork (Mensch + Ork)
INSERT INTO races (name, description,
    base_strength, base_dexterity, base_constitution, base_intelligence, base_wisdom, base_charisma,
    strength_per_level, dexterity_per_level, constitution_per_level, intelligence_per_level, wisdom_per_level, charisma_per_level,
    hp_modifier, mana_modifier, stamina_modifier,
    melee_damage_bonus,
    is_hybrid, parent_race_1, parent_race_2,
    icon, lore) VALUES
('Halb-Ork', 'Eine Mischung aus Mensch und Ork. Stärker als Menschen, aber zivilisierter als Orks.',
    13, 9, 12, 8, 9, 8,
    1.13, 0.93, 1.10, 0.88, 0.90, 0.85,
    1.13, 0.85, 1.10,
    0.08,
    1, 1, 2,
    '👹', 'Halb-Orks kämpfen oft mit ihrer doppelten Natur zwischen Zivilisation und Wildheit.');

-- Halb-Elf (Mensch + Elf)
INSERT INTO races (name, description,
    base_strength, base_dexterity, base_constitution, base_intelligence, base_wisdom, base_charisma,
    strength_per_level, dexterity_per_level, constitution_per_level, intelligence_per_level, wisdom_per_level, charisma_per_level,
    hp_modifier, mana_modifier, stamina_modifier,
    magic_damage_bonus,
    is_hybrid, parent_race_1, parent_race_2,
    icon, lore) VALUES
('Halb-Elf', 'Eine Mischung aus Mensch und Elf. Vielseitig mit elfischer Anmut und menschlicher Entschlossenheit.',
    9, 12, 9, 11, 11, 12,
    0.90, 1.13, 0.93, 1.10, 1.08, 1.08,
    0.90, 1.15, 0.95,
    0.10,
    1, 1, 3,
    '🧝‍♂️', 'Halb-Elfen vereinen das Beste beider Welten, werden aber oft als Außenseiter betrachtet.');

-- ====================================================================
-- KLASSEN EINFÜGEN
-- ====================================================================

-- KAMPF-KLASSEN
INSERT INTO classes (name, description, type,
    primary_stat_1, primary_stat_2,
    strength_modifier, constitution_modifier,
    hp_modifier, stamina_modifier,
    attack_bonus, defense_bonus,
    is_starter_class, icon, lore) VALUES
('Krieger', 'Meister des Nahkampfs. Robust und stark, spezialisiert auf Schwerter und Schilde.',
    'combat', 'strength', 'constitution',
    1.30, 1.20,
    1.30, 1.20,
    0.20, 0.15,
    1, '⚔️', 'Krieger sind die Vorhut jeder Schlacht und trotzen jedem Feind.');

INSERT INTO classes (name, description, type,
    primary_stat_1, primary_stat_2,
    dexterity_modifier, constitution_modifier,
    stamina_modifier, critical_chance_bonus,
    attack_bonus,
    is_starter_class, icon, lore) VALUES
('Schurke', 'Schnell und tödlich. Meister des Schleichens und kritischer Treffer.',
    'combat', 'dexterity', 'constitution',
    1.30, 1.10,
    1.30, 15,
    0.15,
    1, '🗡️', 'Schurken bewegen sich lautlos im Schatten und schlagen im richtigen Moment zu.');

INSERT INTO classes (name, description, type,
    primary_stat_1, primary_stat_2,
    dexterity_modifier, wisdom_modifier,
    stamina_modifier, ranged_damage_bonus,
    is_starter_class, icon, lore) VALUES
('Bogenschütze', 'Meister der Distanz. Präzise Fernkampfangriffe.',
    'combat', 'dexterity', 'wisdom',
    1.25, 1.15,
    1.20, 0.20,
    1, '🏹', 'Bogenschützen treffen ihre Ziele aus großer Entfernung mit tödlicher Präzision.');

-- MAGIE-KLASSEN
INSERT INTO classes (name, description, type,
    primary_stat_1, primary_stat_2,
    intelligence_modifier, wisdom_modifier,
    mana_modifier, magic_damage_bonus,
    is_starter_class, icon, lore) VALUES
('Magier', 'Meister der arkanen Künste. Mächtige Zauber, aber körperlich schwach.',
    'magic', 'intelligence', 'wisdom',
    1.40, 1.20,
    1.50, 0.30,
    1, '🧙', 'Magier beherrschen die Elemente und beugen die Realität nach ihrem Willen.');

INSERT INTO classes (name, description, type,
    primary_stat_1, primary_stat_2,
    wisdom_modifier, charisma_modifier,
    mana_modifier, hp_modifier,
    is_starter_class, icon, lore) VALUES
('Priester', 'Heiler und Unterstützer. Nutzen göttliche Magie.',
    'magic', 'wisdom', 'charisma',
    1.30, 1.15,
    1.40, 1.10,
    1, '⛪', 'Priester sind Diener der Götter und Meister der Heilkunst.');

-- HANDWERKS-KLASSEN
INSERT INTO classes (name, description, type,
    primary_stat_1, primary_stat_2,
    strength_modifier, intelligence_modifier,
    can_craft, crafting_speed_bonus, crafting_quality_bonus,
    is_starter_class, icon, lore) VALUES
('Schmied', 'Meister der Metallbearbeitung. Erstellt und verbessert Waffen und Rüstungen.',
    'craft', 'strength', 'intelligence',
    1.15, 1.15,
    1, 25, 20,
    1, '⚒️', 'Schmiede formen Metall mit Hammer und Amboss zu tödlichen Waffen.');

INSERT INTO classes (name, description, type,
    primary_stat_1, primary_stat_2,
    dexterity_modifier, wisdom_modifier,
    can_craft, can_gather, crafting_quality_bonus,
    is_starter_class, icon, lore) VALUES
('Alchemist', 'Meister der Tränke und Elixiere. Kombiniert Kräuter zu mächtigen Gebräuen.',
    'craft', 'intelligence', 'wisdom',
    1.20, 1.25,
    1, 1, 30,
    1, '⚗️', 'Alchemisten verwandeln simple Zutaten in mächtige Tränke.');

-- SAMMEL-KLASSEN
INSERT INTO classes (name, description, type,
    primary_stat_1, primary_stat_2,
    strength_modifier, constitution_modifier,
    can_gather, gathering_speed_bonus, gathering_amount_bonus,
    wood_bonus_percent,
    is_starter_class, icon, lore) VALUES
('Holzfäller', 'Spezialist für Holzgewinnung. Sammelt Holz schneller und in größeren Mengen.',
    'gather', 'strength', 'constitution',
    1.15, 1.10,
    1, 30, 25,
    25,
    1, '🪓', 'Holzfäller kennen die Wälder wie ihre Westentasche.');

INSERT INTO classes (name, description, type,
    primary_stat_1, primary_stat_2,
    strength_modifier, constitution_modifier,
    can_gather, gathering_speed_bonus, gathering_amount_bonus,
    stone_bonus_percent,
    is_starter_class, icon, lore) VALUES
('Bergarbeiter', 'Spezialist für Bergbau. Sammelt Erze und Stein effizienter.',
    'gather', 'strength', 'constitution',
    1.20, 1.15,
    1, 35, 30,
    30,
    1, '⛏️', 'Bergarbeiter trotzen den Tiefen der Erde für wertvolle Ressourcen.');

-- ERWEITERTE KLASSEN (Beispiele)
INSERT INTO classes (name, description, type,
    primary_stat_1, primary_stat_2,
    strength_modifier, constitution_modifier, charisma_modifier,
    hp_modifier, attack_bonus, defense_bonus,
    required_level, required_class_id,
    is_starter_class, is_advanced_class, icon, lore) VALUES
('Paladin', 'Heiliger Krieger. Kombiniert Kampfkunst mit göttlicher Magie.',
    'hybrid', 'strength', 'wisdom',
    1.35, 1.25, 1.20,
    1.40, 0.25, 0.20,
    20, 1, -- Benötigt Krieger
    0, 1, '🛡️', 'Paladine sind unerschütterliche Verteidiger des Guten.');

INSERT INTO classes (name, description, type,
    primary_stat_1, primary_stat_2,
    strength_modifier, dexterity_modifier, intelligence_modifier,
    attack_bonus, critical_chance_bonus,
    required_level, required_class_id,
    is_starter_class, is_advanced_class, icon, lore) VALUES
('Schwertmeister', 'Absoluter Meister des Schwertkampfs. Höchste Schadenswerte.',
    'combat', 'strength', 'dexterity',
    1.50, 1.40, 1.10,
    0.40, 25,
    30, 1, -- Benötigt Krieger
    0, 1, '⚔️✨', 'Schwertmeister haben ihr Leben dem perfekten Schwerthieb gewidmet.');

-- ====================================================================
-- STARTER SKILLS
-- ====================================================================

-- Krieger Skills
INSERT INTO skills (name, description, type, class_id, mana_cost, stamina_cost, cooldown, damage, scales_with, scaling_factor, icon) VALUES
('Mächtiger Schlag', 'Ein kraftvoller Hieb, der massiven Schaden verursacht.', 'active', 1, 0, 20, 5, 50, 'strength', 1.50, '💥'),
('Schildblock', 'Erhöht die Verteidigung für kurze Zeit.', 'active', 1, 0, 15, 10, 0, 'constitution', 1.00, '🛡️'),
('Kampfschrei', 'Erhöht Angriff und Verteidigung aller Verbündeten.', 'active', 1, 0, 30, 30, 0, 'charisma', 1.20, '📢');

-- Magier Skills
INSERT INTO skills (name, description, type, class_id, mana_cost, stamina_cost, cooldown, damage, scales_with, scaling_factor, icon) VALUES
('Feuerball', 'Schleudert einen Feuerball auf den Gegner.', 'active', 4, 25, 0, 3, 80, 'intelligence', 2.00, '🔥'),
('Froststrahl', 'Verlangsamt den Gegner und fügt Kälteschaden zu.', 'active', 4, 20, 0, 5, 60, 'intelligence', 1.80, '❄️'),
('Magisches Schild', 'Erstellt ein Schutzschild.', 'active', 4, 30, 0, 15, 0, 'intelligence', 1.50, '✨');

-- Schmied Skills (Crafting)
INSERT INTO skills (name, description, type, class_id, icon) VALUES
('Waffenschmieden', 'Stelle Waffen her.', 'crafting', 6, '⚔️'),
('Rüstungsschmieden', 'Stelle Rüstungen her.', 'crafting', 6, '🛡️'),
('Reparieren', 'Repariere beschädigte Ausrüstung.', 'crafting', 6, '🔧');

-- ====================================================================
-- UPDATE BESTEHENDE SPIELER (OPTIONAL)
-- ====================================================================
-- Gibt bestehenden Spielern Standard-Rasse und Klasse
UPDATE players 
SET race_id = 1, -- Mensch
    class_id = 1, -- Krieger
    character_created = 1
WHERE race_id IS NULL;

-- ====================================================================
-- FERTIG!
-- ====================================================================
-- Das RPG-System ist jetzt eingerichtet mit:
-- - 6 Basis-Rassen (Mensch, Ork, Elf, Zwerg, Halb-Ork, Halb-Elf)
-- - 10+ Klassen (Kampf, Magie, Handwerk, Sammeln)
-- - Stats-System (STR, DEX, CON, INT, WIS, CHA)
-- - Multi-Klassen Support
-- - Skills-System
-- - Hybrid-Rassen System