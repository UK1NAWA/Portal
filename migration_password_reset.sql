-- Migration: password reset support
-- Run this in your student_portal database in phpMyAdmin

USE `student_portal`;

-- Forces user to change password on next login (used by admin resets)
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `force_password_change` tinyint(1) NOT NULL DEFAULT 0 AFTER `section`;

-- Secure token table for self-service reset (future email support)
-- Also used to log admin-initiated resets
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `user_id`    int(11)      NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` datetime     NOT NULL,
  `used`       tinyint(1)   NOT NULL DEFAULT 0,
  `created_at` datetime     DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token_hash`),
  KEY `idx_user`  (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
