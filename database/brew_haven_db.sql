-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2026 at 04:44 PM
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
-- Database: `brew_haven_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `active_super_admin_session`
--

CREATE TABLE `active_super_admin_session` (
  `id` int(11) NOT NULL DEFAULT 1,
  `user_id_number` varchar(50) NOT NULL,
  `session_id` varchar(128) NOT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_privileges`
--

CREATE TABLE `admin_privileges` (
  `admin_id_number` varchar(50) NOT NULL,
  `privilege_key` varchar(64) NOT NULL,
  `granted_by_id_number` varchar(50) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_privileges`
--

INSERT INTO `admin_privileges` (`admin_id_number`, `privilege_key`, `granted_by_id_number`, `granted_at`) VALUES
('2026-0002', 'approve_accounts', '2025-0005', '2026-08-20 06:56:29'),
('2026-0002', 'block_accounts', '2025-0005', '2026-08-20 06:56:29'),
('2026-0002', 'manage_orders', '2025-0005', '2026-08-20 05:56:53'),
('2026-0002', 'manage_products', '2025-0005', '2026-08-20 05:56:53'),
('2026-0002', 'manage_users', '2025-0005', '2026-08-20 06:56:29'),
('2026-0002', 'update_user_info', '2025-0005', '2026-08-20 06:56:29'),
('2026-0004', 'approve_accounts', '2025-0005', '2026-08-24 08:19:53'),
('2026-0004', 'block_accounts', '2025-0005', '2026-08-24 08:19:53'),
('2026-0004', 'manage_orders', '2025-0005', '2026-08-24 08:19:53'),
('2026-0004', 'manage_products', '2025-0005', '2026-08-24 08:19:53'),
('2026-0004', 'manage_users', '2025-0005', '2026-08-24 08:19:53'),
('2026-0004', 'update_user_info', '2025-0005', '2026-08-24 08:19:53'),
('2026-0007', 'approve_accounts', '2025-0005', '2026-09-15 05:37:14'),
('2026-0007', 'block_accounts', '2025-0005', '2026-09-15 05:37:14'),
('2026-0007', 'manage_orders', '2025-0005', '2026-09-15 05:37:14'),
('2026-0007', 'manage_products', '2025-0005', '2026-09-15 05:37:14'),
('2026-0007', 'manage_users', '2025-0005', '2026-09-15 05:37:14'),
('2026-0007', 'update_user_info', '2025-0005', '2026-09-15 05:37:14'),
('2026-0008', 'approve_accounts', '2025-0005', '2026-09-09 04:39:59'),
('2026-0008', 'block_accounts', '2025-0005', '2026-09-09 09:32:01'),
('2026-0008', 'manage_orders', '2025-0005', '2026-09-09 04:39:59'),
('2026-0008', 'manage_products', '2025-0005', '2026-09-09 04:39:59'),
('2026-0008', 'manage_users', '2025-0005', '2026-09-09 04:39:59'),
('2026-0008', 'update_user_info', '2025-0005', '2026-09-09 05:24:32'),
('2026-0009', 'approve_accounts', '2025-0005', '2026-09-10 05:05:54'),
('2026-0009', 'block_accounts', '2025-0005', '2026-09-10 05:05:54'),
('2026-0009', 'manage_orders', '2025-0005', '2026-09-10 05:05:54'),
('2026-0009', 'manage_products', '2025-0005', '2026-09-10 05:05:54'),
('2026-0009', 'manage_users', '2025-0005', '2026-09-10 05:05:54'),
('2026-0009', 'update_user_info', '2025-0005', '2026-09-10 05:05:54'),
('2026-0010', 'approve_accounts', '2025-0005', '2026-09-15 05:53:55'),
('2026-0010', 'block_accounts', '2025-0005', '2026-09-15 05:53:55'),
('2026-0010', 'change_account_roles', '2026-0006', '2026-09-16 13:51:20'),
('2026-0010', 'manage_orders', '2025-0005', '2026-09-15 05:53:55'),
('2026-0010', 'manage_products', '2025-0005', '2026-09-15 05:53:55'),
('2026-0010', 'manage_users', '2025-0005', '2026-09-15 05:53:55'),
('2026-0010', 'reset_passwords', '2026-0006', '2026-09-15 09:31:59'),
('2026-0010', 'update_user_info', '2025-0005', '2026-09-15 05:53:55');

-- --------------------------------------------------------

--
-- Table structure for table `delete_requests`
--

CREATE TABLE `delete_requests` (
  `id` int(11) NOT NULL,
  `target_id_number` varchar(50) NOT NULL,
  `requested_by_id_number` varchar(50) NOT NULL,
  `reason` text NOT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by_id_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delete_requests`
--

INSERT INTO `delete_requests` (`id`, `target_id_number`, `requested_by_id_number`, `reason`, `proof_path`, `status`, `reviewed_by_id_number`, `created_at`, `reviewed_at`) VALUES
(3, '2025-0004', '2026-0002', 'wala nagamit', NULL, 'approved', '2025-0005', '2026-08-20 06:57:08', '2026-08-20 06:58:16'),
(4, '2099-9010', '2026-0002', 'Verification test request', NULL, 'approved', '2025-0005', '2026-08-20 08:11:33', '2026-08-20 08:12:01'),
(5, '2026-0003', '2026-0008', 'Way buot', NULL, 'approved', '2025-0005', '2026-09-10 04:58:48', '2026-09-10 04:59:47'),
(6, '2025-0003', '2026-0010', 'Way klaro na tao', '20260915-081201_delete_request_2026-0010_181a1dfaabad1a3b.pdf', 'approved', '2025-0005', '2026-09-15 06:12:01', '2026-09-15 06:14:35');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(20) NOT NULL,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 10,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `name`, `quantity`, `unit`, `low_stock_threshold`, `updated_at`) VALUES
(1, 'Coffee Beans', 50, 'kg', 10, '2026-08-20 02:27:42'),
(2, 'Milk', 80, 'liters', 15, '2026-08-20 02:27:42'),
(3, 'Sugar', 40, 'kg', 10, '2026-08-20 02:27:42'),
(4, 'Chocolate Syrup', 20, 'bottles', 5, '2026-08-20 02:27:42'),
(5, 'Caramel Syrup', 20, 'bottles', 5, '2026-08-20 02:27:42'),
(6, 'Vanilla Syrup', 20, 'bottles', 5, '2026-08-20 02:27:42'),
(7, 'Cups', 500, 'pcs', 100, '2026-08-20 02:27:42'),
(8, 'Lids', 500, 'pcs', 100, '2026-08-20 02:27:42');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id_number` varchar(50) NOT NULL,
  `order_type` enum('Dine In','Takeout') NOT NULL DEFAULT 'Dine In',
  `table_number` varchar(10) DEFAULT NULL,
  `status` enum('Pending','Preparing','Ready','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `total_amount` decimal(8,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id_number`, `order_type`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES
