-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 28, 2025 at 02:50 PM
-- Server version: 10.11.13-MariaDB-0ubuntu0.24.04.1
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce_platform`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`duns1`@`localhost` PROCEDURE `add_column_if_missing` (IN `p_table` VARCHAR(64), IN `p_column` VARCHAR(64), IN `p_definition` TEXT)   BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table
      AND COLUMN_NAME = p_column
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_definition);
    SELECT CONCAT('ADDING COLUMN: ', @ddl) AS info;
    PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$

CREATE DEFINER=`duns1`@`localhost` PROCEDURE `add_index_if_missing` (IN `p_table` VARCHAR(64), IN `p_index` VARCHAR(128), IN `p_definition` TEXT)   BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table
      AND INDEX_NAME = p_index
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD ', p_definition);
    SELECT CONCAT('ADDING INDEX: ', @ddl) AS info;
    PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_feed`
--

CREATE TABLE `activity_feed` (
  `id` int(11) NOT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `actor_type` enum('user','system','admin') NOT NULL DEFAULT 'user',
  `action` varchar(255) NOT NULL,
  `target_type` varchar(100) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('billing','shipping','both') NOT NULL DEFAULT 'both',
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(2) NOT NULL DEFAULT 'US',
  `phone` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_actions`
--

CREATE TABLE `admin_actions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `notes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_actions`
--

INSERT INTO `admin_actions` (`id`, `user_id`, `action`, `target_type`, `target_id`, `old_data`, `new_data`, `notes`, `ip_address`, `created_at`) VALUES
(1, 1, 'update', 'category', 1, NULL, '{\"name\":\"Electronics\",\"parent_id\":null,\"slug\":\"electronics\",\"is_active\":1}', '', NULL, '2025-09-14 20:04:01');

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_logs`
--

CREATE TABLE `admin_activity_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `resource_type` varchar(50) DEFAULT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_analytics`
--

CREATE TABLE `admin_analytics` (
  `id` int(11) NOT NULL,
  `metric_name` varchar(100) NOT NULL,
  `metric_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `metric_type` enum('sales','revenue','orders','users','products','views','clicks') NOT NULL,
  `period_type` enum('daily','weekly','monthly','yearly') NOT NULL DEFAULT 'daily',
  `date_recorded` date NOT NULL,
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_dashboards`
--

CREATE TABLE `admin_dashboards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `layout_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`layout_config`)),
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_shared` tinyint(1) NOT NULL DEFAULT 0,
  `shared_with` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`shared_with`)),
  `refresh_interval` int(11) NOT NULL DEFAULT 300,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_roles`
--

CREATE TABLE `admin_roles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`permissions`)),
  `is_system_role` tinyint(1) NOT NULL DEFAULT 0,
  `hierarchy_level` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_widgets`
--

CREATE TABLE `admin_widgets` (
  `id` int(11) NOT NULL,
  `dashboard_id` int(11) NOT NULL,
  `widget_type` enum('chart','table','counter','progress','list','map','calendar','custom') NOT NULL,
  `widget_name` varchar(255) NOT NULL,
  `data_source` varchar(255) NOT NULL,
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`configuration`)),
  `position_x` int(11) NOT NULL DEFAULT 0,
  `position_y` int(11) NOT NULL DEFAULT 0,
  `width` int(11) NOT NULL DEFAULT 6,
  `height` int(11) NOT NULL DEFAULT 4,
  `refresh_interval` int(11) NOT NULL DEFAULT 300,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_recommendations`
--

CREATE TABLE `ai_recommendations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `recommendation_type` enum('product','category','vendor','content','promotion') NOT NULL,
  `algorithm_used` varchar(100) NOT NULL,
  `recommendation_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`recommendation_data`)),
  `confidence_score` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `interaction_context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`interaction_context`)),
  `is_clicked` tinyint(1) NOT NULL DEFAULT 0,
  `is_purchased` tinyint(1) NOT NULL DEFAULT 0,
  `clicked_at` timestamp NULL DEFAULT NULL,
  `purchased_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_endpoints`
--

CREATE TABLE `api_endpoints` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `endpoint_path` varchar(255) NOT NULL,
  `http_method` enum('GET','POST','PUT','PATCH','DELETE') NOT NULL DEFAULT 'GET',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `requires_auth` tinyint(1) NOT NULL DEFAULT 1,
  `required_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`required_permissions`)),
  `rate_limit_requests` int(11) NOT NULL DEFAULT 100,
  `rate_limit_window` int(11) NOT NULL DEFAULT 3600,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `version` varchar(10) NOT NULL DEFAULT 'v1',
  `documentation_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `api_key` varchar(64) NOT NULL,
  `api_secret` varchar(128) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `rate_limit` int(11) NOT NULL DEFAULT 100,
  `rate_window` int(11) NOT NULL DEFAULT 3600,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_logs`
--

CREATE TABLE `api_logs` (
  `id` int(11) NOT NULL,
  `api_key_id` int(11) DEFAULT NULL,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(10) NOT NULL,
  `request_headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_headers`)),
  `request_body` longtext DEFAULT NULL,
  `response_status` int(11) NOT NULL,
  `response_headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_headers`)),
  `response_body` longtext DEFAULT NULL,
  `response_time` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `resource_type` varchar(100) DEFAULT NULL,
  `resource_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `new_values` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `action`, `resource_type`, `resource_id`, `ip_address`, `user_agent`, `new_values`, `created_at`) VALUES
(1, 1, 'login_failed_inactive', 'user', '1', '105.178.104.198', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '[]', '2025-09-11 18:55:47'),
(2, 1, 'login_success', 'user', '1', '105.178.104.198', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '[]', '2025-09-11 19:11:33'),
(3, 1, 'login_success', 'user', '1', '105.178.104.198', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '[]', '2025-09-11 19:27:53'),
(4, 1, 'login_success', 'user', '1', '105.178.104.198', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '[]', '2025-09-11 19:33:28'),
(5, 1, 'login_success', 'user', '1', '105.178.104.198', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '[]', '2025-09-11 19:36:42'),
(6, 1, 'login_success', 'user', '1', '105.178.104.198', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '[]', '2025-09-11 19:39:47'),
(7, 1, 'login_success', 'user', '1', '105.178.104.198', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '[]', '2025-09-11 19:41:07'),
(8, 1, 'login_success', 'user', '1', '105.178.104.198', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '[]', '2025-09-11 19:41:10'),
(9, 1, 'login_success', 'user', '1', '197.157.155.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '[]', '2025-09-11 21:46:44'),
(10, 1, 'login_success', 'user', '1', '197.157.155.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '[]', '2025-09-11 21:50:31'),
(11, 4, 'login_failed_inactive', 'user', '4', '197.157.155.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '[]', '2025-09-11 21:57:03'),
(12, 1, 'update', 'admin_action', '1', '197.157.145.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '{\"action\":\"update\",\"target_type\":\"category\",\"notes\":\"\",\"new_data\":{\"name\":\"Electronics\",\"parent_id\":null,\"slug\":\"electronics\",\"is_active\":1}}', '2025-09-15 00:04:01'),
(13, 4, 'login_failed_inactive', 'user', '4', '197.157.145.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-15 00:44:24'),
(14, 4, 'login_success', 'user', '4', '197.157.145.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-15 00:45:42'),
(15, 4, 'login_success', 'user', '4', '105.178.32.82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-15 19:09:28'),
(16, 4, 'login_success', 'user', '4', '197.157.155.6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-15 21:22:52'),
(17, 4, 'login_success', 'user', '4', '197.157.155.6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-15 21:29:35'),
(18, 4, 'login_success', 'user', '4', '197.157.155.6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-15 22:00:11'),
(19, 4, 'login_success', 'user', '4', '197.157.155.6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-15 22:08:43'),
(20, 4, 'login_success', 'user', '4', '197.157.155.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-15 23:11:16'),
(21, 4, 'login_success', 'user', '4', '197.157.155.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-15 23:14:55'),
(22, 4, 'login_success', 'user', '4', '197.157.155.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-16 00:19:58'),
(23, 4, 'login_success', 'user', '4', '197.157.155.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-16 01:21:19'),
(24, 4, 'login_success', 'user', '4', '197.157.155.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-16 02:58:23'),
(25, 4, 'login_success', 'user', '4', '105.178.32.56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-16 09:50:29'),
(26, 4, 'login_success', 'user', '4', '105.178.32.56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-16 11:35:24'),
(27, 4, 'login_success', 'user', '4', '105.178.104.56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-16 12:10:19'),
(28, 4, 'login_success', 'user', '4', '105.178.104.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-16 15:25:14'),
(29, 4, 'login_success', 'user', '4', '105.178.32.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-16 20:10:15'),
(30, 4, 'login_success', 'user', '4', '105.178.104.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-16 20:51:50'),
(31, 4, 'login_success', 'user', '4', '197.157.135.133', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-21 02:20:08'),
(32, 4, 'login_success', 'user', '4', '197.157.135.133', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-21 02:21:19'),
(33, NULL, 'login_failed', 'user', NULL, '197.157.135.133', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '{\"email\":\"niyogushimwaj967@gmail.com\"}', '2025-09-21 02:26:56'),
(34, 5, 'login_success', 'user', '5', '197.157.135.133', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-21 02:27:04'),
(35, 4, 'login_success', 'user', '4', '197.157.135.133', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-21 02:31:22'),
(36, NULL, 'login_failed', 'user', NULL, '41.186.132.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '{\"email\":\"ellyj164@gmail.com\"}', '2025-09-21 10:56:56'),
(37, 4, 'login_success', 'user', '4', '41.186.132.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-21 10:57:04'),
(38, 4, 'login_success', 'user', '4', '197.157.187.91', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-21 13:07:53'),
(39, 4, 'login_success', 'user', '4', '105.178.104.165', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-27 11:29:06'),
(40, 4, 'login_success', 'user', '4', '105.178.32.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-27 14:29:31'),
(41, 4, 'login_success', 'user', '4', '102.22.163.69', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-27 16:20:48'),
(42, 4, 'login_success', 'user', '4', '105.178.104.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-27 18:43:44'),
(43, NULL, 'login_failed', 'user', NULL, '105.178.104.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '{\"email\":\"ellyj164@gmail.com\"}', '2025-09-27 19:26:39'),
(44, 4, 'login_success', 'user', '4', '105.178.104.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-27 19:26:47'),
(45, 4, 'login_success', 'user', '4', '105.178.104.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '[]', '2025-09-27 22:44:06');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `level` enum('info','warning','error','critical') NOT NULL DEFAULT 'info',
  `target_id` int(11) DEFAULT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `method` varchar(10) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backups`
--

CREATE TABLE `backups` (
  `id` int(11) NOT NULL,
  `backup_type` enum('database','files','full') NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `compression` enum('none','gzip','zip') NOT NULL DEFAULT 'gzip',
  `status` enum('in_progress','completed','failed') NOT NULL DEFAULT 'in_progress',
  `tables_included` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tables_included`)),
  `paths_included` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`paths_included`)),
  `checksum` varchar(64) DEFAULT NULL,
  `retention_days` int(11) NOT NULL DEFAULT 30,
  `delete_after` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bounces`
--

CREATE TABLE `bounces` (
  `bounce_id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `bounce_type` enum('hard','soft','complaint') NOT NULL,
  `reason` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `gateway_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `slug`, `description`, `logo_path`, `website_url`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Generic Brand', 'generic-brand', 'Default brand placeholder', NULL, NULL, 1, '2025-09-15 15:25:42', '2025-09-15 15:25:42'),
(2, 'Acme', 'acme', 'Acme demonstration brand', NULL, NULL, 1, '2025-09-15 15:25:42', '2025-09-15 15:25:42'),
(3, 'Private Label', 'private-label', NULL, NULL, NULL, 1, '2025-09-20 22:11:14', '2025-09-20 22:11:14');

-- --------------------------------------------------------

--
-- Table structure for table `buyers`
--

CREATE TABLE `buyers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tier` enum('bronze','silver','gold','platinum','diamond') NOT NULL DEFAULT 'bronze',
  `total_spent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `loyalty_points` int(11) NOT NULL DEFAULT 0,
  `preferred_language` varchar(5) DEFAULT 'en',
  `preferred_currency` varchar(3) DEFAULT 'USD',
  `marketing_consent` tinyint(1) NOT NULL DEFAULT 0,
  `data_processing_consent` tinyint(1) NOT NULL DEFAULT 0,
  `last_activity` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_addresses`
--

CREATE TABLE `buyer_addresses` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `label` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `company` varchar(100) DEFAULT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(2) NOT NULL DEFAULT 'US',
  `phone` varchar(20) DEFAULT NULL,
  `is_default_billing` tinyint(1) NOT NULL DEFAULT 0,
  `is_default_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `delivery_instructions` text DEFAULT NULL,
  `access_code` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_consents`
--

CREATE TABLE `buyer_consents` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `consent_type` enum('marketing','analytics','functional','necessary','data_processing','third_party_sharing') NOT NULL,
  `consent_given` tinyint(1) NOT NULL,
  `consent_method` enum('checkbox','opt_in','opt_out','implicit','legal_basis') NOT NULL,
  `legal_basis` varchar(255) DEFAULT NULL,
  `consent_text` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `withdrawn_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_disputes`
--

CREATE TABLE `buyer_disputes` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `vendor_id` int(11) NOT NULL,
  `dispute_number` varchar(50) NOT NULL,
  `type` enum('chargeback','refund_request','product_issue','service_issue','payment_issue','fraud') NOT NULL,
  `status` enum('open','under_review','awaiting_response','resolved','escalated','closed') NOT NULL DEFAULT 'open',
  `amount_disputed` decimal(10,2) NOT NULL,
  `claim_description` text NOT NULL,
  `desired_resolution` text DEFAULT NULL,
  `evidence_provided` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`evidence_provided`)),
  `resolution` text DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `deadline` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_dispute_evidence`
--

CREATE TABLE `buyer_dispute_evidence` (
  `id` int(11) NOT NULL,
  `dispute_id` int(11) NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `evidence_type` enum('document','image','email','communication','tracking','receipt','screenshot') NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `description` text NOT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_dispute_messages`
--

CREATE TABLE `buyer_dispute_messages` (
  `id` int(11) NOT NULL,
  `dispute_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('buyer','seller','admin','system') NOT NULL,
  `message` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_dsr_requests`
--

CREATE TABLE `buyer_dsr_requests` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `request_type` enum('access','portability','rectification','erasure','restrict_processing','object_processing') NOT NULL,
  `status` enum('received','in_progress','completed','rejected','cancelled') NOT NULL DEFAULT 'received',
  `request_details` text DEFAULT NULL,
  `verification_method` enum('email','phone','document','in_person') DEFAULT NULL,
  `verification_completed` tinyint(1) NOT NULL DEFAULT 0,
  `verification_date` timestamp NULL DEFAULT NULL,
  `completion_date` timestamp NULL DEFAULT NULL,
  `response_method` enum('email','download','mail','in_person') DEFAULT NULL,
  `response_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_data`)),
  `rejection_reason` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_kpis`
--

CREATE TABLE `buyer_kpis` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `orders_count` int(11) NOT NULL DEFAULT 0,
  `total_spent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `avg_order_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `returns_count` int(11) NOT NULL DEFAULT 0,
  `loyalty_points_earned` int(11) NOT NULL DEFAULT 0,
  `loyalty_points_spent` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_loyalty_accounts`
--

CREATE TABLE `buyer_loyalty_accounts` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `program_name` varchar(100) NOT NULL DEFAULT 'main',
  `current_points` int(11) NOT NULL DEFAULT 0,
  `lifetime_points` int(11) NOT NULL DEFAULT 0,
  `tier` enum('bronze','silver','gold','platinum','diamond') NOT NULL DEFAULT 'bronze',
  `tier_progress` decimal(5,2) NOT NULL DEFAULT 0.00,
  `next_tier_threshold` int(11) DEFAULT NULL,
  `tier_expiry` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_loyalty_ledger`
--

CREATE TABLE `buyer_loyalty_ledger` (
  `id` int(11) NOT NULL,
  `loyalty_account_id` int(11) NOT NULL,
  `transaction_type` enum('earned','redeemed','expired','adjusted','bonus','refund') NOT NULL,
  `points` int(11) NOT NULL,
  `balance_after` int(11) NOT NULL,
  `reference_type` enum('order','review','referral','birthday','bonus','redemption','expiration','adjustment') DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `expiry_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_messages`
--

CREATE TABLE `buyer_messages` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `conversation_id` varchar(50) NOT NULL,
  `sender_type` enum('buyer','seller','admin','system') NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `message_type` enum('text','image','file','system') NOT NULL DEFAULT 'text',
  `content` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_notifications`
--

CREATE TABLE `buyer_notifications` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `type` enum('order','shipping','delivery','promotion','wishlist','loyalty','system') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `action_url` varchar(500) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_orders`
--

CREATE TABLE `buyer_orders` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled','refunded') NOT NULL,
  `tracking_preference` enum('email','sms','push','all') NOT NULL DEFAULT 'email',
  `delivery_instructions` text DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `rating` tinyint(1) DEFAULT NULL,
  `review` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `can_cancel` tinyint(1) NOT NULL DEFAULT 1,
  `can_return` tinyint(1) NOT NULL DEFAULT 1,
  `return_deadline` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_payment_methods`
--

CREATE TABLE `buyer_payment_methods` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `type` enum('card','paypal','bank_account','crypto','mobile_money','buy_now_pay_later') NOT NULL,
  `provider` varchar(50) NOT NULL,
  `last_four` varchar(4) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `exp_month` tinyint(2) DEFAULT NULL,
  `exp_year` smallint(4) DEFAULT NULL,
  `billing_address_id` int(11) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `token` varchar(255) NOT NULL,
  `fingerprint` varchar(100) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_preferences`
--

CREATE TABLE `buyer_preferences` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `preference_key` varchar(100) NOT NULL,
  `preference_value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_profiles`
--

CREATE TABLE `buyer_profiles` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
  `phone_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `privacy_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`privacy_settings`)),
  `notification_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_preferences`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_rmas`
--

CREATE TABLE `buyer_rmas` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `rma_number` varchar(50) NOT NULL,
  `reason` enum('defective','wrong_item','damaged','not_as_described','change_of_mind','warranty') NOT NULL,
  `status` enum('requested','approved','rejected','shipped','received','refunded','completed') NOT NULL DEFAULT 'requested',
  `return_value` decimal(10,2) NOT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `return_tracking` varchar(100) DEFAULT NULL,
  `return_label_url` varchar(500) DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photos`)),
  `approved_at` timestamp NULL DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_rma_messages`
--

CREATE TABLE `buyer_rma_messages` (
  `id` int(11) NOT NULL,
  `rma_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('buyer','seller','admin','system') NOT NULL,
  `message` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_subscriptions`
--

CREATE TABLE `buyer_subscriptions` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `subscription_type` enum('newsletter','product_updates','price_alerts','promotions','order_updates','security_alerts') NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `status` enum('active','paused','unsubscribed') NOT NULL DEFAULT 'active',
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)),
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_tickets`
--

CREATE TABLE `buyer_tickets` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `ticket_number` varchar(50) NOT NULL,
  `category` enum('order_issue','product_issue','payment_issue','account_issue','technical_issue','general_inquiry') NOT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `status` enum('open','in_progress','waiting_customer','resolved','closed') NOT NULL DEFAULT 'open',
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `assigned_to` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `first_response_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `satisfaction_rating` tinyint(1) DEFAULT NULL,
  `satisfaction_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_ticket_replies`
--

CREATE TABLE `buyer_ticket_replies` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('buyer','agent','system') NOT NULL,
  `message` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_tracking`
--

CREATE TABLE `buyer_tracking` (
  `id` int(11) NOT NULL,
  `buyer_order_id` int(11) NOT NULL,
  `tracking_number` varchar(100) NOT NULL,
  `carrier` varchar(100) NOT NULL,
  `status` enum('label_created','picked_up','in_transit','out_for_delivery','delivered','exception','returned') NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `estimated_delivery` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `tracking_events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tracking_events`)),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_wallets`
--

CREATE TABLE `buyer_wallets` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `status` enum('active','suspended','frozen') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_wallet_entries`
--

CREATE TABLE `buyer_wallet_entries` (
  `id` int(11) NOT NULL,
  `wallet_id` int(11) NOT NULL,
  `transaction_type` enum('credit','debit','refund','cashback','loyalty_conversion','adjustment') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `reference_type` enum('order','refund','cashback','loyalty','promotion','adjustment') DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_wishlist`
--

CREATE TABLE `buyer_wishlist` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variant_info`)),
  `list_name` varchar(100) NOT NULL DEFAULT 'default',
  `notes` text DEFAULT NULL,
  `privacy` enum('private','public','friends') NOT NULL DEFAULT 'private',
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `price_alert_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `target_price` decimal(10,2) DEFAULT NULL,
  `stock_alert_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_wishlist_alerts`
--

CREATE TABLE `buyer_wishlist_alerts` (
  `id` int(11) NOT NULL,
  `wishlist_id` int(11) NOT NULL,
  `alert_type` enum('price_drop','back_in_stock','sale','discontinued') NOT NULL,
  `triggered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `old_value` varchar(255) DEFAULT NULL,
  `new_value` varchar(255) DEFAULT NULL,
  `notification_sent` tinyint(1) NOT NULL DEFAULT 0,
  `notification_sent_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaigns`
--

CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `campaign_type` enum('email','social','banner','flash_sale','push','sms','affiliate') NOT NULL,
  `status` enum('draft','scheduled','active','paused','completed','cancelled') NOT NULL DEFAULT 'draft',
  `budget` decimal(10,2) DEFAULT NULL,
  `spent_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `target_audience` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_audience`)),
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `automation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`automation_rules`)),
  `tracking_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tracking_data`)),
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaign_assets`
--

CREATE TABLE `campaign_assets` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `asset_type` enum('image','video','html','text','banner') NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `click_url` varchar(500) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `a_b_test_variant` varchar(50) DEFAULT NULL,
  `performance_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`performance_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaign_messages`
--

