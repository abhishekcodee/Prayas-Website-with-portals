-- ==============================================================================
-- Migration 014: Proposed Industry & Manufacturing Plant Extensions (PROPOSED - NOT EXECUTED)
-- Database: manpower_portal_db
-- Organization: PRAYAS – The Automation Academy
-- Document Version: 2.0 (Part 06 Cycle 2.0 Architecture)
-- Status: PROPOSED FOR REVIEW (DO NOT EXECUTE WITHOUT EXPLICIT STAKEHOLDER APPROVAL)
-- ==============================================================================

-- ------------------------------------------------------------------------------
-- 1. Proposed 'industries' Table (Company -> Industry Hierarchy)
-- ------------------------------------------------------------------------------
-- Purpose:
-- Allows a corporate employer/company to register and manage multiple distinct 
-- industry business units/verticals (e.g. Automotive, Heavy Engineering, Precision Tools)
-- under a single corporate parent entity.

CREATE TABLE IF NOT EXISTS `industries` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `industry_name` VARCHAR(255) NOT NULL,
    `industry_code` VARCHAR(50) NOT NULL,
    `industry_type` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `website` VARCHAR(255) NULL,
    `established_year` INT NULL,
    `primary_contact_name` VARCHAR(255) NOT NULL,
    `designation` VARCHAR(100) NULL,
    `official_email` VARCHAR(255) NOT NULL,
    `contact_number` VARCHAR(50) NOT NULL,
    `alternate_contact` VARCHAR(50) NULL,
    `address_line1` TEXT NOT NULL,
    `address_line2` TEXT NULL,
    `state` VARCHAR(100) NOT NULL,
    `district` VARCHAR(100) NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `taluka` VARCHAR(100) NULL,
    `village` VARCHAR(100) NULL,
    `pincode` VARCHAR(20) NOT NULL,
    `country` VARCHAR(100) DEFAULT 'India',
    `assigned_admin_user_id` INT UNSIGNED NULL,
    `status` ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ind_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ind_admin` FOREIGN KEY (`assigned_admin_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    UNIQUE KEY `uk_company_industry_code` (`company_id`, `industry_code`),
    INDEX `idx_industry_company` (`company_id`),
    INDEX `idx_industry_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. Proposed Operational Columns on 'manufacturing_plants'
-- ------------------------------------------------------------------------------
-- Purpose:
-- Extends physical facility records with geographical coordinates, local recruitment
-- coordinator contacts, and manufacturing operations metadata (shifts, departments, etc.).

ALTER TABLE `manufacturing_plants`
    ADD COLUMN IF NOT EXISTS `industry_id` INT UNSIGNED NULL AFTER `company_id`,
    ADD COLUMN IF NOT EXISTS `taluka` VARCHAR(100) NULL AFTER `city`,
    ADD COLUMN IF NOT EXISTS `country` VARCHAR(100) DEFAULT 'India' AFTER `pincode`,
    ADD COLUMN IF NOT EXISTS `google_maps_url` VARCHAR(500) NULL AFTER `country`,
    ADD COLUMN IF NOT EXISTS `contact_designation` VARCHAR(100) NULL AFTER `contact_person`,
    ADD COLUMN IF NOT EXISTS `contact_email` VARCHAR(255) NULL AFTER `contact_designation`,
    ADD COLUMN IF NOT EXISTS `alternate_contact_number` VARCHAR(50) NULL AFTER `contact_number`,
    ADD COLUMN IF NOT EXISTS `manufacturing_activity` VARCHAR(255) NULL AFTER `manufacturing_sector`,
    ADD COLUMN IF NOT EXISTS `production_category` VARCHAR(100) NULL AFTER `manufacturing_activity`,
    ADD COLUMN IF NOT EXISTS `departments` TEXT NULL AFTER `production_category`,
    ADD COLUMN IF NOT EXISTS `shifts_count` TINYINT UNSIGNED DEFAULT 3 AFTER `departments`,
    ADD COLUMN IF NOT EXISTS `working_days_per_week` TINYINT UNSIGNED DEFAULT 6 AFTER `shifts_count`,
    ADD COLUMN IF NOT EXISTS `workforce_size` VARCHAR(50) NULL AFTER `working_days_per_week`,
    ADD COLUMN IF NOT EXISTS `assigned_admin_user_id` INT UNSIGNED NULL AFTER `workforce_size`;

-- ------------------------------------------------------------------------------
-- 3. Data Impact & Compatibility Analysis
-- ------------------------------------------------------------------------------
-- Impact Assessment:
-- 1. Zero data loss: All modifications are strictly additive.
-- 2. Backward compatibility: Existing code querying `manufacturing_plants` without these
--    columns will continue to function seamlessly.
-- 3. Active execution policy: This migration script remains UNEXECUTED pending
--    formal stakeholder authorization. All Part 06 functionality utilizes dynamic
--    persistence via `system_settings` to guarantee 100% operation without altering
--    live database structures.

-- ------------------------------------------------------------------------------
-- 4. Rollback SQL (Exact Reversal Script)
-- ------------------------------------------------------------------------------
/*
ALTER TABLE `manufacturing_plants`
    DROP COLUMN IF EXISTS `assigned_admin_user_id`,
    DROP COLUMN IF EXISTS `workforce_size`,
    DROP COLUMN IF EXISTS `working_days_per_week`,
    DROP COLUMN IF EXISTS `shifts_count`,
    DROP COLUMN IF EXISTS `departments`,
    DROP COLUMN IF EXISTS `production_category`,
    DROP COLUMN IF EXISTS `manufacturing_activity`,
    DROP COLUMN IF EXISTS `alternate_contact_number`,
    DROP COLUMN IF EXISTS `contact_email`,
    DROP COLUMN IF EXISTS `contact_designation`,
    DROP COLUMN IF EXISTS `google_maps_url`,
    DROP COLUMN IF EXISTS `country`,
    DROP COLUMN IF EXISTS `taluka`,
    DROP COLUMN IF EXISTS `industry_id`;

DROP TABLE IF EXISTS `industries`;
*/

