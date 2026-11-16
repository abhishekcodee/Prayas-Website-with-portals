-- Migration 003: Manpower Requirements and Related Sub-tables
-- Database: manpower_portal_db
-- Organization: PRAYAS – The Automation Academy

CREATE TABLE IF NOT EXISTS `manpower_requirements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `requirement_id` VARCHAR(50) NOT NULL UNIQUE,
    `company_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `quantity_required` INT UNSIGNED NOT NULL,
    `qualification` VARCHAR(100) NOT NULL,
    `board_type` ENUM('NCVT', 'SCVT', 'Either') DEFAULT 'Either',
    `passing_year_from` VARCHAR(50) NULL,
    `passing_year_to` VARCHAR(50) NULL,
    `experience_required` VARCHAR(100) NOT NULL,
    `job_location_state` VARCHAR(100) NOT NULL,
    `job_location_district` VARCHAR(100) NOT NULL,
    `job_location_city` VARCHAR(100) NOT NULL,
    `job_description` TEXT NOT NULL,
    `min_age` INT UNSIGNED DEFAULT 18,
    `max_age` INT UNSIGNED NULL,
    `gender_preference` ENUM('Any', 'Male', 'Female') DEFAULT 'Any',
    `min_salary` DECIMAL(10,2) NOT NULL,
    `max_salary` DECIMAL(10,2) NOT NULL,
    `salary_type` ENUM('Monthly', 'Stipend', 'Annual') DEFAULT 'Monthly',
    `employment_type` ENUM('Full Time', 'Apprenticeship', 'Fixed Term', 'OJT') DEFAULT 'Full Time',
    `working_hours` VARCHAR(50) DEFAULT '8 Hours',
    `shift_details` VARCHAR(100) DEFAULT 'General Shift',
    `accommodation_provided` ENUM('Yes', 'No', 'Subsidized') DEFAULT 'No',
    `food_facility` ENUM('Yes', 'No', 'Subsidized') DEFAULT 'No',
    `transportation_provided` ENUM('Yes', 'No', 'Subsidized') DEFAULT 'No',
    `pf_esic_benefit` ENUM('Yes', 'No') DEFAULT 'Yes',
    `submission_date` DATE NOT NULL,
    `application_deadline` DATE NOT NULL,
    `interview_date` DATE NOT NULL,
    `target_joining_date` DATE NOT NULL,
    `priority` ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
    `status` ENUM(
        'DRAFT', 
        'SUBMITTED', 
        'UNDER_VALIDATION', 
        'NEEDS_CORRECTION', 
        'PENDING_REVIEW', 
        'APPROVED', 
        'RECRUITMENT_IN_PROGRESS', 
        'PARTIALLY_FULFILLED', 
        'FULFILLED', 
        'CLOSED', 
        'CANCELLED', 
        'REJECTED'
    ) DEFAULT 'DRAFT',
    `approved_by` INT UNSIGNED NULL,
    `approved_at` DATETIME NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_req_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_req_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_req_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_req_company` (`company_id`),
    INDEX `idx_req_status` (`status`),
    INDEX `idx_req_district` (`job_location_district`),
    INDEX `idx_req_priority` (`priority`),
    INDEX `idx_req_dates` (`target_joining_date`, `application_deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `requirement_trades` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `requirement_id` INT UNSIGNED NOT NULL,
    `trade_name` VARCHAR(150) NOT NULL,
    `vacancies` INT UNSIGNED DEFAULT 1,
    CONSTRAINT `fk_rt_requirement` FOREIGN KEY (`requirement_id`) REFERENCES `manpower_requirements` (`id`) ON DELETE CASCADE,
    INDEX `idx_rt_trade` (`trade_name`),
    INDEX `idx_rt_req` (`requirement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `requirement_skills` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `requirement_id` INT UNSIGNED NOT NULL,
    `skill_name` VARCHAR(150) NOT NULL,
    CONSTRAINT `fk_rs_requirement` FOREIGN KEY (`requirement_id`) REFERENCES `manpower_requirements` (`id`) ON DELETE CASCADE,
    INDEX `idx_rs_skill` (`skill_name`),
    INDEX `idx_rs_req` (`requirement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `requirement_status_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `requirement_id` INT UNSIGNED NOT NULL,
    `previous_status` VARCHAR(50) NULL,
    `new_status` VARCHAR(50) NOT NULL,
    `changed_by` INT UNSIGNED NULL,
    `remarks` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_rsh_requirement` FOREIGN KEY (`requirement_id`) REFERENCES `manpower_requirements` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rsh_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_rsh_req` (`requirement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

