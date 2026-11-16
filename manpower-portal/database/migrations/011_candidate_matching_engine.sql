-- Migration 011: Candidate Matching Runs, Detailed Scores & Shortlisting Actions
-- Database: manpower_portal_db
-- Organization: PRAYAS – The Automation Academy

-- 1. Candidate Matching Execution Runs
CREATE TABLE IF NOT EXISTS `candidate_matching_runs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `requirement_id` INT UNSIGNED NOT NULL,
    `run_identifier` VARCHAR(64) NOT NULL UNIQUE,
    `total_candidates_scanned` INT UNSIGNED DEFAULT 0,
    `eligible_candidates_found` INT UNSIGNED DEFAULT 0,
    `ineligible_candidates_count` INT UNSIGNED DEFAULT 0,
    `weights_json` TEXT NULL,
    `filters_applied_json` TEXT NULL,
    `triggered_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_cmr_requirement` FOREIGN KEY (`requirement_id`) REFERENCES `manpower_requirements` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cmr_user` FOREIGN KEY (`triggered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_cmr_req` (`requirement_id`),
    INDEX `idx_cmr_run` (`run_identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Enhance candidate_matches Table with Granular Score Dimensions and Industry Sharing Gate
ALTER TABLE `candidate_matches`
    ADD COLUMN `matching_run_id` VARCHAR(64) NULL AFTER `requirement_id`,
    ADD COLUMN `eligibility_status` ENUM('ELIGIBLE', 'NOT_ELIGIBLE') DEFAULT 'ELIGIBLE' AFTER `match_score`,
    ADD COLUMN `trade_score` DECIMAL(5,2) DEFAULT 0.00 AFTER `eligibility_status`,
    ADD COLUMN `batch_score` DECIMAL(5,2) DEFAULT 0.00 AFTER `trade_score`,
    ADD COLUMN `qual_score` DECIMAL(5,2) DEFAULT 0.00 AFTER `batch_score`,
    ADD COLUMN `skills_score` DECIMAL(5,2) DEFAULT 0.00 AFTER `qual_score`,
    ADD COLUMN `location_score` DECIMAL(5,2) DEFAULT 0.00 AFTER `skills_score`,
    ADD COLUMN `exp_score` DECIMAL(5,2) DEFAULT 0.00 AFTER `location_score`,
    ADD COLUMN `avail_score` DECIMAL(5,2) DEFAULT 0.00 AFTER `exp_score`,
    ADD COLUMN `eligibility_reasons` TEXT NULL AFTER `avail_score`,
    ADD COLUMN `is_shared_with_industry` TINYINT(1) DEFAULT 0 AFTER `status`,
    ADD COLUMN `shared_at` DATETIME NULL AFTER `is_shared_with_industry`,
    ADD INDEX `idx_cm_eligibility` (`eligibility_status`),
    ADD INDEX `idx_cm_shared` (`is_shared_with_industry`);

-- 3. Candidate Shortlist & Recruiter Audit Actions
CREATE TABLE IF NOT EXISTS `candidate_shortlist_actions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `match_id` INT UNSIGNED NOT NULL,
    `action` ENUM('SHORTLIST', 'REJECT', 'HOLD', 'RESET', 'SHARE_WITH_INDUSTRY') NOT NULL,
    `remarks` TEXT NULL,
    `performed_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_csa_match` FOREIGN KEY (`match_id`) REFERENCES `candidate_matches` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_csa_user` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_csa_match` (`match_id`),
    INDEX `idx_csa_action` (`action`),
    INDEX `idx_csa_user` (`performed_by`),
    INDEX `idx_csa_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