CREATE TABLE `campaign_messages` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `send_time` timestamp NULL DEFAULT NULL,
  `status` enum('scheduled','sent','failed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaign_products`
--

CREATE TABLE `campaign_products` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaign_recipients`
--

CREATE TABLE `campaign_recipients` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('sent','failed') NOT NULL DEFAULT 'sent',
  `opened_at` datetime DEFAULT NULL,
  `clicked_at` datetime DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaign_stats`
--

CREATE TABLE `campaign_stats` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `impressions` int(11) NOT NULL DEFAULT 0,
  `clicks` int(11) NOT NULL DEFAULT 0,
  `conversions` int(11) NOT NULL DEFAULT 0,
  `revenue` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reach` int(11) NOT NULL DEFAULT 0,
  `engagement_rate` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `click_through_rate` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `conversion_rate` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `return_on_ad_spend` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaign_targets`
--

CREATE TABLE `campaign_targets` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `target_type` enum('user','segment','category','product','location') NOT NULL,
  `target_criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`target_criteria`)),
  `estimated_reach` int(11) DEFAULT NULL,
  `actual_reach` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `session_id` varchar(128) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `slug` varchar(120) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `parent_id`, `slug`, `image_url`, `sort_order`, `is_active`, `status`, `meta_title`, `meta_description`, `created_at`, `updated_at`) VALUES
(1, 'Electronics', 'Electronic devices and accessories', NULL, 'electronics', NULL, 1, 1, 'active', '', '', '2025-09-14 19:54:24', '2025-09-14 20:04:01'),
(2, 'Clothing & Fashion', 'Apparel and fashion accessories', NULL, 'clothing-fashion', NULL, 2, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(3, 'Home & Garden', 'Home improvement and garden supplies', NULL, 'home-garden', NULL, 3, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(4, 'Sports & Outdoors', 'Sports equipment and outdoor gear', NULL, 'sports-outdoors', NULL, 4, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(5, 'Books & Media', 'Books, movies, music and digital media', NULL, 'books-media', NULL, 5, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(6, 'Health & Beauty', 'Health products and beauty supplies', NULL, 'health-beauty', NULL, 6, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(7, 'Toys & Games', 'Toys, games and hobby supplies', NULL, 'toys-games', NULL, 7, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(8, 'Automotive', 'Car parts and automotive accessories', NULL, 'automotive', NULL, 8, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(9, 'Food & Beverages', 'Food items and beverages', NULL, 'food-beverages', NULL, 9, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(10, 'Baby & Kids', 'Baby products and children supplies', NULL, 'baby-kids', NULL, 10, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(11, 'Office & Business', 'Office supplies and business equipment', NULL, 'office-business', NULL, 11, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(12, 'Pet Supplies', 'Pet food, toys and accessories', NULL, 'pet-supplies', NULL, 12, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(13, 'Arts & Crafts', 'Art supplies and crafting materials', NULL, 'arts-crafts', NULL, 13, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(14, 'Travel & Luggage', 'Travel accessories and luggage', NULL, 'travel-luggage', NULL, 14, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(15, 'Music & Instruments', 'Musical instruments and equipment', NULL, 'music-instruments', NULL, 15, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(101, 'Smartphones', 'Mobile phones and smartphones', 1, 'smartphones', NULL, 1, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(102, 'Laptops & Computers', 'Laptops, desktops and computer parts', 1, 'laptops-computers', NULL, 2, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(103, 'Tablets', 'Tablet computers and e-readers', 1, 'tablets', NULL, 3, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(104, 'TV & Audio', 'Televisions and audio equipment', 1, 'tv-audio', NULL, 4, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(105, 'Cameras', 'Digital cameras and photography equipment', 1, 'cameras', NULL, 5, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(106, 'Gaming', 'Video game consoles and accessories', 1, 'gaming', NULL, 6, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(107, 'Wearable Tech', 'Smartwatches and fitness trackers', 1, 'wearable-tech', NULL, 7, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(108, 'Home Electronics', 'Small appliances and home tech', 1, 'home-electronics', NULL, 8, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(201, 'Men\'s Clothing', 'Clothing for men', 2, 'mens-clothing', NULL, 1, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(202, 'Women\'s Clothing', 'Clothing for women', 2, 'womens-clothing', NULL, 2, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(203, 'Shoes', 'Footwear for all occasions', 2, 'shoes', NULL, 3, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(204, 'Accessories', 'Fashion accessories and jewelry', 2, 'accessories', NULL, 4, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(205, 'Bags & Luggage', 'Handbags, backpacks and travel bags', 2, 'bags-luggage', NULL, 5, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(206, 'Watches', 'Wristwatches and timepieces', 2, 'watches', NULL, 6, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(207, 'Sunglasses', 'Sunglasses and eyewear', 2, 'sunglasses', NULL, 7, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(301, 'Furniture', 'Home and office furniture', 3, 'furniture', NULL, 1, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(302, 'Kitchen & Dining', 'Kitchen appliances and dining ware', 3, 'kitchen-dining', NULL, 2, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(303, 'Bedding & Bath', 'Bedding, towels and bathroom accessories', 3, 'bedding-bath', NULL, 3, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(304, 'Home Decor', 'Decorative items and artwork', 3, 'home-decor', NULL, 4, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(305, 'Garden & Outdoor', 'Gardening tools and outdoor furniture', 3, 'garden-outdoor', NULL, 5, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(306, 'Lighting', 'Lamps and lighting fixtures', 3, 'lighting', NULL, 6, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(307, 'Storage & Organization', 'Storage solutions and organizers', 3, 'storage-organization', NULL, 7, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(401, 'Fitness Equipment', 'Exercise and fitness gear', 4, 'fitness-equipment', NULL, 1, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(402, 'Team Sports', 'Equipment for team sports', 4, 'team-sports', NULL, 2, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(403, 'Outdoor Recreation', 'Camping, hiking and outdoor gear', 4, 'outdoor-recreation', NULL, 3, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(404, 'Water Sports', 'Swimming and water activity gear', 4, 'water-sports', NULL, 4, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(405, 'Winter Sports', 'Skiing, snowboarding and winter gear', 4, 'winter-sports', NULL, 5, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(406, 'Athletic Wear', 'Sports clothing and footwear', 4, 'athletic-wear', NULL, 6, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(501, 'Books', 'Physical and digital books', 5, 'books', NULL, 1, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(502, 'Movies & TV', 'DVDs, Blu-rays and digital movies', 5, 'movies-tv', NULL, 2, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(503, 'Music', 'CDs, vinyl and digital music', 5, 'music', NULL, 3, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(504, 'Magazines', 'Magazine subscriptions and back issues', 5, 'magazines', NULL, 4, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(505, 'Video Games', 'Game software and digital downloads', 5, 'video-games', NULL, 5, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(601, 'Skincare', 'Facial care and skin treatments', 6, 'skincare', NULL, 1, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(602, 'Makeup', 'Cosmetics and beauty products', 6, 'makeup', NULL, 2, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(603, 'Hair Care', 'Shampoo, conditioner and styling products', 6, 'hair-care', NULL, 3, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(604, 'Personal Care', 'Personal hygiene and grooming products', 6, 'personal-care', NULL, 4, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(605, 'Vitamins & Supplements', 'Health supplements and vitamins', 6, 'vitamins-supplements', NULL, 5, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(606, 'Fragrances', 'Perfumes and colognes', 6, 'fragrances', NULL, 6, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(701, 'Action Figures', 'Action figures and collectibles', 7, 'action-figures', NULL, 1, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(702, 'Board Games', 'Board games and card games', 7, 'board-games', NULL, 2, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(703, 'Building Sets', 'LEGO and construction toys', 7, 'building-sets', NULL, 3, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(704, 'Dolls & Accessories', 'Dolls and doll accessories', 7, 'dolls-accessories', NULL, 4, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(705, 'Educational Toys', 'Learning and educational toys', 7, 'educational-toys', NULL, 5, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(706, 'Outdoor Toys', 'Outdoor play equipment', 7, 'outdoor-toys', NULL, 6, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(801, 'Car Parts', 'Replacement parts and accessories', 8, 'car-parts', NULL, 1, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(802, 'Car Electronics', 'GPS, stereos and car electronics', 8, 'car-electronics', NULL, 2, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(803, 'Motorcycles', 'Motorcycle parts and accessories', 8, 'motorcycles', NULL, 3, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(901, 'Snacks', 'Snack foods and treats', 9, 'snacks', NULL, 1, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(902, 'Beverages', 'Drinks and beverages', 9, 'beverages', NULL, 2, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(903, 'Gourmet Foods', 'Specialty and gourmet food items', 9, 'gourmet-foods', NULL, 3, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(1001, 'Baby Clothing', 'Clothing for babies and toddlers', 10, 'baby-clothing', NULL, 1, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(1002, 'Baby Gear', 'Strollers, car seats and baby equipment', 10, 'baby-gear', NULL, 2, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(1003, 'Baby Feeding', 'Bottles, high chairs and feeding supplies', 10, 'baby-feeding', NULL, 3, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(1101, 'Office Supplies', 'Pens, paper and office essentials', 11, 'office-supplies', NULL, 1, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(1102, 'Office Furniture', 'Desks, chairs and office furniture', 11, 'office-furniture', NULL, 2, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(1201, 'Dog Supplies', 'Food, toys and accessories for dogs', 12, 'dog-supplies', NULL, 1, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(1202, 'Cat Supplies', 'Food, toys and accessories for cats', 12, 'cat-supplies', NULL, 2, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(1203, 'Small Pet Supplies', 'Supplies for birds, fish and small pets', 12, 'small-pet-supplies', NULL, 3, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(1301, 'Painting Supplies', 'Paints, brushes and canvases', 13, 'painting-supplies', NULL, 1, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(1302, 'Crafting Materials', 'Fabric, yarn and crafting supplies', 13, 'crafting-materials', NULL, 2, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(1401, 'Suitcases', 'Travel suitcases and carry-ons', 14, 'suitcases', NULL, 1, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(1402, 'Travel Accessories', 'Travel pillows, adapters and accessories', 14, 'travel-accessories', NULL, 2, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(1501, 'Guitars', 'Acoustic and electric guitars', 15, 'guitars', NULL, 1, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(1502, 'Keyboards & Pianos', 'Digital pianos and keyboards', 15, 'keyboards-pianos', NULL, 2, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24'),
(1503, 'Drums', 'Drum sets and percussion', 15, 'drums', NULL, 3, 1, 'active', NULL, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24');

-- --------------------------------------------------------

--
-- Table structure for table `category_attributes`
--

CREATE TABLE `category_attributes` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `attribute_name` varchar(100) NOT NULL,
  `attribute_type` enum('text','number','boolean','select','multiselect','date') NOT NULL,
  `attribute_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attribute_options`)),
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `is_filterable` tinyint(1) NOT NULL DEFAULT 0,
  `is_searchable` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `stream_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','emoji','system','product_link','moderation') NOT NULL DEFAULT 'text',
  `is_highlighted` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_reason` varchar(255) DEFAULT NULL,
  `parent_message_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cms_media`
--

CREATE TABLE `cms_media` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `media_type` enum('image','video','audio','document','other') NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `uploaded_by` int(11) NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cms_pages`
--

CREATE TABLE `cms_pages` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `page_type` enum('static','policy','help','blog','custom') NOT NULL DEFAULT 'static',
  `template` varchar(100) DEFAULT 'default',
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `featured_image` varchar(500) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `requires_auth` tinyint(1) NOT NULL DEFAULT 0,
  `allowed_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_roles`)),
  `custom_css` text DEFAULT NULL,
  `custom_js` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cms_posts`
--

CREATE TABLE `cms_posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `post_type` enum('blog','news','announcement','tutorial','faq') NOT NULL DEFAULT 'blog',
  `status` enum('draft','published','scheduled','archived') NOT NULL DEFAULT 'draft',
  `featured_image` varchar(500) DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `comment_count` int(11) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comm_messages`
--

CREATE TABLE `comm_messages` (
  `message_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `channel` enum('email','sms','push','in_app') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` longtext NOT NULL,
  `status` enum('pending','sent','delivered','failed','bounced') NOT NULL DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `opened_at` timestamp NULL DEFAULT NULL,
  `clicked_at` timestamp NULL DEFAULT NULL,
  `template_id` int(11) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `personalization_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`personalization_data`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `type` enum('fixed','percentage') NOT NULL DEFAULT 'fixed',
  `value` decimal(10,2) NOT NULL,
  `minimum_amount` decimal(10,2) DEFAULT NULL,
  `maximum_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `user_usage_limit` int(11) DEFAULT NULL,
  `status` enum('active','inactive','expired') NOT NULL DEFAULT 'active',
  `valid_from` timestamp NULL DEFAULT NULL,
  `valid_to` timestamp NULL DEFAULT NULL,
  `applies_to` enum('all','categories','products','users') NOT NULL DEFAULT 'all',
  `applicable_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_items`)),
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupon_redemptions`
--

CREATE TABLE `coupon_redemptions` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `original_order_amount` decimal(10,2) NOT NULL,
  `final_order_amount` decimal(10,2) NOT NULL,
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupon_rules`
--

CREATE TABLE `coupon_rules` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `rule_type` enum('minimum_amount','product_category','user_segment','date_range','usage_limit','first_time_buyer') NOT NULL,
  `rule_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`rule_data`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupon_usage`
--

CREATE TABLE `coupon_usage` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `currencies`
--

CREATE TABLE `currencies` (
  `id` int(11) NOT NULL,
  `code` varchar(3) NOT NULL,
  `name` varchar(100) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `decimal_places` tinyint(2) NOT NULL DEFAULT 2,
  `exchange_rate` decimal(10,6) NOT NULL DEFAULT 1.000000,
  `is_base_currency` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `currencies`
--

INSERT INTO `currencies` (`id`, `code`, `name`, `symbol`, `decimal_places`, `exchange_rate`, `is_base_currency`, `is_active`, `updated_at`, `created_at`) VALUES
(1, 'USD', 'US Dollar', '$', 2, 1.000000, 1, 1, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(2, 'EUR', 'Euro', 'â‚¬', 2, 0.850000, 0, 1, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(3, 'GBP', 'British Pound', 'Â£', 2, 0.750000, 0, 1, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(4, 'JPY', 'Japanese Yen', 'Â¥', 0, 110.000000, 0, 1, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(5, 'CAD', 'Canadian Dollar', 'C$', 2, 1.250000, 0, 1, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(6, 'AUD', 'Australian Dollar', 'A$', 2, 1.350000, 0, 1, '2025-09-14 19:54:26', '2025-09-14 19:54:26');

-- --------------------------------------------------------

--
-- Table structure for table `customer_order_feedback`
--

CREATE TABLE `customer_order_feedback` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` between 1 and 5),
  `delivery_rating` tinyint(1) DEFAULT NULL CHECK (`delivery_rating` between 1 and 5),
  `communication_rating` tinyint(1) DEFAULT NULL CHECK (`communication_rating` between 1 and 5),
  `feedback_text` text DEFAULT NULL,
  `would_recommend` tinyint(1) DEFAULT NULL,
  `issues_encountered` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`issues_encountered`)),
  `seller_response` text DEFAULT NULL,
  `seller_responded_at` timestamp NULL DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `helpful_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_profiles`
--

CREATE TABLE `customer_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
  `interests` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`interests`)),
  `preferred_language` varchar(5) NOT NULL DEFAULT 'en',
  `preferred_currency` varchar(3) NOT NULL DEFAULT 'USD',
  `marketing_consent` tinyint(1) NOT NULL DEFAULT 0,
  `data_processing_consent` tinyint(1) NOT NULL DEFAULT 1,
  `newsletter_subscription` tinyint(1) NOT NULL DEFAULT 0,
  `sms_notifications` tinyint(1) NOT NULL DEFAULT 0,
  `loyalty_points` int(11) NOT NULL DEFAULT 0,
  `total_spent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `favorite_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`favorite_categories`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_profiles`
--

INSERT INTO `customer_profiles` (`id`, `user_id`, `date_of_birth`, `gender`, `interests`, `preferred_language`, `preferred_currency`, `marketing_consent`, `data_processing_consent`, `newsletter_subscription`, `sms_notifications`, `loyalty_points`, `total_spent`, `total_orders`, `favorite_categories`, `created_at`, `updated_at`) VALUES
(1, 4, NULL, NULL, NULL, 'en', 'USD', 0, 1, 0, 0, 0, 0.00, 0, NULL, '2025-09-14 19:54:24', '2025-09-14 19:54:24');

-- --------------------------------------------------------

--
-- Table structure for table `customer_support_conversations`
--

CREATE TABLE `customer_support_conversations` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `message` longtext NOT NULL,
  `message_type` enum('customer','vendor','admin','system','auto') NOT NULL DEFAULT 'customer',
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `read_by_customer` tinyint(1) NOT NULL DEFAULT 0,
  `read_by_vendor` tinyint(1) NOT NULL DEFAULT 0,
  `read_by_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_widgets`
--

CREATE TABLE `dashboard_widgets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `widget_type` enum('kpi','chart','table','notification','counter','link','activity') NOT NULL,
  `widget_name` varchar(255) NOT NULL,
  `widget_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`widget_config`)),
  `position_x` int(11) NOT NULL DEFAULT 0,
  `position_y` int(11) NOT NULL DEFAULT 0,
  `width` int(11) NOT NULL DEFAULT 4,
  `height` int(11) NOT NULL DEFAULT 4,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disputes`
--

CREATE TABLE `disputes` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `dispute_number` varchar(50) NOT NULL,
  `type` enum('refund','return','quality','delivery','billing','other') NOT NULL,
  `category` enum('item_not_received','item_damaged','wrong_item','quality_issue','billing_error','shipping_issue','other') NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `amount_disputed` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('open','investigating','pending_vendor','pending_customer','escalated','resolved','closed') NOT NULL DEFAULT 'open',
  `sla_deadline` datetime DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `assigned_to` int(11) DEFAULT NULL,
  `escalated_to` int(11) DEFAULT NULL,
  `sla_due_date` timestamp NULL DEFAULT NULL,
  `resolution_type` enum('refund','replacement','partial_refund','discount','no_action') DEFAULT NULL,
  `resolution_amount` decimal(10,2) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `customer_satisfaction` tinyint(1) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dispute_decisions`
--

CREATE TABLE `dispute_decisions` (
  `id` int(11) NOT NULL,
  `dispute_id` int(11) NOT NULL,
  `decided_by` int(11) NOT NULL,
  `decision` enum('favor_customer','favor_vendor','split_decision','need_more_info','escalate') NOT NULL,
  `reasoning` text NOT NULL,
  `resolution_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`resolution_details`)),
  `financial_impact` decimal(10,2) DEFAULT NULL,
  `follow_up_required` tinyint(1) NOT NULL DEFAULT 0,
  `follow_up_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dispute_evidence`
--

CREATE TABLE `dispute_evidence` (
  `id` int(11) NOT NULL,
  `dispute_id` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `evidence_type` enum('image','document','video','other') NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dispute_messages`
--

CREATE TABLE `dispute_messages` (
  `id` int(11) NOT NULL,
  `dispute_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('customer','vendor','admin','system') NOT NULL,
  `message` text NOT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `read_by_customer` tinyint(1) NOT NULL DEFAULT 0,
  `read_by_vendor` tinyint(1) NOT NULL DEFAULT 0,
  `read_by_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'general',
  `user_id` int(11) DEFAULT NULL,
  `status` enum('pending','sent','failed','error') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `to_name` varchar(255) DEFAULT NULL,
  `subject` varchar(500) NOT NULL,
  `body` longtext NOT NULL,
  `template` varchar(100) DEFAULT NULL,
  `template_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`template_data`)),
  `priority` tinyint(1) NOT NULL DEFAULT 3,
  `status` enum('pending','sending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `max_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 3,
  `error_message` text DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_tokens`
--

CREATE TABLE `email_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `type` enum('email_verification','password_reset','email_change','two_fa_backup') NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_tokens`
--

INSERT INTO `email_tokens` (`id`, `user_id`, `token`, `type`, `email`, `expires_at`, `used_at`, `ip_address`, `created_at`) VALUES
(1, 5, '7ab495d655a50fd0e2f4317d4a2af14cdea18c06caccbc25010954ee7bbcc194', 'email_verification', 'niyogushimwaj967@gmail.com', '2025-09-20 20:38:57', NULL, '172.68.42.184', '2025-09-20 20:23:57');

-- --------------------------------------------------------

--
-- Table structure for table `fact_campaigns`
--

CREATE TABLE `fact_campaigns` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `date_key` int(11) NOT NULL,
  `impressions` int(11) NOT NULL DEFAULT 0,
  `clicks` int(11) NOT NULL DEFAULT 0,
  `conversions` int(11) NOT NULL DEFAULT 0,
  `revenue` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fact_sales`
--

CREATE TABLE `fact_sales` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `date_key` int(11) NOT NULL,
  `time_key` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `commission_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT NULL,
  `order_status` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fact_users`
--

CREATE TABLE `fact_users` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_key` int(11) NOT NULL,
  `registration_date` date DEFAULT NULL,
  `last_login_date` date DEFAULT NULL,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `total_spent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `average_order_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `days_since_last_order` int(11) DEFAULT NULL,
  `user_segment` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `file_uploads`
--

CREATE TABLE `file_uploads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) UNSIGNED NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_hash` varchar(64) DEFAULT NULL,
  `upload_type` enum('product_image','user_avatar','document','attachment','other') NOT NULL DEFAULT 'other',
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `download_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `homepage_banners`
--

CREATE TABLE `homepage_banners` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) NOT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `button_text` varchar(100) DEFAULT NULL,
  `background_color` varchar(7) DEFAULT '#ffffff',
  `text_color` varchar(7) DEFAULT '#000000',
  `position` enum('hero','top','middle','bottom','sidebar') NOT NULL DEFAULT 'hero',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive','draft','scheduled') NOT NULL DEFAULT 'draft',
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `click_count` int(11) NOT NULL DEFAULT 0,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `target_audience` enum('all','customers','vendors','new_users') NOT NULL DEFAULT 'all',
  `device_target` enum('all','desktop','mobile','tablet') NOT NULL DEFAULT 'all',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `homepage_sections`
--

CREATE TABLE `homepage_sections` (
  `id` int(11) NOT NULL,
  `section_key` varchar(100) NOT NULL,
  `section_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`section_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `homepage_sections`
--

INSERT INTO `homepage_sections` (`id`, `section_key`, `section_data`, `created_at`, `updated_at`) VALUES
(1, 'layout_config', '[{\"id\":\"hero\",\"type\":\"hero\",\"title\":\"Hero Banner\",\"enabled\":true},{\"id\":\"categories\",\"type\":\"categories\",\"title\":\"Featured Categories\",\"enabled\":true},{\"id\":\"deals\",\"type\":\"deals\",\"title\":\"Daily Deals\",\"enabled\":true},{\"id\":\"trending\",\"type\":\"products\",\"title\":\"Trending Products\",\"enabled\":true},{\"id\":\"brands\",\"type\":\"brands\",\"title\":\"Top Brands\",\"enabled\":true},{\"id\":\"featured\",\"type\":\"products\",\"title\":\"Featured Products\",\"enabled\":true},{\"id\":\"new-arrivals\",\"type\":\"products\",\"title\":\"New Arrivals\",\"enabled\":true},{\"id\":\"recommendations\",\"type\":\"products\",\"title\":\"Recommended for You\",\"enabled\":true}]', '2025-09-27 18:28:52', '2025-09-27 18:46:41');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `safety_stock` int(11) NOT NULL DEFAULT 0,
  `min_stock_level` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_adjustments`
--

CREATE TABLE `inventory_adjustments` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `adjustment` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `adjusted_by` int(11) DEFAULT NULL,
  `adjusted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_alerts`
--

CREATE TABLE `inventory_alerts` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `alert_type` enum('low_stock','out_of_stock','high_demand','slow_moving') NOT NULL,
  `threshold_value` int(11) NOT NULL,
  `current_value` int(11) NOT NULL,
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` enum('active','acknowledged','resolved','dismissed') NOT NULL DEFAULT 'active',
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `status` enum('draft','sent','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
  `pdf_path` varchar(500) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `job_type` enum('scheduled','manual','automatic') NOT NULL DEFAULT 'scheduled',
  `command` varchar(500) NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `schedule` varchar(100) DEFAULT NULL,
  `status` enum('pending','running','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `priority` enum('low','normal','high','critical') NOT NULL DEFAULT 'normal',
  `progress` int(11) NOT NULL DEFAULT 0,
  `output` longtext DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `max_retries` int(11) NOT NULL DEFAULT 3,
  `timeout` int(11) NOT NULL DEFAULT 3600,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_daily`
--

CREATE TABLE `kpi_daily` (
  `id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `total_sales` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `gmv` decimal(15,2) NOT NULL DEFAULT 0.00,
  `commission_revenue` decimal(15,2) NOT NULL DEFAULT 0.00,
  `active_users` int(11) NOT NULL DEFAULT 0,
  `active_buyers` int(11) NOT NULL DEFAULT 0,
  `active_sellers` int(11) NOT NULL DEFAULT 0,
  `guest_visitors` int(11) NOT NULL DEFAULT 0,
  `conversion_rate` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `average_order_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kyc_decisions`
--

CREATE TABLE `kyc_decisions` (
  `id` int(11) NOT NULL,
  `kyc_request_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `decision` enum('approve','reject','request_more_info','escalate') NOT NULL,
  `reason` text NOT NULL,
  `risk_assessment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`risk_assessment`)),
  `follow_up_required` tinyint(1) NOT NULL DEFAULT 0,
  `follow_up_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kyc_documents`
--

CREATE TABLE `kyc_documents` (
  `id` int(11) NOT NULL,
  `kyc_request_id` int(11) NOT NULL,
  `document_type` enum('passport','drivers_license','national_id','utility_bill','bank_statement','business_registration','other') NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `ocr_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ocr_data`)),
  `verification_status` enum('pending','processing','verified','failed') NOT NULL DEFAULT 'pending',
  `verification_notes` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kyc_flags`
--

CREATE TABLE `kyc_flags` (
  `id` int(11) NOT NULL,
  `kyc_request_id` int(11) NOT NULL,
  `flag_type` enum('duplicate_identity','suspicious_activity','high_risk_country','document_mismatch','other') NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `description` text NOT NULL,
  `flag_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`flag_data`)),
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kyc_requests`
--

CREATE TABLE `kyc_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_type` enum('individual','business','enhanced') NOT NULL DEFAULT 'individual',
  `status` enum('pending','in_review','approved','rejected','requires_more_info') NOT NULL DEFAULT 'pending',
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `risk_score` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `personal_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`personal_info`)),
  `business_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`business_info`)),
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_chat_messages`
--

CREATE TABLE `live_chat_messages` (
  `id` int(11) NOT NULL,
  `stream_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `message_type` enum('chat','system','product','reaction') NOT NULL DEFAULT 'chat',
  `is_highlighted` tinyint(1) NOT NULL DEFAULT 0,
  `is_moderated` tinyint(1) NOT NULL DEFAULT 0,
  `moderated_by` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_streams`
--

CREATE TABLE `live_streams` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `stream_url` varchar(500) DEFAULT NULL,
  `chat_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('scheduled','live','ended','cancelled') NOT NULL DEFAULT 'scheduled',
  `viewer_count` int(11) NOT NULL DEFAULT 0,
  `max_viewers` int(11) NOT NULL DEFAULT 0,
  `total_revenue` decimal(10,2) NOT NULL DEFAULT 0.00,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_stream_products`
--

CREATE TABLE `live_stream_products` (
  `id` int(11) NOT NULL,
  `stream_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `special_price` decimal(10,2) DEFAULT NULL,
  `discount_percentage` decimal(5,2) DEFAULT NULL,
  `limited_quantity` int(11) DEFAULT NULL,
  `sold_quantity` int(11) NOT NULL DEFAULT 0,
  `featured_order` int(11) NOT NULL DEFAULT 0,
  `featured_at` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `user_agent` text DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `identifier`, `ip_address`, `success`, `user_agent`, `attempted_at`) VALUES
(3, 'ellyj164@gmail.com', '172.69.254.164', 1, NULL, '2025-09-11 15:11:33'),
(4, 'ellyj164@gmail.com', '172.69.254.164', 1, NULL, '2025-09-11 15:27:53'),
(5, 'ellyj164@gmail.com', '172.69.254.164', 1, NULL, '2025-09-11 15:33:28'),
(6, 'ellyj164@gmail.com', '172.69.254.164', 1, NULL, '2025-09-11 15:36:42'),
(7, 'ellyj164@gmail.com', '172.69.254.165', 1, NULL, '2025-09-11 15:39:47'),
(8, 'ellyj164@gmail.com', '172.69.254.164', 1, NULL, '2025-09-11 15:41:07'),
(9, 'ellyj164@gmail.com', '172.69.254.164', 1, NULL, '2025-09-11 15:41:10'),
(10, 'ellyj164@gmail.com', '172.68.42.185', 1, NULL, '2025-09-11 17:46:44'),
(11, 'ellyj164@gmail.com', '197.234.242.181', 1, NULL, '2025-09-11 17:50:31'),
(14, 'ellyj164@gmail.com', '197.234.242.180', 1, NULL, '2025-09-14 20:45:42'),
(15, 'ellyj164@gmail.com', '172.69.254.164', 1, NULL, '2025-09-15 15:09:28'),
(16, 'ellyj164@gmail.com', '197.234.242.180', 1, NULL, '2025-09-15 17:22:52'),
(17, 'ellyj164@gmail.com', '172.68.42.184', 1, NULL, '2025-09-15 17:29:35'),
(18, 'ellyj164@gmail.com', '172.68.42.185', 1, NULL, '2025-09-15 18:00:11'),
(19, 'ellyj164@gmail.com', '197.234.242.180', 1, NULL, '2025-09-15 18:08:43'),
(20, 'ellyj164@gmail.com', '197.234.242.180', 1, NULL, '2025-09-15 19:11:16'),
(21, 'ellyj164@gmail.com', '197.234.242.181', 1, NULL, '2025-09-15 19:14:55'),
(22, 'ellyj164@gmail.com', '197.234.242.181', 1, NULL, '2025-09-15 20:19:58'),
(23, 'ellyj164@gmail.com', '172.68.42.184', 1, NULL, '2025-09-15 21:21:19'),
(24, 'ellyj164@gmail.com', '172.68.42.185', 1, NULL, '2025-09-15 22:58:23'),
(25, 'ellyj164@gmail.com', '172.69.254.163', 1, NULL, '2025-09-16 05:50:29'),
(26, 'ellyj164@gmail.com', '172.69.254.165', 1, NULL, '2025-09-16 07:35:24'),
(27, 'ellyj164@gmail.com', '172.69.254.165', 1, NULL, '2025-09-16 08:10:19'),
(28, 'ellyj164@gmail.com', '172.69.254.164', 1, NULL, '2025-09-16 11:25:14'),
(29, 'ellyj164@gmail.com', '172.69.254.164', 1, NULL, '2025-09-16 16:10:15'),
(30, 'ellyj164@gmail.com', '172.69.254.164', 1, NULL, '2025-09-16 16:51:50'),
(31, 'ellyj164@gmail.com', '172.68.42.185', 1, NULL, '2025-09-20 22:20:08'),
(32, 'ellyj164@gmail.com', '172.68.42.185', 1, NULL, '2025-09-20 22:21:19'),
(34, 'niyogushimwaj967@gmail.com', '197.234.242.180', 1, NULL, '2025-09-20 22:27:04'),
(35, 'ellyj164@gmail.com', '172.68.42.185', 1, NULL, '2025-09-20 22:31:22'),
(37, 'ellyj164@gmail.com', '197.234.242.154', 1, NULL, '2025-09-21 06:57:04'),
(38, 'ellyj164@gmail.com', '172.68.42.184', 1, NULL, '2025-09-21 09:07:53'),
(39, 'ellyj164@gmail.com', '172.69.254.165', 1, NULL, '2025-09-27 09:29:06'),
(40, 'ellyj164@gmail.com', '172.69.254.165', 1, NULL, '2025-09-27 12:29:31'),
(41, 'ellyj164@gmail.com', '172.68.42.184', 1, NULL, '2025-09-27 14:20:48'),
(42, 'ellyj164@gmail.com', '172.69.254.165', 1, NULL, '2025-09-27 16:43:44'),
(44, 'ellyj164@gmail.com', '172.69.254.165', 1, NULL, '2025-09-27 17:26:47'),
(45, 'ellyj164@gmail.com', '172.69.254.164', 1, NULL, '2025-09-27 20:44:06');

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_tiers`
--

CREATE TABLE `loyalty_tiers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `min_points` int(11) NOT NULL DEFAULT 0,
  `max_points` int(11) DEFAULT NULL,
  `benefits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`benefits`)),
  `point_multiplier` decimal(3,2) NOT NULL DEFAULT 1.00,
  `icon` varchar(255) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loyalty_tiers`
--

INSERT INTO `loyalty_tiers` (`id`, `name`, `description`, `min_points`, `max_points`, `benefits`, `point_multiplier`, `icon`, `color`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'Bronze', 'Entry level tier', 0, 999, '{\"free_shipping_threshold\": 100, \"birthday_bonus\": 50}', 1.00, NULL, NULL, 1, 1, '2025-09-14 19:54:26'),
(2, 'Silver', 'Intermediate tier', 1000, 4999, '{\"free_shipping_threshold\": 75, \"birthday_bonus\": 100, \"early_access\": true}', 1.25, NULL, NULL, 2, 1, '2025-09-14 19:54:26'),
(3, 'Gold', 'Premium tier', 5000, 14999, '{\"free_shipping\": true, \"birthday_bonus\": 200, \"early_access\": true, \"priority_support\": true}', 1.50, NULL, NULL, 3, 1, '2025-09-14 19:54:26'),
(4, 'Platinum', 'Elite tier', 15000, NULL, '{\"free_shipping\": true, \"birthday_bonus\": 500, \"early_access\": true, \"priority_support\": true, \"exclusive_offers\": true}', 2.00, NULL, NULL, 4, 1, '2025-09-14 19:54:26');

-- --------------------------------------------------------

--
-- Table structure for table `marketing_campaigns`
--

CREATE TABLE `marketing_campaigns` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `type` enum('email','sms') NOT NULL DEFAULT 'email',
  `description` text DEFAULT NULL,
  `campaign_type` enum('flash_sale','daily_deal','seasonal','promotion','affiliate','email','social') NOT NULL,
  `status` enum('draft','scheduled','active','paused','completed','cancelled') NOT NULL DEFAULT 'draft',
  `budget` decimal(10,2) DEFAULT NULL,
  `spent_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `target_audience` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_audience`)),
  `discount_type` enum('percentage','fixed','bogo','free_shipping') DEFAULT NULL,
  `discount_value` decimal(10,2) DEFAULT NULL,
  `minimum_order_amount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_limit_per_user` int(11) DEFAULT 1,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `applicable_products` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_products`)),
  `applicable_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_categories`)),
  `tracking_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tracking_data`)),
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

