-- Run this in phpMyAdmin before using the updated portal
-- Creates the sessions table for database-based session storage

CREATE TABLE IF NOT EXISTS `php_sessions` (
  `session_id`   varchar(128) NOT NULL,
  `session_data` mediumtext   NOT NULL,
  `last_activity` int(11)     NOT NULL,
  PRIMARY KEY (`session_id`),
  KEY `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
