-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2026 at 06:11 AM
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
-- Database: `onestep_property`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `role`, `action`, `details`, `created_at`) VALUES
(1, 1, 'admin', 'Landlord account created', '7876584763654 (OSS/RGA/2026/O01)', '2026-10-28 15:20:37'),
(2, 1, 'admin', 'Landlord account created', 'titohb (OSS/RGA/2026/O01)', '2026-10-28 15:43:34'),
(3, 1, 'admin', 'Landlord account created', 'David (OSS/RGA/2026/O01)', '2026-10-29 17:45:50'),
(4, 1, 'admin', 'Landlord account created', 'David (OSS/RGA/2026/O01)', '2026-10-29 17:57:21'),
(5, 1, 'admin', 'Landlord account created', 'David (OSS/RGA/2026/O01)', '2026-10-29 17:58:33'),
(6, 1, 'admin', 'Settings updated', 'Payments', '2026-10-29 18:46:32'),
(7, 1, 'admin', 'House added for landlord', '20 units of spacious single rooms', '2026-10-29 18:50:53');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `house_id` int(11) NOT NULL,
  `move_in_date` date DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `source` enum('online','staff','agent') NOT NULL DEFAULT 'online',
  `created_by` int(11) DEFAULT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `commissions`
--

CREATE TABLE `commissions` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `house_title` varchar(150) NOT NULL,
  `rent` decimal(10,2) NOT NULL,
  `percent` decimal(5,2) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid') NOT NULL DEFAULT 'pending',
  `reference` varchar(60) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `houses`
--