CREATE TABLE `menus` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(50) NOT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` varchar(100) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','image','file','system') NOT NULL DEFAULT 'text',
  `attachment_url` varchar(500) DEFAULT NULL,
  `attachment_type` varchar(50) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `parent_message_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_delivery_logs`
--

CREATE TABLE `message_delivery_logs` (
  `log_id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `event_type` enum('sent','delivered','opened','clicked','bounced','complained','unsubscribed') NOT NULL,
  `event_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `gateway_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_response`)),
  `user_agent` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_templates`
--

CREATE TABLE `message_templates` (
  `template_id` int(11) NOT NULL,
  `type` enum('email','sms','push','in_app') NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `content_html` longtext DEFAULT NULL,
  `content_text` longtext DEFAULT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `version` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `category` varchar(100) DEFAULT NULL,
  `language` varchar(5) NOT NULL DEFAULT 'en',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL DEFAULT 1,
  `executed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `multi_language_content`
--

CREATE TABLE `multi_language_content` (
  `id` int(11) NOT NULL,
  `content_type` enum('product','category','cms_page','banner','notification') NOT NULL,
  `content_id` int(11) NOT NULL,
  `language_code` varchar(5) NOT NULL DEFAULT 'en',
  `field_name` varchar(100) NOT NULL,
  `translated_content` longtext NOT NULL,
  `is_auto_translated` tinyint(1) NOT NULL DEFAULT 0,
  `translator_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('order','promotion','wishlist','account','system','vendor','live_shopping','security') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `action_url` varchar(500) DEFAULT NULL,
  `action_text` varchar(100) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `read_at` timestamp NULL DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `expires_at` timestamp NULL DEFAULT NULL,
  `sent_via_email` tinyint(1) NOT NULL DEFAULT 0,
  `sent_via_push` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('order','promotion','wishlist','account','system','vendor','live_shopping','security') NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `email_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `push_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `sms_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `frequency` enum('immediate','hourly','daily','weekly') NOT NULL DEFAULT 'immediate',
  `quiet_hours_start` time DEFAULT NULL,
  `quiet_hours_end` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed','refunded','partial_refund') NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_transaction_id` varchar(255) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `billing_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`billing_address`)),
  `shipping_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`shipping_address`)),
  `shipping_method` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_disputes`
--

CREATE TABLE `order_disputes` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `order_item_id` int(11) DEFAULT NULL,
  `buyer_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `dispute_type` enum('product_not_received','product_not_as_described','quality_issue','shipping_damage','refund_request','warranty_claim') NOT NULL,
  `dispute_reason` text NOT NULL,
  `buyer_evidence` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`buyer_evidence`)),
  `vendor_response` text DEFAULT NULL,
  `vendor_evidence` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vendor_evidence`)),
  `admin_notes` text DEFAULT NULL,
  `status` enum('open','under_review','pending_buyer_response','pending_vendor_response','escalated','resolved','closed') NOT NULL DEFAULT 'open',
  `resolution` enum('refund_full','refund_partial','replacement','repair','no_action','favor_vendor','favor_buyer') DEFAULT NULL,
  `resolution_amount` decimal(10,2) DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `escalated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `status` enum('pending','processing','shipped','delivered','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `tracking_number` varchar(100) DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `from_status` varchar(50) DEFAULT NULL,
  `to_status` varchar(50) NOT NULL,
  `reason` text DEFAULT NULL,
  `changed_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `notify_customer` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otp_attempts`
--

CREATE TABLE `otp_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `token_type` enum('email_verification','password_reset','email_change','two_fa_backup') NOT NULL DEFAULT 'email_verification'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_attempts`
--

INSERT INTO `otp_attempts` (`id`, `user_id`, `email`, `ip_address`, `attempted_at`, `success`, `token_type`) VALUES
(1, 5, 'niyogushimwaj967@gmail.com', '172.68.42.185', '2025-09-20 22:24:28', 0, 'email_verification');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL,
  `gateway` varchar(50) NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `gateway_transaction_id` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `status` enum('pending','processing','completed','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `gateway_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_response`)),
  `fees` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(10,2) NOT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `refund_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_events`
--

CREATE TABLE `payment_events` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `event_type` enum('created','processed','completed','failed','refunded','disputed','settled') NOT NULL,
  `event_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_data`)),
  `gateway_event_id` varchar(255) DEFAULT NULL,
  `webhook_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`webhook_data`)),
  `processed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_gateways`
--

CREATE TABLE `payment_gateways` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `provider` enum('stripe','paypal','square','authorize_net','braintree','razorpay','custom') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `supported_currencies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supported_currencies`)),
  `supported_countries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supported_countries`)),
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration`)),
  `test_mode` tinyint(1) NOT NULL DEFAULT 1,
  `transaction_fee_percentage` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `transaction_fee_fixed` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_amount` decimal(10,2) NOT NULL DEFAULT 0.01,
  `max_amount` decimal(10,2) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('credit_card','debit_card','paypal','bank_transfer','wallet') NOT NULL,
  `provider` varchar(50) DEFAULT NULL,
  `last_four` varchar(4) DEFAULT NULL,
  `exp_month` tinyint(2) DEFAULT NULL,
  `exp_year` smallint(4) DEFAULT NULL,
  `cardholder_name` varchar(100) DEFAULT NULL,
  `brand` varchar(20) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `fingerprint` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_reconciliations`
--

CREATE TABLE `payment_reconciliations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `reconciled_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payouts`
--

CREATE TABLE `payouts` (
  `id` int(11) NOT NULL,
  `payout_request_id` int(11) NOT NULL,
  `batch_id` varchar(100) DEFAULT NULL,
  `gateway_payout_id` varchar(255) DEFAULT NULL,
  `gateway_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_response`)),
  `fees_charged` decimal(10,2) NOT NULL DEFAULT 0.00,
  `exchange_rate` decimal(10,6) DEFAULT NULL,
  `final_amount` decimal(10,2) NOT NULL,
  `final_currency` varchar(3) NOT NULL DEFAULT 'USD',
  `status` enum('initiated','processing','completed','failed','returned') NOT NULL DEFAULT 'initiated',
  `tracking_reference` varchar(255) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payout_requests`
--

CREATE TABLE `payout_requests` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `wallet_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_amount` decimal(10,2) NOT NULL,
  `processing_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL,
  `payout_method` enum('bank_transfer','paypal','stripe','wise','check','manual') NOT NULL,
  `payout_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payout_details`)),
  `status` enum('pending','approved','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `approval_notes` text DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `external_transaction_id` varchar(255) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `platform_notifications`
--

CREATE TABLE `platform_notifications` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('announcement','maintenance','promotion','warning','info') NOT NULL DEFAULT 'info',
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `target_audience` enum('all','customers','vendors','admins') NOT NULL DEFAULT 'all',
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#007bff',
  `action_url` varchar(500) DEFAULT NULL,
  `action_text` varchar(100) DEFAULT NULL,
  `is_dismissible` tinyint(1) NOT NULL DEFAULT 1,
  `auto_dismiss_after` int(11) DEFAULT NULL COMMENT 'Auto dismiss after X seconds',
  `status` enum('draft','active','paused','expired') NOT NULL DEFAULT 'draft',
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `total_sent` int(11) NOT NULL DEFAULT 0,
  `total_read` int(11) NOT NULL DEFAULT 0,
  `total_clicked` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `platform_notification_reads`
--

CREATE TABLE `platform_notification_reads` (
  `id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `clicked_at` timestamp NULL DEFAULT NULL,
  `dismissed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `seller_id` int(10) UNSIGNED DEFAULT NULL,
  `vendor_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `brand_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(275) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL, -- Main product image URL for compatibility
  `sku` varchar(100) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `compare_price` decimal(12,2) DEFAULT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `currency_code` char(3) NOT NULL DEFAULT 'USD',
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `min_stock_level` int(11) NOT NULL DEFAULT 5,
  `max_stock_level` int(11) DEFAULT NULL,
  `weight` decimal(8,2) DEFAULT NULL,
  `dimensions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dimensions`)),
  `status` enum('active','inactive','draft','archived') NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `visibility` enum('public','private','hidden') NOT NULL DEFAULT 'public',
  `track_inventory` tinyint(1) NOT NULL DEFAULT 1,
  `allow_backorder` tinyint(1) NOT NULL DEFAULT 0,
  `stock_qty` int(11) DEFAULT NULL,
  `low_stock_threshold` int(11) DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `digital` tinyint(1) NOT NULL DEFAULT 0,
  `downloadable` tinyint(1) NOT NULL DEFAULT 0,
  `virtual` tinyint(1) NOT NULL DEFAULT 0,
  `tags` text DEFAULT NULL,
  `attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attributes`)),
  `variations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variations`)),
  `shipping_class` varchar(50) DEFAULT NULL,
  `weight_kg` decimal(10,3) DEFAULT NULL,
  `length_cm` decimal(10,2) DEFAULT NULL,
  `width_cm` decimal(10,2) DEFAULT NULL,
  `height_cm` decimal(10,2) DEFAULT NULL,
  `seo_title` varchar(70) DEFAULT NULL,
  `seo_description` varchar(170) DEFAULT NULL,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `return_policy_text` text DEFAULT NULL,
  `warranty_text` text DEFAULT NULL,
  `compliance_notes` text DEFAULT NULL,
  `age_restriction` tinyint(1) NOT NULL DEFAULT 0,
  `digital_is` tinyint(1) NOT NULL DEFAULT 0,
  `digital_url` varchar(512) DEFAULT NULL,
  `digital_file_path` varchar(512) DEFAULT NULL,
  `thumbnail_path` varchar(512) DEFAULT NULL,
  `custom_barcode` varchar(64) DEFAULT NULL,
  `mpn` varchar(64) DEFAULT NULL,
  `gtin` varchar(64) DEFAULT NULL,
  `condition` enum('new','used','refurbished') NOT NULL DEFAULT 'new',
  `brand` varchar(160) DEFAULT NULL,
  `tax_status` enum('taxable','shipping','none') NOT NULL DEFAULT 'taxable',
  `tax_class` varchar(50) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `keywords` text DEFAULT NULL, -- SEO keywords field for product search functionality
  `view_count` int(11) NOT NULL DEFAULT 0,
  `purchase_count` int(11) NOT NULL DEFAULT 0,
  `average_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `review_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `vendor_id`, `category_id`, `brand_id`, `name`, `slug`, `description`, `short_description`, `image_url`, `sku`, `barcode`, `price`, `compare_price`, `sale_price`, `cost_price`, `currency_code`, `stock_quantity`, `min_stock_level`, `max_stock_level`, `weight`, `dimensions`, `status`, `is_featured`, `visibility`, `track_inventory`, `allow_backorder`, `stock_qty`, `low_stock_threshold`, `featured`, `digital`, `downloadable`, `virtual`, `tags`, `attributes`, `variations`, `shipping_class`, `weight_kg`, `length_cm`, `width_cm`, `height_cm`, `seo_title`, `seo_description`, `seo_keywords`, `published_at`, `scheduled_at`, `return_policy_text`, `warranty_text`, `compliance_notes`, `age_restriction`, `digital_is`, `digital_url`, `digital_file_path`, `thumbnail_path`, `custom_barcode`, `mpn`, `gtin`, `condition`, `brand`, `tax_status`, `tax_class`, `meta_title`, `meta_description`, `keywords`, `view_count`, `purchase_count`, `average_rating`, `review_count`, `created_at`, `updated_at`) VALUES
(1, NULL, 3, 1, NULL, 'iphone 16 PROMAX', 'iphone-16-promax', 'BUSHOO', 'IPHONE', NULL, NULL, NULL, 1000.00, NULL, NULL, NULL, 'USD', 8, 5, NULL, NULL, NULL, 'active', 0, 'public', 1, 0, NULL, NULL, 0, 0, 0, 0, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'new', NULL, 'taxable', NULL, NULL, NULL, NULL, 0, 0, 0.00, 0, '2025-09-14 19:00:47', '2025-09-14 19:00:47'),
(3, NULL, 3, 501, 1, 'TOYOTA HILUX', 'toyota-hilux-', 'The pickup truck was sold with the Hilux name in most markets, but in North America, the Hilux name was retired in 1976 in favor of Truck, Pickup Truck, or Compact Truck. In North America, the popular option package, the SR5 (Sport Runabout 5-Speed), was colloquially used as a model name for the truck, even though the option package was also used on other Toyota models, like the 1972 to 1979 Corolla. In 1984, the Trekker, the wagon version of the Hilux, was renamed the 4Runner in Venezuela, Australia and North America, and the Hilux Surf in Japan. In 1992, Toyota', 'The pickup truck was sold with the Hilux name in most markets, but in North America, the Hilux name was retired in 1976 in favor of Truck, Pickup Truck, or Compact Truck. In North America, the popular option package, the SR5 (Sport Runabout 5-Speed), was colloquially used as a model name for the truck, even though the option package was also used on other Toyota models, like the 1972 to 1979 Corolla. In 1984, the Trekker, the wagon version of the Hilux, was renamed the 4Runner in Venezuela, Australia and North America, and the Hilux Surf in Japan. In 1992, Toyota', NULL, 'TOYSXHD', NULL, 4000.00, NULL, NULL, NULL, 'USD', 100, 5, NULL, NULL, NULL, 'active', 0, 'public', 1, 0, NULL, 5, 1, 0, 0, 0, '', NULL, NULL, 'standard', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'used', NULL, 'taxable', NULL, '', '', NULL, 0, 0, 0.00, 0, '2025-09-27 13:02:05', '2025-09-27 13:02:05');

-- --------------------------------------------------------

--
-- Table structure for table `product_analytics`
--

CREATE TABLE `product_analytics` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `views` int(11) NOT NULL DEFAULT 0,
  `clicks` int(11) NOT NULL DEFAULT 0,
  `conversions` int(11) NOT NULL DEFAULT 0,
  `revenue` decimal(15,2) NOT NULL DEFAULT 0.00,
  `profit_margin` decimal(5,2) DEFAULT NULL,
  `competitor_price` decimal(10,2) DEFAULT NULL,
  `search_ranking` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_approvals`
--

CREATE TABLE `product_approvals` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected','revision_requested') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_attributes`
--

CREATE TABLE `product_attributes` (
  `id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `value` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `attr_key` varchar(100) DEFAULT NULL,
  `attr_value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_audit_logs`
--

CREATE TABLE `product_audit_logs` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `reason` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_autosaves`
--

CREATE TABLE `product_autosaves` (
  `id` int(11) NOT NULL,
  `seller_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `slug` varchar(275) DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `brand` varchar(160) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `compare_price` decimal(10,2) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `currency_code` char(3) DEFAULT 'USD',
  `stock_qty` int(11) DEFAULT NULL,
  `low_stock_threshold` int(11) DEFAULT NULL,
  `track_inventory` tinyint(1) DEFAULT 1,
  `allow_backorder` tinyint(1) DEFAULT 0,
  `condition` enum('new','used','refurbished') DEFAULT 'new',
  `tags` text DEFAULT NULL,
  `weight_kg` decimal(10,3) DEFAULT NULL,
  `length_cm` decimal(10,2) DEFAULT NULL,
  `width_cm` decimal(10,2) DEFAULT NULL,
  `height_cm` decimal(10,2) DEFAULT NULL,
  `seo_title` varchar(70) DEFAULT NULL,
  `seo_description` varchar(170) DEFAULT NULL,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_bulk_operations`
--

CREATE TABLE `product_bulk_operations` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `operation_type` varchar(20) NOT NULL COMMENT 'import | export | update | delete',
  `file_path` varchar(500) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending | processing | completed | failed',
  `total_records` int(11) NOT NULL DEFAULT 0,
  `processed_records` int(11) NOT NULL DEFAULT 0,
  `error_records` int(11) NOT NULL DEFAULT 0,
  `error_log` text DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_bulk_uploads`
--

CREATE TABLE `product_bulk_uploads` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `total_rows` int(11) NOT NULL DEFAULT 0,
  `processed_rows` int(11) NOT NULL DEFAULT 0,
  `successful_rows` int(11) NOT NULL DEFAULT 0,
  `failed_rows` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `error_log` longtext DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `processing_started_at` timestamp NULL DEFAULT NULL,
  `processing_completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_certificates`
--

CREATE TABLE `product_certificates` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(11) NOT NULL,
  `certificate_type` varchar(100) DEFAULT NULL,
  `certificate_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `issuing_authority` varchar(255) DEFAULT NULL,
  `certificate_number` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_drafts`
--

CREATE TABLE `product_drafts` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `draft_name` varchar(255) DEFAULT NULL,
  `product_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`product_data`)),
  `auto_save` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `file_path` varchar(500) DEFAULT NULL,
  `sort` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `alt_text`, `is_primary`, `created_at`, `updated_at`, `file_path`, `sort`) VALUES
(1, 3, '/uploads/products/2025/09/img_1758985325_5dffc53e5a3f9764.jpg', NULL, 1, '2025-09-27 15:02:05', '2025-09-27 15:02:05', '/uploads/products/2025/09/img_1758985325_5dffc53e5a3f9764.jpg', 0),
(2, 3, '/uploads/products/2025/09/img_1758985325_884ee88bdf4ebeaa.jpg', NULL, 0, '2025-09-27 15:02:05', '2025-09-27 15:02:05', '/uploads/products/2025/09/img_1758985325_884ee88bdf4ebeaa.jpg', 0),
(3, 3, '/uploads/products/2025/09/img_1758985325_87c18aa1407ddf7a.jpg', NULL, 0, '2025-09-27 15:02:05', '2025-09-27 15:02:05', '/uploads/products/2025/09/img_1758985325_87c18aa1407ddf7a.jpg', 0),
(4, 3, '/uploads/products/2025/09/img_1758985325_ed80746d21e0308b.png', NULL, 0, '2025-09-27 15:02:05', '2025-09-27 15:02:05', '/uploads/products/2025/09/img_1758985325_ed80746d21e0308b.png', 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_inventory`
--

CREATE TABLE `product_inventory` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `reserved_quantity` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 5,
  `out_of_stock_threshold` int(11) NOT NULL DEFAULT 0,
  `backorder_limit` int(11) DEFAULT NULL,
  `reorder_point` int(11) DEFAULT NULL,
  `reorder_quantity` int(11) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_media`
--

CREATE TABLE `product_media` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `media_type` varchar(20) NOT NULL DEFAULT 'image' COMMENT 'image | video | 360_image',
  `file_path` varchar(500) DEFAULT NULL,
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `youtube_url` varchar(500) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_thumbnail` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_pricing`
--

CREATE TABLE `product_pricing` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(11) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `sale_start_date` datetime DEFAULT NULL,
  `sale_end_date` datetime DEFAULT NULL,
  `bulk_pricing` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bulk_pricing`)),
  `tier_pricing` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tier_pricing`)),
  `currency_code` char(3) NOT NULL DEFAULT 'USD',
  `tax_class` varchar(50) DEFAULT NULL,
  `margin_percentage` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_recommendations`
--

CREATE TABLE `product_recommendations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `recommended_product_id` int(11) NOT NULL,
  `type` enum('viewed_together','bought_together','similar','complementary','trending') NOT NULL,
  `score` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `algorithm` varchar(50) DEFAULT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `clicked` tinyint(1) NOT NULL DEFAULT 0,
  `purchased` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_relations`
--

CREATE TABLE `product_relations` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(11) NOT NULL,
  `related_product_id` int(11) NOT NULL,
  `relation_type` varchar(20) NOT NULL COMMENT 'cross_sell | upsell | related | bundle',
  `priority` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` between 1 and 5),
  `title` varchar(255) DEFAULT NULL,
  `review_text` text DEFAULT NULL,
  `pros` text DEFAULT NULL,
  `cons` text DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `status` enum('pending','approved','rejected','spam') NOT NULL DEFAULT 'pending',
  `moderated_by` int(11) DEFAULT NULL,
  `moderation_notes` text DEFAULT NULL,
  `helpful_votes` int(11) NOT NULL DEFAULT 0,
  `verified_purchase` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_seo`
--

CREATE TABLE `product_seo` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(11) NOT NULL,
  `meta_title` varchar(60) DEFAULT NULL,
  `meta_description` varchar(160) DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `focus_keyword` varchar(100) DEFAULT NULL,
  `canonical_url` varchar(500) DEFAULT NULL,
  `og_title` varchar(60) DEFAULT NULL,
  `og_description` varchar(160) DEFAULT NULL,
  `og_image` varchar(500) DEFAULT NULL,
  `twitter_title` varchar(60) DEFAULT NULL,
  `twitter_description` varchar(160) DEFAULT NULL,
  `schema_markup` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`schema_markup`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_shipping`
--

CREATE TABLE `product_shipping` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(11) NOT NULL,
  `weight` decimal(10,3) DEFAULT NULL,
  `length` decimal(10,2) DEFAULT NULL,
  `width` decimal(10,2) DEFAULT NULL,
  `height` decimal(10,2) DEFAULT NULL,
  `shipping_class` varchar(50) DEFAULT NULL,
  `handling_time` int(11) NOT NULL DEFAULT 1,
  `free_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `shipping_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`shipping_rules`)),
  `hs_code` varchar(20) DEFAULT NULL,
  `country_of_origin` char(2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_tag`
--

CREATE TABLE `product_tag` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock_qty` int(11) DEFAULT NULL,
  `attributes_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attributes_json`)),
  `image_path` varchar(512) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `variant_options_json` longtext DEFAULT NULL,
  `option_name` varchar(100) DEFAULT NULL,
  `option_value` varchar(100) DEFAULT NULL,
  `price_delta` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_views`
--

CREATE TABLE `product_views` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `view_duration` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `endpoint` varchar(500) NOT NULL,
  `p256dh_key` varchar(255) NOT NULL,
  `auth_token` varchar(255) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_used` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reconciliations`
--

CREATE TABLE `reconciliations` (
  `id` int(11) NOT NULL,
  `reconciliation_date` date NOT NULL,
  `gateway` varchar(50) NOT NULL,
  `total_transactions` int(11) NOT NULL DEFAULT 0,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_fees` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','in_progress','completed','failed','manual_review') NOT NULL DEFAULT 'pending',
  `discrepancies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`discrepancies`)),
  `gateway_report_path` varchar(500) DEFAULT NULL,
  `reconciled_by` int(11) DEFAULT NULL,
  `reconciled_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `redirects`
--

CREATE TABLE `redirects` (
  `id` int(11) NOT NULL,
  `from_url` varchar(500) NOT NULL,
  `to_url` varchar(500) NOT NULL,
  `redirect_type` enum('301','302','307','308') NOT NULL DEFAULT '301',
  `reason` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `hit_count` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

CREATE TABLE `refunds` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `order_item_id` int(11) DEFAULT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `refund_reason` enum('customer_request','defective_product','wrong_item','damaged_shipping','cancelled_order','dispute_resolution','admin_decision') NOT NULL,
  `refund_method` enum('original_payment','store_credit','bank_transfer','manual') NOT NULL DEFAULT 'original_payment',
  `status` enum('pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `external_refund_id` varchar(255) DEFAULT NULL,
  `processor_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`processor_response`)),
  `admin_notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_jobs`
--

CREATE TABLE `report_jobs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `report_type` enum('sales','users','inventory','financial','marketing','custom') NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `format` enum('csv','excel','pdf','json') NOT NULL DEFAULT 'csv',
  `schedule` varchar(100) DEFAULT NULL,
  `email_recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`email_recipients`)),
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('queued','running','completed','failed') NOT NULL DEFAULT 'queued',
  `progress` int(11) NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `order_item_id` int(11) DEFAULT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` between 1 and 5),
  `title` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `pros` text DEFAULT NULL,
  `cons` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','hidden') NOT NULL DEFAULT 'pending',
  `helpful_count` int(11) NOT NULL DEFAULT 0,
  `unhelpful_count` int(11) NOT NULL DEFAULT 0,
  `verified_purchase` tinyint(1) NOT NULL DEFAULT 0,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `admin_response` text DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_helpfulness`
--

CREATE TABLE `review_helpfulness` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_helpful` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `search_queries`
--

CREATE TABLE `search_queries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `query` varchar(500) NOT NULL,
  `results_count` int(11) NOT NULL DEFAULT 0,
  `clicked_product_id` int(11) DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `filters_used` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters_used`)),
  `sort_order` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event_type` enum('login_success','login_failed','login_blocked','logout','password_change','email_change','two_fa_enabled','two_fa_disabled','account_locked','account_unlocked','suspicious_activity','access_denied','data_breach','privilege_escalation') NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `resource_type` varchar(50) DEFAULT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `risk_score` tinyint(3) UNSIGNED DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_analytics`
--

CREATE TABLE `seller_analytics` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `total_views` int(11) NOT NULL DEFAULT 0,
  `total_sales` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `total_revenue` decimal(15,2) NOT NULL DEFAULT 0.00,
  `conversion_rate` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `average_order_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `return_rate` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `customer_satisfaction` decimal(3,2) DEFAULT NULL,
  `traffic_sources` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`traffic_sources`)),
  `top_products` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`top_products`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_bank_details`
--

