-- Migration 010: Validation History, Notification Enhancements, Email Logs & Deadline Reminders
-- Database: manpower_portal_db
-- Organization: PRAYAS – The Automation Academy

-- 1. Requirement Validation History (Persistent Audit of Algorithm Decisions)
CREATE TABLE IF NOT EXISTS `requirement_validation_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `requirement_id` INT UNSIGNED NOT NULL,
    `validation_run_id` VARCHAR(64) NOT NULL,
    `rule_id` VARCHAR(50) NOT NULL,
    `rule_name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `severity` ENUM('ERROR', 'WARNING', 'INFO', 'MANUAL_REVIEW') NOT NULL,
    `result` ENUM('PASS', 'FAIL', 'WARNING', 'REVIEW_REQUIRED') NOT NULL,
    `field_name` VARCHAR(100) NULL,
    `error_message` TEXT NULL,
    `details_json` TEXT NULL,
    `validation_version` VARCHAR(20) DEFAULT 'ALG-VAL-2026-V1.0',
    `triggered_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_val_hist_req` FOREIGN KEY (`requirement_id`) REFERENCES `manpower_requirements` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_val_hist_user` FOREIGN KEY (`triggered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_val_req` (`requirement_id`),
    INDEX `idx_val_run` (`validation_run_id`),
    INDEX `idx_val_rule` (`rule_id`),
    INDEX `idx_val_severity` (`severity`),
    INDEX `idx_val_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Enhance Existing Notifications Table
ALTER TABLE `notifications`
    ADD COLUMN `company_id` INT UNSIGNED NULL AFTER `recipient_user_id`,
    ADD COLUMN `notification_type` VARCHAR(100) NULL AFTER `company_id`,
    ADD COLUMN `entity_type` VARCHAR(50) NULL AFTER `action_url`,
    ADD COLUMN `entity_id` INT UNSIGNED NULL AFTER `entity_type`,
    ADD COLUMN `read_at` DATETIME NULL AFTER `is_read`,
    ADD COLUMN `status` ENUM('unread', 'read', 'archived') DEFAULT 'unread' AFTER `read_at`,
    ADD CONSTRAINT `fk_notif_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
    ADD INDEX `idx_notif_type` (`notification_type`),
    ADD INDEX `idx_notif_company` (`company_id`),
    ADD INDEX `idx_notif_entity` (`entity_type`, `entity_id`);

-- 3. Email Delivery Logs Table
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `recipient_email` VARCHAR(255) NOT NULL,
    `recipient_user_id` INT UNSIGNED NULL,
    `subject` VARCHAR(255) NOT NULL,
    `template_name` VARCHAR(100) NOT NULL,
    `body_html` MEDIUMTEXT NULL,
    `body_text` TEXT NULL,
    `delivery_status` ENUM('QUEUED', 'SENT', 'FAILED', 'SIMULATED') DEFAULT 'QUEUED',
    `error_message` TEXT NULL,
    `retry_count` INT UNSIGNED DEFAULT 0,
    `sent_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_email_recipient` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_email_recipient` (`recipient_email`),
    INDEX `idx_email_status` (`delivery_status`),
    INDEX `idx_email_template` (`template_name`),
    INDEX `idx_email_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Idempotent Deadline Reminder Logs Table
CREATE TABLE IF NOT EXISTS `deadline_reminder_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `requirement_id` INT UNSIGNED NOT NULL,
    `reminder_type` ENUM('DEADLINE_APPROACHING', 'INTERVIEW_UPCOMING', 'JOINING_UPCOMING', 'REQUIREMENT_EXPIRY') NOT NULL,
    `target_date` DATE NOT NULL,
    `recipient_user_id` INT UNSIGNED NOT NULL,
    `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_rem_req` FOREIGN KEY (`requirement_id`) REFERENCES `manpower_requirements` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rem_user` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_req_rem_user_date` (`requirement_id`, `reminder_type`, `target_date`, `recipient_user_id`),
    INDEX `idx_rem_type` (`reminder_type`),
    INDEX `idx_rem_target` (`target_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Seed Mail / SMTP Platform Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`, `is_public`) VALUES
('smtp_host', 'localhost', 'SMTP Server Hostname', 0),
('smtp_port', '1025', 'SMTP Server Port (e.g. 1025, 587, 465)', 0),
('smtp_user', '', 'SMTP Username / Account', 0),
('smtp_pass', '', 'SMTP Password / API Key', 0),
('smtp_secure', 'tls', 'SMTP Encryption (tls, ssl, none)', 0),
('mail_from_address', 'noreply@prayas-academy.org', 'Sender Email Address for Notifications', 1),
('mail_from_name', 'PRAYAS – The Automation Academy', 'Sender Display Name for Notifications', 1),
('mail_enabled', '0', 'Toggle email dispatch (0=In-App Only/Simulated, 1=Live SMTP)', 0)
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

