-- Migration 012: Recruitment Pipeline Enhancements (Screening to Joining Lifecycle)
-- Database: manpower_portal_db
-- Organization: PRAYAS – The Automation Academy

-- 1. Extend candidate_matches Pipeline Stages
ALTER TABLE `candidate_matches`
    MODIFY COLUMN `status` ENUM(
        'Matched', 
        'Shortlisted', 
        'Screened', 
        'Scheduled', 
        'Completed',
        'Selected', 
        'Offer Released', 
        'Offer Accepted', 
        'Offer Declined', 
        'Joining Pending', 
        'Joined', 
        'Rejected', 
        'On Hold', 
        'Withdrawn', 
        'No Show', 
        'Closed'
    ) DEFAULT 'Matched';

-- 2. Audit Trail for Pipeline Stage Transitions
CREATE TABLE IF NOT EXISTS `recruitment_stage_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `match_id` INT UNSIGNED NOT NULL,
    `previous_stage` VARCHAR(50) NULL,
    `new_stage` VARCHAR(50) NOT NULL,
    `notes` TEXT NULL,
    `changed_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_rsh_match` FOREIGN KEY (`match_id`) REFERENCES `candidate_matches` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rsh_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_rsh_match` (`match_id`),
    INDEX `idx_rsh_stage` (`new_stage`),
    INDEX `idx_rsh_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Enhance Candidate Screening Table
ALTER TABLE `candidate_screening`
    MODIFY COLUMN `screening_outcome` ENUM('Passed', 'Failed', 'On Hold', 'Needs Re-Screening') NOT NULL,
    ADD COLUMN `willing_to_relocate` VARCHAR(50) NULL AFTER `screening_outcome`,
    ADD COLUMN `candidate_availability` VARCHAR(100) NULL AFTER `willing_to_relocate`,
    ADD COLUMN `expected_salary` VARCHAR(100) NULL AFTER `candidate_availability`,
    ADD COLUMN `verified_trade_skills` TINYINT(1) DEFAULT 1 AFTER `expected_salary`;

-- 4. Enhance Interviews Table
ALTER TABLE `interviews`
    ADD COLUMN `interview_mode` ENUM('In-Person', 'Online', 'Telephone') DEFAULT 'In-Person' AFTER `venue_or_link`,
    ADD COLUMN `interviewer_designation` VARCHAR(150) NULL AFTER `interviewer_name`,
    ADD COLUMN `interview_end_datetime` DATETIME NULL AFTER `interview_datetime`,
    ADD COLUMN `interview_remarks` TEXT NULL AFTER `status`;

-- 5. Enhance Interview Feedback Table
ALTER TABLE `interview_feedback`
    MODIFY COLUMN `recommendation` ENUM('Strongly Recommended', 'Recommended', 'Hold', 'Not Recommended') NOT NULL,
    ADD COLUMN `trade_knowledge_rating` TINYINT UNSIGNED DEFAULT 3 AFTER `practical_rating`,
    ADD COLUMN `safety_rating` TINYINT UNSIGNED DEFAULT 3 AFTER `communication_rating`,
    ADD COLUMN `overall_rating` TINYINT UNSIGNED DEFAULT 3 AFTER `safety_rating`;

-- 6. Enhance Selections Table
ALTER TABLE `selections`
    MODIFY COLUMN `selection_status` ENUM('Selected', 'Rejected', 'On Hold', 'Offer Released', 'Offer Accepted', 'Offer Declined') DEFAULT 'Selected',
    ADD COLUMN `decision_remarks` TEXT NULL AFTER `selection_status`,
    ADD COLUMN `plant_id` INT UNSIGNED NULL AFTER `offered_designation`,
    ADD CONSTRAINT `fk_sel_plant` FOREIGN KEY (`plant_id`) REFERENCES `manufacturing_plants` (`id`) ON DELETE SET NULL;

-- 7. Enhance Offer Letters Table
ALTER TABLE `offer_letters`
    MODIFY COLUMN `status` ENUM('Draft', 'Pending Approval', 'Approved', 'Released', 'Accepted', 'Declined', 'Expired', 'Withdrawn') DEFAULT 'Draft',
    ADD COLUMN `job_designation` VARCHAR(255) NULL AFTER `letter_reference_no`,
    ADD COLUMN `salary_offered` DECIMAL(10,2) NULL AFTER `job_designation`,
    ADD COLUMN `salary_type` VARCHAR(50) DEFAULT 'Monthly' AFTER `salary_offered`,
    ADD COLUMN `benefits_summary` TEXT NULL AFTER `salary_type`,
    ADD COLUMN `job_location` VARCHAR(255) NULL AFTER `benefits_summary`,
    ADD COLUMN `proposed_joining_date` DATE NULL AFTER `validity_date`,
    ADD COLUMN `offer_remarks` TEXT NULL AFTER `status`,
    ADD COLUMN `candidate_response_notes` TEXT NULL AFTER `offer_remarks`,
    ADD COLUMN `approved_by` INT UNSIGNED NULL AFTER `candidate_response_notes`,
    ADD CONSTRAINT `fk_ol_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- 8. Enhance Joinings Table
ALTER TABLE `joinings`
    MODIFY COLUMN `joining_status` ENUM('Joining Pending', 'Joined', 'Joining Delayed', 'Did Not Join', 'Cancelled', 'No Show') DEFAULT 'Joined',
    ADD COLUMN `expected_joining_date` DATE NULL AFTER `selection_id`,
    ADD COLUMN `delay_reason` TEXT NULL AFTER `onboarding_remarks`;