CREATE TABLE `seller_bank_details` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `account_type` enum('checking','savings','business') NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_holder_name` varchar(255) NOT NULL,
  `account_number_encrypted` varchar(500) NOT NULL,
  `routing_number_encrypted` varchar(500) NOT NULL,
  `swift_code` varchar(20) DEFAULT NULL,
  `iban` varchar(50) DEFAULT NULL,
  `bank_address` text DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_campaigns`
--

CREATE TABLE `seller_campaigns` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('email','social','display','search','affiliate','influencer') NOT NULL,
  `status` enum('draft','active','paused','completed','cancelled') NOT NULL DEFAULT 'draft',
  `budget` decimal(15,2) DEFAULT NULL,
  `spent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `target_audience` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_audience`)),
  `objectives` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`objectives`)),
  `start_date` timestamp NOT NULL,
  `end_date` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_campaign_assets`
--

CREATE TABLE `seller_campaign_assets` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `asset_type` enum('image','video','text','html','banner') NOT NULL,
  `name` varchar(255) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `dimensions` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','pending_approval') NOT NULL DEFAULT 'pending_approval',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_campaign_stats`
--

CREATE TABLE `seller_campaign_stats` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `impressions` int(11) NOT NULL DEFAULT 0,
  `clicks` int(11) NOT NULL DEFAULT 0,
  `conversions` int(11) NOT NULL DEFAULT 0,
  `spend` decimal(15,2) NOT NULL DEFAULT 0.00,
  `revenue` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_chat_messages`
--

CREATE TABLE `seller_chat_messages` (
  `id` int(11) NOT NULL,
  `stream_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','emoji','system','product_link') NOT NULL DEFAULT 'text',
  `is_moderator` tinyint(1) NOT NULL DEFAULT 0,
  `is_seller` tinyint(1) NOT NULL DEFAULT 0,
  `is_highlighted` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_commissions`
--

CREATE TABLE `seller_commissions` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `sale_amount` decimal(10,2) NOT NULL,
  `commission_rate` decimal(5,4) NOT NULL,
  `commission_amount` decimal(10,2) NOT NULL,
  `platform_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','paid','disputed') NOT NULL DEFAULT 'pending',
  `approved_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `payout_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_coupons`
--

CREATE TABLE `seller_coupons` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `type` enum('percentage','fixed_amount','free_shipping','buy_x_get_y') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `minimum_amount` decimal(10,2) DEFAULT NULL,
  `maximum_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_limit_per_customer` int(11) DEFAULT 1,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive','expired') NOT NULL DEFAULT 'active',
  `start_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_date` timestamp NULL DEFAULT NULL,
  `applicable_products` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_products`)),
  `applicable_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_categories`)),
  `excluded_products` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`excluded_products`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_coupon_redemptions`
--

CREATE TABLE `seller_coupon_redemptions` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_coupon_rules`
--

CREATE TABLE `seller_coupon_rules` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `rule_type` enum('customer_group','first_time_buyer','geographic','time_based','purchase_history') NOT NULL,
  `rule_condition` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`rule_condition`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_disputes`
--

CREATE TABLE `seller_disputes` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `dispute_number` varchar(50) NOT NULL,
  `type` enum('chargeback','refund_request','product_issue','service_issue','payment_issue') NOT NULL,
  `status` enum('open','under_review','awaiting_response','resolved','escalated','closed') NOT NULL DEFAULT 'open',
  `amount_disputed` decimal(10,2) NOT NULL,
  `customer_claim` text NOT NULL,
  `seller_response` text DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `deadline` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_dispute_evidence`
--

CREATE TABLE `seller_dispute_evidence` (
  `id` int(11) NOT NULL,
  `dispute_id` int(11) NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `evidence_type` enum('document','image','email','communication','tracking','receipt') NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `description` text NOT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_dispute_messages`
--

CREATE TABLE `seller_dispute_messages` (
  `id` int(11) NOT NULL,
  `dispute_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('customer','seller','admin','system') NOT NULL,
  `message` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_documents`
--

CREATE TABLE `seller_documents` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `document_type` enum('business_license','tax_id','identity','address_proof','bank_statement','tax_form','insurance','certification') NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `document_number` varchar(100) DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected','expired') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_inventory`
--

CREATE TABLE `seller_inventory` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `quantity_available` int(11) NOT NULL DEFAULT 0,
  `quantity_reserved` int(11) NOT NULL DEFAULT 0,
  `quantity_damaged` int(11) NOT NULL DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_kpis`
--

CREATE TABLE `seller_kpis` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `total_sales` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `total_customers` int(11) NOT NULL DEFAULT 0,
  `conversion_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `avg_order_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `return_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_kyc`
--

CREATE TABLE `seller_kyc` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `verification_type` enum('individual','business','corporation') NOT NULL,
  `business_registration_number` varchar(100) DEFAULT NULL,
  `tax_identification_number` varchar(100) DEFAULT NULL,
  `identity_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`identity_documents`)),
  `business_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`business_documents`)),
  `address_verification` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`address_verification`)),
  `bank_verification` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bank_verification`)),
  `verification_status` enum('pending','in_review','approved','rejected','requires_resubmission') NOT NULL DEFAULT 'pending',
  `verification_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_live_streams`
--

CREATE TABLE `seller_live_streams` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `stream_key` varchar(255) NOT NULL,
  `stream_url` varchar(500) DEFAULT NULL,
  `status` enum('scheduled','live','ended','cancelled') NOT NULL DEFAULT 'scheduled',
  `scheduled_start` timestamp NOT NULL,
  `actual_start` timestamp NULL DEFAULT NULL,
  `actual_end` timestamp NULL DEFAULT NULL,
  `max_viewers` int(11) DEFAULT 0,
  `total_views` int(11) DEFAULT 0,
  `chat_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `recording_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `recording_url` varchar(500) DEFAULT NULL,
  `products_featured` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`products_featured`)),
  `stream_analytics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`stream_analytics`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_messages`
--

CREATE TABLE `seller_messages` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `conversation_id` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `sender_type` enum('seller','customer','system') NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `message_type` enum('text','image','file','order_update','system') NOT NULL DEFAULT 'text',
  `content` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `order_id` int(11) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_message_templates`
--

CREATE TABLE `seller_message_templates` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` enum('order_confirmation','shipping_notification','delivery_confirmation','return_approved','general_inquiry','support') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_notifications`
--

CREATE TABLE `seller_notifications` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `type` enum('order','product','payout','dispute','system','marketing') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `action_url` varchar(500) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_orders`
--

CREATE TABLE `seller_orders` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `tracking_number` varchar(100) DEFAULT NULL,
  `shipping_carrier` varchar(100) DEFAULT NULL,
  `shipping_cost` decimal(10,2) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `commission_amount` decimal(10,2) DEFAULT NULL,
  `payout_status` enum('pending','processing','paid','on_hold') NOT NULL DEFAULT 'pending',
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_order_items`
--

CREATE TABLE `seller_order_items` (
  `id` int(11) NOT NULL,
  `seller_order_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `commission_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','fulfilled','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_payouts`
--

CREATE TABLE `seller_payouts` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `request_amount` decimal(10,2) NOT NULL,
  `processing_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL,
  `payout_method` enum('bank_transfer','paypal','stripe','wise','manual') NOT NULL DEFAULT 'bank_transfer',
  `payout_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payout_details`)),
  `status` enum('requested','pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'requested',
  `reference_number` varchar(100) DEFAULT NULL,
  `external_transaction_id` varchar(255) DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_payout_requests`
--

CREATE TABLE `seller_payout_requests` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `fee` decimal(15,2) DEFAULT 0.00,
  `net_amount` decimal(15,2) NOT NULL,
  `method` enum('bank_transfer','paypal','crypto','check') NOT NULL DEFAULT 'bank_transfer',
  `account_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`account_details`)),
  `status` enum('pending','processing','approved','paid','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_performance_metrics`
--

CREATE TABLE `seller_performance_metrics` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `response_time_avg` decimal(8,2) DEFAULT NULL,
  `customer_satisfaction` decimal(3,2) DEFAULT NULL,
  `order_fulfillment_rate` decimal(5,2) DEFAULT NULL,
  `return_rate` decimal(5,2) DEFAULT NULL,
  `dispute_rate` decimal(5,2) DEFAULT NULL,
  `on_time_shipping_rate` decimal(5,2) DEFAULT NULL,
  `product_quality_score` decimal(3,2) DEFAULT NULL,
  `communication_score` decimal(3,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_products`
--

CREATE TABLE `seller_products` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `profit_margin` decimal(5,2) DEFAULT NULL,
  `min_stock_level` int(11) DEFAULT 0,
  `max_stock_level` int(11) DEFAULT NULL,
  `reorder_point` int(11) DEFAULT NULL,
  `lead_time_days` int(11) DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected','under_review') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_product_media`
--

CREATE TABLE `seller_product_media` (
  `id` int(11) NOT NULL,
  `seller_product_id` int(11) NOT NULL,
  `media_type` enum('image','video','document') NOT NULL DEFAULT 'image',
  `file_path` varchar(500) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_product_variants`
--

CREATE TABLE `seller_product_variants` (
  `id` int(11) NOT NULL,
  `seller_product_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `price_adjustment` decimal(10,2) DEFAULT 0.00,
  `cost_adjustment` decimal(10,2) DEFAULT 0.00,
  `stock_quantity` int(11) DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_profiles`
--

CREATE TABLE `seller_profiles` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `store_name` varchar(255) NOT NULL,
  `store_description` longtext DEFAULT NULL,
  `store_logo` varchar(500) DEFAULT NULL,
  `store_banner` varchar(500) DEFAULT NULL,
  `store_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`store_address`)),
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_links`)),
  `business_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`business_hours`)),
  `shipping_policy` longtext DEFAULT NULL,
  `return_policy` longtext DEFAULT NULL,
  `store_policies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`store_policies`)),
  `notification_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_settings`)),
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_reports_jobs`
--

CREATE TABLE `seller_reports_jobs` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`parameters`)),
  `status` enum('queued','processing','completed','failed','cancelled') NOT NULL DEFAULT 'queued',
  `progress` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_rmas`
--

CREATE TABLE `seller_rmas` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rma_number` varchar(50) NOT NULL,
  `reason` enum('defective','wrong_item','damaged','not_as_described','change_of_mind','warranty') NOT NULL,
  `status` enum('pending','approved','rejected','received','refunded','completed') NOT NULL DEFAULT 'pending',
  `return_value` decimal(10,2) NOT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `return_label_generated` tinyint(1) DEFAULT 0,
  `return_tracking` varchar(100) DEFAULT NULL,
  `received_condition` enum('good','damaged','unopened','used') DEFAULT NULL,
  `resolution` enum('full_refund','partial_refund','replacement','repair','rejected') DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `seller_notes` text DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_rma_notes`
--

CREATE TABLE `seller_rma_notes` (
  `id` int(11) NOT NULL,
  `rma_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('customer','seller','admin') NOT NULL,
  `note` text NOT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_sales_reports`
--

CREATE TABLE `seller_sales_reports` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `report_type` enum('daily','weekly','monthly','quarterly','yearly','custom') NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `total_sales` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `total_customers` int(11) NOT NULL DEFAULT 0,
  `avg_order_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `top_products` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`top_products`)),
  `geographic_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`geographic_breakdown`)),
  `payment_methods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_methods`)),
  `report_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`report_data`)),
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_shipping_rates`
--

CREATE TABLE `seller_shipping_rates` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `shipping_zone_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `method` enum('flat_rate','weight_based','price_based','quantity_based','free') NOT NULL,
  `rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditions`)),
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `max_weight` decimal(8,2) DEFAULT NULL,
  `estimated_delivery_days` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_shipping_zones`
--

CREATE TABLE `seller_shipping_zones` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `countries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`countries`)),
  `states` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`states`)),
  `postal_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`postal_codes`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_staff`
--

CREATE TABLE `seller_staff` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('manager','editor','viewer','support') NOT NULL DEFAULT 'viewer',
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `invited_by` int(11) NOT NULL,
  `invitation_token` varchar(255) DEFAULT NULL,
  `invitation_expires_at` timestamp NULL DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','active','suspended','removed') NOT NULL DEFAULT 'pending',
  `last_active_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_stock_logs`
--

CREATE TABLE `seller_stock_logs` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `movement_type` enum('in','out','adjustment','reserved','released','damaged') NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `reference_type` enum('order','return','adjustment','damage','restock') DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `performed_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_stream_products`
--

CREATE TABLE `seller_stream_products` (
  `id` int(11) NOT NULL,
  `stream_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `featured_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `featured_duration` int(11) DEFAULT NULL,
  `special_price` decimal(10,2) DEFAULT NULL,
  `quantity_available` int(11) DEFAULT NULL,
  `quantity_sold` int(11) NOT NULL DEFAULT 0,
  `clicks` int(11) NOT NULL DEFAULT 0,
  `conversions` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seo_meta`
--

CREATE TABLE `seo_meta` (
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` varchar(300) DEFAULT NULL,
  `canonical_url` varchar(500) DEFAULT NULL,
  `og_title` varchar(200) DEFAULT NULL,
  `og_description` varchar(300) DEFAULT NULL,
  `og_image` varchar(500) DEFAULT NULL,
  `robots` varchar(50) DEFAULT 'index,follow',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seo_metadata`
--

CREATE TABLE `seo_metadata` (
  `id` int(11) NOT NULL,
  `entity_type` enum('product','category','page','vendor') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `canonical_url` varchar(500) DEFAULT NULL,
  `og_title` varchar(255) DEFAULT NULL,
  `og_description` text DEFAULT NULL,
  `og_image` varchar(500) DEFAULT NULL,
  `twitter_title` varchar(255) DEFAULT NULL,
  `twitter_description` text DEFAULT NULL,
  `twitter_image` varchar(500) DEFAULT NULL,
  `schema_markup` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`schema_markup`)),
  `robots_directive` varchar(255) DEFAULT 'index,follow',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_group` varchar(100) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `setting_type` enum('string','integer','decimal','boolean','json','text','password') NOT NULL DEFAULT 'string',
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `validation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_rules`)),
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_group`, `setting_key`, `setting_value`, `setting_type`, `is_public`, `is_encrypted`, `description`, `validation_rules`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'general', 'site_name', 'E-Commerce Platform', 'string', 1, 0, 'Site name displayed in header and emails', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(2, 'general', 'site_description', 'Professional E-Commerce Platform', 'string', 1, 0, 'Site description for SEO', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(3, 'general', 'admin_email', 'admin@example.com', 'string', 0, 0, 'Administrator email address', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(4, 'general', 'timezone', 'UTC', 'string', 1, 0, 'Default timezone', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(5, 'general', 'currency', 'USD', 'string', 1, 0, 'Default currency', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(6, 'general', 'maintenance_mode', 'false', 'boolean', 0, 0, 'Enable maintenance mode', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(7, 'email', 'smtp_host', 'localhost', 'string', 0, 0, 'SMTP server hostname', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(8, 'email', 'smtp_port', '587', 'integer', 0, 0, 'SMTP server port', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(9, 'email', 'smtp_username', '', 'string', 0, 0, 'SMTP username', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(10, 'email', 'smtp_password', '', 'password', 0, 0, 'SMTP password', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(11, 'email', 'smtp_encryption', 'tls', 'string', 0, 0, 'SMTP encryption method', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(12, 'payments', 'default_gateway', 'stripe', 'string', 0, 0, 'Default payment gateway', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(13, 'payments', 'stripe_publishable_key', '', 'string', 0, 0, 'Stripe publishable key', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(14, 'payments', 'stripe_secret_key', '', 'password', 0, 0, 'Stripe secret key', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(15, 'payments', 'paypal_client_id', '', 'string', 0, 0, 'PayPal client ID', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(16, 'payments', 'paypal_client_secret', '', 'password', 0, 0, 'PayPal client secret', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(17, 'security', 'session_timeout', '3600', 'integer', 0, 0, 'Session timeout in seconds', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(18, 'security', 'max_login_attempts', '5', 'integer', 0, 0, 'Maximum login attempts before lockout', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(19, 'security', 'lockout_duration', '900', 'integer', 0, 0, 'Account lockout duration in seconds', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(20, 'security', 'require_2fa', 'false', 'boolean', 0, 0, 'Require two-factor authentication', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(21, 'features', 'enable_reviews', 'true', 'boolean', 1, 0, 'Enable product reviews', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(22, 'features', 'enable_wishlist', 'true', 'boolean', 1, 0, 'Enable wishlist functionality', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(23, 'features', 'enable_loyalty', 'true', 'boolean', 1, 0, 'Enable loyalty program', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(24, 'features', 'enable_live_streaming', 'true', 'boolean', 1, 0, 'Enable live streaming features', NULL, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26');

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `carrier` varchar(100) DEFAULT NULL,
  `shipping_method` varchar(100) DEFAULT NULL,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_items`
--

CREATE TABLE `shipment_items` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipping_carriers`
--

CREATE TABLE `shipping_carriers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `tracking_url` varchar(255) DEFAULT NULL COMMENT 'URL template for tracking, e.g., https://www.fedex.com/apps/fedextrack/?tracknumbers={tracking_number}',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipping_carriers`
--

INSERT INTO `shipping_carriers` (`id`, `name`, `tracking_url`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'FedEx', 'https://www.fedex.com/apps/fedextrack/?tracknumbers=', 1, '2025-09-27 10:19:25', '2025-09-27 10:19:25'),
(2, 'UPS', 'https://www.ups.com/track?loc=en_US&tracknum=', 1, '2025-09-27 10:19:25', '2025-09-27 10:19:25'),
(3, 'USPS', 'https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1=', 1, '2025-09-27 10:19:25', '2025-09-27 10:19:25'),
(4, 'DHL', 'https://www.dhl.com/en/express/tracking.html?AWB=', 1, '2025-09-27 10:19:25', '2025-09-27 10:19:25');

-- --------------------------------------------------------

--
-- Table structure for table `stream_events`
--

CREATE TABLE `stream_events` (
  `id` int(11) NOT NULL,
  `stream_id` int(11) NOT NULL,
  `event_type` enum('started','ended','viewer_joined','viewer_left','product_featured','chat_message','error') NOT NULL,
  `event_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_data`)),
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stream_products`
--

CREATE TABLE `stream_products` (
  `id` int(11) NOT NULL,
  `stream_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `featured_at` timestamp NULL DEFAULT NULL,
  `unfeatured_at` timestamp NULL DEFAULT NULL,
  `special_price` decimal(10,2) DEFAULT NULL,
  `quantity_sold` int(11) NOT NULL DEFAULT 0,
  `revenue_generated` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_currently_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stream_viewers`
--

CREATE TABLE `stream_viewers` (
  `id` int(11) NOT NULL,
  `stream_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `left_at` timestamp NULL DEFAULT NULL,
  `watch_duration` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `subscription_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `channel` enum('email','sms','push','in_app') NOT NULL,
  `opt_in_status` tinyint(1) NOT NULL DEFAULT 1,
  `subscription_type` enum('marketing','transactional','notifications','all') NOT NULL DEFAULT 'all',
  `source` varchar(100) DEFAULT NULL,
  `opted_in_at` timestamp NULL DEFAULT NULL,
  `opted_out_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`subscription_id`, `user_id`, `channel`, `opt_in_status`, `subscription_type`, `source`, `opted_in_at`, `opted_out_at`, `updated_at`, `created_at`) VALUES
(1, 4, 'email', 1, 'all', NULL, '2025-09-11 15:56:21', NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26');

-- --------------------------------------------------------

--
-- Table structure for table `support_messages`
--

CREATE TABLE `support_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_email` varchar(255) DEFAULT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `category` enum('general','technical','billing','shipping','returns','product','account','complaint','suggestion') NOT NULL DEFAULT 'general',
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `status` enum('open','in_progress','pending_customer','pending_vendor','escalated','resolved','closed') NOT NULL DEFAULT 'open',
  `description` text NOT NULL,
  `resolution` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `escalated_to` int(11) DEFAULT NULL,
  `related_order_id` int(11) DEFAULT NULL,
  `related_product_id` int(11) DEFAULT NULL,
  `satisfaction_rating` tinyint(1) DEFAULT NULL,
  `satisfaction_feedback` text DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `first_response_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_ticket_replies`
--

CREATE TABLE `support_ticket_replies` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_email` varchar(255) DEFAULT NULL,
  `reply_type` enum('customer','admin','system','auto') NOT NULL DEFAULT 'customer',
  `message` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `is_solution` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_alerts`
--

CREATE TABLE `system_alerts` (
  `id` int(11) NOT NULL,
  `alert_type` enum('security','performance','business','technical','compliance') NOT NULL,
  `severity` enum('info','warning','error','critical') NOT NULL DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `alert_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`alert_data`)),
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_events`
--

CREATE TABLE `system_events` (
  `id` int(11) NOT NULL,
  `event_type` enum('performance','security','backup','maintenance','error','warning','info') NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `component` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `event_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_data`)),
  `metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metrics`)),
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `setting_type` enum('string','integer','decimal','boolean','json','text') NOT NULL DEFAULT 'string',
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tax_rules`
--

CREATE TABLE `tax_rules` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `tax_type` enum('vat','gst','sales_tax','other') NOT NULL,
  `rate` decimal(5,4) NOT NULL,
  `country` varchar(2) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`postal_codes`)),
  `product_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`product_categories`)),
  `is_inclusive` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `templates`
--

CREATE TABLE `templates` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `template_type` enum('email','sms','notification','page','component') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `content` longtext NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('payment','refund','partial_refund','chargeback','fee') NOT NULL,
  `status` enum('pending','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `payment_method` varchar(50) DEFAULT NULL,
  `gateway` varchar(50) DEFAULT NULL,
  `gateway_transaction_id` varchar(255) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `gateway_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_response`)),
  `fees` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `unsubscribe_links`
--

CREATE TABLE `unsubscribe_links` (
  `id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `channel` enum('email','sms','push','in_app') NOT NULL,
  `subscription_type` enum('marketing','transactional','notifications','all') NOT NULL DEFAULT 'marketing',
  `message_id` int(11) DEFAULT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `pass_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('customer','vendor','admin') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive','pending','suspended') NOT NULL DEFAULT 'pending',
  `verified_at` timestamp NULL DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)),
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified_at` timestamp NULL DEFAULT NULL, -- Email verification timestamp for user account security
  `two_fa_secret` varchar(255) DEFAULT NULL,
  `login_email_alerts` tinyint(1) NOT NULL DEFAULT 1,
  `login_sms_alerts` tinyint(1) NOT NULL DEFAULT 0,
  `new_device_alerts` tinyint(1) NOT NULL DEFAULT 1,
  `suspicious_activity_alerts` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `pass_hash`, `first_name`, `last_name`, `phone`, `role`, `status`, `verified_at`, `avatar`, `bio`, `preferences`, `two_factor_enabled`, `email_verified_at`, `two_fa_secret`, `login_email_alerts`, `login_sms_alerts`, `new_device_alerts`, `suspicious_activity_alerts`, `last_login_at`, `last_login_ip`, `created_at`, `updated_at`, `last_login`) VALUES
(4, 'Joseph', 'ellyj164@gmail.com', '$argon2id$v=19$m=65536,t=4,p=3$Yjg2Y2dNN0wzdFZZOUEuUA$XCK6vnbTtHx4S8EJvZP0qHf3xXNl0UQKNxa9fIcTHWs', 'xxxx', 'Mark bb', '+250 789 721 783', 'admin', 'active', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, 0, 1, 1, NULL, NULL, '2025-09-11 15:56:21', '2025-09-20 22:28:43', NULL),
(5, 'niyo', 'niyogushimwaj967@gmail.com', '$argon2id$v=19$m=65536,t=4,p=3$RW9vWGRWVHNRY0xrTVpKRg$4NdBl5tNh3vcmVSxIt5ROsXzYLH8z1YFnd8HLkxxZAY', 'NIYogu', 'Joseph', '+250 785 241 817', 'customer', 'active', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, 0, 1, 1, NULL, NULL, '2025-09-20 20:23:57', '2025-09-20 22:32:20', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_activities`
--

CREATE TABLE `user_activities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `activity_type` enum('view','add_to_cart','purchase') NOT NULL DEFAULT 'view',
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_audit_logs`
--

CREATE TABLE `user_audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_documents`
--

CREATE TABLE `user_documents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_type` enum('identity','address','business','tax','other') NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `verification_status` enum('pending','approved','rejected','expired') NOT NULL DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_follows`
--

CREATE TABLE `user_follows` (
  `id` int(11) NOT NULL,
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `type` enum('user','vendor') NOT NULL DEFAULT 'user',
  `notifications_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_logins`
--

CREATE TABLE `user_logins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_type` enum('password','oauth','two_factor','sso') NOT NULL DEFAULT 'password',
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `device_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`device_info`)),
  `success` tinyint(1) NOT NULL DEFAULT 1,
  `failure_reason` varchar(255) DEFAULT NULL,
  `session_duration` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `profile_image` varchar(500) DEFAULT NULL,
  `cover_image` varchar(500) DEFAULT NULL,
  `biography` text DEFAULT NULL,
  `website` varchar(500) DEFAULT NULL,
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_links`)),
  `privacy_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`privacy_settings`)),
  `notification_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_preferences`)),
  `timezone` varchar(50) DEFAULT 'UTC',
  `language` varchar(5) DEFAULT 'en',
  `currency` varchar(3) DEFAULT 'USD',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`permissions`)),
  `is_system_role` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_role_assignments`
--

