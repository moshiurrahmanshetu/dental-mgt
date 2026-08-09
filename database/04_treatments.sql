-- Dental Management System Database Schema - Phase 4: Treatment Management
-- This file must be run AFTER 01_roles_users.sql, 02_patients.sql, AND 03_appointments.sql
-- Database: dental_management_db

USE dental_management_db;

-- Table: treatment_records
-- Stores treatment records and medical history
CREATE TABLE IF NOT EXISTS treatment_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_code VARCHAR(20) NOT NULL UNIQUE,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT NULL,
    visit_date DATE NOT NULL,
    chief_complaint TEXT NULL,
    diagnosis TEXT NULL,
    dental_findings TEXT NULL,
    follow_up_date DATE NULL,
    doctor_notes TEXT NULL,
    status ENUM('active', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    INDEX idx_patient_id (patient_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_appointment_id (appointment_id),
    INDEX idx_visit_date (visit_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: treatment_items
-- Stores individual treatments performed during a visit
CREATE TABLE IF NOT EXISTS treatment_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    treatment_record_id INT NOT NULL,
    treatment_name VARCHAR(100) NOT NULL,
    tooth_number VARCHAR(20) NULL,
    treatment_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (treatment_record_id) REFERENCES treatment_records(id) ON DELETE CASCADE,
    INDEX idx_treatment_record_id (treatment_record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: prescriptions
-- Stores prescriptions issued during a visit
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    treatment_record_id INT NOT NULL,
    medicine_name VARCHAR(150) NOT NULL,
    dosage VARCHAR(100) NULL,
    frequency VARCHAR(100) NULL,
    duration VARCHAR(100) NULL,
    instructions VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (treatment_record_id) REFERENCES treatment_records(id) ON DELETE CASCADE,
    INDEX idx_treatment_record_id (treatment_record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
