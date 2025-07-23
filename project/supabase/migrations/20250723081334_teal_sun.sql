-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 23, 2025 at 12:15 PM
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
-- Database: `pms`
--

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `document_path` varchar(500) NOT NULL,
  `document_type` enum('program_document','signed_document') DEFAULT 'program_document',
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `program_id`, `document_name`, `document_path`, `document_type`, `uploaded_by`, `created_at`) VALUES
(1, 1, 'W letter', 'uploads/documents/6880a707f3d0b.pdf', 'program_document', 3, '2025-07-23 09:10:31');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `budget` decimal(12,2) NOT NULL,
  `recipient_name` varchar(255) NOT NULL,
  `exco_letter_ref_number` varchar(100) NOT NULL,
  `status` enum('Draft','Under Review by Finance','Query','Query Answered','Approved','Rejected') DEFAULT 'Draft',
  `voucher_number` varchar(100) DEFAULT NULL,
  `eft_number` varchar(100) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `submitted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `program_name`, `budget`, `recipient_name`, `exco_letter_ref_number`, `status`, `voucher_number`, `eft_number`, `created_by`, `created_at`, `updated_at`, `submitted_at`) VALUES
(1, 'Program 1', 1000.00, 'Irrfan', '123456789', 'Approved', 'hello', 'hello', 3, '2025-07-23 09:10:31', '2025-07-23 09:12:02', '2025-07-23 09:11:36'),
(2, 'Program 2', 200.00, 'abc', '1234', 'Query', NULL, NULL, 3, '2025-07-23 10:00:37', '2025-07-23 10:02:14', '2025-07-23 10:01:28');

-- --------------------------------------------------------

--
-- Table structure for table `queries`
--

CREATE TABLE `queries` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `query_text` text NOT NULL,
  `response_text` text DEFAULT NULL,
  `status` enum('Open','Answered') DEFAULT 'Open',
  `created_by` int(11) NOT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `queries`
--

INSERT INTO `queries` (`id`, `program_id`, `query_text`, `response_text`, `status`, `created_by`, `responded_by`, `created_at`, `responded_at`) VALUES
(1, 2, 'why?', NULL, 'Open', 2, NULL, '2025-07-23 10:02:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `remarks`
--

CREATE TABLE `remarks` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `remark_text` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `remarks`
--

INSERT INTO `remarks` (`id`, `program_id`, `remark_text`, `created_by`, `created_at`) VALUES
(1, 1, 'hi', 2, '2025-07-23 09:59:49');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `role` enum('admin','exco_user','exco_pa','finance') NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone_number`, `role`, `profile_photo`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'System Administrator', 'admin@kedah.gov.my', '$2y$10$o6OhgEc5O9Q4nH9qJJGYeueYoD.n1Do3.Mo6/CyMnALGvTxgZBMbe', '04-1234567', 'admin', NULL, 1, '2025-07-23 08:25:50', '2025-07-23 09:07:57'),
(2, 'Ahmad Finance', 'finance@kedah.gov.my', '$2y$10$o6OhgEc5O9Q4nH9qJJGYeueYoD.n1Do3.Mo6/CyMnALGvTxgZBMbe', '04-2345678', 'finance', 'uploads/profile_photos/profile_2_1753262118.jpg', 1, '2025-07-23 08:25:51', '2025-07-23 09:15:18'),
(3, 'Siti Exco User', 'exco_user@kedah.gov.my', '$2y$10$o6OhgEc5O9Q4nH9qJJGYeueYoD.n1Do3.Mo6/CyMnALGvTxgZBMbe', '04-3456789', 'exco_user', NULL, 1, '2025-07-23 08:25:51', '2025-07-23 09:09:26'),
(4, 'Rahman Exco PA', 'exco_pa@kedah.gov.my', '$2y$10$o6OhgEc5O9Q4nH9qJJGYeueYoD.n1Do3.Mo6/CyMnALGvTxgZBMbe', '04-4567890', 'exco_pa', NULL, 1, '2025-07-23 08:25:51', '2025-07-23 09:09:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `queries`
--
ALTER TABLE `queries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `responded_by` (`responded_by`);

--
-- Indexes for table `remarks`
--
ALTER TABLE `remarks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `queries`
--
ALTER TABLE `queries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `remarks`
--
ALTER TABLE `remarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `queries`
--
ALTER TABLE `queries`
  ADD CONSTRAINT `queries_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `queries_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `queries_ibfk_3` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `remarks`
--
ALTER TABLE `remarks`
  ADD CONSTRAINT `remarks_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `remarks_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
