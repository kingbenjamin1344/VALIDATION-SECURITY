-- Add security question fields to users table
ALTER TABLE `users` ADD `security_q1` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `users` ADD `security_a1` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `users` ADD `security_q2` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `users` ADD `security_a2` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `users` ADD `security_q3` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `users` ADD `security_a3` VARCHAR(255) DEFAULT NULL;

-- Create password_resets table for OTP
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;