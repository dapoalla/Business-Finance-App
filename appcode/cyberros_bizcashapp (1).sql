-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 23, 2025 at 07:16 AM
-- Server version: 8.0.42-cll-lve
-- PHP Version: 8.3.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cyberros_bizcashapp`
--

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `name`, `email`, `phone`, `created_at`) VALUES
(4, 'Olanrewaju Akinnuoye', '', '', '2025-07-16 18:28:43'),
(6, 'StarLink Woman', '', '', '2025-07-18 11:08:11'),
(7, 'AFSS', '', '', '2025-07-24 05:27:48'),
(8, 'AFM', '', '', '2025-07-24 17:04:34'),
(9, 'Okereke', '', '', '2025-07-26 15:46:36'),
(10, 'Rotimi-LAGPPAD', '', '', '2025-08-20 07:09:23'),
(11, 'Spiro Mobility', '', '', '2025-08-30 18:20:27');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int NOT NULL,
  `invoice_display_id` varchar(255) DEFAULT NULL,
  `client_id` int NOT NULL,
  `invoice_date` date NOT NULL,
  `description` text,
  `status` enum('Open','Completed') NOT NULL DEFAULT 'Open',
  `payment_status` enum('Unpaid','Paid') NOT NULL DEFAULT 'Unpaid',
  `tithe_status` enum('Untithed','Tithed') NOT NULL DEFAULT 'Untithed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_display_id`, `client_id`, `invoice_date`, `description`, `status`, `payment_status`, `tithe_status`, `created_at`) VALUES
(5, '070725-INV-OLA-001_ResidenceCCTV', 4, '2025-07-07', 'Residence CCTV', 'Completed', 'Paid', 'Untithed', '2025-07-16 21:14:43'),
(7, '010525-INV-AFS-001_MikrotikRouterU', 7, '2025-05-01', 'Mikrotik Router Upgrade', 'Completed', 'Paid', 'Untithed', '2025-07-24 05:28:28'),
(11, '140825-INV-ROT-001_SmartDoorReset', 10, '2025-08-14', 'SmartDoor Reset', 'Completed', 'Paid', 'Untithed', '2025-08-20 07:09:57'),
(12, '220825-INV-OLA-002_SmartSocketSupp', 4, '2025-08-22', 'SmartSocket Supply', 'Completed', 'Paid', 'Untithed', '2025-08-22 14:34:04'),
(13, '220825-INV-OLA-003_Wifiinstall', 4, '2025-08-22', 'Wifi install', 'Completed', 'Paid', 'Untithed', '2025-08-22 14:38:04'),
(14, '300825-INV-SPI-001_CCTVAccessContr', 11, '2025-08-30', 'CCTV, AccessControl, LAN, etc', 'Open', 'Unpaid', 'Untithed', '2025-08-30 18:20:57'),
(15, '090925-INV-AFS-002_Camera', 7, '2025-09-09', 'Camera', 'Open', 'Unpaid', 'Untithed', '2025-09-09 12:47:06');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int NOT NULL,
  `invoice_id` int NOT NULL,
  `type` enum('Inflow','Outflow') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `invoice_id`, `type`, `amount`, `description`, `transaction_date`, `created_at`) VALUES
