-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 22, 2026 at 04:21 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `my_shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Stationery', '', '2026-06-20 14:10:08'),
(2, 'Books and Notes', 'Fiction, non-fiction, and educational materials', '2026-06-20 14:10:08'),
(3, 'Electronics', '', '2026-06-20 14:10:08'),
(4, 'Bicycles', '', '2026-06-20 14:10:08'),
(5, 'Fashion', '', '2026-06-21 14:15:56'),
(6, 'Hostel Essentials', '', '2026-06-22 05:33:18'),
(7, 'DU Merchandise', '', '2026-06-22 05:33:29'),
(8, 'Second-Hand Market', '', '2026-06-22 05:33:43'),
(9, 'Food &amp; Snacks', '', '2026-06-22 05:33:54'),
(10, 'Others', '', '2026-06-22 05:34:00');

-- --------------------------------------------------------

--
-- Table structure for table `lost_items`
--

CREATE TABLE `lost_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(200) NOT NULL,
  `category` enum('lost','found') NOT NULL,
  `contact_info` varchar(100) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `status` enum('open','resolved') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lost_items`
--

INSERT INTO `lost_items` (`id`, `user_id`, `title`, `description`, `location`, `category`, `contact_info`, `image_url`, `status`, `created_at`, `updated_at`) VALUES
(1, 10, 'mobile', 'dadfa fs;af; ri[rg', 'Central Library', 'lost', '0137842574729', 'lost_1782111178_6a38dbca42a18.jpeg', 'open', '2026-06-22 06:52:58', '2026-06-22 06:52:58'),
(2, 10, 'gfs&#039;', 'flfifs rrrfjf', 'Shamsun Nahar Hall', 'found', '0137842574729', 'lost_1782111217_6a38dbf19d230.jpeg', 'open', '2026-06-22 06:53:37', '2026-06-22 06:53:37');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shop_id` int(11) DEFAULT NULL,
  `order_number` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_address` text NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `shop_id`, `order_number`, `total_amount`, `shipping_address`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, 'ORD-20231015-001', 179.93, '123 Main Street, New York, NY 10001', 'shipped', '2026-06-20 14:10:08', '2026-06-21 14:47:12'),
(2, 2, NULL, 'ORD-20231020-002', 79.99, '123 Main Street, New York, NY 10001', 'shipped', '2026-06-20 14:10:08', '2026-06-20 14:10:08'),
(3, 3, NULL, 'ORD-20231018-003', 748.99, '456 Oak Avenue, Los Angeles, CA 90001', 'delivered', '2026-06-20 14:10:08', '2026-06-21 14:41:20'),
(4, 5, NULL, 'ORD-TEST-20260620-5682', 159.98, '123 Test Street, Test City', 'pending', '2026-06-20 20:31:25', '2026-06-20 20:31:25'),
(18, 6, NULL, 'ORD-20260621-9530', 91624.00, '428, Dhaka, Bangladesh, 10001', 'pending', '2026-06-21 04:49:13', '2026-06-21 04:49:13'),
(19, 6, NULL, 'ORD-20260621-8602', 182756.00, 'dhaka, Dhaka, Bangladesh, 3500', 'pending', '2026-06-21 04:54:59', '2026-06-21 04:54:59'),
(20, 6, NULL, 'ORD-20260621-9092', 182756.00, 'dhaka, Dhaka, Bangladesh, 35002', 'pending', '2026-06-21 04:55:09', '2026-06-21 04:55:09'),
(21, 6, NULL, 'ORD-20260621-0498', 182756.00, 'dhaka, Dhaka, Bangladesh, 35002', 'pending', '2026-06-21 04:57:52', '2026-06-21 04:57:52'),
(22, 6, NULL, 'ORD-20260621-6346', 182756.00, 'dhaka, Dhaka, Bangladesh, 35002', 'pending', '2026-06-21 04:58:00', '2026-06-21 04:58:00'),
(23, 6, NULL, 'ORD-20260621-2373', 182756.00, 'dhaka, Dhaka, Bangladesh, 35002', 'pending', '2026-06-21 04:58:06', '2026-06-21 04:58:06'),
(24, 6, NULL, 'ORD-20260621-4840', 182756.00, '428, Dhaka, Bangladesh, 100011', 'pending', '2026-06-21 04:58:20', '2026-06-21 04:58:20'),
(25, 6, NULL, 'ORD-20260621-1756', 182756.00, 'dhaka, Dhaka, Bangladesh, 35002', 'pending', '2026-06-21 04:59:58', '2026-06-21 04:59:58'),
(26, 6, NULL, 'ORD-20260621-0146', 91132.00, '428, Dhaka, Bangladesh, 1000', 'pending', '2026-06-21 05:00:29', '2026-06-21 05:00:29'),
(27, 5, NULL, 'ORD-20260621-2154', 91132.00, '428, Dhaka, Bangladesh, 1000', 'pending', '2026-06-21 05:02:27', '2026-06-21 05:02:27'),
(28, 5, NULL, 'ORD-20260621-2893', 129229.00, '428, Dhaka, Bangladesh, 1000', 'pending', '2026-06-21 05:17:57', '2026-06-21 05:17:57'),
(29, 5, NULL, 'ORD-20260621-4764', 63000.00, '428, Dhaka, Bangladesh, 1000', 'pending', '2026-06-21 11:51:22', '2026-06-21 11:51:22'),
(35, 5, NULL, 'ORD-20260621-6943', 45670.98, '428, Dhaka, Bangladesh, 1000', 'cancelled', '2026-06-21 12:29:01', '2026-06-21 14:23:57'),
(36, 5, NULL, 'ORD-20260621-3906', 45566.00, '428, Dhaka, Bangladesh, 1000', 'pending', '2026-06-21 12:33:41', '2026-06-21 12:33:41'),
(37, 5, NULL, 'ORD-20260621-5467', 444.00, '428, Dhaka, Bangladesh, 1000', 'pending', '2026-06-21 12:40:43', '2026-06-21 12:40:43'),
(38, 5, NULL, 'ORD-20260621-1933', 45566.00, '428, Dhaka, Bangladesh, 1000', 'delivered', '2026-06-21 13:23:14', '2026-06-21 14:23:36'),
(39, 5, NULL, 'ORD-20260621-5004', 45566.00, '428, Dhaka, Bangladesh, 1000', 'pending', '2026-06-21 17:46:10', '2026-06-21 17:46:10'),
(40, 5, NULL, 'ORD-20260621-2626', 45566.00, '428, Dhaka, Bangladesh, 1000', 'cancelled', '2026-06-21 17:56:58', '2026-06-21 18:04:03'),
(41, 5, NULL, 'ORD-20260621-4944', 42000.00, '428, Dhaka, Bangladesh, 1000', 'cancelled', '2026-06-21 18:42:31', '2026-06-21 18:42:48'),
(42, 7, NULL, 'ORD-20260621-7036', 24000.00, '428, Dhaka, Bangladesh, 1000', 'delivered', '2026-06-21 19:44:01', '2026-06-21 19:45:41'),
(43, 5, NULL, 'ORD-20260621-7304', 2400.00, '428, Dhaka, Bangladesh, 1000', 'pending', '2026-06-21 20:25:38', '2026-06-21 20:25:38'),
(44, 5, NULL, 'ORD-20260621-2617', 49.00, '428, Dhaka, Bangladesh, 1000', 'pending', '2026-06-21 20:34:24', '2026-06-21 20:34:24'),
(57, 5, NULL, 'ORD-20260621-8719', 49.00, '428tyt, Dhakgsdfa, Bangladesh, 100033', 'pending', '2026-06-21 21:09:20', '2026-06-21 21:09:20'),
(58, 8, NULL, 'ORD-20260621-1521', 868.00, '428, Dhaka, Bangladesh, 1000', 'pending', '2026-06-21 21:09:41', '2026-06-21 21:09:41'),
(59, 9, NULL, 'ORD-20260622-9461', 868.00, '428, Dhaka, Bangladesh, 1000', 'pending', '2026-06-22 04:38:51', '2026-06-22 04:38:51'),
(60, 8, NULL, 'ORD-20260622-1911', 1088.00, '428, Dhaka, Bangladesh, 1000', 'pending', '2026-06-22 04:42:06', '2026-06-22 04:42:06'),
(61, 5, NULL, 'ORD-20260622-2830', 544.00, '428, Dhaka, Bangladesh, 1000', 'pending', '2026-06-22 05:39:24', '2026-06-22 05:39:24'),
(62, 5, NULL, 'ORD-20260622-8076', 456.00, '428, Dhaka, Bangladesh, 1000', 'pending', '2026-06-22 06:11:31', '2026-06-22 06:11:31'),
(63, 8, NULL, 'ORD-20260622-8603', 477.00, '428, Dhaka, Bangladesh, 1000', 'processing', '2026-06-22 13:48:20', '2026-06-22 13:49:19');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `product_price`, `quantity`) VALUES
(49, 43, 36, 'Saiful Islam', 1200.00, 2),
(50, 44, 37, 'b Flower', 22.00, 2),
(63, 57, 37, 'b Flower', 22.00, 2),
(64, 58, 38, 'mobile phone', 434.00, 2),
(65, 59, 38, 'mobile phone', 434.00, 2),
(66, 60, 39, 'Sun flower', 544.00, 2),
(67, 61, 39, 'Sun flower', 544.00, 1),
(68, 62, 38, 'mobile phone', 434.00, 1),
(69, 62, 37, 'b Flower', 22.00, 1),
(70, 63, 38, 'mobile phone', 434.00, 1),
(71, 63, 35, 'flower', 21.00, 1),
(72, 63, 37, 'b Flower', 22.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(4) DEFAULT 1,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `avg_rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `shop_id`, `category_id`, `name`, `description`, `price`, `stock_quantity`, `image_url`, `created_at`, `updated_at`, `is_active`, `status`, `avg_rating`, `total_reviews`) VALUES
(34, 1, 3, 'bike', 'new', 220000.00, 21, 'product_1782071554_6a384102b1f58.jpeg', '2026-06-21 19:52:34', '2026-06-21 21:22:53', 1, 'approved', 0.00, 0),
(35, 1, 2, 'flower', 'wow', 21.00, 2, 'product_1782071586_6a3841228860c.jpeg', '2026-06-21 19:53:06', '2026-06-22 13:48:20', 1, 'approved', 0.00, 0),
(36, 3, 1, 'Saiful Islam', 'fe', 1200.00, 0, 'product_1782073460_6a38487465652.jpeg', '2026-06-21 20:24:20', '2026-06-21 21:22:53', 1, 'approved', 0.00, 0),
(37, 3, 3, 'b Flower', 'e', 22.00, 50, 'product_1782074031_6a384aaf9df59.jpeg', '2026-06-21 20:33:51', '2026-06-22 13:48:20', 1, 'approved', 0.00, 0),
(38, 2, 2, 'mobile phone', 'rr', 434.00, 35, 'product_1782075435_6a38502bbc82f.jpeg', '2026-06-21 20:57:15', '2026-06-22 13:48:20', 1, 'approved', 0.00, 0),
(39, 4, 2, 'Sun flower', '', 544.00, 62, 'product_1782102994_6a38bbd2617f7.jpeg', '2026-06-22 04:36:34', '2026-06-22 05:39:24', 1, 'pending', 0.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `title` varchar(200) DEFAULT NULL,
  `review` text NOT NULL,
  `pros` text DEFAULT NULL,
  `cons` text DEFAULT NULL,
  `images` text DEFAULT NULL,
  `is_verified_purchase` tinyint(4) DEFAULT 0,
  `is_approved` tinyint(4) DEFAULT 1,
  `helpful_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `product_reviews`
--
DELIMITER $$
CREATE TRIGGER `update_product_rating` AFTER INSERT ON `product_reviews` FOR EACH ROW BEGIN
    UPDATE products p
    SET 
        p.avg_rating = (
            SELECT AVG(rating) 
            FROM product_reviews 
            WHERE product_id = NEW.product_id 
            AND is_approved = 1
        ),
        p.total_reviews = (
            SELECT COUNT(*) 
            FROM product_reviews 
            WHERE product_id = NEW.product_id 
            AND is_approved = 1
        )
    WHERE p.id = NEW.product_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_product_rating_on_approval` AFTER UPDATE ON `product_reviews` FOR EACH ROW BEGIN
    IF NEW.is_approved != OLD.is_approved THEN
        UPDATE products p
        SET 
            p.avg_rating = (
                SELECT COALESCE(AVG(rating), 0)
                FROM product_reviews 
                WHERE product_id = NEW.product_id 
                AND is_approved = 1
            ),
            p.total_reviews = (
                SELECT COUNT(*) 
                FROM product_reviews 
                WHERE product_id = NEW.product_id 
                AND is_approved = 1
            )
        WHERE p.id = NEW.product_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `review_helpful_votes`
--

CREATE TABLE `review_helpful_votes` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_helpful` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_images`
--

CREATE TABLE `review_images` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('currency', 'BDT', '2026-06-22 05:22:18'),
('free_shipping_threshold', '50.00', '2026-06-22 05:22:18'),
('maintenance_mode', '1', '2026-06-22 05:22:18'),
('shipping_cost', '20', '2026-06-22 05:22:18'),
('site_address', 'Dhaka University', '2026-06-22 05:22:18'),
('site_email', 'saif387212@gmail.com', '2026-06-22 05:22:18'),
('site_name', 'My Shop', '2026-06-22 05:22:18'),
('site_phone', '01864138063', '2026-06-22 05:22:18'),
('tax_rate', '0.00', '2026-06-22 05:22:18');

-- --------------------------------------------------------

--
-- Table structure for table `shops`
--

CREATE TABLE `shops` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shop_name` varchar(100) NOT NULL,
  `shop_slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `is_approved` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shops`
--

INSERT INTO `shops` (`id`, `user_id`, `shop_name`, `shop_slug`, `description`, `logo`, `banner`, `address`, `phone`, `email`, `is_active`, `is_approved`, `created_at`, `updated_at`) VALUES
(1, 2, 'My Shop', 'my-shop', NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, '2026-06-21 16:05:11', '2026-06-21 16:05:11'),
(2, 5, 'Toronngo', 'toronngo', 'Dhaka', NULL, NULL, '428', '01864138063', 'saif387212@gmail.com', 1, 1, '2026-06-21 17:15:58', '2026-06-21 17:16:57'),
(3, 8, 'Digonto', 'digonto', '', NULL, NULL, 'dhaka', '01336016378', 'alamin@gmail.com', 1, 1, '2026-06-21 20:02:00', '2026-06-21 20:05:47'),
(4, 9, 'Bondu', 'bondu', '', NULL, NULL, 'Dhaka, University', '+8801336016328', 'sajjad@gmail.com', 1, 1, '2026-06-22 04:34:44', '2026-06-22 05:23:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `batch` varchar(10) DEFAULT NULL,
  `hall` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `is_verified` tinyint(4) DEFAULT 0,
  `verification_doc` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `student_id`, `department`, `batch`, `hall`, `password`, `is_admin`, `is_verified`, `verification_doc`, `last_login`, `created_at`, `updated_at`) VALUES
(2, 'John Doe', 'john@example.com', NULL, NULL, NULL, NULL, '$2y$10$2KXwao01qX50UFlhqNvNvOPzlIf4z2DsmH19kqnMlLrbfwaTV4fcK', 1, 0, NULL, '2026-06-20 16:47:51', '2026-06-20 14:10:08', '2026-06-20 17:04:28'),
(3, 'Jane Smith', 'jane@example.com', NULL, NULL, NULL, NULL, '$2y$10$2KXwao01qX50UFlhqNvNvOPzlIf4z2DsmH19kqnMlLrbfwaTV4fcK', 1, 0, NULL, '2026-06-20 16:58:45', '2026-06-20 14:10:08', '2026-06-20 17:04:29'),
(5, 'Saiful Islam', 'saif387212@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$2KXwao01qX50UFlhqNvNvOPzlIf4z2DsmH19kqnMlLrbfwaTV4fcK', 1, 0, NULL, '2026-06-22 13:49:00', '2026-06-20 16:30:05', '2026-06-22 13:49:00'),
(6, 'Saiful', 'si7246013@gamil.com', NULL, NULL, NULL, NULL, '$2y$10$BOU2FFAI17LLU39v9AWT0u00VJd.3.FarASX.WBE.4vDP5YlkPjOK', 0, 0, NULL, '2026-06-22 04:40:53', '2026-06-20 17:33:01', '2026-06-22 04:40:53'),
(7, 'Sabbir', 'sabbir@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$qSOxKipOpsjbWen7EPV7Zu.c52muuLJ0FjMO2FuI87/9XB8RqdBXK', 0, 0, NULL, NULL, '2026-06-21 19:43:18', '2026-06-21 19:43:18'),
(8, 'al amin', 'alamin@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$P2Ud/bnTeMMA8psLYZ7IOeD9PQjhEWT5Ms8NTGMnW5ntPyc5RFe36', 0, 0, NULL, '2026-06-22 13:40:42', '2026-06-21 20:01:28', '2026-06-22 13:40:42'),
(9, 'sajjad', 'sajjad@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$UAFDndPnQdrJl7eJ2O9R3e/Locq0JsO44/U5dtYxUJ.Ra.rBGhGOu', 0, 0, NULL, '2026-06-22 04:42:20', '2026-06-22 04:34:02', '2026-06-22 04:42:20'),
(10, 'Saiful Islam', 'saif@du.ac.bd', '2024117436', 'Faculty of Earth and Environmental Sciences', '2024', 'Shahid Ziaur Rahman Hall', '$2y$10$lx5V2UJK03Qi5HYjQ9.TB.tKqCD09OevQJhOBRqVycGjOKlkwmEkm', 0, 0, NULL, NULL, '2026-06-22 06:44:22', '2026-06-22 06:44:22');

-- --------------------------------------------------------

--
-- Table structure for table `vendor_orders`
--

CREATE TABLE `vendor_orders` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','accepted','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendor_orders`
--

INSERT INTO `vendor_orders` (`id`, `order_id`, `shop_id`, `vendor_id`, `total_amount`, `status`, `created_at`, `updated_at`) VALUES
(2, 44, 2, 5, 99.99, 'accepted', '2026-06-21 20:53:52', '2026-06-21 21:15:30'),
(3, 57, 3, 8, 44.00, 'pending', '2026-06-21 21:09:20', '2026-06-21 21:09:20'),
(4, 58, 2, 5, 868.00, 'pending', '2026-06-21 21:09:41', '2026-06-21 21:15:12'),
(5, 59, 2, 5, 868.00, 'pending', '2026-06-22 04:38:51', '2026-06-22 04:38:51'),
(6, 60, 4, 9, 1088.00, 'shipped', '2026-06-22 04:42:06', '2026-06-22 05:23:43'),
(7, 61, 4, 9, 544.00, 'pending', '2026-06-22 05:39:24', '2026-06-22 05:39:24'),
(8, 62, 2, 5, 434.00, 'pending', '2026-06-22 06:11:31', '2026-06-22 06:11:31'),
(9, 62, 3, 8, 22.00, 'pending', '2026-06-22 06:11:31', '2026-06-22 06:11:31'),
(10, 63, 2, 5, 434.00, 'pending', '2026-06-22 13:48:20', '2026-06-22 13:48:20'),
(11, 63, 1, 2, 21.00, 'pending', '2026-06-22 13:48:20', '2026-06-22 13:48:20'),
(12, 63, 3, 8, 22.00, 'pending', '2026-06-22 13:48:20', '2026-06-22 13:48:20');

-- --------------------------------------------------------

--
-- Table structure for table `vendor_settings`
--

CREATE TABLE `vendor_settings` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `fk_cart_product` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lost_items`
--
ALTER TABLE `lost_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `fk_order_user` (`user_id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orderitem_order` (`order_id`),
  ADD KEY `fk_orderitem_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_product_category` (`category_id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_rating` (`product_id`,`rating`),
  ADD KEY `idx_user_reviews` (`user_id`,`created_at`);

--
-- Indexes for table `review_helpful_votes`
--
ALTER TABLE `review_helpful_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`review_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `review_images`
--
ALTER TABLE `review_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `review_id` (`review_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shop_name` (`shop_name`),
  ADD UNIQUE KEY `shop_slug` (`shop_slug`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vendor_orders`
--
ALTER TABLE `vendor_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `shop_id` (`shop_id`),
  ADD KEY `vendor_id` (`vendor_id`);

--
-- Indexes for table `vendor_settings`
--
ALTER TABLE `vendor_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `lost_items`
--
ALTER TABLE `lost_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_helpful_votes`
--
ALTER TABLE `review_helpful_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_images`
--
ALTER TABLE `review_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shops`
--
ALTER TABLE `shops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `vendor_orders`
--
ALTER TABLE `vendor_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `vendor_settings`
--
ALTER TABLE `vendor_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `lost_items`
--
ALTER TABLE `lost_items`
  ADD CONSTRAINT `lost_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orderitem_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_helpful_votes`
--
ALTER TABLE `review_helpful_votes`
  ADD CONSTRAINT `review_helpful_votes_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `product_reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_helpful_votes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_images`
--
ALTER TABLE `review_images`
  ADD CONSTRAINT `review_images_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `product_reviews` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shops`
--
ALTER TABLE `shops`
  ADD CONSTRAINT `shops_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_orders`
--
ALTER TABLE `vendor_orders`
  ADD CONSTRAINT `vendor_orders_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vendor_orders_ibfk_2` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vendor_orders_ibfk_3` FOREIGN KEY (`vendor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_settings`
--
ALTER TABLE `vendor_settings`
  ADD CONSTRAINT `vendor_settings_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
