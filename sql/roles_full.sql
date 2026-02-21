-- roles_full.sql
-- Run this file on your database to add role support, role tables,
-- and ensure OTP/security-question tables/columns exist.

-- Note: `ADD COLUMN IF NOT EXISTS` requires MySQL 8+/MariaDB 10.3+.
-- If your server is older, run the ALTER TABLE statements manually or
-- update the SQL to conditionally check INFORMATION_SCHEMA.

-- 1) Add `role` column to `users` (default 'user')
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `role` VARCHAR(20) NOT NULL DEFAULT 'user';

-- 2) Ensure security question columns exist on `users`
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `security_q1` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `security_a1` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `security_q2` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `security_a2` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `security_q3` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `security_a3` VARCHAR(255) DEFAULT NULL;

-- 3) Create password_resets (OTP) table if not exists
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` int(11) DEFAULT 0,
  `resend_count` int(11) DEFAULT 0,
  `is_blocked` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4) Create role-specific tables
CREATE TABLE IF NOT EXISTS `upper_management` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_upper_management_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `superadmin` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_superadmin_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `admin` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_admin_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5) Optional: populate role tables from current `users.role` values
-- (uncomment and run if you want to sync existing roles into role tables)
-- INSERT IGNORE INTO upper_management (user_id)
--   SELECT id FROM users WHERE role='upper_management';
-- INSERT IGNORE INTO superadmin (user_id)
--   SELECT id FROM users WHERE role='superadmin';
-- INSERT IGNORE INTO admin (user_id)
--   SELECT id FROM users WHERE role='admin';

-- End of file