(6, 5, 'Inflow', 800000.00, 'Initial payment 1', '2025-07-16', '2025-07-16 21:16:19'),
(7, 5, 'Inflow', 300000.00, 'Payment 2', '2025-07-14', '2025-07-16 21:17:02'),
(8, 5, 'Outflow', 282564.00, 'NVR Hikvision 16CH NVR DS-7616NI-K2/16P', '2025-07-16', '2025-07-16 21:18:58'),
(9, 5, 'Outflow', 145000.00, '4 TERABYTE HARD DISK ', '2025-07-16', '2025-07-16 21:21:18'),
(10, 5, 'Outflow', 492000.00, 'Cat 6 outdoor cable', '2025-07-16', '2025-07-16 21:22:31'),
(11, 5, 'Outflow', 90000.00, 'Kolade’s wages part 1', '2025-07-16', '2025-07-16 21:24:03'),
(12, 5, 'Outflow', 52000.00, 'Civil works and materials', '2025-07-16', '2025-07-16 21:24:29'),
(14, 5, 'Outflow', 15000.00, 'Fuel to Magboro x2', '2025-07-16', '2025-07-16 21:28:58'),
(15, 5, 'Outflow', 7000.00, 'Ladder rental', '2025-07-17', '2025-07-17 19:49:11'),
(16, 5, 'Outflow', 18000.00, 'Ladder, other expenses', '2025-07-21', '2025-07-21 11:06:57'),
(17, 7, 'Inflow', 45000.00, 'Payment for Labour Charge', '2025-05-01', '2025-07-24 05:29:25'),
(18, 7, 'Outflow', 2000.00, 'MTN Internet to upgrage router', '2025-07-24', '2025-07-24 05:29:56'),
(19, 5, 'Outflow', 40000.00, 'Pole Fabrication - x4units', '2025-07-24', '2025-07-24 18:31:30'),
(20, 5, 'Outflow', 162300.00, 'Additional items (Switch, Poles, etc)', '2025-07-24', '2025-07-24 18:33:26'),
(21, 5, 'Inflow', 98000.00, 'Additionally works', '2025-08-05', '2025-08-05 07:07:54'),
(22, 5, 'Inflow', 300000.00, 'Lanre\'s NVR Sales', '2025-08-05', '2025-08-05 07:13:00'),
(23, 11, 'Inflow', 10000.00, 'To reset the Smart door at LAGPPAD', '2025-08-20', '2025-08-20 07:10:43'),
(24, 12, 'Inflow', 110000.00, 'Supply of smart socket ', '2025-08-22', '2025-08-22 14:34:51'),
(25, 12, 'Outflow', 102000.00, 'Actual cost', '2025-08-22', '2025-08-22 14:35:23'),
(26, 13, 'Inflow', 324500.00, 'Mobilization', '2025-08-22', '2025-08-22 14:38:46'),
(27, 13, 'Outflow', 25000.00, 'Wages for Kolade', '2025-08-22', '2025-08-22 22:02:33'),
(28, 13, 'Outflow', 250000.00, 'Tplink wall wifi purchases actual', '2025-08-22', '2025-08-22 22:03:12'),
(29, 14, 'Inflow', 7052705.00, '65% Down Payment', '2025-08-30', '2025-08-30 18:21:48'),
(31, 14, 'Outflow', 121800.00, 'Pipes, Flexible Pipes, Fiber Patch Panels, Cable Mnager', '2025-08-30', '2025-08-30 18:35:24'),
(33, 14, 'Outflow', 60000.00, 'Freight / Dispatch of Materials to Shagamu.', '2025-09-02', '2025-09-04 22:47:54'),
(34, 14, 'Outflow', 20000.00, 'Part 1 Freight / Dispatch of Materials From Lagos to Anthony.', '2025-09-04', '2025-09-04 22:48:59'),
(35, 14, 'Outflow', 1636000.00, 'ANEKWE - Purchase 01 - Lan Items', '2025-09-02', '2025-09-04 22:50:24'),
(36, 14, 'Outflow', 465900.00, 'Emmy Success - Purchase 02 - Cabling Items ', '2025-09-04', '2025-09-04 22:51:07'),
(37, 14, 'Outflow', 3992000.00, 'SGS - Purchase 03 - ACS Items', '2025-09-02', '2025-09-04 22:52:10'),
(38, 14, 'Outflow', 250000.00, 'CSR - Sammy', '2025-09-01', '2025-09-04 22:52:52'),
(39, 14, 'Outflow', 136416.00, 'TDAfrica - LAN Items', '2025-09-01', '2025-09-04 22:54:45'),
(40, 14, 'Outflow', 200000.00, 'Kolade - Part Payment 1', '2025-09-01', '2025-09-05 04:50:16'),
(41, 14, 'Outflow', 5000.00, 'industrial Ladder rental', '2025-09-08', '2025-09-08 18:44:14'),
(42, 14, 'Outflow', 5000.00, 'Cable tie at Shagamu', '2025-09-08', '2025-09-08 18:44:37'),
(43, 14, 'Outflow', 3000.00, 'Kolade Shagamu transport', '2025-09-08', '2025-09-08 18:45:37'),
(44, 15, 'Inflow', 100000.00, 'Total', '2025-09-09', '2025-09-09 12:47:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
