-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql309.infinityfree.com
-- Generation Time: May 30, 2026 at 05:06 AM
-- Server version: 11.4.11-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41843901_expense_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `currency` varchar(10) DEFAULT '₹',
  `language` varchar(10) DEFAULT 'en'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `email`, `password`, `currency`, `language`) VALUES
(6, 'itznayanghosh@gmail.com', '$2y$10$3sESC/2wyk.u8yLYZDe/TeOvuLbYVmV8B.iqZnilPRvpSV7Gz6G62', '₹', 'en');

-- --------------------------------------------------------

--
-- Table structure for table `category_monthly_budgets`
--

CREATE TABLE `category_monthly_budgets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `budget_month` varchar(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `budget` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category_monthly_budgets`
--

INSERT INTO `category_monthly_budgets` (`id`, `user_id`, `category_id`, `budget_month`, `budget`, `created_at`) VALUES
(7, 15, 45, '2026-05', '999999.00', '2026-05-10 14:03:14'),
(58, 11, 55, '2026-05', '1000.00', '2026-05-12 13:42:04'),
(124, 19, 73, '2025-09', '8000.00', '2026-05-29 13:22:14'),
(125, 19, 73, '2025-10', '4000.00', '2026-05-29 13:22:14'),
(126, 19, 73, '2025-11', '4000.00', '2026-05-29 13:22:14'),
(127, 19, 73, '2025-12', '4000.00', '2026-05-29 13:22:14'),
(128, 19, 73, '2026-01', '4000.00', '2026-05-29 13:22:14'),
(129, 19, 73, '2026-02', '4000.00', '2026-05-29 13:22:14'),
(130, 19, 73, '2026-03', '4000.00', '2026-05-29 13:22:14'),
(131, 19, 73, '2026-04', '4000.00', '2026-05-29 13:22:14'),
(132, 19, 73, '2026-05', '4000.00', '2026-05-29 13:22:14'),
(133, 19, 74, '2025-12', '264.00', '2026-05-29 13:22:14'),
(134, 19, 74, '2026-01', '128.00', '2026-05-29 13:22:14'),
(135, 19, 74, '2026-03', '296.00', '2026-05-29 13:22:14'),
(136, 19, 75, '2026-02', '465.00', '2026-05-29 13:22:14'),
(137, 19, 75, '2026-03', '525.00', '2026-05-29 13:22:14'),
(138, 19, 75, '2026-04', '385.00', '2026-05-29 13:22:14');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `entry_date` date NOT NULL,
  `entry_time` time NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `custom_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `user_id`, `category_id`, `entry_date`, `entry_time`, `amount`, `description`, `custom_data`, `created_at`) VALUES
(69, 12, 44, '2026-05-07', '12:15:00', '500.00', 'Giving by Nasira Bibi', '[]', '2026-05-07 11:06:38'),
(70, 12, 44, '2026-05-07', '16:37:00', '4626.00', 'Giving by central government ', '[]', '2026-05-07 11:09:08'),
(72, 17, 47, '2026-05-09', '21:01:00', '2000.00', 'Monthly payment ', '[]', '2026-05-09 15:32:02'),
(181, 19, 73, '2025-09-09', '11:38:00', '8000.00', 'September Month ( Advance ₹4000)', '{\"Date Of Payment\":{\"type\":\"date\",\"value\":\"2025-09-12\"}}', '2026-05-29 13:22:14'),
(182, 19, 73, '2025-10-16', '11:05:00', '4000.00', 'October Payment', '{\"Date Of Payment\":{\"type\":\"date\",\"value\":\"2025-10-16\"}}', '2026-05-29 13:22:14'),
(183, 19, 73, '2025-11-09', '17:43:00', '4000.00', 'November Payment', '{\"Date Of Payment\":{\"type\":\"date\",\"value\":\"2025-11-09\"}}', '2026-05-29 13:22:14'),
(184, 19, 73, '2025-12-12', '18:11:00', '4000.00', 'December Payment', '{\"Date Of Payment\":{\"type\":\"date\",\"value\":\"2025-12-11\"}}', '2026-05-29 13:22:14'),
(185, 19, 73, '2026-01-07', '13:15:00', '4000.00', 'January Payment', '{\"Date Of Payment\":{\"type\":\"date\",\"value\":\"2026-01-07\"}}', '2026-05-29 13:22:14'),
(186, 19, 73, '2026-02-01', '15:39:00', '4000.00', 'February Payment', '{\"Date Of Payment\":{\"type\":\"date\",\"value\":\"2026-02-01\"}}', '2026-05-29 13:22:14'),
(187, 19, 73, '2026-03-01', '21:45:00', '4000.00', 'March Payment', '{\"Date Of Payment\":{\"type\":\"date\",\"value\":\"2026-03-01\"}}', '2026-05-29 13:22:14'),
(188, 19, 73, '2026-04-01', '15:42:00', '4000.00', 'April Payment', '{\"Date Of Payment\":{\"type\":\"date\",\"value\":\"2026-04-01\"}}', '2026-05-29 13:22:14'),
(189, 19, 73, '2026-05-01', '06:06:00', '4000.00', 'May Payment', '{\"Date Of Payment\":{\"type\":\"date\",\"value\":\"2026-05-01\"}}', '2026-05-29 13:22:14'),
(190, 19, 74, '2025-09-09', '20:25:00', '0.00', 'Electric Billing start', '{\"Starting Gap Date\":{\"type\":\"date\",\"value\":\"2025-09-22\"},\"Ending Gap Date\":{\"type\":\"date\",\"value\":\"2025-10-14\"},\"Start Gap Time\":{\"type\":\"time\",\"value\":\"20:00\"},\"End Gap Time\":{\"type\":\"time\",\"value\":\"07:08\"},\"Actual Meter Reading\":{\"type\":\"number\",\"value\":\"230.8\"},\"Before Gap Meter Reading\":{\"type\":\"number\",\"value\":\"241.0\"},\"After Gap Meter Reading\":{\"type\":\"number\",\"value\":\"245.0\"},\"Total Gap Unit\":{\"type\":\"number\",\"value\":\"4\"},\"total Unit Before Payment\":{\"type\":\"number\",\"value\":\"\"}}', '2026-05-29 13:22:14'),
(191, 19, 74, '2025-11-20', '01:31:00', '0.00', 'November', '{\"Starting Gap Date\":{\"type\":\"date\",\"value\":\"2025-11-11\"},\"Ending Gap Date\":{\"type\":\"date\",\"value\":\"2025-11-20\"},\"Start Gap Time\":{\"type\":\"time\",\"value\":\"20:00\"},\"End Gap Time\":{\"type\":\"time\",\"value\":\"07:45\"},\"Actual Meter Reading\":{\"type\":\"number\",\"value\":\"260\"},\"Before Gap Meter Reading\":{\"type\":\"number\",\"value\":\"259\"},\"After Gap Meter Reading\":{\"type\":\"number\",\"value\":\"260\"},\"Total Gap Unit\":{\"type\":\"number\",\"value\":\"1\"},\"total Unit Before Payment\":{\"type\":\"number\",\"value\":\"\"}}', '2026-05-29 13:22:14'),
(192, 19, 74, '2025-12-03', '18:33:00', '0.00', 'September to December', '{\"Starting Gap Date\":{\"type\":\"date\",\"value\":\"2025-11-23\"},\"Ending Gap Date\":{\"type\":\"date\",\"value\":\"2025-12-03\"},\"Start Gap Time\":{\"type\":\"time\",\"value\":\"20:00\"},\"End Gap Time\":{\"type\":\"time\",\"value\":\"07:45\"},\"Actual Meter Reading\":{\"type\":\"number\",\"value\":\"\"},\"Before Gap Meter Reading\":{\"type\":\"number\",\"value\":\"260\"},\"After Gap Meter Reading\":{\"type\":\"number\",\"value\":\"268\"},\"Total Gap Unit\":{\"type\":\"number\",\"value\":\"8\"},\"total Unit Before Payment\":{\"type\":\"number\",\"value\":\"\"}}', '2026-05-29 13:22:14'),
(193, 19, 74, '2025-12-12', '23:14:00', '264.00', 'Final Payment of September to December', '{\"Starting Gap Date\":{\"type\":\"date\",\"value\":\"\"},\"Ending Gap Date\":{\"type\":\"date\",\"value\":\"\"},\"Start Gap Time\":{\"type\":\"time\",\"value\":\"\"},\"End Gap Time\":{\"type\":\"time\",\"value\":\"\"},\"Actual Meter Reading\":{\"type\":\"number\",\"value\":\"271.9\"},\"Before Gap Meter Reading\":{\"type\":\"number\",\"value\":\"\"},\"After Gap Meter Reading\":{\"type\":\"number\",\"value\":\"\"},\"Total Gap Unit\":{\"type\":\"number\",\"value\":\"\"},\"total Unit Before Payment\":{\"type\":\"number\",\"value\":\"33\"}}', '2026-05-29 13:22:14'),
(194, 19, 74, '2026-01-09', '18:00:00', '128.00', 'Temporary Reading', '{\"Starting Gap Date\":{\"type\":\"date\",\"value\":\"\"},\"Ending Gap Date\":{\"type\":\"date\",\"value\":\"\"},\"Start Gap Time\":{\"type\":\"time\",\"value\":\"\"},\"End Gap Time\":{\"type\":\"time\",\"value\":\"\"},\"Actual Meter Reading\":{\"type\":\"number\",\"value\":\"288\"},\"Before Gap Meter Reading\":{\"type\":\"number\",\"value\":\"\"},\"After Gap Meter Reading\":{\"type\":\"number\",\"value\":\"\"},\"Total Gap Unit\":{\"type\":\"number\",\"value\":\"\"},\"total Unit Before Payment\":{\"type\":\"number\",\"value\":\"16\"}}', '2026-05-29 13:22:14'),
(195, 19, 74, '2026-02-09', '20:24:00', '0.00', 'No money Deducted for this Unit', '{\"Starting Gap Date\":{\"type\":\"date\",\"value\":\"\"},\"Ending Gap Date\":{\"type\":\"date\",\"value\":\"\"},\"Start Gap Time\":{\"type\":\"time\",\"value\":\"\"},\"End Gap Time\":{\"type\":\"time\",\"value\":\"\"},\"Actual Meter Reading\":{\"type\":\"number\",\"value\":\"293.8\"},\"Before Gap Meter Reading\":{\"type\":\"number\",\"value\":\"\"},\"After Gap Meter Reading\":{\"type\":\"number\",\"value\":\"\"},\"Total Gap Unit\":{\"type\":\"number\",\"value\":\"\"},\"total Unit Before Payment\":{\"type\":\"number\",\"value\":\"\"}}', '2026-05-29 13:22:14'),
(196, 19, 74, '2026-03-20', '14:05:00', '296.00', 'Final Payment of december last to march', '{\"Starting Gap Date\":{\"type\":\"date\",\"value\":\"\"},\"Ending Gap Date\":{\"type\":\"date\",\"value\":\"\"},\"Start Gap Time\":{\"type\":\"time\",\"value\":\"\"},\"End Gap Time\":{\"type\":\"time\",\"value\":\"\"},\"Actual Meter Reading\":{\"type\":\"number\",\"value\":\"316.4\"},\"Before Gap Meter Reading\":{\"type\":\"number\",\"value\":\"\"},\"After Gap Meter Reading\":{\"type\":\"number\",\"value\":\"\"},\"Total Gap Unit\":{\"type\":\"number\",\"value\":\"5\"},\"total Unit Before Payment\":{\"type\":\"number\",\"value\":\"22.6\"}}', '2026-05-29 13:22:14'),
(197, 19, 74, '2026-05-01', '01:30:00', '0.00', 'Gap Because Of Vote', '{\"Starting Gap Date\":{\"type\":\"date\",\"value\":\"2026-04-22\"},\"Ending Gap Date\":{\"type\":\"date\",\"value\":\"2026-05-01\"},\"Start Gap Time\":{\"type\":\"time\",\"value\":\"19:40\"},\"End Gap Time\":{\"type\":\"time\",\"value\":\"06:00\"},\"Actual Meter Reading\":{\"type\":\"number\",\"value\":\"344.2\"},\"Before Gap Meter Reading\":{\"type\":\"number\",\"value\":\"343.9\"},\"After Gap Meter Reading\":{\"type\":\"number\",\"value\":\"344.2\"},\"Total Gap Unit\":{\"type\":\"number\",\"value\":\"0.3\"},\"total Unit Before Payment\":{\"type\":\"number\",\"value\":\"\"}}', '2026-05-29 13:22:14'),
(198, 19, 75, '2025-12-13', '01:36:00', '0.00', 'Starting Date', '{\"Starting Gap Date\":{\"type\":\"date\",\"value\":\"\"},\"Ending Gap Date\":{\"type\":\"date\",\"value\":\"\"}}', '2026-05-29 13:22:14'),
(199, 19, 75, '2026-02-14', '20:34:00', '465.00', 'Ending - Starting Date', '{\"Starting Gap Date\":{\"type\":\"date\",\"value\":\"\"},\"Ending Gap Date\":{\"type\":\"date\",\"value\":\"\"}}', '2026-05-29 13:22:14'),
(200, 19, 75, '2026-03-20', '14:04:00', '525.00', 'February to March ( Gap till 31st March)', '{\"Starting Gap Date\":{\"type\":\"date\",\"value\":\"\"},\"Ending Gap Date\":{\"type\":\"date\",\"value\":\"\"}}', '2026-05-29 13:22:14'),
(201, 19, 75, '2026-04-30', '09:52:00', '385.00', 'March to April', '{\"Starting Gap Date\":{\"type\":\"date\",\"value\":\"2026-04-23\"},\"Ending Gap Date\":{\"type\":\"date\",\"value\":\"2026-04-30\"}}', '2026-05-29 13:22:14');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `action` varchar(100) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rate_limits`
--

