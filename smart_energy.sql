-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 08, 2025 at 03:53 PM
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
-- Database: `smart_energy`
--

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` varchar(64) NOT NULL,
  `api_key` varchar(128) NOT NULL,
  `name` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `devices`
--

INSERT INTO `devices` (`id`, `device_id`, `api_key`, `name`) VALUES
(1, 'esp32meter', 'LUb0FTb+UjvgGSuyXZDBU+lBy2Y9Ixdc8a+KZqZ9taA=', 'SmartEnergyMeter');

-- --------------------------------------------------------

--
-- Table structure for table `device_tariff`
--

CREATE TABLE `device_tariff` (
  `device_id` varchar(64) NOT NULL,
  `tariff_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `device_tariff`
--

INSERT INTO `device_tariff` (`device_id`, `tariff_id`) VALUES
('esp32meter', 1);

-- --------------------------------------------------------

--
-- Table structure for table `readings`
--

CREATE TABLE `readings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` varchar(64) NOT NULL,
  `ts` datetime NOT NULL DEFAULT current_timestamp(),
  `voltage` decimal(10,2) NOT NULL,
  `current_ma` decimal(10,2) NOT NULL,
  `power_w` decimal(10,2) NOT NULL,
  `energy_kwh_current` decimal(16,6) NOT NULL,
  `energy_kwh_total` decimal(16,6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_dev_ts` (`device_id`,`ts`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `readings`
--

INSERT INTO `readings` (`id`, `device_id`, `ts`, `voltage`, `current_ma`, `power_w`, `energy_kwh_current`, `energy_kwh_total`) VALUES
(2008, 'esp32meter', '2025-11-08 19:12:25', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2009, 'esp32meter', '2025-11-08 19:12:28', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2010, 'esp32meter', '2025-11-08 19:12:31', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2011, 'esp32meter', '2025-11-08 19:12:34', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2012, 'esp32meter', '2025-11-08 19:12:36', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2013, 'esp32meter', '2025-11-08 19:12:39', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2014, 'esp32meter', '2025-11-08 19:12:42', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2015, 'esp32meter', '2025-11-08 19:12:45', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2016, 'esp32meter', '2025-11-08 19:12:48', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2017, 'esp32meter', '2025-11-08 19:12:51', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2018, 'esp32meter', '2025-11-08 19:12:53', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2019, 'esp32meter', '2025-11-08 19:12:56', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2020, 'esp32meter', '2025-11-08 19:13:01', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2021, 'esp32meter', '2025-11-08 19:13:04', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2022, 'esp32meter', '2025-11-08 19:13:07', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2023, 'esp32meter', '2025-11-08 19:13:13', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2024, 'esp32meter', '2025-11-08 19:13:16', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2025, 'esp32meter', '2025-11-08 19:13:19', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2026, 'esp32meter', '2025-11-08 19:13:22', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2027, 'esp32meter', '2025-11-08 19:13:25', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2028, 'esp32meter', '2025-11-08 19:13:28', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2029, 'esp32meter', '2025-11-08 19:13:30', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2030, 'esp32meter', '2025-11-08 19:13:35', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2031, 'esp32meter', '2025-11-08 19:13:38', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2032, 'esp32meter', '2025-11-08 19:13:44', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2033, 'esp32meter', '2025-11-08 19:13:49', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2034, 'esp32meter', '2025-11-08 19:13:53', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2035, 'esp32meter', '2025-11-08 19:13:55', 0.00, 0.00, 0.00, 0.000000, 2.420236),
(2036, 'esp32meter', '2025-11-08 19:13:58', 0.00, 0.00, 0.00, 0.000000, 2.420236);

-- --------------------------------------------------------

--
-- Table structure for table `tariffs`
--

CREATE TABLE `tariffs` (
  `id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'BDT',
  `rate_per_kwh` decimal(10,4) NOT NULL,
  `fixed_monthly` decimal(10,2) NOT NULL DEFAULT 0.00,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tariffs`
--

INSERT INTO `tariffs` (`id`, `name`, `currency`, `rate_per_kwh`, `fixed_monthly`, `effective_from`, `effective_to`) VALUES
(1, 'Default Flat', 'BDT', 8.0000, 0.00, '2025-01-01', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_id` (`device_id`);

--
-- Indexes for table `device_tariff`
--
ALTER TABLE `device_tariff`
  ADD PRIMARY KEY (`device_id`,`tariff_id`),
  ADD KEY `tariff_id` (`tariff_id`);

--
-- Indexes for table `readings`
--
--
-- Table structure for table `daily_usage`
--

CREATE TABLE `daily_usage` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` varchar(64) NOT NULL,
  `day` date NOT NULL,
  `tariff_id` int(11) DEFAULT NULL,
  `rate_per_kwh` decimal(10,4) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'BDT',
  `energy_kwh` decimal(16,6) NOT NULL DEFAULT 0.000000,
  `energy_cost` decimal(16,6) NOT NULL DEFAULT 0.000000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_daily_device_day` (`device_id`,`day`),
  KEY `idx_daily_device_day` (`device_id`,`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `monthly_usage`
--

CREATE TABLE `monthly_usage` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` varchar(64) NOT NULL,
  `month` date NOT NULL,
  `tariff_id` int(11) DEFAULT NULL,
  `rate_per_kwh` decimal(10,4) NOT NULL,
  `fixed_monthly` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` char(3) NOT NULL DEFAULT 'BDT',
  `energy_kwh` decimal(16,6) NOT NULL DEFAULT 0.000000,
  `energy_cost_variable` decimal(16,6) NOT NULL DEFAULT 0.000000,
  `fixed_charge_applied` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_monthly_device_month` (`device_id`,`month`),
  KEY `idx_monthly_device_month` (`device_id`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for table `tariffs`
--
ALTER TABLE `tariffs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `readings`
--
ALTER TABLE `readings`
  MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2037;

--
-- AUTO_INCREMENT for table `daily_usage`
--
ALTER TABLE `daily_usage`
  MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monthly_usage`
--
ALTER TABLE `monthly_usage`
  MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tariffs`
--
ALTER TABLE `tariffs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `device_tariff`
--
ALTER TABLE `device_tariff`
  ADD CONSTRAINT `device_tariff_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`),
  ADD CONSTRAINT `device_tariff_ibfk_2` FOREIGN KEY (`tariff_id`) REFERENCES `tariffs` (`id`);

--
-- Constraints for table `readings`
--
ALTER TABLE `readings`
  ADD CONSTRAINT `readings_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`);

--
-- Constraints for table `daily_usage`
--
ALTER TABLE `daily_usage`
  ADD CONSTRAINT `daily_usage_device_fk` FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `daily_usage_tariff_fk` FOREIGN KEY (`tariff_id`) REFERENCES `tariffs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `monthly_usage`
--
ALTER TABLE `monthly_usage`
  ADD CONSTRAINT `monthly_usage_device_fk` FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `monthly_usage_tariff_fk` FOREIGN KEY (`tariff_id`) REFERENCES `tariffs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
