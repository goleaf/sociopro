-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 25, 2024 at 10:07 AM
-- Server version: 5.7.24
-- PHP Version: 8.2.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tmain`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_active_requests`
--

CREATE TABLE `account_active_requests` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `status` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------


--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `activity_id` int(11) NOT NULL,
  `activity_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` int(11) DEFAULT NULL,
  `icon` int(11) DEFAULT NULL,
  `created_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `addons`
--

CREATE TABLE `addons` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `features` varchar(255) DEFAULT NULL,
  `version` float DEFAULT NULL,
  `unique_identifier` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `albums`
--

CREATE TABLE `albums` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `page_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sub_title` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `thumbnail` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `privacy` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `album_images`
--

CREATE TABLE `album_images` (
  `id` int(11) NOT NULL,
  `album_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `page_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `image` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `batchs`
--

CREATE TABLE `batchs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `start_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `end_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `block_users`
--

CREATE TABLE `block_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `block_user` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blogcategories`
--

CREATE TABLE `blogcategories` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `blogs`
--

CREATE TABLE `blogs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `thumbnail` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `banner` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tag` text COLLATE utf8_unicode_ci,
  `view` text COLLATE utf8_unicode_ci,
  `created_at` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `updated_at` varchar(100) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `chats`
--

CREATE TABLE `chats` (
  `id` int(11) NOT NULL,
  `message_thrade` int(11) DEFAULT NULL,
  `reciver_id` int(11) DEFAULT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `thumbsup` tinyint(1) NOT NULL DEFAULT '0',
  `file` text,
  `react` text,
  `reply_id` int(11) DEFAULT NULL,
  `chatcenter` text,
  `read_status` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) DEFAULT NULL,
  `is_type` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'post, event, any other type post''s comment',
  `id_of_type` int(11) DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `user_reacts` longtext COLLATE utf8_unicode_ci,
  `created_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `currencies`
--

CREATE TABLE `currencies` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `symbol` varchar(255) DEFAULT NULL,
  `paypal_supported` int(11) DEFAULT NULL,
  `stripe_supported` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `currencies`
--

INSERT INTO `currencies` (`id`, `name`, `code`, `symbol`, `paypal_supported`, `stripe_supported`) VALUES
(1, 'Leke', 'ALL', 'Lek', 0, 1),
(2, 'Dollars', 'USD', '$', 1, 1),
(3, 'Afghanis', 'AFN', '؋', 0, 1),
(4, 'Pesos', 'ARS', '$', 0, 1),
(5, 'Guilders', 'AWG', 'ƒ', 0, 1),
(6, 'Dollars', 'AUD', '$', 1, 1),
(7, 'New Manats', 'AZN', 'ман', 0, 1),
(8, 'Dollars', 'BSD', '$', 0, 1),
(9, 'Dollars', 'BBD', '$', 0, 1),
(10, 'Rubles', 'BYR', 'p.', 0, 0),
(11, 'Euro', 'EUR', '€', 1, 1),
(12, 'Dollars', 'BZD', 'BZ$', 0, 1),
(13, 'Dollars', 'BMD', '$', 0, 1),
(14, 'Bolivianos', 'BOB', '$b', 0, 1),
(15, 'Convertible Marka', 'BAM', 'KM', 0, 1),
(16, 'Pula', 'BWP', 'P', 0, 1),
(17, 'Leva', 'BGN', 'лв', 0, 1),
(18, 'Reais', 'BRL', 'R$', 1, 1),
(19, 'Pounds', 'GBP', '£', 1, 1),
(20, 'Dollars', 'BND', '$', 0, 1),
(21, 'Riels', 'KHR', '៛', 0, 1),
(22, 'Dollars', 'CAD', '$', 1, 1),
(23, 'Dollars', 'KYD', '$', 0, 1),
(24, 'Pesos', 'CLP', '$', 0, 1),
(25, 'Yuan Renminbi', 'CNY', '¥', 0, 1),
(26, 'Pesos', 'COP', '$', 0, 1),
(27, 'Colón', 'CRC', '₡', 0, 1),
(28, 'Kuna', 'HRK', 'kn', 0, 1),
(29, 'Pesos', 'CUP', '₱', 0, 0),
(30, 'Koruny', 'CZK', 'Kč', 1, 1),
(31, 'Kroner', 'DKK', 'kr', 1, 1),
(32, 'Pesos', 'DOP ', 'RD$', 0, 1),
(33, 'Dollars', 'XCD', '$', 0, 1),
(34, 'Pounds', 'EGP', '£', 0, 1),
(35, 'Colones', 'SVC', '$', 0, 0),
(36, 'Pounds', 'FKP', '£', 0, 1),
(37, 'Dollars', 'FJD', '$', 0, 1),
(38, 'Cedis', 'GHC', '¢', 0, 0),
(39, 'Pounds', 'GIP', '£', 0, 1),
(40, 'Quetzales', 'GTQ', 'Q', 0, 1),
(41, 'Pounds', 'GGP', '£', 0, 0),
(42, 'Dollars', 'GYD', '$', 0, 1),
(43, 'Lempiras', 'HNL', 'L', 0, 1),
(44, 'Dollars', 'HKD', '$', 1, 1),
(45, 'Forint', 'HUF', 'Ft', 1, 1),
(46, 'Kronur', 'ISK', 'kr', 0, 1),
(47, 'Rupees', 'INR', 'Rp', 1, 1),
(48, 'Rupiahs', 'IDR', 'Rp', 0, 1),
(49, 'Rials', 'IRR', '﷼', 0, 0),
(50, 'Pounds', 'IMP', '£', 0, 0),
(51, 'New Shekels', 'ILS', '₪', 1, 1),
(52, 'Dollars', 'JMD', 'J$', 0, 1),
(53, 'Yen', 'JPY', '¥', 1, 1),
(54, 'Pounds', 'JEP', '£', 0, 0),
(55, 'Tenge', 'KZT', 'лв', 0, 1),
(56, 'Won', 'KPW', '₩', 0, 0),
(57, 'Won', 'KRW', '₩', 0, 1),
(58, 'Soms', 'KGS', 'лв', 0, 1),
(59, 'Kips', 'LAK', '₭', 0, 1),
(60, 'Lati', 'LVL', 'Ls', 0, 0),
(61, 'Pounds', 'LBP', '£', 0, 1),
(62, 'Dollars', 'LRD', '$', 0, 1),
(63, 'Switzerland Francs', 'CHF', 'CHF', 1, 1),
(64, 'Litai', 'LTL', 'Lt', 0, 0),
(65, 'Denars', 'MKD', 'ден', 0, 1),
(66, 'Ringgits', 'MYR', 'RM', 1, 1),
(67, 'Rupees', 'MUR', '₨', 0, 1),
(68, 'Pesos', 'MXN', '$', 1, 1),
(69, 'Tugriks', 'MNT', '₮', 0, 1),
(70, 'Meticais', 'MZN', 'MT', 0, 1),
(71, 'Dollars', 'NAD', '$', 0, 1),
(72, 'Rupees', 'NPR', '₨', 0, 1),
(73, 'Guilders', 'ANG', 'ƒ', 0, 1),
(74, 'Dollars', 'NZD', '$', 1, 1),
(75, 'Cordobas', 'NIO', 'C$', 0, 1),
(76, 'Nairas', 'NGN', '₦', 0, 1),
(77, 'Krone', 'NOK', 'kr', 1, 1),
(78, 'Rials', 'OMR', '﷼', 0, 0),
(79, 'Rupees', 'PKR', '₨', 0, 1),
(80, 'Balboa', 'PAB', 'B/.', 0, 1),
(81, 'Guarani', 'PYG', 'Gs', 0, 1),
(82, 'Nuevos Soles', 'PEN', 'S/.', 0, 1),
(83, 'Pesos', 'PHP', 'Php', 1, 1),
(84, 'Zlotych', 'PLN', 'zł', 1, 1),
(85, 'Rials', 'QAR', '﷼', 0, 1),
(86, 'New Lei', 'RON', 'lei', 0, 1),
(87, 'Rubles', 'RUB', 'руб', 1, 1),
(88, 'Pounds', 'SHP', '£', 0, 1),
(89, 'Riyals', 'SAR', '﷼', 0, 1),
(90, 'Dinars', 'RSD', 'Дин.', 0, 1),
(91, 'Rupees', 'SCR', '₨', 0, 1),
(92, 'Dollars', 'SGD', '$', 1, 1),
(93, 'Dollars', 'SBD', '$', 0, 1),
(94, 'Shillings', 'SOS', 'S', 0, 1),
(95, 'Rand', 'ZAR', 'R', 0, 1),
(96, 'Rupees', 'LKR', '₨', 0, 1),
(97, 'Kronor', 'SEK', 'kr', 1, 1),
(98, 'Dollars', 'SRD', '$', 0, 1),
(99, 'Pounds', 'SYP', '£', 0, 0),
(100, 'New Dollars', 'TWD', 'NT$', 1, 1),
(101, 'Baht', 'THB', '฿', 1, 1),
(102, 'Dollars', 'TTD', 'TT$', 0, 1),
(103, 'Lira', 'TRY', 'TL', 0, 1),
(104, 'Liras', 'TRL', '£', 0, 0),
(105, 'Dollars', 'TVD', '$', 0, 0),
(106, 'Hryvnia', 'UAH', '₴', 0, 1),
(107, 'Pesos', 'UYU', '$U', 0, 1),
(108, 'Sums', 'UZS', 'лв', 0, 1),
(109, 'Bolivares Fuertes', 'VEF', 'Bs', 0, 0),
(110, 'Dong', 'VND', '₫', 0, 1),
(111, 'Rials', 'YER', '﷼', 0, 1),
(112, 'Zimbabwe Dollars', 'ZWD', 'Z$', 0, 0),
(113, 'Taka', 'BDT', '৳', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `publisher` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `publisher_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `event_date` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `event_time` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location` text COLLATE utf8_unicode_ci,
  `going_users_id` longtext COLLATE utf8_unicode_ci,
  `interested_users_id` longtext COLLATE utf8_unicode_ci,
  `thumbnail` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `banner` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `privacy` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci,
  `queue` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci,
  `exception` longtext COLLATE utf8mb4_unicode_ci,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feeling_and_activities`
--

