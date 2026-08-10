-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 09, 2026 at 09:03 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dental_mgt_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'Login', 'User logged in successfully', '::1', '2026-08-09 10:43:24'),
(2, 1, 'Logout', 'User logged out', '::1', '2026-08-09 10:43:40'),
(3, 1, 'Login', 'User logged in successfully', '::1', '2026-08-09 10:43:54'),
(4, 1, 'Login', 'Test login via script', '127.0.0.1', '2026-08-09 10:44:45'),
(5, 1, 'Logout', 'Test logout', '127.0.0.1', '2026-08-09 10:46:26'),
(6, 1, 'Patient Added', 'New patient added: Test Patient (Code: PAT-000999)', NULL, '2026-08-09 11:05:23'),
(7, 1, 'Patient Updated', 'Patient updated: Test Patient (Code: PAT-000999)', NULL, '2026-08-09 11:05:23'),
(8, 1, 'Patient Deactivated', 'Patient deactivated: Test Patient (Code: PAT-000999)', NULL, '2026-08-09 11:05:23'),
(9, 1, 'Patient Added', 'Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description Test description ', NULL, '2026-08-09 11:05:23'),
(10, 1, 'Patient Added', 'New patient added: Rakib Mia (Code: PAT-000003)', '::1', '2026-08-09 11:47:09'),
(11, 1, 'Profile Updated', 'User updated their own profile', '::1', '2026-08-09 13:47:11'),
(12, 1, 'Profile Updated', 'User updated their own profile', '::1', '2026-08-09 13:47:26');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `appointment_code` varchar(20) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `appointment_type` varchar(50) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Pending','Confirmed','Completed','Cancelled','No Show') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(20) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `treatment_record_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_type` enum('flat','percentage') DEFAULT 'flat',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('Unpaid','Partially Paid','Paid') DEFAULT 'Unpaid',
  `invoice_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `item_description` varchar(200) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `line_total` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `patient_code` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `medical_notes` text DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `registered_by` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `patient_code`, `user_id`, `full_name`, `gender`, `date_of_birth`, `phone`, `email`, `address`, `blood_group`, `emergency_contact_name`, `emergency_contact_phone`, `medical_notes`, `profile_photo`, `registered_by`, `status`, `created_at`, `updated_at`) VALUES
(1, 'PAT-000001', NULL, 'John Doe', 'Male', '1990-01-15', '555-1234', 'john@example.com', '123 Main St', 'A+', 'Jane Doe', '555-5678', 'No known allergies', NULL, 1, 'active', '2026-08-09 11:00:59', '2026-08-09 11:03:50'),
(2, 'PAT-000002', NULL, 'Jane Smith', 'Female', '1985-05-20', '555-9876', 'jane@example.com', '456 Oak Ave', 'B+', 'John Smith', '555-4321', 'Penicillin allergy', NULL, 1, 'active', '2026-08-09 11:00:59', '2026-08-09 11:00:59'),
(3, 'PAT-000003', NULL, 'Rakib Mia', 'Male', '2026-08-03', '01234567890', 'admin@medicalcamp.com', NULL, 'B+', 'fdafdssdf', '3424314343242', 'dfzdfdf', NULL, 1, 'active', '2026-08-09 11:47:09', '2026-08-09 11:47:09');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `payment_code` varchar(20) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `received_by` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','Card','Bank Transfer','Mobile Banking','Other') DEFAULT 'Cash',
  `payment_date` date NOT NULL,
  `reference_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `permission_key` varchar(100) NOT NULL,
  `module_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `permission_key`, `module_name`, `description`, `created_at`) VALUES
(23, 'patients.view', 'Patients', 'View patient list and details', '2026-08-09 14:07:48'),
(24, 'patients.add', 'Patients', 'Add new patients', '2026-08-09 14:07:48'),
(25, 'patients.edit', 'Patients', 'Edit patient information', '2026-08-09 14:07:48'),
(26, 'patients.delete', 'Patients', 'Delete patients', '2026-08-09 14:07:48'),
(27, 'appointments.view', 'Appointments', 'View appointment list and details', '2026-08-09 14:07:48'),
(28, 'appointments.add', 'Appointments', 'Book new appointments', '2026-08-09 14:07:48'),
(29, 'appointments.edit', 'Appointments', 'Edit appointment information', '2026-08-09 14:07:48'),
(30, 'appointments.cancel', 'Appointments', 'Cancel appointments', '2026-08-09 14:07:48'),
(31, 'treatments.view', 'Treatments', 'View treatment records', '2026-08-09 14:07:48'),
(32, 'treatments.add', 'Treatments', 'Add treatment records', '2026-08-09 14:07:48'),
(33, 'treatments.edit', 'Treatments', 'Edit treatment records', '2026-08-09 14:07:48'),
(34, 'billing.view', 'Billing', 'View invoices and billing information', '2026-08-09 14:07:48'),
(35, 'billing.add_invoice', 'Billing', 'Create new invoices', '2026-08-09 14:07:48'),
(36, 'billing.edit_invoice', 'Billing', 'Edit invoice information', '2026-08-09 14:07:48'),
(37, 'billing.record_payment', 'Billing', 'Record payments', '2026-08-09 14:07:48'),
(38, 'reports.patient', 'Reports', 'View patient reports', '2026-08-09 14:07:48'),
(39, 'reports.appointment', 'Reports', 'View appointment reports', '2026-08-09 14:07:48'),
(40, 'reports.treatment', 'Reports', 'View treatment reports', '2026-08-09 14:07:48'),
(41, 'reports.revenue', 'Reports', 'View revenue reports', '2026-08-09 14:07:48'),
(42, 'reports.payment', 'Reports', 'View payment reports', '2026-08-09 14:07:48'),
(43, 'reports.due_payment', 'Reports', 'View due payment reports', '2026-08-09 14:07:48'),
(44, 'users.manage', 'Users', 'Manage user accounts', '2026-08-09 14:07:48');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `treatment_record_id` int(11) NOT NULL,
  `medicine_name` varchar(150) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `instructions` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `created_at`) VALUES
(1, 'Admin', '2026-08-09 10:42:54'),
(2, 'Doctor', '2026-08-09 10:42:54'),
(3, 'Receptionist', '2026-08-09 10:42:54'),
(4, 'Patient', '2026-08-09 10:42:54');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `created_at`) VALUES
(70, 1, 28, '2026-08-09 14:07:48'),
(71, 1, 30, '2026-08-09 14:07:48'),
(72, 1, 29, '2026-08-09 14:07:48'),
(73, 1, 27, '2026-08-09 14:07:48'),
(74, 1, 35, '2026-08-09 14:07:48'),
(75, 1, 36, '2026-08-09 14:07:48'),
(76, 1, 37, '2026-08-09 14:07:48'),
(77, 1, 34, '2026-08-09 14:07:48'),
(78, 1, 24, '2026-08-09 14:07:48'),
(79, 1, 26, '2026-08-09 14:07:48'),
(80, 1, 25, '2026-08-09 14:07:48'),
(81, 1, 23, '2026-08-09 14:07:48'),
(82, 1, 39, '2026-08-09 14:07:48'),
(83, 1, 43, '2026-08-09 14:07:48'),
(84, 1, 38, '2026-08-09 14:07:48'),
(85, 1, 42, '2026-08-09 14:07:48'),
(86, 1, 41, '2026-08-09 14:07:48'),
(87, 1, 40, '2026-08-09 14:07:48'),
(88, 1, 32, '2026-08-09 14:07:48'),
(89, 1, 33, '2026-08-09 14:07:48'),
(90, 1, 31, '2026-08-09 14:07:48'),
(91, 1, 44, '2026-08-09 14:07:48'),
(101, 2, 27, '2026-08-09 14:07:48'),
(102, 2, 23, '2026-08-09 14:07:48'),
(103, 2, 38, '2026-08-09 14:07:48'),
(104, 2, 40, '2026-08-09 14:07:48'),
(105, 2, 32, '2026-08-09 14:07:48'),
(106, 2, 33, '2026-08-09 14:07:48'),
(107, 2, 31, '2026-08-09 14:07:48'),
(108, 3, 28, '2026-08-09 14:07:48'),
(109, 3, 30, '2026-08-09 14:07:48'),
(110, 3, 29, '2026-08-09 14:07:48'),
(111, 3, 27, '2026-08-09 14:07:48'),
(112, 3, 35, '2026-08-09 14:07:48'),
(113, 3, 36, '2026-08-09 14:07:48'),
(114, 3, 37, '2026-08-09 14:07:48'),
(115, 3, 34, '2026-08-09 14:07:48'),
(116, 3, 24, '2026-08-09 14:07:48'),
(117, 3, 26, '2026-08-09 14:07:48'),
(118, 3, 25, '2026-08-09 14:07:48'),
(119, 3, 23, '2026-08-09 14:07:48'),
(120, 3, 39, '2026-08-09 14:07:48'),
(121, 3, 43, '2026-08-09 14:07:48'),
(122, 3, 42, '2026-08-09 14:07:48'),
(123, 3, 31, '2026-08-09 14:07:48');

-- --------------------------------------------------------

--
-- Table structure for table `treatment_items`
--

CREATE TABLE `treatment_items` (
  `id` int(11) NOT NULL,
  `treatment_record_id` int(11) NOT NULL,
  `treatment_name` varchar(100) NOT NULL,
  `tooth_number` varchar(20) DEFAULT NULL,
  `treatment_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `treatment_records`
