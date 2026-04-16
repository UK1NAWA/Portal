-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 16, 2026 at 11:11 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e-tect`
--
CREATE DATABASE IF NOT EXISTS `e-tect` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `e-tect`;
--
-- Database: `phpmyadmin`
--
CREATE DATABASE IF NOT EXISTS `phpmyadmin` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `phpmyadmin`;

-- --------------------------------------------------------

--
-- Table structure for table `pma__bookmark`
--

CREATE TABLE `pma__bookmark` (
  `id` int(10) UNSIGNED NOT NULL,
  `dbase` varchar(255) NOT NULL DEFAULT '',
  `user` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `query` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Bookmarks';

-- --------------------------------------------------------

--
-- Table structure for table `pma__central_columns`
--

CREATE TABLE `pma__central_columns` (
  `db_name` varchar(64) NOT NULL,
  `col_name` varchar(64) NOT NULL,
  `col_type` varchar(64) NOT NULL,
  `col_length` text DEFAULT NULL,
  `col_collation` varchar(64) NOT NULL,
  `col_isNull` tinyint(1) NOT NULL,
  `col_extra` varchar(255) DEFAULT '',
  `col_default` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Central list of columns';

-- --------------------------------------------------------

--
-- Table structure for table `pma__column_info`
--

CREATE TABLE `pma__column_info` (
  `id` int(5) UNSIGNED NOT NULL,
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `column_name` varchar(64) NOT NULL DEFAULT '',
  `comment` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `mimetype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `transformation` varchar(255) NOT NULL DEFAULT '',
  `transformation_options` varchar(255) NOT NULL DEFAULT '',
  `input_transformation` varchar(255) NOT NULL DEFAULT '',
  `input_transformation_options` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Column information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__designer_settings`
--

CREATE TABLE `pma__designer_settings` (
  `username` varchar(64) NOT NULL,
  `settings_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Settings related to Designer';

-- --------------------------------------------------------

--
-- Table structure for table `pma__export_templates`
--

CREATE TABLE `pma__export_templates` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL,
  `export_type` varchar(10) NOT NULL,
  `template_name` varchar(64) NOT NULL,
  `template_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved export templates';

-- --------------------------------------------------------

--
-- Table structure for table `pma__favorite`
--

CREATE TABLE `pma__favorite` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Favorite tables';

-- --------------------------------------------------------

--
-- Table structure for table `pma__history`
--

CREATE TABLE `pma__history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db` varchar(64) NOT NULL DEFAULT '',
  `table` varchar(64) NOT NULL DEFAULT '',
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp(),
  `sqlquery` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='SQL history for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__navigationhiding`
--