CREATE TABLE `user_role_assignments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `csrf_token` varchar(64) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_token`, `ip_address`, `user_agent`, `created_at`, `expires_at`, `is_active`, `csrf_token`, `updated_at`) VALUES
(1, 1, 'e1654d2204296d2e9e2e580616a9744bdde20c55f326981c90dd58e01e1e2d0dacc37f6992964a9a4ae3f1f1f0fa3653656d90cc1e5ee1448442b4522e62a81b', '197.157.155.163', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-11 17:50:31', '2025-09-11 18:50:31', 1, '09331009433d0babe15f2a59f0705954d359e6951cc10c6ea8ab261b6ea72854', '2025-09-11 21:50:31'),
(2, 4, 'fe8d72ba1cd07e56ac312d78f08701d3257c0af5481f2f6bd5fdd52367a685926b027db24ebbbca2d681e48e7af61e64b715419eeb098986a55291dd7293cf3d', '197.157.145.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-14 20:45:42', '2025-09-14 21:45:42', 1, '6b923c8a66d1674bbcd17ebbfe977793eef8d2670de119389104dcef2051357e', '2025-09-15 00:45:42'),
(3, 4, 'ff880e6fb3c6ab3bababc31e633d5be0324ffe4dc0688e39dbc01bbaf2042d1c18f54d56e5c6aa58708de36db0ad462f1476754091065d0ad269c27c1c14be1b', '105.178.32.82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 15:09:28', '2025-09-15 16:09:28', 1, 'b3233fde0d2673231819456355f082f470cbbec8a69eed9bb4a0969a6f0a5ad7', '2025-09-15 19:09:28'),
(4, 4, '5b25d47ee859b5b6f6c515e7f0acf4e56e285a3f59a6451c8a5a166d5f9e5fea98fefd67eafdabd08744908fe239959353006e8e120e05367381a321c6e116c2', '197.157.155.6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 17:22:52', '2025-09-15 18:22:52', 1, 'ce3b39495005f73048a5cf91e3c8deaf684defae8fcb70517dc774a680847239', '2025-09-15 21:22:52'),
(5, 4, '0d1dcfbfbd356513bb531a18627a96114d1d5321d9ffae9d79d08332a8004ced3fa0dd42b86316050fc3c0da471ca54ca2cf5ac03a2eadc47d22b96e0cc54e0e', '197.157.155.6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 17:29:35', '2025-09-15 18:29:35', 1, '474d6a4b6a916600337f2f9f0aa39697f668b22105ce44cc6c84d8593649c6c1', '2025-09-15 21:29:35'),
(6, 4, '9ecce897b923b4f8461cbfc308be28634ed4d7a8c6b520e5fe37658b59b29986b6c2acca0233957a2d1c1ace4795526c2d51aedea55b06cbd7ef70bb1a1f6ff8', '197.157.155.6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 18:00:11', '2025-09-15 19:00:11', 1, '9a684e5a7360e8cfc8fa9eba31b8107f22996e38fdd881c7aeb8478fe14baecb', '2025-09-15 22:00:11'),
(7, 4, '6a209fb3fa928c9818f50d945d86e1b2eef1c0bb6387f44c00cb0285f982ffed747f545da5e5e91b21c589374a5dec86a3d9161487ad7884de7e9dc42be3a9df', '197.157.155.6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 18:08:43', '2025-09-15 19:08:43', 1, '5aae8c8ec3ef68809ed03865471b93ee6e7ded6b35506ec03c60eb77326956e4', '2025-09-15 22:08:43'),
(8, 4, '31eff7e649fbef5b5009df6c69c11a883a1f8aaaa74cb7be40d7ad2d8d177f0decc7c499b3781f717869a3de951f968b57bbc1f5e413c991210a1c83db28945d', '197.157.155.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 19:11:16', '2025-09-15 20:11:16', 1, '7e6e0c0e65090089112602c2405d0a5a523ab011b9b1c4ba46e81f8fc57dadb1', '2025-09-15 23:11:16'),
(9, 4, '2a41df3d0661b91a1a3c3f4089f1f1e602e9a1afa129dd904dfe747044f75d5206e35c7dac8045a07be13b65167f64225341973552f69c68bd093c2ec1e35d98', '197.157.155.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 19:14:55', '2025-09-15 20:14:55', 1, '99d79fcbccc6cc24421caa733efeb49cbff537bb6d1d6b1ec1f2e56ca47e1d05', '2025-09-15 23:14:55'),
(10, 4, 'ad96b5bd247d2f2a75a101e99f1ba83f02ee6790a857e72e9bc1ba4147b222983125f860f1bb4a573851e2d96ae40368ed3066f67dcf9988986592f1473b3767', '197.157.155.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 20:19:58', '2025-09-15 21:19:58', 1, 'bf6b85fd9531318f8aab8e140b249491786b3de80ca7cf9d0c3c23ea0acc2e8d', '2025-09-16 00:19:58'),
(11, 4, '9bbd26db276e02923b827a2e4d94460319d17e8a0d95dba916caad1ab9659956a456edd147faeffe2d0ffb28d22edc27d4e0918c145512e3c766e247c6ac7425', '197.157.155.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 21:21:19', '2025-09-15 22:21:19', 1, 'd1b5078286463a664178429896c413e9a762421e49970e2c69c3e808631955a0', '2025-09-16 01:21:19'),
(12, 4, 'e71ea2be86bd51b48d108666fe1d058379f4010c8230dd88a421040a18e70fa36f68a43518d39464f88a5822ae3a63260753ce130db87cad50fc682f1032355d', '197.157.155.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 22:58:23', '2025-09-15 23:58:23', 1, '3184ab76216f6b2b23019d7748fa9de084abaf302c91cb18447f45b3458f7fd4', '2025-09-16 02:58:23'),
(13, 4, 'ad51fa8ca9f6dbfd513aceb12e16b84f4f82fc424383d7e40e9e1796cfed94356a2dc84eb074596fc386fb89658135105f213a748b0e6b46f8844c7274713b66', '105.178.32.56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 05:50:29', '2025-09-16 06:50:29', 1, '8a0c4fbb69399122a88237ef943c1a71287e9123d409255e5e1b8ec0b92c1be5', '2025-09-16 09:50:29'),
(14, 4, 'ab820e967e2a60803ae8ad708caa94ddaf76397c8cdb2e2c859b9c0e4750ccf07f5e6011674a823fe71ffe7f29b7f903acbd13ce6af9d686538eff0136b37b57', '105.178.32.56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 07:35:24', '2025-09-16 08:35:24', 1, '00c8ab0dc8f51bdae66fbf4287379f0c696e3850ebdf2b474b82d9a0cf74ebda', '2025-09-16 11:35:24'),
(15, 4, '43566b07beb8b96eec9c5093b266f8e19e5036cca2a3188e4e969ededbae37e04ec7395b77605ab954418b2683c95126fe0c44d9694b22cfa0dec634db29745e', '105.178.104.56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 08:10:19', '2025-09-16 09:10:19', 1, '07060d5c4c96784bfdcca697fb0faf2a9a61d4306f5aafce65ba84bd14071fb4', '2025-09-16 12:10:19'),
(16, 4, '17a70883d6dc922f23b79cf26e86398643777262ca39e562414b838c44dc6b59f9645c6798d60a6a6d9c97d5bb8aaad5771c9bd42d817af3d621186fea3b5a09', '105.178.104.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 11:25:14', '2025-09-16 12:25:14', 1, '5d8a570eaa0df9911f432762c89a302623ef14bf76b78dbf293330f2b2dc992d', '2025-09-16 15:25:14'),
(17, 4, '5f06dba291252182a8b2f0a42d27e664370dcbf24b6acad465640eec09158de832e4e0e24a493bac638a1fb4074e521506340b26d15ddd67dd59202f61f78fe4', '105.178.32.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 16:10:15', '2025-09-16 17:10:15', 1, '643bfd2f1d062cb8ddc92397773c92c179773243fc704f9d6f1686a6ed260814', '2025-09-16 20:10:15'),
(18, 4, 'd48785759ea8b4e32517219ae0e188a3392d11b7b691de768621d869fb45173486bd0c35aac6fa275c5dbe7705fa03e9059f92c66cabf920ff077f40c707a8c8', '105.178.104.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 16:51:50', '2025-09-16 17:51:50', 1, 'e008343dc0e36d559227eb129dd83f0d31b5795065360d0c8996e0b6ae0084b3', '2025-09-16 20:51:50'),
(19, 4, '8245a00989a6f128a028c5a0d28e56e1028ec4420468ef05ffd0fd3a2ccefe6744f93188b8051d4cb49a0085635d6b2532f20468313343d638278281bfc0afad', '197.157.135.133', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-20 22:20:08', '2025-09-20 23:20:08', 1, '647ccf73e5fd4d974e8f051eeef2ed60e7214a523ebe8e62949dc51b631ba9c0', '2025-09-21 02:20:08'),
(20, 4, '592af2f528d10a25a9e3f81d5d50208360ca6e8858f21c97dbbdb033fe501ddc6acc37150bce90284ac0749a35859ccd2978a0214f1a9237b56326b16335f9db', '197.157.135.133', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-20 22:21:19', '2025-09-20 23:21:19', 1, '390744a4592115ab2ddd1c07ceb1de1ca3fd481a6f70a56ab39de4801df1e51b', '2025-09-21 02:21:19'),
(21, 5, '6704b4de660ddc6e8d9164f3766f4fb51fa2e69d426914839dce67aa088fdf31040980c2da4814c55e0f7d394452d8cf65344ddcbd811f75ca1dc3e5a165847f', '197.157.135.133', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-20 22:27:04', '2025-09-20 23:27:04', 1, 'b378b0d439c6140bcfe6e651ae07d6238c17825ceaeb2c8c07b7b144d21f85b3', '2025-09-21 02:27:04'),
(22, 4, 'f5e9dfdcd300c36413a2b387bc9b43f7c61bdab1a18a57cad62a41a4b1bb9e5e80902e3241ddb9b3baa402172811d0cdb074cf6e90021dae3bed8021f7cc2d45', '197.157.135.133', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-20 22:31:22', '2025-09-20 23:31:22', 1, 'e35af7b71dc7111e95584e52ddc69eead7715ee96484874670ec1f9c8c7e0696', '2025-09-21 02:31:22'),
(23, 4, '17f2c2a513cb00ea106ded0edfdc8465cf5ce7e94ee7a904eca94cb214fd4cfbe0e1ccd24a246831a472ec1bec15e64ec6586fb864076bf5d199da7b254e8c3f', '41.186.132.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 06:57:04', '2025-09-21 07:57:04', 1, '6d761f39b7a07245e02f186554c44ddaf4b1ba0620d0a5f2900adf5c7abb030b', '2025-09-21 10:57:04'),
(24, 4, '4fb2f4a6be0ff7e4ce7a37455d4270094b7180ddeb15c0443817ce56b31c0fb34202f7cfd13e2c0a9f4502a3209fa9095c5be8ca6c4c9dd6850ca263115ec952', '197.157.187.91', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 09:07:53', '2025-09-21 10:07:53', 1, 'cd24a266e6ba806401e754034eaa260adebdec513f755fc4a276e7be12591015', '2025-09-21 13:07:53'),
(25, 4, '031730c9413b2bc112a1b8079542234cdf516ae67b41e63b744956aa8cfe70ed668bf0997479062aee68405c8214a86a281bc735b4f0331801249c30e7fb11c6', '105.178.104.165', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 09:29:06', '2025-09-27 10:29:06', 1, '57becd5acda28d9ba6494d5229b87015d662c43dba50e78a7ab900c080caadb0', '2025-09-27 11:29:06'),
(26, 4, '2c270d1f3db48df927fd814152fe264f2193cec550c15a52d928835f2147a0e99112d7db0e4d52af6a75184e74c0cccb5b7f6b63b9f4671062c98453508bdc44', '105.178.32.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 12:29:31', '2025-09-27 13:29:31', 1, '8c1ae6be54d60be95aec8f9de52bc07903ee90fb2acb62a0ddf3b6aeb084c972', '2025-09-27 14:29:31'),
(27, 4, '8578ddaec20f803f947ee962300a696d16e8b46b1779879e7227a4720610b8d7d3b379840372ed9c3d27c889537b3642b8dedd7ba6eb57db9e307f2969cb0df5', '102.22.163.69', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 14:20:48', '2025-09-27 15:20:48', 1, '825db3a2d91457ba5207469df11722671107a066dc7c4fbe39325dfab372e8bc', '2025-09-27 16:20:48'),
(28, 4, '684c6bc5b380a264ad958463f0a226fb18c5811e28f57fa7d49dfea3ed06cfc0676ca96b3797f8f36b239861b6aa6bbd41d97cb1070b1aec449bc4f546c6beea', '105.178.104.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 16:43:44', '2025-09-27 17:43:44', 1, '55e5420a7a4827c30822c04a90b5105f95a8741b8a011cea1d36e707e06918a6', '2025-09-27 18:43:44'),
(29, 4, 'f6214bb5cdd9fa00a0da42123cdee0deae7b037dafd5ed5388419b894ff9852afdc00d196621873a55e2ee09d79c94c7a22454e38528962a879ad85d1c2e4235', '105.178.104.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 17:26:47', '2025-09-27 18:26:47', 1, '8cf34af13f09929fbe934e1d33237600c6a6891c0f748e6f389257ef329edb70', '2025-09-27 19:26:47'),
(30, 4, 'e06f0e1c761a523ae9debbddc229ccf36ba24e38c23de0ebeb742f9f298d83ae201a15ed741226a0525803a907d095acd13e340553745c9d7c31e2892ac579bd', '105.178.104.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 20:44:06', '2025-09-27 21:44:06', 1, '6dcf8006144476cb1fa39c4eb77a51eb408dc1d6c170d761dee1d8af503254e8', '2025-09-27 22:44:06');

-- --------------------------------------------------------

--
-- Table structure for table `user_two_factor_auth`
--

CREATE TABLE `user_two_factor_auth` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `method` enum('totp','sms','email','backup_codes') NOT NULL,
  `secret_key` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `backup_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`backup_codes`)),
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `recovery_codes_used` int(11) NOT NULL DEFAULT 0,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `enabled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `business_name` varchar(100) NOT NULL,
  `business_description` text DEFAULT NULL,
  `business_type` enum('individual','business','corporation') NOT NULL DEFAULT 'individual',
  `tax_id` varchar(50) DEFAULT NULL,
  `business_address` text DEFAULT NULL,
  `business_phone` varchar(20) DEFAULT NULL,
  `business_email` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `banner_url` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','suspended','rejected') NOT NULL DEFAULT 'pending',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 10.00,
  `payment_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_details`)),
  `business_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`business_documents`)),
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `name` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`id`, `user_id`, `business_name`, `business_description`, `business_type`, `tax_id`, `business_address`, `business_phone`, `business_email`, `website`, `description`, `logo_url`, `banner_url`, `status`, `commission_rate`, `payment_details`, `business_documents`, `approved_at`, `approved_by`, `created_at`, `updated_at`, `name`, `email`) VALUES
(3, 4, 'ffffeza', 'ffffff', 'individual', '', 'fffffffffffffff', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 10.00, NULL, NULL, NULL, NULL, '2025-09-14 20:46:17', '2025-09-14 20:48:37', '', ''),
(4, 5, 'Joseph store', 'Businesss managenebt', 'individual', '', 'BUsiness tools to manage my account', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', 10.00, NULL, NULL, NULL, NULL, '2025-09-20 22:27:33', '2025-09-20 20:31:07', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `vendor_commissions`
--

CREATE TABLE `vendor_commissions` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `commission_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `commission_rate` decimal(8,4) NOT NULL DEFAULT 5.0000,
  `commission_amount` decimal(10,2) DEFAULT NULL,
  `minimum_payout` decimal(10,2) NOT NULL DEFAULT 50.00,
  `payout_schedule` enum('weekly','monthly','quarterly') NOT NULL DEFAULT 'monthly',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `effective_from` timestamp NOT NULL DEFAULT current_timestamp(),
  `effective_until` timestamp NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_payouts`
--

CREATE TABLE `vendor_payouts` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `commission_earned` decimal(10,2) NOT NULL,
  `platform_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(10,2) NOT NULL,
  `payout_method` enum('bank_transfer','paypal','stripe','manual') NOT NULL DEFAULT 'bank_transfer',
  `reference_number` varchar(100) NOT NULL,
  `status` enum('pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `processed_at` timestamp NULL DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `payment_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_details`)),
  `period_from` date NOT NULL,
  `period_to` date NOT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE `wallets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `wallet_type` enum('vendor','affiliate','customer') NOT NULL DEFAULT 'vendor',
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `pending_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `frozen_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_earned` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_withdrawn` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `minimum_payout` decimal(10,2) NOT NULL DEFAULT 50.00,
  `auto_payout_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `auto_payout_threshold` decimal(10,2) NOT NULL DEFAULT 100.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallet_entries`
--

CREATE TABLE `wallet_entries` (
  `id` int(11) NOT NULL,
  `wallet_id` int(11) NOT NULL,
  `entry_type` enum('credit','debit') NOT NULL,
  `transaction_type` enum('sale','commission','payout','refund','adjustment','fee','bonus') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_before` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL,
  `wallet_id` int(11) NOT NULL,
  `type` enum('credit','debit','system_adjustment','initial') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

CREATE TABLE `warehouses` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(2) NOT NULL DEFAULT 'US',
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `operating_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`operating_hours`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `name`, `code`, `address`, `city`, `state`, `postal_code`, `country`, `phone`, `email`, `manager_id`, `capacity`, `is_active`, `operating_hours`, `created_at`, `updated_at`) VALUES
(1, 'Main Warehouse', 'MAIN', '123 Warehouse St', 'Los Angeles', 'CA', '90210', 'US', '+1-555-0123', 'warehouse@example.com', NULL, NULL, 1, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26'),
(2, 'East Coast Facility', 'EAST', '456 Shipping Ave', 'New York', 'NY', '10001', 'US', '+1-555-0124', 'east@example.com', NULL, NULL, 1, NULL, '2025-09-14 19:54:26', '2025-09-14 19:54:26');

-- --------------------------------------------------------

--
-- Table structure for table `webhook_subscriptions`
--

CREATE TABLE `webhook_subscriptions` (
  `id` int(11) NOT NULL,
  `url` varchar(500) NOT NULL,
  `events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`events`)),
  `secret` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `retry_count` int(11) NOT NULL DEFAULT 3,
  `timeout` int(11) NOT NULL DEFAULT 30,
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`headers`)),
  `last_triggered_at` timestamp NULL DEFAULT NULL,
  `failure_count` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `priority` tinyint(1) NOT NULL DEFAULT 3,
  `notes` text DEFAULT NULL,
  `price_alert` tinyint(1) NOT NULL DEFAULT 0,
  `alert_price` decimal(10,2) DEFAULT NULL,
  `notify_on_restock` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- MISSING TABLES SECTION
-- Tables required by the codebase but missing from original schema
-- Added based on comprehensive codebase analysis per user request
--

--
-- Table structure for table `seller_wallets`
-- Required by: seller/finance.php, seller/dashboard.php
--

CREATE TABLE `seller_wallets` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `pending_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_earned` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
-- Required by: admin/roles/index.php
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
-- Required by: admin/roles/index.php
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
-- Required by: admin/roles/index.php
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
-- Required by: wishlist/toggle.php (fallback table)
-- Note: This is separate from 'wishlists' for compatibility
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mail_queue`
-- Required by: includes/email.php
--

CREATE TABLE `mail_queue` (
  `id` int(11) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `template_name` varchar(100) NOT NULL,
  `template_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`template_data`)),
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `status` enum('pending','sent','failed','retry') NOT NULL DEFAULT 'pending',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_attempt` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_log`
-- Required by: includes/email.php
--

CREATE TABLE `email_log` (
  `id` int(11) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `template_name` varchar(100) DEFAULT NULL,
  `status` enum('sent','failed') NOT NULL,
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `returns`
-- Required by: admin/returns/index.php, includes/models_advanced.php
--

CREATE TABLE `returns` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `return_number` varchar(50) NOT NULL,
  `reason` enum('defective','wrong_item','damaged','not_as_described','change_of_mind','warranty','other') NOT NULL,
  `status` enum('requested','approved','rejected','shipped','received','refunded','completed','cancelled') NOT NULL DEFAULT 'requested',
  `description` text DEFAULT NULL,
  `return_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `return_tracking` varchar(100) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_accounts`
-- Required by: admin/loyalty/index.php
--

CREATE TABLE `loyalty_accounts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `program_name` varchar(100) NOT NULL DEFAULT 'main',
  `current_points` int(11) NOT NULL DEFAULT 0,
  `lifetime_points` int(11) NOT NULL DEFAULT 0,
  `tier` enum('bronze','silver','gold','platinum','diamond') NOT NULL DEFAULT 'bronze',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_ledger`
-- Required by: admin/loyalty/index.php
--

CREATE TABLE `loyalty_ledger` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `transaction_type` enum('earned','redeemed','expired','adjusted','bonus','penalty') NOT NULL,
  `points` int(11) NOT NULL,
  `balance_after` int(11) NOT NULL,
  `reference_type` enum('order','review','referral','birthday','adjustment','redemption','expiration') DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `expiry_date` timestamp NULL DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_settings`
-- Required by: admin/loyalty/index.php
--

CREATE TABLE `loyalty_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('string','integer','boolean','json') NOT NULL DEFAULT 'string',
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'general',
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_rewards`
-- Required by: admin/loyalty/index.php
--

CREATE TABLE `loyalty_rewards` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `reward_type` enum('discount','free_shipping','product','cashback','custom') NOT NULL,
  `reward_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `points_required` int(11) NOT NULL,
  `max_redemptions` int(11) DEFAULT NULL,
  `current_redemptions` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `valid_from` timestamp NULL DEFAULT NULL,
  `valid_until` timestamp NULL DEFAULT NULL,
  `terms_conditions` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_redemptions`
-- Required by: admin/loyalty/index.php
--

CREATE TABLE `loyalty_redemptions` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `points_used` int(11) NOT NULL,
  `redemption_value` decimal(10,2) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `status` enum('pending','applied','expired','cancelled') NOT NULL DEFAULT 'pending',
  `redemption_code` varchar(50) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kyc_verifications`
-- Required by: admin/kyc/index.php
--

CREATE TABLE `kyc_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `verification_type` enum('identity','address','business','financial') NOT NULL,
  `status` enum('pending','approved','rejected','expired') NOT NULL DEFAULT 'pending',
  `verification_level` enum('basic','intermediate','advanced') NOT NULL DEFAULT 'basic',
  `documents_provided` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`documents_provided`)),
  `verification_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`verification_data`)),
  `reviewer_id` int(11) DEFAULT NULL,
  `reviewer_notes` text DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `integrations`
-- Required by: admin/integrations/index.php
--

CREATE TABLE `integrations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('payment','shipping','marketing','analytics','communication','storage','other') NOT NULL,
  `provider` varchar(100) NOT NULL,
  `status` enum('active','inactive','error','pending') NOT NULL DEFAULT 'inactive',
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`config`)),
  `api_credentials` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`api_credentials`)),
  `webhook_url` varchar(500) DEFAULT NULL,
  `webhook_secret` varchar(255) DEFAULT NULL,
  `last_sync` timestamp NULL DEFAULT NULL,
  `sync_frequency` int(11) DEFAULT NULL,
  `error_count` int(11) NOT NULL DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `installed_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `webhook_deliveries`
-- Required by: admin/integrations/index.php
--

CREATE TABLE `webhook_deliveries` (
  `id` int(11) NOT NULL,
  `integration_id` int(11) NOT NULL,
  `webhook_url` varchar(500) NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `response_status` int(11) DEFAULT NULL,
  `response_headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_headers`)),
  `response_body` longtext DEFAULT NULL,
  `delivery_attempts` int(11) NOT NULL DEFAULT 1,
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp(),
  `next_attempt` timestamp NULL DEFAULT NULL,
  `status` enum('pending','delivered','failed','abandoned') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_status_logs`
-- Required by: seller/orders.php
--

CREATE TABLE `order_status_logs` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_tracking`
-- Required by: seller/orders.php
--

CREATE TABLE `order_tracking` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `tracking_number` varchar(100) NOT NULL,
  `carrier` varchar(100) NOT NULL,
  `status` enum('label_created','picked_up','in_transit','out_for_delivery','delivered','exception','returned') NOT NULL DEFAULT 'label_created',
  `location` varchar(255) DEFAULT NULL,
  `estimated_delivery` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `tracking_events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tracking_events`)),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `canned_responses`
-- Required by: admin/support/index.php
--

CREATE TABLE `canned_responses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_related`
-- Required by: seller/products/add.php, seller/products/edit.php, seller/products/delete.php
--

CREATE TABLE `product_related` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `related_product_id` int(11) NOT NULL,
  `relation_type` varchar(50) NOT NULL DEFAULT 'related',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- END OF MISSING TABLES SECTION
