-- Run this in phpMyAdmin before using audit logs
-- Creates the audit_log table for tracking all important system actions

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `user_id`     int(11)      DEFAULT NULL COMMENT 'NULL for unauthenticated actions',
  `username`    varchar(50)  DEFAULT NULL,
  `role`        varchar(20)  DEFAULT NULL,
  `action`      varchar(100) NOT NULL COMMENT 'e.g. LOGIN, GRADE_UPLOAD, USER_APPROVED',
  `target`      varchar(150) DEFAULT NULL COMMENT 'What was affected e.g. student name, section',
  `detail`      text         DEFAULT NULL COMMENT 'Extra detail about the action',
  `ip_address`  varchar(45)  DEFAULT NULL,
  `created_at`  datetime     NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `audit_log` ADD PRIMARY KEY (`id`);
ALTER TABLE `audit_log` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `audit_log` ADD KEY `idx_user`    (`user_id`);
ALTER TABLE `audit_log` ADD KEY `idx_action`  (`action`);
ALTER TABLE `audit_log` ADD KEY `idx_created` (`created_at`);
