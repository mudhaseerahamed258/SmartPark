-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 30, 2026 at 08:18 AM
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
-- Database: `smartparkconnect`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `org_name` varchar(150) NOT NULL,
  `admin_id` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `full_name`, `email`, `phone_number`, `org_name`, `admin_id`, `password`, `created_at`) VALUES
(3, 'Mudhaseer', 'mudhaseer@gmail.com', '9059587071', 'KRP COLONY', 'mudhaseeradm', '$2y$10$3zFN9nDCgvsKWlUn6xJegOFHhPUVs4v29lE0UkOFuTCHSEVqD3Jd6', '2026-03-08 07:43:13'),
(4, 'Muzamil', 'muzamil@gmail.com', '9493009646', 'DOS', 'muzamiladm', '$2y$10$NUWSI81fF8t5c8cQ66.MV.rMITAl9rVA1qlrvAE/NDNnIDRzD6TrW', '2026-03-15 04:57:55'),
(5, 'Mudhaseer', 'skmudhaseerahamed2580@gmail.com', '9440067071', 'DRDL', 'mudhaseeradm1', '$2y$10$j63Foc42UJVuENfckxxTpOgp.9Vx9g8jKxdvtDodnrYuNeekryfWC', '2026-03-15 14:20:57'),
(6, 'Mudhaseer Saveetha', 'shaikmudhaseerahamed1968.sse@saveetha.com', '9493006571', 'SIRUSERI', 'mudhaseeradm2', '$2y$10$KzgEVfE49nXJRmfMWJBx5u8mrcqqxc6MO1BGL7zIpe6fUmI/pSLbq', '2026-03-15 14:27:20');

-- --------------------------------------------------------

--
-- Table structure for table `community_posts`
--

CREATE TABLE `community_posts` (
  `id` int(11) NOT NULL,
  `org_code` varchar(100) NOT NULL,
  `author_type` enum('ADMIN','USER') NOT NULL,
  `author_user_id` int(11) DEFAULT NULL,
  `author_admin_id` varchar(50) DEFAULT NULL,
  `author_name` varchar(150) NOT NULL,
  `author_unit` varchar(150) DEFAULT '',
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_posts`
--

INSERT INTO `community_posts` (`id`, `org_code`, `author_type`, `author_user_id`, `author_admin_id`, `author_name`, `author_unit`, `title`, `description`, `created_at`, `image_path`) VALUES
(8, 'KRP123', 'ADMIN', NULL, 'mudhaseeradm', 'Mudhaseer', 'KRP COLONY', 'Security', 'Secure', '2026-03-11 08:32:30', 'uploads/community_posts/post_1773217950_69b1289e98e80.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `id` int(11) NOT NULL,
  `admin_id` varchar(30) NOT NULL,
  `org_name` varchar(150) NOT NULL,
  `org_code` varchar(30) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`id`, `admin_id`, `org_name`, `org_code`, `address`, `city`, `state`, `pincode`, `contact_phone`, `contact_email`, `description`, `created_at`, `updated_at`) VALUES
(2, 'mudhaseeradm', 'KRP COLONY', 'KRP123', 'Pulicat Nagar Sullurupeta', 'Sullurupeta', 'Andhra Pradesh', '524121', '9059587071', 'mudhaseer@gmail.com', 'Gated Community', '2026-03-08 07:44:52', '2026-03-08 07:44:52'),
(3, 'muzamiladm', 'DOS', 'DOS123', 'DOS Colony Sullurupeta', 'Sullurupeta', 'Andhra Pradesh', '524121', '9493009646', 'muzamil@gmail.com', 'Gated Community', '2026-03-15 04:59:17', '2026-03-15 04:59:17');

-- --------------------------------------------------------

--
-- Table structure for table `organization_emergency_contacts`
--

CREATE TABLE `organization_emergency_contacts` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `security_office` varchar(20) DEFAULT NULL,
  `management_office` varchar(20) DEFAULT NULL,
  `gate_security` varchar(20) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organization_emergency_contacts`
--

INSERT INTO `organization_emergency_contacts` (`id`, `organization_id`, `security_office`, `management_office`, `gate_security`, `updated_at`) VALUES
(1, 2, '9059587071', '9440067071', '9493006571', '2026-03-09 08:57:23');

-- --------------------------------------------------------

--
-- Table structure for table `organization_parking`
--

CREATE TABLE `organization_parking` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `total_slots` int(11) DEFAULT 0,
  `two_wheeler_slots` int(11) DEFAULT 0,
  `four_wheeler_slots` int(11) DEFAULT 0,
  `visitor_slots` int(11) DEFAULT 0,
  `disabled_slots` int(11) DEFAULT 0,
  `ev_slots` int(11) DEFAULT 0,
  `parking_hours` varchar(100) DEFAULT NULL,
  `parking_rules` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organization_parking`
--

INSERT INTO `organization_parking` (`id`, `organization_id`, `total_slots`, `two_wheeler_slots`, `four_wheeler_slots`, `visitor_slots`, `disabled_slots`, `ev_slots`, `parking_hours`, `parking_rules`, `created_at`, `updated_at`) VALUES
(2, 2, 500, 200, 200, 100, 0, 0, '24/7', '', '2026-03-08 07:45:42', '2026-03-08 07:45:42'),
(3, 3, 200, 50, 50, 50, 0, 50, '24/7', 'No Parking in Different Slots', '2026-03-15 05:00:10', '2026-03-15 05:00:10');

-- --------------------------------------------------------

--
-- Table structure for table `organization_rules`
--

CREATE TABLE `organization_rules` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `max_vehicles_per_resident` int(11) NOT NULL DEFAULT 3,
  `visitor_parking_duration_hours` int(11) NOT NULL DEFAULT 4,
  `allow_overnight_parking` tinyint(1) NOT NULL DEFAULT 1,
  `require_advance_booking` tinyint(1) NOT NULL DEFAULT 0,
  `advance_booking_hours` int(11) NOT NULL DEFAULT 0,
  `max_visitors_per_day` int(11) NOT NULL DEFAULT 5,
  `visitor_pass_validity_hours` int(11) NOT NULL DEFAULT 24,
  `require_host_approval` tinyint(1) NOT NULL DEFAULT 1,
  `require_security_verification` tinyint(1) NOT NULL DEFAULT 1,
  `allow_guest_posting` tinyint(1) NOT NULL DEFAULT 0,
  `post_moderation_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `allow_anonymous_posts` tinyint(1) NOT NULL DEFAULT 0,
  `emergency_contacts_visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organization_rules`
--

INSERT INTO `organization_rules` (`id`, `organization_id`, `max_vehicles_per_resident`, `visitor_parking_duration_hours`, `allow_overnight_parking`, `require_advance_booking`, `advance_booking_hours`, `max_visitors_per_day`, `visitor_pass_validity_hours`, `require_host_approval`, `require_security_verification`, `allow_guest_posting`, `post_moderation_enabled`, `allow_anonymous_posts`, `emergency_contacts_visible`, `created_at`, `updated_at`) VALUES
(2, 2, 4, 5, 1, 1, 5, 6, 25, 1, 1, 1, 1, 0, 1, '2026-03-09 07:17:11', '2026-03-09 08:09:10');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `user_type` enum('admin','user') NOT NULL,
  `otp` varchar(6) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `user_type`, `otp`, `is_verified`, `expires_at`, `created_at`) VALUES