INSERT INTO `rate_limits` (`action`, `ip`, `attempts`, `created_at`) VALUES
('otp_request_ankushbs38390@gmail.com', '152.56.156.147', 1, '2026-05-16 20:53:48'),
('otp_request_itzmeparomita@gmail.com', '152.56.132.17', 1, '2026-05-30 00:36:17'),
('otp_request_itzmeparomita@gmail.com', '152.56.156.216', 3, '2026-05-30 00:38:57'),
('otp_request_itznayanghosh@gmail.com', '152.56.156.147', 1, '2026-05-16 20:33:04');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('super_password', '$2y$10$IYtrY8hZ5Q2LRNcL6UFBAOy/yeU0zSix2pzlK/ONKC7O3rGZX4saG');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `currency` varchar(10) DEFAULT '₹',
  `language` varchar(10) DEFAULT 'en',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_active_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_budget` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `currency`, `language`, `created_at`, `last_active_at`, `total_budget`) VALUES
(11, 'itzmeparomita@gmail.com', '$2y$10$NDCN4jshcVp1AOibvBeZJut6PIieKoBNKZxKubN1uDxmP4xwJGPte', '₹', 'en', '2026-05-07 04:05:06', '2026-05-29 19:42:39', '0.00'),
(12, 'nasifalom12@gmail.com', '9123@NASIFalom', '₹', 'en', '2026-05-07 10:54:37', '2026-05-07 11:03:54', '0.00'),
(14, 'asdF@asdf.com', 'Pass@123', '₹', 'en', '2026-05-07 16:52:21', '2026-05-07 16:52:21', '0.00'),
(15, 'admin@google.com', 'Pass@123', '₹', 'en', '2026-05-07 16:52:56', '2026-05-07 16:53:07', '0.00'),
(16, 'thesoma69@gmail.com', 'thesoma69@S', 'VND', 'en', '2026-05-08 07:03:41', '2026-05-08 07:04:10', '0.00'),
(17, 'bikramsingharay0@gmail.com', 'UWBDcqcmd88hiXe\"', '₹', 'en', '2026-05-09 15:27:58', '2026-05-09 15:30:07', '0.00'),
(19, 'ankushbs38390@gmail.com', '$2y$10$Ux7H6tePMWR4nB375ebi5uA3xiva33xEy4Rs8n6vuu1j7U0MkS8am', '₹', 'en', '2026-05-10 22:29:58', '2026-05-29 19:13:40', '0.00'),
(20, 'hafey57302@badgerhole.com', '$2y$10$sQVYOuXuoHHR5hF32Al1XuKthvw8r33BRnQsFSUmngmiA3GOZXkKu', '₹', 'en', '2026-05-12 15:06:53', '2026-05-12 16:04:46', '0.00'),
(21, 'manikghosh980@gmail.com', '$2y$10$YC9zUWsTJ8mtIZb/znbWv.NqyGEFfuxHKKJQA69qimFFLVUAzs0vu', '₹', 'en', '2026-05-16 14:36:13', '2026-05-16 14:36:13', '0.00'),
(22, 'test@test.com', '$2y$10$nYNPw1Ar9Sdv/xMlksVUneCFkUbVd/bo1x1pHgISRNJsyTmUl3YHa', '₹', 'en', '2026-05-16 17:24:26', '2026-05-17 10:01:23', '0.00'),
(23, 'co.r.ridorf.gj@gmail.com', '$2y$10$VcNf0NzXevDhCO6depNuUuzGZVKF.ZIg7yjIc87t1i36mZLZi/rhS', '₹', 'en', '2026-05-17 09:42:37', '2026-05-17 09:42:37', '0.00');

-- --------------------------------------------------------

--
-- Table structure for table `user_categories`
--

CREATE TABLE `user_categories` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `budget` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_categories`
--

