-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 13, 2026 at 04:45 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `murna_foundation`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `balance` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `user_id`, `account_number`, `balance`, `created_at`) VALUES
(2, 2, 'MF14C13F18', 3200.00, '2026-05-22 15:19:34'),
(3, 3, 'MF4190964B', 0.00, '2026-05-27 15:03:20'),
(4, 4, 'MFA4EA9C4A', 0.00, '2026-05-31 13:32:47'),
(5, 5, 'MF8F936030', 0.00, '2026-06-03 14:41:57'),
(10, 10, 'MF19669868', 8400.00, '2026-06-08 11:03:51'),
(11, 11, 'MFF0C858DF', 600.00, '2026-06-11 10:04:35');

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_log`
--

CREATE TABLE `admin_activity_log` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_activity_log`
--

INSERT INTO `admin_activity_log` (`id`, `admin_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-25 18:48:08'),
(2, 1, 'SETTINGS_UPDATE', 'Updated verification costs and bank settings', '::1', '2026-05-25 18:51:12'),
(3, 1, 'LOGOUT', 'Admin logged out', '::1', '2026-05-25 18:51:32'),
(4, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-25 20:43:57'),
(5, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-25 21:17:20'),
(6, 1, 'SETTINGS_UPDATE', 'Updated verification costs and bank settings', '::1', '2026-05-25 21:18:02'),
(7, 1, 'SETTINGS_UPDATE', 'Updated verification costs and bank settings', '::1', '2026-05-25 21:22:30'),
(8, 1, 'SETTINGS_UPDATE', 'Updated verification costs and bank settings', '::1', '2026-05-25 21:25:29'),
(9, 1, 'SETTINGS_UPDATE', 'Updated verification costs and bank settings', '::1', '2026-05-25 21:25:58'),
(10, 1, 'SETTINGS_UPDATE', 'Updated verification costs and bank settings', '::1', '2026-05-25 21:27:23'),
(11, 1, 'SETTINGS_UPDATE', 'Updated verification costs and bank settings', '::1', '2026-05-25 21:27:28'),
(12, 1, 'SETTINGS_UPDATE', 'Updated verification costs and bank settings', '::1', '2026-05-25 21:27:31'),
(13, 1, 'SETTINGS_UPDATE', 'Updated verification costs and bank settings', '::1', '2026-05-25 21:27:57'),
(14, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-26 08:33:17'),
(15, 1, 'SETTINGS_UPDATE', 'Updated verification costs and bank settings', '::1', '2026-05-26 08:33:48'),
(16, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-26 09:11:00'),
(17, 1, 'LOGOUT', 'Admin logged out', '::1', '2026-05-26 09:11:15'),
(18, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-26 09:26:37'),
(19, 1, 'LOGOUT', 'Admin logged out', '::1', '2026-05-26 09:33:28'),
(20, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-26 09:33:38'),
(21, 1, 'LOGOUT', 'Admin logged out', '::1', '2026-05-26 09:37:43'),
(22, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-26 09:38:04'),
(23, 1, 'LOGOUT', 'Admin logged out', '::1', '2026-05-26 09:44:21'),
(24, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-27 15:04:00'),
(25, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-29 22:28:08'),
(26, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-31 09:29:46'),
(27, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-31 09:30:43'),
(28, 1, 'USER_STATUS', 'Changed user 2 status to suspended', '::1', '2026-05-31 09:31:26'),
(29, 1, 'LOGOUT', 'Admin logged out', '::1', '2026-05-31 09:32:26'),
(30, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-31 10:20:56'),
(31, 1, 'LOGOUT', 'Admin logged out', '::1', '2026-05-31 10:21:24'),
(32, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-31 10:24:07'),
(33, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-31 10:25:55'),
(34, 1, 'USER_STATUS', 'Changed user 2 status to active', '::1', '2026-05-31 10:27:10'),
(35, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-31 10:28:44'),
(36, 1, 'USER_STATUS', 'Changed user 2 status to suspended', '::1', '2026-05-31 10:29:04'),
(37, 1, 'LOGOUT', 'Admin logged out', '::1', '2026-05-31 13:30:18'),
(38, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-31 13:30:28'),
(39, 1, 'LOGOUT', 'Admin logged out', '::1', '2026-05-31 13:30:35'),
(40, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-31 13:36:21'),
(41, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-31 13:39:45'),
(42, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-31 13:51:44'),
(43, 1, 'LOGOUT', 'Admin logged out', '::1', '2026-05-31 13:55:39'),
(44, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-31 13:56:25'),
(45, 1, 'LOGOUT', 'Admin logged out', '::1', '2026-05-31 13:57:19'),
(46, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-05-31 13:58:30'),
(47, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-06-02 12:56:24'),
(48, 1, 'USER_STATUS', 'Changed user 2 status to active', '::1', '2026-06-02 12:56:59'),
(49, 1, 'LOGOUT', 'Admin logged out', '::1', '2026-06-02 12:58:34'),
(50, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-06-02 12:59:39'),
(51, 1, 'LOGOUT', 'Admin logged out', '::1', '2026-06-02 13:01:12'),
(52, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-06-03 13:58:23'),
(53, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-06-03 14:43:30'),
(54, 1, 'LOGIN', 'Admin logged in successfully', '::1', '2026-06-08 10:52:43');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role` enum('super_admin','admin') DEFAULT 'admin',
  `last_login` datetime DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `last_login`, `status`, `created_at`) VALUES
