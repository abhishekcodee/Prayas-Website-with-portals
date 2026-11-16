-- Migration 018: Add Company Website / Profile URL to Manpower Requirements
-- Database: manpower_portal_db
-- Organization: PRAYAS – The Automation Academy

ALTER TABLE `manpower_requirements`
    ADD COLUMN IF NOT EXISTS `company_website` VARCHAR(255) NULL AFTER `requirement_purpose`;