CREATE TABLE `pma__navigationhiding` (
  `username` varchar(64) NOT NULL,
  `item_name` varchar(64) NOT NULL,
  `item_type` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Hidden items of navigation tree';

-- --------------------------------------------------------

--
-- Table structure for table `pma__pdf_pages`
--

CREATE TABLE `pma__pdf_pages` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `page_nr` int(10) UNSIGNED NOT NULL,
  `page_descr` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='PDF relation pages for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__recent`
--

CREATE TABLE `pma__recent` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Recently accessed tables';

--
-- Dumping data for table `pma__recent`
--

INSERT INTO `pma__recent` (`username`, `tables`) VALUES
('root', '[{\"db\":\"student_portal\",\"table\":\"users\"},{\"db\":\"student_portal\",\"table\":\"student_grades\"},{\"db\":\"student_portal\",\"table\":\"grade_submissions\"},{\"db\":\"student_portal\",\"table\":\"audit_log\"},{\"db\":\"student_portal\",\"table\":\"time_slots\"},{\"db\":\"student_portal\",\"table\":\"schedules\"},{\"db\":\"student_portal\",\"table\":\"section_schedules\"},{\"db\":\"student-portal\",\"table\":\"users\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `pma__relation`
--

CREATE TABLE `pma__relation` (
  `master_db` varchar(64) NOT NULL DEFAULT '',
  `master_table` varchar(64) NOT NULL DEFAULT '',
  `master_field` varchar(64) NOT NULL DEFAULT '',
  `foreign_db` varchar(64) NOT NULL DEFAULT '',
  `foreign_table` varchar(64) NOT NULL DEFAULT '',
  `foreign_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Relation table';

-- --------------------------------------------------------

--
-- Table structure for table `pma__savedsearches`
--

CREATE TABLE `pma__savedsearches` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `search_name` varchar(64) NOT NULL DEFAULT '',
  `search_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved searches';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_coords`
--

CREATE TABLE `pma__table_coords` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `pdf_page_number` int(11) NOT NULL DEFAULT 0,
  `x` float UNSIGNED NOT NULL DEFAULT 0,
  `y` float UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table coordinates for phpMyAdmin PDF output';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_info`
--

CREATE TABLE `pma__table_info` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `display_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_uiprefs`
--

CREATE TABLE `pma__table_uiprefs` (
  `username` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `prefs` text NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Tables'' UI preferences';

--
-- Dumping data for table `pma__table_uiprefs`
--

INSERT INTO `pma__table_uiprefs` (`username`, `db_name`, `table_name`, `prefs`, `last_update`) VALUES
('root', 'student_portal', 'section_schedules', '{\"sorted_col\":\"`section_schedules`.`time_slot_id` ASC\"}', '2026-02-22 02:02:51'),
('root', 'student_portal', 'users', '{\"sorted_col\":\"`password` DESC\"}', '2026-02-24 13:27:17');

-- --------------------------------------------------------

--
-- Table structure for table `pma__tracking`
--

CREATE TABLE `pma__tracking` (
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `version` int(10) UNSIGNED NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `schema_snapshot` text NOT NULL,
  `schema_sql` text DEFAULT NULL,
  `data_sql` longtext DEFAULT NULL,
  `tracking` set('UPDATE','REPLACE','INSERT','DELETE','TRUNCATE','CREATE DATABASE','ALTER DATABASE','DROP DATABASE','CREATE TABLE','ALTER TABLE','RENAME TABLE','DROP TABLE','CREATE INDEX','DROP INDEX','CREATE VIEW','ALTER VIEW','DROP VIEW') DEFAULT NULL,
  `tracking_active` int(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Database changes tracking for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__userconfig`
--

CREATE TABLE `pma__userconfig` (
  `username` varchar(64) NOT NULL,
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `config_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User preferences storage for phpMyAdmin';

--
-- Dumping data for table `pma__userconfig`
--

INSERT INTO `pma__userconfig` (`username`, `timevalue`, `config_data`) VALUES
('root', '2026-04-16 09:10:03', '{\"Console\\/Mode\":\"collapse\"}');

-- --------------------------------------------------------

--
-- Table structure for table `pma__usergroups`
--

CREATE TABLE `pma__usergroups` (
  `usergroup` varchar(64) NOT NULL,
  `tab` varchar(64) NOT NULL,
  `allowed` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User groups with configured menu items';

-- --------------------------------------------------------

--
-- Table structure for table `pma__users`
--

CREATE TABLE `pma__users` (
  `username` varchar(64) NOT NULL,
  `usergroup` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Users and their assignments to user groups';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pma__central_columns`
--
ALTER TABLE `pma__central_columns`
  ADD PRIMARY KEY (`db_name`,`col_name`);

--
-- Indexes for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`);

--
-- Indexes for table `pma__designer_settings`
--
ALTER TABLE `pma__designer_settings`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`);

--
-- Indexes for table `pma__favorite`
--
ALTER TABLE `pma__favorite`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__history`
--
ALTER TABLE `pma__history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`,`db`,`table`,`timevalue`);

--
-- Indexes for table `pma__navigationhiding`
--
ALTER TABLE `pma__navigationhiding`
  ADD PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`);

--
-- Indexes for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  ADD PRIMARY KEY (`page_nr`),
  ADD KEY `db_name` (`db_name`);

--
-- Indexes for table `pma__recent`
--
ALTER TABLE `pma__recent`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__relation`
--
ALTER TABLE `pma__relation`
  ADD PRIMARY KEY (`master_db`,`master_table`,`master_field`),
  ADD KEY `foreign_field` (`foreign_db`,`foreign_table`);

--
-- Indexes for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`);

--
-- Indexes for table `pma__table_coords`
--
ALTER TABLE `pma__table_coords`
  ADD PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`);

--
-- Indexes for table `pma__table_info`
--
ALTER TABLE `pma__table_info`
  ADD PRIMARY KEY (`db_name`,`table_name`);

--
-- Indexes for table `pma__table_uiprefs`
--
ALTER TABLE `pma__table_uiprefs`
  ADD PRIMARY KEY (`username`,`db_name`,`table_name`);

--
-- Indexes for table `pma__tracking`
--
ALTER TABLE `pma__tracking`
  ADD PRIMARY KEY (`db_name`,`table_name`,`version`);

--
-- Indexes for table `pma__userconfig`
--
ALTER TABLE `pma__userconfig`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__usergroups`
--
ALTER TABLE `pma__usergroups`
  ADD PRIMARY KEY (`usergroup`,`tab`,`allowed`);

--
-- Indexes for table `pma__users`
--
ALTER TABLE `pma__users`
  ADD PRIMARY KEY (`username`,`usergroup`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__history`
--
ALTER TABLE `pma__history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  MODIFY `page_nr` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- Database: `student_portal`
--
CREATE DATABASE IF NOT EXISTS `student_portal` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `student_portal`;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `section` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `posted_by` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `section`, `message`, `posted_by`, `created_at`) VALUES
(2, 'schedule ts', 'yessir', 'Can Harold', '2026-03-20 11:06:12'),
(3, 'schedule ts', 'hi', 'Can Harold', '2026-03-20 11:06:29');

-- --------------------------------------------------------

--
-- Table structure for table `archived_attendance`
--

CREATE TABLE `archived_attendance` (
  `id` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `lrn` varchar(12) DEFAULT NULL,
  `section` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late') NOT NULL,
  `archived_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archived_grades`
--

CREATE TABLE `archived_grades` (
  `id` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `lrn` varchar(12) DEFAULT NULL,
  `section` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `quarter` enum('Q1','Q2','Q3','Q4') NOT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `remarks` varchar(50) DEFAULT NULL,
  `archived_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `section` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late') NOT NULL DEFAULT 'present',
  `marked_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `section`, `date`, `status`, `marked_by`) VALUES
(1, 1, 'schedule ts', '2026-03-12', 'present', 17),
(2, 1, 'schedule ts', '2026-03-18', 'absent', 17);

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `target` varchar(150) DEFAULT NULL,
  `detail` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `username`, `role`, `action`, `target`, `detail`, `ip_address`, `created_at`) VALUES
(1, 1, 'works', 'student', 'LOGIN', 'works', 'Role: student', '::1', '2026-03-27 11:23:35'),
(2, 1, 'works', 'student', 'LOGOUT', 'works', '', '::1', '2026-03-27 11:24:22'),
(3, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-03-27 11:24:27'),
(4, 17, 'Har0ld', 'teacher', 'LOGOUT', 'Har0ld', '', '::1', '2026-03-27 11:28:04'),
(5, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '::1', '2026-03-27 11:28:10'),
(6, 22, 'Adm1n', 'admin', 'USER_APPROVED', 'Y0LOO', 'LRN: 123456789011, Section: ', '::1', '2026-03-27 11:31:35'),
(7, 22, 'Adm1n', 'admin', 'LOGOUT', 'Adm1n', '', '::1', '2026-03-27 11:38:53'),
(8, 1, 'works', 'student', 'LOGIN', 'works', 'Role: student', '192.168.1.7', '2026-03-27 17:18:09'),
(9, 1, 'works', 'student', 'LOGOUT', 'works', '', '192.168.1.7', '2026-03-27 17:25:32'),
(10, 17, 'har0ld', 'teacher', 'LOGIN', 'har0ld', 'Role: teacher', '192.168.1.7', '2026-03-27 17:25:47'),
(11, 17, 'har0ld', 'teacher', 'LOGOUT', 'har0ld', '', '192.168.1.7', '2026-03-27 17:26:35'),
(12, 22, 'adm1n', 'admin', 'LOGIN', 'adm1n', 'Role: admin', '192.168.1.7', '2026-03-27 17:26:44'),
(13, 22, 'adm1n', 'admin', 'LOGOUT', 'adm1n', '', '192.168.1.7', '2026-03-27 17:27:32'),
(14, 17, 'har0ld', 'teacher', 'LOGIN', 'har0ld', 'Role: teacher', '192.168.1.7', '2026-03-27 17:27:42'),
(15, 1, 'works', 'student', 'LOGIN', 'works', 'Role: student', '::1', '2026-03-28 16:42:56'),
(16, 1, 'works', 'student', 'LOGIN', 'works', 'Role: student', '::1', '2026-03-28 16:44:11'),
(17, 1, 'works', 'student', 'LOGOUT', 'works', '', '::1', '2026-03-28 16:44:14'),
(18, 1, 'works', 'student', 'LOGIN', 'works', 'Role: student', '::1', '2026-03-28 16:50:28'),
(19, 1, 'works', 'student', 'LOGOUT', 'works', '', '::1', '2026-03-28 16:50:31'),
(20, 1, 'works', 'student', 'LOGIN', 'works', 'Role: student', '::1', '2026-03-28 16:51:51'),
(21, 1, 'works', 'student', 'LOGOUT', 'works', '', '::1', '2026-03-28 16:52:33'),
(22, 1, 'works', 'student', 'LOGIN', 'works', 'Role: student', '::1', '2026-03-28 16:54:50'),
(23, 1, 'works', 'student', 'LOGOUT', 'works', '', '::1', '2026-03-28 16:55:39'),
(24, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '::1', '2026-03-29 13:58:02'),
(25, 22, 'Adm1n', 'admin', 'USER_APPROVED', 'finaltest', 'LRN: 312332155332, Section: schedule ts', '::1', '2026-03-29 13:59:06'),
(26, 22, 'Adm1n', 'admin', 'LOGOUT', 'Adm1n', '', '::1', '2026-03-29 14:04:46'),
(27, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-03-29 14:04:50'),
(28, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '192.168.1.11', '2026-03-29 14:18:18'),
(29, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-02 16:31:43'),
(30, 17, 'Har0ld', 'teacher', 'LOGOUT', 'Har0ld', '', '::1', '2026-04-02 16:32:03'),
(31, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-02 16:32:06'),
(32, 17, 'Har0ld', 'teacher', 'LOGOUT', 'Har0ld', '', '::1', '2026-04-02 16:32:08'),
(33, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '::1', '2026-04-02 16:32:12'),
(34, 22, 'Adm1n', 'admin', 'LOGOUT', 'Adm1n', '', '::1', '2026-04-02 16:32:20'),
(35, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-02 16:32:23'),
(36, 17, 'Har0ld', 'teacher', 'LOGOUT', 'Har0ld', '', '::1', '2026-04-02 16:33:27'),
(37, 1, 'works', 'student', 'LOGIN', 'works', 'Role: student', '::1', '2026-04-02 16:33:32'),
(38, 1, 'works', 'student', 'LOGOUT', 'works', '', '::1', '2026-04-02 16:33:49'),
(39, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-02 16:33:54'),
(40, 17, 'Har0ld', 'teacher', 'LOGOUT', 'Har0ld', '', '::1', '2026-04-02 16:35:58'),
(41, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '::1', '2026-04-02 16:36:02'),
(42, 22, 'Adm1n', 'admin', 'LOGOUT', 'Adm1n', '', '::1', '2026-04-02 16:36:05'),
(43, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-02 16:36:08'),
(44, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-02 16:37:52'),
(45, 1, 'works', 'student', 'LOGIN', 'works', 'Role: student', '::1', '2026-04-05 14:25:37'),
(46, 1, 'works', 'student', 'LOGOUT', 'works', '', '::1', '2026-04-05 14:25:51'),
(47, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-05 14:26:04'),
(48, 1, 'works', 'student', 'LOGIN', 'works', 'Role: student', '::1', '2026-04-05 14:31:28'),
(49, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-05 14:32:34'),
(50, 17, 'Har0ld', 'teacher', 'LOGOUT', 'Har0ld', '', '::1', '2026-04-05 14:33:06'),
(51, 1, 'works', 'student', 'LOGIN', 'works', 'Role: student', '::1', '2026-04-05 14:33:11'),
(52, 1, 'works', 'student', 'LOGOUT', 'works', '', '::1', '2026-04-05 14:33:46'),
(53, 1, 'works', 'student', 'LOGIN', 'works', 'Role: student', '::1', '2026-04-05 14:33:56'),
(54, 1, 'works', 'student', 'LOGOUT', 'works', '', '::1', '2026-04-05 14:34:42'),
(55, NULL, NULL, NULL, 'LOGIN_FAILED', 'gradetest', 'Invalid credentials', '::1', '2026-04-05 14:34:53'),
(56, 28, 'gradetest', 'student', 'LOGIN', 'gradetest', 'Role: student', '::1', '2026-04-05 14:35:35'),
(57, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-05 15:30:49'),
(58, 1, 'works', 'student', 'LOGIN', 'works', 'Role: student', '::1', '2026-04-05 15:31:59'),
(59, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-05 15:53:49'),
(60, 17, 'Har0ld', 'teacher', 'LOGOUT', 'Har0ld', '', '::1', '2026-04-05 15:55:34'),
(61, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '::1', '2026-04-05 15:55:38'),
(62, 22, 'Adm1n', 'admin', 'LOGOUT', 'Adm1n', '', '::1', '2026-04-05 15:59:48'),
(63, 1, 'works', 'student', 'LOGIN', 'works', 'Role: student', '::1', '2026-04-05 15:59:52'),
(64, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-07 16:11:34'),
(65, 1, 'works', 'student', 'LOGIN', 'works', 'Role: student', '::1', '2026-04-07 16:18:09'),
(66, 1, 'works', 'student', 'LOGOUT', 'works', '', '::1', '2026-04-07 16:26:05'),
(67, 1, 'works', 'student', 'LOGIN', 'works', 'Role: student', '::1', '2026-04-07 16:26:09'),
(68, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-08 11:57:42'),
(69, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-11 20:31:16'),
(70, 17, 'Har0ld', 'teacher', 'LOGOUT', 'Har0ld', '', '::1', '2026-04-11 20:31:22'),
(71, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '::1', '2026-04-11 20:31:26'),
(72, 22, 'Adm1n', 'admin', 'LOGOUT', 'Adm1n', '', '::1', '2026-04-11 20:34:11'),
(73, 1, 'works', 'student', 'LOGIN', 'works', 'Role: student', '::1', '2026-04-11 20:34:15'),
(74, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '::1', '2026-04-12 12:55:50'),
(75, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '::1', '2026-04-12 18:04:00'),
(76, 22, 'Adm1n', 'admin', 'LOGIN_FAILED', 'gradetest', 'Invalid credentials', '::1', '2026-04-12 18:23:45'),
(77, 22, 'Adm1n', 'admin', 'USER_APPROVED', 'GradeTest', 'LRN: 312315521312, Section: ', '::1', '2026-04-12 18:25:01'),
(78, 31, 'test', 'student', 'LOGIN', 'test', 'Role: student', '::1', '2026-04-12 18:25:20'),
(79, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '::1', '2026-04-12 18:25:31'),
(80, 22, 'Adm1n', 'admin', 'LOGOUT', 'Adm1n', '', '::1', '2026-04-12 18:26:25'),
(81, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-12 18:26:30'),
(82, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '::1', '2026-04-12 18:26:59'),
(83, 22, 'Adm1n', 'admin', 'USER_SECTION_ASSIGNED', 'User ID: 31', 'Section: ', '::1', '2026-04-12 18:27:49'),
(84, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-12 18:29:08'),
(85, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '::1', '2026-04-12 18:30:08'),
(86, 22, 'Adm1n', 'admin', 'LOGIN_FAILED', 'Har0ld', 'Invalid credentials', '::1', '2026-04-12 18:34:06'),
(87, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-12 18:34:10'),
(88, 17, 'Har0ld', 'teacher', 'GRADE_UPLOAD', 'schedule ts', 'Q1: 0 record(s) saved', '::1', '2026-04-12 18:34:20'),
(89, 31, 'Test', 'student', 'LOGIN', 'Test', 'Role: student', '::1', '2026-04-12 18:36:14'),
(90, 31, 'Test', 'student', 'LOGIN_FAILED', 'Har0ld', 'Invalid credentials', '::1', '2026-04-12 18:57:06'),
(91, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-12 18:57:09'),
(92, 17, 'Har0ld', 'teacher', 'GRADE_DELETED', 'C:\\xampp\\htdocs/grade_uploads_private/grades_schedule_ts_Q1_1775990060.xlsx', 'Submission deleted', '::1', '2026-04-12 18:58:16'),
(93, 17, 'Har0ld', 'teacher', 'GRADE_UPLOAD', 'schedule ts', 'Q1: 0 record(s) saved', '::1', '2026-04-12 18:58:28'),
(94, 31, 'Test', 'student', 'LOGIN', 'Test', 'Role: student', '::1', '2026-04-12 18:58:47'),
(95, 31, 'Test', 'student', 'LOGOUT', 'Test', '', '::1', '2026-04-12 18:58:50'),
(96, 31, 'test', 'student', 'LOGIN', 'test', 'Role: student', '::1', '2026-04-12 18:58:54'),
(97, 31, 'test', 'student', 'LOGOUT', 'test', '', '::1', '2026-04-12 19:00:05'),
(98, 31, 'Test', 'student', 'LOGIN', 'Test', 'Role: student', '::1', '2026-04-12 19:00:09'),
(99, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '::1', '2026-04-12 19:00:25'),
(100, 22, 'Adm1n', 'admin', 'USER_SECTION_ASSIGNED', 'User ID: 31', 'Section: ', '::1', '2026-04-12 19:00:34'),
(101, 22, 'Adm1n', 'admin', 'USER_SECTION_ASSIGNED', 'User ID: 31', 'Section: ', '::1', '2026-04-12 19:01:21'),
(102, 22, 'Adm1n', 'admin', 'USER_SECTION_ASSIGNED', 'User ID: 31', 'Section: ', '::1', '2026-04-12 19:02:15'),
(103, 31, 'Test', 'student', 'LOGIN', 'Test', 'Role: student', '::1', '2026-04-12 19:05:58'),
(104, 31, 'Test', 'student', 'LOGIN', 'Test', 'Role: student', '::1', '2026-04-12 19:08:03'),
(105, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-12 19:09:03'),
(106, 17, 'Har0ld', 'teacher', 'GRADE_DELETED', 'C:\\xampp\\htdocs/grade_uploads_private/grades_schedule_ts_Q1_1775991508.csv', 'Submission deleted', '::1', '2026-04-12 19:09:08'),
(107, 17, 'Har0ld', 'teacher', 'GRADE_UPLOAD', 'schedule ts', 'Q1: 0 record(s) saved', '::1', '2026-04-12 19:09:19'),
(108, 31, 'Test', 'student', 'LOGIN', 'Test', 'Role: student', '::1', '2026-04-12 19:09:36'),
(109, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-12 19:09:52'),
(110, 17, 'Har0ld', 'teacher', 'LOGOUT', 'Har0ld', '', '::1', '2026-04-12 19:13:49'),
(111, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '::1', '2026-04-12 19:13:53'),
(112, 22, 'Adm1n', 'admin', 'USER_APPROVED', 'Grade', 'LRN: 111111111110, Section: ', '::1', '2026-04-12 19:14:15'),
(113, 22, 'Adm1n', 'admin', 'USER_SECTION_ASSIGNED', 'User ID: 32', 'Section: ', '::1', '2026-04-12 19:14:28'),
(114, 32, 'Grade', 'student', 'LOGIN', 'Grade', 'Role: student', '::1', '2026-04-12 19:15:00'),
(115, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-12 19:16:18'),
(116, 17, 'Har0ld', 'teacher', 'GRADE_UPLOAD', 'schedule ts', 'Q1: 0 record(s) saved', '::1', '2026-04-12 19:16:31'),
(117, 32, 'Grade', 'student', 'LOGIN', 'Grade', 'Role: student', '::1', '2026-04-12 19:17:09'),
(118, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-12 19:27:53'),
(119, 17, 'Har0ld', 'teacher', 'GRADE_DELETED', 'C:\\xampp\\htdocs/grade_uploads_private/grades_schedule_ts_Q1_1775992591.csv', 'Submission deleted', '::1', '2026-04-12 19:27:57'),
(120, 17, 'Har0ld', 'teacher', 'GRADE_UPLOAD', 'schedule ts', 'Q1: 3 record(s) saved', '::1', '2026-04-12 19:28:06'),
(121, 17, 'Har0ld', 'teacher', 'GRADE_DELETED', 'C:\\xampp\\htdocs/grade_uploads_private/grades_schedule_ts_Q1_1775993286.xlsx', 'Submission deleted', '::1', '2026-04-12 19:28:42'),
(122, 17, 'Har0ld', 'teacher', 'GRADE_UPLOAD', 'schedule ts', 'Q1: 4 record(s) saved', '::1', '2026-04-12 19:29:01'),
(123, 32, 'Grade', 'student', 'LOGIN', 'Grade', 'Role: student', '::1', '2026-04-12 19:29:20'),
(124, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-12 19:38:14'),
(125, 17, 'Har0ld', 'teacher', 'LOGOUT', 'Har0ld', '', '::1', '2026-04-12 19:38:32'),
(126, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-12 19:41:24'),
(127, 17, 'Har0ld', 'teacher', 'LOGOUT', 'Har0ld', '', '::1', '2026-04-12 19:41:26'),
(128, 17, 'Har0ld', 'teacher', 'LOGIN', 'Har0ld', 'Role: teacher', '::1', '2026-04-12 19:42:46'),
(129, 17, 'Har0ld', 'teacher', 'LOGOUT', 'Har0ld', '', '::1', '2026-04-12 19:42:48'),
(130, NULL, NULL, NULL, 'LOGOUT', 'unknown', '', '::1', '2026-04-12 19:42:59'),
(131, 1, 'works', 'student', 'LOGIN', 'works', 'Role: student', '::1', '2026-04-12 19:43:10'),
(132, 1, 'works', 'student', 'LOGOUT', 'works', '', '::1', '2026-04-12 19:43:15'),
(133, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '::1', '2026-04-14 14:01:19'),
(134, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '::1', '2026-04-14 14:08:23'),
(135, 22, 'Adm1n', 'admin', 'USER_APPROVED', 'strandtest', 'LRN: 134213125212, Section: ', '::1', '2026-04-14 14:09:30'),
(136, 33, 'strand', 'student', 'LOGIN', 'strand', 'Role: student', '::1', '2026-04-14 14:10:00'),
(137, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '::1', '2026-04-14 14:10:08'),
(138, 22, 'Adm1n', 'admin', 'LOGOUT', 'Adm1n', '', '::1', '2026-04-14 14:26:36'),
(139, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '::1', '2026-04-14 14:26:40'),
(140, 22, 'Adm1n', 'admin', 'LOGOUT', 'Adm1n', '', '::1', '2026-04-14 14:30:35'),
(141, 22, 'Adm1n', 'admin', 'LOGIN', 'Adm1n', 'Role: admin', '::1', '2026-04-14 14:31:28'),
(142, 22, 'Adm1n', 'admin', 'USER_APPROVED', 'StemStudent', 'LRN: 135123321233, Section: ', '::1', '2026-04-14 14:31:36'),
(143, 22, 'Adm1n', 'admin', 'USER_APPROVED', 'TVL', 'LRN: 312312131232, Section: ', '::1', '2026-04-14 14:34:45'),
(144, 22, 'Adm1n', 'admin', 'USER_APPROVED', 'DUWATVL', 'LRN: 312312131239, Section: ', '::1', '2026-04-14 14:34:49'),
(145, 22, 'Adm1n', 'admin', 'USER_SECTION_ASSIGNED', 'User ID: 33', 'Section: ', '::1', '2026-04-14 14:37:47');

-- --------------------------------------------------------

--
-- Table structure for table `grade_submissions`
--

CREATE TABLE `grade_submissions` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `section` varchar(50) NOT NULL,
  `quarter` enum('Q1','Q2','Q3','Q4') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `status` enum('submitted','locked') DEFAULT 'submitted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grade_submissions`
--

INSERT INTO `grade_submissions` (`id`, `teacher_id`, `section`, `quarter`, `file_path`, `submitted_at`, `status`) VALUES
(10, 17, 'schedule ts', 'Q1', 'C:\\xampp\\htdocs/grade_uploads_private/grades_schedule_ts_Q1_1775993341.xlsx', '2026-04-12 19:29:01', 'submitted');

-- --------------------------------------------------------

--
-- Table structure for table `grading_periods`
--

CREATE TABLE `grading_periods` (
  `id` int(11) NOT NULL,
  `quarter` enum('Q1','Q2','Q3','Q4') NOT NULL,
  `school_year` varchar(20) NOT NULL DEFAULT '2025-2026',
  `is_open` tinyint(1) NOT NULL DEFAULT 0,
  `opened_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grading_periods`
--

INSERT INTO `grading_periods` (`id`, `quarter`, `school_year`, `is_open`, `opened_at`) VALUES
(1, 'Q1', '2025-2026', 1, '2026-03-11 09:04:38'),
(2, 'Q2', '2025-2026', 0, NULL),
(3, 'Q3', '2025-2026', 0, NULL),
(4, 'Q4', '2025-2026', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pending_registrations`
--

CREATE TABLE `pending_registrations` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `status` enum('pending','rejected') NOT NULL DEFAULT 'pending',
  `lrn` varchar(12) DEFAULT NULL COMMENT 'LRN provided by student at registration (unverified)',
  `student_id_no` varchar(30) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `strand` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_registrations`
--

INSERT INTO `pending_registrations` (`id`, `fullname`, `email`, `username`, `password`, `submitted_at`, `status`, `lrn`, `student_id_no`, `year_level`, `strand`) VALUES
(1, 'testing testing', 'aron123@gmail.com', 'testing0', '$2y$10$HLii1UXb.rutNLVKvO.7I.7kSkIs2AOmzMSSQikt1.rpbd.whrtPe', '2026-03-18 05:51:32', 'rejected', NULL, NULL, NULL, NULL),
(2, 'lrntesting', 'lrn@gmail.com', 'lrn', '$2y$10$PS0VFyHGUiK9AE.aOnebH.G/j98Gjhuo6SQoxaMLgwoFUYYfJikI2', '2026-03-19 11:32:59', 'rejected', NULL, NULL, NULL, NULL),
(3, 'lrntesting2', 'lrn2@gmail.com', 'lrn2', '$2y$10$UmgUnAZvYzLqV4OFnCDyxe8PB4NqHnLZY8/pmqMJihw7MYxYujp/m', '2026-03-19 11:35:32', 'rejected', NULL, NULL, NULL, NULL),
(8, 'finaltest1', 'final1@gmail.com', 'final1', '$2y$10$29OnZi1nlLi.Yk2ehFXDVeuQEazxWaDpslTyolvqpARVD3fMdnoE.', '2026-03-29 14:02:15', 'pending', NULL, '1111111111111111', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `php_sessions`
--

CREATE TABLE `php_sessions` (
  `session_id` varchar(128) NOT NULL,
  `session_data` mediumtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `php_sessions`
--

INSERT INTO `php_sessions` (`session_id`, `session_data`, `last_activity`) VALUES
('5bb7uemrugk2uohilvncfas08r', 'loggedIn|b:1;username|s:5:\"Adm1n\";fullname|s:11:\"AdminHarold\";id|i:22;role|s:5:\"admin\";section|N;last_activity|i:1776149333;csrf_token|s:64:\"1ab7643b8e40154e0ed65068cf85302664103c6b59521abafe292900c0240130\";', 1776149333),
('f9adhlmc981jqjt2341e641tm6', '', 1775994208),
('iuskp5la1hintnm82cfcm1cs81', 'loggedIn|b:1;username|s:5:\"Adm1n\";fullname|s:11:\"AdminHarold\";id|i:22;role|s:5:\"admin\";section|N;last_activity|i:1775972329;csrf_token|s:64:\"627aa9d3c2d63d66c3332df7bcbdaac2249fccf253cfe39e65d98d19c5fa1b72\";', 1775972329),
('t0bgh2a8n9co2dr6ced25j0l5g', 'loggedIn|b:1;username|s:5:\"works\";fullname|s:13:\"canuto harold\";id|i:1;role|s:7:\"student\";section|s:11:\"schedule ts\";last_activity|i:1775910855;', 1775910855);

-- --------------------------------------------------------

--
-- Table structure for table `schedule_images`
--

CREATE TABLE `schedule_images` (
  `id` int(11) NOT NULL,
  `section` varchar(100) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `grade_level` varchar(20) DEFAULT NULL,
  `strand` varchar(50) DEFAULT NULL,
  `section_name` varchar(50) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `grade_level`, `strand`, `section_name`, `teacher_id`) VALUES
(10, 'Grade 12', 'STEM', 'schedule ts', 17),
(11, 'Grade 12', 'STEM', 'Stem', 17),
(12, 'Grade 12', 'ABM', 'ABM', 17),
(13, 'Grade 12', 'HUMSS', 'HUMMS', 17),
(14, 'Grade 11', 'TVL', 'SECTION-TVL', 17);

-- --------------------------------------------------------

--
-- Table structure for table `section_schedules`
--

CREATE TABLE `section_schedules` (
  `id` int(11) NOT NULL,
  `section` varchar(50) DEFAULT NULL,
  `day` varchar(20) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `instructor` varchar(100) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `time_slot_id` int(11) DEFAULT NULL,
  `status` enum('draft','published') DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `section_schedules`
--

INSERT INTO `section_schedules` (`id`, `section`, `day`, `subject`, `instructor`, `room`, `time_slot_id`, `status`) VALUES
(55, 'schedule ts', 'Mon', 'ICT-PROGRAMMING', 'Tacadenae', '22', 1, 'published'),
(56, 'schedule ts', 'Mon', 'MATH', 'Tacadenae', '211', 2, 'published'),
(57, 'schedule ts', 'Tue', 'MATH', 'dsa', '2121', 1, 'published'),
(58, 'schedule ts', 'Wed', 'MATH', '42133', '21211', 1, 'published'),
(59, 'schedule ts', 'Thu', 'yessir', 'fasd', 'dadadada', 1, 'published'),
(60, 'schedule ts', 'Fri', 'dasd', 'fafa', '21', 1, 'published'),
(61, 'schedule ts', 'Sat', 'ICT-PROGRAMMING', 'dasd', '42133', 1, 'published'),
(62, 'schedule ts', 'Tue', 'math', 'santa', '2133', 4, 'published');

-- --------------------------------------------------------

--
-- Table structure for table `student_grades`
--

CREATE TABLE `student_grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `section` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `quarter` enum('Q1','Q2','Q3','Q4') NOT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `remarks` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_grades`
--

INSERT INTO `student_grades` (`id`, `student_id`, `section`, `subject`, `quarter`, `grade`, `remarks`) VALUES
(1, 28, 'schedule ts', 'Mathematics', 'Q1', 88.00, 'Passed'),
(3, 31, 'schedule ts', 'Mathematics', 'Q1', 88.00, 'Passed'),
(4, 31, 'schedule ts', 'English', 'Q1', 72.00, 'Failed'),
(5, 31, 'schedule ts', 'Science', 'Q1', 91.00, 'Passed'),
(9, 32, 'schedule ts', 'Mathematics', 'Q1', 92.00, 'Passed');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `section` varchar(50) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('high','mid','low') NOT NULL DEFAULT 'mid',
  `posted_by` int(11) NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `file_path` varchar(300) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `section`, `title`, `description`, `due_date`, `priority`, `posted_by`, `link`, `file_path`, `created_at`) VALUES
(1, 'schedule ts', 'fast', 'yes', NULL, 'mid', 17, NULL, NULL, '2026-03-12 07:42:07'),
(2, 'schedule ts', 'Test', '', NULL, 'mid', 17, 'https://classroom.google.com/u/0/c/ODU4NzY0ODgyMjgx', NULL, '2026-04-05 14:31:14'),
(3, 'schedule ts', 'FileTest', '', NULL, 'mid', 17, NULL, 'task_files/b5c4caa450a1fb73_Trollface.png', '2026-04-05 14:32:59'),
(4, 'schedule ts', 'yes', 'yesso', '2026-07-04', 'mid', 17, NULL, NULL, '2026-04-05 15:30:59');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_schedule`
--

CREATE TABLE `teacher_schedule` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `day` varchar(10) NOT NULL,
  `time_slot_id` int(11) NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL,
  `label` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `label`) VALUES
(1, '7:30 - 8:30'),
(2, '8:30 - 9:30'),
(3, '9:30 - 10:30'),
(4, '10:30 - 11:30'),
(5, '11:30 - 12:30'),
(6, '1:30 - 2:30'),
(7, '2:30 - 3:30'),
(8, '5:30 - 6:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `student_id_no` varchar(30) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `strand` varchar(50) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'student',
  `section` varchar(50) DEFAULT NULL,
  `force_password_change` tinyint(1) NOT NULL DEFAULT 0,
  `lrn` varchar(12) DEFAULT NULL COMMENT 'DepEd Learner Reference Number (12 digits)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `contact_number`, `student_id_no`, `year_level`, `strand`, `username`, `password`, `role`, `section`, `force_password_change`, `lrn`) VALUES
(1, 'canuto harold', 'aron@gmail.com', '09311826886', NULL, NULL, NULL, 'works', '$2y$10$jgF2XlvO8PT4.cs6UnQSJ.2o9C/XkzS3IxbySdsLkZn0CXRdF6UvS', 'student', 'schedule ts', 0, NULL),
(17, 'Can Harold', 'aronc1nut0@gmail.com', NULL, NULL, NULL, NULL, 'Har0ld', '$2y$10$ykSSGyfmTwG358tQWWWy1eyp0q6BmB3Oc4ZgG3ICc/C6bJs5Izjhu', 'teacher', NULL, 0, NULL),
(22, 'AdminHarold', 'admin@gmail.com', NULL, NULL, NULL, NULL, 'Adm1n', '$2y$10$.u92MSvRwdft4F3PT5sXO.z8D1l8.S/cP.A6rBwBNpabpH4XCOlg2', 'admin', NULL, 0, NULL),
(31, 'GradeTest', 'aronc1nut0@gmail.com', NULL, '3124521', NULL, NULL, 'Test', '$2y$10$uQ1fW3G.KG6KtgAfAZTu1uScc.Jc.hPoukKaLCMkiY043EOS3Bqsa', 'student', 'schedule ts', 0, '312315521312'),
(32, 'Grade', 'grade1@gmail.com', NULL, '1602321', NULL, NULL, 'Grade', '$2y$10$hDCp5fZvfhPdDZAQQJfNpeu7EuQ5AGp25EQeWHPSEnvMaYcUwEANO', 'student', 'schedule ts', 0, '111111111110'),
(33, 'strandtest', 'aronc1nut0@gmail.com', NULL, '1603213', 'Grade 12', 'ICT', 'strand', '$2y$10$tZg1UHUHqCgNwG7bwTcAIOxyvRCanL7YRUhuIqhpJhP2CBvHvRtJa', 'student', 'SECTION-TVL', 0, '134213125212'),
(34, 'StemStudent', 'aronc1nut0@gmail.com', NULL, '1602931', 'Grade 12', 'STEM', 'Stem', '$2y$10$4P8W/7TFuMJwEBvQroamSuEqNFfw1pduoSyF.EkdISuaglUO/c94e', 'student', 'Stem', 0, '135123321233'),
(35, 'TVL', 'aronc1nut0@gmail.com', NULL, '1332113', 'Grade 11', 'TVL', 'TVL1', '$2y$10$LfP1Nl6mOkMqbWE35.etyuZTTLVulAcSEi76GW7Qj1NYJQlROniV6', 'student', 'SECTION-TVL', 0, '312312131232'),
(36, 'DUWATVL', 'aronc1nut0@gmail.com', NULL, '1332132', 'Grade 11', 'TVL', 'TVL2', '$2y$10$2kkzhXmTqZVV.80MC4pmMuSPv40fekU0jZtdThaoJPGrpq9tEwJGu', 'student', 'SECTION-TVL', 0, '312312131239');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archived_attendance`
--
ALTER TABLE `archived_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sy` (`school_year`),
  ADD KEY `idx_student` (`student_id`);

--
-- Indexes for table `archived_grades`
--
ALTER TABLE `archived_grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sy` (`school_year`),
  ADD KEY `idx_student` (`student_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `one_per_day` (`student_id`,`date`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `grade_submissions`
--
ALTER TABLE `grade_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `one_per_section_quarter` (`section`,`quarter`);

--
-- Indexes for table `grading_periods`
--
ALTER TABLE `grading_periods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quarter_year` (`quarter`,`school_year`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token` (`token_hash`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `pending_registrations`
--
ALTER TABLE `pending_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `php_sessions`
--
ALTER TABLE `php_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_last_activity` (`last_activity`);

--
-- Indexes for table `schedule_images`
--
ALTER TABLE `schedule_images`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section` (`section`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `section_schedules`
--
ALTER TABLE `section_schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_grades`
--
ALTER TABLE `student_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `one_grade` (`student_id`,`subject`,`quarter`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_section` (`section`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_posted_by` (`posted_by`);

--
-- Indexes for table `teacher_schedule`
--
ALTER TABLE `teacher_schedule`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `one_per_slot` (`teacher_id`,`day`,`time_slot_id`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `uq_lrn` (`lrn`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `archived_attendance`
--
ALTER TABLE `archived_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `archived_grades`
--
ALTER TABLE `archived_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=146;

--
-- AUTO_INCREMENT for table `grade_submissions`
--
ALTER TABLE `grade_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `grading_periods`
--
ALTER TABLE `grading_periods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pending_registrations`
--
ALTER TABLE `pending_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `schedule_images`
--
ALTER TABLE `schedule_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `section_schedules`
--
ALTER TABLE `section_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `student_grades`
--
ALTER TABLE `student_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `teacher_schedule`
--
ALTER TABLE `teacher_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;
--
-- Database: `test`
--
CREATE DATABASE IF NOT EXISTS `test` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `test`;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
