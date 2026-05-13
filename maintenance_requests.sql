-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 14, 2025 at 06:20 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sentillas_aircon`
--

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `brand_name` varchar(255) DEFAULT NULL,
  `issue` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `maintenance_date` datetime DEFAULT NULL,
  `status` enum('Pending','Confirmed','Declined') DEFAULT 'Pending',
  `admin_remarks` text DEFAULT NULL,
  `estimated_cost` decimal(10,2) DEFAULT 0.00,
  `image_path` varchar(255) DEFAULT NULL,
  `video_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_requests`
--

INSERT INTO `maintenance_requests` (`id`, `user_id`, `product_name`, `brand_name`, `issue`, `created_at`, `maintenance_date`, `status`, `admin_remarks`, `estimated_cost`, `image_path`, `video_path`) VALUES
(29, 29, 'dsa', 'dfasfd', '\nAdditional details: safdsa', '2025-09-09 10:37:13', '2025-09-27 00:07:00', 'Confirmed', 'dsadsa', 3231.00, NULL, NULL),
(35, 29, 'tcl', 'dsa', 'Refrigerant leak\nAdditional details: dsadas', '2025-09-14 03:03:58', NULL, 'Pending', NULL, 0.00, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD CONSTRAINT `maintenance_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