--

CREATE TABLE `treatment_records` (
  `id` int(11) NOT NULL,
  `record_code` varchar(20) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `chief_complaint` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `dental_findings` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `doctor_notes` text DEFAULT NULL,
  `status` enum('active','archived') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `appointment_code` (`appointment_code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_appointment_date` (`appointment_date`),
  ADD KEY `idx_doctor_id` (`doctor_id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date_doctor` (`appointment_date`,`doctor_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `treatment_record_id` (`treatment_record_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_invoice_date` (`invoice_date`),
  ADD KEY `idx_payment_status` (`payment_status`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice_id` (`invoice_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patient_code` (`patient_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `registered_by` (`registered_by`),
  ADD KEY `idx_patient_code` (`patient_code`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_full_name` (`full_name`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_code` (`payment_code`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `idx_invoice_id` (`invoice_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_permission_key` (`permission_key`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_treatment_record_id` (`treatment_record_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `treatment_items`
--
ALTER TABLE `treatment_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_treatment_record_id` (`treatment_record_id`);

--
-- Indexes for table `treatment_records`
--
ALTER TABLE `treatment_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `record_code` (`record_code`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_doctor_id` (`doctor_id`),
  ADD KEY `idx_appointment_id` (`appointment_id`),
  ADD KEY `idx_visit_date` (`visit_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `treatment_items`
--
ALTER TABLE `treatment_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `treatment_records`
--
ALTER TABLE `treatment_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`treatment_record_id`) REFERENCES `treatment_records` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `patients_ibfk_2` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`treatment_record_id`) REFERENCES `treatment_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `treatment_items`
--
ALTER TABLE `treatment_items`
  ADD CONSTRAINT `treatment_items_ibfk_1` FOREIGN KEY (`treatment_record_id`) REFERENCES `treatment_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `treatment_records`
--
ALTER TABLE `treatment_records`
  ADD CONSTRAINT `treatment_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `treatment_records_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `treatment_records_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
