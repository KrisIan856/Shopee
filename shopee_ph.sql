-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2026 at 11:15 AM
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
-- Database: `shopee_ph`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(60) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('buyer','seller','admin','rider') NOT NULL DEFAULT 'buyer',
  `full_name` varchar(120) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `full_name`, `avatar`, `phone`, `address`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@shopeeph.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin User', NULL, '09171234567', 'Taguig City, Metro Manila', 1, '2026-04-19 08:43:35', '2026-04-19 08:43:35'),
(2, 'seller_juan', 'juan@shopeeph.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', 'Juan dela Cruz', NULL, '09281234567', 'Makati City, Metro Manila', 1, '2026-04-19 08:43:35', '2026-04-19 08:43:35'),
(3, 'seller_maria', 'maria@shopeeph.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', 'Maria Santos', NULL, '09391234567', 'Quezon City, Metro Manila', 1, '2026-04-19 08:43:35', '2026-04-19 08:43:35'),
(4, 'buyer_pedro', 'pedro@shopeeph.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer', 'Pedro Reyes', 'avatar_6a0199a60ba2b.jpg', '09501234567', 'Pasig City, Metro Manila', 1, '2026-04-19 08:43:35', '2026-05-11 16:56:06'),
(5, 'buyer_ana', 'ana@shopeeph.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer', 'Ana Gonzalez', NULL, '09611234567', 'Cebu City', 1, '2026-04-19 08:43:35', '2026-05-10 15:53:32'),
(6, 'kris ian', 'kyutaper856@gmail.com', '$2y$10$QzrnOrkB9ZDyQrpsGR3dzu6ni8ndbpWRQX8X7j9TibjmodgX1Eyz2', 'seller', NULL, NULL, NULL, NULL, 1, '2026-04-19 08:53:20', '2026-04-19 08:53:20'),
(7, 'krisian', 'krisian@shopeeph.com', '$2y$10$anZAvo48F800vSiu.kedxOJi05JSogXvCpx3.n9FjpmxyvaW9YWc2', 'admin', 'Krisian Admin', NULL, NULL, NULL, 1, '2026-05-10 15:53:12', '2026-05-10 15:53:12'),
(8, 'Sian Bandiola', 'ban@gmail.com', '$2y$10$FFFnd4iMXzBdzAg1U4x8Hez2LuDJZXjZNV6Qt.VnpGE0B/hdQU.hS', 'seller', NULL, NULL, NULL, NULL, 1, '2026-05-10 16:06:56', '2026-05-10 16:06:56'),
(9, 'rider', 'rider@gmail.com', '$2y$10$EIHRtYM5.8cGYy3LEyV92eWBtbkzF3dNnHFS02n41aM7lG8r6DXdC', 'rider', NULL, NULL, NULL, NULL, 1, '2026-05-11 16:19:12', '2026-05-11 16:19:32'),
(10, 'seler', 'seler@gmail.com', '$2y$10$82qFsfRhEpoOYj1fM9zzeOXTtdP8ZlNiSPYL9cDg9tX3htYFw4aei', 'seller', 'seller seller', '', '', '', 1, '2026-05-11 16:29:29', '2026-05-11 16:51:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
