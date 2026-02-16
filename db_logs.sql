-- SQL schema for activity_logs table
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(150) DEFAULT NULL,
  `usertype` VARCHAR(50) NOT NULL DEFAULT 'user',
  `action` VARCHAR(255) NOT NULL,
  `device` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`created_at`),
  INDEX (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Examples:
-- INSERT INTO activity_logs (username, usertype, action, device) VALUES ('admin','admin','login','Chrome on Windows');
-- INSERT INTO activity_logs (username, usertype, action, device) VALUES ('super','superadmin','logout','Edge on Windows');
