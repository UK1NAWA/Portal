-- Run this in phpMyAdmin SQL tab

-- Add student_id_no to pending_registrations
ALTER TABLE `pending_registrations`
  ADD COLUMN `student_id_no` varchar(30) DEFAULT NULL AFTER `lrn`;

-- student_id_no already exists in users table, just ensure it's there
-- (it was in the original schema so no ALTER needed for users)
