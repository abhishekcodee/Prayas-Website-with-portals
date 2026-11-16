-- Migration 002: Companies and Users Tables
-- Database: manpower_portal_db
-- Organization: PRAYAS – The Automation Academy

CREATE TABLE IF NOT EXISTS `companies` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_name` VARCHAR(255) NOT NULL,
    `registration_number` VARCHAR(100) NULL UNIQUE,
    `industry_type` VARCHAR(100) NOT NULL,
    `address` TEXT NOT NULL,
    `state` VARCHAR(100) NOT NULL,
    `district` VARCHAR(100) NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `pincode` VARCHAR(20) NOT NULL,
    `hr_contact_name` VARCHAR(255) NOT NULL,
    `hr_contact_phone` VARCHAR(50) NOT NULL,
    `hr_contact_email` VARCHAR(255) NOT NULL UNIQUE,
    `verification_status` ENUM('Pending', 'Verified', 'Rejected', 'Suspended') DEFAULT 'Pending',
    `verified_by` INT UNSIGNED NULL,
    `verified_at` DATETIME NULL,
    `rejection_reason` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_companies_status` (`verification_status`),
    INDEX `idx_companies_district` (`district`),
    INDEX `idx_companies_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL,
    `company_id` INT UNSIGNED NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `phone` VARCHAR(50) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `must_change_password` TINYINT(1) DEFAULT 0,
    `status` ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    `last_login_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_users_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role_id`),
    INDEX `idx_users_company` (`company_id`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `companies` 
    ADD CONSTRAINT `fk_companies_verified_by` 
    FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

