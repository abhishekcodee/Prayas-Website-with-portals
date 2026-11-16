-- Migration 004: Candidate Matching and Recruitment Pipeline Tables
-- Database: manpower_portal_db
-- Organization: PRAYAS – The Automation Academy

CREATE TABLE IF NOT EXISTS `candidate_matches` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `requirement_id` INT UNSIGNED NOT NULL,
    `candidate_source` ENUM('prayas_dashboard', 'itistudents') NOT NULL,
    `candidate_source_id` INT UNSIGNED NOT NULL,
    `candidate_name` VARCHAR(255) NOT NULL,
    `trade` VARCHAR(150) NOT NULL,
    `passing_year` VARCHAR(50) NULL,
    `match_score` DECIMAL(5,2) NOT NULL,
    `match_details_json` TEXT NULL,
    `status` ENUM(
        'Matched', 
        'Shortlisted', 
        'Screened', 
        'Scheduled', 
        'Selected', 
        'Joined', 
        'Rejected', 
        'On Hold'
    ) DEFAULT 'Matched',
    `rejection_reason` VARCHAR(255) NULL,
    `matched_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_cm_requirement` FOREIGN KEY (`requirement_id`) REFERENCES `manpower_requirements` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_req_candidate` (`requirement_id`, `candidate_source`, `candidate_source_id`),
    INDEX `idx_cm_status` (`status`),
    INDEX `idx_cm_score` (`match_score`),
    INDEX `idx_cm_trade` (`trade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `candidate_screening` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `match_id` INT UNSIGNED NOT NULL,
    `screener_id` INT UNSIGNED NOT NULL,
    `screening_outcome` ENUM('Passed', 'On Hold', 'Rejected') NOT NULL,
    `screening_notes` TEXT NULL,
    `screened_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_cs_match` FOREIGN KEY (`match_id`) REFERENCES `candidate_matches` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cs_screener` FOREIGN KEY (`screener_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_cs_outcome` (`screening_outcome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `interviews` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `match_id` INT UNSIGNED NOT NULL,
    `interview_datetime` DATETIME NOT NULL,
    `venue_or_link` TEXT NOT NULL,
    `interview_round` VARCHAR(100) DEFAULT 'Round 1 - Technical',
    `interviewer_name` VARCHAR(255) NULL,
    `status` ENUM('Scheduled', 'Completed', 'Cancelled', 'Rescheduled', 'No Show') DEFAULT 'Scheduled',
    `scheduled_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_int_match` FOREIGN KEY (`match_id`) REFERENCES `candidate_matches` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_int_scheduled_by` FOREIGN KEY (`scheduled_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_int_datetime` (`interview_datetime`),
    INDEX `idx_int_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `interview_feedback` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `interview_id` INT UNSIGNED NOT NULL,
    `technical_rating` TINYINT UNSIGNED DEFAULT 3,
    `practical_rating` TINYINT UNSIGNED DEFAULT 3,
    `communication_rating` TINYINT UNSIGNED DEFAULT 3,
    `punctuality_rating` TINYINT UNSIGNED DEFAULT 3,
    `recommendation` ENUM('Selected', 'Rejected', 'On Hold') NOT NULL,
    `feedback_comments` TEXT NULL,
    `submitted_by` INT UNSIGNED NOT NULL,
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_fb_interview` FOREIGN KEY (`interview_id`) REFERENCES `interviews` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fb_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_fb_recommendation` (`recommendation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `selections` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `match_id` INT UNSIGNED NOT NULL UNIQUE,
    `offered_designation` VARCHAR(255) NOT NULL,
    `offered_ctc` DECIMAL(10,2) NOT NULL,
    `offer_date` DATE NOT NULL,
    `target_joining_date` DATE NOT NULL,
    `selection_status` ENUM('Selected', 'Offer Accepted', 'Offer Declined') DEFAULT 'Selected',
    `selected_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_sel_match` FOREIGN KEY (`match_id`) REFERENCES `candidate_matches` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sel_selected_by` FOREIGN KEY (`selected_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_sel_status` (`selection_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `offer_letters` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `selection_id` INT UNSIGNED NOT NULL UNIQUE,
    `letter_reference_no` VARCHAR(100) NOT NULL UNIQUE,
    `document_path` VARCHAR(255) NULL,
    `issue_date` DATE NOT NULL,
    `validity_date` DATE NOT NULL,
    `status` ENUM('Issued', 'Signed', 'Expired', 'Revoked') DEFAULT 'Issued',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ol_selection` FOREIGN KEY (`selection_id`) REFERENCES `selections` (`id`) ON DELETE CASCADE,
    INDEX `idx_ol_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `joinings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `selection_id` INT UNSIGNED NOT NULL UNIQUE,
    `actual_joining_date` DATE NOT NULL,
    `plant_reporting_unit` VARCHAR(255) NULL,
    `joining_status` ENUM('Joined', 'No Show', 'Withdrawn') DEFAULT 'Joined',
    `onboarding_remarks` TEXT NULL,
    `verified_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_joi_selection` FOREIGN KEY (`selection_id`) REFERENCES `selections` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_joi_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_joi_status` (`joining_status`),
    INDEX `idx_joi_date` (`actual_joining_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