(2, '2026-0001', 'Dine In', NULL, 'Pending', 89.00, '2026-08-21 04:37:34', '2026-08-21 04:37:34'),
(3, '2026-0001', 'Dine In', NULL, 'Completed', 109.00, '2026-08-21 04:38:49', '2026-08-21 11:51:51'),
(5, '2026-0001', 'Dine In', '1', 'Ready', 89.00, '2026-08-21 04:51:35', '2026-08-21 11:51:47'),
(6, '2026-0001', 'Takeout', NULL, 'Preparing', 109.00, '2026-08-21 04:53:09', '2026-08-21 11:51:43'),
(7, '2026-0005', 'Takeout', NULL, 'Pending', 198.00, '2026-09-15 08:09:41', '2026-09-15 08:09:41'),
(10, '2026-0005', 'Dine In', '12', 'Preparing', 109.00, '2026-09-15 08:33:48', '2026-09-15 08:38:44');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size` enum('Small','Medium','Large') NOT NULL DEFAULT 'Medium',
  `temperature` enum('Hot','Iced') NOT NULL DEFAULT 'Hot',
  `sweetness` enum('Regular','Less Sweet','No Sugar') NOT NULL DEFAULT 'Regular',
  `addons` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(8,2) NOT NULL,
  `subtotal` decimal(8,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `size`, `temperature`, `sweetness`, `addons`, `quantity`, `unit_price`, `subtotal`) VALUES
(2, 2, 1, 'Small', 'Hot', 'No Sugar', 'Extra Espresso', 1, 89.00, 89.00),
(3, 3, 2, 'Medium', 'Hot', 'Regular', NULL, 1, 109.00, 109.00),
(5, 5, 1, 'Small', 'Hot', 'Regular', 'Vanilla Syrup', 1, 89.00, 89.00),
(6, 6, 2, 'Medium', 'Hot', 'Regular', NULL, 1, 109.00, 109.00),
(7, 7, 1, 'Medium', 'Hot', 'Regular', NULL, 1, 89.00, 89.00),
(8, 7, 3, 'Medium', 'Hot', 'Regular', 'Caramel Syrup', 1, 109.00, 109.00),
(11, 10, 2, 'Medium', 'Hot', 'Regular', NULL, 1, 109.00, 109.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('Hot Coffee','Iced Coffee','Non-Coffee','Add-ons') NOT NULL,
  `price` decimal(8,2) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `category`, `price`, `image_path`, `is_available`, `created_at`) VALUES
(1, 'Americano', 'Rich espresso with hot water.', 'Hot Coffee', 89.00, 'https://images.unsplash.com/photo-1521302080334-4bebac2763a6?w=600&q=80', 1, '2026-08-20 02:27:42'),
(2, 'Cafe Latte', 'Smooth espresso with steamed milk.', 'Hot Coffee', 109.00, 'https://images.unsplash.com/photo-1512568400610-62da28bc8a13?w=600&q=80', 1, '2026-08-20 02:27:42'),
(3, 'Cappuccino', 'Espresso with steamed milk and rich foam.', 'Hot Coffee', 109.00, 'https://images.unsplash.com/photo-1497935586351-b67a49e012bf?w=600&q=80', 1, '2026-08-20 02:27:42'),
(4, 'Caramel Macchiato', 'Espresso with creamy milk and caramel flavor.', 'Hot Coffee', 129.00, 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=600&q=80', 1, '2026-08-20 02:27:42'),
(5, 'Mocha', 'Chocolate and espresso blended with milk.', 'Hot Coffee', 129.00, 'https://images.unsplash.com/photo-1442512595331-e89e73853f31?w=600&q=80', 1, '2026-08-20 02:27:42'),
(6, 'Spanish Latte', 'Rich espresso with sweetened milk.', 'Hot Coffee', 119.00, 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=600&q=80', 1, '2026-08-20 02:27:42'),
(7, 'Vanilla Latte', 'Espresso with steamed milk and vanilla flavor.', 'Hot Coffee', 119.00, 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=600&q=80', 1, '2026-08-20 02:27:42'),
(8, 'Iced Coffee', 'Smooth chilled coffee served over ice.', 'Iced Coffee', 99.00, 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=600&q=80', 1, '2026-08-20 02:27:42'),
(9, 'Hot Chocolate', 'Creamy chocolate, no coffee.', 'Non-Coffee', 99.00, 'https://images.unsplash.com/photo-1517578239113-b03992dcdd25?w=600&q=80', 1, '2026-08-20 02:27:42'),
(10, 'Matcha Latte', 'Earthy matcha with steamed milk.', 'Non-Coffee', 119.00, 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?w=600&q=80', 1, '2026-08-20 02:27:42');

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `actor_id_number` varchar(50) DEFAULT NULL,
  `target_id_number` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `previous_state` varchar(255) DEFAULT NULL,
  `new_state` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `security_logs`
--

INSERT INTO `security_logs` (`id`, `event_type`, `actor_id_number`, `target_id_number`, `ip_address`, `user_agent`, `details`, `reason`, `proof_path`, `previous_state`, `new_state`, `created_at`) VALUES
(1, 'login_success', '2025-0005', '2025-0005', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 02:53:08'),
(2, 'login_failed', NULL, NULL, NULL, NULL, 'unknown username', NULL, NULL, NULL, NULL, '2026-08-20 02:56:15'),
(24, 'login_success', '2025-0005', '2025-0005', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 03:14:47'),
(25, 'login_success', '2025-0005', '2025-0005', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 03:16:53'),
(26, 'login_success', '2025-0005', '2025-0005', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 03:50:02'),
(27, 'account_blocked', '2025-0005', '2025-0004', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 03:53:26'),
(28, 'account_unblocked', '2025-0005', '2025-0004', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 03:53:28'),
(29, 'login_success', '2025-0005', '2025-0005', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 03:55:11'),
(34, 'login_success', '2025-0005', '2025-0005', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 04:01:30'),
(38, 'login_success', '2025-0005', '2025-0005', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 04:05:38'),
(39, 'login_success', '2025-0005', '2025-0005', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 04:24:38'),
(40, 'login_success', '2025-0005', '2025-0005', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 05:01:42'),
(41, 'registration_submitted', '2026-0001', '2026-0001', NULL, NULL, 'New customer registration pending approval', NULL, NULL, NULL, NULL, '2026-08-20 05:05:26'),
(42, 'login_success', '2025-0005', '2025-0005', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 05:05:46'),
(43, 'account_approved', '2025-0005', '2026-0001', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 05:05:53'),
(44, 'login_success', '2026-0001', '2026-0001', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 05:06:18'),
(45, 'login_failed', NULL, NULL, NULL, NULL, 'unknown username', NULL, NULL, NULL, NULL, '2026-08-20 05:44:24'),
(46, 'login_failed', NULL, NULL, NULL, NULL, 'unknown username', NULL, NULL, NULL, NULL, '2026-08-20 05:44:28'),
(47, 'login_failed', NULL, '2026-0001', NULL, NULL, 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-20 05:44:32'),
(48, 'login_success', '2026-0001', '2026-0001', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 05:44:53'),
(49, 'login_success', '2025-0005', '2025-0005', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 05:50:51'),
(50, 'login_success', '2025-0005', '2025-0005', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 05:55:09'),
(51, 'login_success', '2025-0005', '2025-0005', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 05:56:52'),
(52, 'staff_account_created', '2025-0005', '2026-0002', NULL, NULL, 'role=admin employee_id=EMP-0002', NULL, NULL, NULL, NULL, '2026-08-20 05:56:53'),
(53, 'account_updated', '2025-0005', '2026-0002', NULL, NULL, 'fields: lastname,firstname,middlename,birth_date,age,gender,email,street,barangay,city_municipality,province,zipcode,country', NULL, NULL, NULL, NULL, '2026-08-20 05:58:39'),
(54, 'login_success', '2025-0005', '2025-0005', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 05:58:56'),
(55, 'login_success', '2026-0002', '2026-0002', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 05:59:11'),
(56, 'login_success', '2025-0005', '2025-0005', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 06:03:24'),
(57, 'security_questions_setup', '2025-0005', '2025-0005', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 06:04:00'),
(61, 'login_failed', NULL, '2026-0001', NULL, NULL, 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-20 06:04:40'),
(63, 'login_success', '2025-0005', '2025-0005', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 06:05:11'),
(64, 'login_success', '2026-0002', '2026-0002', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 06:05:46'),
(65, 'login_success', '2025-0005', '2025-0005', NULL, NULL, '', NULL, NULL, NULL, NULL, '2026-08-20 06:06:22'),
(68, 'login_success', '2026-0001', '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 06:17:42'),
(69, 'login_success', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', '', NULL, NULL, NULL, NULL, '2026-08-20 06:25:01'),
(75, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 06:26:11'),
(76, 'login_success', '2026-0001', '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 06:27:05'),
(77, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 06:27:08'),
(78, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 06:27:45'),
(79, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 06:35:00'),
(80, 'password_reset_by_admin', '2025-0005', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 06:39:15'),
(81, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 06:40:53'),
(82, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 06:44:31'),
(83, 'privilege_revoked', '2025-0005', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'privilege=manage_users', NULL, NULL, NULL, NULL, '2026-08-20 06:54:02'),
(84, 'privilege_revoked', '2025-0005', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'privilege=approve_accounts', NULL, NULL, NULL, NULL, '2026-08-20 06:54:02'),
(85, 'privilege_revoked', '2025-0005', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'privilege=block_accounts', NULL, NULL, NULL, NULL, '2026-08-20 06:54:02'),
(86, 'privilege_revoked', '2025-0005', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'privilege=update_user_info', NULL, NULL, NULL, NULL, '2026-08-20 06:54:03'),
(87, 'login_success', '2026-0002', '2026-0002', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 06:54:14'),
(88, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 06:54:40'),
(89, 'login_success', '2026-0001', '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 06:55:20'),
(90, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 06:56:06'),
(91, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 06:56:08'),
(92, 'privilege_granted', '2025-0005', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'privilege=manage_users', NULL, NULL, NULL, NULL, '2026-08-20 06:56:29'),
(93, 'privilege_granted', '2025-0005', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'privilege=approve_accounts', NULL, NULL, NULL, NULL, '2026-08-20 06:56:29'),
(94, 'privilege_granted', '2025-0005', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'privilege=block_accounts', NULL, NULL, NULL, NULL, '2026-08-20 06:56:29'),
(95, 'privilege_granted', '2025-0005', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'privilege=update_user_info', NULL, NULL, NULL, NULL, '2026-08-20 06:56:29'),
(96, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 06:56:40'),
(97, 'login_success', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', '', NULL, NULL, NULL, NULL, '2026-08-20 06:56:58'),
(98, 'delete_request_submitted', '2026-0002', '2025-0004', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'reason recorded in delete_requests table', NULL, NULL, NULL, NULL, '2026-08-20 06:57:08'),
(99, 'login_failed', NULL, '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-20 06:57:31'),
(100, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 06:57:32'),
(101, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 06:57:44'),
(102, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 06:57:51'),
(103, 'delete_request_approved', '2025-0005', '2025-0004', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'request_id=3', NULL, NULL, NULL, NULL, '2026-08-20 06:58:16'),
(104, 'account_deleted', '2025-0005', '2025-0004', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'via delete request #3', NULL, NULL, NULL, NULL, '2026-08-20 06:58:16'),
(105, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-20 07:00:48'),
(106, 'login_failed', NULL, '2026-0002', '::1', 'curl/8.21.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-20 07:01:10'),
(107, 'login_success', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', '', NULL, NULL, NULL, NULL, '2026-08-20 07:01:47'),
(108, 'password_reset_by_admin', '2025-0005', '2026-0002', '::1', 'curl/8.21.0', '', NULL, NULL, NULL, NULL, '2026-08-20 07:01:47'),
(109, 'login_success', '2026-0002', '2026-0002', '::1', 'curl/8.21.0', '', NULL, NULL, NULL, NULL, '2026-08-20 07:01:48'),
(110, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:01:59'),
(111, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:02:01'),
(113, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:18:42'),
(114, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:18:44'),
(115, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:22:02'),
(116, 'login_success', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', '', NULL, NULL, NULL, NULL, '2026-08-20 07:22:38'),
(117, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:23:25'),
(118, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:23:48'),
(119, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:25:06'),
(120, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:25:17'),
(122, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:35:04'),
(123, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:36:11'),
(124, 'login_success', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', '', NULL, NULL, NULL, NULL, '2026-08-20 07:36:24'),
(125, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:41:07'),
(127, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:43:09'),
(128, 'login_success', '2025-0005', '2025-0005', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:44:36'),
(129, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:45:01'),
(130, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:45:26'),
(131, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:47:08'),
(132, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:48:36'),
(134, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:51:48'),
(135, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:52:15'),
(136, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:53:20'),
(137, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:54:05'),
(138, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 07:54:28'),
(139, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 08:06:18'),
(141, 'login_success', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', '', NULL, NULL, NULL, NULL, '2026-08-20 08:11:08'),
(142, 'login_success', '2026-0002', '2026-0002', '::1', 'curl/8.21.0', '', NULL, NULL, NULL, NULL, '2026-08-20 08:11:21'),
(146, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 08:12:29'),
(147, 'login_success', '2026-0002', '2026-0002', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 08:12:33'),
(148, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 08:19:39'),
(149, 'login_success', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', '', NULL, NULL, NULL, NULL, '2026-08-20 08:27:01'),
(151, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 08:27:37'),
(153, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 08:36:08'),
(154, 'login_success', '2025-0005', '2025-0005', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 08:36:36'),
(156, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-20 08:37:06'),
(157, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-20 08:37:15'),
(158, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-20 08:38:51'),
(159, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 08:39:54'),
(160, 'password_reset_by_admin', '2025-0005', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 08:40:14'),
(161, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 08:40:27'),
(162, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-20 08:45:11'),
(163, 'login_failed', NULL, '2026-0002', '::1', 'curl/8.21.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-20 08:45:33'),
(164, 'login_success', '2025-0005', '2025-0005', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 08:45:49'),
(165, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 08:46:14'),
(166, 'login_success', '2026-0001', '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 08:54:06'),
(168, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-20 08:58:54'),
(174, 'login_failed', NULL, '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-21 04:33:40'),
(175, 'login_success', '2026-0001', '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-21 04:33:46'),
(177, 'order_placed', '2026-0001', '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'order_id=2', NULL, NULL, NULL, NULL, '2026-08-21 04:37:34'),
(178, 'order_placed', '2026-0001', '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'order_id=3', NULL, NULL, NULL, NULL, '2026-08-21 04:38:49'),
(182, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-21 04:50:00'),
(183, 'login_failed', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'unknown username', NULL, NULL, NULL, NULL, '2026-08-21 04:50:39'),
(184, 'login_success', '2026-0001', '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-21 04:50:44'),
(185, 'order_placed', '2026-0001', '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'order_id=5', NULL, NULL, NULL, NULL, '2026-08-21 04:51:35'),
(186, 'order_placed', '2026-0001', '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'order_id=6', NULL, NULL, NULL, NULL, '2026-08-21 04:53:09'),
(188, 'login_success', '2026-0001', '2026-0001', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-21 11:50:35'),
(189, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-21 11:51:15'),
(190, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-21 11:51:23'),
(191, 'order_status_changed', '2026-0002', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'order_id=6 status=Preparing', NULL, NULL, NULL, NULL, '2026-08-21 11:51:43'),
(192, 'order_status_changed', '2026-0002', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'order_id=5 status=Ready', NULL, NULL, NULL, NULL, '2026-08-21 11:51:47'),
(193, 'order_status_changed', '2026-0002', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'order_id=3 status=Completed', NULL, NULL, NULL, NULL, '2026-08-21 11:51:51'),
(195, 'login_success', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', '', NULL, NULL, NULL, NULL, '2026-08-24 03:49:17'),
(196, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 03:49:51'),
(198, 'registration_submitted', '2026-0003', '2026-0003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'New customer registration pending approval', NULL, NULL, NULL, NULL, '2026-08-24 03:58:28'),
(199, 'login_blocked', NULL, '2026-0003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'approval_status=pending', NULL, NULL, NULL, NULL, '2026-08-24 03:58:38'),
(200, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 03:59:58'),
(201, 'account_approved', '2026-0002', '2026-0003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 04:00:15'),
(202, 'login_success', '2026-0003', '2026-0003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 04:00:34'),
(203, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 04:01:44'),
(204, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-24 04:30:09'),
(205, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-24 04:30:45'),
(206, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-24 04:31:19'),
(207, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 04:32:45'),
(208, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-24 04:32:52'),
(209, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-24 04:33:25'),
(210, 'login_success', '2026-0002', '2026-0002', '::1', 'curl/8.21.0', '', NULL, NULL, NULL, NULL, '2026-08-24 04:34:42'),
(211, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 04:36:07'),
(212, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-24 04:36:14'),
(213, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-24 04:36:48'),
(214, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 04:38:46'),
(215, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 04:39:57'),
(216, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 04:40:13'),
(217, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-24 04:40:21'),
(218, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 04:40:25'),
(219, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 04:40:31'),
(220, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-24 04:40:56'),
(221, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 04:42:26'),
(222, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 04:43:56'),
(223, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 04:50:35'),
(224, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 04:51:51'),
(225, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 04:53:10'),
(226, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 04:54:20'),
(227, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 04:54:58'),
(228, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 04:59:38'),
(229, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 05:00:09'),
(230, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 05:00:15'),
(231, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 05:03:02'),
(232, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 05:07:55'),
(233, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 05:12:33'),
(234, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 05:18:00'),
(235, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 07:40:17'),
(236, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 07:40:46'),
(237, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 07:42:41'),
(238, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 07:44:20'),
(239, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 07:46:55'),
(240, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 07:47:44'),
(241, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 07:50:10'),
(242, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 07:57:42'),
(243, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 07:59:31'),
(244, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:00:05'),
(245, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:00:57'),
(246, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:01:18'),
(248, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-24 08:02:13'),
(249, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-24 08:02:21'),
(250, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-24 08:02:22'),
(251, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-24 08:02:54'),
(252, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-24 08:03:11'),
(253, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:05:24'),
(254, 'password_reset_by_admin', '2025-0005', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:05:46'),
(255, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:06:01'),
(256, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:09:38'),
(257, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-24 08:09:46'),
(258, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-24 08:10:03'),
(259, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:10:43'),
(260, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:11:16'),
(261, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:13:08'),
(263, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:13:16'),
(265, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:15:38'),
(266, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:15:46'),
(268, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:18:17'),
(269, 'staff_account_created', '2025-0005', '2026-0004', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'role=admin employee_id=EMP-003', NULL, NULL, NULL, NULL, '2026-08-24 08:19:53'),
(270, 'login_success', '2026-0004', '2026-0004', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:20:22'),
(271, 'security_questions_setup', '2026-0004', '2026-0004', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:20:58'),
(272, 'product_availability_toggled', '2026-0004', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'product_id=1', NULL, NULL, NULL, NULL, '2026-08-24 08:28:46'),
(273, 'product_availability_toggled', '2026-0004', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'product_id=1', NULL, NULL, NULL, NULL, '2026-08-24 08:28:50'),
(274, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:31:39'),
(275, 'logout', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:31:40'),
(276, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:31:42'),
(277, 'logout', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:31:43'),
(278, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:31:45'),
(279, 'logout', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:31:47'),
(280, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:31:49'),
(281, 'logout', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:31:52'),
(285, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:33:13'),
(286, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:47:36'),
(287, 'logout', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:47:38'),
(288, 'login_success', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:47:40'),
(289, 'logout', '2026-0002', '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:47:42'),
(292, 'logout', '2026-0004', '2026-0004', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:50:57'),
(293, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:51:26'),
(294, 'logout', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-24 08:51:51'),
(295, 'registration_submitted', '2026-0005', '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'New customer registration pending approval', NULL, NULL, NULL, NULL, '2026-08-24 08:54:58'),
(303, 'login_success', '2026-0001', '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-25 05:21:20'),
(304, 'logout', '2026-0001', '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-25 05:21:42'),
(305, 'login_failed', NULL, '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-25 05:23:07'),
(306, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-25 05:23:18'),
(307, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-27 08:05:14'),
(308, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-27 08:05:15'),
(313, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-27 08:10:09'),
(314, 'login_failed', NULL, '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-27 08:16:46'),
(315, 'password_reset_started', NULL, '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-27 08:17:00'),
(321, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-27 08:29:23'),
(322, 'password_reset_started', NULL, '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-27 08:31:35'),
(324, 'password_reset_started', NULL, '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-27 08:36:27'),
(325, 'password_reset_started', NULL, '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-27 08:40:42');
INSERT INTO `security_logs` (`id`, `event_type`, `actor_id_number`, `target_id_number`, `ip_address`, `user_agent`, `details`, `reason`, `proof_path`, `previous_state`, `new_state`, `created_at`) VALUES
(326, 'secret_question_verified', NULL, '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'correctCount=3', NULL, NULL, NULL, NULL, '2026-08-27 08:41:01'),
(328, 'login_failed', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', 'unknown username', NULL, NULL, NULL, NULL, '2026-08-27 08:48:15'),
(329, 'login_failed', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.7922.34 Safari/537.36', 'unknown username', NULL, NULL, NULL, NULL, '2026-08-27 08:48:16'),
(330, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-27 08:48:30'),
(331, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-27 08:49:14'),
(332, 'login_failed', NULL, '2026-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-08-27 08:49:16'),
(333, 'password_reset_started', NULL, '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-08-27 08:49:28'),
(334, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-03 07:03:45'),
(335, 'logout', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-03 07:18:50'),
(336, 'login_failed', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'unknown username', NULL, NULL, NULL, NULL, '2026-09-03 07:22:04'),
(337, 'login_failed', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'unknown username', NULL, NULL, NULL, NULL, '2026-09-03 07:22:05'),
(338, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 07:22:59'),
(339, 'otp_resent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-03 07:23:11'),
(340, 'otp_resent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-03 07:23:52'),
(341, 'otp_sent', NULL, '2025-0001', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 07:32:28'),
(342, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 07:34:04'),
(343, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 07:37:50'),
(344, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 07:41:06'),
(345, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 07:42:38'),
(346, 'otp_resent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-03 07:43:01'),
(347, 'otp_failed', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'incorrect otp', NULL, NULL, NULL, NULL, '2026-09-03 07:43:12'),
(348, 'otp_resent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-03 07:43:36'),
(349, 'otp_verified', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-03 07:43:58'),
(350, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 07:51:44'),
(351, 'otp_failed', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect otp', NULL, NULL, NULL, NULL, '2026-09-03 07:52:40'),
(352, 'otp_resent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-03 07:52:59'),
(353, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 07:53:42'),
(354, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 07:54:27'),
(355, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 07:55:17'),
(356, 'otp_failed', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect otp', NULL, NULL, NULL, NULL, '2026-09-03 07:55:44'),
(357, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 07:56:50'),
(358, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 07:57:40'),
(359, 'otp_failed', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect otp', NULL, NULL, NULL, NULL, '2026-09-03 07:57:59'),
(360, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 08:01:43'),
(361, 'otp_failed', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect otp', NULL, NULL, NULL, NULL, '2026-09-03 08:01:50'),
(362, 'otp_resent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-03 08:02:58'),
(363, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 08:03:15'),
(364, 'otp_failed', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect otp', NULL, NULL, NULL, NULL, '2026-09-03 08:03:23'),
(365, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 08:03:47'),
(366, 'otp_failed', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect otp', NULL, NULL, NULL, NULL, '2026-09-03 08:03:57'),
(367, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 08:04:37'),
(368, 'otp_failed', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect otp', NULL, NULL, NULL, NULL, '2026-09-03 08:04:45'),
(369, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 08:05:30'),
(370, 'otp_sent', NULL, '2025-0001', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 08:05:53'),
(371, 'otp_verified', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-03 08:06:07'),
(372, 'otp_sent', NULL, '2026-0001', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'masked_email=k****************3@gmail.com', NULL, NULL, NULL, NULL, '2026-09-03 08:08:05'),
(373, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 08:08:36'),
(374, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 08:12:36'),
(375, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 08:13:18'),
(376, 'otp_verified', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-03 08:13:59'),
(377, 'secret_question_failed', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'correctCount=0', NULL, NULL, NULL, NULL, '2026-09-03 08:15:40'),
(378, 'secret_question_failed', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'correctCount=0', NULL, NULL, NULL, NULL, '2026-09-03 08:16:05'),
(379, 'otp_sent', NULL, '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'masked_email=k****************3@gmail.com', NULL, NULL, NULL, NULL, '2026-09-03 08:17:58'),
(380, 'otp_verified', NULL, '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-03 08:19:29'),
(381, 'secret_question_verified', NULL, '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'correctCount=2', NULL, NULL, NULL, NULL, '2026-09-03 08:20:55'),
(382, 'password_reset', '2026-0001', '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'via secret question recovery', NULL, NULL, NULL, NULL, '2026-09-03 08:21:23'),
(383, 'login_success', '2026-0001', '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-03 08:22:12'),
(384, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 08:25:19'),
(385, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 08:25:42'),
(386, 'otp_failed', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect otp', NULL, NULL, NULL, NULL, '2026-09-03 08:25:55'),
(387, 'otp_sent', NULL, '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-03 08:26:36'),
(388, 'logout', '2026-0001', '2026-0001', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-03 08:31:35'),
(389, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 03:40:34'),
(398, 'account_approved', '2025-0005', '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 04:12:56'),
(399, 'account_updated', '2025-0005', '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'fields: lastname,firstname,middlename,birth_date,age,gender,email,street,barangay,city_municipality,province,zipcode,country', NULL, NULL, NULL, NULL, '2026-09-07 04:14:00'),
(411, 'logout', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 04:43:00'),
(412, 'login_failed', NULL, '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-07 04:45:30'),
(413, 'login_failed', NULL, '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-07 04:45:49'),
(414, 'login_failed', NULL, '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-07 04:45:57'),
(415, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 04:46:36'),
(480, 'login_failed', NULL, '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-07 12:24:20'),
(481, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 12:24:37'),
(482, 'staff_account_created', '2025-0005', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'role=super_admin id_number=2026-0006', NULL, NULL, NULL, NULL, '2026-09-07 12:25:51'),
(483, 'logout', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 12:26:08'),
(484, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 12:26:43'),
(485, 'logout', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 12:29:49'),
(486, 'login_failed', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'unknown username', NULL, NULL, NULL, NULL, '2026-09-07 12:30:16'),
(487, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 12:31:03'),
(488, 'account_updated', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'initial profile setup completed', NULL, NULL, NULL, NULL, '2026-09-07 12:32:06'),
(489, 'password_reset', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'first-login setup completed successfully', NULL, NULL, NULL, NULL, '2026-09-07 12:33:52'),
(490, 'first_login_completed', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 12:33:52'),
(491, 'login_success', '2026-0006', '2026-0006', '::1', NULL, '', NULL, NULL, NULL, NULL, '2026-09-07 13:29:41'),
(492, 'login_blocked', NULL, '2026-9999', '::1', NULL, 'super_admin_already_active', NULL, NULL, NULL, NULL, '2026-09-07 13:29:41'),
(493, 'logout', '2026-0006', '2026-0006', '::1', NULL, '', NULL, NULL, NULL, NULL, '2026-09-07 13:29:41'),
(494, 'login_success', '2026-9999', '2026-9999', '::1', NULL, '', NULL, NULL, NULL, NULL, '2026-09-07 13:29:42'),
(495, 'login_success', '2026-0006', '2026-0006', '::1', NULL, '', NULL, NULL, NULL, NULL, '2026-09-07 13:29:42'),
(496, 'session_terminated_super_admin_expired', '2026-0006', '2026-0006', '::1', NULL, 'session expired or superseded', NULL, NULL, NULL, NULL, '2026-09-07 13:29:42'),
(497, 'login_success', '2026-0006', '2026-0006', '::1', NULL, '', NULL, NULL, NULL, NULL, '2026-09-07 13:29:42'),
(498, 'login_success', '2026-0004', '2026-0004', '::1', NULL, '', NULL, NULL, NULL, NULL, '2026-09-07 13:29:42'),
(499, 'login_success', '2025-0001', '2025-0001', '::1', NULL, '', NULL, NULL, NULL, NULL, '2026-09-07 13:29:42'),
(500, 'login_success', '2026-0006', '2026-0006', '::1', NULL, '', NULL, NULL, NULL, NULL, '2026-09-07 13:29:43'),
(501, 'login_blocked', NULL, '2026-9999', '::1', NULL, 'super_admin_already_active', NULL, NULL, NULL, NULL, '2026-09-07 13:29:43'),
(502, 'session_terminated_super_admin_expired', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'session expired or superseded', NULL, NULL, NULL, NULL, '2026-09-07 13:30:47'),
(503, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 13:31:07'),
(504, 'session_terminated_super_admin_expired', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'session expired or superseded', NULL, NULL, NULL, NULL, '2026-09-07 14:31:20'),
(505, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 14:31:45'),
(506, 'logout', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 14:32:29'),
(507, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 14:33:14'),
(508, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'inactivity timeout exceeded 60s', NULL, NULL, NULL, NULL, '2026-09-07 14:34:49'),
(509, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 14:35:25'),
(510, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'inactivity timeout exceeded 60s', NULL, NULL, NULL, NULL, '2026-09-07 14:37:21'),
(511, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 14:45:10'),
(512, 'staff_account_created', '2026-0006', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'role=admin id_number=2026-0007', NULL, NULL, NULL, NULL, '2026-09-07 14:45:56'),
(513, 'logout', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 14:46:10'),
(514, 'login_success', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 14:46:43'),
(515, 'login_success', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 14:46:46'),
(516, 'login_success', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 14:46:50'),
(517, 'otp_sent', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'first_login_otp masked_email=t***********8@gmail.com', NULL, NULL, NULL, NULL, '2026-09-07 14:46:50'),
(518, 'otp_sent', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'first_login_otp masked_email=t***********8@gmail.com', NULL, NULL, NULL, NULL, '2026-09-07 14:46:53'),
(519, 'otp_sent', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'first_login_otp masked_email=t***********8@gmail.com', NULL, NULL, NULL, NULL, '2026-09-07 14:47:02'),
(520, 'otp_failed', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'incorrect first_login_otp attempt 1', NULL, NULL, NULL, NULL, '2026-09-07 14:47:26'),
(521, 'otp_failed', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'incorrect first_login_otp attempt 2', NULL, NULL, NULL, NULL, '2026-09-07 14:47:36'),
(522, 'otp_resent', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'first_login_otp resent', NULL, NULL, NULL, NULL, '2026-09-07 14:47:53'),
(523, 'otp_failed', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'incorrect first_login_otp attempt 1', NULL, NULL, NULL, NULL, '2026-09-07 14:48:31'),
(524, 'otp_resent', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'first_login_otp resent', NULL, NULL, NULL, NULL, '2026-09-07 14:49:35'),
(525, 'session_timeout_inactivity', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'auto-logout after 1 minute of inactivity', NULL, NULL, NULL, NULL, '2026-09-07 14:50:21'),
(526, 'login_failed', NULL, '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-07 14:50:40'),
(527, 'login_failed', NULL, '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-07 14:50:52'),
(528, 'login_success', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 14:50:57'),
(529, 'login_success', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 14:50:59'),
(530, 'login_success', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 14:51:03'),
(531, 'otp_sent', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'first_login_otp masked_email=t***********8@gmail.com', NULL, NULL, NULL, NULL, '2026-09-07 14:51:04'),
(532, 'otp_sent', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'first_login_otp masked_email=t***********8@gmail.com', NULL, NULL, NULL, NULL, '2026-09-07 14:51:06'),
(533, 'otp_sent', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'first_login_otp masked_email=t***********8@gmail.com', NULL, NULL, NULL, NULL, '2026-09-07 14:51:10'),
(534, 'session_timeout_inactivity', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'auto-logout after 1 minute of inactivity', NULL, NULL, NULL, NULL, '2026-09-07 14:52:07'),
(535, 'otp_sent', NULL, '2025-0001', '::1', NULL, 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-07 15:12:23'),
(536, 'otp_failed', NULL, '2025-0001', '::1', NULL, 'incorrect otp', NULL, NULL, NULL, NULL, '2026-09-07 15:13:15'),
(537, 'otp_sent', NULL, '2025-0001', '::1', NULL, 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-07 15:18:20'),
(538, 'otp_failed', NULL, '2025-0001', '::1', NULL, 'incorrect otp', NULL, NULL, NULL, NULL, '2026-09-07 15:18:20'),
(539, 'login_success', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 15:19:50'),
(540, 'login_success', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 15:20:00'),
(541, 'otp_sent', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'first_login_otp masked_email=t***********8@gmail.com', NULL, NULL, NULL, NULL, '2026-09-07 15:20:05'),
(542, 'otp_sent', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'first_login_otp masked_email=t***********8@gmail.com', NULL, NULL, NULL, NULL, '2026-09-07 15:20:07'),
(543, 'otp_failed', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'incorrect first_login_otp attempt 1', NULL, NULL, NULL, NULL, '2026-09-07 15:21:16'),
(544, 'otp_failed', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'incorrect first_login_otp attempt 2', NULL, NULL, NULL, NULL, '2026-09-07 15:21:27'),
(545, 'otp_resent', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'first_login_otp resent', NULL, NULL, NULL, NULL, '2026-09-07 15:21:52'),
(546, 'session_timeout_inactivity', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-07 15:37:32'),
(547, 'login_failed', NULL, '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-07 16:00:45'),
(548, 'login_success', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 16:01:06'),
(549, 'login_success', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 16:01:09'),
(550, 'otp_sent', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'first_login_otp masked_email=t***********8@gmail.com', NULL, NULL, NULL, NULL, '2026-09-07 16:01:13'),
(551, 'otp_sent', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'first_login_otp masked_email=t***********8@gmail.com', NULL, NULL, NULL, NULL, '2026-09-07 16:01:16'),
(552, 'otp_verified', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'first_login email verified', NULL, NULL, NULL, NULL, '2026-09-07 16:02:11'),
(553, 'account_updated', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'initial profile setup completed', NULL, NULL, NULL, NULL, '2026-09-07 16:02:53'),
(554, 'password_reset', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'first-login password changed (step 2 of 3)', NULL, NULL, NULL, NULL, '2026-09-07 16:04:21'),
(555, 'security_questions_setup', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 16:05:10'),
(556, 'first_login_completed', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'all 3 setup steps completed', NULL, NULL, NULL, NULL, '2026-09-07 16:05:10'),
(557, 'session_timeout_inactivity', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'inactivity timeout exceeded 300s', NULL, NULL, NULL, NULL, '2026-09-07 16:12:33'),
(558, 'login_success', '2026-0007', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-07 16:12:52'),
(559, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-09 04:13:38'),
(560, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-09 04:19:06'),
(561, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-09 04:29:03'),
(562, 'privilege_revoked', '2025-0005', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'privilege=block_accounts', NULL, NULL, NULL, NULL, '2026-09-09 04:31:35'),
(563, 'logout', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-09 04:34:20'),
(564, 'login_failed', NULL, '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 04:34:40'),
(565, 'login_failed', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'unknown username', NULL, NULL, NULL, NULL, '2026-09-09 04:34:49'),
(566, 'login_failed', NULL, '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 04:35:04'),
(567, 'login_failed', NULL, '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 04:36:11'),
(568, 'login_failed', NULL, '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 04:36:18'),
(569, 'otp_sent', NULL, '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'masked_email=t***********8@gmail.com', NULL, NULL, NULL, NULL, '2026-09-09 04:36:43'),
(570, 'otp_verified', NULL, '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-09 04:37:19'),
(571, 'secret_question_failed', NULL, '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'correctCount=0', NULL, NULL, NULL, NULL, '2026-09-09 04:37:43'),
(572, 'secret_question_failed', NULL, '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'correctCount=0', NULL, NULL, NULL, NULL, '2026-09-09 04:38:15'),
(573, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-09 04:39:01'),
(574, 'staff_account_created', '2025-0005', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'role=admin id_number=2026-0008', NULL, NULL, NULL, NULL, '2026-09-09 04:39:59'),
(575, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-09 04:45:46'),
(576, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-09 04:49:13'),
(577, 'login_failed', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 04:50:10'),
(578, 'login_success', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-09 04:50:18'),
(579, 'otp_sent', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'first_login_otp masked_email=a*******y@gmail.com', NULL, NULL, NULL, NULL, '2026-09-09 04:50:22'),
(580, 'otp_verified', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'first_login email verified', NULL, NULL, NULL, NULL, '2026-09-09 04:50:57'),
(581, 'account_updated', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'initial profile setup completed', NULL, NULL, NULL, NULL, '2026-09-09 04:51:39'),
(582, 'password_reset', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'first-login password changed (step 2 of 3)', NULL, NULL, NULL, NULL, '2026-09-09 04:52:09'),
(583, 'security_questions_setup', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-09 04:52:26'),
(584, 'first_login_completed', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'all 3 setup steps completed', NULL, NULL, NULL, NULL, '2026-09-09 04:52:26'),
(585, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-09 04:58:38'),
(586, 'session_timeout_inactivity', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-09 05:03:29'),
(587, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-09 05:03:50'),
(588, 'login_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'super_admin_already_active', NULL, NULL, NULL, NULL, '2026-09-09 05:06:04'),
(589, 'logout', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-09 05:06:29'),
(590, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-09 05:06:32'),
(591, 'login_failed', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 05:15:44'),
(592, 'login_failed', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 05:15:51'),
(593, 'login_failed', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 05:16:05'),
(594, 'login_failed', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 05:16:43'),
(595, 'login_failed', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 05:16:55'),
(596, 'login_failed', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 05:16:58'),
(597, 'login_failed', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 05:17:29'),
(598, 'login_failed', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 05:17:30'),
(599, 'otp_sent', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'masked_email=a*******y@gmail.com', NULL, NULL, NULL, NULL, '2026-09-09 05:17:58'),
(600, 'otp_verified', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-09 05:20:18'),
(601, 'secret_question_verified', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'correctCount=3', NULL, NULL, NULL, NULL, '2026-09-09 05:20:32'),
(602, 'secret_question_verified', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'correctCount=3', NULL, NULL, NULL, NULL, '2026-09-09 05:20:33'),
(603, 'password_reset', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'via secret question recovery', NULL, NULL, NULL, NULL, '2026-09-09 05:21:09'),
(604, 'login_success', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-09 05:21:28'),
(605, 'session_timeout_inactivity', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'inactivity timeout exceeded 300s', NULL, NULL, NULL, NULL, '2026-09-09 05:21:40'),
(606, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-09 05:22:06'),
(607, 'privilege_revoked', '2025-0005', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'privilege=block_accounts', NULL, NULL, NULL, NULL, '2026-09-09 05:22:24'),
(608, 'privilege_revoked', '2025-0005', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'privilege=update_user_info', NULL, NULL, NULL, NULL, '2026-09-09 05:23:18'),
(609, 'privilege_granted', '2025-0005', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'privilege=update_user_info', NULL, NULL, NULL, NULL, '2026-09-09 05:24:32'),
(610, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-09 07:34:56'),
(611, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-09 07:44:52'),
(612, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-09 07:45:18'),
(613, 'login_failed', NULL, '2026-0008', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 07:46:26'),
(614, 'login_failed', NULL, '2026-0007', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 07:47:55'),
(615, 'login_failed', NULL, '2026-0007', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 07:48:00'),
(616, 'login_failed', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 07:50:02'),
(617, 'login_failed', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 07:50:05');
INSERT INTO `security_logs` (`id`, `event_type`, `actor_id_number`, `target_id_number`, `ip_address`, `user_agent`, `details`, `reason`, `proof_path`, `previous_state`, `new_state`, `created_at`) VALUES
(618, 'otp_sent', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=a*******y@gmail.com', NULL, NULL, NULL, NULL, '2026-09-09 07:50:23'),
(619, 'otp_verified', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-09 07:50:48'),
(620, 'secret_question_verified', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'correctCount=3', NULL, NULL, NULL, NULL, '2026-09-09 07:51:01'),
(621, 'password_reset', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'via secret question recovery', NULL, NULL, NULL, NULL, '2026-09-09 07:51:36'),
(622, 'login_success', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-09 07:51:58'),
(623, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-09 08:00:53'),
(624, 'session_timeout_inactivity', '2026-0008', '2026-0008', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-09 08:01:10'),
(625, 'login_success', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-09 08:11:45'),
(626, 'login_failed', NULL, '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 08:13:58'),
(627, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-09 08:14:00'),
(628, 'logout', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-09 08:17:23'),
(629, 'login_failed', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 08:17:35'),
(630, 'login_failed', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 08:17:37'),
(631, 'otp_sent', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=a*******y@gmail.com', NULL, NULL, NULL, NULL, '2026-09-09 08:18:13'),
(632, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'inactivity timeout exceeded 300s', NULL, NULL, NULL, NULL, '2026-09-09 08:24:35'),
(633, 'login_success', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-09 08:25:00'),
(634, 'session_timeout_inactivity', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-09 08:30:33'),
(635, 'login_failed', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 08:35:10'),
(636, 'login_failed', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 08:35:11'),
(637, 'otp_send_failed', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'find_account send failed', NULL, NULL, NULL, NULL, '2026-09-09 08:35:55'),
(638, 'otp_sent', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=a*******y@gmail.com', NULL, NULL, NULL, NULL, '2026-09-09 08:36:23'),
(639, 'otp_verified', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'otp_success', NULL, NULL, NULL, NULL, '2026-09-09 08:36:44'),
(640, 'otp_sent', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=a*******y@gmail.com', NULL, NULL, NULL, NULL, '2026-09-09 08:37:20'),
(641, 'otp_verified', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'otp_success', NULL, NULL, NULL, NULL, '2026-09-09 08:37:31'),
(642, 'otp_sent', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=a*******y@gmail.com', NULL, NULL, NULL, NULL, '2026-09-09 08:40:44'),
(643, 'otp_verified', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'otp_success', NULL, NULL, NULL, NULL, '2026-09-09 08:41:02'),
(644, 'otp_sent', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=a*******y@gmail.com', NULL, NULL, NULL, NULL, '2026-09-09 08:47:25'),
(645, 'otp_verified', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'otp_success', NULL, NULL, NULL, NULL, '2026-09-09 08:47:39'),
(646, 'login_success', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-09 08:52:19'),
(647, 'logout', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-09 08:52:23'),
(648, 'login_failed', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 08:52:29'),
(649, 'login_failed', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 08:52:30'),
(650, 'otp_sent', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=a*******y@gmail.com', NULL, NULL, NULL, NULL, '2026-09-09 08:52:40'),
(651, 'otp_verified', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'otp_success', NULL, NULL, NULL, NULL, '2026-09-09 08:53:10'),
(652, 'otp_sent', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=a*******y@gmail.com', NULL, NULL, NULL, NULL, '2026-09-09 08:55:32'),
(653, 'otp_resent', NULL, '2026-0008', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'masked_email=a*******y@gmail.com', NULL, NULL, NULL, NULL, '2026-09-09 08:56:47'),
(654, 'otp_verified', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'otp_success', NULL, NULL, NULL, NULL, '2026-09-09 08:57:02'),
(655, 'secret_question_verified', NULL, '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'correctCount=3', NULL, NULL, NULL, NULL, '2026-09-09 08:57:13'),
(656, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-09 09:03:38'),
(657, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-09 09:19:40'),
(658, 'login_failed', NULL, '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-09 09:21:18'),
(659, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-09 09:21:40'),
(660, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'inactivity timeout exceeded 300s', NULL, NULL, NULL, NULL, '2026-09-09 09:29:03'),
(661, 'login_success', '2025-0005', '2025-0005', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-09 09:29:14'),
(662, 'privilege_granted', '2025-0005', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'privilege=block_accounts', NULL, NULL, NULL, NULL, '2026-09-09 09:32:01'),
(663, 'account_blocked', '2025-0005', '2026-0004', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-09 09:33:31'),
(664, 'login_success', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-09 09:44:44'),
(665, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-09 09:49:25'),
(666, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-09 09:52:06'),
(667, 'action_confirm_failed', '2025-0005', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'action=privileges_updated', NULL, NULL, NULL, NULL, '2026-09-09 09:52:40'),
(668, 'privilege_granted', '2025-0005', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'privilege=manage_super_admin_accounts', NULL, NULL, NULL, NULL, '2026-09-09 09:53:07'),
(669, 'privilege_granted', '2025-0005', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'privilege=manage_super_admin_accounts', NULL, NULL, NULL, NULL, '2026-09-09 09:56:05'),
(670, 'privilege_revoked', '2025-0005', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'privilege=', NULL, NULL, NULL, NULL, '2026-09-09 09:56:05'),
(671, 'session_timeout_inactivity', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'inactivity timeout exceeded 300s', NULL, NULL, NULL, NULL, '2026-09-09 10:05:52'),
(672, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-09 10:08:40'),
(673, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-10 02:52:23'),
(674, 'session_timeout_inactivity', '2025-0005', '2025-0005', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'inactivity timeout exceeded 300s', NULL, NULL, NULL, NULL, '2026-09-10 03:14:56'),
(675, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-10 03:15:19'),
(676, 'login_failed', NULL, '2026-0008', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-10 03:16:14'),
(677, 'login_success', '2026-0008', '2026-0008', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-10 03:16:34'),
(678, 'session_timeout_inactivity', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-10 03:26:09'),
(679, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-10 03:30:54'),
(680, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=reset_password code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:31:59'),
(681, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=edit code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:31:59'),
(682, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=delete code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:32:00'),
(683, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=change_role code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:32:00'),
(684, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=block code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:32:00'),
(685, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:32:00'),
(686, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=delete code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:32:00'),
(687, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=change_role code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:32:01'),
(688, 'unauthorized_action_blocked', '2025-0005', '2026-0001', '::1', 'curl/8.21.0', 'action=unblock code=invalid_state', NULL, NULL, NULL, NULL, '2026-09-10 04:32:01'),
(689, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=edit code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:32:02'),
(690, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:32:02'),
(691, 'unauthorized_action_blocked', '2026-0008', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:32:02'),
(692, 'action_confirm_failed', '2025-0005', '2025-0001', '::1', 'curl/8.21.0', 'action=account_updated', NULL, NULL, NULL, NULL, '2026-09-10 04:32:03'),
(703, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=reset_password code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:35:14'),
(704, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=edit code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:35:14'),
(705, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=delete code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:35:15'),
(706, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=change_role code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:35:15'),
(707, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=manage_privileges code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:35:15'),
(708, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=block code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:35:15'),
(709, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:35:15'),
(710, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=delete code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:35:16'),
(711, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=change_role code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:35:16'),
(712, 'unauthorized_action_blocked', '2025-0005', '2026-0001', '::1', 'curl/8.21.0', 'action=unblock code=invalid_state', NULL, NULL, NULL, NULL, '2026-09-10 04:35:16'),
(713, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=edit code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:35:17'),
(714, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:35:17'),
(715, 'unauthorized_action_blocked', '2026-0008', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:35:17'),
(718, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=reset_password code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:39:54'),
(719, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=edit code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:39:54'),
(720, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=delete code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:39:55'),
(721, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=change_role code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:39:55'),
(722, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=manage_privileges code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:39:55'),
(723, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=block code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:39:55'),
(724, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:39:55'),
(725, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=delete code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:39:55'),
(726, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=change_role code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:39:55'),
(727, 'unauthorized_action_blocked', '2025-0005', '2026-0001', '::1', 'curl/8.21.0', 'action=unblock code=invalid_state', NULL, NULL, NULL, NULL, '2026-09-10 04:39:56'),
(728, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=edit code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:39:56'),
(729, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:39:56'),
(730, 'unauthorized_action_blocked', '2026-0008', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:39:57'),
(741, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-10 04:43:47'),
(742, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=reset_password code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:50:35'),
(743, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=edit code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:50:35'),
(744, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=delete code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:50:36'),
(745, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=change_role code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:50:36'),
(746, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=manage_privileges code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:50:36'),
(747, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=block code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:50:36'),
(748, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:50:36'),
(749, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=delete code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:50:36'),
(750, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=change_role code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:50:36'),
(751, 'unauthorized_action_blocked', '2025-0005', '2026-0001', '::1', 'curl/8.21.0', 'action=unblock code=invalid_state', NULL, NULL, NULL, NULL, '2026-09-10 04:50:37'),
(752, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=edit code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:50:38'),
(753, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:50:38'),
(754, 'unauthorized_action_blocked', '2026-0008', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:50:38'),
(760, 'session_terminated_super_admin_expired', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'session expired or superseded', NULL, NULL, NULL, NULL, '2026-09-10 04:52:48'),
(761, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-10 04:53:03'),
(762, 'login_success', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-10 04:53:38'),
(763, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=reset_password code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:55:10'),
(764, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=edit code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:55:10'),
(765, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=delete code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:55:10'),
(766, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=change_role code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:55:10'),
(767, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=manage_privileges code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:55:10'),
(768, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=block code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:55:11'),
(769, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:55:11'),
(770, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=delete code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:55:11'),
(771, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=change_role code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:55:11'),
(772, 'action_confirm_failed', '2025-0005', '2026-0002', '::1', 'curl/8.21.0', 'action=account_block', NULL, NULL, NULL, NULL, '2026-09-10 04:55:12'),
(773, 'unauthorized_action_blocked', '2025-0005', '2026-0001', '::1', 'curl/8.21.0', 'action=unblock code=invalid_state', NULL, NULL, NULL, NULL, '2026-09-10 04:55:12'),
(774, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=edit code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:55:12'),
(775, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:55:12'),
(776, 'unauthorized_action_blocked', '2026-0008', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:55:12'),
(784, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=reset_password code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:55:27'),
(785, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=edit code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:55:28'),
(786, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=delete code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:55:28'),
(787, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=change_role code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:55:28'),
(788, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=manage_privileges code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:55:28'),
(789, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=block code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:55:28'),
(790, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:55:28'),
(791, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=delete code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:55:28'),
(792, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=change_role code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:55:28'),
(793, 'action_confirm_failed', '2025-0005', '2026-0002', '::1', 'curl/8.21.0', 'action=account_block', NULL, NULL, NULL, NULL, '2026-09-10 04:55:29'),
(794, 'unauthorized_action_blocked', '2025-0005', '2026-0001', '::1', 'curl/8.21.0', 'action=unblock code=invalid_state', NULL, NULL, NULL, NULL, '2026-09-10 04:55:29'),
(795, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=edit code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:55:30'),
(796, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:55:30'),
(797, 'unauthorized_action_blocked', '2026-0008', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:55:30'),
(798, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=reset_password code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:56:07'),
(799, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=edit code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:56:07'),
(800, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=delete code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:56:07'),
(801, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=change_role code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:56:07'),
(802, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=manage_privileges code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:56:08'),
(803, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=block code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 04:56:08'),
(804, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:56:08'),
(805, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=delete code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:56:08'),
(806, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=change_role code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 04:56:08'),
(807, 'action_confirm_failed', '2025-0005', '2026-0002', '::1', 'curl/8.21.0', 'action=account_block', NULL, NULL, NULL, NULL, '2026-09-10 04:56:09'),
(808, 'unauthorized_action_blocked', '2025-0005', '2026-0001', '::1', 'curl/8.21.0', 'action=unblock code=invalid_state', NULL, NULL, NULL, NULL, '2026-09-10 04:56:09'),
(809, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=edit code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:56:09'),
(810, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:56:09'),
(811, 'unauthorized_action_blocked', '2026-0008', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 04:56:10'),
(819, 'delete_request_submitted', '2026-0008', '2026-0003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'request_id=5 target_role=customer', 'Way buot', NULL, 'status=active', 'delete_request=pending', '2026-09-10 04:58:48'),
(820, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'inactivity timeout exceeded 300s', NULL, NULL, NULL, NULL, '2026-09-10 04:58:55'),
(821, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-10 04:59:12'),
(822, 'delete_request_approved', '2025-0005', '2026-0003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'request_id=5', 'Confirmed', NULL, 'delete_request=pending', 'delete_request=approved', '2026-09-10 04:59:47'),
(823, 'account_deleted', '2025-0005', '2026-0003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'via delete request #5', 'Way buot', NULL, 'account exists', 'deleted', '2026-09-10 04:59:47'),
(824, 'staff_account_created', '2025-0005', '2026-0009', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'role=admin id_number=2026-0009', NULL, NULL, NULL, NULL, '2026-09-10 05:05:54'),
(825, 'logout', '2026-0008', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-10 05:06:09'),
(826, 'login_failed', NULL, '2026-0009', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-10 05:06:25'),
(827, 'login_success', '2026-0009', '2026-0009', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-10 05:06:35'),
(828, 'otp_sent', '2026-0009', '2026-0009', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'first_login_otp masked_email=a**************4@gmail.com', NULL, NULL, NULL, NULL, '2026-09-10 05:06:40'),
(829, 'otp_verified', '2026-0009', '2026-0009', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'first_login email verified', NULL, NULL, NULL, NULL, '2026-09-10 05:07:19'),
(830, 'account_updated', '2026-0009', '2026-0009', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'initial profile setup completed', NULL, NULL, NULL, NULL, '2026-09-10 05:10:24'),
(832, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=reset_password code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 05:11:13'),
(833, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=edit code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 05:11:13'),
(834, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=delete code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 05:11:13'),
(835, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=change_role code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 05:11:13'),
(836, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=manage_privileges code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 05:11:13'),
(837, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=block code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 05:11:13'),
(838, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 05:11:14'),
(839, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=delete code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 05:11:14'),
(840, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=change_role code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 05:11:14'),
(841, 'action_confirm_failed', '2025-0005', '2026-0002', '::1', 'curl/8.21.0', 'action=account_block', NULL, NULL, NULL, NULL, '2026-09-10 05:11:14'),
(842, 'unauthorized_action_blocked', '2025-0005', '2026-0001', '::1', 'curl/8.21.0', 'action=unblock code=invalid_state', NULL, NULL, NULL, NULL, '2026-09-10 05:11:15'),
(843, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=edit code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 05:11:15'),
(844, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 05:11:15'),
(845, 'unauthorized_action_blocked', '2026-0008', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 05:11:15'),
(853, 'password_reset', '2026-0009', '2026-0009', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'first-login password changed (step 2 of 3)', NULL, NULL, NULL, NULL, '2026-09-10 05:11:38'),
(854, 'security_questions_setup', '2026-0009', '2026-0009', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-10 05:12:04'),
(855, 'first_login_completed', '2026-0009', '2026-0009', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'all 3 setup steps completed', NULL, NULL, NULL, NULL, '2026-09-10 05:12:04'),
(856, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-10 05:12:59'),
(857, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-10 05:13:44'),
(858, 'logout', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-10 05:15:11'),
(859, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-10 05:15:30'),
(864, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=reset_password code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 05:21:13'),
(865, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=edit code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 05:21:14'),
(866, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=delete code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 05:21:14'),
(867, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=change_role code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 05:21:14'),
(868, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=manage_privileges code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 05:21:14'),
(869, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=block code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 05:21:14'),
(870, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 05:21:15'),
(871, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=delete code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 05:21:15'),
(872, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=change_role code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 05:21:15'),
(873, 'action_confirm_failed', '2025-0005', '2026-0002', '::1', 'curl/8.21.0', 'action=account_block', NULL, NULL, NULL, NULL, '2026-09-10 05:21:15'),
(874, 'unauthorized_action_blocked', '2025-0005', '2026-0001', '::1', 'curl/8.21.0', 'action=unblock code=invalid_state', NULL, NULL, NULL, NULL, '2026-09-10 05:21:15'),
(875, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=edit code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 05:21:16'),
(876, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 05:21:16'),
(877, 'unauthorized_action_blocked', '2026-0008', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 05:21:16'),
(885, 'session_timeout_inactivity', '2026-0009', '2026-0009', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-10 05:21:22'),
(888, 'session_timeout_inactivity', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-10 05:23:57'),
(889, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-10 05:24:09'),
(890, 'session_timeout_inactivity', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-10 06:07:03'),
(891, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-10 06:34:20'),
(892, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=reset_password code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 06:37:45'),
(893, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=edit code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 06:37:45'),
(894, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=delete code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 06:37:46'),
(895, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=change_role code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 06:37:46'),
(896, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=manage_privileges code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 06:37:46'),
(897, 'unauthorized_action_blocked', '2025-0005', '2026-0004', '::1', 'curl/8.21.0', 'action=block code=target_blocked', NULL, NULL, NULL, NULL, '2026-09-10 06:37:46'),
(898, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 06:37:46'),
(899, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=delete code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 06:37:46'),
(900, 'unauthorized_action_blocked', '2025-0005', '2025-0005', '::1', 'curl/8.21.0', 'action=change_role code=cannot_target_self', NULL, NULL, NULL, NULL, '2026-09-10 06:37:47'),
(901, 'action_confirm_failed', '2025-0005', '2026-0002', '::1', 'curl/8.21.0', 'action=account_block', NULL, NULL, NULL, NULL, '2026-09-10 06:37:47'),
(902, 'unauthorized_action_blocked', '2025-0005', '2026-0001', '::1', 'curl/8.21.0', 'action=unblock code=invalid_state', NULL, NULL, NULL, NULL, '2026-09-10 06:37:47'),
(903, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=edit code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 06:37:48'),
(904, 'unauthorized_action_blocked', '2026-0008', '2026-0002', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 06:37:48'),
(905, 'unauthorized_action_blocked', '2026-0008', '2025-0005', '::1', 'curl/8.21.0', 'action=block code=forbidden', NULL, NULL, NULL, NULL, '2026-09-10 06:37:48'),
(915, 'session_terminated_super_admin_expired', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'session expired or superseded', NULL, NULL, NULL, NULL, '2026-09-10 06:39:13'),
(916, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-10 06:39:27'),
(917, 'password_reset_by_admin', '2025-0005', '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'target_role=customer', 'Forgotten', NULL, 'password=(previous hash)', 'password=(reset by 2025-0005)', '2026-09-10 06:40:13'),
(918, 'logout', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-10 06:40:21'),
(919, 'login_success', '2026-0001', '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-10 06:40:48'),
(920, 'logout', '2026-0001', '2026-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-10 06:41:59'),
(921, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-10 06:42:12'),
(922, 'role_changed', '2025-0005', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'old_role=admin new_role=super_admin', 'Newly hired', NULL, 'role=admin', 'role=super_admin', '2026-09-10 06:42:46'),
(923, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-10 06:47:49'),
(924, 'password_reset_blocked', NULL, '2026-0004', '::1', 'curl/8.21.0', 'approval=approved account=blocked', NULL, NULL, NULL, NULL, '2026-09-10 06:54:16'),
(925, 'otp_sent', NULL, '2025-0001', '::1', 'curl/8.21.0', 'masked_email=e**********l@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-10 06:54:20'),
(928, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-10 08:10:43'),
(929, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-10 08:18:08'),
(930, 'login_failed', NULL, '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-15 03:01:12'),
(931, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 03:01:30'),
(932, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-15 03:11:21'),
(933, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 03:19:18'),
(934, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-15 03:45:24');
INSERT INTO `security_logs` (`id`, `event_type`, `actor_id_number`, `target_id_number`, `ip_address`, `user_agent`, `details`, `reason`, `proof_path`, `previous_state`, `new_state`, `created_at`) VALUES
(937, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 04:16:03'),
(938, 'account_unblocked', '2025-0005', '2026-0004', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'target_role=admin', 'Back to the company', NULL, 'status=blocked', 'status=active', '2026-09-15 04:17:15'),
(942, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-15 04:38:29'),
(943, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 04:38:50'),
(944, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'inactivity timeout exceeded 300s', NULL, NULL, NULL, NULL, '2026-09-15 04:46:51'),
(945, 'login_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'account_status=blocked', NULL, NULL, NULL, NULL, '2026-09-15 04:47:31'),
(946, 'login_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'account_status=blocked', NULL, NULL, NULL, NULL, '2026-09-15 04:47:36'),
(947, 'password_reset_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'approval=approved account=blocked', NULL, NULL, NULL, NULL, '2026-09-15 04:49:05'),
(948, 'password_reset_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'approval=approved account=blocked', NULL, NULL, NULL, NULL, '2026-09-15 04:54:53'),
(949, 'password_reset_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'approval=approved account=blocked', NULL, NULL, NULL, NULL, '2026-09-15 05:00:32'),
(950, 'password_reset_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'approval=approved account=blocked', NULL, NULL, NULL, NULL, '2026-09-15 05:00:33'),
(951, 'password_reset_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'approval=approved account=blocked', NULL, NULL, NULL, NULL, '2026-09-15 05:02:20'),
(952, 'password_reset_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'approval=approved account=blocked', NULL, NULL, NULL, NULL, '2026-09-15 05:05:20'),
(953, 'password_reset_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'approval=approved account=blocked', NULL, NULL, NULL, NULL, '2026-09-15 05:07:10'),
(976, 'password_reset_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'approval=approved account=blocked', NULL, NULL, NULL, NULL, '2026-09-15 05:36:21'),
(977, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:36:37'),
(978, 'account_unblocked', '2025-0005', '2026-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'unblock with role change: old_role=super_admin new_role=admin', 'djfhdfhdfjdhf', NULL, 'role=super_admin status=blocked', 'role=admin status=active', '2026-09-15 05:37:14'),
(979, 'logout', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:37:56'),
(980, 'login_failed', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-15 05:38:19'),
(981, 'login_failed', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-15 05:38:21'),
(982, 'password_reset_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'approval=approved account=blocked', NULL, NULL, NULL, NULL, '2026-09-15 05:38:41'),
(983, 'otp_sent', NULL, '2026-0004', '::1', 'curl/8.21.0', 'masked_email=s****n@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:42:08'),
(985, 'password_reset_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'approval=approved account=blocked', NULL, NULL, NULL, NULL, '2026-09-15 05:44:04'),
(986, 'password_reset_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'approval=approved account=blocked', NULL, NULL, NULL, NULL, '2026-09-15 05:45:24'),
(987, 'password_reset_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'approval=approved account=blocked', NULL, NULL, NULL, NULL, '2026-09-15 05:46:26'),
(988, 'password_reset_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'approval=approved account=blocked', NULL, NULL, NULL, NULL, '2026-09-15 05:47:58'),
(989, 'password_reset_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'approval=approved account=blocked', NULL, NULL, NULL, NULL, '2026-09-15 05:48:06'),
(990, 'password_reset_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'approval=approved account=blocked', NULL, NULL, NULL, NULL, '2026-09-15 05:48:07'),
(991, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:50:24'),
(992, 'staff_account_created', '2025-0005', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'role=admin id_number=2026-0010 status=inactive', NULL, NULL, NULL, NULL, '2026-09-15 05:53:55'),
(993, 'logout', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:54:13'),
(994, 'login_failed', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'unknown username', NULL, NULL, NULL, NULL, '2026-09-15 05:54:25'),
(995, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:20'),
(996, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:25'),
(997, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:26'),
(998, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:26'),
(999, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:26'),
(1000, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:26'),
(1001, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:28'),
(1002, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:28'),
(1003, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:30'),
(1004, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:30'),
(1005, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:31'),
(1006, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:31'),
(1007, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:31'),
(1008, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:31'),
(1009, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:31'),
(1010, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:32'),
(1011, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:32'),
(1012, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:32'),
(1013, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:32'),
(1014, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:33'),
(1015, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:33'),
(1016, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:35'),
(1017, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:35'),
(1018, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:36'),
(1019, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:36'),
(1020, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:36'),
(1021, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:36'),
(1022, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:37'),
(1023, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:37'),
(1024, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:37'),
(1025, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:37'),
(1026, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:38'),
(1027, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:38'),
(1028, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:39'),
(1029, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:40'),
(1030, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:40'),
(1031, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:41'),
(1032, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:41'),
(1033, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:41'),
(1034, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:41'),
(1035, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:41'),
(1036, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 05:55:41'),
(1037, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:43'),
(1038, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:43'),
(1039, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:44'),
(1040, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:44'),
(1041, 'otp_sent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=f***********2@gmail.com', NULL, NULL, NULL, NULL, '2026-09-15 05:55:46'),
(1042, 'otp_failed', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'incorrect first_login_otp attempt 1', NULL, NULL, NULL, NULL, '2026-09-15 05:56:00'),
(1043, 'otp_resent', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp resent', NULL, NULL, NULL, NULL, '2026-09-15 05:56:32'),
(1044, 'otp_failed', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'incorrect first_login_otp attempt 1', NULL, NULL, NULL, NULL, '2026-09-15 05:57:15'),
(1045, 'otp_failed', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'incorrect first_login_otp attempt 2', NULL, NULL, NULL, NULL, '2026-09-15 05:57:27'),
(1046, 'otp_verified', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login email verified', NULL, NULL, NULL, NULL, '2026-09-15 05:57:45'),
(1047, 'account_updated', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'initial profile setup completed', NULL, NULL, NULL, NULL, '2026-09-15 05:59:31'),
(1048, 'password_reset', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first-login password changed (step 2 of 3)', NULL, NULL, NULL, NULL, '2026-09-15 06:00:01'),
(1049, 'security_questions_setup', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 06:00:30'),
(1050, 'first_login_completed', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'all 3 setup steps completed', NULL, NULL, NULL, NULL, '2026-09-15 06:00:30'),
(1051, 'action_confirm_failed', '2026-0010', '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'action=verify:account_approve.php', NULL, NULL, NULL, NULL, '2026-09-15 06:01:28'),
(1052, 'account_blocked', '2026-0010', '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'target_role=customer', 'Deleted', '20260915-080150_block_2026-0010_64fb147d5b1bd0b7.jpg', 'status=active', 'status=blocked', '2026-09-15 06:01:50'),
(1053, 'session_timeout_inactivity', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-15 06:08:03'),
(1054, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 06:09:48'),
(1055, 'proof_viewed', '2026-0010', '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'proof=20260915-080150_block_2026-0010_64fb147d5b1bd0b7.jpg', NULL, NULL, NULL, NULL, '2026-09-15 06:10:22'),
(1056, 'delete_request_submitted', '2026-0010', '2025-0003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'request_id=6 target_role=customer', 'Way klaro na tao', '20260915-081201_delete_request_2026-0010_181a1dfaabad1a3b.pdf', 'status=active', 'delete_request=pending', '2026-09-15 06:12:01'),
(1057, 'proof_viewed', '2026-0010', '2025-0003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'proof=20260915-081201_delete_request_2026-0010_181a1dfaabad1a3b.pdf', NULL, NULL, NULL, NULL, '2026-09-15 06:12:19'),
(1058, 'logout', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 06:12:43'),
(1059, 'login_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'account_status=blocked', NULL, NULL, NULL, NULL, '2026-09-15 06:13:01'),
(1060, 'login_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'account_status=blocked', NULL, NULL, NULL, NULL, '2026-09-15 06:13:13'),
(1061, 'password_reset_blocked', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'approval=approved account=blocked', NULL, NULL, NULL, NULL, '2026-09-15 06:13:24'),
(1062, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 06:13:49'),
(1063, 'delete_request_approved', '2025-0005', '2025-0003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'request_id=6', 'confirmed', '20260915-081201_delete_request_2026-0010_181a1dfaabad1a3b.pdf', 'delete_request=pending', 'delete_request=approved', '2026-09-15 06:14:35'),
(1064, 'account_deleted', '2025-0005', '2025-0003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'via delete request #6', 'Way klaro na tao', '20260915-081201_delete_request_2026-0010_181a1dfaabad1a3b.pdf', 'account exists', 'deleted', '2026-09-15 06:14:35'),
(1065, 'account_deleted', '2025-0005', '2026-0004', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'direct delete by super_admin, target=Sachin Kuman (sachin12345)', 'Way kklaro na employee', NULL, 'role=admin status=active', 'deleted', '2026-09-15 06:15:49'),
(1066, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-15 06:22:04'),
(1067, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 06:26:51'),
(1068, 'logout', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 06:27:16'),
(1069, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 06:27:30'),
(1070, 'session_timeout_inactivity', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-15 06:32:58'),
(1088, 'login_failed', NULL, '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-15 06:57:27'),
(1089, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 06:57:38'),
(1090, 'account_unblocked', '2025-0005', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'unblock with role change: old_role=super_admin new_role=admin', 'usdifgsdf', NULL, 'role=super_admin status=blocked', 'role=admin status=active', '2026-09-15 07:00:45'),
(1091, 'session_timeout_inactivity', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-15 07:07:35'),
(1092, 'login_success', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 07:13:37'),
(1093, 'account_blocked', '2025-0005', '2026-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'target_role=admin', 'inactive', '20260915-091746_block_2025-0005_268054a89fc30ba3.pdf', 'status=active', 'status=blocked', '2026-09-15 07:17:46'),
(1094, 'role_changed', '2025-0005', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'old_role=admin new_role=super_admin', 'dhdjghfjghfjfghfg', NULL, 'role=admin status=active', 'role=super_admin status=inactive', '2026-09-15 07:20:11'),
(1095, 'logout', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 07:20:27'),
(1096, 'super_admin_handover_blocked', '2025-0005', '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'replaced_by=2026-0006', 'Automatic Super Admin handover (logout).', NULL, 'status=active', 'status=blocked', '2026-09-15 07:20:27'),
(1097, 'super_admin_handover_activated', '2025-0005', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'replaces=2025-0005', 'Automatic Super Admin handover (logout).', NULL, 'status=inactive', 'status=active', '2026-09-15 07:20:27'),
(1098, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 07:20:39'),
(1099, 'logout', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 07:24:48'),
(1100, 'login_failed', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'unknown username', NULL, NULL, NULL, NULL, '2026-09-15 07:24:58'),
(1101, 'login_failed', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'unknown username', NULL, NULL, NULL, NULL, '2026-09-15 07:24:59'),
(1102, 'login_failed', NULL, '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-15 07:26:03'),
(1103, 'login_failed', NULL, '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-15 07:26:46'),
(1104, 'login_blocked', NULL, '2025-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'account_status=blocked', NULL, NULL, NULL, NULL, '2026-09-15 07:26:58'),
(1105, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 07:27:21'),
(1106, 'logout', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 07:27:37'),
(1107, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 07:27:51'),
(1108, 'logout', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 07:28:08'),
(1109, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 07:28:21'),
(1110, 'session_timeout_inactivity', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-15 07:36:07'),
(1111, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 07:43:13'),
(1115, 'session_timeout_inactivity', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'inactivity timeout exceeded 300s', NULL, NULL, NULL, NULL, '2026-09-15 07:52:13'),
(1116, 'login_failed', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'unknown username', NULL, NULL, NULL, NULL, '2026-09-15 07:52:34'),
(1117, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 07:52:47'),
(1133, 'session_timeout_inactivity', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-15 08:01:28'),
(1134, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 08:02:17'),
(1135, 'action_confirm_failed', '2026-0006', '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'action=verify:account_approve.php', NULL, NULL, NULL, NULL, '2026-09-15 08:03:12'),
(1136, 'account_unblocked', '2026-0006', '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'target_role=customer', 'hjdfhdfjhfh', '20260915-100343_unblock_2026-0006_90e35d0ae8e641d4.pdf', 'status=blocked', 'status=active', '2026-09-15 08:03:43'),
(1137, 'action_confirm_failed', '2026-0006', '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'action=verify:account_reset_password.php', NULL, NULL, NULL, NULL, '2026-09-15 08:07:15'),
(1138, 'password_reset_by_admin', '2026-0006', '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'target_role=customer', 'Cahnge password', '20260915-100730_reset_password_2026-0006_e56ac2fee3855452.pdf', 'password=(previous hash)', 'password=(reset by 2026-0006)', '2026-09-15 08:07:30'),
(1139, 'logout', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 08:07:38'),
(1140, 'login_success', '2026-0005', '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 08:07:57'),
(1141, 'order_placed', '2026-0005', '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'order_id=7', NULL, NULL, NULL, NULL, '2026-09-15 08:09:41'),
(1151, 'session_timeout_inactivity', '2026-0005', '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-15 08:32:59'),
(1152, 'login_failed', NULL, '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-15 08:33:13'),
(1153, 'login_success', '2026-0005', '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 08:33:21'),
(1154, 'order_placed', '2026-0005', '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'order_id=10', NULL, NULL, NULL, NULL, '2026-09-15 08:33:48'),
(1155, 'account_updated', '2026-0005', '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'fields: lastname,firstname,middlename,email,street,barangay,city_municipality,province,zipcode,country', NULL, NULL, 'street=P1', 'street=Purok 1', '2026-09-15 08:35:17'),
(1156, 'logout', '2026-0005', '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 08:35:36'),
(1157, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 08:35:49'),
(1158, 'order_status_changed', '2026-0010', '2026-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'order_id=10 status=Preparing', NULL, NULL, NULL, NULL, '2026-09-15 08:38:44'),
(1159, 'logout', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 08:40:18'),
(1160, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 08:40:27'),
(1161, 'privilege_granted', '2026-0006', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'privilege=manage_super_admin_accounts', 'Needed for auditing', '20260915-104116_manage_privileges_2026-0006_40d45d0a52815267.pdf', 'not granted', 'granted: Delete Users', '2026-09-15 08:41:16'),
(1162, 'privilege_granted', '2026-0006', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'privilege=view_logs', 'Needed for auditing', '20260915-104116_manage_privileges_2026-0006_40d45d0a52815267.pdf', 'not granted', 'granted: View All Logs', '2026-09-15 08:41:16'),
(1163, 'logout', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 08:41:25'),
(1164, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 08:41:36'),
(1165, 'session_timeout_inactivity', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-15 08:49:56'),
(1166, 'login_failed', NULL, '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-15 08:51:02'),
(1167, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 08:51:09'),
(1168, 'logout', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 08:51:34'),
(1169, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 08:51:45'),
(1176, 'session_timeout_inactivity', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-15 09:07:28'),
(1177, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 09:07:40'),
(1178, 'logout', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 09:08:21'),
(1179, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 09:08:36'),
(1180, 'login_failed', NULL, '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-15 09:10:51'),
(1181, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-15 09:10:56'),
(1182, 'logout', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 09:13:41'),
(1183, 'logout', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-15 09:13:51'),
(1184, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-15 09:14:02'),
(1185, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 09:15:13'),
(1186, 'privilege_granted', '2026-0006', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'privilege=manage_deletion_requests', 'sdfhdjfhdfjhd', NULL, 'not granted', 'granted: Manage Deletion Requests', '2026-09-15 09:15:36'),
(1187, 'privilege_revoked', '2026-0006', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'privilege=manage_deletion_requests', 'dhjdghgjhghd', NULL, 'granted: Manage Deletion Requests', 'revoked', '2026-09-15 09:16:46'),
(1188, 'privilege_revoked', '2026-0006', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'privilege=view_logs', 'dhjdghgjhghd', NULL, 'granted: View All Logs', 'revoked', '2026-09-15 09:16:46'),
(1189, 'session_timeout_inactivity', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-15 09:24:02'),
(1190, 'session_timeout_inactivity', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-15 09:28:13'),
(1191, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 09:28:23'),
(1202, 'session_terminated_super_admin_expired', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'session expired or superseded', NULL, NULL, NULL, NULL, '2026-09-15 09:31:19'),
(1203, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-15 09:31:31'),
(1204, 'privilege_granted', '2026-0006', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'privilege=reset_passwords', 'fdgfdfiyfi', NULL, 'not granted', 'granted: Reset Passwords', '2026-09-15 09:31:59'),
(1205, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '', NULL, NULL, NULL, NULL, '2026-09-15 09:32:13'),
(1206, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 12:18:32'),
(1207, 'logout', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 12:21:24'),
(1208, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 12:21:34'),
(1209, 'session_timeout_inactivity', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-16 12:26:48'),
(1210, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 12:50:35'),
(1211, 'logout', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 12:50:49');
INSERT INTO `security_logs` (`id`, `event_type`, `actor_id_number`, `target_id_number`, `ip_address`, `user_agent`, `details`, `reason`, `proof_path`, `previous_state`, `new_state`, `created_at`) VALUES
(1212, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 12:51:26'),
(1213, 'session_timeout_inactivity', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'inactivity timeout exceeded 300s', NULL, NULL, NULL, NULL, '2026-09-16 13:03:40'),
(1214, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:03:56'),
(1215, 'account_deleted', '2026-0010', '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'direct delete by admin, target=Edmond Napil (edmond123)', 'Way pulos', '20260916-150451_delete_2026-0010_493323662b9c7caf.pdf', 'role=customer status=active', 'deleted', '2026-09-16 13:04:51'),
(1216, 'account_blocked', '2026-0010', '2025-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'target_role=customer', 'fdfdfdfdfdf', NULL, 'status=active', 'status=blocked', '2026-09-16 13:05:39'),
(1217, 'logout', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:05:49'),
(1218, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:06:49'),
(1219, 'proof_viewed', '2026-0006', '2025-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'proof=20260916-150451_delete_2026-0010_493323662b9c7caf.pdf', NULL, NULL, NULL, NULL, '2026-09-16 13:07:09'),
(1220, 'customer_account_created', '2026-0006', '2026-0011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'role=customer id_number=2026-0011 status=inactive', NULL, NULL, NULL, NULL, '2026-09-16 13:08:37'),
(1221, 'logout', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:09:55'),
(1222, 'login_success', '2026-0011', '2026-0011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:10:10'),
(1223, 'login_success', '2026-0011', '2026-0011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:10:13'),
(1224, 'otp_sent', '2026-0011', '2026-0011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=e***********s@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-16 13:10:15'),
(1225, 'otp_sent', '2026-0011', '2026-0011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login_otp masked_email=e***********s@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-16 13:10:19'),
(1226, 'otp_verified', '2026-0011', '2026-0011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first_login email verified', NULL, NULL, NULL, NULL, '2026-09-16 13:10:44'),
(1227, 'account_updated', '2026-0011', '2026-0011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'initial profile setup completed', NULL, NULL, NULL, NULL, '2026-09-16 13:11:39'),
(1228, 'password_reset', '2026-0011', '2026-0011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'first-login password changed (step 2 of 3)', NULL, NULL, NULL, NULL, '2026-09-16 13:12:03'),
(1229, 'security_questions_setup', '2026-0011', '2026-0011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:12:24'),
(1230, 'first_login_completed', '2026-0011', '2026-0011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'all 3 setup steps completed', NULL, NULL, NULL, NULL, '2026-09-16 13:12:24'),
(1231, 'logout', '2026-0011', '2026-0011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:13:12'),
(1232, 'registration_submitted', '2026-0012', '2026-0012', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'New customer registration pending approval', NULL, NULL, NULL, NULL, '2026-09-16 13:15:31'),
(1233, 'login_blocked', NULL, '2026-0012', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'approval_status=pending', NULL, NULL, NULL, NULL, '2026-09-16 13:15:49'),
(1234, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:16:06'),
(1235, 'logout', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:17:02'),
(1236, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:17:11'),
(1237, 'account_updated', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'fields: lastname,firstname,middlename,email,street,barangay,city_municipality,province,zipcode,country', NULL, NULL, 'middlename=; street=N/A; barangay=N/A; city_municipality=N/A; province=N/A; zipcode=0000', 'middlename=Beserel; street=Gaisano; barangay=Balangay; city_municipality=Butuan City; province=Agusan del Norte; zipcode=8600', '2026-09-16 13:25:56'),
(1238, 'action_confirm_failed', '2026-0006', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'action=verify:update_privileges.php', NULL, NULL, NULL, NULL, '2026-09-16 13:29:28'),
(1239, 'privilege_revoked', '2026-0006', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'privilege=manage_super_admin_accounts', 'unreleased', NULL, 'granted: Delete Users', 'revoked', '2026-09-16 13:29:33'),
(1240, 'logout', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:29:38'),
(1241, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:29:49'),
(1242, 'logout', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:30:36'),
(1243, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:33:02'),
(1244, 'logout', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:41:58'),
(1245, 'login_failed', NULL, '2026-0011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-16 13:45:02'),
(1246, 'login_failed', NULL, '2026-0011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'incorrect password', NULL, NULL, NULL, NULL, '2026-09-16 13:45:04'),
(1247, 'otp_sent', NULL, '2026-0011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'masked_email=e***********s@csucc.edu.ph', NULL, NULL, NULL, NULL, '2026-09-16 13:45:31'),
(1248, 'login_success', '2026-0011', '2026-0011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:48:28'),
(1249, 'logout', '2026-0011', '2026-0011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:49:21'),
(1250, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:49:35'),
(1251, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:50:13'),
(1252, 'privilege_granted', '2026-0006', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'privilege=change_account_roles', 'okayed', NULL, 'not granted', 'granted: Change Account Roles', '2026-09-16 13:51:20'),
(1253, 'logout', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:51:24'),
(1254, 'login_success', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:51:34'),
(1255, 'logout', '2026-0010', '2026-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:52:00'),
(1256, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 13:52:10'),
(1257, 'session_timeout_inactivity', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-16 13:57:11'),
(1258, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 14:11:36'),
(1259, 'session_timeout_inactivity', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', 'auto-logout after 5 minutes of inactivity', NULL, NULL, NULL, NULL, '2026-09-16 14:17:09'),
(1260, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 14:20:48'),
(1261, 'login_success', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 14:20:56'),
(1262, 'logout', '2026-0006', '2026-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '', NULL, NULL, NULL, NULL, '2026-09-16 14:22:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_number` varchar(50) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `middlename` varchar(100) DEFAULT NULL,
  `extension` varchar(50) DEFAULT NULL,
  `birth_date` date NOT NULL,
  `age` int(11) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','customer') NOT NULL DEFAULT 'customer',
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `account_status` enum('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `first_login` tinyint(1) NOT NULL DEFAULT 0,
  `employee_id` varchar(20) DEFAULT NULL,
  `customer_id` varchar(20) DEFAULT NULL,
  `street` varchar(255) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `city_municipality` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `zipcode` varchar(10) NOT NULL,
  `country` varchar(100) NOT NULL,
  `auth_question1` varchar(255) DEFAULT NULL,
  `auth_answer1` varchar(255) DEFAULT NULL,
  `auth_question2` varchar(255) DEFAULT NULL,
  `auth_answer2` varchar(255) DEFAULT NULL,
  `auth_question3` varchar(255) DEFAULT NULL,
  `auth_answer3` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `active_super_admin_flag` tinyint(1) GENERATED ALWAYS AS (if(`role` = 'super_admin' and `account_status` = 'active',1,NULL)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_number`, `lastname`, `firstname`, `middlename`, `extension`, `birth_date`, `age`, `gender`, `email`, `username`, `password`, `role`, `approval_status`, `account_status`, `first_login`, `employee_id`, `customer_id`, `street`, `barangay`, `city_municipality`, `province`, `zipcode`, `country`, `auth_question1`, `auth_answer1`, `auth_question2`, `auth_answer2`, `auth_question3`, `auth_answer3`, `created_at`, `updated_at`) VALUES
('2025-0002', 'Staunton', 'France', 'Ligad', '', '2003-11-25', 21, 'Male', 'france@csucc.edu.ph', 'france123', '$2y$10$Rk1LuUKnApsnWxXAhuTNPOI.bqe09w3a5qv.fosvwYNFQ8jAJgpNu', 'customer', 'approved', 'blocked', 0, NULL, '2025-0002', 'P-5', 'Hawaii', 'Tubay', 'Agusan Del Norte', '8606', 'Philippines', 'Who is your best friend in Elementary?', '$2y$10$GPy0jnCuwb9a2tZNfR4IGOHEj.VStGL9qrZiTxUGgvi9jC0zFWQYq', 'What is your favorite drink?', '$2y$10$gLLxnZg9fZeck0asvYqmHOL0DG0UVQvnVsqsBD7bNQHoberJ0fw02', 'What is your favorite fruit?', '$2y$10$zpDzuWDhh9l96MmLwQh5uOTjnq2SJ5o.ESm.lONK2gvenDjYFdJfq', '2025-11-19 18:03:03', '2026-09-16 13:05:39'),
('2025-0005', 'Admin', 'Super', NULL, NULL, '2000-01-01', 25, 'Other', 'superadmin@brewhaven.local', 'superadmin', '$2y$10$6pj/4Mmaqzn8xb15dhQcjOrxbSGboD/xfiLP93f/CWFOYF3FJefi.', 'super_admin', 'approved', 'blocked', 0, 'EMP-001', NULL, 'Brew Haven HQ', 'Poblacion', 'Cabadbaran City', 'Agusan Del Norte', '8605', 'Philippines', 'Who is your best friend in Elementary?', '$2y$10$YLxobZf8b3fyhhfOFgSSDe.wYHNjflhoa38kUI4y4Xmq8m7p/R0sW', 'What is your favorite sport?', '$2y$10$JVbB5qYzWa9g/BOc2w7Y7.LuQ8AUR3A2OLGzoVsy1kCJ1IRiToAL6', 'What is your favorite fruit?', '$2y$10$3x4Qmn33BmwB4G1WYHXMaO1QjhxxvDNf7MOfO6hT7.XHYtomTl6NG', '2026-08-20 02:27:42', '2026-09-15 07:20:27'),
('2026-0001', 'Bersabal', 'Kitchie', 'Awitan', '', '2005-07-02', 21, 'Female', 'kitchiebersabal263@gmail.com', 'kitchay21', '$2y$10$A4AGWnm2Ufyfx7VW4mhTx.dPCgCqe8cx8/mN2mSKGkue5UxSjHrH2', 'customer', 'approved', 'active', 0, NULL, '2026-0001', 'Purok 8', 'Calamba', 'Cabadbaran City', 'Agusan Del Norte', '8605', 'Philippines', 'Who is your best friend in Elementary?', '$2y$10$RZCRTZ.QwsaV5dUH30UcVeqZjOb5tAzpcCha4qIGr4NLXIswDPGMS', 'What is your favorite sport?', '$2y$10$a5jKSkirrApylUqwTQx4yeHn74ISJdfCogYSn/pn5WA2uingBjUAm', 'What is your favorite fruit?', '$2y$10$YNPTsmeCOroVjNMMciCfx.gwyZENv9lPDYMXR1bGNq/c2Y0TW88Pi', '2026-08-20 05:05:26', '2026-09-10 06:40:13'),
('2026-0002', 'Reyes', 'Andrea', '', NULL, '1996-04-12', 30, 'Female', 'andrea.reyes@brewhaven.local', 'admin', '$2y$10$WI7W9EpFLAUboStDrKNIeuXYfTCcUdTrAjlt7W9jBicOxhKgD5ueC', 'admin', 'approved', 'active', 0, 'EMP-002', NULL, '123 Roastery Lane', 'Poblacion', 'Cebu City', 'Cebu', '6000', 'Philippines', 'Who is your best friend in Elementary?', '$2y$10$7pehvjQy6RJqCTXfqgo25eJ.q6B5ZxTgUtlR94dObUMGK6c65UeOW', 'What is your favorite sport?', '$2y$10$VVJvy1BXLmZUVJiTmwXHau04pwsVyzvFeMCZBYm7WVnbd/h2UVqBi', 'What is your favorite fruit?', '$2y$10$qNcSQEY1Sv8e69pFdD8b5e4XgR0SjWq0J.Bd3A.BpqHiazpiTUSsq', '2026-08-20 05:56:53', '2026-08-24 08:10:16'),
('2026-0005', 'Napil', 'Edmond', 'Nakila', '', '2004-11-27', 21, 'Male', 'napile890@gmail.com', 'edmondpogi12345', '$2y$10$hob3r.pOiVrKfFQnC/vGP.xe/o3IWt/9smh6yMis4KU340/dHi3E.', 'customer', 'approved', 'active', 0, NULL, '2026-0003', 'Purok 1', 'Mabini', 'Cbr', 'Agusan Del Sur', '8600', 'Philippines', 'Who is your best friend in Elementary?', '$2y$10$JgWgMZkT1/jCe746RNB6Pub6sCeOevmaZic6KEZTExK6xq5LVA0Wq', 'What is your favorite sport?', '$2y$10$hUF/LAkzcCkbxvXq3NcSs.qaqKRc8gjiFkbK6HSBhdWGgtVsgcb2W', 'What is your favorite fruit?', '$2y$10$A2XaS8zWgM4E.4Tylqg5weDSyUT0fqYCQl9.ERva.8HHgpqyBMPzm', '2026-08-24 08:54:58', '2026-09-15 08:35:17'),
('2026-0006', 'Salazar', 'Rain', 'Beserel', NULL, '2004-11-27', 21, 'Female', 'raingliezl16@gmail.com', 'raingliezl1234', '$2y$10$aeAcO4FJVCcydEEnVMB7NOXYHrST3UED2O3kIWM5060yAkRCoknGS', 'super_admin', 'approved', 'active', 0, NULL, NULL, 'Gaisano', 'Balangay', 'Butuan City', 'Agusan del Norte', '8600', 'Philippines', 'What is the name of your favorite pet?', '$2y$10$0554sVx7tyfVPTDoZXuz7.w7MHS0kZPWr3u.aW78MAsdmVWCeUVqa', 'What is your favorite sport?', '$2y$10$ZPGM09SO2eyf5K2yf4iDCO3OS.zmMS0q9L5HB2j6rjh/CXBRvJMV.', 'What is your favorite fruit?', '$2y$10$o00f6.vBFVPK0rI5RJsi1u5ud964qU0US6t2Z/jr1WVP3o.iBhe5u', '2026-09-07 12:25:51', '2026-09-16 13:25:56'),
('2026-0007', 'Andilan', 'Tiko', '', NULL, '2004-11-25', 21, 'Male', 'tikoandilan28@gmail.com', 'tiko1234', '$2y$10$ZNtuoNmXz68g5d.SCs6udu/bLl9W0HZXNOWkzaxhWRask4XtN9Ppu', 'admin', 'approved', 'active', 0, NULL, NULL, 'N/A', 'N/A', 'N/A', 'N/A', '0000', 'Philippines', 'Who is your best friend in Elementary?', '$2y$10$U1jNwHxayI.Hk3jt3Y85Cu6YaaO/wnDDquOh/2kD7ELaY9p6Qu9gC', 'What is your favorite sport?', '$2y$10$.eGrk67VxYD5UZaK0ZrBPuMhUKqItp6DRYSNQZdQXYQ5vh6kXaQv.', 'What is your favorite fruit?', '$2y$10$SC3WVgVOACMKpIrmWObHMu8UEziFat4xFublD2ZUFEc5FQJcOqFOG', '2026-09-07 14:45:56', '2026-09-15 05:37:14'),
('2026-0008', 'Jomboy', 'Ash', '', NULL, '2005-04-27', 21, 'Male', 'ashjomboy@gmail.com', 'ash2021', '$2y$10$tQVALP7sa.88jXVmFtFEN.3SJtaE.SCM9siPV0DKvEvkiYNG3MVy2', 'admin', 'approved', 'blocked', 0, NULL, NULL, 'N/A', 'N/A', 'N/A', 'N/A', '0000', 'Philippines', 'Who is your best friend in Elementary?', '$2y$10$ZzrcQnXXLF84AU8Jw2YIGe4A.EHgi6keF1CeIEIsEObgTMvGsfkwy', 'What is your favorite sport?', '$2y$10$SXvXqomIo9djS3.VGf.57uqGYcq8Re509HeWFZlIFXlxXr75Uw2Z2', 'What is your favorite fruit?', '$2y$10$BgTmRyk8FL5LZx9bRP5jsuGnZnIjOYoQNfGHHG.lLoHqEEKXxWkMm', '2026-09-09 04:39:59', '2026-09-15 07:17:46'),
('2026-0009', 'Jomboy', 'Alexander', 'Nakila', NULL, '2005-04-25', 21, 'Male', 'alexanderjomboy4@gmail.com', 'alexander2021', '$2y$10$OXVfz6IHzK6FDwrQa8.JQuFdUW.XD9df.zgwsNusv3mGfNcl3.G4e', 'admin', 'approved', 'active', 0, NULL, NULL, 'Purok 1', 'Cabadbaran', 'Cbr', 'Agusan Del Sur', '8600', 'Philippines', 'Who is your best friend in Elementary?', '$2y$10$mIjfsMrtdCm/k0HTaEM2tuAgAsk1LlP6AU1EXPZMc1QNGnAvfdmJm', 'What is your favorite sport?', '$2y$10$UUOAmMb4i65bMk6SRhW44ukLmOQ4B0wyxli7zoT6nW26eVS0o5Bdq', 'What is your favorite fruit?', '$2y$10$h/8rACT6g8VKwkX66cndA.aQwzeK9zXesqPe0bwlSbrm5EUB7jFwu', '2026-09-10 05:05:54', '2026-09-10 05:12:04'),
('2026-0010', 'Esma', 'Ivan', 'Bersabal', NULL, '2006-11-25', 19, 'Male', 'franzivan1432@gmail.com', 'ivan24', '$2y$10$i1EEdy/aN6hkSYk.lV1jiOWR2O8RwSI/sdITL.f8xGXB5zXzINvvK', 'admin', 'approved', 'active', 0, NULL, NULL, 'Purok 1', 'Mabini', 'Cabadbaran City', 'Agusan Del Sur', '8600', 'Philippines', 'Who is your best friend in Elementary?', '$2y$10$HXzzRC/a3yWqUUWkusteI.5j76pmoTWA7fYoi6luaI7zbJo4vWCrm', 'What is your favorite sport?', '$2y$10$NX3td1OjvDelqgBNKiJmSuqulmjbTE1cQr86viP3ongq2kYZMQYfa', 'What is your favorite fruit?', '$2y$10$oIU5v3dlfD7R.UHC.syhze6eGQ7.Rs5rwsv9kgniADLuwTkz48jBW', '2026-09-15 05:53:55', '2026-09-15 09:29:25'),
('2026-0011', 'Prones', 'Edrian', 'Conteas', NULL, '2004-12-07', 21, 'Female', 'edrian.prones@csucc.edu.ph', 'pronpron1234', '$2y$10$TVr3Nllvqrs4JDetVwhGjukXpylZo9hnsE9TG1BaJ6rUw/OvcKmH2', 'customer', 'approved', 'active', 0, NULL, NULL, 'Purok 1', 'Mabini', 'Buenavista', 'Agusan del Norte', '8604', 'Philippines', 'Who is your best friend in Elementary?', '$2y$10$vmDLLgJu3C9TfYIAyLGSdu.NP.ij4.rklL1nv0bzHA5yFCatqL4LO', 'What is your favorite sport?', '$2y$10$lv5UmFFuKKfHHaJmW7RZB.p.3XvRJVONtkSB4YS.3ipdY3qxxqW2C', 'What is your favorite fruit?', '$2y$10$hgsX3.NtoGH9qA4RQji9xeX.RH1FM706HzEEAStYFFfszKbNVMMBW', '2026-09-16 13:08:37', '2026-09-16 13:12:24'),
('2026-0012', 'Omolon', 'Nickson', '', 'Sr', '2006-11-24', 19, 'Male', 'nicksonomolon10@gmail.com', 'nix1234', '$2y$10$T4S5ta1ilNv2zgv9HFheq.qoa3RaTCGIl2aqgXyE0NrI8f2VOle2K', 'customer', 'pending', 'active', 0, NULL, NULL, 'Purok 1', 'Mabini', 'Magallanes', 'Agusan Del Norte', '8600', 'Philippines', 'Who is your best friend in Elementary?', '$2y$10$Gkks6.2XnWVP860KX4XrpOy1nyd9rXw4..YBvMG0nL5t6USnk1/3m', 'What is your favorite sport?', '$2y$10$er9cxvEEQw6S0T06LFWN6OIVZlAkWjJfCXSK0wm1XNQTGEE.umHRW', 'What is your favorite fruit?', '$2y$10$nswqgWvoh2j72XtR1ilsn.an2/HUg8WXPdiogT8/xU7RbA5foydN.', '2026-09-16 13:15:31', '2026-09-16 13:15:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `active_super_admin_session`
--
ALTER TABLE `active_super_admin_session`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_super_admin_user` (`user_id_number`);

--
-- Indexes for table `admin_privileges`
--
ALTER TABLE `admin_privileges`
  ADD PRIMARY KEY (`admin_id_number`,`privilege_key`);

--
-- Indexes for table `delete_requests`
--
ALTER TABLE `delete_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_target` (`target_id_number`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer` (`customer_id_number`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event` (`event_type`),
  ADD KEY `idx_actor` (`actor_id_number`),
  ADD KEY `idx_target` (`target_id_number`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_number`),
  ADD UNIQUE KEY `id_number` (`id_number`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`),
  ADD UNIQUE KEY `uniq_single_active_super_admin` (`active_super_admin_flag`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `delete_requests`
--
ALTER TABLE `delete_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1263;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
