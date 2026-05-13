-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 17, 2025 at 04:05 PM
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
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_confirmed` tinyint(1) DEFAULT 0,
  `confirm_token` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `created_at`, `is_confirmed`, `confirm_token`) VALUES
(4, 'jenjen', '$2y$10$6brteS0.DJiV6Cy57CvQOuAXEb3LaGj/My859mSmMfG.LpTIcnxce', '2025-09-14 03:30:25', 0, 'ba872eecfa5823d09469170984b6fda7'),
(5, 'jen', '$2y$10$7/eAhgVI7TTDcN/vFQ9wwuWIpV3aVz4V97ybqQOrOIKfK21fPYudO', '2025-09-14 03:30:35', 0, '051a28174e600a4978edb0402a0f5826'),
(6, 'lance', '$2y$10$6VqylP3iq3nRMrfGd8June0bWka1AVZOkE6.pxxsDqjSAO2ggunOG', '2025-09-14 03:34:20', 1, NULL),
(7, 'lalance', '$2y$10$Fo4rz86IAYlOq.WtaiXRo.R3fDvyyC8xHM8etAAAs6jIEvekKGYDu', '2025-09-14 03:36:41', 1, NULL),
(8, 'jejen', '$2y$10$.5ZPYPcxzgfvH0eVW6Q6suZ4Z1LmLPNt7sOzW2MBY5HxoaHy7QvC6', '2025-09-14 04:15:16', 1, NULL),
(9, 'potek', '$2y$10$aqJVt5b1t6XIQT495odqVulgGZjUa6rJwU210d8urKDHCp/wVHydu', '2025-09-14 04:16:22', 1, NULL),
(10, 'admin', '$2y$10$fcD3LL0tC14Epvee4s0N1eHaN.WZ8gZBChNHfz6uQ01.2u/yT/bC2', '2025-09-17 14:04:03', 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
