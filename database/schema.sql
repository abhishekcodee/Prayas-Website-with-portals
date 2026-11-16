-- ==========================================================
-- PRAYAS PLATFORM - COMPREHENSIVE DATABASE SCHEMA
-- Compatible with MySQL 5.7+ / MariaDB 10.3+ / SQLite 3
-- ==========================================================

-- 1. Enquiries Table (Website Contact & Lead Ingestion)
CREATE TABLE IF NOT EXISTS `enquiries` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `organisation` VARCHAR(200) DEFAULT NULL,
    `role` VARCHAR(100) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('new', 'in_progress', 'contacted', 'converted', 'closed') NOT NULL DEFAULT 'new',
    `notes` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_enquiries_status` (`status`),
    INDEX `idx_enquiries_email` (`email`),
    INDEX `idx_enquiries_role` (`role`),
    INDEX `idx_enquiries_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Administrators Table
CREATE TABLE IF NOT EXISTS `admin` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('super_admin', 'admin', 'recruiter') NOT NULL DEFAULT 'admin',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `last_login` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_admin_email` (`email`),
    INDEX `idx_admin_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Users / Candidates Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `registration_no` VARCHAR(30) UNIQUE DEFAULT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `mobile` VARCHAR(15) NOT NULL UNIQUE,
    `password` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('Pending', 'Active', 'Blocked') NOT NULL DEFAULT 'Pending',
    `remarks` TEXT DEFAULT NULL,
    `reviewed_by` INT UNSIGNED DEFAULT NULL,
    `reviewed_at` DATETIME DEFAULT NULL,
    `first_login` TINYINT(1) DEFAULT 1,
    `current_step` TINYINT DEFAULT 1,
    `otp` VARCHAR(6) DEFAULT NULL,
    `otp_expiry` DATETIME DEFAULT NULL,
    `otp_verified` TINYINT(1) DEFAULT 0,
    `registration_completed` TINYINT(1) DEFAULT 0,
    `profile_confirmed` TINYINT(1) DEFAULT 0,
    `source` VARCHAR(50) DEFAULT 'website',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_mobile` (`mobile`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Student Profiles Table
CREATE TABLE IF NOT EXISTS `student_profile` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `dob` DATE DEFAULT NULL,
    `place_of_birth` VARCHAR(150) DEFAULT NULL,
    `career_objective` TEXT DEFAULT NULL,
    `hobbies` TEXT DEFAULT NULL,
    `gender` ENUM('Male', 'Female', 'Other') DEFAULT NULL,
    `marital_status` VARCHAR(50) DEFAULT NULL,
    `height` DECIMAL(5,2) DEFAULT NULL,
    `weight` DECIMAL(5,2) DEFAULT NULL,
    `blood_group` VARCHAR(10) DEFAULT NULL,
    `passport_no` VARCHAR(50) DEFAULT NULL,
    `alternate_number` VARCHAR(15) DEFAULT NULL,
    `emergency_contact` VARCHAR(15) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `permanent_address` TEXT DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `permanent_city` VARCHAR(100) DEFAULT NULL,
    `state` VARCHAR(100) DEFAULT NULL,
    `permanent_state` VARCHAR(100) DEFAULT NULL,
    `pincode` VARCHAR(10) DEFAULT NULL,
    `permanent_pincode` VARCHAR(10) DEFAULT NULL,
    `father_name` VARCHAR(150) DEFAULT NULL,
    `father_age` TINYINT DEFAULT NULL,
    `father_qualification` VARCHAR(150) DEFAULT NULL,
    `father_occupation` VARCHAR(150) DEFAULT NULL,
    `father_income` DECIMAL(12,2) DEFAULT NULL,
    `mother_name` VARCHAR(150) DEFAULT NULL,
    `mother_age` TINYINT DEFAULT NULL,
    `mother_qualification` VARCHAR(150) DEFAULT NULL,
    `mother_occupation` VARCHAR(150) DEFAULT NULL,
    `mother_income` DECIMAL(12,2) DEFAULT NULL,
    `nationality` VARCHAR(100) DEFAULT 'Indian',
    `religion` VARCHAR(50) DEFAULT NULL,
    `category` VARCHAR(30) DEFAULT NULL,
    `aadhaar_no` VARCHAR(12) DEFAULT NULL,
    `pan_no` VARCHAR(10) DEFAULT NULL,
    `govt_id_type` VARCHAR(50) DEFAULT NULL,
    `govt_id_number` VARCHAR(100) DEFAULT NULL,
    `govt_id_image` VARCHAR(255) DEFAULT NULL,
    `domicile_state` VARCHAR(100) DEFAULT NULL,
    `pwbd` ENUM('Yes', 'No') DEFAULT 'No',
    `pwbd_id` VARCHAR(30) DEFAULT NULL,
    `pwbd_certificate` VARCHAR(255) DEFAULT NULL,
    `photo` VARCHAR(255) DEFAULT NULL,
    `declaration` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_profile_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Education Table
CREATE TABLE IF NOT EXISTS `education` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `qualification` VARCHAR(100) DEFAULT NULL,
    `specialization` VARCHAR(150) DEFAULT NULL,
    `institute` VARCHAR(200) DEFAULT NULL,
    `board` VARCHAR(100) DEFAULT NULL,
    `passing_year` YEAR DEFAULT NULL,
    `percentage` DECIMAL(5,2) DEFAULT NULL,
    `status` ENUM('Completed', 'Pursuing') DEFAULT 'Completed',
    INDEX `idx_education_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Experience Table
