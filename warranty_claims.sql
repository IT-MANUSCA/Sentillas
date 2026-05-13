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
-- Table structure for table `warranty_claims`
--

CREATE TABLE `warranty_claims` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `warranty` varchar(255) DEFAULT NULL,
  `claim_code` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `warranty_claims`
--

INSERT INTO `warranty_claims` (`id`, `order_id`, `user_id`, `product_name`, `warranty`, `claim_code`, `created_at`) VALUES
(4, 70, 29, 'Midea O-36CDN8-MD3', 'l year on parts and service; 10 years on compressor; 3 years on PCB and fan motors', 'WRT-39494772', '2025-09-08 18:02:44'),
(5, 70, 29, 'Midea O-36CDN8-MD3', 'l year on parts and service; 10 years on compressor; 3 years on PCB and fan motors', 'WRT-13AF12B3', '2025-09-09 10:01:17'),
(6, 71, 29, 'TCL ( TAC-19CSD/KEI12)', 'l year on parts and labor;', 'WRT-EE64ACBA', '2025-09-14 04:13:05'),
(7, 71, 29, 'TCL ( TAC-19CSD/KEI12)', 'l year on parts and labor;', 'WRT-ABD7A24E', '2025-09-14 04:13:08'),
(8, 71, 29, 'TCL ( TAC-19CSD/KEI12)', 'l year on parts and labor;', 'WRT-FEBA34A5', '2025-09-14 04:13:12'),
(9, 71, 29, 'TCL ( TAC-19CSD/KEI12)', 'l year on parts and labor;', 'WRT-17389066', '2025-09-14 04:13:15'),
(10, 71, 29, 'TCL ( TAC-19CSD/KEI12)', 'l year on parts and labor;', 'WRT-CC62298F', '2025-09-14 04:13:18'),
(11, 71, 29, 'TCL ( TAC-19CSD/KEI12)', 'l year on parts and labor;', 'WRT-50DE6C19', '2025-09-14 04:13:28'),
(12, 71, 29, 'TCL ( TAC-19CSD/KEI12)', 'l year on parts and labor;', 'WRT-3F8AC47D', '2025-09-14 04:13:31'),
(13, 71, 29, 'TCL ( TAC-19CSD/KEI12)', 'l year on parts and labor;', 'WRT-BA2E462B', '2025-09-14 04:13:34'),
(14, 71, 29, 'TCL ( TAC-19CSD/KEI12)', 'l year on parts and labor;', 'WRT-5059F4C6', '2025-09-14 04:13:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `warranty_claims`
--
ALTER TABLE `warranty_claims`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `claim_code` (`claim_code`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `warranty_claims`
--
ALTER TABLE `warranty_claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `warranty_claims`
--
ALTER TABLE `warranty_claims`
  ADD CONSTRAINT `warranty_claims_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warranty_claims_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
