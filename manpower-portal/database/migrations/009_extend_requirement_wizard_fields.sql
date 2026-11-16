-- Migration 009: Extend Requirement Wizard Fields and Concurrency Sequence Table
-- Database: manpower_portal_db
-- Organization: PRAYAS – The Automation Academy

-- 1. Concurrency-Safe Requirement Number Sequence Table
CREATE TABLE IF NOT EXISTS `requirement_sequences` (
    `year_month` VARCHAR(6) NOT NULL PRIMARY KEY,
    `last_sequence` INT UNSIGNED NOT NULL DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Extend Manpower Requirements Table with Detailed Wizard Fields
ALTER TABLE `manpower_requirements`
    ADD COLUMN IF NOT EXISTS `department` VARCHAR(100) NULL AFTER `plant_id`,
    ADD COLUMN IF NOT EXISTS `industry_sector` VARCHAR(100) NULL AFTER `department`,
    ADD COLUMN IF NOT EXISTS `requirement_purpose` VARCHAR(255) NULL AFTER `industry_sector`,
    ADD COLUMN IF NOT EXISTS `job_location_pincode` VARCHAR(20) NULL AFTER `job_location_city`,
    ADD COLUMN IF NOT EXISTS `weekly_working_days` TINYINT UNSIGNED DEFAULT 6 AFTER `working_hours`,
    ADD COLUMN IF NOT EXISTS `rotational_shift` ENUM('Yes', 'No') DEFAULT 'Yes' AFTER `shift_details`,
    ADD COLUMN IF NOT EXISTS `safety_equipment` ENUM('Yes', 'No') DEFAULT 'Yes' AFTER `transportation_provided`,
    ADD COLUMN IF NOT EXISTS `other_facilities` TEXT NULL AFTER `safety_equipment`,
    ADD COLUMN IF NOT EXISTS `in_hand_salary` DECIMAL(10,2) NULL AFTER `max_salary`,
    ADD COLUMN IF NOT EXISTS `overtime_available` ENUM('Yes', 'No') DEFAULT 'No' AFTER `in_hand_salary`,
    ADD COLUMN IF NOT EXISTS `bonus_details` VARCHAR(255) NULL AFTER `overtime_available`,
    ADD COLUMN IF NOT EXISTS `additional_benefits` TEXT NULL AFTER `bonus_details`,
    ADD COLUMN IF NOT EXISTS `interview_end_date` DATE NULL AFTER `interview_date`,
    ADD COLUMN IF NOT EXISTS `requirement_expiry_date` DATE NULL AFTER `target_joining_date`,
    ADD COLUMN IF NOT EXISTS `interview_mode` ENUM('Face to Face', 'Virtual / Online', 'Plant Campus Walk-in', 'Hybrid') DEFAULT 'Plant Campus Walk-in' AFTER `priority`,
    ADD COLUMN IF NOT EXISTS `interview_location` TEXT NULL AFTER `interview_mode`,
    ADD COLUMN IF NOT EXISTS `interview_process` TEXT NULL AFTER `interview_location`,
    ADD COLUMN IF NOT EXISTS `number_of_rounds` TINYINT UNSIGNED DEFAULT 2 AFTER `interview_process`,
    ADD COLUMN IF NOT EXISTS `technical_interview_required` TINYINT(1) DEFAULT 1 AFTER `number_of_rounds`,
    ADD COLUMN IF NOT EXISTS `practical_assessment_required` TINYINT(1) DEFAULT 1 AFTER `technical_interview_required`,
    ADD COLUMN IF NOT EXISTS `document_verification_required` TINYINT(1) DEFAULT 1 AFTER `practical_assessment_required`,
    ADD COLUMN IF NOT EXISTS `contact_person` VARCHAR(255) NULL AFTER `document_verification_required`,
    ADD COLUMN IF NOT EXISTS `contact_email` VARCHAR(255) NULL AFTER `contact_person`,
    ADD COLUMN IF NOT EXISTS `contact_phone` VARCHAR(50) NULL AFTER `contact_email`,
    ADD COLUMN IF NOT EXISTS `wizard_step` TINYINT UNSIGNED DEFAULT 1 AFTER `status`,
    ADD COLUMN IF NOT EXISTS `draft_payload_json` LONGTEXT NULL AFTER `wizard_step`;

-- 3. Extend Requirement Trades Table for Multi-Trade Allocation
ALTER TABLE `requirement_trades`
    ADD COLUMN IF NOT EXISTS `qualification` VARCHAR(100) NULL AFTER `vacancies`,
    ADD COLUMN IF NOT EXISTS `board_type` VARCHAR(50) DEFAULT 'Either' AFTER `qualification`,
    ADD COLUMN IF NOT EXISTS `passing_year_from` VARCHAR(50) NULL AFTER `board_type`,
    ADD COLUMN IF NOT EXISTS `passing_year_to` VARCHAR(50) NULL AFTER `passing_year_from`,
    ADD COLUMN IF NOT EXISTS `required_skills` VARCHAR(255) NULL AFTER `passing_year_to`,
    ADD COLUMN IF NOT EXISTS `min_experience` VARCHAR(50) DEFAULT 'Fresher' AFTER `required_skills`,
    ADD COLUMN IF NOT EXISTS `max_experience` VARCHAR(50) NULL AFTER `min_experience`,
    ADD COLUMN IF NOT EXISTS `fresher_allowed` TINYINT(1) DEFAULT 1 AFTER `max_experience`;