CREATE TABLE IF NOT EXISTS `experience` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `company_name` VARCHAR(200) DEFAULT NULL,
    `designation` VARCHAR(150) DEFAULT NULL,
    `employment_type` ENUM('Full Time', 'Part Time', 'Internship', 'Freelance') DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `currently_working` TINYINT(1) DEFAULT 0,
    `job_description` TEXT DEFAULT NULL,
    `achievement` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_experience_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Skills Table
CREATE TABLE IF NOT EXISTS `skills` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `skill_name` VARCHAR(100) DEFAULT NULL,
    `skill_level` ENUM('Beginner', 'Intermediate', 'Advanced') DEFAULT 'Intermediate',
    INDEX `idx_skills_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Certifications Table
CREATE TABLE IF NOT EXISTS `certificates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `certificate_name` VARCHAR(255) NOT NULL,
    `organization` VARCHAR(255) DEFAULT NULL,
    `issue_date` DATE DEFAULT NULL,
    `certificate_id` VARCHAR(100) DEFAULT NULL,
    `certificate_file` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cert_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Projects Table
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `project_title` VARCHAR(255) DEFAULT NULL,
    `project_description` TEXT DEFAULT NULL,
    `technologies` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_projects_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Resumes Table
CREATE TABLE IF NOT EXISTS `resume` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `resume_type` ENUM('upload', 'generated') DEFAULT 'upload',
    `resume_file` VARCHAR(255) DEFAULT NULL,
    `original_name` VARCHAR(255) DEFAULT NULL,
    `file_size` BIGINT DEFAULT NULL,
    `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_resume_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Aptitude Questions Table
CREATE TABLE IF NOT EXISTS `aptitude_questions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `question` TEXT NOT NULL,
    `option_a` VARCHAR(255) NOT NULL,
    `option_b` VARCHAR(255) NOT NULL,
    `option_c` VARCHAR(255) NOT NULL,
    `option_d` VARCHAR(255) NOT NULL,
    `correct_option` CHAR(1) NOT NULL,
    `marks` INT DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Aptitude Results Table
CREATE TABLE IF NOT EXISTS `aptitude_results` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `score` INT NOT NULL,
    `total_marks` INT NOT NULL,
    `status` VARCHAR(50) DEFAULT 'completed',
    `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_results_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
