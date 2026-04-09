-- Migration: add profile columns to users table
-- Run this once in your student_portal database

USE `student_portal`;

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `contact_number` varchar(20)  DEFAULT NULL AFTER `email`,
    ADD COLUMN IF NOT EXISTS `student_id_no`  varchar(30)  DEFAULT NULL AFTER `contact_number`,
    ADD COLUMN IF NOT EXISTS `year_level`     varchar(20)  DEFAULT NULL AFTER `student_id_no`,
    ADD COLUMN IF NOT EXISTS `strand`         varchar(50)  DEFAULT NULL AFTER `year_level`;
