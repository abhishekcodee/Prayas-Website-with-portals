-- Migration 008: Industry Portal Support (Plants, Invitations, Extended Profile)
-- Database: manpower_portal_db
-- Organization: PRAYAS – The Automation Academy

-- 1. Extend Companies Table with profile & re-verification fields
ALTER TABLE `companies`
    ADD COLUMN IF NOT EXISTS `website` VARCHAR(255) NULL AFTER `pincode`,
    ADD COLUMN IF NOT EXISTS `company_logo` VARCHAR(255) NULL AFTER `website`,
    ADD COLUMN IF NOT EXISTS `description` TEXT NULL AFTER `company_logo`,
    ADD COLUMN IF NOT EXISTS `sensitive_modified_at` DATETIME NULL AFTER `description`;

-- 2. Manufacturing Plants Table
CREATE TABLE IF NOT EXISTS `manufacturing_plants` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `plant_name` VARCHAR(255) NOT NULL,
    `plant_code` VARCHAR(50) NOT NULL,
    `plant_address` TEXT NOT NULL,
    `state` VARCHAR(100) NOT NULL,
    `district` VARCHAR(100) NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `pincode` VARCHAR(20) NOT NULL,
    `contact_person` VARCHAR(255) NOT NULL,
    `contact_number` VARCHAR(50) NOT NULL,
    `plant_type` VARCHAR(100) DEFAULT 'Manufacturing Unit',
    `manufacturing_sector` VARCHAR(100) NULL,
    `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_plant_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_company_plant_code` (`company_id`, `plant_code`),
    INDEX `idx_plant_company` (`company_id`),
    INDEX `idx_plant_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Company Team Member Invitations Table
CREATE TABLE IF NOT EXISTS `company_invitations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `contact_number` VARCHAR(50) NULL,
    `token_hash` VARCHAR(64) NOT NULL UNIQUE,
    `status` ENUM('Pending', 'Accepted', 'Expired', 'Revoked') DEFAULT 'Pending',
    `expires_at` DATETIME NOT NULL,
    `invited_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_inv_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_inv_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_inv_user` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_inv_company` (`company_id`),
    INDEX `idx_inv_email` (`email`),
    INDEX `idx_inv_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Extend Manpower Requirements with Plant Reference
ALTER TABLE `manpower_requirements`
    ADD COLUMN IF NOT EXISTS `plant_id` INT UNSIGNED NULL AFTER `company_id`;

-- 5. Add Industry User Role if not present
INSERT IGNORE INTO `roles` (`id`, `name`, `label`, `description`) 
VALUES (5, 'industry_user', 'Industry Team Member', 'Company team member assisting with plant hiring and candidate review');

