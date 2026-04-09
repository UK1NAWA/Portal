-- Migration: pending registrations
-- Run this in your student_portal database in phpMyAdmin

USE `student_portal`;

CREATE TABLE IF NOT EXISTS `pending_registrations` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `fullname`    varchar(100) NOT NULL,
  `email`       varchar(100) NOT NULL,
  `username`    varchar(50)  NOT NULL,
  `password`    varchar(255) NOT NULL,
  `submitted_at` datetime    DEFAULT current_timestamp(),
  `status`      enum('pending','rejected') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