--

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_feed`
--
ALTER TABLE `activity_feed`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_actor_id` (`actor_id`),
  ADD KEY `idx_actor_type` (`actor_type`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_target` (`target_type`,`target_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_activity_feed_actor_action` (`actor_id`,`action`,`created_at`);

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_is_default` (`is_default`);

--
-- Indexes for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_target` (`target_type`,`target_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_resource_type` (`resource_type`),
  ADD KEY `idx_resource_id` (`resource_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `admin_analytics`
--
ALTER TABLE `admin_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_metric_period_date` (`metric_name`,`period_type`,`date_recorded`),
  ADD KEY `idx_metric_type` (`metric_type`),
  ADD KEY `idx_period_type` (`period_type`),
  ADD KEY `idx_date_recorded` (`date_recorded`);

--
-- Indexes for table `admin_dashboards`
--
ALTER TABLE `admin_dashboards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_default` (`is_default`),
  ADD KEY `idx_is_shared` (`is_shared`);

--
-- Indexes for table `admin_roles`
--
ALTER TABLE `admin_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_slug` (`slug`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_is_system_role` (`is_system_role`),
  ADD KEY `idx_hierarchy_level` (`hierarchy_level`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `admin_widgets`
--
ALTER TABLE `admin_widgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dashboard_id` (`dashboard_id`),
  ADD KEY `idx_widget_type` (`widget_type`),
  ADD KEY `idx_is_visible` (`is_visible`);

--
-- Indexes for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_recommendation_type` (`recommendation_type`),
  ADD KEY `idx_algorithm_used` (`algorithm_used`),
  ADD KEY `idx_confidence_score` (`confidence_score`),
  ADD KEY `idx_is_clicked` (`is_clicked`),
  ADD KEY `idx_is_purchased` (`is_purchased`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `api_endpoints`
--
ALTER TABLE `api_endpoints`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_endpoint_method_version` (`endpoint_path`,`http_method`,`version`),
  ADD KEY `idx_is_public` (`is_public`),
  ADD KEY `idx_requires_auth` (`requires_auth`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_version` (`version`);

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_api_key` (`api_key`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_last_used_at` (`last_used_at`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `api_logs`
--
ALTER TABLE `api_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_api_key_id` (`api_key_id`),
  ADD KEY `idx_endpoint` (`endpoint`),
  ADD KEY `idx_method` (`method`),
  ADD KEY `idx_response_status` (`response_status`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_action` (`action`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_event` (`event`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_level` (`level`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_target` (`target_type`,`target_id`),
  ADD KEY `idx_audit_logs_composite` (`user_id`,`category`,`created_at`);

--
-- Indexes for table `backups`
--
ALTER TABLE `backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_backup_type` (`backup_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_delete_after` (`delete_after`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `bounces`
--
ALTER TABLE `bounces`
  ADD PRIMARY KEY (`bounce_id`),
  ADD KEY `idx_message_id` (`message_id`),
  ADD KEY `idx_bounce_type` (`bounce_type`),
  ADD KEY `idx_email_address` (`email_address`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_bounces_email_type` (`email_address`,`bounce_type`,`timestamp`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_brands_name` (`name`),
  ADD UNIQUE KEY `uq_brands_slug` (`slug`),
  ADD KEY `idx_brands_active` (`is_active`);

--
-- Indexes for table `buyers`
--
ALTER TABLE `buyers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_tier` (`tier`),
  ADD KEY `idx_last_activity` (`last_activity`);

--
-- Indexes for table `buyer_addresses`
--
ALTER TABLE `buyer_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_buyer` (`buyer_id`),
  ADD KEY `idx_defaults` (`is_default_billing`,`is_default_shipping`);

--
-- Indexes for table `buyer_consents`
--
ALTER TABLE `buyer_consents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_buyer_type` (`buyer_id`,`consent_type`),
  ADD KEY `idx_consent_given` (`consent_given`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `buyer_disputes`
--
ALTER TABLE `buyer_disputes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dispute_number` (`dispute_number`),
  ADD KEY `idx_buyer_status` (`buyer_id`,`status`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_vendor` (`vendor_id`),
  ADD KEY `idx_deadline` (`deadline`),
  ADD KEY `fk_buyer_disputes_resolver` (`resolved_by`);

--
-- Indexes for table `buyer_dispute_evidence`
--
ALTER TABLE `buyer_dispute_evidence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dispute` (`dispute_id`),
  ADD KEY `idx_submission_date` (`submission_date`),
  ADD KEY `fk_buyer_dispute_evidence_user` (`submitted_by`);

--
-- Indexes for table `buyer_dispute_messages`
--
ALTER TABLE `buyer_dispute_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dispute` (`dispute_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_buyer_dispute_messages_sender` (`sender_id`);

--
-- Indexes for table `buyer_dsr_requests`
--
ALTER TABLE `buyer_dsr_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_buyer_status` (`buyer_id`,`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_processed_by` (`processed_by`);

--
-- Indexes for table `buyer_kpis`
--
ALTER TABLE `buyer_kpis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `buyer_date` (`buyer_id`,`metric_date`),
  ADD KEY `idx_metric_date` (`metric_date`);

--
-- Indexes for table `buyer_loyalty_accounts`
--
ALTER TABLE `buyer_loyalty_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `buyer_program` (`buyer_id`,`program_name`),
  ADD KEY `idx_tier` (`tier`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `buyer_loyalty_ledger`
--
ALTER TABLE `buyer_loyalty_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_loyalty_account` (`loyalty_account_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_expiry_date` (`expiry_date`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `buyer_messages`
--
ALTER TABLE `buyer_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_buyer_conversation` (`buyer_id`,`conversation_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_read_at` (`read_at`),
  ADD KEY `fk_buyer_messages_sender` (`sender_id`);

--
-- Indexes for table `buyer_notifications`
--
ALTER TABLE `buyer_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_buyer_read` (`buyer_id`,`read_at`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `buyer_orders`
--
ALTER TABLE `buyer_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `buyer_order` (`buyer_id`,`order_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `fk_buyer_orders_order` (`order_id`);

--
-- Indexes for table `buyer_payment_methods`
--
ALTER TABLE `buyer_payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_buyer` (`buyer_id`),
  ADD KEY `idx_is_default` (`is_default`),
  ADD KEY `idx_fingerprint` (`fingerprint`),
  ADD KEY `fk_buyer_payment_methods_address` (`billing_address_id`);

--
-- Indexes for table `buyer_preferences`
--
ALTER TABLE `buyer_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `buyer_category_key` (`buyer_id`,`category`,`preference_key`);

--
-- Indexes for table `buyer_profiles`
--
ALTER TABLE `buyer_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `buyer_id` (`buyer_id`);

--
-- Indexes for table `buyer_rmas`
--
ALTER TABLE `buyer_rmas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rma_number` (`rma_number`),
  ADD KEY `idx_buyer_status` (`buyer_id`,`status`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_vendor` (`vendor_id`);

--
-- Indexes for table `buyer_rma_messages`
--
ALTER TABLE `buyer_rma_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rma` (`rma_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_buyer_rma_messages_sender` (`sender_id`);

--
-- Indexes for table `buyer_subscriptions`
--
ALTER TABLE `buyer_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `buyer_type_category_vendor` (`buyer_id`,`subscription_type`,`category`,`vendor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_buyer_subscriptions_vendor` (`vendor_id`);

--
-- Indexes for table `buyer_tickets`
--
ALTER TABLE `buyer_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `idx_buyer_status` (`buyer_id`,`status`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `fk_buyer_tickets_order` (`order_id`),
  ADD KEY `fk_buyer_tickets_product` (`product_id`);

--
-- Indexes for table `buyer_ticket_replies`
--
ALTER TABLE `buyer_ticket_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket` (`ticket_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_buyer_ticket_replies_sender` (`sender_id`);

--
-- Indexes for table `buyer_tracking`
--
ALTER TABLE `buyer_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_buyer_order` (`buyer_order_id`),
  ADD KEY `idx_tracking_number` (`tracking_number`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `buyer_wallets`
--
ALTER TABLE `buyer_wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `buyer_currency` (`buyer_id`,`currency`);

--
-- Indexes for table `buyer_wallet_entries`
--
ALTER TABLE `buyer_wallet_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wallet` (`wallet_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `buyer_wishlist`
--
ALTER TABLE `buyer_wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `buyer_product_list` (`buyer_id`,`product_id`,`list_name`),
  ADD KEY `idx_list_name` (`list_name`),
  ADD KEY `idx_privacy` (`privacy`),
  ADD KEY `idx_price_alert` (`price_alert_enabled`,`target_price`),
  ADD KEY `fk_buyer_wishlist_product` (`product_id`);

--
-- Indexes for table `buyer_wishlist_alerts`
--
ALTER TABLE `buyer_wishlist_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wishlist` (`wishlist_id`),
  ADD KEY `idx_triggered_at` (`triggered_at`);

--
-- Indexes for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_campaign_type` (`campaign_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_end_date` (`end_date`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_campaigns_type_status_dates` (`campaign_type`,`status`,`start_date`,`end_date`);

--
-- Indexes for table `campaign_assets`
--
ALTER TABLE `campaign_assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_campaign_id` (`campaign_id`),
  ADD KEY `idx_asset_type` (`asset_type`),
  ADD KEY `idx_is_primary` (`is_primary`);

--
-- Indexes for table `campaign_messages`
--
ALTER TABLE `campaign_messages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_campaign_message` (`campaign_id`,`message_id`),
  ADD KEY `idx_campaign_id` (`campaign_id`),
  ADD KEY `idx_message_id` (`message_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `campaign_products`
--
ALTER TABLE `campaign_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_campaign_product_vendor` (`campaign_id`,`product_id`,`vendor_id`),
  ADD KEY `idx_campaign_id` (`campaign_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_joined_at` (`joined_at`);

--
-- Indexes for table `campaign_recipients`
--
ALTER TABLE `campaign_recipients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `campaign_user` (`campaign_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `campaign_stats`
--
ALTER TABLE `campaign_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_campaign_date` (`campaign_id`,`metric_date`),
  ADD KEY `idx_metric_date` (`metric_date`);

--
-- Indexes for table `campaign_targets`
--
ALTER TABLE `campaign_targets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_campaign_id` (`campaign_id`),
  ADD KEY `idx_target_type` (`target_type`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_product` (`user_id`,`product_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_cart_product` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_slug` (`slug`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- Indexes for table `category_attributes`
--
ALTER TABLE `category_attributes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_attribute_name` (`attribute_name`),
  ADD KEY `idx_is_filterable` (`is_filterable`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stream_id` (`stream_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_message_type` (`message_type`),
  ADD KEY `idx_is_deleted` (`is_deleted`),
  ADD KEY `idx_parent_message_id` (`parent_message_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_chat_messages_moderator` (`deleted_by`);

--
-- Indexes for table `cms_media`
--
ALTER TABLE `cms_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_filename` (`filename`),
  ADD KEY `idx_media_type` (`media_type`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`),
  ADD KEY `idx_is_public` (`is_public`);

--
-- Indexes for table `cms_pages`
--
ALTER TABLE `cms_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_slug` (`slug`),
  ADD KEY `idx_page_type` (`page_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_sort_order` (`sort_order`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_updated_by` (`updated_by`);

--
-- Indexes for table `cms_posts`
--
ALTER TABLE `cms_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_slug` (`slug`),
  ADD KEY `idx_post_type` (`post_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_author_id` (`author_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_published_at` (`published_at`),
  ADD KEY `idx_cms_posts_type_status_published` (`post_type`,`status`,`published_at`);

--
-- Indexes for table `comm_messages`
--
ALTER TABLE `comm_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_channel` (`channel`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_template_id` (`template_id`),
  ADD KEY `idx_campaign_id` (`campaign_id`),
  ADD KEY `idx_sent_at` (`sent_at`),
  ADD KEY `idx_comm_messages_channel_status` (`channel`,`status`,`sent_at`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_code` (`code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_valid_from` (`valid_from`),
  ADD KEY `idx_valid_to` (`valid_to`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `coupon_redemptions`
--
ALTER TABLE `coupon_redemptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_coupon_id` (`coupon_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_redeemed_at` (`redeemed_at`);

--
-- Indexes for table `coupon_rules`
--
ALTER TABLE `coupon_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_coupon_id` (`coupon_id`),
  ADD KEY `idx_rule_type` (`rule_type`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_coupon_id` (`coupon_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_used_at` (`used_at`);

--
-- Indexes for table `currencies`
--
ALTER TABLE `currencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_code` (`code`),
  ADD KEY `idx_is_base_currency` (`is_base_currency`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `customer_order_feedback`
--
ALTER TABLE `customer_order_feedback`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_order_customer` (`order_id`,`customer_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_is_public` (`is_public`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `customer_profiles`
--
ALTER TABLE `customer_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_preferred_language` (`preferred_language`),
  ADD KEY `idx_loyalty_points` (`loyalty_points`),
  ADD KEY `idx_total_spent` (`total_spent`);

--
-- Indexes for table `customer_support_conversations`
--
ALTER TABLE `customer_support_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_id` (`ticket_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_message_type` (`message_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `dashboard_widgets`
--
ALTER TABLE `dashboard_widgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_widget_type` (`widget_type`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_dashboard_widgets_user_active` (`user_id`,`is_active`);

--
-- Indexes for table `disputes`
--
ALTER TABLE `disputes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_dispute_number` (`dispute_number`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_sla_due_date` (`sla_due_date`),
  ADD KEY `fk_disputes_escalated` (`escalated_to`),
  ADD KEY `idx_disputes_status_priority` (`status`,`priority`,`sla_due_date`);

--
-- Indexes for table `dispute_decisions`
--
ALTER TABLE `dispute_decisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dispute_id` (`dispute_id`),
  ADD KEY `idx_decided_by` (`decided_by`),
  ADD KEY `idx_decision` (`decision`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `dispute_evidence`
--
ALTER TABLE `dispute_evidence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dispute_id` (`dispute_id`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`),
  ADD KEY `idx_evidence_type` (`evidence_type`),
  ADD KEY `idx_is_public` (`is_public`);

--
-- Indexes for table `dispute_messages`
--
ALTER TABLE `dispute_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dispute_id` (`dispute_id`),
  ADD KEY `idx_sender_id` (`sender_id`),
  ADD KEY `idx_sender_type` (`sender_type`),
  ADD KEY `idx_is_internal` (`is_internal`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recipient` (`recipient`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_scheduled_at` (`scheduled_at`),
  ADD KEY `idx_attempts` (`attempts`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `email_tokens`
--
ALTER TABLE `email_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_token` (`token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_used_at` (`used_at`);

--
-- Indexes for table `fact_campaigns`
--
ALTER TABLE `fact_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_campaign_date` (`campaign_id`,`date_key`),
  ADD KEY `idx_date_key` (`date_key`);

--
-- Indexes for table `fact_sales`
--
ALTER TABLE `fact_sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_date_key` (`date_key`),
  ADD KEY `idx_time_key` (`time_key`),
  ADD KEY `fk_fact_sales_order_item` (`order_item_id`),
  ADD KEY `idx_fact_sales_date_vendor` (`date_key`,`vendor_id`,`total_amount`);

--
-- Indexes for table `fact_users`
--
ALTER TABLE `fact_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_date` (`user_id`,`date_key`),
  ADD KEY `idx_date_key` (`date_key`),
  ADD KEY `idx_user_segment` (`user_segment`);

--
-- Indexes for table `file_uploads`
--
ALTER TABLE `file_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_file_hash` (`file_hash`),
  ADD KEY `idx_upload_type` (`upload_type`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `homepage_banners`
--
ALTER TABLE `homepage_banners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_position` (`position`),
  ADD KEY `idx_sort_order` (`sort_order`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_end_date` (`end_date`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_status_position_sort` (`status`,`position`,`sort_order`),
  ADD KEY `idx_status_start_end` (`status`,`start_date`,`end_date`);

--
-- Indexes for table `homepage_sections`
--
ALTER TABLE `homepage_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_section_key` (`section_key`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_warehouse` (`product_id`,`warehouse_id`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Indexes for table `inventory_adjustments`
--
ALTER TABLE `inventory_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Indexes for table `inventory_alerts`
--
ALTER TABLE `inventory_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_alert_type` (`alert_type`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_acknowledged_by` (`acknowledged_by`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_invoice_number` (`invoice_number`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_invoice_date` (`invoice_date`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_job_type` (`job_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_next_run_at` (`next_run_at`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_jobs_status_priority_next_run` (`status`,`priority`,`next_run_at`);

--
-- Indexes for table `kpi_daily`
--
ALTER TABLE `kpi_daily`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_metric_date` (`metric_date`),
  ADD KEY `idx_total_sales` (`total_sales`),
  ADD KEY `idx_total_orders` (`total_orders`),
  ADD KEY `idx_kpi_daily_date_sales` (`metric_date`,`total_sales`);

--
-- Indexes for table `kyc_decisions`
--
ALTER TABLE `kyc_decisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kyc_request_id` (`kyc_request_id`),
  ADD KEY `idx_reviewer_id` (`reviewer_id`),
  ADD KEY `idx_decision` (`decision`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `kyc_documents`
--
ALTER TABLE `kyc_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kyc_request_id` (`kyc_request_id`),
  ADD KEY `idx_document_type` (`document_type`),
  ADD KEY `idx_verification_status` (`verification_status`);

--
-- Indexes for table `kyc_flags`
--
ALTER TABLE `kyc_flags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kyc_request_id` (`kyc_request_id`),
  ADD KEY `idx_flag_type` (`flag_type`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_is_resolved` (`is_resolved`),
  ADD KEY `fk_kyc_flags_resolver` (`resolved_by`);

--
-- Indexes for table `kyc_requests`
--
ALTER TABLE `kyc_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_submitted_at` (`submitted_at`),
  ADD KEY `idx_kyc_requests_status_priority` (`status`,`priority`,`submitted_at`);

--
-- Indexes for table `live_chat_messages`
--
ALTER TABLE `live_chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stream_id` (`stream_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_message_type` (`message_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_is_moderated` (`is_moderated`),
  ADD KEY `fk_live_chat_messages_moderator` (`moderated_by`);

--
-- Indexes for table `live_streams`
--
ALTER TABLE `live_streams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_scheduled_at` (`scheduled_at`),
  ADD KEY `idx_started_at` (`started_at`),
  ADD KEY `idx_viewer_count` (`viewer_count`),
  ADD KEY `idx_live_streams_status_scheduled` (`status`,`scheduled_at`);

--
-- Indexes for table `live_stream_products`
--
ALTER TABLE `live_stream_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_stream_product` (`stream_id`,`product_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_featured_order` (`featured_order`),
  ADD KEY `idx_active` (`active`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_identifier` (`identifier`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_success` (`success`),
  ADD KEY `idx_attempted_at` (`attempted_at`);

--
-- Indexes for table `loyalty_tiers`
--
ALTER TABLE `loyalty_tiers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_min_points` (`min_points`),
  ADD KEY `idx_sort_order` (`sort_order`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `marketing_campaigns`
--
ALTER TABLE `marketing_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_campaign_type` (`campaign_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_end_date` (`end_date`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_location` (`location`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversation_id` (`conversation_id`),
  ADD KEY `idx_sender_id` (`sender_id`),
  ADD KEY `idx_recipient_id` (`recipient_id`),
  ADD KEY `idx_read_at` (`read_at`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_parent_message_id` (`parent_message_id`);

--
-- Indexes for table `message_delivery_logs`
--
ALTER TABLE `message_delivery_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_message_id` (`message_id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_event_timestamp` (`event_timestamp`),
  ADD KEY `idx_delivery_logs_message_event` (`message_id`,`event_type`,`event_timestamp`);

--
-- Indexes for table `message_templates`
--
ALTER TABLE `message_templates`
  ADD PRIMARY KEY (`template_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_language` (`language`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_message_templates_type_active` (`type`,`is_active`,`category`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_filename` (`filename`),
  ADD KEY `idx_batch` (`batch`),
  ADD KEY `idx_executed_at` (`executed_at`);

--
-- Indexes for table `multi_language_content`
--
ALTER TABLE `multi_language_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_content_language_field` (`content_type`,`content_id`,`language_code`,`field_name`),
  ADD KEY `idx_content_type` (`content_type`),
  ADD KEY `idx_content_id` (`content_id`),
  ADD KEY `idx_language_code` (`language_code`),
  ADD KEY `idx_translator_id` (`translator_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_read_at` (`read_at`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_type` (`user_id`,`type`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_payment_transaction_id` (`payment_transaction_id`),
  ADD KEY `idx_orders_status_created` (`status`,`created_at`);

--
-- Indexes for table `order_disputes`
--
ALTER TABLE `order_disputes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_order_item_id` (`order_item_id`),
  ADD KEY `idx_buyer_id` (`buyer_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_dispute_type` (`dispute_type`),
  ADD KEY `idx_resolved_by` (`resolved_by`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_orders_vendor_status` (`vendor_id`,`status`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_to_status` (`to_status`),
  ADD KEY `idx_changed_by` (`changed_by`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `otp_attempts`
--
ALTER TABLE `otp_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_attempted_at` (`attempted_at`),
  ADD KEY `idx_token_type` (`token_type`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_transaction_id` (`transaction_id`),
  ADD KEY `idx_gateway_transaction_id` (`gateway_transaction_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_payments_status_gateway` (`status`,`gateway`,`created_at`);

--
-- Indexes for table `payment_events`
--
ALTER TABLE `payment_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_id` (`payment_id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_gateway_event_id` (`gateway_event_id`),
  ADD KEY `idx_processed` (`processed`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `payment_gateways`
--
ALTER TABLE `payment_gateways`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_slug` (`slug`),
  ADD KEY `idx_provider` (`provider`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_is_default` (`is_default`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_is_default` (`is_default`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `payment_reconciliations`
--
ALTER TABLE `payment_reconciliations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reconciled_by_user` (`reconciled_by`);

--
-- Indexes for table `payouts`
--
ALTER TABLE `payouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payout_request_id` (`payout_request_id`),
  ADD KEY `idx_batch_id` (`batch_id`),
  ADD KEY `idx_gateway_payout_id` (`gateway_payout_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `payout_requests`
--
ALTER TABLE `payout_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wallet_id` (`wallet_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payout_method` (`payout_method`),
  ADD KEY `idx_processed_by` (`processed_by`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `platform_notifications`
--
ALTER TABLE `platform_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_target_audience` (`target_audience`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_end_date` (`end_date`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `platform_notification_reads`
--
ALTER TABLE `platform_notification_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_notification_user` (`notification_id`,`user_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_read_at` (`read_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_sku` (`sku`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_featured` (`featured`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_stock_quantity` (`stock_quantity`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_products_status_featured` (`status`,`featured`),
  ADD KEY `idx_products_vendor_status` (`vendor_id`,`status`),
  ADD KEY `idx_products_brand` (`brand_id`);
ALTER TABLE `products` ADD FULLTEXT KEY `idx_search` (`name`,`description`,`tags`);

--
-- Add performance index for keywords field (for search functionality)
--
ALTER TABLE `products` ADD KEY `idx_products_keywords` (`keywords`(255));

--
-- Indexes for table `product_analytics`
--
ALTER TABLE `product_analytics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_analytics_product_date` (`product_id`,`metric_date`);

--
-- Indexes for table `product_approvals`
--
ALTER TABLE `product_approvals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_reviewed_by` (`reviewed_by`),
  ADD KEY `idx_submitted_at` (`submitted_at`);

--
-- Indexes for table `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_attributes_product_id` (`product_id`);

--
-- Indexes for table `product_audit_logs`
--
ALTER TABLE `product_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `product_autosaves`
--
ALTER TABLE `product_autosaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_seller_id` (`seller_id`),
  ADD KEY `idx_updated_at` (`updated_at`);

--
-- Indexes for table `product_bulk_operations`
--
ALTER TABLE `product_bulk_operations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_bulk_ops_user_id` (`user_id`);

--
-- Indexes for table `product_bulk_uploads`
--
ALTER TABLE `product_bulk_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`product_id`,`category_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `product_certificates`
--
ALTER TABLE `product_certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_certificates_product_id` (`product_id`);

--
-- Indexes for table `product_drafts`
--
ALTER TABLE `product_drafts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_drafts_user_id` (`user_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `product_inventory`
--
ALTER TABLE `product_inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_inventory_product_id` (`product_id`),
  ADD KEY `idx_product_inventory_sku` (`sku`);

--
-- Indexes for table `product_media`
--
ALTER TABLE `product_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_media_product_id` (`product_id`),
  ADD KEY `idx_product_media_type` (`media_type`);

--
-- Indexes for table `product_pricing`
--
ALTER TABLE `product_pricing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_pricing_product_id` (`product_id`);

--
-- Indexes for table `product_recommendations`
--
ALTER TABLE `product_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_product_recommended` (`user_id`,`product_id`,`recommended_product_id`,`type`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_recommended_product_id` (`recommended_product_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_score` (`score`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `product_relations`
--
ALTER TABLE `product_relations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_relations_product_id` (`product_id`),
  ADD KEY `idx_product_relations_related_product_id` (`related_product_id`),
  ADD KEY `idx_product_relations_type` (`relation_type`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_product_reviews_moderator` (`moderated_by`);

--
-- Indexes for table `product_seo`
--
ALTER TABLE `product_seo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_seo_product_id` (`product_id`);

--
-- Indexes for table `product_shipping`
--
ALTER TABLE `product_shipping`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_shipping_product_id` (`product_id`);

--
-- Indexes for table `product_tag`
--
ALTER TABLE `product_tag`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_product_variants_product_id` (`product_id`);

--
-- Indexes for table `product_views`
--
ALTER TABLE `product_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_ip_address` (`ip_address`);

--
-- Indexes for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_endpoint` (`endpoint`(255)),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_last_used` (`last_used`);

--
-- Indexes for table `reconciliations`
--
ALTER TABLE `reconciliations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_date_gateway` (`reconciliation_date`,`gateway`),
  ADD KEY `idx_gateway` (`gateway`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_reconciled_by` (`reconciled_by`);

--
-- Indexes for table `redirects`
--
ALTER TABLE `redirects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_from_url` (`from_url`),
  ADD KEY `idx_to_url` (`to_url`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `refunds`
--
ALTER TABLE `refunds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_order_item_id` (`order_item_id`),
  ADD KEY `idx_transaction_id` (`transaction_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_refund_method` (`refund_method`),
  ADD KEY `idx_processed_by` (`processed_by`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `report_jobs`
--
ALTER TABLE `report_jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_order_item_id` (`order_item_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_verified_purchase` (`verified_purchase`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_reviews_responder` (`responded_by`),
  ADD KEY `fk_reviews_approver` (`approved_by`);

--
-- Indexes for table `review_helpfulness`
--
ALTER TABLE `review_helpfulness`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_review_user` (`review_id`,`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `search_queries`
--
ALTER TABLE `search_queries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_query` (`query`),
  ADD KEY `idx_clicked_product_id` (`clicked_product_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_risk_score` (`risk_score`),
  ADD KEY `idx_is_resolved` (`is_resolved`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_security_logs_resolver` (`resolved_by`);

--
-- Indexes for table `seller_analytics`
--
ALTER TABLE `seller_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_vendor_date` (`vendor_id`,`metric_date`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_metric_date` (`metric_date`),
  ADD KEY `idx_total_revenue` (`total_revenue`);

--
-- Indexes for table `seller_bank_details`
--
ALTER TABLE `seller_bank_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor` (`vendor_id`),
  ADD KEY `idx_is_default` (`is_default`);

--
-- Indexes for table `seller_campaigns`
--
ALTER TABLE `seller_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor_status` (`vendor_id`,`status`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Indexes for table `seller_campaign_assets`
--
ALTER TABLE `seller_campaign_assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_campaign` (`campaign_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `seller_campaign_stats`
--
ALTER TABLE `seller_campaign_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `campaign_date` (`campaign_id`,`date`),
  ADD KEY `idx_date` (`date`);

--
-- Indexes for table `seller_chat_messages`
--
ALTER TABLE `seller_chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stream` (`stream_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `seller_commissions`
--
ALTER TABLE `seller_commissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_order_item_commission` (`order_item_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payout_id` (`payout_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_seller_commissions_vendor_status` (`vendor_id`,`status`);

--
-- Indexes for table `seller_coupons`
--
ALTER TABLE `seller_coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_code` (`vendor_id`,`code`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Indexes for table `seller_coupon_redemptions`
--
ALTER TABLE `seller_coupon_redemptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_coupon` (`coupon_id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_redeemed_at` (`redeemed_at`);

--
-- Indexes for table `seller_coupon_rules`
--
ALTER TABLE `seller_coupon_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_coupon` (`coupon_id`),
  ADD KEY `idx_rule_type` (`rule_type`);

--
-- Indexes for table `seller_disputes`
--
ALTER TABLE `seller_disputes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dispute_number` (`dispute_number`),
  ADD KEY `idx_vendor_status` (`vendor_id`,`status`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_deadline` (`deadline`),
  ADD KEY `fk_seller_disputes_customer` (`customer_id`),
  ADD KEY `fk_seller_disputes_resolver` (`resolved_by`);

--
-- Indexes for table `seller_dispute_evidence`
--
ALTER TABLE `seller_dispute_evidence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dispute` (`dispute_id`),
  ADD KEY `idx_submission_date` (`submission_date`),
  ADD KEY `fk_seller_dispute_evidence_user` (`submitted_by`);

--
-- Indexes for table `seller_dispute_messages`
--
ALTER TABLE `seller_dispute_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dispute` (`dispute_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_seller_dispute_messages_sender` (`sender_id`);

--
-- Indexes for table `seller_documents`
--
ALTER TABLE `seller_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor_type` (`vendor_id`,`document_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expiry_date` (`expiry_date`),
  ADD KEY `fk_seller_documents_reviewer` (`reviewed_by`);

--
-- Indexes for table `seller_inventory`
--
ALTER TABLE `seller_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_product_variant_location` (`vendor_id`,`product_id`,`variant_id`,`location`),
  ADD KEY `idx_quantity_available` (`quantity_available`),
  ADD KEY `fk_seller_inventory_product` (`product_id`),
  ADD KEY `fk_seller_inventory_updater` (`updated_by`);

--
-- Indexes for table `seller_kpis`
--
ALTER TABLE `seller_kpis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_date` (`vendor_id`,`metric_date`),
  ADD KEY `idx_metric_date` (`metric_date`);

--
-- Indexes for table `seller_kyc`
--
ALTER TABLE `seller_kyc`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_verification_status` (`verification_status`),
  ADD KEY `idx_verification_type` (`verification_type`),
  ADD KEY `idx_verified_by` (`verified_by`),
  ADD KEY `idx_submitted_at` (`submitted_at`);

--
-- Indexes for table `seller_live_streams`
--
ALTER TABLE `seller_live_streams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stream_key` (`stream_key`),
  ADD KEY `idx_vendor_status` (`vendor_id`,`status`),
  ADD KEY `idx_scheduled_start` (`scheduled_start`);

--
-- Indexes for table `seller_messages`
--
ALTER TABLE `seller_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor_conversation` (`vendor_id`,`conversation_id`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_seller_messages_order` (`order_id`);

--
-- Indexes for table `seller_message_templates`
--
ALTER TABLE `seller_message_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor_category` (`vendor_id`,`category`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `seller_notifications`
--
ALTER TABLE `seller_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor_read` (`vendor_id`,`read_at`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `seller_orders`
--
ALTER TABLE `seller_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_order` (`vendor_id`,`order_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payout_status` (`payout_status`),
  ADD KEY `idx_tracking_number` (`tracking_number`),
  ADD KEY `fk_seller_orders_order` (`order_id`);

--
-- Indexes for table `seller_order_items`
--
ALTER TABLE `seller_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_seller_order` (`seller_order_id`),
  ADD KEY `idx_order_item` (`order_item_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `seller_payouts`
--
ALTER TABLE `seller_payouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_requested_at` (`requested_at`),
  ADD KEY `idx_processed_by` (`processed_by`),
  ADD KEY `idx_reference_number` (`reference_number`),
  ADD KEY `idx_seller_payouts_vendor_status` (`vendor_id`,`status`);

--
-- Indexes for table `seller_payout_requests`
--
ALTER TABLE `seller_payout_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor_status` (`vendor_id`,`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_seller_payout_requests_processor` (`processed_by`);

--
-- Indexes for table `seller_performance_metrics`
--
ALTER TABLE `seller_performance_metrics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_date` (`vendor_id`,`metric_date`),
  ADD KEY `idx_metric_date` (`metric_date`);

--
-- Indexes for table `seller_products`
--
ALTER TABLE `seller_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_product` (`vendor_id`,`product_id`),
  ADD KEY `idx_approval_status` (`approval_status`),
  ADD KEY `idx_sku` (`sku`),
  ADD KEY `fk_seller_products_product` (`product_id`),
  ADD KEY `fk_seller_products_approver` (`approved_by`);

--
-- Indexes for table `seller_product_media`
--
ALTER TABLE `seller_product_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_seller_product` (`seller_product_id`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- Indexes for table `seller_product_variants`
--
ALTER TABLE `seller_product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_seller_product` (`seller_product_id`),
  ADD KEY `idx_sku` (`sku`);

--
-- Indexes for table `seller_profiles`
--
ALTER TABLE `seller_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_is_verified` (`is_verified`);

--
-- Indexes for table `seller_reports_jobs`
--
ALTER TABLE `seller_reports_jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor_status` (`vendor_id`,`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `seller_rmas`
--
ALTER TABLE `seller_rmas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rma_number` (`rma_number`),
  ADD KEY `idx_vendor_status` (`vendor_id`,`status`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_customer` (`customer_id`);

--
-- Indexes for table `seller_rma_notes`
--
ALTER TABLE `seller_rma_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rma` (`rma_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_seller_rma_notes_user` (`user_id`);

--
-- Indexes for table `seller_sales_reports`
--
ALTER TABLE `seller_sales_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor_type` (`vendor_id`,`report_type`),
  ADD KEY `idx_period` (`period_start`,`period_end`);

--
-- Indexes for table `seller_shipping_rates`
--
ALTER TABLE `seller_shipping_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_shipping_zone_id` (`shipping_zone_id`),
  ADD KEY `idx_method` (`method`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `seller_shipping_zones`
--
ALTER TABLE `seller_shipping_zones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- Indexes for table `seller_staff`
--
ALTER TABLE `seller_staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_vendor_user` (`vendor_id`,`user_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_invited_by` (`invited_by`);

--
-- Indexes for table `seller_stock_logs`
--
ALTER TABLE `seller_stock_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventory` (`inventory_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`),
  ADD KEY `fk_seller_stock_logs_user` (`performed_by`);

--
-- Indexes for table `seller_stream_products`
--
ALTER TABLE `seller_stream_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stream` (`stream_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_featured_at` (`featured_at`);

--
-- Indexes for table `seo_meta`
--
ALTER TABLE `seo_meta`
  ADD UNIQUE KEY `uniq_entity` (`entity_type`,`entity_id`);

--
-- Indexes for table `seo_metadata`
--
ALTER TABLE `seo_metadata`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_entity_type` (`entity_type`),
  ADD KEY `idx_entity_id` (`entity_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_group_key` (`setting_group`,`setting_key`),
  ADD KEY `idx_setting_group` (`setting_group`),
  ADD KEY `idx_is_public` (`is_public`),
  ADD KEY `idx_updated_by` (`updated_by`),
  ADD KEY `idx_settings_group_public` (`setting_group`,`is_public`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `tracking_number` (`tracking_number`),
  ADD KEY `fk_shipments_created_by` (`created_by`);

--
-- Indexes for table `shipment_items`
--
ALTER TABLE `shipment_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipment_id` (`shipment_id`),
  ADD KEY `order_item_id` (`order_item_id`);

--
-- Indexes for table `shipping_carriers`
--
ALTER TABLE `shipping_carriers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `stream_events`
--
ALTER TABLE `stream_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stream_id` (`stream_id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `stream_products`
--
ALTER TABLE `stream_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stream_id` (`stream_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_is_currently_featured` (`is_currently_featured`);

--
-- Indexes for table `stream_viewers`
--
ALTER TABLE `stream_viewers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stream_id` (`stream_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_joined_at` (`joined_at`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`subscription_id`),
  ADD UNIQUE KEY `idx_user_channel_type` (`user_id`,`channel`,`subscription_type`),
  ADD KEY `idx_channel` (`channel`),
  ADD KEY `idx_opt_in_status` (`opt_in_status`),
  ADD KEY `idx_subscription_type` (`subscription_type`),
  ADD KEY `idx_subscriptions_user_channel` (`user_id`,`channel`,`opt_in_status`);

--
-- Indexes for table `support_messages`
--
ALTER TABLE `support_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_ticket_number` (`ticket_number`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_guest_email` (`guest_email`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_escalated_to` (`escalated_to`),
  ADD KEY `idx_related_order_id` (`related_order_id`),
  ADD KEY `idx_related_product_id` (`related_product_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `support_ticket_replies`
--
ALTER TABLE `support_ticket_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_id` (`ticket_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_reply_type` (`reply_type`),
  ADD KEY `idx_is_internal` (`is_internal`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `system_alerts`
--
ALTER TABLE `system_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_alert_type` (`alert_type`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_is_resolved` (`is_resolved`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_resolved_by` (`resolved_by`),
  ADD KEY `idx_system_alerts_type_severity` (`alert_type`,`severity`,`is_resolved`);

--
-- Indexes for table `system_events`
--
ALTER TABLE `system_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_component` (`component`),
  ADD KEY `idx_is_resolved` (`is_resolved`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_system_events_resolver` (`resolved_by`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_setting_key` (`setting_key`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_is_public` (`is_public`),
  ADD KEY `idx_updated_by` (`updated_by`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tax_rules`
--
ALTER TABLE `tax_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_country` (`country`),
  ADD KEY `idx_state` (`state`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_effective_from` (`effective_from`);

--
-- Indexes for table `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_template_type` (`template_type`),
  ADD KEY `idx_is_system` (`is_system`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_gateway_transaction_id` (`gateway_transaction_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `unsubscribe_links`
--
ALTER TABLE `unsubscribe_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_token` (`token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_channel` (`channel`),
  ADD KEY `idx_message_id` (`message_id`),
  ADD KEY `idx_is_used` (`is_used`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_username` (`username`),
  ADD UNIQUE KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_users_role_status` (`role`,`status`);

--
-- Indexes for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_activities_user` (`user_id`),
  ADD KEY `idx_user_activities_product` (`product_id`),
  ADD KEY `idx_user_activities_session` (`session_id`),
  ADD KEY `idx_user_activities_action` (`activity_type`),
  ADD KEY `idx_user_activities_created` (`created_at`);

--
-- Indexes for table `user_audit_logs`
--
ALTER TABLE `user_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `user_documents`
--
ALTER TABLE `user_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_document_type` (`document_type`),
  ADD KEY `idx_verification_status` (`verification_status`),
  ADD KEY `idx_verified_by` (`verified_by`);

--
-- Indexes for table `user_follows`
--
ALTER TABLE `user_follows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_follower_following` (`follower_id`,`following_id`,`type`),
  ADD KEY `idx_following_id` (`following_id`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `user_logins`
--
ALTER TABLE `user_logins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_login_type` (`login_type`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_success` (`success`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_user_profiles_timezone_language` (`timezone`,`language`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_name` (`name`),
  ADD KEY `idx_is_system_role` (`is_system_role`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `user_role_assignments`
--
ALTER TABLE `user_role_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_role_unique` (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `user_two_factor_auth`
--
ALTER TABLE `user_two_factor_auth`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_method` (`user_id`,`method`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_method` (`method`),
  ADD KEY `idx_is_enabled` (`is_enabled`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_business_name` (`business_name`),
  ADD KEY `fk_vendors_approver` (`approved_by`);

--
-- Indexes for table `vendor_commissions`
--
ALTER TABLE `vendor_commissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_effective_from` (`effective_from`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `vendor_payouts`
--
ALTER TABLE `vendor_payouts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_reference_number` (`reference_number`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_processed_at` (`processed_at`),
  ADD KEY `idx_period_from` (`period_from`),
  ADD KEY `idx_period_to` (`period_to`),
  ADD KEY `idx_processed_by` (`processed_by`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_wallet_type` (`user_id`,`wallet_type`),
  ADD KEY `idx_wallet_type` (`wallet_type`),
  ADD KEY `idx_balance` (`balance`);

--
-- Indexes for table `wallet_entries`
--
ALTER TABLE `wallet_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wallet_id` (`wallet_id`),
  ADD KEY `idx_entry_type` (`entry_type`),
  ADD KEY `idx_transaction_type` (`transaction_type`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_wallet_entries_creator` (`created_by`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wallet_id` (`wallet_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_code` (`code`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_manager_id` (`manager_id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `webhook_subscriptions`
--
ALTER TABLE `webhook_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_last_triggered_at` (`last_triggered_at`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_product` (`user_id`,`product_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_price_alert` (`price_alert`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_feed`
--
ALTER TABLE `activity_feed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_actions`
--
ALTER TABLE `admin_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_analytics`
--
ALTER TABLE `admin_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_dashboards`
--
ALTER TABLE `admin_dashboards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_roles`
--
ALTER TABLE `admin_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_widgets`
--
ALTER TABLE `admin_widgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api_endpoints`
--
ALTER TABLE `api_endpoints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api_logs`
--
ALTER TABLE `api_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `backups`
--
ALTER TABLE `backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bounces`
--
ALTER TABLE `bounces`
  MODIFY `bounce_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `buyers`
--
ALTER TABLE `buyers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_addresses`
--
ALTER TABLE `buyer_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_consents`
--
ALTER TABLE `buyer_consents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_disputes`
--
ALTER TABLE `buyer_disputes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_dispute_evidence`
--
ALTER TABLE `buyer_dispute_evidence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_dispute_messages`
--
ALTER TABLE `buyer_dispute_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_dsr_requests`
--
ALTER TABLE `buyer_dsr_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_kpis`
--
ALTER TABLE `buyer_kpis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_loyalty_accounts`
--
ALTER TABLE `buyer_loyalty_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_loyalty_ledger`
--
ALTER TABLE `buyer_loyalty_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_messages`
--
ALTER TABLE `buyer_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_notifications`
--
ALTER TABLE `buyer_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_orders`
--
ALTER TABLE `buyer_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_payment_methods`
--
ALTER TABLE `buyer_payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_preferences`
--
ALTER TABLE `buyer_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_profiles`
--
ALTER TABLE `buyer_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_rmas`
--
ALTER TABLE `buyer_rmas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_rma_messages`
--
ALTER TABLE `buyer_rma_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_subscriptions`
--
ALTER TABLE `buyer_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_tickets`
--
ALTER TABLE `buyer_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_ticket_replies`
--
ALTER TABLE `buyer_ticket_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_tracking`
--
ALTER TABLE `buyer_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_wallets`
--
ALTER TABLE `buyer_wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_wallet_entries`
--
ALTER TABLE `buyer_wallet_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_wishlist`
--
ALTER TABLE `buyer_wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyer_wishlist_alerts`
--
ALTER TABLE `buyer_wishlist_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaign_assets`
--
ALTER TABLE `campaign_assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaign_messages`
--
ALTER TABLE `campaign_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaign_products`
--
ALTER TABLE `campaign_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaign_recipients`
--
ALTER TABLE `campaign_recipients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaign_stats`
--
ALTER TABLE `campaign_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaign_targets`
--
ALTER TABLE `campaign_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1504;

--
-- AUTO_INCREMENT for table `category_attributes`
--
ALTER TABLE `category_attributes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cms_media`
--
ALTER TABLE `cms_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cms_pages`
--
ALTER TABLE `cms_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cms_posts`
--
ALTER TABLE `cms_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comm_messages`
--
ALTER TABLE `comm_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupon_redemptions`
--
ALTER TABLE `coupon_redemptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupon_rules`
--
ALTER TABLE `coupon_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `currencies`
--
ALTER TABLE `currencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customer_order_feedback`
--
ALTER TABLE `customer_order_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_profiles`
--
ALTER TABLE `customer_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_support_conversations`
--
ALTER TABLE `customer_support_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dashboard_widgets`
--
ALTER TABLE `dashboard_widgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disputes`
--
ALTER TABLE `disputes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dispute_decisions`
--
ALTER TABLE `dispute_decisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dispute_evidence`
--
ALTER TABLE `dispute_evidence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dispute_messages`
--
ALTER TABLE `dispute_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_tokens`
--
ALTER TABLE `email_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fact_campaigns`
--
ALTER TABLE `fact_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fact_sales`
--
ALTER TABLE `fact_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fact_users`
--
ALTER TABLE `fact_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file_uploads`
--
ALTER TABLE `file_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `homepage_banners`
--
ALTER TABLE `homepage_banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `homepage_sections`
--
ALTER TABLE `homepage_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_adjustments`
--
ALTER TABLE `inventory_adjustments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_alerts`
--
ALTER TABLE `inventory_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpi_daily`
--
ALTER TABLE `kpi_daily`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kyc_decisions`
--
ALTER TABLE `kyc_decisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kyc_documents`
--
ALTER TABLE `kyc_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kyc_flags`
--
ALTER TABLE `kyc_flags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kyc_requests`
--
ALTER TABLE `kyc_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `live_chat_messages`
--
ALTER TABLE `live_chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `live_streams`
--
ALTER TABLE `live_streams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `live_stream_products`
--
ALTER TABLE `live_stream_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `loyalty_tiers`
--
ALTER TABLE `loyalty_tiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `marketing_campaigns`
--
ALTER TABLE `marketing_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_delivery_logs`
--
ALTER TABLE `message_delivery_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_templates`
--
ALTER TABLE `message_templates`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `multi_language_content`
--
ALTER TABLE `multi_language_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_disputes`
--
ALTER TABLE `order_disputes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otp_attempts`
--
ALTER TABLE `otp_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_events`
--
ALTER TABLE `payment_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_gateways`
--
ALTER TABLE `payment_gateways`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_reconciliations`
--
ALTER TABLE `payment_reconciliations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payouts`
--
ALTER TABLE `payouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payout_requests`
--
ALTER TABLE `payout_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `platform_notifications`
--
ALTER TABLE `platform_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `platform_notification_reads`
--
ALTER TABLE `platform_notification_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_analytics`
--
ALTER TABLE `product_analytics`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_approvals`
--
ALTER TABLE `product_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_attributes`
--
ALTER TABLE `product_attributes`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_audit_logs`
--
ALTER TABLE `product_audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_autosaves`
--
ALTER TABLE `product_autosaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_bulk_operations`
--
ALTER TABLE `product_bulk_operations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_bulk_uploads`
--
ALTER TABLE `product_bulk_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_certificates`
--
ALTER TABLE `product_certificates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_drafts`
--
ALTER TABLE `product_drafts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product_inventory`
--
ALTER TABLE `product_inventory`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_media`
--
ALTER TABLE `product_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_pricing`
--
ALTER TABLE `product_pricing`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_recommendations`
--
ALTER TABLE `product_recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_relations`
--
ALTER TABLE `product_relations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_seo`
--
ALTER TABLE `product_seo`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_shipping`
--
ALTER TABLE `product_shipping`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_tag`
--
ALTER TABLE `product_tag`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_views`
--
ALTER TABLE `product_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reconciliations`
--
ALTER TABLE `reconciliations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `redirects`
--
ALTER TABLE `redirects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `refunds`
--
ALTER TABLE `refunds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_jobs`
--
ALTER TABLE `report_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_helpfulness`
--
ALTER TABLE `review_helpfulness`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `search_queries`
--
ALTER TABLE `search_queries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_analytics`
--
ALTER TABLE `seller_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_bank_details`
--
ALTER TABLE `seller_bank_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_campaigns`
--
ALTER TABLE `seller_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_campaign_assets`
--
ALTER TABLE `seller_campaign_assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_campaign_stats`
--
ALTER TABLE `seller_campaign_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_chat_messages`
--
ALTER TABLE `seller_chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_commissions`
--
ALTER TABLE `seller_commissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_coupons`
--
ALTER TABLE `seller_coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_coupon_redemptions`
--
ALTER TABLE `seller_coupon_redemptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_coupon_rules`
--
ALTER TABLE `seller_coupon_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_disputes`
--
ALTER TABLE `seller_disputes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_dispute_evidence`
--
ALTER TABLE `seller_dispute_evidence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_dispute_messages`
--
ALTER TABLE `seller_dispute_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_documents`
--
ALTER TABLE `seller_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_inventory`
--
ALTER TABLE `seller_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_kpis`
--
ALTER TABLE `seller_kpis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_kyc`
--
ALTER TABLE `seller_kyc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_live_streams`
--
ALTER TABLE `seller_live_streams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_messages`
--
ALTER TABLE `seller_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_message_templates`
--
ALTER TABLE `seller_message_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_notifications`
--
ALTER TABLE `seller_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_orders`
--
ALTER TABLE `seller_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_order_items`
--
ALTER TABLE `seller_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_payouts`
--
ALTER TABLE `seller_payouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_payout_requests`
--
ALTER TABLE `seller_payout_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_performance_metrics`
--
ALTER TABLE `seller_performance_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_products`
--
ALTER TABLE `seller_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_product_media`
--
ALTER TABLE `seller_product_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_product_variants`
--
ALTER TABLE `seller_product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_profiles`
--
ALTER TABLE `seller_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_reports_jobs`
--
ALTER TABLE `seller_reports_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_rmas`
--
ALTER TABLE `seller_rmas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_rma_notes`
--
ALTER TABLE `seller_rma_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_sales_reports`
--
ALTER TABLE `seller_sales_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_shipping_rates`
--
ALTER TABLE `seller_shipping_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_shipping_zones`
--
ALTER TABLE `seller_shipping_zones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_staff`
--
ALTER TABLE `seller_staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_stock_logs`
--
ALTER TABLE `seller_stock_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_stream_products`
--
ALTER TABLE `seller_stream_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seo_metadata`
--
ALTER TABLE `seo_metadata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipment_items`
--
ALTER TABLE `shipment_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipping_carriers`
--
ALTER TABLE `shipping_carriers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `stream_events`
--
ALTER TABLE `stream_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stream_products`
--
ALTER TABLE `stream_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stream_viewers`
--
ALTER TABLE `stream_viewers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `subscription_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `support_messages`
--
ALTER TABLE `support_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_ticket_replies`
--
ALTER TABLE `support_ticket_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_alerts`
--
ALTER TABLE `system_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_events`
--
ALTER TABLE `system_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tax_rules`
--
ALTER TABLE `tax_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `templates`
--
ALTER TABLE `templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `unsubscribe_links`
--
ALTER TABLE `unsubscribe_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_audit_logs`
--
ALTER TABLE `user_audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_documents`
--
ALTER TABLE `user_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_follows`
--
ALTER TABLE `user_follows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_logins`
--
ALTER TABLE `user_logins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_role_assignments`
--
ALTER TABLE `user_role_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `user_two_factor_auth`
--
ALTER TABLE `user_two_factor_auth`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vendor_commissions`
--
ALTER TABLE `vendor_commissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_payouts`
--
ALTER TABLE `vendor_payouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallets`
--
ALTER TABLE `wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallet_entries`
--
ALTER TABLE `wallet_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `webhook_subscriptions`
--
ALTER TABLE `webhook_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  ADD CONSTRAINT `fk_admin_activity_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_dashboards`
--
ALTER TABLE `admin_dashboards`
  ADD CONSTRAINT `fk_admin_dashboards_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_roles`
--
ALTER TABLE `admin_roles`
  ADD CONSTRAINT `fk_admin_roles_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_widgets`
--
ALTER TABLE `admin_widgets`
  ADD CONSTRAINT `fk_admin_widgets_dashboard` FOREIGN KEY (`dashboard_id`) REFERENCES `admin_dashboards` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD CONSTRAINT `fk_ai_recommendations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD CONSTRAINT `fk_api_keys_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `api_logs`
--
ALTER TABLE `api_logs`
  ADD CONSTRAINT `fk_api_logs_api_key` FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `backups`
--
ALTER TABLE `backups`
  ADD CONSTRAINT `fk_backups_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bounces`
--
ALTER TABLE `bounces`
  ADD CONSTRAINT `fk_bounces_message` FOREIGN KEY (`message_id`) REFERENCES `comm_messages` (`message_id`) ON DELETE CASCADE;

--
-- Constraints for table `buyers`
--
ALTER TABLE `buyers`
  ADD CONSTRAINT `fk_buyers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_addresses`
--
ALTER TABLE `buyer_addresses`
  ADD CONSTRAINT `fk_buyer_addresses_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_consents`
--
ALTER TABLE `buyer_consents`
  ADD CONSTRAINT `fk_buyer_consents_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_disputes`
--
ALTER TABLE `buyer_disputes`
  ADD CONSTRAINT `fk_buyer_disputes_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_buyer_disputes_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_buyer_disputes_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_buyer_disputes_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_dispute_evidence`
--
ALTER TABLE `buyer_dispute_evidence`
  ADD CONSTRAINT `fk_buyer_dispute_evidence_dispute` FOREIGN KEY (`dispute_id`) REFERENCES `buyer_disputes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_buyer_dispute_evidence_user` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_dispute_messages`
--
ALTER TABLE `buyer_dispute_messages`
  ADD CONSTRAINT `fk_buyer_dispute_messages_dispute` FOREIGN KEY (`dispute_id`) REFERENCES `buyer_disputes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_buyer_dispute_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_dsr_requests`
--
ALTER TABLE `buyer_dsr_requests`
  ADD CONSTRAINT `fk_buyer_dsr_requests_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_buyer_dsr_requests_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `buyer_kpis`
--
ALTER TABLE `buyer_kpis`
  ADD CONSTRAINT `fk_buyer_kpis_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_loyalty_accounts`
--
ALTER TABLE `buyer_loyalty_accounts`
  ADD CONSTRAINT `fk_buyer_loyalty_accounts_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_loyalty_ledger`
--
ALTER TABLE `buyer_loyalty_ledger`
  ADD CONSTRAINT `fk_buyer_loyalty_ledger_account` FOREIGN KEY (`loyalty_account_id`) REFERENCES `buyer_loyalty_accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_messages`
--
ALTER TABLE `buyer_messages`
  ADD CONSTRAINT `fk_buyer_messages_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_buyer_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `buyer_notifications`
--
ALTER TABLE `buyer_notifications`
  ADD CONSTRAINT `fk_buyer_notifications_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_orders`
--
ALTER TABLE `buyer_orders`
  ADD CONSTRAINT `fk_buyer_orders_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_buyer_orders_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_payment_methods`
--
ALTER TABLE `buyer_payment_methods`
  ADD CONSTRAINT `fk_buyer_payment_methods_address` FOREIGN KEY (`billing_address_id`) REFERENCES `buyer_addresses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_buyer_payment_methods_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_preferences`
--
ALTER TABLE `buyer_preferences`
  ADD CONSTRAINT `fk_buyer_preferences_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_profiles`
--
ALTER TABLE `buyer_profiles`
  ADD CONSTRAINT `fk_buyer_profiles_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_rmas`
--
ALTER TABLE `buyer_rmas`
  ADD CONSTRAINT `fk_buyer_rmas_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_buyer_rmas_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_buyer_rmas_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_rma_messages`
--
ALTER TABLE `buyer_rma_messages`
  ADD CONSTRAINT `fk_buyer_rma_messages_rma` FOREIGN KEY (`rma_id`) REFERENCES `buyer_rmas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_buyer_rma_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_subscriptions`
--
ALTER TABLE `buyer_subscriptions`
  ADD CONSTRAINT `fk_buyer_subscriptions_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_buyer_subscriptions_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_tickets`
--
ALTER TABLE `buyer_tickets`
  ADD CONSTRAINT `fk_buyer_tickets_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_buyer_tickets_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_buyer_tickets_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_buyer_tickets_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `buyer_ticket_replies`
--
ALTER TABLE `buyer_ticket_replies`
  ADD CONSTRAINT `fk_buyer_ticket_replies_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_buyer_ticket_replies_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `buyer_tickets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_tracking`
--
ALTER TABLE `buyer_tracking`
  ADD CONSTRAINT `fk_buyer_tracking_buyer_order` FOREIGN KEY (`buyer_order_id`) REFERENCES `buyer_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_wallets`
--
ALTER TABLE `buyer_wallets`
  ADD CONSTRAINT `fk_buyer_wallets_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_wallet_entries`
--
ALTER TABLE `buyer_wallet_entries`
  ADD CONSTRAINT `fk_buyer_wallet_entries_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `buyer_wallets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_wishlist`
--
ALTER TABLE `buyer_wishlist`
  ADD CONSTRAINT `fk_buyer_wishlist_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_buyer_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_wishlist_alerts`
--
ALTER TABLE `buyer_wishlist_alerts`
  ADD CONSTRAINT `fk_buyer_wishlist_alerts_wishlist` FOREIGN KEY (`wishlist_id`) REFERENCES `buyer_wishlist` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD CONSTRAINT `fk_campaigns_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `campaign_assets`
--
ALTER TABLE `campaign_assets`
  ADD CONSTRAINT `fk_campaign_assets_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `campaign_messages`
--
ALTER TABLE `campaign_messages`
  ADD CONSTRAINT `fk_campaign_messages_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_campaign_messages_message` FOREIGN KEY (`message_id`) REFERENCES `comm_messages` (`message_id`) ON DELETE CASCADE;

--
-- Constraints for table `campaign_products`
--
ALTER TABLE `campaign_products`
  ADD CONSTRAINT `fk_campaign_products_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `marketing_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_campaign_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_campaign_products_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `campaign_recipients`
--
ALTER TABLE `campaign_recipients`
  ADD CONSTRAINT `campaign_recipients_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `marketing_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campaign_recipients_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `campaign_stats`
--
ALTER TABLE `campaign_stats`
  ADD CONSTRAINT `fk_campaign_stats_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `campaign_targets`
--
ALTER TABLE `campaign_targets`
  ADD CONSTRAINT `fk_campaign_targets_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `category_attributes`
--
ALTER TABLE `category_attributes`
  ADD CONSTRAINT `fk_category_attributes_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_chat_messages_moderator` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_chat_messages_stream` FOREIGN KEY (`stream_id`) REFERENCES `live_streams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_chat_messages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cms_media`
--
ALTER TABLE `cms_media`
  ADD CONSTRAINT `fk_cms_media_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cms_pages`
--
ALTER TABLE `cms_pages`
  ADD CONSTRAINT `fk_cms_pages_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cms_pages_parent` FOREIGN KEY (`parent_id`) REFERENCES `cms_pages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cms_pages_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cms_posts`
--
ALTER TABLE `cms_posts`
  ADD CONSTRAINT `fk_cms_posts_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cms_posts_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `comm_messages`
--
ALTER TABLE `comm_messages`
  ADD CONSTRAINT `fk_comm_messages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `coupons`
--
ALTER TABLE `coupons`
  ADD CONSTRAINT `fk_coupons_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `coupon_redemptions`
--
ALTER TABLE `coupon_redemptions`
  ADD CONSTRAINT `fk_coupon_redemptions_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_coupon_redemptions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_coupon_redemptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coupon_rules`
--
ALTER TABLE `coupon_rules`
  ADD CONSTRAINT `fk_coupon_rules_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD CONSTRAINT `fk_coupon_usage_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_coupon_usage_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_coupon_usage_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_order_feedback`
--
ALTER TABLE `customer_order_feedback`
  ADD CONSTRAINT `fk_customer_order_feedback_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_customer_order_feedback_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_customer_order_feedback_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_profiles`
--
ALTER TABLE `customer_profiles`
  ADD CONSTRAINT `fk_customer_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_support_conversations`
--
ALTER TABLE `customer_support_conversations`
  ADD CONSTRAINT `fk_support_conversations_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_support_conversations_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_support_conversations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_support_conversations_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `dashboard_widgets`
--
ALTER TABLE `dashboard_widgets`
  ADD CONSTRAINT `fk_dashboard_widgets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `disputes`
--
ALTER TABLE `disputes`
  ADD CONSTRAINT `fk_disputes_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_disputes_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_disputes_escalated` FOREIGN KEY (`escalated_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_disputes_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_disputes_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dispute_decisions`
--
ALTER TABLE `dispute_decisions`
  ADD CONSTRAINT `fk_dispute_decisions_decider` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dispute_decisions_dispute` FOREIGN KEY (`dispute_id`) REFERENCES `disputes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dispute_evidence`
--
ALTER TABLE `dispute_evidence`
  ADD CONSTRAINT `fk_dispute_evidence_dispute` FOREIGN KEY (`dispute_id`) REFERENCES `disputes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dispute_evidence_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dispute_messages`
--
ALTER TABLE `dispute_messages`
  ADD CONSTRAINT `fk_dispute_messages_dispute` FOREIGN KEY (`dispute_id`) REFERENCES `disputes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dispute_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_tokens`
--
ALTER TABLE `email_tokens`
  ADD CONSTRAINT `fk_email_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fact_campaigns`
--
ALTER TABLE `fact_campaigns`
  ADD CONSTRAINT `fk_fact_campaigns_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fact_sales`
--
ALTER TABLE `fact_sales`
  ADD CONSTRAINT `fk_fact_sales_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fact_sales_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fact_users`
--
ALTER TABLE `fact_users`
  ADD CONSTRAINT `fk_fact_users_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `file_uploads`
--
ALTER TABLE `file_uploads`
  ADD CONSTRAINT `fk_file_uploads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `homepage_banners`
--
ALTER TABLE `homepage_banners`
  ADD CONSTRAINT `fk_homepage_banners_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_adjustments`
--
ALTER TABLE `inventory_adjustments`
  ADD CONSTRAINT `inventory_adjustments_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_adjustments_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_alerts`
--
ALTER TABLE `inventory_alerts`
  ADD CONSTRAINT `fk_inventory_alerts_acknowledger` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inventory_alerts_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoices_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invoices_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `fk_jobs_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `kyc_decisions`
--
ALTER TABLE `kyc_decisions`
  ADD CONSTRAINT `fk_kyc_decisions_request` FOREIGN KEY (`kyc_request_id`) REFERENCES `kyc_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_kyc_decisions_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kyc_documents`
--
ALTER TABLE `kyc_documents`
  ADD CONSTRAINT `fk_kyc_documents_request` FOREIGN KEY (`kyc_request_id`) REFERENCES `kyc_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kyc_flags`
--
ALTER TABLE `kyc_flags`
  ADD CONSTRAINT `fk_kyc_flags_request` FOREIGN KEY (`kyc_request_id`) REFERENCES `kyc_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_kyc_flags_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `kyc_requests`
--
ALTER TABLE `kyc_requests`
  ADD CONSTRAINT `fk_kyc_requests_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_kyc_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `live_chat_messages`
--
ALTER TABLE `live_chat_messages`
  ADD CONSTRAINT `fk_live_chat_messages_moderator` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_live_chat_messages_stream` FOREIGN KEY (`stream_id`) REFERENCES `live_streams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_live_chat_messages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `live_streams`
--
ALTER TABLE `live_streams`
  ADD CONSTRAINT `fk_live_streams_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `live_stream_products`
--
ALTER TABLE `live_stream_products`
  ADD CONSTRAINT `fk_live_stream_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_live_stream_products_stream` FOREIGN KEY (`stream_id`) REFERENCES `live_streams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `marketing_campaigns`
--
ALTER TABLE `marketing_campaigns`
  ADD CONSTRAINT `fk_marketing_campaigns_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_parent` FOREIGN KEY (`parent_message_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_messages_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `message_delivery_logs`
--
ALTER TABLE `message_delivery_logs`
  ADD CONSTRAINT `fk_message_delivery_logs_message` FOREIGN KEY (`message_id`) REFERENCES `comm_messages` (`message_id`) ON DELETE CASCADE;

--
-- Constraints for table `message_templates`
--
ALTER TABLE `message_templates`
  ADD CONSTRAINT `fk_message_templates_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `multi_language_content`
--
ALTER TABLE `multi_language_content`
  ADD CONSTRAINT `fk_multi_language_content_translator` FOREIGN KEY (`translator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD CONSTRAINT `fk_notification_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_disputes`
--
ALTER TABLE `order_disputes`
  ADD CONSTRAINT `fk_order_disputes_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_disputes_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_disputes_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_disputes_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_disputes_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_order_items_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`);

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `fk_order_status_history_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_status_history_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `otp_attempts`
--
ALTER TABLE `otp_attempts`
  ADD CONSTRAINT `fk_otp_attempts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payments_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_events`
--
ALTER TABLE `payment_events`
  ADD CONSTRAINT `fk_payment_events_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD CONSTRAINT `fk_payment_methods_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_reconciliations`
--
ALTER TABLE `payment_reconciliations`
  ADD CONSTRAINT `fk_reconciled_by_user` FOREIGN KEY (`reconciled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `payouts`
--
ALTER TABLE `payouts`
  ADD CONSTRAINT `fk_payouts_request` FOREIGN KEY (`payout_request_id`) REFERENCES `payout_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payout_requests`
--
ALTER TABLE `payout_requests`
  ADD CONSTRAINT `fk_payout_requests_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payout_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payout_requests_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `platform_notifications`
--
ALTER TABLE `platform_notifications`
  ADD CONSTRAINT `fk_platform_notifications_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `platform_notification_reads`
--
ALTER TABLE `platform_notification_reads`
  ADD CONSTRAINT `fk_platform_notification_reads_notification` FOREIGN KEY (`notification_id`) REFERENCES `platform_notifications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_platform_notification_reads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_products_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_analytics`
--
ALTER TABLE `product_analytics`
  ADD CONSTRAINT `fk_product_analytics_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_approvals`
--
ALTER TABLE `product_approvals`
  ADD CONSTRAINT `fk_product_approvals_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_approvals_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_product_approvals_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD CONSTRAINT `fk_attributes_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_audit_logs`
--
ALTER TABLE `product_audit_logs`
  ADD CONSTRAINT `fk_product_audit_logs_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_bulk_operations`
--
ALTER TABLE `product_bulk_operations`
  ADD CONSTRAINT `fk_product_bulk_ops_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_bulk_uploads`
--
ALTER TABLE `product_bulk_uploads`
  ADD CONSTRAINT `fk_product_bulk_uploads_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `fk_pc_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pc_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product_certificates`
--
ALTER TABLE `product_certificates`
  ADD CONSTRAINT `fk_product_certificates_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_drafts`
--
ALTER TABLE `product_drafts`
  ADD CONSTRAINT `fk_product_drafts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_inventory`
--
ALTER TABLE `product_inventory`
  ADD CONSTRAINT `fk_product_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_media`
--
ALTER TABLE `product_media`
  ADD CONSTRAINT `fk_product_media_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_pricing`
--
ALTER TABLE `product_pricing`
  ADD CONSTRAINT `fk_product_pricing_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_recommendations`
--
ALTER TABLE `product_recommendations`
  ADD CONSTRAINT `fk_product_recommendations_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_recommendations_recommended` FOREIGN KEY (`recommended_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_recommendations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_relations`
--
ALTER TABLE `product_relations`
  ADD CONSTRAINT `fk_product_relations_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_relations_related_product` FOREIGN KEY (`related_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `fk_product_reviews_moderator` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_product_reviews_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_product_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_seo`
--
ALTER TABLE `product_seo`
  ADD CONSTRAINT `fk_product_seo_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_shipping`
--
ALTER TABLE `product_shipping`
  ADD CONSTRAINT `fk_product_shipping_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_views`
--
ALTER TABLE `product_views`
  ADD CONSTRAINT `fk_product_views_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_views_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD CONSTRAINT `fk_push_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reconciliations`
--
ALTER TABLE `reconciliations`
  ADD CONSTRAINT `fk_reconciliations_user` FOREIGN KEY (`reconciled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `redirects`
--
ALTER TABLE `redirects`
  ADD CONSTRAINT `fk_redirects_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `refunds`
--
ALTER TABLE `refunds`
  ADD CONSTRAINT `fk_refunds_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_refunds_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_refunds_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_refunds_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `report_jobs`
--
ALTER TABLE `report_jobs`
  ADD CONSTRAINT `fk_report_jobs_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reviews_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_responder` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_helpfulness`
--
ALTER TABLE `review_helpfulness`
  ADD CONSTRAINT `fk_review_helpfulness_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_helpfulness_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `search_queries`
--
ALTER TABLE `search_queries`
  ADD CONSTRAINT `fk_search_queries_product` FOREIGN KEY (`clicked_product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_search_queries_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD CONSTRAINT `fk_security_logs_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_security_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `seller_analytics`
--
ALTER TABLE `seller_analytics`
  ADD CONSTRAINT `fk_seller_analytics_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_bank_details`
--
ALTER TABLE `seller_bank_details`
  ADD CONSTRAINT `fk_seller_bank_details_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_campaigns`
--
ALTER TABLE `seller_campaigns`
  ADD CONSTRAINT `fk_seller_campaigns_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_campaign_assets`
--
ALTER TABLE `seller_campaign_assets`
  ADD CONSTRAINT `fk_seller_campaign_assets_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `seller_campaigns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_campaign_stats`
--
ALTER TABLE `seller_campaign_stats`
  ADD CONSTRAINT `fk_seller_campaign_stats_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `seller_campaigns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_chat_messages`
--
ALTER TABLE `seller_chat_messages`
  ADD CONSTRAINT `fk_seller_chat_messages_stream` FOREIGN KEY (`stream_id`) REFERENCES `seller_live_streams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_chat_messages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `seller_commissions`
--
ALTER TABLE `seller_commissions`
  ADD CONSTRAINT `fk_seller_commissions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_commissions_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_commissions_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_commissions_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_coupons`
--
ALTER TABLE `seller_coupons`
  ADD CONSTRAINT `fk_seller_coupons_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_coupon_redemptions`
--
ALTER TABLE `seller_coupon_redemptions`
  ADD CONSTRAINT `fk_seller_coupon_redemptions_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `seller_coupons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_coupon_redemptions_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_coupon_redemptions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_coupon_rules`
--
ALTER TABLE `seller_coupon_rules`
  ADD CONSTRAINT `fk_seller_coupon_rules_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `seller_coupons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_disputes`
--
ALTER TABLE `seller_disputes`
  ADD CONSTRAINT `fk_seller_disputes_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_disputes_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_seller_disputes_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_seller_disputes_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_dispute_evidence`
--
ALTER TABLE `seller_dispute_evidence`
  ADD CONSTRAINT `fk_seller_dispute_evidence_dispute` FOREIGN KEY (`dispute_id`) REFERENCES `seller_disputes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_dispute_evidence_user` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_dispute_messages`
--
ALTER TABLE `seller_dispute_messages`
  ADD CONSTRAINT `fk_seller_dispute_messages_dispute` FOREIGN KEY (`dispute_id`) REFERENCES `seller_disputes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_dispute_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_documents`
--
ALTER TABLE `seller_documents`
  ADD CONSTRAINT `fk_seller_documents_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_seller_documents_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_inventory`
--
ALTER TABLE `seller_inventory`
  ADD CONSTRAINT `fk_seller_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_inventory_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_seller_inventory_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_kpis`
--
ALTER TABLE `seller_kpis`
  ADD CONSTRAINT `fk_seller_kpis_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_kyc`
--
ALTER TABLE `seller_kyc`
  ADD CONSTRAINT `fk_seller_kyc_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_kyc_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `seller_live_streams`
--
ALTER TABLE `seller_live_streams`
  ADD CONSTRAINT `fk_seller_live_streams_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_messages`
--
ALTER TABLE `seller_messages`
  ADD CONSTRAINT `fk_seller_messages_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_messages_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_seller_messages_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_message_templates`
--
ALTER TABLE `seller_message_templates`
  ADD CONSTRAINT `fk_seller_message_templates_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_notifications`
--
ALTER TABLE `seller_notifications`
  ADD CONSTRAINT `fk_seller_notifications_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_orders`
--
ALTER TABLE `seller_orders`
  ADD CONSTRAINT `fk_seller_orders_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_orders_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_order_items`
--
ALTER TABLE `seller_order_items`
  ADD CONSTRAINT `fk_seller_order_items_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_order_items_seller_order` FOREIGN KEY (`seller_order_id`) REFERENCES `seller_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_payouts`
--
ALTER TABLE `seller_payouts`
  ADD CONSTRAINT `fk_seller_payouts_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_seller_payouts_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_payout_requests`
--
ALTER TABLE `seller_payout_requests`
  ADD CONSTRAINT `fk_seller_payout_requests_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_seller_payout_requests_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_performance_metrics`
--
ALTER TABLE `seller_performance_metrics`
  ADD CONSTRAINT `fk_seller_performance_metrics_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_products`
--
ALTER TABLE `seller_products`
  ADD CONSTRAINT `fk_seller_products_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_seller_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_products_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_product_media`
--
ALTER TABLE `seller_product_media`
  ADD CONSTRAINT `fk_seller_product_media_product` FOREIGN KEY (`seller_product_id`) REFERENCES `seller_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_product_variants`
--
ALTER TABLE `seller_product_variants`
  ADD CONSTRAINT `fk_seller_product_variants_product` FOREIGN KEY (`seller_product_id`) REFERENCES `seller_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_profiles`
--
ALTER TABLE `seller_profiles`
  ADD CONSTRAINT `fk_seller_profiles_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_reports_jobs`
--
ALTER TABLE `seller_reports_jobs`
  ADD CONSTRAINT `fk_seller_reports_jobs_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_rmas`
--
ALTER TABLE `seller_rmas`
  ADD CONSTRAINT `fk_seller_rmas_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_rmas_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_rmas_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_rma_notes`
--
ALTER TABLE `seller_rma_notes`
  ADD CONSTRAINT `fk_seller_rma_notes_rma` FOREIGN KEY (`rma_id`) REFERENCES `seller_rmas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_rma_notes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_sales_reports`
--
ALTER TABLE `seller_sales_reports`
  ADD CONSTRAINT `fk_seller_sales_reports_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_shipping_rates`
--
ALTER TABLE `seller_shipping_rates`
  ADD CONSTRAINT `fk_seller_shipping_rates_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_shipping_rates_zone` FOREIGN KEY (`shipping_zone_id`) REFERENCES `seller_shipping_zones` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_shipping_zones`
--
ALTER TABLE `seller_shipping_zones`
  ADD CONSTRAINT `fk_seller_shipping_zones_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_staff`
--
ALTER TABLE `seller_staff`
  ADD CONSTRAINT `fk_seller_staff_inviter` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_staff_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_stock_logs`
--
ALTER TABLE `seller_stock_logs`
  ADD CONSTRAINT `fk_seller_stock_logs_inventory` FOREIGN KEY (`inventory_id`) REFERENCES `seller_inventory` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_stock_logs_user` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_stream_products`
--
ALTER TABLE `seller_stream_products`
  ADD CONSTRAINT `fk_seller_stream_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seller_stream_products_stream` FOREIGN KEY (`stream_id`) REFERENCES `seller_live_streams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `fk_settings_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `fk_shipments_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_shipments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_shipments_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `shipment_items`
--
ALTER TABLE `shipment_items`
  ADD CONSTRAINT `fk_shipment_items_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_shipment_items_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `stream_events`
--
ALTER TABLE `stream_events`
  ADD CONSTRAINT `fk_stream_events_stream` FOREIGN KEY (`stream_id`) REFERENCES `live_streams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_stream_events_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stream_products`
--
ALTER TABLE `stream_products`
  ADD CONSTRAINT `fk_stream_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_stream_products_stream` FOREIGN KEY (`stream_id`) REFERENCES `live_streams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stream_viewers`
--
ALTER TABLE `stream_viewers`
  ADD CONSTRAINT `fk_stream_viewers_stream` FOREIGN KEY (`stream_id`) REFERENCES `live_streams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_stream_viewers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `fk_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `fk_support_tickets_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_support_tickets_escalated` FOREIGN KEY (`escalated_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_support_tickets_order` FOREIGN KEY (`related_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_support_tickets_product` FOREIGN KEY (`related_product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_support_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `support_ticket_replies`
--
ALTER TABLE `support_ticket_replies`
  ADD CONSTRAINT `fk_support_ticket_replies_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_support_ticket_replies_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `system_alerts`
--
ALTER TABLE `system_alerts`
  ADD CONSTRAINT `fk_system_alerts_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `system_events`
--
ALTER TABLE `system_events`
  ADD CONSTRAINT `fk_system_events_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `fk_system_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `templates`
--
ALTER TABLE `templates`
  ADD CONSTRAINT `fk_templates_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_transactions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `unsubscribe_links`
--
ALTER TABLE `unsubscribe_links`
  ADD CONSTRAINT `fk_unsubscribe_links_message` FOREIGN KEY (`message_id`) REFERENCES `comm_messages` (`message_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_unsubscribe_links_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD CONSTRAINT `fk_user_activities_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `user_audit_logs`
--
ALTER TABLE `user_audit_logs`
  ADD CONSTRAINT `fk_user_audit_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_documents`
--
ALTER TABLE `user_documents`
  ADD CONSTRAINT `fk_user_documents_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_documents_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_follows`
--
ALTER TABLE `user_follows`
  ADD CONSTRAINT `fk_user_follows_follower` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_follows_following` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_logins`
--
ALTER TABLE `user_logins`
  ADD CONSTRAINT `fk_user_logins_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `fk_user_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_user_roles_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_two_factor_auth`
--
ALTER TABLE `user_two_factor_auth`
  ADD CONSTRAINT `fk_user_two_factor_auth_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendors`
--
ALTER TABLE `vendors`
  ADD CONSTRAINT `fk_vendors_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_vendors_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_commissions`
--
ALTER TABLE `vendor_commissions`
  ADD CONSTRAINT `fk_vendor_commissions_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_vendor_commissions_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_payouts`
--
ALTER TABLE `vendor_payouts`
  ADD CONSTRAINT `fk_vendor_payouts_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_vendor_payouts_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallets`
--
ALTER TABLE `wallets`
  ADD CONSTRAINT `fk_wallets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_entries`
--
ALTER TABLE `wallet_entries`
  ADD CONSTRAINT `fk_wallet_entries_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_wallet_entries_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wallet_transactions_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD CONSTRAINT `fk_warehouses_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `webhook_subscriptions`
--
ALTER TABLE `webhook_subscriptions`
  ADD CONSTRAINT `fk_webhook_subscriptions_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `fk_wishlists_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wishlists_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Indexes and Constraints for Missing Tables Section
-- Added based on comprehensive codebase analysis per user request
--

--
-- Indexes for table `seller_wallets`
--
ALTER TABLE `seller_wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_vendor_wallet` (`vendor_id`),
  ADD KEY `idx_seller_wallets_balance` (`balance`),
  ADD KEY `idx_seller_wallets_created` (`created_at`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_role_name` (`name`),
  ADD KEY `idx_role_active` (`is_active`),
  ADD KEY `idx_role_level` (`level`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_permission_name` (`name`),
  ADD KEY `idx_permission_category` (`category`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_role_permission` (`role_id`, `permission_id`),
  ADD KEY `idx_role_permissions_permission` (`permission_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_product_wishlist` (`user_id`, `product_id`),
  ADD KEY `idx_user_wishlist` (`user_id`),
  ADD KEY `idx_product_wishlist` (`product_id`),
  ADD KEY `idx_wishlist_created` (`created_at`);

--
-- Indexes for table `mail_queue`
--
ALTER TABLE `mail_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mail_status` (`status`),
  ADD KEY `idx_mail_created` (`created_at`),
  ADD KEY `idx_mail_to_email` (`to_email`),
  ADD KEY `idx_mail_template` (`template_name`);

--
-- Indexes for table `email_log`
--
ALTER TABLE `email_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_log_status` (`status`),
  ADD KEY `idx_email_log_sent` (`sent_at`),
  ADD KEY `idx_email_log_to_email` (`to_email`);

--
-- Indexes for table `returns`
--
ALTER TABLE `returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_return_number` (`return_number`),
  ADD KEY `idx_return_order` (`order_id`),
  ADD KEY `idx_return_user` (`user_id`),
  ADD KEY `idx_return_vendor` (`vendor_id`),
  ADD KEY `idx_return_status` (`status`),
  ADD KEY `idx_return_created` (`created_at`),
  ADD KEY `idx_return_processed_by` (`processed_by`);

--
-- Indexes for table `loyalty_accounts`
--
ALTER TABLE `loyalty_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_program` (`user_id`, `program_name`),
  ADD KEY `idx_loyalty_tier` (`tier`),
  ADD KEY `idx_loyalty_status` (`status`);

--
-- Indexes for table `loyalty_ledger`
--
ALTER TABLE `loyalty_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_loyalty_account` (`account_id`),
  ADD KEY `idx_loyalty_type` (`transaction_type`),
  ADD KEY `idx_loyalty_created` (`created_at`),
  ADD KEY `idx_loyalty_ledger_processor` (`processed_by`);

--
-- Indexes for table `loyalty_settings`
--
ALTER TABLE `loyalty_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_loyalty_setting_key` (`setting_key`),
  ADD KEY `idx_loyalty_settings_category` (`category`),
  ADD KEY `idx_loyalty_settings_user` (`updated_by`);

--
-- Indexes for table `loyalty_rewards`
--
ALTER TABLE `loyalty_rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_loyalty_rewards_active` (`is_active`),
  ADD KEY `idx_loyalty_rewards_points` (`points_required`),
  ADD KEY `idx_loyalty_rewards_creator` (`created_by`),
  ADD KEY `idx_loyalty_rewards_type` (`reward_type`);

--
-- Indexes for table `loyalty_redemptions`
--
ALTER TABLE `loyalty_redemptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_loyalty_redemption_account` (`account_id`),
  ADD KEY `idx_loyalty_redemption_reward` (`reward_id`),
  ADD KEY `idx_loyalty_redemption_order` (`order_id`),
  ADD KEY `idx_loyalty_redemption_status` (`status`);

--
-- Indexes for table `kyc_verifications`
--
ALTER TABLE `kyc_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kyc_user` (`user_id`),
  ADD KEY `idx_kyc_status` (`status`),
  ADD KEY `idx_kyc_type` (`verification_type`),
  ADD KEY `idx_kyc_reviewer` (`reviewer_id`);

--
-- Indexes for table `integrations`
--
ALTER TABLE `integrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_integration_name` (`name`),
  ADD KEY `idx_integration_type` (`type`),
  ADD KEY `idx_integration_status` (`status`),
  ADD KEY `idx_integration_installer` (`installed_by`);

--
-- Indexes for table `webhook_deliveries`
--
ALTER TABLE `webhook_deliveries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_webhook_integration` (`integration_id`),
  ADD KEY `idx_webhook_status` (`status`),
  ADD KEY `idx_webhook_event` (`event_type`),
  ADD KEY `idx_webhook_next_attempt` (`next_attempt`);

--
-- Indexes for table `order_status_logs`
--
ALTER TABLE `order_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_status_order` (`order_id`),
  ADD KEY `idx_order_status_changed_by` (`changed_by`),
  ADD KEY `idx_order_status_created` (`created_at`);

--
-- Indexes for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_tracking_order` (`order_id`),
  ADD KEY `idx_order_tracking_number` (`tracking_number`),
  ADD KEY `idx_order_tracking_status` (`status`);

--
-- Indexes for table `canned_responses`
--
ALTER TABLE `canned_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_canned_responses_category` (`category`),
  ADD KEY `idx_canned_responses_active` (`is_active`),
  ADD KEY `idx_canned_responses_creator` (`created_by`);

--
-- Indexes for table `product_related`
--
ALTER TABLE `product_related`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_product_related_unique` (`product_id`, `related_product_id`, `relation_type`),
  ADD KEY `idx_product_related_product` (`product_id`),
  ADD KEY `idx_product_related_related` (`related_product_id`),
  ADD KEY `idx_product_related_type` (`relation_type`);

--
-- Constraints for Missing Tables Section
--

--
-- Constraints for table `seller_wallets`
--
ALTER TABLE `seller_wallets`
  ADD CONSTRAINT `fk_seller_wallets_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `returns`
--
ALTER TABLE `returns`
  ADD CONSTRAINT `fk_returns_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_returns_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_returns_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_returns_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `loyalty_accounts`
--
ALTER TABLE `loyalty_accounts`
  ADD CONSTRAINT `fk_loyalty_accounts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loyalty_ledger`
--
ALTER TABLE `loyalty_ledger`
  ADD CONSTRAINT `fk_loyalty_ledger_account` FOREIGN KEY (`account_id`) REFERENCES `loyalty_accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_loyalty_ledger_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `loyalty_settings`
--
ALTER TABLE `loyalty_settings`
  ADD CONSTRAINT `fk_loyalty_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `loyalty_rewards`
--
ALTER TABLE `loyalty_rewards`
  ADD CONSTRAINT `fk_loyalty_rewards_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loyalty_redemptions`
--
ALTER TABLE `loyalty_redemptions`
  ADD CONSTRAINT `fk_loyalty_redemptions_account` FOREIGN KEY (`account_id`) REFERENCES `loyalty_accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_loyalty_redemptions_reward` FOREIGN KEY (`reward_id`) REFERENCES `loyalty_rewards` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_loyalty_redemptions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `kyc_verifications`
--
ALTER TABLE `kyc_verifications`
  ADD CONSTRAINT `fk_kyc_verifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_kyc_verifications_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `integrations`
--
ALTER TABLE `integrations`
  ADD CONSTRAINT `fk_integrations_installer` FOREIGN KEY (`installed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `webhook_deliveries`
--
ALTER TABLE `webhook_deliveries`
  ADD CONSTRAINT `fk_webhook_deliveries_integration` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_status_logs`
--
ALTER TABLE `order_status_logs`
  ADD CONSTRAINT `fk_order_status_logs_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_status_logs_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD CONSTRAINT `fk_order_tracking_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `canned_responses`
--
ALTER TABLE `canned_responses`
  ADD CONSTRAINT `fk_canned_responses_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_related`
--
ALTER TABLE `product_related`
  ADD CONSTRAINT `fk_product_related_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_related_related_product` FOREIGN KEY (`related_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
