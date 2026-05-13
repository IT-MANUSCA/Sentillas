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
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT 'uploads/default.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `stock` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `brand`, `price`, `image`, `created_at`, `stock`) VALUES
(25, 'Panasonic (CS/CU-XU9AKQ)', 'Panasonic', 43998.00, 'uploads/1757251326_image_2025-09-07_212204953.png', '2025-09-07 13:22:06', 5),
(26, 'Carrier 53XFV055CHR', 'Carrier', 161299.00, 'uploads/1757251813_image_2025-09-07_212936695.png', '2025-09-07 13:30:13', 5),
(27, 'LG (HS09IPX3)', 'LG', 39998.00, 'uploads/1757252112_image_2025-09-07_213511054.png', '2025-09-07 13:35:12', 5),
(28, 'TCL ( TAC-19CSD/KEI12)', 'TCL', 35998.00, 'uploads/1757252371_image_2025-09-07_213857553.png', '2025-09-07 13:39:31', 0),
(29, 'Midea O-36CDN8-MD3', 'Midea', 108198.00, 'uploads/1757252619_image_2025-09-07_214339096.png', '2025-09-07 13:43:39', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