(1, 'admin', 'admin@murna.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'super_admin', '2026-06-08 11:52:43', 'active', '2026-05-25 16:03:34'),
(2, 'testadmin', 'testadmin@gmail.com', 'test@123', 'Sani Suleiman', 'admin', NULL, 'active', '2026-05-25 17:24:49');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(1, 10, '$2y$10$OOxUDfC.yPY34gfLOKhXVe/C9lfZeZsA4qOZLxSjH1QJAtxnb1soO', '2026-06-12 18:38:42', 0, '2026-06-12 17:08:42'),
(2, 10, '$2y$10$eK3XDbOLw/2cMpOvTQ3pB.JA1ghVR3vplYKCUuxuQBjmpgsrB7oyu', '2026-06-12 18:48:55', 0, '2026-06-12 17:18:56'),
(3, 10, '$2y$10$1NYL4LRAtpz86aGFsT4GP.q23opkb5QSIViMfkzgD8FV5Nq3iBuPi', '2026-06-12 18:50:47', 0, '2026-06-12 17:20:47'),
(4, 10, '$2y$10$6pet2F8m5DPenjLmdyufu.hZ91ISzJ2qegfh/.4xvKRnHTXx6hye6', '2026-06-12 18:59:11', 0, '2026-06-12 17:29:11'),
(5, 10, '$2y$10$.J.NRsuX9ao/68D2NpFFVemfK6f6JyrzhuzrZF5Sj66WLB/IB5wJS', '2026-06-12 19:01:29', 0, '2026-06-12 17:31:29'),
(6, 10, '$2y$10$LdrJCz14ccrZFzmhmbPCzu6k9TCXpcw7n5JZtY30lagJKUFErEmJG', '2026-06-12 18:51:43', 1, '2026-06-12 17:37:44'),
(7, 10, '$2y$10$D0vIqGhE2s7ducwnsc.pnuLDTGYZUviEYwLspJllIjLgLLlig28ei', '2026-06-12 19:13:22', 0, '2026-06-12 17:43:22'),
(8, 10, '$2y$10$d6Imu1hFdAaIdilkvpaiwOF4J3kgR3SgabCcH.tThPIZEK.taYARy', '2026-06-12 18:59:07', 1, '2026-06-12 18:55:06');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'nin_verification_cost', '200.00', '2026-05-25 21:25:58'),
(2, 'phone_verification_cost', '200.00', '2026-05-25 21:25:58'),
(3, 'demographic_verification_cost', '200.00', '2026-05-26 08:33:48'),
(4, 'bank_name', NULL, '2026-05-25 21:25:58'),
(5, 'bank_account_number', NULL, '2026-05-25 21:25:58'),
(6, 'bank_account_name', NULL, '2026-05-25 21:25:58'),
(7, 'paystack_public_key', 'pk_test_your_paystack_public_key_here', '2026-05-22 13:11:03'),
(8, 'paystack_secret_key', 'sk_test_your_paystack_secret_key_here', '2026-05-22 13:11:03'),
(9, 'randaverify_password', '', '2026-06-02 13:48:45');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('credit','debit') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference` varchar(100) NOT NULL,
  `method` enum('paystack','bank_transfer') NOT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `type`, `amount`, `reference`, `method`, `status`, `description`, `created_at`) VALUES
(9, 2, 'credit', 1000.00, 'MF_1780405148724_2787', 'paystack', 'success', 'Wallet top-up via Paystack', '2026-06-02 12:59:17'),
(42, 10, 'credit', 10000.00, 'MF_1780916959980_6189', 'paystack', 'success', 'Wallet top-up via Paystack', '2026-06-08 11:09:29'),
(43, 10, 'debit', 200.00, 'VRF_1780916984_9072', '', 'success', 'RandaVerify NIN verification', '2026-06-08 11:09:44'),
(44, 10, 'debit', 200.00, 'VRF_1780917145_9627', '', 'success', 'RandaVerify NIN verification', '2026-06-08 11:12:25'),
(45, 10, 'debit', 200.00, 'VRF_1780917387_8515', '', 'success', 'RandaVerify NIN verification', '2026-06-08 11:16:27'),
(46, 2, 'debit', 200.00, 'VRF_1780917683_5518', '', 'success', 'RandaVerify NIN verification', '2026-06-08 11:21:23'),
(47, 2, 'debit', 200.00, 'VRF_1780922175_4166', '', 'success', 'RandaVerify NIN verification', '2026-06-08 12:36:15'),
(48, 2, 'debit', 200.00, 'VRF_1780922463_3189', '', 'success', 'RandaVerify NIN verification', '2026-06-08 12:41:03'),
(49, 2, 'debit', 200.00, 'VRF_1780922870_9590', '', 'success', 'RandaVerify NIN verification', '2026-06-08 12:47:50'),
(50, 2, 'credit', 10000.00, 'MF_1780923003714_9615', 'paystack', 'success', 'Wallet top-up via Paystack', '2026-06-08 12:50:16'),
(51, 2, 'debit', 200.00, 'VRF_1780923035_3218', '', 'success', 'RandaVerify NIN verification', '2026-06-08 12:50:35'),
(52, 2, 'debit', 200.00, 'VRF_1780923970_6998', '', 'success', 'RandaVerify NIN verification', '2026-06-08 13:06:10'),
(53, 2, 'debit', 200.00, 'VRF_1780924089_1038', '', 'success', 'RandaVerify NIN verification', '2026-06-08 13:08:09'),
(54, 2, 'debit', 200.00, 'VRF_1780924138_8118', '', 'success', 'RandaVerify NIN verification', '2026-06-08 13:08:58'),
(55, 2, 'debit', 200.00, 'VRF_1780924235_2572', '', 'success', 'RandaVerify NIN verification', '2026-06-08 13:10:35'),
(56, 2, 'debit', 200.00, 'VRF_1780924383_9621', '', 'success', 'RandaVerify NIN verification', '2026-06-08 13:13:03'),
(57, 2, 'debit', 200.00, 'VRF_1780924659_9191', '', 'success', 'RandaVerify NIN verification', '2026-06-08 13:17:39'),
(58, 2, 'debit', 200.00, 'VRF_1780930210_5613', '', 'success', 'RandaVerify NIN verification', '2026-06-08 14:50:10'),
(59, 2, 'debit', 200.00, 'VRF_1780930454_8497', '', 'success', 'RandaVerify NIN verification', '2026-06-08 14:54:14'),
(60, 2, 'debit', 200.00, 'VRF_1780930534_9445', '', 'success', 'RandaVerify NIN verification', '2026-06-08 14:55:34'),
(61, 2, 'debit', 200.00, 'VRF_1780956495_6426', '', 'success', 'RandaVerify NIN verification', '2026-06-08 22:08:15'),
(62, 2, 'debit', 200.00, 'VRF_1780979450_1618', '', 'success', 'RandaVerify NIN verification', '2026-06-09 04:30:50'),
(63, 2, 'debit', 200.00, 'VRF_1780997871_4834', '', 'success', 'RandaVerify NIN verification', '2026-06-09 09:37:51'),
(64, 2, 'debit', 200.00, 'VRF_1780997907_6144', '', 'success', 'RandaVerify NIN verification', '2026-06-09 09:38:27'),
(65, 2, 'debit', 200.00, 'VRF_1780998562_3126', '', 'success', 'RandaVerify NIN verification', '2026-06-09 09:49:22'),
(66, 2, 'debit', 200.00, 'VRF_1780998662_5614', '', 'success', 'RandaVerify NIN verification', '2026-06-09 09:51:02'),
(67, 2, 'debit', 200.00, 'VRF_1781011646_7268', '', 'success', 'RandaVerify NIN verification', '2026-06-09 13:27:26'),
(68, 2, 'debit', 200.00, 'VRF_1781084589_7283', '', 'success', 'RandaVerify NIN verification', '2026-06-10 09:43:09'),
(69, 2, 'debit', 200.00, 'VRF_1781099272_1212', '', 'success', 'RandaVerify NIN verification', '2026-06-10 13:47:52'),
(70, 2, 'debit', 200.00, 'VRF_1781099388_3018', '', 'success', 'RandaVerify NIN verification', '2026-06-10 13:49:48'),
(71, 2, 'debit', 200.00, 'VRF_1781099874_4948', '', 'success', 'RandaVerify NIN verification', '2026-06-10 13:57:54'),
(72, 2, 'debit', 200.00, 'VRF_1781109681_1355', '', 'success', 'RandaVerify NIN verification', '2026-06-10 16:41:21'),
(73, 2, 'debit', 200.00, 'VRF_1781116639_7246', '', 'success', 'RandaVerify NIN verification', '2026-06-10 18:37:19'),
(74, 2, 'debit', 200.00, 'VRF_1781117313_6969', '', 'success', 'RandaVerify NIN verification', '2026-06-10 18:48:33'),
(75, 2, 'debit', 200.00, 'VRF_1781120026_4508', '', 'success', 'RandaVerify NIN verification', '2026-06-10 19:33:46'),
(76, 2, 'debit', 200.00, 'VRF_1781151870_2195', '', 'success', 'RandaVerify NIN verification', '2026-06-11 04:24:30'),
(77, 2, 'debit', 200.00, 'VRF_1781155137_7286', '', 'success', 'RandaVerify NIN verification', '2026-06-11 05:18:57'),
(78, 2, 'debit', 200.00, 'VRF_1781155210_5897', '', 'success', 'RandaVerify NIN verification', '2026-06-11 05:20:10'),
(79, 2, 'debit', 200.00, 'VRF_1781165089_5165', '', 'success', 'RandaVerify NIN verification', '2026-06-11 08:04:49'),
(80, 2, 'debit', 200.00, 'VRF_1781169004_4460', '', 'success', 'RandaVerify NIN verification', '2026-06-11 09:10:04'),
(81, 2, 'debit', 200.00, 'VRF_1781169066_2705', '', 'success', 'RandaVerify NIN verification', '2026-06-11 09:11:06'),
(82, 2, 'debit', 200.00, 'VRF_1781169799_8396', '', 'success', 'RandaVerify NIN verification', '2026-06-11 09:23:19'),
(83, 2, 'debit', 200.00, 'VRF_1781171094_1761', '', 'success', 'RandaVerify NIN verification', '2026-06-11 09:44:54'),
(84, 10, 'debit', 200.00, 'VRF_1781171310_9738', '', 'success', 'RandaVerify NIN verification', '2026-06-11 09:48:30'),
(85, 10, 'debit', 200.00, 'VRF_1781171347_4682', '', 'success', 'RandaVerify NIN verification', '2026-06-11 09:49:07'),
(86, 2, 'debit', 200.00, 'VRF_1781171460_7052', '', 'success', 'RandaVerify NIN verification', '2026-06-11 09:51:00'),
(87, 10, 'debit', 200.00, 'VRF_1781172050_7118', '', 'success', 'RandaVerify NIN verification', '2026-06-11 10:00:50'),
(88, 11, 'credit', 1000.00, 'MF_1781172473756_8778', 'paystack', 'success', 'Wallet top-up via Paystack', '2026-06-11 10:08:07'),
(89, 11, 'debit', 200.00, 'VRF_1781172526_5598', '', 'success', 'RandaVerify NIN verification', '2026-06-11 10:08:46'),
(90, 11, 'debit', 200.00, 'VRF_1781177338_7364', '', 'success', 'RandaVerify NIN verification', '2026-06-11 11:28:58'),
(91, 2, 'debit', 200.00, 'VRF_1781180324_8062', '', 'success', 'RandaVerify NIN verification', '2026-06-11 12:18:44'),
(92, 10, 'debit', 200.00, 'VRF_1781290865_9749', '', 'success', 'RandaVerify NIN verification', '2026-06-12 19:01:05'),
(93, 10, 'debit', 200.00, 'VRF_1781290946_3008', '', 'success', 'RandaVerify NIN verification', '2026-06-12 19:02:26');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `account_type` enum('individual','corporate') NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `org_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `nin_input` varchar(20) NOT NULL,
  `rc_number` varchar(100) DEFAULT NULL,
  `org_address` text DEFAULT NULL,
  `org_state` varchar(100) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `is_verified` tinyint(4) DEFAULT 0,
  `status` enum('active','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `nin_verifications` int(11) DEFAULT 0,
  `phone_verifications` int(11) DEFAULT 0,
  `demographic_verifications` int(11) DEFAULT 0,
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `account_type`, `full_name`, `org_name`, `email`, `phone`, `password`, `nin_input`, `rc_number`, `org_address`, `org_state`, `contact_person`, `contact_phone`, `is_verified`, `status`, `created_at`, `nin_verifications`, `phone_verifications`, `demographic_verifications`, `email_verified`, `verification_token`, `token_expires_at`) VALUES
(2, 'individual', 'Musa Danladi', NULL, 'musadanladi@gmail.com', '08168171704', '$2y$10$MLDNS.E5X8TW8dEdKEZLvODO0Jwuv1CegYaTP069VpcrFUcQ70IJu', '0', NULL, NULL, NULL, NULL, NULL, 0, 'active', '2026-05-22 15:19:34', 0, 0, 0, 0, NULL, NULL),
(3, 'individual', 'Isah Lado', NULL, 'isahlado@123', '08168171404', '$2y$10$nmwFRmo8NyVLDboa8e9iNeqAED.210l9wmoOTG/WXiVCXRb7yhJ3u', '0', NULL, NULL, NULL, NULL, NULL, 0, 'active', '2026-05-27 15:03:20', 0, 0, 0, 0, NULL, NULL),
(4, 'individual', 'UMAR MAHUTA', NULL, 'umarmalay@yahoo.com', '2348039554275', '$2y$10$S.wDQQl069TzAuP0ZXc33.s9JJLQAzOgV1LFoo0WUESVLyoz39dFG', '0', NULL, NULL, NULL, NULL, NULL, 0, 'active', '2026-05-31 13:32:47', 0, 0, 0, 0, NULL, NULL),
(5, 'individual', 'Bashir Bello', NULL, 'bashirbello@123', '8168171709', '$2y$10$OaFgiXM022AK1zl4B3wVuOJjqOMm0wsyekngXPB4rW0emxuBHEcZa', '12345678901', NULL, NULL, NULL, NULL, NULL, 0, 'active', '2026-06-03 14:41:57', 0, 0, 0, 0, NULL, NULL),
(10, 'individual', 'Sani Makusidi', NULL, 'sani.makusidi@gmail.com', '08168171703', '$2y$10$CIpkG.xZsocX0CBjsHnUTeOwP/xwd0ec9Z44cjqG2KKB7JQFIIY4G', '12345678906', NULL, NULL, NULL, NULL, NULL, 0, 'active', '2026-06-08 11:03:51', 0, 0, 0, 0, '7708cf657d6abe2127498b17d45ab15ea467edb7a08b097b838ab863acc5c532', '2026-06-09 13:03:51'),
(11, 'individual', 'AISHA AHMED', NULL, 'murnafoundation01@yahoo.com', '080365642775', '$2y$10$ofcYtId/OJ.4E/JEXCu5oeZ5k9YhKSVULBUjgG0m/fsNoDoAcUfWG', '95768522222', NULL, NULL, NULL, NULL, NULL, 0, 'active', '2026-06-11 10:04:35', 0, 0, 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `verification_logs`
--

CREATE TABLE `verification_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `verification_type` enum('nin','phone','demographic') NOT NULL,
  `query_input` text DEFAULT NULL,
  `response_data` longtext DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT 100.00,
  `status` enum('success','failed','not_found') DEFAULT 'success',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `verification_logs`
--

INSERT INTO `verification_logs` (`id`, `user_id`, `verification_type`, `query_input`, `response_data`, `cost`, `status`, `created_at`) VALUES
(76, 10, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-08 11:09:44'),
(77, 10, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-08 11:12:25'),
(78, 10, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-08 11:16:27'),
(79, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-08 11:21:23'),
(80, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-08 12:36:15'),
(81, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-08 12:41:03'),
(82, 2, 'nin', '12345678902', '0', 200.00, 'success', '2026-06-08 12:47:50'),
(83, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-08 12:50:35'),
(84, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-08 13:06:10'),
(85, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-08 13:08:09'),
(86, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-08 13:08:58'),
(87, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-08 13:10:35'),
(88, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-08 13:13:03'),
(89, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-08 13:17:39'),
(90, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-08 14:50:10'),
(91, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-08 14:54:14'),
(92, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-08 14:55:34'),
(93, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-08 22:08:15'),
(94, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-09 04:30:50'),
(95, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-09 09:37:51'),
(96, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-09 09:38:27'),
(97, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-09 09:49:22'),
(98, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-09 09:51:02'),
(99, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-09 13:27:26'),
(100, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-10 09:43:09'),
(101, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-10 13:47:52'),
(102, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-10 13:49:48'),
(103, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-10 13:57:54'),
(104, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-10 16:41:21'),
(105, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-10 18:37:19'),
(106, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-10 18:48:33'),
(107, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-10 19:33:46'),
(108, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-11 04:24:30'),
(109, 2, 'nin', '12345678902', '0', 200.00, 'success', '2026-06-11 05:18:57'),
(110, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-11 05:20:10'),
(111, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-11 08:04:49'),
(112, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-11 09:10:04'),
(113, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-11 09:11:06'),
(114, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-11 09:23:19'),
(115, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-11 09:44:54'),
(116, 10, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-11 09:48:30'),
(117, 10, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-11 09:49:07'),
(118, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-11 09:51:00'),
(119, 10, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-11 10:00:50'),
(120, 11, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-11 10:08:46'),
(121, 11, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-11 11:28:58'),
(122, 2, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-11 12:18:44'),
(123, 10, 'nin', '12345678902', '0', 200.00, 'failed', '2026-06-12 19:00:12'),
(124, 10, 'nin', '12345678902', '0', 200.00, 'success', '2026-06-12 19:01:05'),
(125, 10, 'nin', '12345678901', '0', 200.00, 'success', '2026-06-12 19:02:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_number` (`account_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD UNIQUE KEY `uq_setting_key` (`setting_key`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference` (`reference`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `verification_logs`
--
ALTER TABLE `verification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `verification_logs`
--
ALTER TABLE `verification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD CONSTRAINT `admin_activity_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `verification_logs`
--
ALTER TABLE `verification_logs`
  ADD CONSTRAINT `verification_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
