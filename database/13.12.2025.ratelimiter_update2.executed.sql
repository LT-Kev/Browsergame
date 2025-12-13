-- ============================================================================
-- Rate Limiting Tabelle (ersetzt login_attempts)
-- ============================================================================

-- Alte Tabelle umbenennen/löschen falls vorhanden
DROP TABLE IF EXISTS `login_attempts`;

-- Neue universelle Rate-Limit-Tabelle
CREATE TABLE `rate_limits` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `identifier` VARCHAR(255) NOT NULL,
    `type` VARCHAR(50) NOT NULL DEFAULT 'api',
    `attempt_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_identifier_type` (`identifier`, `type`),
    INDEX `idx_attempt_time` (`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kommentar
ALTER TABLE `rate_limits` 
COMMENT = 'Universelle Rate-Limiting-Tabelle für Login, API und andere kritische Aktionen';

-- Beispiel-Daten (optional, zum Testen)
-- INSERT INTO `rate_limits` (`identifier`, `type`) VALUES
-- ('192.168.1.100', 'login'),
-- ('user_123', 'api'),
-- ('192.168.1.101', 'password_reset');