CREATE TABLE `houses` (
  `id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `added_by` int(11) DEFAULT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `room_type` enum('bedsitter','single','studio','one_bedroom','two_bedroom','three_bedroom','maisonette','bungalow','hostel') NOT NULL,
  `suitable_for` enum('anyone','students','non_students') NOT NULL DEFAULT 'anyone',
  `price` decimal(10,2) NOT NULL,
  `deposit` decimal(10,2) DEFAULT NULL,
  `location` varchar(150) NOT NULL,
  `nearest_landmark` varchar(150) DEFAULT NULL,
  `distance_notes` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amenities` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `status` enum('available','booked') NOT NULL DEFAULT 'available',
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `houses`
--

INSERT INTO `houses` (`id`, `landlord_id`, `added_by`, `agent_id`, `title`, `room_type`, `suitable_for`, `price`, `deposit`, `location`, `nearest_landmark`, `distance_notes`, `description`, `amenities`, `latitude`, `longitude`, `status`, `approval_status`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 6, 1, NULL, 'spacious single rooms - Unit 01', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(2, 6, 1, NULL, 'spacious single rooms - Unit 02', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(3, 6, 1, NULL, 'spacious single rooms - Unit 03', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(4, 6, 1, NULL, 'spacious single rooms - Unit 04', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(5, 6, 1, NULL, 'spacious single rooms - Unit 05', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(6, 6, 1, NULL, 'spacious single rooms - Unit 06', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(7, 6, 1, NULL, 'spacious single rooms - Unit 07', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(8, 6, 1, NULL, 'spacious single rooms - Unit 08', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(9, 6, 1, NULL, 'spacious single rooms - Unit 09', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(10, 6, 1, NULL, 'spacious single rooms - Unit 10', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(11, 6, 1, NULL, 'spacious single rooms - Unit 11', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(12, 6, 1, NULL, 'spacious single rooms - Unit 12', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(13, 6, 1, NULL, 'spacious single rooms - Unit 13', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(14, 6, 1, NULL, 'spacious single rooms - Unit 14', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(15, 6, 1, NULL, 'spacious single rooms - Unit 15', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(16, 6, 1, NULL, 'spacious single rooms - Unit 16', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(17, 6, 1, NULL, 'spacious single rooms - Unit 17', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(18, 6, 1, NULL, 'spacious single rooms - Unit 18', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(19, 6, 1, NULL, 'spacious single rooms - Unit 19', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53'),
(20, 6, 1, NULL, 'spacious single rooms - Unit 20', 'single', 'anyone', 3500.00, 3000.00, 'Murang\'a Mumbi area', 'Mumbi girls high school', '1hour walk from the University', 'Very clean houses and very spacious and no loud music is aloud', 'WIFI', -0.7218750, 37.1508055, 'available', 'approved', NULL, '2026-10-29 18:50:53', '2026-10-29 18:50:53');

-- --------------------------------------------------------

--
-- Table structure for table `house_images`
--

CREATE TABLE `house_images` (
  `id` int(11) NOT NULL,
  `house_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `house_images`
--

INSERT INTO `house_images` (`id`, `house_id`, `image_path`) VALUES
(1, 1, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(2, 2, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(3, 3, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(4, 4, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(5, 5, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(6, 6, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(7, 7, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(8, 8, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(9, 9, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(10, 10, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(11, 11, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(12, 12, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(13, 13, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(14, 14, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(15, 15, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(16, 16, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(17, 17, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(18, 18, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(19, 19, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg'),
(20, 20, 'assets/uploads/houses/houses_23f03de8f91d60c1e651.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `landlord_profiles`
--

CREATE TABLE `landlord_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `agreement_ref` varchar(60) DEFAULT NULL COMMENT 'management agreement reference with One Step',
  `agreement_date` date DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `landlord_profiles`
--

INSERT INTO `landlord_profiles` (`id`, `user_id`, `agreement_ref`, `agreement_date`, `notes`) VALUES
(5, 6, 'OSS/RGA/2026/O01', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `login_otps`
--

CREATE TABLE `login_otps` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `purpose` enum('login','reset') NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `sent_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` tinyint(4) NOT NULL DEFAULT 0,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_otps`
--

INSERT INTO `login_otps` (`id`, `user_id`, `purpose`, `code_hash`, `sent_at`, `expires_at`, `attempts`, `used`, `ip`) VALUES
(1, 1, 'login', '$2y$10$prm8MXD2QqIsCpKzmeK3RuhphPXS0sn/bC5q9dwOBosQNEC3CApQK', '2026-10-22 07:18:32', '2026-10-22 07:28:32', 1, 1, '::1'),
(2, 1, 'login', '$2y$10$upz6RiAqulGCQpRof6MpQuWvHhTa7b1BpPppa8kEdQXsV4sEeMbzy', '2026-10-22 12:57:24', '2026-10-22 13:07:24', 1, 1, '::1'),
(3, 1, 'login', '$2y$10$c2aQuuKlWvxu5LY/QGY56.XpPjNm7CBdNpWoyKtgQzn9DJSr9oM.6', '2026-10-22 15:56:36', '2026-10-22 16:06:36', 1, 1, '::1'),
(4, 1, 'login', '$2y$10$8bSLU21rAIUuE15F7Vj1Z.XMWvZ69zKfhdzG7NMQu1q3dlwr1zegK', '2026-10-24 15:07:53', '2026-10-24 15:17:53', 2, 1, '127.0.0.1'),
(5, 1, 'login', '$2y$10$qzVyG9F9mdDb0/z8fozinO0PVYWI8HbnVpOwUQa0NVwYPW088oLm6', '2026-10-24 15:24:28', '2026-10-24 15:34:28', 3, 1, '::1'),
(6, 1, 'login', '$2y$10$50eE4gULpeUMSkVZ2UhImOVbmFP.LbewGaqTFS26/Lrp/jkuGB7yS', '2026-10-24 15:26:48', '2026-10-24 15:36:48', 0, 1, '::1'),
(7, 1, 'login', '$2y$10$OWxoy3ej9R6DAUrL3aCW/e.n9JqsdTvBR/l7k9niLexXJZbuEN5DG', '2026-10-28 18:13:13', '2026-10-28 18:23:13', 0, 1, '::1'),
(8, 1, 'login', '$2y$10$6Dd/gXLcYt940vh4L1B9r.zBBf7DWObOhWbtCBK2kdr8VIyp7x6PK', '2026-10-28 18:16:13', '2026-10-28 18:26:13', 2, 1, '::1'),
(9, 1, 'login', '$2y$10$5FGYqulfmwdUn0wMEum2OuKCbH0SNr4u8wuVbbpdHo6zQNqQUPZ6i', '2026-10-28 22:14:40', '2026-10-28 22:24:40', 1, 1, '::1'),
(10, 1, 'login', '$2y$10$sEiHxI3WuPt6vzkOryi3n.i9qjgH7xyd2t6ikDMLOk2PJ0EUCtpz2', '2026-10-29 20:41:28', '2026-10-29 20:51:28', 1, 1, '::1'),
(11, 1, 'reset', '$2y$10$pZcaYp6PudDzkJTUbsi3pOwQr1lHXV0fLzri/aggxA9NcAHMKJPhy', '2026-11-12 08:03:27', '2026-11-12 08:13:27', 1, 1, '::1'),
(12, 1, 'reset', '$2y$10$mURYs9ne5bjxLTdZZu0vgOKHmUXF0s38OpVU.Qg4et8bTrTgUi5OS', '2026-11-12 08:07:36', '2026-11-12 08:17:36', 1, 1, '::1'),
(13, 1, 'login', '$2y$10$6wIM9Sm./pKc9jZ1RPYY9eWrM4IhuVb0NctoWHG00fKEE72OaQSf2', '2026-11-12 08:08:44', '2026-11-12 08:18:44', 1, 1, '::1');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `channel` enum('email','sms') NOT NULL,
  `recipient` varchar(150) NOT NULL,
  `subject` varchar(190) NOT NULL,
  `message` text NOT NULL,
  `status` enum('sent','failed') NOT NULL,
  `error` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `channel`, `recipient`, `subject`, `message`, `status`, `error`, `created_at`) VALUES
(1, 1, 'email', 'franklinedavid196@gmail.com', 'Verification code (log in)', '[code hidden]', 'sent', NULL, '2026-10-22 04:18:32'),
(2, 1, 'email', 'franklinedavid196@gmail.com', 'Verification code (log in)', '[code hidden]', 'sent', NULL, '2026-10-22 09:57:24'),
(3, 1, 'email', 'franklinedavid196@gmail.com', 'Verification code (log in)', '[code hidden]', 'sent', NULL, '2026-10-22 12:56:36'),
(4, 1, 'email', 'franklinedavid196@gmail.com', 'Verification code (log in)', '[code hidden]', 'sent', NULL, '2026-10-24 12:07:53'),
(5, 1, 'email', 'franklinedavid196@gmail.com', 'Verification code (log in)', '[code hidden]', 'sent', NULL, '2026-10-24 12:24:28'),
(6, 1, 'email', 'franklinedavid196@gmail.com', 'Verification code (log in)', '[code hidden]', 'sent', NULL, '2026-10-24 12:26:48'),
(7, 1, 'email', 'franklinedavid196@gmail.com', 'Verification code (log in)', '[code hidden]', 'sent', NULL, '2026-10-28 15:13:13'),
(8, 1, 'email', 'franklinedavid196@gmail.com', 'Verification code (log in)', '[code hidden]', 'sent', NULL, '2026-10-28 15:16:13'),
(9, 2, 'email', 'cgjdjhdsthsdys@gmail.com', 'Account created (landlord)', '[credentials hidden]', 'sent', NULL, '2026-10-28 15:20:37'),
(10, 3, 'email', 'davidfranklin@ail.com', 'Account created (landlord)', '[credentials hidden]', 'sent', NULL, '2026-10-28 15:43:34'),
(11, 1, 'email', 'franklinedavid196@gmail.com', 'Verification code (log in)', '[code hidden]', 'sent', NULL, '2026-10-28 19:14:40'),
(12, 1, 'email', 'franklinedavid196@gmail.com', 'Verification code (log in)', '[code hidden]', 'sent', NULL, '2026-10-29 17:41:28'),
(13, 4, 'email', 'franklinedavid@gmail.com', 'Account created (landlord)', '[credentials hidden]', 'sent', NULL, '2026-10-29 17:45:50'),
(14, 5, 'email', 'franklinedavid@gmail.com', 'Account created (landlord)', '[credentials hidden]', 'sent', NULL, '2026-10-29 17:57:21'),
(15, 6, 'email', 'franklinedavid3@gmail.com', 'Account created (landlord)', '[credentials hidden]', 'sent', NULL, '2026-10-29 17:58:33'),
(16, 1, 'email', 'franklinedavid196@gmail.com', 'Verification code (reset your password)', '[code hidden]', 'sent', NULL, '2026-11-12 05:03:27'),
(17, 1, 'email', 'franklinedavid196@gmail.com', 'Verification code (reset your password)', '[code hidden]', 'sent', NULL, '2026-11-12 05:07:36'),
(18, 1, 'email', 'franklinedavid196@gmail.com', 'Your password was changed', 'Your One Step Solution Property password was just changed. If this was not you, contact us immediately.', 'sent', NULL, '2026-11-12 05:08:22'),
(19, 1, 'email', 'franklinedavid196@gmail.com', 'Verification code (log in)', '[code hidden]', 'sent', NULL, '2026-11-12 05:08:44');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `house_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `skey` varchar(60) NOT NULL,
  `svalue` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`skey`, `svalue`, `updated_at`) VALUES
('PAY_ACCOUNT_NOTE', 'frankline', '2026-10-29 18:46:32'),
('PAY_BANK_ACCOUNT', '0717254510', '2026-10-29 18:46:32'),
('PAY_BANK_ACCOUNT_NAME', 'frankline', '2026-10-29 18:46:32'),
('PAY_BANK_NAME', 'KCB', '2026-10-29 18:46:32'),
('PAY_PAYBILL', '0717254510', '2026-10-29 18:46:32'),
('PAY_TEXT', 'pay via the phone number listed', '2026-10-29 18:46:32'),
('PAY_TILL', '0717254510', '2026-10-29 18:46:32');

-- --------------------------------------------------------

--
-- Table structure for table `team_profiles`
--

CREATE TABLE `team_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `position` varchar(80) DEFAULT NULL COMMENT 'staff job title, e.g. Receptionist',
  `territory` varchar(150) DEFAULT NULL COMMENT 'agent area of operation',
  `commission_percent` decimal(5,2) DEFAULT NULL COMMENT 'agent commission, % of first month rent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('admin','staff','agent','landlord','tenant') NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('approved','suspended') NOT NULL DEFAULT 'approved',
  `tenant_type` enum('student','non_student') DEFAULT NULL,
  `organization` varchar(150) DEFAULT NULL COMMENT 'institution for students, employer/occupation for others',
  `id_number` varchar(30) DEFAULT NULL,
  `notify_pref` enum('email','sms','both') NOT NULL DEFAULT 'email',
  `is_super` tinyint(1) NOT NULL DEFAULT 0,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `full_name`, `email`, `phone`, `password`, `status`, `tenant_type`, `organization`, `id_number`, `notify_pref`, `is_super`, `must_change_password`, `last_login_at`, `created_at`) VALUES
(1, 'admin', 'Tito Frankline', 'franklinedavid196@gmail.com', '+254717254510', '$2y$10$.MVvatfOCx0JM.Dn0a3/8.FjK2QQ/Nd6K4QZu10rIdO5Bkh1PK.QG', 'approved', NULL, NULL, NULL, 'email', 1, 0, '2026-11-12 08:09:08', '2026-10-22 04:16:53'),
(6, 'landlord', 'David', 'franklinedavid3@gmail.com', '+254717254598', '$2y$10$AwMfsEZKOzQ377Dv/B9bF.tYwjK05DZTZzZgoINf8y0/0i61vUNO2', 'approved', NULL, NULL, '41675432', 'email', 0, 1, NULL, '2026-10-29 17:58:33');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `house_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `house_id` (`house_id`),
  ADD KEY `bookings_created_by` (`created_by`),
  ADD KEY `bookings_agent` (`agent_id`);

--
-- Indexes for table `commissions`
--
ALTER TABLE `commissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`),
  ADD KEY `agent_id` (`agent_id`);

--
-- Indexes for table `houses`
--
ALTER TABLE `houses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `landlord_id` (`landlord_id`),
  ADD KEY `approval_status` (`approval_status`),
  ADD KEY `houses_added_by` (`added_by`),
  ADD KEY `houses_agent` (`agent_id`);

--
-- Indexes for table `house_images`
--
ALTER TABLE `house_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `house_id` (`house_id`);

--
-- Indexes for table `landlord_profiles`
--
ALTER TABLE `landlord_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `login_otps`
--
ALTER TABLE `login_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_purpose` (`user_id`,`purpose`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `one_review` (`house_id`,`tenant_id`),
  ADD KEY `r_tenant` (`tenant_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`skey`);

--
-- Indexes for table `team_profiles`
--
ALTER TABLE `team_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wish` (`tenant_id`,`house_id`),
  ADD KEY `w_house` (`house_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `commissions`
--
ALTER TABLE `commissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `houses`
--
ALTER TABLE `houses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `house_images`
--
ALTER TABLE `house_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `landlord_profiles`
--
ALTER TABLE `landlord_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `login_otps`
--
ALTER TABLE `login_otps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `team_profiles`
--
ALTER TABLE `team_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `b_house` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `b_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_agent` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bookings_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `commissions`
--
ALTER TABLE `commissions`
  ADD CONSTRAINT `c_agent` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `houses`
--
ALTER TABLE `houses`
  ADD CONSTRAINT `houses_added_by` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `houses_agent` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `houses_landlord` FOREIGN KEY (`landlord_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `house_images`
--
ALTER TABLE `house_images`
  ADD CONSTRAINT `hi_house` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `landlord_profiles`
--
ALTER TABLE `landlord_profiles`
  ADD CONSTRAINT `lp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_otps`
--
ALTER TABLE `login_otps`
  ADD CONSTRAINT `otp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `r_house` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `r_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `team_profiles`
--
ALTER TABLE `team_profiles`
  ADD CONSTRAINT `tp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `w_house` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `w_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
