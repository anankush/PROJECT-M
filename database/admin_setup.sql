-- database/admin_setup.sql

CREATE TABLE IF NOT EXISTS `security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `action` enum('login_success', 'login_failed', 'admin_login_success', 'admin_login_failed', 'logout', 'password_reset_request', 'password_reset_success', 'user_blocked', 'user_unblocked') NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add status column to users table if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = "users";
SET @columnname = "status";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE table_schema = @dbname
     AND table_name = @tablename
     AND column_name = @columnname) > 0,
  "SELECT 1",
  "ALTER TABLE `users` ADD COLUMN `status` enum('active', 'blocked') DEFAULT 'active'"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
