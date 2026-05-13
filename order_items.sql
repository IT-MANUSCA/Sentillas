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
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `brand_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_name`, `brand_name`, `quantity`, `created_at`) VALUES
(7, 70, 'Midea O-36CDN8-MD3', 'Midea', 1, '2025-09-08 18:02:44'),
(8, 70, 'Midea O-36CDN8-MD3', 'Midea', 1, '2025-09-09 10:01:17'),
(9, 71, 'TCL ( TAC-19CSD/KEI12)', 'TCL', 1, '2025-09-14 04:13:05'),
(10, 71, 'TCL ( TAC-19CSD/KEI12)', 'TCL', 1, '2025-09-14 04:13:08'),
(11, 71, 'TCL ( TAC-19CSD/KEI12)', 'TCL', 1, '2025-09-14 04:13:12'),
(12, 71, 'TCL ( TAC-19CSD/KEI12)', 'TCL', 1, '2025-09-14 04:13:15'),
(13, 71, 'TCL ( TAC-19CSD/KEI12)', 'TCL', 1, '2025-09-14 04:13:18'),
(14, 71, 'TCL ( TAC-19CSD/KEI12)', 'TCL', 1, '2025-09-14 04:13:28'),
(15, 71, 'TCL ( TAC-19CSD/KEI12)', 'TCL', 1, '2025-09-14 04:13:31'),
(16, 71, 'TCL ( TAC-19CSD/KEI12)', 'TCL', 1, '2025-09-14 04:13:34'),
(17, 71, 'TCL ( TAC-19CSD/KEI12)', 'TCL', 1, '2025-09-14 04:13:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