INSERT INTO `user_categories` (`id`, `user_id`, `category_name`, `created_at`, `budget`) VALUES
(44, 12, 'Bank account savings', '2026-05-07 11:04:29', '0.00'),
(45, 15, 'asdf', '2026-05-07 16:53:17', '999999.00'),
(47, 17, 'Buget', '2026-05-09 15:30:53', '0.00'),
(55, 11, 'Food', '2026-05-12 13:41:08', '0.00'),
(56, 20, 'test', '2026-05-12 16:05:01', '0.00'),
(73, 19, 'ROOM RENT', '2026-05-29 13:22:14', '0.00'),
(74, 19, 'ELECTRIC BILL', '2026-05-29 13:22:14', '0.00'),
(75, 19, 'MILK BILL', '2026-05-29 13:22:14', '0.00'),
(76, 19, 'FOOD BILL (Cloud Kitchen)', '2026-05-29 13:22:14', '0.00');

-- --------------------------------------------------------

--
-- Table structure for table `user_notes`
--

CREATE TABLE `user_notes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `note_content` varchar(1000) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_notes`
--

INSERT INTO `user_notes` (`id`, `user_id`, `category_id`, `note_content`, `created_at`, `updated_at`) VALUES
(1, 19, 57, 'Hi', '2026-05-29 13:14:48', '2026-05-29 13:14:48'),
(2, 9, 65, 'Hi', '2026-05-29 13:19:27', '2026-05-29 13:19:27'),
(3, 19, 61, 'hi', '2026-05-29 13:20:31', '2026-05-29 13:20:31'),
(4, 19, 69, 'hi', '2026-05-29 13:21:23', '2026-05-29 13:21:23'),
(5, 19, 73, 'hi', '2026-05-29 13:22:14', '2026-05-29 13:22:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `category_monthly_budgets`
--
ALTER TABLE `category_monthly_budgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_cat_month` (`user_id`,`category_id`,`budget_month`),
  ADD KEY `fk_category_budget` (`category_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`action`,`ip`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_categories`
--
ALTER TABLE `user_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_notes`
--
ALTER TABLE `user_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_category_unique` (`user_id`,`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `category_monthly_budgets`
--
ALTER TABLE `category_monthly_budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `user_categories`
--
ALTER TABLE `user_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `user_notes`
--
ALTER TABLE `user_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `category_monthly_budgets`
--
ALTER TABLE `category_monthly_budgets`
  ADD CONSTRAINT `fk_budget_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_category_budget` FOREIGN KEY (`category_id`) REFERENCES `user_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_categories`
--
ALTER TABLE `user_categories`
  ADD CONSTRAINT `user_categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


-- --------------------------------------------------------
-- New tables for Savings Management (PROJECT S)
-- --------------------------------------------------------

-- Table structure for table `savings_goals`
CREATE TABLE `savings_goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `goal_name` varchar(255) NOT NULL,
  `target_amount` decimal(10,2) NOT NULL,
  `target_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `savings_transactions`
CREATE TABLE `savings_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `goal_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_type` enum('deposit','withdrawal') DEFAULT 'deposit',
  `transaction_date` date NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `goal_id` (`goal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Constraints for new tables
ALTER TABLE `savings_goals`
  ADD CONSTRAINT `fk_savings_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `savings_transactions`
  ADD CONSTRAINT `fk_savings_trans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_savings_trans_goal` FOREIGN KEY (`goal_id`) REFERENCES `savings_goals` (`id`) ON DELETE CASCADE;
