-- Quick Setup Instructions
-- 1. Open phpMyAdmin
-- 2. Select database: it107_security_sql
-- 3. Go to SQL tab
-- 4. Copy and paste this entire script
-- 5. Click "Go" to execute

-- ============================================
-- BACKUP your current database first!
-- ============================================

-- Step 1: Update password_resets table
-- Drop old table if it exists
DROP TABLE IF EXISTS `password_resets`;

-- Create new password_resets table with all necessary fields
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `otp_attempts` int(11) DEFAULT 0,
  `resend_count` int(11) DEFAULT 0,
  `is_blocked` tinyint(1) DEFAULT 0,
  `blocked_until` datetime DEFAULT NULL,
  `last_resend_time` datetime DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Step 2: Create security_questions table
CREATE TABLE IF NOT EXISTS `security_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `question_1` varchar(255) NOT NULL,
  `answer_1` varchar(255) NOT NULL,
  `question_2` varchar(255) NOT NULL,
  `answer_2` varchar(255) NOT NULL,
  `question_3` varchar(255) NOT NULL,
  `answer_3` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Step 3: Create security_attempts table (for future use)
CREATE TABLE IF NOT EXISTS `security_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `attempts` int(11) DEFAULT 0,
  `is_blocked` tinyint(1) DEFAULT 0,
  `blocked_until` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Verify tables were created
SELECT 'Database setup complete!' as Status;
SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'it107_security_sql' 
AND TABLE_NAME IN ('password_resets', 'security_questions', 'security_attempts');
