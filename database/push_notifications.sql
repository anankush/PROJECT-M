-- Push Notification Tables Migration
-- Run this on your database

CREATE TABLE IF NOT EXISTS `push_subscriptions` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT NOT NULL,
    `endpoint`    TEXT NOT NULL,
    `p256dh`      TEXT NOT NULL,
    `auth`        TEXT NOT NULL,
    `user_agent`  VARCHAR(255) DEFAULT NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_ep` (`user_id`, `endpoint`(191)),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `push_preferences` (
    `user_id`          INT PRIMARY KEY,
    `budget_alert`     TINYINT(1) NOT NULL DEFAULT 1,
    `budget_exceeded`  TINYINT(1) NOT NULL DEFAULT 1,
    `savings_goal`     TINYINT(1) NOT NULL DEFAULT 1,
    `monthly_summary`  TINYINT(1) NOT NULL DEFAULT 1,
    `login_alert`      TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