(2, 'shaikmudhaseerahamed1968.sse@saveetha.com', 'admin', '847231', 0, '2026-03-15 16:47:36', '2026-03-15 15:42:36');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `flat` varchar(50) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `org_code` varchar(50) DEFAULT NULL,
  `status` enum('not_joined','pending','approved','rejected') NOT NULL DEFAULT 'not_joined',
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approval_seen` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `flat`, `email`, `phone_number`, `org_code`, `status`, `password`, `created_at`, `approval_seen`) VALUES
(6, 'shaheen', NULL, 'shaheen@gmail.com', '9059587071', 'KRP123', 'approved', '$2y$10$usvFt5wvrOPlr3jXuWbdaun60E/zxDTNe/IIVo8oSKS.z5DwXQlxK', '2026-03-08 08:12:22', 1),
(8, 'Nazeer', NULL, 'nazeer@gmail.com', '9440067071', NULL, 'not_joined', '$2y$10$bajuGs2ga.ZSj45SG8fqtOGBjEkREpxZAhrzHFhE2pWDKgP0Aqj1C', '2026-03-08 09:53:38', 0),
(9, 'Mudhaseer', 'B4 105', 'mudhaseer@gmail.com', '', 'KRP123', 'approved', '$2y$10$hldU551CExZwYSMRFqED4eB4hXqAz4Q.wn.pdCryDM7lRVei8EYze', '2026-03-09 06:43:15', 1),
(10, 'Tcs', NULL, 'mudhaseerahamedshaik@gmail.com', '9493002750', NULL, 'not_joined', '$2y$10$DP./b9xNYqrSDM1M5nuL6eD/hJKXetxvPEaC9X0nK3YTgreLr0fBi', '2026-03-15 15:29:12', 0),
(11, 'Mudhaseer', NULL, 'mudhaseerahamed@gmail.com', '9249137846', 'KRP123', 'approved', '$2y$10$fc7azMdlDyf1H9CPrVvUDeAtmit9ZzVcolyKEFWMcAM.3QcIjGCzm', '2026-03-29 14:48:17', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_organizations`
--

CREATE TABLE `user_organizations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `org_code` varchar(50) NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_organizations`
--

INSERT INTO `user_organizations` (`id`, `user_id`, `org_code`, `status`, `created_at`) VALUES
(1, 9, 'KRP123', 'APPROVED', '2026-03-15 05:01:14'),
(2, 9, 'DOS123', 'APPROVED', '2026-03-15 05:01:28'),
(3, 11, 'KRP123', 'APPROVED', '2026-03-29 14:48:39');

-- --------------------------------------------------------

--
-- Table structure for table `user_vehicles`
--

