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
-- Table structure for table `product_descriptions`
--

CREATE TABLE `product_descriptions` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `specs` text DEFAULT NULL,
  `features` text DEFAULT NULL,
  `warranty` text DEFAULT NULL,
  `additional_info` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_descriptions`
--

INSERT INTO `product_descriptions` (`id`, `product_id`, `specs`, `features`, `warranty`, `additional_info`, `created_at`, `updated_at`) VALUES
(4, 25, 'Aircon Type: Split\r\nSplit Type: Hi-Wall\r\nCooling Capacity (HP): 1HP\r\nLength (cm): Indoor: 20| Outdoor: 37.4\r\nWidth (cm): Indoor: 87| Outdoor: 95.2\r\nHeight (cm): Indoor: 29.5| Outdoor: 51.1\r\nGross Weight (kg): Indoor: 12| Outdoor: 19\r\nNet Weight( (kg): Indoor: 10| Outdoor: 17', 'nanoe x Technology with Mark 3 Generator Inside Cleaning on Demand\r\nBuilt- in Easy Connect Wi-Fi System\r\nAuto Adjust to the Optional Eco\r\nPanasonic Inverter Reduces Power Consumption with Consistent Cooling\r\nComfort\r\nWith Aerowings Airflow\r\nAuto-X Provides Stronger & Faster Cooling (25% Cooler Faster)\r\nHumidity Control with Humidity Sensor + Dry Mode\r\nAl ECO\r\n640W\r\n1-Phase/ 230VAC/ 60Hz', '1 Year on parts and labor, 3 years on PCB, 12 years on compressor', 'Free Delivery, Free Installation', '2025-09-07 13:28:36', '2025-09-07 13:28:36'),
(5, 26, 'Aircon Type: Package\r\nLength (cm): Indoor: 39.2l Outdoor:\r\n36.8\r\nWidth (cm): Indoor: 58.8 | Outdoor:\r\n93.8\r\nHeight (cm): Indoor: 192.4 | Outdoor:\r\n133.6\r\nGross Weight (kg): Indoor: 66.7l\r\nOutdoor: 113.5\r\nNet Weight (kg): Indoor: 571\r\nOutdoor: 95\r\nCooling Capacity (HP): 6HP\r\n', 'The XFV Aura Inverter is a 5-Star Floor Mounted that features 5-Star rated\r\nEnergy Efficiency with Air Purifying Tencnology.\r\nMain Features:\r\nHepa Filter: The high-density health filter can resist 95% of the dust. It Is equipped with Auxiliary H1l Filter which can fully absorb harmful gases like Formaldehyde, Benzene, Toluene, Xylene, Smoke Smell and more.\r\nUvc Bulb: Ultra Violet C refers to the Short Wavelength (Between 200 To 280\r\nNanometers) Radiant Energy used in Ultraviolet Germicidal Irradiation (uvGi) Applications, which is highly effective at killing bacteria and viruses by destroying the molecular bonds that hold their DNA together. In+N1 doing so, the virus loses the ability to reproduce.\r\n20-Meter-Long Distance Airflow: The large diameter centrifugal fan brings a strong supply effect of more pleasant wind. Air Directions Of Up, Down, Left And Right give users a comfortable feeling reliable\r\nGold Fin: The unique Anti-Corrosive Golden Coating on the heat exchangers can withstand the Salty Air, Rein and other Corrosive Elements. It Also Effectively Prevents Bacteria From Breeding And Improves Heat Transfer Efficiency.\r\nOther Features: Automatic Restart, Turbo Cooling, Dehumidification, Sleep Mode, Auto Mode, 3-Minute Compressor Protection, DC Condenser, Fan Motor, Top Mounted Inverter Controls on Condensing Unit.', '1 year on parts and labor;\r\n4 years on compressor', 'Free Delivery, Free Installation', '2025-09-07 13:33:11', '2025-09-07 13:33:11'),
(6, 27, 'Campaigns: Free Delivery, Free Installation, Best Deals\r\nAircon Type: Split\r\nSplit Type: Yes\r\n\r\nEER Rating: 5.44\r\nCooling Capacity (HP): 1HP\r\nwidth (cm): Indoor: 79.0 | Outdoor:\r\n71.7\r\nHeight (cm): Indoor: 30.7 | Outdoor:\r\n49.5\r\nGross Weight (kg): Indoor: 11.5 l\r\nOutdoor: 21\r\nNet Weight (kg): Indoor: 10.2l\r\nOutcloor: 19.6\r\nLength (cm): Indoor: 23.5 l Outdoor: 23', 'Unit Code:\r\nIndoor Unit Code:\r\nHSNO9IPX3\r\nOutdoor Unit Code:\r\nHSLUSIPKE\r\nDual Inverter Compressor\r\nActive Energy Control\r\nAll Cleaning\r\nlonizer\r\nMicro Dust Filter\r\nDry (Dehumidification) Operation\r\nJet Cool\r\nSleep Mode\r\nSmart Diagnosis\r\nLG ThinQ\r\nKid Manager\r\nSoft Air\r\nDeluxe Inverter\r\nColor: White\r\n810 watts\r\nSmart Vi-Fi', '1 year on labor; 10 years on compressor', 'Free Delivery, Free Installation', '2025-09-07 13:38:01', '2025-09-07 13:38:01'),
(7, 28, 'Campaigns: Bestsellers, Free\r\nInstallation\r\nAircon Type: Split\r\nhlights\r\nSplit Type: Yes\r\nCooling Capacity (HP): 2HP\r\nLength (cm): Indoor: 92 | Outdoor:\r\n79.5\r\nWidth (cm): Indoor: 19.5 | Outdoor:\r\n30.5\r\nHeight (cm): Indoor: 30.6 | Outdoor:\r\n54.9\r\nGross Weight (kg): Indoor: 261\r\nOutcoor: 12\r\n10 years on compressor\r\nNet Weight (kg): Indoor: 23 | Outdoor: 10', 'Unit Code:\r\nIndoor Unit Code:\r\nTAC-19CSD/KEI2-ID\r\nOutdoor Unit Code:\r\nTAC-19CSD/KEI2-OD\r\nT-Al Technology with 37% Energy Saving vs Full DC Inverter T-Al Technology with 80% Energy Saving vs Non- Inverter AC\r\nGolden Titanium Fins\r\nCorrosion-Proof Copper U Bends\r\nHigh-Stability DC PCB with Double-Sided Conformal Protection\r\n27dB low Noise Operation\r\n30 seconds Fast Cooling\r\nDU and ODU Self-cleaning\r\nFilter Cleaning Reminder\r\nGentle Breeze\r\n4 Way Air flow\r\n4 Easys-Easy Installation, Easy Maintenance, Easy Cleaning and Easy Assembly', 'l year on parts and labor;', 'Free delivery, Free Installation', '2025-09-07 13:41:25', '2025-09-07 13:41:25'),
(8, 29, '\r\nAircon Type: Package\r\nLength (cm): Indoor: 54 | Outdoor:\r\n89\r\nWidth (cm): Indoor: 41 | Outdoor:\r\n34.2\r\nHeight (cm): Indoor: 182.5 | Outdoor:\r\n67.3\r\nGross Weight (kg): Indoor: 64.5 l\r\nOutdoor: 47.1\r\nNet Weight (kg): Indoor: 48.8l\r\nOutdoor: 43.9\r\nWarranty: l year on parts and service; 10 years on compressor; 3 years on PCB and fan moto', 'ECo-Friendly Refrigerant R02\r\n3m Half Filter\r\nIndependent Dehumidifier\r\nBuilt-In Drain Pump\r\nSlim Design\r\nSleep Mode\r\nHyper Grapfins\r\nAutomatic Swing (Motorized Louver)\r\n4200 watts\r\nEstimated Energy Cost:\r\nMonthly: 445.5 kiwh\r\nDaily: 14.85 kWh', 'l year on parts and service; 10 years on compressor; 3 years on PCB and fan motors', 'Free Delivery, Free Installation', '2025-09-07 13:45:34', '2025-09-07 13:45:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `product_descriptions`
--
ALTER TABLE `product_descriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `product_descriptions`
--
ALTER TABLE `product_descriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `product_descriptions`
--
ALTER TABLE `product_descriptions`
  ADD CONSTRAINT `product_descriptions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
