-- Admin-Level zur players Tabelle hinzufügen
ALTER TABLE players ADD COLUMN admin_level INT(11) DEFAULT 0 AFTER defense;

-- Admin-Rechte Tabelle erstellen
CREATE TABLE IF NOT EXISTS admin_permissions (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_level INT(11) NOT NULL,
    permission_key VARCHAR(50) NOT NULL,
    permission_name VARCHAR(100) NOT NULL,
    description TEXT,
    UNIQUE KEY unique_level_permission (admin_level, permission_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin-Level Definitionen
CREATE TABLE IF NOT EXISTS admin_levels (
    level INT(11) PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    color VARCHAR(20) DEFAULT '#95a5a6'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin-Levels einfügen
INSERT INTO admin_levels (level, name, description, color) VALUES
(0, 'Spieler', 'Normaler Spieler ohne Admin-Rechte', '#95a5a6'),
(1, 'Supporter', 'Kann Logs einsehen und Spieler-Support leisten', '#3498db'),
(2, 'Moderator', 'Kann Spieler verwarnen und temporär sperren', '#9b59b6'),
(3, 'Game Master', 'Kann Spieler-Daten bearbeiten und Items vergeben', '#1abc9c'),
(4, 'Senior GM', 'Erweiterte Spieler-Verwaltung', '#16a085'),
(5, 'Administrator', 'Kann System-Einstellungen ändern', '#e67e22'),
(6, 'Senior Admin', 'Erweiterte System-Verwaltung', '#d35400'),
(7, 'Lead Admin', 'Kann andere Admins ernennen (bis Level 6)', '#e74c3c'),
(8, 'Co-Owner', 'Fast vollständiger Zugriff', '#c0392b'),
(9, 'Developer', 'Vollständiger Zugriff inkl. Datenbank', '#2c3e50'),
(10, 'Owner', 'Uneingeschränkter Zugriff auf alles', '#000000');

-- Standard-Berechtigungen einfügen
INSERT INTO admin_permissions (admin_level, permission_key, permission_name, description) VALUES
-- Level 1: Supporter
(1, 'view_logs', 'Logs ansehen', 'Kann System-Logs einsehen'),
(1, 'view_players', 'Spieler ansehen', 'Kann Spieler-Daten ansehen'),

-- Level 2: Moderator
(2, 'view_logs', 'Logs ansehen', 'Kann System-Logs einsehen'),
(2, 'view_players', 'Spieler ansehen', 'Kann Spieler-Daten ansehen'),
(2, 'warn_players', 'Spieler verwarnen', 'Kann Spieler verwarnen'),
(2, 'mute_players', 'Spieler stumm schalten', 'Kann Spieler temporär stumm schalten'),

-- Level 3: Game Master
(3, 'view_logs', 'Logs ansehen', 'Kann System-Logs einsehen'),
(3, 'view_players', 'Spieler ansehen', 'Kann Spieler-Daten ansehen'),
(3, 'edit_players', 'Spieler bearbeiten', 'Kann Spieler-Ressourcen und Stats ändern'),
(3, 'give_items', 'Items vergeben', 'Kann Items an Spieler vergeben'),
(3, 'warn_players', 'Spieler verwarnen', 'Kann Spieler verwarnen'),
(3, 'ban_players', 'Spieler sperren', 'Kann Spieler temporär sperren'),

-- Level 5: Administrator
(5, 'view_logs', 'Logs ansehen', 'Kann System-Logs einsehen'),
(5, 'view_players', 'Spieler ansehen', 'Kann Spieler-Daten ansehen'),
(5, 'edit_players', 'Spieler bearbeiten', 'Kann Spieler-Daten ändern'),
(5, 'edit_buildings', 'Gebäude bearbeiten', 'Kann Gebäude-Einstellungen ändern'),
(5, 'edit_items', 'Items bearbeiten', 'Kann Items erstellen und bearbeiten'),
(5, 'system_settings', 'System-Einstellungen', 'Kann System-Einstellungen ändern'),

-- Level 7: Lead Admin
(7, 'manage_admins', 'Admins verwalten', 'Kann Admins bis Level 6 ernennen'),
(7, 'view_logs', 'Logs ansehen', 'Kann System-Logs einsehen'),
(7, 'view_players', 'Spieler ansehen', 'Kann Spieler-Daten ansehen'),
(7, 'edit_players', 'Spieler bearbeiten', 'Kann Spieler-Daten ändern'),
(7, 'system_settings', 'System-Einstellungen', 'Kann System-Einstellungen ändern'),

-- Level 9: Developer
(9, 'database_access', 'Datenbank-Zugriff', 'Direkter Zugriff auf Datenbank'),
(9, 'manage_admins', 'Admins verwalten', 'Kann alle Admins verwalten'),
(9, 'view_logs', 'Logs ansehen', 'Kann System-Logs einsehen'),
(9, 'view_players', 'Spieler ansehen', 'Kann Spieler-Daten ansehen'),
(9, 'edit_players', 'Spieler bearbeiten', 'Kann Spieler-Daten ändern'),

-- Level 10: Owner
(10, 'full_access', 'Vollzugriff', 'Uneingeschränkter Zugriff auf alles');