CREATE TABLE `user_vehicles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `org_code` varchar(100) NOT NULL,
  `vehicle_number` varchar(50) NOT NULL,
  `vehicle_type` varchar(50) NOT NULL,
  `parking_slot` varchar(50) DEFAULT 'N/A',
  `pillar_number` varchar(50) DEFAULT 'N/A',
  `landmark` varchar(150) DEFAULT 'N/A',
  `floor` varchar(50) DEFAULT 'N/A',
  `zone_name` varchar(50) DEFAULT 'N/A',
  `is_visitor` tinyint(1) DEFAULT 0,
  `status` enum('ACTIVE','DELETED') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_vehicles`
--

INSERT INTO `user_vehicles` (`id`, `user_id`, `org_code`, `vehicle_number`, `vehicle_type`, `parking_slot`, `pillar_number`, `landmark`, `floor`, `zone_name`, `is_visitor`, `status`, `created_at`, `updated_at`) VALUES
(1, 6, 'KRP123', 'AP-25-AX-0665', 'Car', 'A-56', 'PILLAR 06', 'Near security Gate', 'Ground Floor', 'North Wing', 0, 'ACTIVE', '2026-03-12 06:07:20', '2026-03-12 06:19:28'),
(3, 9, 'KRP123', 'AP-26-AX-2009', 'Bike', 'A 24', 'P 17', 'Near Security Gate', 'Ground Floor', 'East Wing', 0, 'DELETED', '2026-03-12 06:42:31', '2026-03-15 05:08:32'),
(4, 9, 'KRP123', 'AP-26-AX-7071', 'Car', 'A 39', 'P 152', 'Near Main Gate', 'Basment 2', 'C Block', 0, 'ACTIVE', '2026-03-15 05:09:22', '2026-03-15 05:09:22'),
(5, 9, 'DOS123', 'AP-25-AX-9587', 'Car', 'A 13', 'P 26', 'Near Laundry', 'Base 3', 'West Wing', 0, 'ACTIVE', '2026-03-15 05:59:33', '2026-03-15 05:59:33');

-- --------------------------------------------------------

--
-- Table structure for table `visitor_passes`
--

CREATE TABLE `visitor_passes` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `org_code` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_number` varchar(30) NOT NULL,
  `visitor_name` varchar(100) NOT NULL,
  `visitor_phone_number` varchar(20) DEFAULT NULL,
  `purpose` varchar(255) NOT NULL,
  `duration_hours` int(11) NOT NULL,
  `building` varchar(100) NOT NULL,
  `floor` varchar(50) NOT NULL,
  `slot_number` varchar(50) NOT NULL,
  `entry_time` varchar(20) NOT NULL,
  `exit_time` varchar(20) NOT NULL,
  `pass_code` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visitor_passes`
--

INSERT INTO `visitor_passes` (`id`, `organization_id`, `org_code`, `user_id`, `vehicle_number`, `visitor_name`, `visitor_phone_number`, `purpose`, `duration_hours`, `building`, `floor`, `slot_number`, `entry_time`, `exit_time`, `pass_code`, `status`, `created_at`) VALUES
(3, 2, 'KRP123', 9, 'AP 26 AX 2004', 'Mudhaseer', '9493006571', 'Guest', 1, 'B4 26', '2', '45', '3:42 pm', '4:42 pm', '#VIS-7063', 'ACTIVE', '2026-03-13 10:12:07');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone_number` (`phone_number`),
  ADD UNIQUE KEY `admin_id` (`admin_id`);

--
-- Indexes for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `org_code` (`org_code`);

--
-- Indexes for table `organization_emergency_contacts`
--
ALTER TABLE `organization_emergency_contacts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `organization_id` (`organization_id`);

--
-- Indexes for table `organization_parking`
--
ALTER TABLE `organization_parking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `organization_rules`
--
ALTER TABLE `organization_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `organization_id` (`organization_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email_type` (`email`,`user_type`),
  ADD UNIQUE KEY `email_user_type` (`email`,`user_type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone_number` (`phone_number`);

--
-- Indexes for table `user_organizations`
--
ALTER TABLE `user_organizations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_org` (`user_id`,`org_code`);

--
-- Indexes for table `user_vehicles`
--
ALTER TABLE `user_vehicles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `visitor_passes`
--
ALTER TABLE `visitor_passes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `community_posts`
--
ALTER TABLE `community_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `organization_emergency_contacts`
--
ALTER TABLE `organization_emergency_contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `organization_parking`
--
ALTER TABLE `organization_parking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `organization_rules`
--
ALTER TABLE `organization_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_organizations`
--
ALTER TABLE `user_organizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_vehicles`
--
ALTER TABLE `user_vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `visitor_passes`
--
ALTER TABLE `visitor_passes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `organization_emergency_contacts`
--
ALTER TABLE `organization_emergency_contacts`
  ADD CONSTRAINT `organization_emergency_contacts_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `organization_rules`
--
ALTER TABLE `organization_rules`
  ADD CONSTRAINT `fk_organization_rules_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