CREATE TABLE `feeling_and_activities` (
  `feeling_and_activity_id` int(11) NOT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `icon` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `updated_at` varchar(100) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `feeling_and_activities`
--

INSERT INTO `feeling_and_activities` (`feeling_and_activity_id`, `type`, `title`, `icon`, `created_at`, `updated_at`) VALUES
(1, 'activity', 'Traveling', 'travelling.png', '2023-04-05 14:11:49', '2023-04-05 14:11:49'),
(2, 'activity', 'Watching', 'watch.png', '2023-04-05 14:11:49', '2023-04-05 14:11:49'),
(3, 'activity', 'Listening', 'listen.png', '2023-04-05 14:11:49', '2023-04-05 14:11:49'),
(4, 'activity', 'Playing', 'playing.png', '2023-04-05 14:11:49', '2023-04-05 14:11:49'),
(5, 'activity', 'Relaxed', 'relax.png', '2023-04-05 14:11:49', '2023-04-05 14:11:49'),
(6, 'feeling', 'Happy', 'happy.png', '2023-04-05 14:11:49', '2023-04-05 14:11:49'),
(7, 'feeling', 'Lovely', 'lovely.png', '2023-04-05 14:11:49', '2023-04-05 14:11:49'),
(8, 'feeling', 'Loved', 'loved.png', '2023-04-05 14:11:49', '2023-04-05 14:11:49'),
(9, 'feeling', 'Fun', 'fun.png', '2023-04-05 14:11:49', '2023-04-05 14:11:49'),
(10, 'feeling', 'Crazy', 'crazy.png', '2023-04-05 14:11:49', '2023-04-05 14:11:49'),
(11, 'feeling', 'Relaxed', 'relax.png', '2023-04-05 14:11:49', '2023-04-05 14:11:49'),
(12, 'feeling', 'Happy blessed', 'blessed.png', '2023-04-05 14:11:49', '2023-04-05 14:11:49'),
(13, 'feeling', 'Lovely Sad', 'r-cry1.png', '2023-04-05 14:11:49', '2023-04-05 14:11:49'),
(14, 'feeling', 'Loved Thankful', 'r-care.png', '2023-04-05 14:11:49', '2023-04-05 14:11:49'),
(15, 'feeling', 'Fun Cool', 'cool.png', '2023-04-05 14:11:49', '2023-04-05 14:11:49'),
(16, 'feeling', 'Crazy Surprised', 'amused.png', '2023-04-05 14:11:49', '2023-04-05 14:11:49'),
(17, 'feeling', 'Relaxed Angry', 'angry.png', '2023-04-05 14:11:49', '2023-04-05 14:11:49'),
(18, 'feeling', 'Relaxed Heartbroken', 'surprise.png', '2023-04-05 14:11:49', '2023-04-05 14:11:49');

-- --------------------------------------------------------

--
-- Table structure for table `followers`
--

CREATE TABLE `followers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `follow_id` int(11) DEFAULT NULL,
  `page_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `friendships`
--

CREATE TABLE `friendships` (
  `id` int(11) NOT NULL,
  `requester` int(11) DEFAULT NULL,
  `accepter` int(11) DEFAULT NULL,
  `importance` int(11) DEFAULT NULL,
  `is_accepted` int(11) DEFAULT NULL,
  `accepted_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `user_id` text COLLATE utf8_unicode_ci,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subtitle` varchar(300) COLLATE utf8_unicode_ci DEFAULT NULL,
  `privacy` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `group_type` varchar(300) COLLATE utf8_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `banner` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `about` longtext COLLATE utf8_unicode_ci,
  `status` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `is_accepted` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `role` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invites`
--

CREATE TABLE `invites` (
  `id` bigint(20) NOT NULL,
  `invite_sender_id` int(11) DEFAULT NULL,
  `invite_reciver_id` int(11) DEFAULT NULL,
  `is_accepted` int(11) NOT NULL DEFAULT '0',
  `event_id` int(11) DEFAULT NULL,
  `page_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `post_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE `languages` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phrase` varchar(300) COLLATE utf8_unicode_ci DEFAULT NULL,
  `translated` varchar(300) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`id`, `name`, `phrase`, `translated`, `created_at`, `updated_at`) VALUES
(1, 'english', 'English', 'English', '2023-04-05 11:34:21', '2023-04-05 11:34:21'),
(2, 'english', 'Login', 'Login', '2023-06-19 11:39:31', '2023-06-19 11:39:31'),
(3, 'english', 'Activate', 'Activate', '2023-10-09 10:35:35', '2023-10-09 10:35:35'),
(784, 'english', 'Email', 'Email', '2024-01-08 10:57:23', '2024-01-08 10:57:23'),
(785, 'english', 'Enter your email address', 'Enter your email address', '2024-01-08 10:57:23', '2024-01-08 10:57:23'),
(786, 'english', 'Password', 'Password', '2024-01-08 10:57:23', '2024-01-08 10:57:23'),
(787, 'english', 'Your password', 'Your password', '2024-01-08 10:57:23', '2024-01-08 10:57:23'),
(788, 'english', 'Remember me', 'Remember me', '2024-01-08 10:57:23', '2024-01-08 10:57:23'),
(789, 'english', 'Forgot your password?', 'Forgot your password?', '2024-01-08 10:57:23', '2024-01-08 10:57:23'),
(790, 'english', 'My Profile', 'My Profile', '2024-01-08 10:57:33', '2024-01-08 10:57:33'),
(791, 'english', 'Go to admin panel', 'Go to admin panel', '2024-01-08 10:57:33', '2024-01-08 10:57:33'),
(792, 'english', 'Addons', 'Addons', '2024-01-08 10:57:33', '2024-01-08 10:57:33'),
(793, 'english', 'Change Password', 'Change Password', '2024-01-08 10:57:33', '2024-01-08 10:57:33'),
(794, 'english', 'Log Out', 'Log Out', '2024-01-08 10:57:33', '2024-01-08 10:57:33'),
(795, 'english', 'Timeline', 'Timeline', '2024-01-08 10:57:33', '2024-01-08 10:57:33'),
(796, 'english', 'Profile', 'Profile', '2024-01-08 10:57:33', '2024-01-08 10:57:33'),
(797, 'english', 'Group', 'Group', '2024-01-08 10:57:33', '2024-01-08 10:57:33'),
(798, 'english', 'Page', 'Page', '2024-01-08 10:57:33', '2024-01-08 10:57:33'),
(799, 'english', 'Marketplace', 'Marketplace', '2024-01-08 10:57:33', '2024-01-08 10:57:33'),
(800, 'english', 'Video and Shorts', 'Video and Shorts', '2024-01-08 10:57:33', '2024-01-08 10:57:33'),
(801, 'english', 'Event', 'Event', '2024-01-08 10:57:33', '2024-01-08 10:57:33'),
(802, 'english', 'Blog', 'Blog', '2024-01-08 10:57:33', '2024-01-08 10:57:33'),
(803, 'english', 'About', 'About', '2024-01-08 10:57:33', '2024-01-08 10:57:33'),
(804, 'english', 'Privacy Policy', 'Privacy Policy', '2024-01-08 10:57:33', '2024-01-08 10:57:33'),
(805, 'english', 'Create story', 'Create story', '2024-01-08 10:57:33', '2024-01-08 10:57:33'),
(806, 'english', 'What\'s on your mind ____', 'What\'s on your mind ____', '2024-01-08 10:57:33', '2024-01-08 10:57:33'),
(807, 'english', 'Create Post', 'Create Post', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(808, 'english', 'Public', 'Public', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(809, 'english', 'Only Me', 'Only Me', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(810, 'english', 'Friends', 'Friends', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(811, 'english', 'Click to browse', 'Click to browse', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(812, 'english', 'Tag People', 'Tag People', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(813, 'english', 'Tagged', 'Tagged', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(814, 'english', 'Search more peoples', 'Search more peoples', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(815, 'english', 'Suggestions', 'Suggestions', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(816, 'english', 'What are you doing', 'What are you doing', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(817, 'english', 'Activities', 'Activities', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(818, 'english', 'How are you feeling', 'How are you feeling', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(819, 'english', 'Feelings', 'Feelings', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(820, 'english', 'Search for location', 'Search for location', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(821, 'english', 'Determine your location', 'Determine your location', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(822, 'english', 'Add to your post', 'Add to your post', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(823, 'english', 'Publish Now', 'Publish Now', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(824, 'english', 'Processing', 'Processing', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(825, 'english', 'Uploading', 'Uploading', '2024-01-08 10:57:34', '2024-01-08 10:57:34'),
(826, 'english', 'Link Copied', 'Link Copied', '2024-01-08 10:57:35', '2024-01-08 10:57:35'),
(827, 'english', 'Hi', 'Hi', '2024-01-08 10:57:35', '2024-01-08 10:57:35'),
(828, 'english', 'Good Afternoon', 'Good Afternoon', '2024-01-08 10:57:35', '2024-01-08 10:57:35'),
(829, 'english', 'Sponsored', 'Sponsored', '2024-01-08 10:57:35', '2024-01-08 10:57:35'),
(830, 'english', 'Active users', 'Active users', '2024-01-08 10:57:35', '2024-01-08 10:57:35'),
(831, 'english', 'Loading...', 'Loading...', '2024-01-08 10:57:35', '2024-01-08 10:57:35'),
(832, 'english', 'Create new story', 'Create new story', '2024-01-08 10:57:35', '2024-01-08 10:57:35'),
(833, 'english', 'Stories', 'Stories', '2024-01-08 10:57:35', '2024-01-08 10:57:35'),
(834, 'english', 'Confirmation', 'Confirmation', '2024-01-08 10:57:35', '2024-01-08 10:57:35'),
(835, 'english', 'Are you sure', 'Are you sure', '2024-01-08 10:57:35', '2024-01-08 10:57:35'),
(836, 'english', 'Cancel', 'Cancel', '2024-01-08 10:57:35', '2024-01-08 10:57:35'),
(837, 'english', 'Continue', 'Continue', '2024-01-08 10:57:35', '2024-01-08 10:57:35'),
(838, 'english', '404 page not found', '404 page not found', '2024-01-08 10:57:37', '2024-01-08 10:57:37'),
(839, 'english', '404 page not found', '404 page not found', '2024-01-08 10:57:37', '2024-01-08 10:57:37'),
(840, 'english', 'This page is not available, please provide a valid URL', 'This page is not available, please provide a valid URL', '2024-01-08 10:57:37', '2024-01-08 10:57:37'),
(841, 'english', 'Back', 'Back', '2024-01-08 10:57:37', '2024-01-08 10:57:37'),
(842, 'english', 'Dashboard', 'Dashboard', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(843, 'english', 'User', 'User', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(844, 'english', 'Users', 'Users', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(845, 'english', 'Create new user', 'Create new user', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(846, 'english', 'Pages', 'Pages', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(847, 'english', 'Create Page', 'Create Page', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(848, 'english', 'Category', 'Category', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(849, 'english', 'Create Category', 'Create Category', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(850, 'english', 'Brand', 'Brand', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(851, 'english', 'Blogs', 'Blogs', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(852, 'english', 'Create Blog', 'Create Blog', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(853, 'english', 'Sponsored Post', 'Sponsored Post', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(854, 'english', 'Ads', 'Ads', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(855, 'english', 'Create Ad', 'Create Ad', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(856, 'english', 'Reported Post', 'Reported Post', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(857, 'english', 'Payment history', 'Payment history', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(858, 'english', 'Settings', 'Settings', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(859, 'english', 'System Setting', 'System Setting', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(860, 'english', 'Amazon s3 settings', 'Amazon s3 settings', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(861, 'english', 'Custom Pages', 'Custom Pages', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(862, 'english', 'Payment Setting', 'Payment Setting', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(863, 'english', 'Language Setting', 'Language Setting', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(864, 'english', 'SMTP Setting', 'SMTP Setting', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(865, 'english', 'Visit Website', 'Visit Website', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(866, 'english', 'My Account', 'My Account', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(867, 'english', 'Total Users', 'Total Users', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(868, 'english', 'Post', 'Post', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(869, 'english', 'Total Posts', 'Total Posts', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(870, 'english', 'Total Pages', 'Total Pages', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(871, 'english', 'Total Blogs', 'Total Blogs', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(872, 'english', 'Ad', 'Ad', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(873, 'english', 'Total Sponsored Posts', 'Total Sponsored Posts', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(874, 'english', 'Marketplace Products', 'Marketplace Products', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(875, 'english', 'Total Products', 'Total Products', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(876, 'english', 'By ____', 'By ____', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(877, 'english', 'Number of user', 'Number of user', '2024-01-08 10:57:42', '2024-01-08 10:57:42'),
(878, 'english', 'System Settings', 'System Settings', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(879, 'english', 'System Name', 'System Name', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(880, 'english', 'System Title', 'System Title', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(881, 'english', 'System Email', 'System Email', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(882, 'english', 'System Phone', 'System Phone', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(883, 'english', 'System Fax', 'System Fax', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(884, 'english', 'Address', 'Address', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(885, 'english', 'System currency', 'System currency', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(886, 'english', 'System language', 'System language', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(887, 'english', 'Public signup', 'Public signup', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(888, 'english', 'enabled', 'enabled', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(889, 'english', 'disabled', 'disabled', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(890, 'english', 'Ad charge per day', 'Ad charge per day', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(891, 'english', 'Footer', 'Footer', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(892, 'english', 'Footer Link', 'Footer Link', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(893, 'english', 'Aoogle Analytics Id', 'Aoogle Analytics Id', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(894, 'english', 'Commission on Paid content', 'Commission on Paid content', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(895, 'english', 'Update', 'Update', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(896, 'english', 'Product Update', 'Product Update', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(897, 'english', 'SYSTEM LOGO', 'SYSTEM LOGO', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(898, 'english', 'Dark logo', 'Dark logo', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(899, 'english', 'Light logo', 'Light logo', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(900, 'english', 'Favicon', 'Favicon', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(901, 'english', 'Update Logo', 'Update Logo', '2024-01-08 10:57:47', '2024-01-08 10:57:47'),
(902, 'english', 'Version updated successfully', 'Version updated successfully', '2024-01-08 10:58:12', '2024-01-08 10:58:12'),
(903, 'english', 'Not found', 'Not found', '2024-01-08 10:58:17', '2024-01-08 10:58:17'),
(904, 'english', 'About this application', 'About this application', '2024-01-08 10:58:19', '2024-01-08 10:58:19'),
(905, 'english', 'Software version', 'Software version', '2024-01-08 10:58:19', '2024-01-08 10:58:19'),
(906, 'english', 'Check update', 'Check update', '2024-01-08 10:58:19', '2024-01-08 10:58:19'),
(907, 'english', 'PHP version', 'PHP version', '2024-01-08 10:58:19', '2024-01-08 10:58:19'),
(908, 'english', 'Curl enable', 'Curl enable', '2024-01-08 10:58:19', '2024-01-08 10:58:19'),
(909, 'english', 'Purchase code', 'Purchase code', '2024-01-08 10:58:19', '2024-01-08 10:58:19'),
(910, 'english', 'Product license', 'Product license', '2024-01-08 10:58:19', '2024-01-08 10:58:19'),
(911, 'english', 'invalid', 'invalid', '2024-01-08 10:58:19', '2024-01-08 10:58:19'),
(912, 'english', 'Enter valid purchase code', 'Enter valid purchase code', '2024-01-08 10:58:19', '2024-01-08 10:58:19'),
(913, 'english', 'Customer support status', 'Customer support status', '2024-01-08 10:58:19', '2024-01-08 10:58:19'),
(914, 'english', 'Support expiry date', 'Support expiry date', '2024-01-08 10:58:19', '2024-01-08 10:58:19'),
(915, 'english', 'Customer name', 'Customer name', '2024-01-08 10:58:19', '2024-01-08 10:58:19'),
(916, 'english', 'Get customer support', 'Get customer support', '2024-01-08 10:58:19', '2024-01-08 10:58:19'),
(917, 'english', 'Customer support', 'Customer support', '2024-01-08 10:58:19', '2024-01-08 10:58:19'),
(918, 'english', 'Enter your purchase code', 'Enter your purchase code', '2024-01-08 10:58:19', '2024-01-08 10:58:19'),
(919, 'english', 'Invalid purchase code', 'Invalid purchase code', '2024-01-08 10:58:19', '2024-01-08 10:58:19'),
(920, 'english', 'Submit', 'Submit', '2024-01-08 10:58:19', '2024-01-08 10:58:19'),
(921, 'english', 'My articles', 'My articles', '2024-01-08 10:59:07', '2024-01-08 10:59:07'),
(922, 'english', 'Create New Article', 'Create New Article', '2024-01-08 10:59:09', '2024-01-08 10:59:09'),
(923, 'english', 'Title', 'Title', '2024-01-08 10:59:09', '2024-01-08 10:59:09'),
(924, 'english', 'Select Category', 'Select Category', '2024-01-08 10:59:09', '2024-01-08 10:59:09'),
(925, 'english', 'Tags', 'Tags', '2024-01-08 10:59:09', '2024-01-08 10:59:09'),
(926, 'english', 'Description', 'Description', '2024-01-08 10:59:09', '2024-01-08 10:59:09'),
(927, 'english', 'Image', 'Image', '2024-01-08 10:59:09', '2024-01-08 10:59:09'),
(928, 'english', 'Watch', 'Watch', '2024-01-08 11:04:41', '2024-01-08 11:04:41'),
(929, 'english', 'Create Video & Shorts ', 'Create Video & Shorts ', '2024-01-08 11:04:41', '2024-01-08 11:04:41'),
(930, 'english', 'Create', 'Create', '2024-01-08 11:04:41', '2024-01-08 11:04:41'),
(931, 'english', 'Shorts', 'Shorts', '2024-01-08 11:04:41', '2024-01-08 11:04:41'),
(932, 'english', 'Videos', 'Videos', '2024-01-08 11:04:41', '2024-01-08 11:04:41'),
(933, 'english', 'Good Evening', 'Good Evening', '2024-01-08 11:04:41', '2024-01-08 11:04:41'),
(934, 'english', 'Private', 'Private', '2024-01-08 11:04:46', '2024-01-08 11:04:46'),
(935, 'english', 'Video', 'Video', '2024-01-08 11:04:46', '2024-01-08 11:04:46'),
(936, 'english', 'Video/Shorts', 'Video/Shorts', '2024-01-08 11:04:46', '2024-01-08 11:04:46'),
(937, 'english', 'Install addon', 'Install addon', '2024-01-08 11:08:36', '2024-01-08 11:08:36'),
(938, 'english', 'Sl No', 'Sl No', '2024-01-08 11:08:36', '2024-01-08 11:08:36'),
(939, 'english', 'Name', 'Name', '2024-01-08 11:08:36', '2024-01-08 11:08:36'),
(940, 'english', 'Version', 'Version', '2024-01-08 11:08:36', '2024-01-08 11:08:36'),
(941, 'english', 'Status', 'Status', '2024-01-08 11:08:36', '2024-01-08 11:08:36'),
(942, 'english', 'Action', 'Action', '2024-01-08 11:08:36', '2024-01-08 11:08:36'),
(943, 'english', 'All Blogs', 'All Blogs', '2024-01-08 11:08:39', '2024-01-08 11:08:39'),
(944, 'english', 'Blog owner', 'Blog owner', '2024-01-08 11:08:39', '2024-01-08 11:08:39'),
(945, 'english', 'Events', 'Events', '2024-01-08 11:08:44', '2024-01-08 11:08:44'),
(946, 'english', 'Create Event', 'Create Event', '2024-01-08 11:08:44', '2024-01-08 11:08:44'),
(947, 'english', 'My Event', 'My Event', '2024-01-08 11:08:44', '2024-01-08 11:08:44'),
(948, 'english', 'Event Name', 'Event Name', '2024-01-08 11:08:46', '2024-01-08 11:08:46'),
(949, 'english', 'Event Date', 'Event Date', '2024-01-08 11:08:46', '2024-01-08 11:08:46'),
(950, 'english', 'Event Time', 'Event Time', '2024-01-08 11:08:46', '2024-01-08 11:08:46'),
(951, 'english', 'Location', 'Location', '2024-01-08 11:08:46', '2024-01-08 11:08:46'),
(952, 'english', 'Event Description', 'Event Description', '2024-01-08 11:08:46', '2024-01-08 11:08:46'),
(953, 'english', 'Cover Photo', 'Cover Photo', '2024-01-08 11:08:46', '2024-01-08 11:08:46'),
(954, 'english', 'Memories', 'Memories', '2024-02-01 07:47:19', '2024-02-01 07:47:19'),
(955, 'english', 'No memories to view or share today.', 'No memories to view or share today.', '2024-02-01 07:47:26', '2024-02-01 07:47:26'),
(956, 'english', 'We\'ll notify you when there are some to reminisce about', 'We\'ll notify you when there are some to reminisce about', '2024-02-01 07:47:26', '2024-02-01 07:47:26'),
(957, 'english', 'Badge', 'Badge', '2024-02-19 09:18:45', '2024-02-19 09:18:45'),
(958, 'english', 'Build trust with Sociopro Verified', 'Build trust with Sociopro Verified', '2024-02-19 09:25:58', '2024-02-19 09:25:58'),
(959, 'english', 'A verified badge', 'A verified badge', '2024-02-19 09:25:58', '2024-02-19 09:25:58'),
(960, 'english', 'Your audience can trust that you\"re a real person sharing your real stories.', 'Your audience can trust that you\"re a real person sharing your real stories.', '2024-02-19 09:25:58', '2024-02-19 09:25:58'),
(961, 'english', 'Increased account protection', 'Increased account protection', '2024-02-19 09:25:58', '2024-02-19 09:25:58'),
(962, 'english', 'Worry less about impersonation with proactive identity monitoring.', 'Worry less about impersonation with proactive identity monitoring.', '2024-02-19 09:25:58', '2024-02-19 09:25:58'),
(963, 'english', 'Next', 'Next', '2024-02-19 09:25:58', '2024-02-19 09:25:58'),
(964, 'english', 'Confirm and pay', 'Confirm and pay', '2024-02-19 09:26:03', '2024-02-19 09:26:03'),
(965, 'english', 'You are subscribing to Meta Verified on Sociopro.', 'You are subscribing to Meta Verified on Sociopro.', '2024-02-19 09:26:03', '2024-02-19 09:26:03'),
(966, 'english', 'Sociopro', 'Sociopro', '2024-02-19 09:26:03', '2024-02-19 09:26:03'),
(967, 'english', 'You\'ll be billed', 'You\'ll be billed', '2024-02-19 09:26:03', '2024-02-19 09:26:03'),
(968, 'english', 'per month.', 'per month.', '2024-02-19 09:26:03', '2024-02-19 09:26:03'),
(969, 'english', 'What you get with your subscription.', 'What you get with your subscription.', '2024-02-19 09:26:03', '2024-02-19 09:26:03'),
(970, 'english', 'Pay Now', 'Pay Now', '2024-02-19 09:26:03', '2024-02-19 09:26:03'),
(971, 'english', 'Good Morning', 'Good Morning', '2024-03-17 05:23:55', '2024-03-17 05:23:55'),
(972, 'english', 'Edit your profile', 'Edit your profile', '2024-03-17 05:24:10', '2024-03-17 05:24:10'),
(973, 'english', 'Edit Profile', 'Edit Profile', '2024-03-17 05:24:10', '2024-03-17 05:24:10'),
(974, 'english', 'Update your cover photo', 'Update your cover photo', '2024-03-17 05:24:10', '2024-03-17 05:24:10'),
(975, 'english', 'Edit Cover Photo', 'Edit Cover Photo', '2024-03-17 05:24:10', '2024-03-17 05:24:10'),
(976, 'english', 'Photo', 'Photo', '2024-03-17 05:24:10', '2024-03-17 05:24:10'),
(977, 'english', 'Intro', 'Intro', '2024-03-17 05:24:10', '2024-03-17 05:24:10'),
(978, 'english', 'Edit Bio', 'Edit Bio', '2024-03-17 05:24:10', '2024-03-17 05:24:10'),
(979, 'english', 'Save Bio', 'Save Bio', '2024-03-17 05:24:10', '2024-03-17 05:24:10'),
(980, 'english', 'Info', 'Info', '2024-03-17 05:24:10', '2024-03-17 05:24:10'),
(981, 'english', 'Studied at', 'Studied at', '2024-03-17 05:24:10', '2024-03-17 05:24:10'),
(982, 'english', 'From', 'From', '2024-03-17 05:24:10', '2024-03-17 05:24:10'),
(983, 'english', 'male', 'male', '2024-03-17 05:24:10', '2024-03-17 05:24:10'),
(984, 'english', 'Joined', 'Joined', '2024-03-17 05:24:10', '2024-03-17 05:24:10'),
(985, 'english', 'Edit info', 'Edit info', '2024-03-17 05:24:10', '2024-03-17 05:24:10'),
(986, 'english', 'See All', 'See All', '2024-03-17 05:24:11', '2024-03-17 05:24:11'),
(987, 'english', 'All Groups', 'All Groups', '2024-03-17 05:24:12', '2024-03-17 05:24:12'),
(988, 'english', ' Create New Group', ' Create New Group', '2024-03-17 05:24:12', '2024-03-17 05:24:12'),
(989, 'english', 'Groups', 'Groups', '2024-03-17 05:24:12', '2024-03-17 05:24:12'),
(990, 'english', 'Group you Manage', 'Group you Manage', '2024-03-17 05:24:12', '2024-03-17 05:24:12'),
(991, 'english', 'Group you Joined', 'Group you Joined', '2024-03-17 05:24:12', '2024-03-17 05:24:12'),
(992, 'english', 'My Pages', 'My Pages', '2024-03-17 05:24:13', '2024-03-17 05:24:13'),
(993, 'english', 'Suggested Pages', 'Suggested Pages', '2024-03-17 05:24:13', '2024-03-17 05:24:13'),
(994, 'english', 'Liked Pages', 'Liked Pages', '2024-03-17 05:24:13', '2024-03-17 05:24:13'),
(995, 'english', 'Create Product', 'Create Product', '2024-03-17 05:24:14', '2024-03-17 05:24:14'),
(996, 'english', 'My Products', 'My Products', '2024-03-17 05:24:14', '2024-03-17 05:24:14'),
(997, 'english', 'Saved Product', 'Saved Product', '2024-03-17 05:24:14', '2024-03-17 05:24:14'),
(998, 'english', 'Saved', 'Saved', '2024-03-17 05:24:14', '2024-03-17 05:24:14'),
(999, 'english', 'Filters', 'Filters', '2024-03-17 05:24:14', '2024-03-17 05:24:14'),
(1000, 'english', 'Condition', 'Condition', '2024-03-17 05:24:14', '2024-03-17 05:24:14'),
(1001, 'english', 'Used', 'Used', '2024-03-17 05:24:14', '2024-03-17 05:24:14'),
(1002, 'english', 'New', 'New', '2024-03-17 05:24:14', '2024-03-17 05:24:14'),
(1003, 'english', 'Select Brand', 'Select Brand', '2024-03-17 05:24:14', '2024-03-17 05:24:14'),
(1004, 'english', 'All Users', 'All Users', '2024-03-17 05:25:36', '2024-03-17 05:25:36'),
(1005, 'english', 'Create user', 'Create user', '2024-03-17 05:25:36', '2024-03-17 05:25:36'),
(1006, 'english', 'Create a new user', 'Create a new user', '2024-03-17 05:25:36', '2024-03-17 05:25:36'),
(1007, 'english', 'Actions', 'Actions', '2024-03-17 05:25:36', '2024-03-17 05:25:36'),
(1008, 'english', 'All Sponsors', 'All Sponsors', '2024-03-17 05:25:45', '2024-03-17 05:25:45'),
(1009, 'english', 'Start Date', 'Start Date', '2024-03-17 05:25:45', '2024-03-17 05:25:45'),
(1010, 'english', 'Payment Settings', 'Payment Settings', '2024-03-17 05:25:49', '2024-03-17 05:25:49'),
(1011, 'english', 'Profile Picture', 'Profile Picture', '2024-03-31 05:02:17', '2024-03-31 05:02:17'),
(1012, 'english', 'Enter your name', 'Enter your name', '2024-03-31 05:02:17', '2024-03-31 05:02:17'),
(1013, 'english', 'Nickname', 'Nickname', '2024-03-31 05:02:17', '2024-03-31 05:02:17'),
(1014, 'english', 'Enter your nickname name', 'Enter your nickname name', '2024-03-31 05:02:17', '2024-03-31 05:02:17'),
(1015, 'english', 'Marital status', 'Marital status', '2024-03-31 05:02:17', '2024-03-31 05:02:17'),
(1016, 'english', 'Enter your marital status', 'Enter your marital status', '2024-03-31 05:02:17', '2024-03-31 05:02:17'),
(1017, 'english', 'Phone', 'Phone', '2024-03-31 05:02:17', '2024-03-31 05:02:17'),
(1018, 'english', 'Enter your phone number', 'Enter your phone number', '2024-03-31 05:02:17', '2024-03-31 05:02:17'),
(1019, 'english', 'Date of birth', 'Date of birth', '2024-03-31 05:02:17', '2024-03-31 05:02:17'),
(1020, 'english', 'Your date of birth', 'Your date of birth', '2024-03-31 05:02:17', '2024-03-31 05:02:17'),
(1021, 'english', 'Update Profile', 'Update Profile', '2024-03-31 05:02:17', '2024-03-31 05:02:17'),
(1022, 'english', 'Profile updated successfully', 'Profile updated successfully', '2024-03-31 05:02:31', '2024-03-31 05:02:31'),
(1023, 'english', 'Just now', 'Just now', '2024-03-31 05:02:32', '2024-03-31 05:02:32'),
(1024, 'english', 'Copy Link', 'Copy Link', '2024-03-31 05:02:32', '2024-03-31 05:02:32'),
(1025, 'english', 'Edit post', 'Edit post', '2024-03-31 05:02:32', '2024-03-31 05:02:32'),
(1026, 'english', 'Edit', 'Edit', '2024-03-31 05:02:32', '2024-03-31 05:02:32'),
(1027, 'english', 'Delete', 'Delete', '2024-03-31 05:02:32', '2024-03-31 05:02:32'),
(1028, 'english', 'Report Post', 'Report Post', '2024-03-31 05:02:32', '2024-03-31 05:02:32'),
(1029, 'english', 'Report', 'Report', '2024-03-31 05:02:32', '2024-03-31 05:02:32'),
(1030, 'english', 'Preview', 'Preview', '2024-03-31 05:02:32', '2024-03-31 05:02:32'),
(1031, 'english', 'Like', 'Like', '2024-03-31 05:02:32', '2024-03-31 05:02:32'),
(1032, 'english', 'Comments', 'Comments', '2024-03-31 05:02:32', '2024-03-31 05:02:32'),
(1033, 'english', 'Share post', 'Share post', '2024-03-31 05:02:32', '2024-03-31 05:02:32'),
(1034, 'english', 'Share', 'Share', '2024-03-31 05:02:32', '2024-03-31 05:02:32'),
(1035, 'english', 'No data found!', 'No data found!', '2024-03-31 05:02:42', '2024-03-31 05:02:42'),
(1036, 'english', 'Please go back', 'Please go back', '2024-03-31 05:02:42', '2024-03-31 05:02:42'),
(1037, 'english', 'My Blog', 'My Blog', '2024-03-31 05:02:44', '2024-03-31 05:02:44'),
(1038, 'english', 'year', 'year', '2024-03-31 05:02:53', '2024-03-31 05:02:53'),
(1039, 'english', 'month', 'month', '2024-03-31 05:02:53', '2024-03-31 05:02:53'),
(1040, 'english', 'day', 'day', '2024-03-31 05:02:53', '2024-03-31 05:02:53'),
(1041, 'english', 'ago', 'ago', '2024-03-31 05:02:53', '2024-03-31 05:02:53'),
(1042, 'english', 'Page Name', 'Page Name', '2024-04-03 07:27:47', '2024-04-03 07:27:47'),
(1043, 'english', 'Page BIO', 'Page BIO', '2024-04-03 07:27:47', '2024-04-03 07:27:47'),
(1044, 'english', 'Profile Photo', 'Profile Photo', '2024-04-03 07:27:47', '2024-04-03 07:27:47'),
(1045, 'english', 'Page Category', 'Page Category', '2024-04-03 07:27:47', '2024-04-03 07:27:47'),
(1046, 'english', 'Currency', 'Currency', '2024-04-03 07:27:57', '2024-04-03 07:27:57'),
(1047, 'english', 'Select Currency', 'Select Currency', '2024-04-03 07:27:57', '2024-04-03 07:27:57'),
(1048, 'english', 'Price', 'Price', '2024-04-03 07:27:57', '2024-04-03 07:27:57'),
(1049, 'english', 'Select Condition', 'Select Condition', '2024-04-03 07:27:57', '2024-04-03 07:27:57'),
(1050, 'english', 'Select Status', 'Select Status', '2024-04-03 07:27:57', '2024-04-03 07:27:57'),
(1051, 'english', 'In Stock', 'In Stock', '2024-04-03 07:27:57', '2024-04-03 07:27:57'),
(1052, 'english', 'Out Of Stock', 'Out Of Stock', '2024-04-03 07:27:57', '2024-04-03 07:27:57'),
(1053, 'english', 'Buy link', 'Buy link', '2024-04-03 07:27:57', '2024-04-03 07:27:57'),
(1054, 'english', 'Enter the buy link', 'Enter the buy link', '2024-04-03 07:27:57', '2024-04-03 07:27:57'),
(1055, 'english', 'Product Image', 'Product Image', '2024-04-03 07:27:57', '2024-04-03 07:27:57'),
(1056, 'english', 'Group Title', 'Group Title', '2024-04-03 07:29:34', '2024-04-03 07:29:34'),
(1057, 'english', 'Group Sub Title', 'Group Sub Title', '2024-04-03 07:29:34', '2024-04-03 07:29:34'),
(1058, 'english', 'Update Profile Photo', 'Update Profile Photo', '2024-04-03 07:29:34', '2024-04-03 07:29:34'),
(1059, 'english', 'Active', 'Active', '2024-04-03 07:29:34', '2024-04-03 07:29:34'),
(1060, 'english', 'Deactive', 'Deactive', '2024-04-03 07:29:34', '2024-04-03 07:29:34'),
(1061, 'english', 'Create Group', 'Create Group', '2024-04-03 07:29:34', '2024-04-03 07:29:34'),
(1062, 'english', 'My Friends', 'My Friends', '2024-04-03 07:29:44', '2024-04-03 07:29:44'),
(1063, 'english', 'Friend Requests', 'Friend Requests', '2024-04-03 07:29:44', '2024-04-03 07:29:44'),
(1064, 'english', 'Add Photo To Album', 'Add Photo To Album', '2024-04-03 07:29:46', '2024-04-03 07:29:46'),
(1065, 'english', 'Add Photo/Album', 'Add Photo/Album', '2024-04-03 07:29:46', '2024-04-03 07:29:46'),
(1066, 'english', 'Your Photos', 'Your Photos', '2024-04-03 07:29:46', '2024-04-03 07:29:46'),
(1067, 'english', 'Album', 'Album', '2024-04-03 07:29:46', '2024-04-03 07:29:46'),
(1068, 'english', 'Create Album', 'Create Album', '2024-04-03 07:29:46', '2024-04-03 07:29:46'),
(1069, 'english', 'Album title', 'Album title', '2024-04-03 07:29:52', '2024-04-03 07:29:52'),
(1070, 'english', 'Album subtitle', 'Album subtitle', '2024-04-03 07:29:52', '2024-04-03 07:29:52'),
(1071, 'english', 'Thumbnail', 'Thumbnail', '2024-04-03 07:29:52', '2024-04-03 07:29:52'),
(1072, 'english', 'Share the post on', 'Share the post on', '2024-04-03 07:30:17', '2024-04-03 07:30:17'),
(1073, 'english', 'My Timeline', 'My Timeline', '2024-04-03 07:30:17', '2024-04-03 07:30:17'),
(1074, 'english', 'Send in Message', 'Send in Message', '2024-04-03 07:30:17', '2024-04-03 07:30:17'),
(1075, 'english', 'Share to a Group', 'Share to a Group', '2024-04-03 07:30:17', '2024-04-03 07:30:17'),
(1076, 'english', 'Razorpay', 'Razorpay', '2024-04-03 07:37:02', '2024-04-03 07:37:02'),
(1077, 'english', 'Key id', 'Key id', '2024-04-03 07:37:02', '2024-04-03 07:37:02'),
(1078, 'english', 'Secret\r\n                                            key', 'Secret\r\n                                            key', '2024-04-03 07:37:02', '2024-04-03 07:37:02'),
(1079, 'english', 'Theme\r\n                                            color', 'Theme\r\n                                            color', '2024-04-03 07:37:02', '2024-04-03 07:37:02'),
(1080, 'english', '*Please enter HEX color\r\n                                            code.', '*Please enter HEX color\r\n                                            code.', '2024-04-03 07:37:02', '2024-04-03 07:37:02'),
(1081, 'english', 'Stripe', 'Stripe', '2024-04-03 07:37:02', '2024-04-03 07:37:02'),
(1082, 'english', 'Live mode', 'Live mode', '2024-04-03 07:37:02', '2024-04-03 07:37:02'),
(1083, 'english', 'Public key', 'Public key', '2024-04-03 07:37:02', '2024-04-03 07:37:02'),
(1084, 'english', 'Secret key', 'Secret key', '2024-04-03 07:37:02', '2024-04-03 07:37:02'),
(1085, 'english', 'Public live key', 'Public live key', '2024-04-03 07:37:02', '2024-04-03 07:37:02'),
(1086, 'english', 'Secret live key', 'Secret live key', '2024-04-03 07:37:02', '2024-04-03 07:37:02'),
(1087, 'english', 'Paypal', 'Paypal', '2024-04-03 07:37:02', '2024-04-03 07:37:02'),
(1088, 'english', 'Sandbox\r\n                                                client id', 'Sandbox\r\n                                                client id', '2024-04-03 07:37:02', '2024-04-03 07:37:02'),
(1089, 'english', 'Sandbox secret key', 'Sandbox secret key', '2024-04-03 07:37:02', '2024-04-03 07:37:02'),
(1090, 'english', 'Production client id', 'Production client id', '2024-04-03 07:37:02', '2024-04-03 07:37:02'),
(1091, 'english', 'Flutterwave', 'Flutterwave', '2024-04-03 07:37:02', '2024-04-03 07:37:02'),
(1092, 'english', 'Encryption key', 'Encryption key', '2024-04-03 07:37:02', '2024-04-03 07:37:02'),
(1093, 'english', 'Save settings', 'Save settings', '2024-04-03 07:37:02', '2024-04-03 07:37:02'),
(1094, 'english', 'SYSTEM Theme Color', 'SYSTEM Theme Color', '2024-04-03 07:40:43', '2024-04-03 07:40:43'),
(1095, 'english', 'Default', 'Default', '2024-04-03 07:40:43', '2024-04-03 07:40:43'),
(1096, 'english', 'Create Products', 'Create Products', '2024-04-03 07:57:45', '2024-04-03 07:57:45'),
(1097, 'english', 'Your videos', 'Your videos', '2024-04-03 08:10:01', '2024-04-03 08:10:01'),
(1098, 'english', 'All Product Categories', 'All Product Categories', '2024-04-03 09:04:08', '2024-04-03 09:04:08'),
(1099, 'english', 'Category Name', 'Category Name', '2024-04-03 09:04:08', '2024-04-03 09:04:08'),
(1100, 'english', 'DATE', 'DATE', '2024-04-03 09:04:08', '2024-04-03 09:04:08'),
(1101, 'english', 'View', 'View', '2024-04-03 09:04:09', '2024-04-03 09:04:09'),
(1102, 'english', 'Product Category', 'Product Category', '2024-04-03 09:04:09', '2024-04-03 09:04:09'),
(1103, 'english', 'All Product Brand ', 'All Product Brand ', '2024-04-03 09:04:21', '2024-04-03 09:04:21'),
(1104, 'english', 'Brand Name', 'Brand Name', '2024-04-03 09:04:21', '2024-04-03 09:04:21'),
(1105, 'english', 'Product Brand', 'Product Brand', '2024-04-03 09:04:23', '2024-04-03 09:04:23'),
(1106, 'english', 'Marketplace Product Added Successfully', 'Marketplace Product Added Successfully', '2024-04-03 09:07:48', '2024-04-03 09:07:48'),
(1107, 'english', 'Previous', 'Previous', '2024-04-03 09:08:05', '2024-04-03 09:08:05'),
(1108, 'english', 'Details', 'Details', '2024-04-03 09:08:05', '2024-04-03 09:08:05'),
(1109, 'english', 'Buy Now', 'Buy Now', '2024-04-03 09:08:05', '2024-04-03 09:08:05'),
(1110, 'english', 'Listed', 'Listed', '2024-04-03 09:08:05', '2024-04-03 09:08:05'),
(1111, 'english', 'Published By', 'Published By', '2024-04-03 09:08:05', '2024-04-03 09:08:05'),
(1112, 'english', 'Share Product', 'Share Product', '2024-04-03 09:08:05', '2024-04-03 09:08:05'),
(1113, 'english', 'Related Product', 'Related Product', '2024-04-03 09:08:05', '2024-04-03 09:08:05'),
(1114, 'english', 'Saved Successfully', 'Saved Successfully', '2024-04-03 09:11:19', '2024-04-03 09:11:19'),
(1115, 'english', 'Unsaved Successfully', 'Unsaved Successfully', '2024-04-03 09:11:22', '2024-04-03 09:11:22'),
(1116, 'english', 'Listed by', 'Listed by', '2024-04-03 09:12:19', '2024-04-03 09:12:19'),
(1117, 'english', 'Message', 'Message', '2024-04-03 09:17:11', '2024-04-03 09:17:11'),
(1118, 'english', 'Edit Product', 'Edit Product', '2024-04-03 09:29:18', '2024-04-03 09:29:18'),
(1119, 'english', 'Previous Uploaded Image', 'Previous Uploaded Image', '2024-04-03 09:31:43', '2024-04-03 09:31:43'),
(1120, 'english', 'Marketplace Product Updated Successfully', 'Marketplace Product Updated Successfully', '2024-04-03 09:31:58', '2024-04-03 09:31:58'),
(1121, 'english', 'Sold', 'Sold', '2024-04-03 10:11:04', '2024-04-03 10:11:04'),
(1122, 'english', 'Add a new user', 'Add a new user', '2024-04-03 10:14:40', '2024-04-03 10:14:40'),
(1123, 'english', 'Email address', 'Email address', '2024-04-03 10:14:40', '2024-04-03 10:14:40'),
(1124, 'english', 'Gender', 'Gender', '2024-04-03 10:14:40', '2024-04-03 10:14:40'),
(1125, 'english', 'Female', 'Female', '2024-04-03 10:14:40', '2024-04-03 10:14:40'),
(1126, 'english', 'Bio', 'Bio', '2024-04-03 10:14:40', '2024-04-03 10:14:40'),
(1127, 'english', 'Are You Sure Want To Delete', 'Are You Sure Want To Delete', '2024-04-03 10:15:30', '2024-04-03 10:15:30'),
(1128, 'english', 'Follow', 'Follow', '2024-04-03 10:15:44', '2024-04-03 10:15:44'),
(1129, 'english', 'Chats', 'Chats', '2024-04-03 10:17:03', '2024-04-03 10:17:03'),
(1130, 'english', 'Active now', 'Active now', '2024-04-03 10:17:04', '2024-04-03 10:17:04'),
(1131, 'english', 'View Profile', 'View Profile', '2024-04-03 10:17:04', '2024-04-03 10:17:04'),
(1132, 'english', 'Reset', 'Reset', '2024-04-03 10:17:04', '2024-04-03 10:17:04'),
(1133, 'english', 'Stadied at', 'Stadied at', '2024-04-03 10:23:15', '2024-04-03 10:23:15'),
(1134, 'english', 'See More', 'See More', '2024-04-03 10:23:15', '2024-04-03 10:23:15'),
(1135, 'english', 'Add Friend', 'Add Friend', '2024-04-03 10:23:35', '2024-04-03 10:23:35'),
(1136, 'english', 'Unsave', 'Unsave', '2024-04-03 11:03:58', '2024-04-03 11:03:58'),
(1137, 'english', 'Your post has been published', 'Your post has been published', '2024-04-04 06:29:21', '2024-04-04 06:29:21'),
(1138, 'english', 'Create Photo / Video Story', 'Create Photo / Video Story', '2024-04-04 06:46:19', '2024-04-04 06:46:19'),
(1139, 'english', 'Create a Text Story', 'Create a Text Story', '2024-04-04 06:46:19', '2024-04-04 06:46:19'),
(1140, 'english', 'Share to story', 'Share to story', '2024-04-04 06:46:19', '2024-04-04 06:46:19'),
(1141, 'english', 'Discard', 'Discard', '2024-04-04 06:46:19', '2024-04-04 06:46:19'),
(1142, 'english', 'Friend Request Sent Successfully', 'Friend Request Sent Successfully', '2024-04-04 06:57:04', '2024-04-04 06:57:04'),
(1143, 'english', 'Cancle Friend Request', 'Cancle Friend Request', '2024-04-04 06:57:05', '2024-04-04 06:57:05'),
(1144, 'english', 'Friend request', 'Friend request', '2024-04-04 06:57:05', '2024-04-04 06:57:05'),
(1145, 'english', 'Requested', 'Requested', '2024-04-04 06:57:05', '2024-04-04 06:57:05'),
(1146, 'english', 'Accepted', 'Accepted', '2024-04-04 06:57:05', '2024-04-04 06:57:05'),
(1147, 'english', '1', '1', '2024-04-04 06:57:10', '2024-04-04 06:57:10'),
(1148, 'english', 'Notifications', 'Notifications', '2024-04-04 06:57:15', '2024-04-04 06:57:15'),
(1149, 'english', 'sent you Friend Request', 'sent you Friend Request', '2024-04-04 06:57:15', '2024-04-04 06:57:15'),
(1150, 'english', 'Accept', 'Accept', '2024-04-04 06:57:15', '2024-04-04 06:57:15'),
(1151, 'english', 'Decline', 'Decline', '2024-04-04 06:57:15', '2024-04-04 06:57:15'),
(1152, 'english', 'Friend Request Accepted', 'Friend Request Accepted', '2024-04-04 06:57:23', '2024-04-04 06:57:23'),
(1153, 'english', 'feeling', 'feeling', '2024-04-04 07:13:20', '2024-04-04 07:13:20'),
(1154, 'english', 'Post has been added to your timeline', 'Post has been added to your timeline', '2024-04-04 07:22:32', '2024-04-04 07:22:32'),
(1155, 'english', 'is live now', 'is live now', '2024-04-04 07:22:43', '2024-04-04 07:22:43'),
(1156, 'english', 'Join now', 'Join now', '2024-04-04 07:22:43', '2024-04-04 07:22:43'),
(1157, 'english', 'Post Deleted Successfully', 'Post Deleted Successfully', '2024-04-04 07:23:17', '2024-04-04 07:23:17'),
(1158, 'english', 'Send', 'Send', '2024-04-04 07:32:06', '2024-04-04 07:32:06'),
(1159, 'english', 'at', 'at', '2024-04-04 07:37:29', '2024-04-04 07:37:29'),
(1160, 'english', 'We hope you enjoy revisiting and sharing your memories on Sociopro from the most recent moments to those from days gone by.', 'We hope you enjoy revisiting and sharing your memories on Sociopro from the most recent moments to those from days gone by.', '2024-04-04 07:37:33', '2024-04-04 07:37:33'),
(1161, 'english', 'On this day', 'On this day', '2024-04-04 07:37:33', '2024-04-04 07:37:33'),
(1162, 'english', 'You have a memory ____ ____ ago', 'You have a memory ____ ____ ago', '2024-04-04 07:37:33', '2024-04-04 07:37:33'),
(1163, 'english', 'posts', 'posts', '2024-04-04 07:42:07', '2024-04-04 07:42:07'),
(1164, 'english', 'Search Results', 'Search Results', '2024-04-04 07:42:07', '2024-04-04 07:42:07'),
(1165, 'english', 'All', 'All', '2024-04-04 07:42:08', '2024-04-04 07:42:08'),
(1166, 'english', 'Peoples', 'Peoples', '2024-04-04 07:42:08', '2024-04-04 07:42:08'),
(1167, 'english', 'People', 'People', '2024-04-04 07:42:08', '2024-04-04 07:42:08'),
(1168, 'english', 'Friend', 'Friend', '2024-04-04 07:42:23', '2024-04-04 07:42:23'),
(1169, 'english', 'Mutual Friends', 'Mutual Friends', '2024-04-04 07:57:38', '2024-04-04 07:57:38'),
(1170, 'english', 'Add Sponsors Post', 'Add Sponsors Post', '2024-04-04 07:58:34', '2024-04-04 07:58:34'),
(1171, 'english', 'URL', 'URL', '2024-04-04 07:58:34', '2024-04-04 07:58:34'),
(1172, 'english', '(50 Character Show In Front End)', '(50 Character Show In Front End)', '2024-04-04 07:58:34', '2024-04-04 07:58:34'),
(1173, 'english', 'Not yet published', 'Not yet published', '2024-04-04 07:59:55', '2024-04-04 07:59:55'),
(1174, 'english', 'Are You Sure Want To Delete?', 'Are You Sure Want To Delete?', '2024-04-04 07:59:55', '2024-04-04 07:59:55'),
(1175, 'english', 'Edit Sponsor Post', 'Edit Sponsor Post', '2024-04-04 08:02:10', '2024-04-04 08:02:10'),
(1176, 'english', 'Previous Uploaded File', 'Previous Uploaded File', '2024-04-04 08:02:10', '2024-04-04 08:02:10'),
(1177, 'english', 'End date', 'End date', '2024-04-04 08:02:10', '2024-04-04 08:02:10'),
(1178, 'english', 'Reply', 'Reply', '2024-04-04 08:04:52', '2024-04-04 08:04:52'),
(1179, 'english', 'Delete Comment', 'Delete Comment', '2024-04-04 08:04:52', '2024-04-04 08:04:52'),
(1180, 'english', 'Loved', 'Loved', '2024-04-04 08:04:55', '2024-04-04 08:04:55'),
(1181, 'english', 'Posted On My Timeline Successfully', 'Posted On My Timeline Successfully', '2024-04-04 08:05:05', '2024-04-04 08:05:05'),
(1182, 'english', 'shared post', 'shared post', '2024-04-04 08:05:06', '2024-04-04 08:05:06'),
(1183, 'english', 'Upload', 'Upload', '2024-04-04 09:50:59', '2024-04-04 09:50:59'),
(1184, 'english', 'Cover photo updated', 'Cover photo updated', '2024-04-04 09:53:35', '2024-04-04 09:53:35'),
(1185, 'english', 'Unfriend', 'Unfriend', '2024-04-04 09:53:43', '2024-04-04 09:53:43'),
(1186, 'english', 'View Album', 'View Album', '2024-04-04 09:54:25', '2024-04-04 09:54:25'),
(1187, 'english', 'Delete Album', 'Delete Album', '2024-04-04 09:54:25', '2024-04-04 09:54:25'),
(1188, 'english', 'Items', 'Items', '2024-04-04 09:54:25', '2024-04-04 09:54:25'),
(1189, 'english', 'Confirm', 'Confirm', '2024-04-04 10:24:23', '2024-04-04 10:24:23'),
(1190, 'english', 'Find Friend', 'Find Friend', '2024-04-04 10:26:02', '2024-04-04 10:26:02'),
(1191, 'english', 'Find Friends', 'Find Friends', '2024-04-04 10:26:15', '2024-04-04 10:26:15'),
(1192, 'english', 'Friend request deleted', 'Friend request deleted', '2024-04-04 12:12:31', '2024-04-04 12:12:31'),
(1193, 'english', '2', '2', '2024-04-04 12:13:23', '2024-04-04 12:13:23'),
(1194, 'english', 'accepted Your Friend Request', 'accepted Your Friend Request', '2024-04-06 16:23:03', '2024-04-06 16:23:03'),
(1195, 'english', 'Mark As Read', 'Mark As Read', '2024-04-06 16:23:03', '2024-04-06 16:23:03'),
(1196, 'english', 'Accept Friend Request', 'Accept Friend Request', '2024-04-07 05:22:22', '2024-04-07 05:22:22'),
(1197, 'english', 'Removed from friend list', 'Removed from friend list', '2024-04-07 05:30:57', '2024-04-07 05:30:57'),
(1198, 'english', '3', '3', '2024-04-07 05:36:18', '2024-04-07 05:36:18'),
(1199, 'english', '4', '4', '2024-04-07 05:55:09', '2024-04-07 05:55:09'),
(1200, 'english', '5', '5', '2024-04-07 05:59:26', '2024-04-07 05:59:26'),
(1201, 'english', 'Edit user profile', 'Edit user profile', '2024-04-07 06:07:28', '2024-04-07 06:07:28'),
(1202, 'english', 'Verified', 'Verified', '2024-04-07 06:08:30', '2024-04-07 06:08:30'),
(1203, 'english', 'Details info', 'Details info', '2024-04-07 06:08:30', '2024-04-07 06:08:30'),
(1204, 'english', 'Phone Number', 'Phone Number', '2024-04-07 06:08:30', '2024-04-07 06:08:30'),
(1205, 'english', 'Your name', 'Your name', '2024-04-07 06:08:30', '2024-04-07 06:08:30'),
(1206, 'english', 'Profession', 'Profession', '2024-04-07 06:08:30', '2024-04-07 06:08:30'),
(1207, 'english', 'Enter your Profession', 'Enter your Profession', '2024-04-07 06:08:30', '2024-04-07 06:08:30'),
(1208, 'english', 'Birthday', 'Birthday', '2024-04-07 06:08:30', '2024-04-07 06:08:30'),
(1209, 'english', 'Your address', 'Your address', '2024-04-07 06:08:30', '2024-04-07 06:08:30'),
(1210, 'english', 'Save Changes', 'Save Changes', '2024-04-07 06:08:30', '2024-04-07 06:08:30'),
(1211, 'english', 'Select Album', 'Select Album', '2024-04-07 06:13:18', '2024-04-07 06:13:18'),
(1212, 'english', 'Album Images', 'Album Images', '2024-04-07 06:13:18', '2024-04-07 06:13:18'),
(1213, 'english', 'Your images is added to album', 'Your images is added to album', '2024-04-07 06:13:29', '2024-04-07 06:13:29'),
(1214, 'english', 'Album deleted successfully', 'Album deleted successfully', '2024-04-07 07:26:09', '2024-04-07 07:26:09'),
(1215, 'english', 'This content isn\'t available right now', 'This content isn\'t available right now', '2024-04-07 09:39:25', '2024-04-07 09:39:25'),
(1216, 'english', 'When this happens, it\'s usually because the owner only shared it with a small group of people, changed who can see it or it\'s been deleted.', 'When this happens, it\'s usually because the owner only shared it with a small group of people, changed who can see it or it\'s been deleted.', '2024-04-07 09:39:25', '2024-04-07 09:39:25'),
(1217, 'english', 'All Pages', 'All Pages', '2024-04-08 04:35:33', '2024-04-08 04:35:33'),
(1218, 'english', 'Page owner', 'Page owner', '2024-04-08 04:35:33', '2024-04-08 04:35:33'),
(1219, 'english', 'Activity', 'Activity', '2024-04-08 04:40:27', '2024-04-08 04:40:27'),
(1220, 'english', 'Your bio updated', 'Your bio updated', '2024-04-08 04:41:21', '2024-04-08 04:41:21'),
(1221, 'english', 'Earlier', 'Earlier', '2024-04-08 06:02:36', '2024-04-08 06:02:36'),
(1222, 'english', 'No Conversion Start!', 'No Conversion Start!', '2024-04-08 06:06:09', '2024-04-08 06:06:09'),
(1223, 'english', 'FRONTEND BADGE PRICING SETTINGS', 'FRONTEND BADGE PRICING SETTINGS', '2024-04-08 06:28:26', '2024-04-08 06:28:26'),
(1224, 'english', 'Badge Price', 'Badge Price', '2024-04-08 06:28:26', '2024-04-08 06:28:26'),
(1225, 'english', 'Order summary', 'Order summary', '2024-04-08 06:28:39', '2024-04-08 06:28:39'),
(1226, 'english', 'Select payment gateway', 'Select payment gateway', '2024-04-08 06:28:39', '2024-04-08 06:28:39'),
(1227, 'english', 'Item List', 'Item List', '2024-04-08 06:28:39', '2024-04-08 06:28:39'),
(1228, 'english', 'Grand Total', 'Grand Total', '2024-04-08 06:28:39', '2024-04-08 06:28:39'),
(1229, 'english', 'Payment successfully done!', 'Payment successfully done!', '2024-04-08 06:29:07', '2024-04-08 06:29:07'),
(1230, 'english', 'Already purchased', 'Already purchased', '2024-04-08 06:29:07', '2024-04-08 06:29:07'),
(1231, 'english', 'Badge Purchased History', 'Badge Purchased History', '2024-04-08 06:29:07', '2024-04-08 06:29:07'),
(1232, 'english', 'ID', 'ID', '2024-04-08 06:29:07', '2024-04-08 06:29:07'),
(1233, 'english', 'LIVE', 'LIVE', '2024-04-08 11:33:02', '2024-04-08 11:33:02'),
(1234, 'english', 'Watch now', 'Watch now', '2024-04-08 11:33:02', '2024-04-08 11:33:02'),
(1235, 'english', 'Liked', 'Liked', '2024-04-08 12:00:34', '2024-04-08 12:00:34'),
(1236, 'english', 'Likedss', 'Likedss', '2024-04-08 12:22:07', '2024-04-08 12:22:07'),
(1237, 'english', 'Haha', 'Haha', '2024-04-08 12:27:43', '2024-04-08 12:27:43'),
(1238, 'english', 'Angry', 'Angry', '2024-04-08 12:34:06', '2024-04-08 12:34:06'),
(1239, 'english', 'Deleted successfully', 'Deleted successfully', '2024-04-09 06:43:06', '2024-04-09 06:43:06'),
(1240, 'english', 'All Page Categories', 'All Page Categories', '2024-04-09 06:50:04', '2024-04-09 06:50:04'),
(1241, 'english', 'Page Created Successfully', 'Page Created Successfully', '2024-04-09 06:53:36', '2024-04-09 06:53:36'),
(1242, 'english', 'People like this', 'People like this', '2024-04-09 06:53:37', '2024-04-09 06:53:37'),
(1243, 'english', 'Edit Page', 'Edit Page', '2024-04-09 06:53:37', '2024-04-09 06:53:37'),
(1244, 'english', '____ likes', '____ likes', '2024-04-09 06:53:37', '2024-04-09 06:53:37'),
(1245, 'english', 'Previous Profile Photo', 'Previous Profile Photo', '2024-04-09 06:58:18', '2024-04-09 06:58:18'),
(1246, 'english', 'Page Updated Successfully', 'Page Updated Successfully', '2024-04-09 06:58:25', '2024-04-09 06:58:25'),
(1247, 'english', 'like this', 'like this', '2024-04-09 06:59:04', '2024-04-09 06:59:04'),
(1248, 'english', 'Update Page Info', 'Update Page Info', '2024-04-09 06:59:04', '2024-04-09 06:59:04'),
(1249, 'english', 'Page you may like', 'Page you may like', '2024-04-09 06:59:04', '2024-04-09 06:59:04'),
(1250, 'english', 'Photo/Video', 'Photo/Video', '2024-04-09 06:59:04', '2024-04-09 06:59:04'),
(1251, 'english', 'Photos', 'Photos', '2024-04-09 07:11:00', '2024-04-09 07:11:00'),
(1252, 'english', 'Group Created Successfully', 'Group Created Successfully', '2024-04-09 07:39:09', '2024-04-09 07:39:09'),
(1253, 'english', 'Member', 'Member', '2024-04-09 07:39:10', '2024-04-09 07:39:10'),
(1254, 'english', 'Admin', 'Admin', '2024-04-09 07:39:10', '2024-04-09 07:39:10'),
(1255, 'english', 'Edit Group', 'Edit Group', '2024-04-09 07:39:13', '2024-04-09 07:39:13'),
(1256, 'english', 'Invite', 'Invite', '2024-04-09 07:39:13', '2024-04-09 07:39:13'),
(1257, 'english', 'Discussion', 'Discussion', '2024-04-09 07:39:13', '2024-04-09 07:39:13'),
(1258, 'english', 'Media', 'Media', '2024-04-09 07:39:13', '2024-04-09 07:39:13'),
(1259, 'english', 'Invite Group', 'Invite Group', '2024-04-09 07:39:13', '2024-04-09 07:39:13'),
(1260, 'english', 'Invite Friends', 'Invite Friends', '2024-04-09 07:39:13', '2024-04-09 07:39:13'),
(1261, 'english', 'Optional', 'Optional', '2024-04-09 07:39:13', '2024-04-09 07:39:13'),
(1262, 'english', 'Suggestion', 'Suggestion', '2024-04-09 07:39:13', '2024-04-09 07:39:13'),
(1263, 'english', 'Recent Media', 'Recent Media', '2024-04-09 07:39:13', '2024-04-09 07:39:13'),
(1264, 'english', 'Recent Members', 'Recent Members', '2024-04-09 07:39:13', '2024-04-09 07:39:13'),
(1265, 'english', 'Members', 'Members', '2024-04-09 07:39:22', '2024-04-09 07:39:22'),
(1266, 'english', 'New people and Pages who join this group will appear here', 'New people and Pages who join this group will appear here', '2024-04-09 07:39:22', '2024-04-09 07:39:22'),
(1267, 'english', 'Leave Group', 'Leave Group', '2024-04-09 07:39:22', '2024-04-09 07:39:22'),
(1268, 'english', 'Members With Things in Common', 'Members With Things in Common', '2024-04-09 07:39:22', '2024-04-09 07:39:22'),
(1269, 'english', 'Photos of you', 'Photos of you', '2024-04-09 07:39:26', '2024-04-09 07:39:26'),
(1270, 'english', 'Albums', 'Albums', '2024-04-09 07:39:26', '2024-04-09 07:39:26'),
(1271, 'english', 'Upcoming Events', 'Upcoming Events', '2024-04-09 07:54:48', '2024-04-09 07:54:48'),
(1272, 'english', 'No upcoming events', 'No upcoming events', '2024-04-09 07:54:48', '2024-04-09 07:54:48'),
(1273, 'english', 'Post Events', 'Post Events', '2024-04-09 07:54:48', '2024-04-09 07:54:48'),
(1274, 'english', 'All  people and  who join this group will appear here', 'All  people and  who join this group will appear here', '2024-04-09 07:55:57', '2024-04-09 07:55:57'),
(1275, 'english', 'Event Created Successfully', 'Event Created Successfully', '2024-04-09 07:56:46', '2024-04-09 07:56:46'),
(1276, 'english', 'Nearest event', 'Nearest event', '2024-04-09 07:56:46', '2024-04-09 07:56:46'),
(1277, 'english', 'Total ____ Upcoming events', 'Total ____ Upcoming events', '2024-04-09 07:56:46', '2024-04-09 07:56:46'),
(1278, 'english', 'Edit Event', 'Edit Event', '2024-04-09 07:56:46', '2024-04-09 07:56:46'),
(1279, 'english', 'Delete Event', 'Delete Event', '2024-04-09 07:56:46', '2024-04-09 07:56:46'),
(1280, 'english', 'Created by', 'Created by', '2024-04-09 07:56:46', '2024-04-09 07:56:46'),
(1281, 'english', 'Share Event', 'Share Event', '2024-04-09 07:56:55', '2024-04-09 07:56:55'),
(1282, 'english', 'Recent Activity', 'Recent Activity', '2024-04-09 07:56:55', '2024-04-09 07:56:55'),
(1283, 'english', 'Selected users', 'Selected users', '2024-04-09 07:56:55', '2024-04-09 07:56:55'),
(1284, 'english', 'Invite Event', 'Invite Event', '2024-04-09 07:56:55', '2024-04-09 07:56:55'),
(1285, 'english', 'Guests', 'Guests', '2024-04-09 07:56:55', '2024-04-09 07:56:55'),
(1286, 'english', 'All Going And Interested User', 'All Going And Interested User', '2024-04-09 07:56:55', '2024-04-09 07:56:55'),
(1287, 'english', 'View All', 'View All', '2024-04-09 07:56:55', '2024-04-09 07:56:55'),
(1288, 'english', 'Go With Friends', 'Go With Friends', '2024-04-09 07:56:55', '2024-04-09 07:56:55');
INSERT INTO `languages` (`id`, `name`, `phrase`, `translated`, `created_at`, `updated_at`) VALUES
(1289, 'english', 'Send invitations', 'Send invitations', '2024-04-09 07:56:55', '2024-04-09 07:56:55'),
(1290, 'english', 'Popular Events', 'Popular Events', '2024-04-09 07:56:55', '2024-04-09 07:56:55'),
(1291, 'english', 'Group Invited Done Successfully', 'Group Invited Done Successfully', '2024-04-09 08:07:54', '2024-04-09 08:07:54'),
(1292, 'english', 'invited you to like', 'invited you to like', '2024-04-09 08:07:59', '2024-04-09 08:07:59'),
(1293, 'english', 'Group Invitation Canceled', 'Group Invitation Canceled', '2024-04-09 08:09:29', '2024-04-09 08:09:29'),
(1294, 'english', 'Update Event', 'Update Event', '2024-04-09 09:29:23', '2024-04-09 09:29:23'),
(1295, 'english', 'Enter your group title', 'Enter your group title', '2024-04-09 09:34:23', '2024-04-09 09:34:23'),
(1296, 'english', 'Enter your group sub title', 'Enter your group sub title', '2024-04-09 09:34:23', '2024-04-09 09:34:23'),
(1297, 'english', 'Group Location', 'Group Location', '2024-04-09 09:34:23', '2024-04-09 09:34:23'),
(1298, 'english', 'Enter your group location', 'Enter your group location', '2024-04-09 09:34:23', '2024-04-09 09:34:23'),
(1299, 'english', 'Group Type', 'Group Type', '2024-04-09 09:34:23', '2024-04-09 09:34:23'),
(1300, 'english', 'Enter your group type', 'Enter your group type', '2024-04-09 09:34:23', '2024-04-09 09:34:23'),
(1301, 'english', 'Privacy', 'Privacy', '2024-04-09 09:34:23', '2024-04-09 09:34:23'),
(1302, 'english', 'Cover Photo Updated Successfully', 'Cover Photo Updated Successfully', '2024-04-09 09:34:34', '2024-04-09 09:34:34'),
(1303, 'english', 'Update Custom Pages Information', 'Update Custom Pages Information', '2024-04-09 09:57:38', '2024-04-09 09:57:38'),
(1304, 'english', 'About Page Description', 'About Page Description', '2024-04-09 09:57:38', '2024-04-09 09:57:38'),
(1305, 'english', 'Privacy Page Description', 'Privacy Page Description', '2024-04-09 09:57:38', '2024-04-09 09:57:38'),
(1306, 'english', 'Term and Condition Page Description', 'Term and Condition Page Description', '2024-04-09 09:57:38', '2024-04-09 09:57:38'),
(1307, 'english', 'Create New Blog', 'Create New Blog', '2024-04-09 10:16:58', '2024-04-09 10:16:58'),
(1308, 'english', 'Create post to share', 'Create post to share', '2024-04-20 09:21:24', '2024-04-20 09:21:24'),
(1309, 'english', 'See Moress', 'See Moress', '2024-04-20 12:44:30', '2024-04-20 12:44:30'),
(1310, 'english', 'You are now following', 'You are now following', '2024-04-20 12:48:46', '2024-04-20 12:48:46'),
(1311, 'english', 'Unfollow', 'Unfollow', '2024-04-20 12:48:47', '2024-04-20 12:48:47'),
(1312, 'english', 'likes', 'likes', '2024-04-21 04:44:02', '2024-04-21 04:44:02'),
(1313, 'english', 'Interested', 'Interested', '2024-04-21 04:44:02', '2024-04-21 04:44:02'),
(1314, 'english', 'Going', 'Going', '2024-04-21 04:44:02', '2024-04-21 04:44:02'),
(1315, 'english', 'Interest', 'Interest', '2024-04-21 04:44:02', '2024-04-21 04:44:02'),
(1316, 'english', 'Event has been Canceled', 'Event has been Canceled', '2024-04-21 05:33:23', '2024-04-21 05:33:23'),
(1317, 'english', 'Interested to Event', 'Interested to Event', '2024-04-21 05:35:07', '2024-04-21 05:35:07'),
(1318, 'english', 'Going to Event', 'Going to Event', '2024-04-21 05:38:09', '2024-04-21 05:38:09'),
(1319, 'english', 'Share On Group', 'Share On Group', '2024-04-21 05:47:31', '2024-04-21 05:47:31'),
(1320, 'english', 'Payment gateways', 'Payment gateways', '2024-04-21 06:54:20', '2024-04-21 06:54:20'),
(1321, 'english', 'Payment Gateway', 'Payment Gateway', '2024-04-21 06:54:20', '2024-04-21 06:54:20'),
(1322, 'english', 'Environment', 'Environment', '2024-04-21 06:54:20', '2024-04-21 06:54:20'),
(1323, 'english', 'Test Mode', 'Test Mode', '2024-04-21 06:54:20', '2024-04-21 06:54:20'),
(1324, 'english', 'Are you sure want to change status?', 'Are you sure want to change status?', '2024-04-21 06:54:20', '2024-04-21 06:54:20'),
(1325, 'english', 'Are you sure want to change environment?', 'Are you sure want to change environment?', '2024-04-21 06:54:20', '2024-04-21 06:54:20'),
(1326, 'english', 'Activate live mode', 'Activate live mode', '2024-04-21 06:54:20', '2024-04-21 06:54:20'),
(1327, 'english', 'Live video', 'Live video', '2024-04-21 06:55:05', '2024-04-21 06:55:05'),
(1328, 'english', 'Update zoom api keys', 'Update zoom api keys', '2024-04-21 06:55:09', '2024-04-21 06:55:09'),
(1329, 'english', 'API key', 'API key', '2024-04-21 06:55:09', '2024-04-21 06:55:09'),
(1330, 'english', 'API secret', 'API secret', '2024-04-21 06:55:09', '2024-04-21 06:55:09'),
(1331, 'english', 'Save', 'Save', '2024-04-21 06:55:09', '2024-04-21 06:55:09'),
(1332, 'english', 'Update Zitsi api keys', 'Update Zitsi api keys', '2024-04-21 06:58:51', '2024-04-21 06:58:51'),
(1333, 'english', 'Zitsi Live Settings', 'Zitsi Live Settings', '2024-04-21 08:04:35', '2024-04-21 08:04:35'),
(1334, 'english', 'Jitsi live class settings', 'Jitsi live class settings', '2024-04-21 08:13:58', '2024-04-21 08:13:58'),
(1335, 'english', 'Jitsi API Configuration', 'Jitsi API Configuration', '2024-04-21 08:13:58', '2024-04-21 08:13:58'),
(1336, 'english', 'How to configure Jitsi API?', 'How to configure Jitsi API?', '2024-04-21 08:13:58', '2024-04-21 08:13:58'),
(1337, 'english', 'Account email*', 'Account email*', '2024-04-21 09:25:30', '2024-04-21 09:25:30'),
(1338, 'english', 'Jitsi app id*', 'Jitsi app id*', '2024-04-21 09:25:30', '2024-04-21 09:25:30'),
(1339, 'english', 'Jwt token*', 'Jwt token*', '2024-04-21 09:25:30', '2024-04-21 09:25:30'),
(1340, 'english', 'sandbox client id', 'sandbox client id', '2024-04-21 11:08:09', '2024-04-21 11:08:09'),
(1341, 'english', 'production secret key', 'production secret key', '2024-04-21 11:08:09', '2024-04-21 11:08:09'),
(1342, 'english', 'Your post has been updated', 'Your post has been updated', '2024-04-22 05:43:16', '2024-04-22 05:43:16'),
(1343, 'english', 'Video/Shorts Created Successfully', 'Video/Shorts Created Successfully', '2024-04-22 09:46:25', '2024-04-22 09:46:25'),
(1344, 'english', 'Save Video', 'Save Video', '2024-04-22 09:46:26', '2024-04-22 09:46:26'),
(1345, 'english', 'Delete Video', 'Delete Video', '2024-04-22 09:46:26', '2024-04-22 09:46:26'),
(1346, 'english', 'Latest Short', 'Latest Short', '2024-04-22 09:46:48', '2024-04-22 09:46:48'),
(1347, 'english', 'Removed from followed list', 'Removed from followed list', '2024-04-22 10:02:41', '2024-04-22 10:02:41'),
(1348, 'english', 'View more', 'View more', '2024-04-22 10:17:36', '2024-04-22 10:17:36'),
(1349, 'english', 'Likeds', 'Likeds', '2024-04-22 10:20:54', '2024-04-22 10:20:54'),
(1350, 'english', 'Unsave Video', 'Unsave Video', '2024-04-22 10:25:49', '2024-04-22 10:25:49'),
(1351, 'english', 'Views', 'Views', '2024-04-22 10:25:52', '2024-04-22 10:25:52'),
(1352, 'english', 'All Blog Categories', 'All Blog Categories', '2024-04-22 10:36:19', '2024-04-22 10:36:19'),
(1353, 'english', 'Blog Category', 'Blog Category', '2024-04-22 10:36:25', '2024-04-22 10:36:25'),
(1354, 'english', 'Blog Created Successfully', 'Blog Created Successfully', '2024-04-22 10:37:46', '2024-04-22 10:37:46'),
(1355, 'english', 'Edit Article', 'Edit Article', '2024-04-22 10:40:09', '2024-04-22 10:40:09'),
(1356, 'english', 'Delete Article', 'Delete Article', '2024-04-22 10:40:09', '2024-04-22 10:40:09'),
(1357, 'english', 'Search Resultsbgf', 'Search Resultsbgf', '2024-04-22 10:48:55', '2024-04-22 10:48:55'),
(1358, 'english', 'Remove', 'Remove', '2024-04-22 11:20:26', '2024-04-22 11:20:26'),
(1359, 'english', 'Reset password', 'Reset password', '2024-04-22 12:32:03', '2024-04-22 12:32:03'),
(1360, 'english', 'Current Password', 'Current Password', '2024-04-22 12:32:03', '2024-04-22 12:32:03'),
(1361, 'english', '8 Symbols at least', '8 Symbols at least', '2024-04-22 12:32:03', '2024-04-22 12:32:03'),
(1362, 'english', 'New Password', 'New Password', '2024-04-22 12:32:03', '2024-04-22 12:32:03'),
(1363, 'english', 'Confirm Password', 'Confirm Password', '2024-04-22 12:32:03', '2024-04-22 12:32:03'),
(1364, 'english', 'Update Password', 'Update Password', '2024-04-22 12:32:03', '2024-04-22 12:32:03'),
(1365, 'english', 'Install', 'Install', '2024-04-23 05:58:25', '2024-04-23 05:58:25'),
(1366, 'english', 'Addon updated successfully', 'Addon updated successfully', '2024-04-23 05:58:53', '2024-04-23 05:58:53'),
(1367, 'english', 'Job', 'Job', '2024-04-23 05:58:53', '2024-04-23 05:58:53'),
(1368, 'english', 'Job List', 'Job List', '2024-04-23 05:58:53', '2024-04-23 05:58:53'),
(1369, 'english', 'Create Job', 'Create Job', '2024-04-23 05:58:53', '2024-04-23 05:58:53'),
(1370, 'english', 'Pending Job', 'Pending Job', '2024-04-23 05:58:53', '2024-04-23 05:58:53'),
(1371, 'english', 'All Apply List', 'All Apply List', '2024-04-23 05:58:53', '2024-04-23 05:58:53'),
(1372, 'english', 'Job Price', 'Job Price', '2024-04-23 05:58:53', '2024-04-23 05:58:53'),
(1373, 'english', 'Deactivate', 'Deactivate', '2024-04-23 05:58:53', '2024-04-23 05:58:53'),
(1374, 'english', 'Jobs', 'Jobs', '2024-04-23 05:58:57', '2024-04-23 05:58:57'),
(1375, 'english', 'Explore Jobs', 'Explore Jobs', '2024-04-23 05:59:00', '2024-04-23 05:59:00'),
(1376, 'english', 'My Job', 'My Job', '2024-04-23 05:59:00', '2024-04-23 05:59:00'),
(1377, 'english', 'Saved Job', 'Saved Job', '2024-04-23 05:59:00', '2024-04-23 05:59:00'),
(1378, 'english', 'My Application', 'My Application', '2024-04-23 05:59:00', '2024-04-23 05:59:00'),
(1379, 'english', 'History', 'History', '2024-04-23 06:03:40', '2024-04-23 06:03:40'),
(1380, 'english', 'My Jobs', 'My Jobs', '2024-04-23 06:03:51', '2024-04-23 06:03:51'),
(1381, 'english', 'Saved Jobs', 'Saved Jobs', '2024-04-23 06:03:51', '2024-04-23 06:03:51'),
(1382, 'english', 'Company Name', 'Company Name', '2024-04-23 06:06:01', '2024-04-23 06:06:01'),
(1383, 'english', 'Starting Salary Range', 'Starting Salary Range', '2024-04-23 06:06:01', '2024-04-23 06:06:01'),
(1384, 'english', 'Ending Salary Range', 'Ending Salary Range', '2024-04-23 06:06:01', '2024-04-23 06:06:01'),
(1385, 'english', 'Type', 'Type', '2024-04-23 06:06:01', '2024-04-23 06:06:01'),
(1386, 'english', 'Full Time', 'Full Time', '2024-04-23 06:06:01', '2024-04-23 06:06:01'),
(1387, 'english', 'Part Time', 'Part Time', '2024-04-23 06:06:01', '2024-04-23 06:06:01'),
(1388, 'english', 'Create Job Post', 'Create Job Post', '2024-04-23 06:06:01', '2024-04-23 06:06:01'),
(1389, 'english', 'All Job Categories', 'All Job Categories', '2024-04-23 06:18:14', '2024-04-23 06:18:14'),
(1390, 'english', 'All Jobs Categories', 'All Jobs Categories', '2024-04-23 06:18:22', '2024-04-23 06:18:22'),
(1391, 'english', 'Jobs Category', 'Jobs Category', '2024-04-23 06:18:22', '2024-04-23 06:18:22'),
(1392, 'english', 'Job Created Successfully', 'Job Created Successfully', '2024-04-23 06:22:11', '2024-04-23 06:22:11'),
(1393, 'english', 'Please pay for your created job post.', 'Please pay for your created job post.', '2024-04-23 06:22:12', '2024-04-23 06:22:12'),
(1394, 'english', 'Job Start Date', 'Job Start Date', '2024-04-23 06:22:12', '2024-04-23 06:22:12'),
(1395, 'english', 'Your job on our website expires on date', 'Your job on our website expires on date', '2024-04-23 06:22:12', '2024-04-23 06:22:12'),
(1396, 'english', 'Cancle', 'Cancle', '2024-04-23 06:22:12', '2024-04-23 06:22:12'),
(1397, 'english', 'Add Days', 'Add Days', '2024-04-23 06:22:21', '2024-04-23 06:22:21'),
(1398, 'english', 'After the Administrator sets this day. The job will be visible as of this day.', 'After the Administrator sets this day. The job will be visible as of this day.', '2024-04-23 06:22:21', '2024-04-23 06:22:21'),
(1399, 'english', 'Pay', 'Pay', '2024-04-23 06:26:26', '2024-04-23 06:26:26'),
(1400, 'english', 'Pending', 'Pending', '2024-04-23 06:26:26', '2024-04-23 06:26:26'),
(1401, 'english', 'Job title', 'Job title', '2024-04-23 06:43:52', '2024-04-23 06:43:52'),
(1402, 'english', 'Select a category', 'Select a category', '2024-04-23 06:43:52', '2024-04-23 06:43:52'),
(1403, 'english', 'Salary', 'Salary', '2024-04-23 06:43:52', '2024-04-23 06:43:52'),
(1404, 'english', 'Job Description', 'Job Description', '2024-04-23 06:43:52', '2024-04-23 06:43:52'),
(1405, 'english', 'View Details', 'View Details', '2024-04-23 06:51:03', '2024-04-23 06:51:03'),
(1406, 'english', 'added a job', 'added a job', '2024-04-23 06:51:06', '2024-04-23 06:51:06'),
(1407, 'english', '/ Monthly', '/ Monthly', '2024-04-23 06:51:06', '2024-04-23 06:51:06'),
(1408, 'english', 'See Applicant', 'See Applicant', '2024-04-23 06:51:06', '2024-04-23 06:51:06'),
(1409, 'english', 'Print', 'Print', '2024-04-23 07:10:22', '2024-04-23 07:10:22'),
(1410, 'english', 'Job Name', 'Job Name', '2024-04-23 07:10:22', '2024-04-23 07:10:22'),
(1411, 'english', 'Reference', 'Reference', '2024-04-23 07:10:22', '2024-04-23 07:10:22'),
(1412, 'english', 'Amount', 'Amount', '2024-04-23 07:10:22', '2024-04-23 07:10:22'),
(1413, 'english', 'Paid', 'Paid', '2024-04-23 07:18:39', '2024-04-23 07:18:39'),
(1414, 'english', 'All Pending Jobs', 'All Pending Jobs', '2024-04-23 07:18:46', '2024-04-23 07:18:46'),
(1415, 'english', 'Company', 'Company', '2024-04-23 07:18:46', '2024-04-23 07:18:46'),
(1416, 'english', 'Payment', 'Payment', '2024-04-23 07:18:46', '2024-04-23 07:18:46'),
(1417, 'english', 'Update Job', 'Update Job', '2024-04-23 07:18:51', '2024-04-23 07:18:51'),
(1418, 'english', 'Expires date', 'Expires date', '2024-04-23 07:18:51', '2024-04-23 07:18:51'),
(1419, 'english', 'Are you sure you want to publish this job!', 'Are you sure you want to publish this job!', '2024-04-23 07:18:51', '2024-04-23 07:18:51'),
(1420, 'english', 'All Jobs', 'All Jobs', '2024-04-23 07:19:11', '2024-04-23 07:19:11'),
(1421, 'english', 'Expire Date', 'Expire Date', '2024-04-23 07:19:11', '2024-04-23 07:19:11'),
(1422, 'english', 'Published', 'Published', '2024-04-23 07:19:11', '2024-04-23 07:19:11'),
(1423, 'english', 'Hold', 'Hold', '2024-04-23 07:19:11', '2024-04-23 07:19:11'),
(1424, 'english', 'Holding', 'Holding', '2024-04-23 07:19:19', '2024-04-23 07:19:19'),
(1425, 'english', 'Job Details', 'Job Details', '2024-04-23 07:20:50', '2024-04-23 07:20:50'),
(1426, 'english', 'Open', 'Open', '2024-04-23 07:21:59', '2024-04-23 07:21:59'),
(1427, 'english', 'You Can\"t Apply Your Own Job!', 'You Can\"t Apply Your Own Job!', '2024-04-23 07:22:00', '2024-04-23 07:22:00'),
(1428, 'english', 'Apply', 'Apply', '2024-04-23 07:42:11', '2024-04-23 07:42:11'),
(1429, 'english', 'Apply Now', 'Apply Now', '2024-04-23 07:42:17', '2024-04-23 07:42:17'),
(1430, 'english', 'Email*', 'Email*', '2024-04-23 07:42:18', '2024-04-23 07:42:18'),
(1431, 'english', 'Phone*', 'Phone*', '2024-04-23 07:42:18', '2024-04-23 07:42:18'),
(1432, 'english', 'CV*', 'CV*', '2024-04-23 07:42:18', '2024-04-23 07:42:18'),
(1433, 'english', 'Please upload your CV!', 'Please upload your CV!', '2024-04-23 07:42:18', '2024-04-23 07:42:18'),
(1434, 'english', 'Job Apply Successfully', 'Job Apply Successfully', '2024-04-23 07:44:21', '2024-04-23 07:44:21'),
(1435, 'english', 'Applied', 'Applied', '2024-04-23 07:44:21', '2024-04-23 07:44:21'),
(1436, 'english', 'Applicant', 'Applicant', '2024-04-23 07:44:29', '2024-04-23 07:44:29'),
(1437, 'english', 'Download', 'Download', '2024-04-23 07:44:29', '2024-04-23 07:44:29'),
(1438, 'english', 'Job Owner', 'Job Owner', '2024-04-23 07:50:42', '2024-04-23 07:50:42'),
(1439, 'english', 'Related Jobs', 'Related Jobs', '2024-04-23 09:11:31', '2024-04-23 09:11:31'),
(1440, 'english', 'Upload a preview(for mobile application )', 'Upload a preview(for mobile application )', '2024-05-31 17:52:46', '2024-05-31 17:52:46'),
(1441, 'english', 'All Group', 'All Group', '2024-06-25 09:54:02', '2024-06-25 09:54:02'),
(1442, 'english', 'Group owner', 'Group owner', '2024-06-25 09:54:02', '2024-06-25 09:54:02'),
(1443, 'english', 'Add a new Group', 'Add a new Group', '2024-06-25 09:54:04', '2024-06-25 09:54:04'),
(1444, 'english', 'Group details', 'Group details', '2024-06-25 09:54:04', '2024-06-25 09:54:04'),
(1445, 'english', 'Group Logo', 'Group Logo', '2024-06-25 09:54:04', '2024-06-25 09:54:04');

-- --------------------------------------------------------

--
-- Table structure for table `live_streamings`
--

CREATE TABLE `live_streamings` (
  `streaming_id` int(11) NOT NULL,
  `publisher` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `publisher_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `details` longtext COLLATE utf8_unicode_ci,
  `created_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketplaces`
--

CREATE TABLE `marketplaces` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `currency_id` int(11) DEFAULT NULL,
  `price` varchar(15) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `category` text,
  `status` varchar(250) DEFAULT NULL,
  `condition` text,
  `brand` varchar(250) DEFAULT NULL,
  `buy_link` varchar(300) DEFAULT NULL,
  `description` text,
  `image` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `media_files`
--

CREATE TABLE `media_files` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `post_id` int(11) DEFAULT NULL,
  `story_id` int(11) DEFAULT NULL,
  `album_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `page_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `chat_id` int(11) DEFAULT NULL,
  `album_image_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `file_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `privacy` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_thrades`
--

CREATE TABLE `message_thrades` (
  `id` int(11) NOT NULL,
  `reciver_id` int(11) DEFAULT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `chatcenter` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `sender_user_id` int(11) DEFAULT NULL,
  `reciver_user_id` int(11) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `page_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT '0',
  `view` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pagecategories`
--

CREATE TABLE `pagecategories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subtitle` varchar(300) COLLATE utf8_unicode_ci DEFAULT NULL,
  `page_type` varchar(300) COLLATE utf8_unicode_ci DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `logo` text COLLATE utf8_unicode_ci,
  `coverphoto` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `job` text COLLATE utf8_unicode_ci,
  `lifestyle` text COLLATE utf8_unicode_ci,
  `location` text COLLATE utf8_unicode_ci,
  `status` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `page_likes`
--

CREATE TABLE `page_likes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `page_id` int(11) DEFAULT NULL,
  `role` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_gateways`
--

CREATE TABLE `payment_gateways` (
  `id` int(11) NOT NULL,
  `identifier` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `currency` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `keys` text COLLATE utf8_unicode_ci,
  `model_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `test_mode` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `is_addon` int(11) DEFAULT NULL,
  `created_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `payment_gateways`
--

INSERT INTO `payment_gateways` (`id`, `identifier`, `currency`, `title`, `description`, `keys`, `model_name`, `test_mode`, `status`, `is_addon`, `created_at`, `updated_at`) VALUES
(1, 'paypal', 'USD', 'Paypal', '', '{\"sandbox_client_id\":\"\",\"sandbox_secret_key\":\"\",\"production_client_id\":\"\",\"production_secret_key\":\"\"}', 'Paypal', 1, 1, 0, '', '2023-03-15 06:55:21'),
(2, 'stripe', 'USD', 'Stripe', '', '{\"public_key\":\"\",\"secret_key\":\"\",\"public_live_key\":\"\",\"secret_live_key\":\"\"}', 'StripePay', 1, 1, 0, '', '2023-03-15 06:54:23'),
(3, 'razorpay', 'USD', 'Razorpay', '', '{\"public_key\":\"rzp_test_J60bqBOi1z1aF5\",\"secret_key\":\"uk935K7p4j96UCJgHK8kAU4q\"}', 'Razorpay', 1, 1, 0, NULL, NULL),
(4, 'flutterwave', 'USD', 'Flutterwave', '', '{\"public_key\":\"FLWPUBK_TEST-48dfbeb50344ecd8bc075b4ffe9ba266-X\",\"secret_key\":\"FLWSECK_TEST-1691582e23bd6ee4fb04213ec0b862dd-X\"}', 'Flutterwave', 1, 1, 0, NULL, NULL),
(5, 'paytm', 'USD', 'Paytm', '', '{\"public_key\":\"ApLWOX88722132489587\",\"secret_key\":\"#iFa7&W_C50VL@aT\"}', 'Paytm', 1, 1, 0, NULL, NULL),
(6, 'paystack', 'NGN', 'Paystack', '', '{\"secret_test_key\":\"sk_test_c746060e693dd50c6f397dffc6c3b2f655217c94\",\"public_test_key\":\"pk_test_0816abbed3c339b8473ff22f970c7da1c78cbe1b\",\"secret_live_key\":\"sk_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx\",\"public_live_key\":\"pk_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx\"}', 'Paystack', 1, 1, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment_histories`
--

CREATE TABLE `payment_histories` (
  `id` bigint(20) NOT NULL,
  `item_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `item_id` bigint(20) DEFAULT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `currency` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `identifier` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `transaction_keys` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `publisher` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `publisher_id` int(11) DEFAULT NULL,
  `post_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `privacy` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tagged_user_ids` longtext COLLATE utf8_unicode_ci,
  `activity_id` int(11) DEFAULT NULL,
  `location` varchar(300) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `status` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `report_status` tinyint(1) NOT NULL DEFAULT '0',
  `user_reacts` longtext COLLATE utf8_unicode_ci,
  `shared_user` text COLLATE utf8_unicode_ci,
  `created_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `posted_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hashtag` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `album_image_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mobile_app_image` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_shares`
--

CREATE TABLE `post_shares` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `post_id` int(11) DEFAULT NULL,
  `shared_on` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `post_id` int(11) DEFAULT NULL,
  `report` text,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `saved_products`
--

CREATE TABLE `saved_products` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `saveforlaters`
--

CREATE TABLE `saveforlaters` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `video_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `post_id` int(11) DEFAULT NULL,
  `marketplace_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `blog_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_id` int(11) NOT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_id`, `type`, `description`, `created_at`, `updated_at`) VALUES
(1, 'zoom_configuration', '{\"api_key\":null,\"api_secret\":null}', '2022-09-07 06:07:09', '2024-04-21 09:32:14'),
(2, 'about', '<h2 style=\"font-style:italic;\">What is Lorem Ipsum?</h2>\r\n\r\n<p><strong>Lorem Ipsum</strong>&nbsp;is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry&#39;s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p>\r\n\r\n<h2>Why do we use it?</h2>\r\n\r\n<p>It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using &#39;Content here, content here&#39;, making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for &#39;lorem ipsum&#39; will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose (injected humour and the like).</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<h2>Where does it come from?</h2>\r\n\r\n<p>Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source. Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of &quot;de Finibus Bonorum et Malorum&quot; (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance. The first line of Lorem Ipsum, &quot;Lorem ipsum dolor sit amet..&quot;, comes from a line in section 1.10.32.</p>\r\n\r\n<p>The standard chunk of Lorem Ipsum used since the 1500s is reproduced below for those interested. Sections 1.10.32 and 1.10.33 from &quot;de Finibus Bonorum et Malorum&quot; by Cicero are also reproduced in their exact original form, accompanied by English versions from the 1914 translation by H. Rackham.</p>', '2022-09-07 06:07:09', '2022-09-10 23:07:33'),
(3, 'policy', '<h2>What is Lorem Ipsum?</h2>\r\n\r\n<p><strong>Lorem Ipsum</strong>&nbsp;is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry&#39;s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p>\r\n\r\n<h2>Why do we use it?</h2>\r\n\r\n<p>It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using &#39;Content here, content here&#39;, making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for &#39;lorem ipsum&#39; will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose (injected humour and the like).</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<h2>Where does it come from?</h2>\r\n\r\n<p>Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source. Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of &quot;de Finibus Bonorum et Malorum&quot; (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance. The first line of Lorem Ipsum, &quot;Lorem ipsum dolor sit amet..&quot;, comes from a line in section 1.10.32.</p>\r\n\r\n<p>The standard chunk of Lorem Ipsum used since the 1500s is reproduced below for those interested. Sections 1.10.32 and 1.10.33 from &quot;de Finibus Bonorum et Malorum&quot; by Cicero are also reproduced in their exact original form, accompanied by English versions from the 1914 translation by H. Rackham.</p>', '2022-09-07 06:07:09', '2022-09-07 00:12:27'),
(4, 'term', '<p>Welcome to the University of Dhaka&rsquo;s website, featuring the oldest, largest and the premier multidisciplinary university of Bangladesh!&nbsp;</p>\r\n\r\n<p>Founded in 1921, The University of Dhaka has always had the mission of uplifting the educational standards of the people of the region. It was initially meant to provide tertiary education to people who didn&rsquo;t have access to higher studies till then. Subsequently, it has contributed significantly to the socio-cultural and political development of what was once East Bengal and then East Pakistan, and is now Bangladesh.</p>\r\n\r\n<p>Since its establishment, the university has been fulfilling the hopes and aspirations of its students and their parents. It has, of course, not only been a lighthouse of learning, but has also acted as a torch-bearer of the people of the region in issues such as democracy, freedom of expression, and the need for a just and equitable society. It has always been associated with high quality education and research in which students as well as faculty members and alumni have played their parts. The University of Dhaka&rsquo;s role has expanded beyond its classrooms and research labs during different crises the country has had to face since 1947. In many ways, thus, the university is unique, for it has played a major role in the creation of the independent nation-state of Bangladesh in 1971.</p>\r\n\r\n<p>Writing at this time, I am proud to note that the University of Dhaka has not only fulfilled but also exceeded the aspirations of those who set it up. It has been acclaimed as the best educational institution of the region, and one of the leading universities of the subcontinent. It is an incubator of ideas and has nurtured renowned scientists and academicians, great leaders, administrative and business officials, and entrepreneurs. Its proud alumni include the Father of the Nation Bangabandhu Sheikh Mujibur Rahman, and the incumbent Prime Minister, Sheikh Hasina, his august daughter. Most of the country&rsquo;s presidents, prime ministers, policymakers, politicians and CEOs of leading organizations, researchers and activists have been products of this institution.</p>\r\n\r\n<p>The University of Dhaka&rsquo;s doors are open for people from all classes, religions and parts of the country, and, indeed, also for international students. Its campus, too, regularly hosts different national and international events and festivals where people from every corner can and do participate. It is a hub, for breeding and nourishing liberal, secular and humanistics values.</p>\r\n\r\n<p>At the time when our country is dreaming to become a developed nation by 2041 and has made a firm commitment to achieve the Sustainable Development Goals (SDGs) by 2030, and in an age when we are witnessing the emergence of spirited youths all set to participate in the Fourth Industrial Revolution (4IR), I can affirm that the University of Dhaka is well prepared to meet all future challenges and is ready to lead the nation once again with its acquired experience, available resources, dedicated administrators, experienced faculty members and talented students and employees.</p>\r\n\r\n<p>Having been associated with the university for almost 40 years, first as a student, then as a faculty member, and later in various administrative capacities, it is a great honor and proud privilege for me to be here to not only witness but also to contribute to its centenary celebrations from where I am. Let me emphasize too that it is the singular privilege of all of us at the University of Dhaka to be celebrating its centenary in the year that Bangladesh is celebrating its golden jubilee of independence.</p>\r\n\r\n<p>I would like to request you all to explore the legacy, beauty, and history of this great institution of national, regional and international importance through this website. I hope it will be of use to you as you venture into the knowledge network of the university and acquaint yourself with its history, achievements, centers of learning, residential facilities and other attributes. My office staff and I will be more than happy to listen to you in case you want to visit us in person at a mutually convenient time.&nbsp;</p>\r\n\r\n<p>&nbsp;</p>', '2022-09-07 06:07:09', '2022-09-07 00:19:02'),
(5, 'smtp', '{\"smtp_protocol\":\"smtp\",\"smtp_crypto\":\"tls\",\"smtp_host\":\"smtp.gmail.com\",\"smtp_port\":\"587\",\"smtp_user\":\"your-email\",\"smtp_pass\":\"Email-password\"}', '2022-09-11 04:36:29', '2022-09-10 23:06:38'),
(6, 'about', 'about us', '2022-09-20 03:45:06', '2022-09-20 03:45:06'),
(7, 'policy', 'policy page', '2022-09-20 03:45:06', '2022-09-20 03:45:06'),
(8, 'term', 'term c', '2022-09-20 03:50:51', '2022-09-20 03:50:51'),
(10, 'system_name', 'sociopro', '2022-09-20 03:52:50', '2023-06-21 14:43:29'),
(11, 'system_title', 'Our private social platform', '2022-09-20 03:53:27', '2023-06-21 14:43:29'),
(12, 'system_email', 'admin@example.com', '2022-09-20 03:53:27', '2023-06-21 14:43:29'),
(13, 'system_phone', '236423625746', '2022-09-20 03:53:57', '2023-06-21 14:43:29'),
(14, 'system_fax', '555-123-4567', '2022-09-20 03:53:57', '2023-06-21 14:43:29'),
(15, 'system_address', 'New York, USA', '2022-09-20 03:54:41', '2023-06-21 14:43:29'),
(16, 'system_footer', 'Creativeitem', '2022-09-20 03:54:41', '2023-06-21 14:43:29'),
(17, 'system_footer_link', 'https://creativeitem.com', '2022-09-20 03:55:08', '2023-06-21 14:43:29'),
(18, 'system_dark_logo', '623.png', '2022-09-20 03:55:08', '2022-09-19 21:10:00'),
(19, 'system_light_logo', '727.png', '2022-09-20 03:55:27', '2022-09-19 21:10:00'),
(20, 'system_fav_icon', '450.png', '2022-09-20 03:55:27', '2022-09-19 20:39:06'),
(21, 'version', '2.6.1', '2022-09-20 03:55:27', '2022-09-19 20:39:06'),
(22, 'language', 'english', '2022-09-20 03:55:27', '2022-09-19 20:39:06'),
(23, 'public_signup', '1', '2022-09-20 03:55:27', '2023-06-21 14:43:29'),
(24, 'amazon_s3', '{\"active\":\"0\",\"AWS_ACCESS_KEY_ID\":\"\",\"AWS_SECRET_ACCESS_KEY\":\"\",\"AWS_DEFAULT_REGION\":\"\",\"AWS_BUCKET\":\"\"}', '2022-09-20 03:55:27', '2023-03-29 09:34:49'),
(25, 'ad_charge_per_day', '0.1', '2022-09-20 03:55:27', '2023-06-21 14:43:29'),
(26, 'system_currency', 'USD', '2022-09-07 06:07:09', '2023-06-21 14:43:29'),
(27, 'system_language', 'english', '2022-09-07 06:07:09', '2023-06-21 14:43:29'),
(28, 'purchase_code', 'Enter-your-valid-purchase-code', '2022-09-07 06:07:09', '2023-03-30 09:52:44'),
(29, 'google_analytics_id', NULL, '2022-09-07 06:07:09', '2023-06-21 14:43:29'),
(30, 'meta_pixel_id', NULL, '2022-09-07 06:07:09', '2023-06-21 14:43:29'),
(31, 'commission_rate', '', '2023-08-18 18:21:32', '2023-08-18 18:21:32'),
(32, 'job_price', NULL, '2024-01-08 10:58:12', '2024-04-23 06:22:29'),
(33, 'day', NULL, '2024-01-08 10:58:12', '2024-04-23 06:22:29'),
(34, 'badge_price', NULL, '2024-02-19 09:25:43', '2024-04-08 06:28:30'),
(35, 'theme_color', 'default', '2024-02-19 09:25:43', '2024-04-09 10:01:33'),
(36, 'zitsi_configuration', '{\"account_email\":\"admin@gmail.com\",\"jitsi_app_id\":\"xxxxxxxxxxxxxxxxxxxxxxxxxxx\",\"jitsi_jwt\":\"xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx\"}', '2024-02-19 09:25:43', '2024-04-22 09:13:49'),
(37, 'hugging_face_auth_key', 'Auth Key', '2024-02-19 09:25:43', '2024-11-24 06:12:12');

-- --------------------------------------------------------

--
-- Table structure for table `shares`
--

CREATE TABLE `shares` (
  `id` bigint(20) NOT NULL,
  `share_user_id` text,
  `event_id` int(11) DEFAULT NULL,
  `page_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `url` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sponsors`
--

CREATE TABLE `sponsors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` text,
  `description` text,
  `ext_url` text,
  `image` varchar(255) DEFAULT NULL,
  `paid_amount` double DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `start_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `end_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `stories`
--

CREATE TABLE `stories` (
  `story_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `publisher` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `publisher_id` int(11) DEFAULT NULL,
  `privacy` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `media_files` longtext COLLATE utf8_unicode_ci,
  `description` longtext COLLATE utf8_unicode_ci,
  `status` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nickname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `friends` longtext COLLATE utf8mb4_unicode_ci,
  `followers` longtext COLLATE utf8mb4_unicode_ci,
  `gender` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `studied_at` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profession` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marital_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `about` longtext COLLATE utf8mb4_unicode_ci,
  `save_post` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastActive` timestamp NULL DEFAULT NULL,
  `timezone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `payment_settings` longtext COLLATE utf8mb4_unicode_ci,
  `profile_status` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `category` text,
  `privacy` text,
  `file` text,
  `view` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mobile_app_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_active_requests`
--
ALTER TABLE `account_active_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`activity_id`);

--
-- Indexes for table `addons`
--
ALTER TABLE `addons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `albums`
--
ALTER TABLE `albums`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `album_images`
--
ALTER TABLE `album_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `batchs`
--
ALTER TABLE `batchs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `block_users`
--
ALTER TABLE `block_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blogcategories`
--
ALTER TABLE `blogcategories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blogs`
--
ALTER TABLE `blogs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`);

--
-- Indexes for table `currencies`
--
ALTER TABLE `currencies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `feeling_and_activities`
--
ALTER TABLE `feeling_and_activities`
  ADD PRIMARY KEY (`feeling_and_activity_id`);

--
-- Indexes for table `followers`
--
ALTER TABLE `followers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `friendships`
--
ALTER TABLE `friendships`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invites`
--
ALTER TABLE `invites`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `live_streamings`
--
ALTER TABLE `live_streamings`
  ADD PRIMARY KEY (`streaming_id`);

--
-- Indexes for table `marketplaces`
--
ALTER TABLE `marketplaces`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `media_files`
--
ALTER TABLE `media_files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `message_thrades`
--
ALTER TABLE `message_thrades`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pagecategories`
--
ALTER TABLE `pagecategories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `page_likes`
--
ALTER TABLE `page_likes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `payment_gateways`
--
ALTER TABLE `payment_gateways`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_histories`
--
ALTER TABLE `payment_histories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`);

--
-- Indexes for table `post_shares`
--
ALTER TABLE `post_shares`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `saved_products`
--
ALTER TABLE `saved_products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `saveforlaters`
--
ALTER TABLE `saveforlaters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_id`);

--
-- Indexes for table `shares`
--
ALTER TABLE `shares`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sponsors`
--
ALTER TABLE `sponsors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stories`
--
ALTER TABLE `stories`
  ADD PRIMARY KEY (`story_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_active_requests`
--
ALTER TABLE `account_active_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `addons`
--
ALTER TABLE `addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `albums`
--
ALTER TABLE `albums`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `album_images`
--
ALTER TABLE `album_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batchs`
--
ALTER TABLE `batchs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `block_users`
--
ALTER TABLE `block_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blogcategories`
--
ALTER TABLE `blogcategories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blogs`
--
ALTER TABLE `blogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chats`
--
ALTER TABLE `chats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `currencies`
--
ALTER TABLE `currencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feeling_and_activities`
--
ALTER TABLE `feeling_and_activities`
  MODIFY `feeling_and_activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `followers`
--
ALTER TABLE `followers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `friendships`
--
ALTER TABLE `friendships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invites`
--
ALTER TABLE `invites`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `languages`
--
ALTER TABLE `languages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1446;

--
-- AUTO_INCREMENT for table `live_streamings`
--
ALTER TABLE `live_streamings`
  MODIFY `streaming_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marketplaces`
--
ALTER TABLE `marketplaces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media_files`
--
ALTER TABLE `media_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_thrades`
--
ALTER TABLE `message_thrades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pagecategories`
--
ALTER TABLE `pagecategories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `page_likes`
--
ALTER TABLE `page_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_gateways`
--
ALTER TABLE `payment_gateways`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payment_histories`
--
ALTER TABLE `payment_histories`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post_shares`
--
ALTER TABLE `post_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saved_products`
--
ALTER TABLE `saved_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saveforlaters`
--
ALTER TABLE `saveforlaters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `shares`
--
ALTER TABLE `shares`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sponsors`
--
ALTER TABLE `sponsors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stories`
--
ALTER TABLE `stories`
  MODIFY `story_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
