CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    attempt_time DATETIME NOT NULL,
    INDEX idx_identifier_time (identifier, attempt_time)
);