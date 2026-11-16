-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: manpower_portal_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `company_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `details_json` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_company` (`company_id`),
  KEY `idx_audit_created` (`created_at`),
  CONSTRAINT `fk_audit_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1591 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1575,1,NULL,'SYSTEM_DATABASE_CLEANUP','127.0.0.1',NULL,'{\"message\":\"Option A: Purged test companies and automated test fixtures. Kept Super Admin and Company 285 (Absolute Motion Private Limited).\"}','2026-11-12 04:39:25'),(1576,1,NULL,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','{\"role\":\"super_admin\"}','2026-11-12 04:40:17'),(1577,1,NULL,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','{\"role\":\"super_admin\"}','2026-11-12 04:45:10'),(1581,1,NULL,'LOGOUT','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',NULL,'2026-11-12 04:48:36'),(1583,251,285,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','{\"role\":\"industry_admin\"}','2026-11-12 04:50:39'),(1584,NULL,NULL,'LOGIN_FAILED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','{\"email\":\"hr@absolutemotion.in\",\"reason\":\"Invalid credentials\"}','2026-11-12 04:51:56'),(1585,NULL,NULL,'LOGIN_FAILED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','{\"email\":\"hr@absolutemotion.in\",\"reason\":\"Invalid credentials\"}','2026-11-12 04:52:02'),(1586,NULL,NULL,'LOGIN_FAILED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','{\"email\":\"hr@absolutemotion.in\",\"reason\":\"Invalid credentials\"}','2026-11-12 04:52:09'),(1587,NULL,NULL,'LOGIN_FAILED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','{\"email\":\"hr@absolutemotion.in\",\"reason\":\"Invalid credentials\"}','2026-11-12 04:52:40'),(1588,NULL,NULL,'LOGIN_FAILED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','{\"email\":\"hr@absolutemotion.com\",\"reason\":\"Invalid credentials\"}','2026-11-12 04:52:48'),(1589,264,314,'INDUSTRY_REGISTERED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','{\"company_name\":\"Absolute Motion Private Limited\"}','2026-11-12 04:54:04'),(1590,264,314,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','{\"role\":\"industry_admin\"}','2026-11-12 04:54:08');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `candidate_matches`
--

DROP TABLE IF EXISTS `candidate_matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `candidate_matches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requirement_id` int(10) unsigned NOT NULL,
  `matching_run_id` varchar(64) DEFAULT NULL,
  `candidate_source` enum('prayas_dashboard','itistudents') NOT NULL,
  `candidate_source_id` int(10) unsigned NOT NULL,
  `candidate_name` varchar(255) NOT NULL,
  `trade` varchar(150) NOT NULL,
  `passing_year` varchar(50) DEFAULT NULL,
  `match_score` decimal(5,2) NOT NULL,
  `eligibility_status` enum('ELIGIBLE','NOT_ELIGIBLE') DEFAULT 'ELIGIBLE',
  `trade_score` decimal(5,2) DEFAULT 0.00,
  `batch_score` decimal(5,2) DEFAULT 0.00,
  `qual_score` decimal(5,2) DEFAULT 0.00,
  `skills_score` decimal(5,2) DEFAULT 0.00,
  `location_score` decimal(5,2) DEFAULT 0.00,
  `exp_score` decimal(5,2) DEFAULT 0.00,
  `avail_score` decimal(5,2) DEFAULT 0.00,
  `eligibility_reasons` text DEFAULT NULL,
  `match_details_json` text DEFAULT NULL,
  `status` enum('Matched','Shortlisted','Screened','Scheduled','Completed','Selected','Offer Released','Offer Accepted','Offer Declined','Joining Pending','Joined','Rejected','On Hold','Withdrawn','No Show','Closed') DEFAULT 'Matched',
  `is_shared_with_industry` tinyint(1) DEFAULT 0,
  `shared_at` datetime DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `matched_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_req_candidate` (`requirement_id`,`candidate_source`,`candidate_source_id`),
  KEY `idx_cm_status` (`status`),
  KEY `idx_cm_score` (`match_score`),
  KEY `idx_cm_trade` (`trade`),
  KEY `idx_cm_eligibility` (`eligibility_status`),
  KEY `idx_cm_shared` (`is_shared_with_industry`),
  CONSTRAINT `fk_cm_requirement` FOREIGN KEY (`requirement_id`) REFERENCES `manpower_requirements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18657 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `candidate_matches`
--

LOCK TABLES `candidate_matches` WRITE;
/*!40000 ALTER TABLE `candidate_matches` DISABLE KEYS */;
/*!40000 ALTER TABLE `candidate_matches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `candidate_matching_runs`
--

DROP TABLE IF EXISTS `candidate_matching_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `candidate_matching_runs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requirement_id` int(10) unsigned NOT NULL,
  `run_identifier` varchar(64) NOT NULL,
  `total_candidates_scanned` int(10) unsigned DEFAULT 0,
  `eligible_candidates_found` int(10) unsigned DEFAULT 0,
  `ineligible_candidates_count` int(10) unsigned DEFAULT 0,
  `weights_json` text DEFAULT NULL,
  `filters_applied_json` text DEFAULT NULL,
  `triggered_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `run_identifier` (`run_identifier`),
  KEY `fk_cmr_user` (`triggered_by`),
  KEY `idx_cmr_req` (`requirement_id`),
  KEY `idx_cmr_run` (`run_identifier`),
  CONSTRAINT `fk_cmr_requirement` FOREIGN KEY (`requirement_id`) REFERENCES `manpower_requirements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cmr_user` FOREIGN KEY (`triggered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `candidate_matching_runs`
--

LOCK TABLES `candidate_matching_runs` WRITE;
/*!40000 ALTER TABLE `candidate_matching_runs` DISABLE KEYS */;
/*!40000 ALTER TABLE `candidate_matching_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `candidate_screening`
--

DROP TABLE IF EXISTS `candidate_screening`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `candidate_screening` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `match_id` int(10) unsigned NOT NULL,
  `screener_id` int(10) unsigned NOT NULL,
  `screening_outcome` enum('Passed','Failed','On Hold','Needs Re-Screening') NOT NULL,
  `willing_to_relocate` varchar(50) DEFAULT NULL,
  `candidate_availability` varchar(100) DEFAULT NULL,
  `expected_salary` varchar(100) DEFAULT NULL,
  `verified_trade_skills` tinyint(1) DEFAULT 1,
  `screening_notes` text DEFAULT NULL,
  `screened_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_cs_match` (`match_id`),
  KEY `fk_cs_screener` (`screener_id`),
  KEY `idx_cs_outcome` (`screening_outcome`),
  CONSTRAINT `fk_cs_match` FOREIGN KEY (`match_id`) REFERENCES `candidate_matches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cs_screener` FOREIGN KEY (`screener_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `candidate_screening`
--

LOCK TABLES `candidate_screening` WRITE;
/*!40000 ALTER TABLE `candidate_screening` DISABLE KEYS */;
/*!40000 ALTER TABLE `candidate_screening` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `candidate_shortlist_actions`
--

DROP TABLE IF EXISTS `candidate_shortlist_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `candidate_shortlist_actions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `match_id` int(10) unsigned NOT NULL,
  `action` enum('SHORTLIST','REJECT','HOLD','RESET','SHARE_WITH_INDUSTRY') NOT NULL,
  `remarks` text DEFAULT NULL,
  `performed_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_csa_match` (`match_id`),
  KEY `idx_csa_action` (`action`),
  KEY `idx_csa_user` (`performed_by`),
  KEY `idx_csa_created` (`created_at`),
  CONSTRAINT `fk_csa_match` FOREIGN KEY (`match_id`) REFERENCES `candidate_matches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_csa_user` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `candidate_shortlist_actions`
--

LOCK TABLES `candidate_shortlist_actions` WRITE;
/*!40000 ALTER TABLE `candidate_shortlist_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `candidate_shortlist_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `industry_type` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `state` varchar(100) NOT NULL,
  `district` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `pincode` varchar(20) NOT NULL,
  `website` varchar(255) DEFAULT NULL,
  `company_logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sensitive_modified_at` datetime DEFAULT NULL,
  `hr_contact_name` varchar(255) NOT NULL,
  `hr_contact_phone` varchar(50) NOT NULL,
  `hr_contact_email` varchar(255) NOT NULL,
  `verification_status` enum('Pending','Under Review','Verified','Active','Rejected','Suspended') DEFAULT 'Pending',
  `verified_by` int(10) unsigned DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `hr_contact_email` (`hr_contact_email`),
  UNIQUE KEY `registration_number` (`registration_number`),
  KEY `idx_companies_status` (`verification_status`),
  KEY `idx_companies_district` (`district`),
  KEY `idx_companies_state` (`state`),
  KEY `fk_companies_verified_by` (`verified_by`),
  CONSTRAINT `fk_companies_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=315 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (285,'Absolute Motion Private Limited','1234567890987654scb','Industrial Automation & Robotics','RAbale','Maharashtra','Thane','Navi Mumbai','400070',NULL,NULL,NULL,NULL,'naved','7039229649','hr@absolutemotion.in','Pending',NULL,NULL,NULL,'2026-11-06 08:32:04','2026-11-06 10:11:52'),(314,'Absolute Motion Private Limited','1234567890987654scg','Automotive & Auto Components','Rabale','Maharashtra','Thane','Navi Mumbai','400070',NULL,NULL,NULL,NULL,'naved','7039229649','admin@absolute.in','Pending',NULL,NULL,NULL,'2026-11-12 04:54:04','2026-11-12 04:54:04');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_invitations`
--

DROP TABLE IF EXISTS `company_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_invitations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `token_hash` varchar(64) NOT NULL,
  `status` enum('Pending','Accepted','Expired','Revoked') DEFAULT 'Pending',
  `expires_at` datetime NOT NULL,
  `invited_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `fk_inv_role` (`role_id`),
  KEY `fk_inv_user` (`invited_by`),
  KEY `idx_inv_company` (`company_id`),
  KEY `idx_inv_email` (`email`),
  KEY `idx_inv_status` (`status`),
  CONSTRAINT `fk_inv_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inv_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `fk_inv_user` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_invitations`
--

LOCK TABLES `company_invitations` WRITE;
/*!40000 ALTER TABLE `company_invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `company_invitations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deadline_reminder_logs`
--

DROP TABLE IF EXISTS `deadline_reminder_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deadline_reminder_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requirement_id` int(10) unsigned NOT NULL,
  `reminder_type` enum('DEADLINE_APPROACHING','INTERVIEW_UPCOMING','JOINING_UPCOMING','REQUIREMENT_EXPIRY') NOT NULL,
  `target_date` date NOT NULL,
  `recipient_user_id` int(10) unsigned NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_req_rem_user_date` (`requirement_id`,`reminder_type`,`target_date`,`recipient_user_id`),
  KEY `fk_rem_user` (`recipient_user_id`),
  KEY `idx_rem_type` (`reminder_type`),
  KEY `idx_rem_target` (`target_date`),
  CONSTRAINT `fk_rem_req` FOREIGN KEY (`requirement_id`) REFERENCES `manpower_requirements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rem_user` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deadline_reminder_logs`
--

LOCK TABLES `deadline_reminder_logs` WRITE;
/*!40000 ALTER TABLE `deadline_reminder_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `deadline_reminder_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(255) NOT NULL,
  `recipient_user_id` int(10) unsigned DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `template_name` varchar(100) NOT NULL,
  `body_html` mediumtext DEFAULT NULL,
  `body_text` text DEFAULT NULL,
  `delivery_status` enum('QUEUED','SENT','FAILED','SIMULATED') DEFAULT 'QUEUED',
  `error_message` text DEFAULT NULL,
  `retry_count` int(10) unsigned DEFAULT 0,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_email_recipient` (`recipient_user_id`),
  KEY `idx_email_recipient` (`recipient_email`),
  KEY `idx_email_status` (`delivery_status`),
  KEY `idx_email_template` (`template_name`),
  KEY `idx_email_created` (`created_at`),
  CONSTRAINT `fk_email_recipient` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=161 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interview_feedback`
--

DROP TABLE IF EXISTS `interview_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `interview_feedback` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `interview_id` int(10) unsigned NOT NULL,
  `technical_rating` tinyint(3) unsigned DEFAULT 3,
  `practical_rating` tinyint(3) unsigned DEFAULT 3,
  `trade_knowledge_rating` tinyint(3) unsigned DEFAULT 3,
  `communication_rating` tinyint(3) unsigned DEFAULT 3,
  `safety_rating` tinyint(3) unsigned DEFAULT 3,
  `overall_rating` tinyint(3) unsigned DEFAULT 3,
  `punctuality_rating` tinyint(3) unsigned DEFAULT 3,
  `recommendation` enum('Strongly Recommended','Recommended','Hold','Not Recommended') NOT NULL,
  `feedback_comments` text DEFAULT NULL,
  `submitted_by` int(10) unsigned NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_fb_interview` (`interview_id`),
  KEY `fk_fb_submitted_by` (`submitted_by`),
  KEY `idx_fb_recommendation` (`recommendation`),
  CONSTRAINT `fk_fb_interview` FOREIGN KEY (`interview_id`) REFERENCES `interviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fb_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interview_feedback`
--

LOCK TABLES `interview_feedback` WRITE;
/*!40000 ALTER TABLE `interview_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `interview_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interviews`
--

DROP TABLE IF EXISTS `interviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `interviews` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `match_id` int(10) unsigned NOT NULL,
  `interview_datetime` datetime NOT NULL,
  `interview_end_datetime` datetime DEFAULT NULL,
  `venue_or_link` text NOT NULL,
  `interview_mode` enum('In-Person','Online','Telephone') DEFAULT 'In-Person',
  `interview_round` varchar(100) DEFAULT 'Round 1 - Technical',
  `interviewer_name` varchar(255) DEFAULT NULL,
  `interviewer_designation` varchar(150) DEFAULT NULL,
  `status` enum('Scheduled','Completed','Cancelled','Rescheduled','No Show') DEFAULT 'Scheduled',
  `interview_remarks` text DEFAULT NULL,
  `scheduled_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_int_match` (`match_id`),
  KEY `fk_int_scheduled_by` (`scheduled_by`),
  KEY `idx_int_datetime` (`interview_datetime`),
  KEY `idx_int_status` (`status`),
  CONSTRAINT `fk_int_match` FOREIGN KEY (`match_id`) REFERENCES `candidate_matches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_int_scheduled_by` FOREIGN KEY (`scheduled_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interviews`
--

LOCK TABLES `interviews` WRITE;
/*!40000 ALTER TABLE `interviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `interviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `joinings`
--

DROP TABLE IF EXISTS `joinings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `joinings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `selection_id` int(10) unsigned NOT NULL,
  `expected_joining_date` date DEFAULT NULL,
  `actual_joining_date` date NOT NULL,
  `plant_reporting_unit` varchar(255) DEFAULT NULL,
  `joining_status` enum('Joining Pending','Joined','Joining Delayed','Did Not Join','Cancelled','No Show') DEFAULT 'Joined',
  `onboarding_remarks` text DEFAULT NULL,
  `delay_reason` text DEFAULT NULL,
  `verified_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `selection_id` (`selection_id`),
  KEY `fk_joi_verified_by` (`verified_by`),
  KEY `idx_joi_status` (`joining_status`),
  KEY `idx_joi_date` (`actual_joining_date`),
  CONSTRAINT `fk_joi_selection` FOREIGN KEY (`selection_id`) REFERENCES `selections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_joi_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `joinings`
--

LOCK TABLES `joinings` WRITE;
/*!40000 ALTER TABLE `joinings` DISABLE KEYS */;
/*!40000 ALTER TABLE `joinings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `manpower_requirements`
--

DROP TABLE IF EXISTS `manpower_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `manpower_requirements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requirement_id` varchar(50) NOT NULL,
  `company_id` int(10) unsigned NOT NULL,
  `plant_id` int(10) unsigned DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `industry_sector` varchar(100) DEFAULT NULL,
  `requirement_purpose` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `quantity_required` int(10) unsigned NOT NULL,
  `qualification` varchar(100) NOT NULL,
  `board_type` enum('NCVT','SCVT','Either') DEFAULT 'Either',
  `passing_year_from` varchar(50) DEFAULT NULL,
  `passing_year_to` varchar(50) DEFAULT NULL,
  `experience_required` varchar(100) NOT NULL,
  `job_location_state` varchar(100) NOT NULL,
  `job_location_district` varchar(100) NOT NULL,
  `job_location_city` varchar(100) NOT NULL,
  `job_location_pincode` varchar(20) DEFAULT NULL,
  `job_description` text NOT NULL,
  `min_age` int(10) unsigned DEFAULT 18,
  `max_age` int(10) unsigned DEFAULT NULL,
  `gender_preference` enum('Any','Male','Female') DEFAULT 'Any',
  `min_salary` decimal(10,2) NOT NULL,
  `max_salary` decimal(10,2) NOT NULL,
  `in_hand_salary` decimal(10,2) DEFAULT NULL,
  `overtime_available` enum('Yes','No') DEFAULT 'No',
  `bonus_details` varchar(255) DEFAULT NULL,
  `additional_benefits` text DEFAULT NULL,
  `salary_type` enum('Monthly','Stipend','Annual') DEFAULT 'Monthly',
  `employment_type` enum('Full Time','Apprenticeship','Fixed Term','OJT') DEFAULT 'Full Time',
  `working_hours` varchar(50) DEFAULT '8 Hours',
  `weekly_working_days` tinyint(3) unsigned DEFAULT 6,
  `shift_details` varchar(100) DEFAULT 'General Shift',
  `rotational_shift` enum('Yes','No') DEFAULT 'Yes',
  `accommodation_provided` enum('Yes','No','Subsidized') DEFAULT 'No',
  `food_facility` enum('Yes','No','Subsidized') DEFAULT 'No',
  `transportation_provided` enum('Yes','No','Subsidized') DEFAULT 'No',
  `safety_equipment` enum('Yes','No') DEFAULT 'Yes',
  `other_facilities` text DEFAULT NULL,
  `pf_esic_benefit` enum('Yes','No') DEFAULT 'Yes',
  `submission_date` date NOT NULL,
  `application_deadline` date NOT NULL,
  `interview_date` date NOT NULL,
  `interview_end_date` date DEFAULT NULL,
  `target_joining_date` date NOT NULL,
  `requirement_expiry_date` date DEFAULT NULL,
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `interview_mode` enum('Face to Face','Virtual / Online','Plant Campus Walk-in','Hybrid') DEFAULT 'Plant Campus Walk-in',
  `interview_location` text DEFAULT NULL,
  `interview_process` text DEFAULT NULL,
  `number_of_rounds` tinyint(3) unsigned DEFAULT 2,
  `technical_interview_required` tinyint(1) DEFAULT 1,
  `practical_assessment_required` tinyint(1) DEFAULT 1,
  `document_verification_required` tinyint(1) DEFAULT 1,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `status` enum('DRAFT','SUBMITTED','UNDER_VALIDATION','NEEDS_CORRECTION','PENDING_REVIEW','APPROVED','RECRUITMENT_IN_PROGRESS','PARTIALLY_FULFILLED','FULFILLED','CLOSED','CANCELLED','REJECTED') DEFAULT 'DRAFT',
  `wizard_step` tinyint(3) unsigned DEFAULT 1,
  `draft_payload_json` longtext DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `requirement_id` (`requirement_id`),
  KEY `fk_req_approved_by` (`approved_by`),
  KEY `fk_req_created_by` (`created_by`),
  KEY `idx_req_company` (`company_id`),
  KEY `idx_req_status` (`status`),
  KEY `idx_req_district` (`job_location_district`),
  KEY `idx_req_priority` (`priority`),
  KEY `idx_req_dates` (`target_joining_date`,`application_deadline`),
  CONSTRAINT `fk_req_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_req_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_req_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=256 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `manpower_requirements`
--

LOCK TABLES `manpower_requirements` WRITE;
/*!40000 ALTER TABLE `manpower_requirements` DISABLE KEYS */;
INSERT INTO `manpower_requirements` VALUES (232,'REQ-202611-0127',285,NULL,'','Industrial Automation & Robotics','','Electrician',5,'ITI Passed','Either','2022','2026','Fresher','Maharashtra','Thane','Navi Mumbai','400070','avghdskjldfgydhsufyghbsjhufdghvbsjhg',18,NULL,'Male',15000.00,20000.00,NULL,'No','','','Monthly','Full Time','8 Hours',6,'Rotational Shifts (A/B/C)','Yes','No','Subsidized','No','Yes','','Yes','2026-11-06','2026-11-21','2026-11-26','2026-11-28','2026-12-06',NULL,'Medium','Plant Campus Walk-in','','',2,1,1,1,'naved','hr@absolutemotion.in','7039229649','PENDING_REVIEW',8,NULL,NULL,NULL,251,'2026-11-06 08:34:42','2026-11-06 08:34:42'),(241,'REQ-202611-0144',285,NULL,'','Industrial Automation & Robotics','','Electrician',5,'ITI Passed','Either','2022','2026','Fresher','Maharashtra','Thane','Navi Mumbai','400070','avghdskjldfgydhsufyghbsjhufdghvbsjhg',18,NULL,'Male',15000.00,20000.00,NULL,'No','','','Monthly','Full Time','8 Hours',6,'Rotational Shifts (A/B/C)','Yes','No','Subsidized','No','Yes','','Yes','2026-11-06','2026-11-21','2026-11-26','2026-11-28','2026-12-06',NULL,'Medium','Plant Campus Walk-in','','',2,1,1,1,'naved','hr@absolutemotion.in','7039229649','PENDING_REVIEW',8,NULL,NULL,NULL,251,'2026-11-06 09:04:32','2026-11-06 09:04:32');
/*!40000 ALTER TABLE `manpower_requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `manufacturing_plants`
--

DROP TABLE IF EXISTS `manufacturing_plants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `manufacturing_plants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `plant_name` varchar(255) NOT NULL,
  `plant_code` varchar(50) NOT NULL,
  `plant_address` text NOT NULL,
  `state` varchar(100) NOT NULL,
  `district` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `pincode` varchar(20) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `contact_number` varchar(50) NOT NULL,
  `plant_type` varchar(100) DEFAULT 'Manufacturing Unit',
  `manufacturing_sector` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_plant_code` (`company_id`,`plant_code`),
  KEY `idx_plant_company` (`company_id`),
  KEY `idx_plant_status` (`status`),
  CONSTRAINT `fk_plant_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=167 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `manufacturing_plants`
--

LOCK TABLES `manufacturing_plants` WRITE;
/*!40000 ALTER TABLE `manufacturing_plants` DISABLE KEYS */;
/*!40000 ALTER TABLE `manufacturing_plants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `recipient_user_id` int(10) unsigned NOT NULL,
  `company_id` int(10) unsigned DEFAULT NULL,
  `notification_type` varchar(100) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(10) unsigned DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `status` enum('unread','read','archived') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`recipient_user_id`),
  KEY `idx_notif_read` (`is_read`),
  KEY `idx_notif_type` (`notification_type`),
  KEY `idx_notif_company` (`company_id`),
  KEY `idx_notif_entity` (`entity_type`,`entity_id`),
  CONSTRAINT `fk_notif_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_notif_recipient` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=403 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (402,1,NULL,NULL,'New Industry Registered','Company \'Absolute Motion Private Limited\' has registered and is pending verification.','/views/super_admin/companies.php',NULL,NULL,0,NULL,'unread','2026-11-12 04:54:04');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `offer_letters`
--

DROP TABLE IF EXISTS `offer_letters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offer_letters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `selection_id` int(10) unsigned NOT NULL,
  `letter_reference_no` varchar(100) NOT NULL,
  `job_designation` varchar(255) DEFAULT NULL,
  `salary_offered` decimal(10,2) DEFAULT NULL,
  `salary_type` varchar(50) DEFAULT 'Monthly',
  `benefits_summary` text DEFAULT NULL,
  `job_location` varchar(255) DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `issue_date` date NOT NULL,
  `validity_date` date NOT NULL,
  `proposed_joining_date` date DEFAULT NULL,
  `status` enum('Draft','Pending Approval','Approved','Released','Accepted','Declined','Expired','Withdrawn') DEFAULT 'Draft',
  `offer_remarks` text DEFAULT NULL,
  `candidate_response_notes` text DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `selection_id` (`selection_id`),
  UNIQUE KEY `letter_reference_no` (`letter_reference_no`),
  KEY `idx_ol_status` (`status`),
  KEY `fk_ol_approved_by` (`approved_by`),
  CONSTRAINT `fk_ol_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ol_selection` FOREIGN KEY (`selection_id`) REFERENCES `selections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offer_letters`
--

LOCK TABLES `offer_letters` WRITE;
/*!40000 ALTER TABLE `offer_letters` DISABLE KEYS */;
/*!40000 ALTER TABLE `offer_letters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pr_email` (`email`),
  KEY `idx_pr_token` (`token_hash`),
  KEY `idx_pr_expires` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_permissions_module` (`module`),
  KEY `idx_permissions_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'company.create','companies','Register new company','2026-11-05 05:23:44'),(2,'company.read','companies','View company details','2026-11-05 05:23:44'),(3,'company.update','companies','Update company profile','2026-11-05 05:23:44'),(4,'company.delete','companies','Delete or suspend company','2026-11-05 05:23:44'),(5,'company.verify','companies','Verify or reject company onboarding','2026-11-05 05:23:44'),(6,'user.create','users','Create new user account','2026-11-05 05:23:44'),(7,'user.read','users','View user list and details','2026-11-05 05:23:44'),(8,'user.update','users','Update user details','2026-11-05 05:23:44'),(9,'user.delete','users','Deactivate or delete user','2026-11-05 05:23:44'),(10,'requirement.create','requirements','Submit manpower requisition','2026-11-05 05:23:44'),(11,'requirement.read','requirements','View manpower requirements','2026-11-05 05:23:44'),(12,'requirement.update','requirements','Update manpower requirement','2026-11-05 05:23:44'),(13,'requirement.cancel','requirements','Cancel manpower requirement','2026-11-05 05:23:44'),(14,'requirement.approve','requirements','Approve or auto-approve requirement','2026-11-05 05:23:44'),(15,'requirement.reject','requirements','Reject manpower requirement','2026-11-05 05:23:44'),(16,'requirement.assign','requirements','Assign requirement to recruiters','2026-11-05 05:23:44'),(17,'candidate.match','matching','Execute candidate matching algorithm','2026-11-05 05:23:44'),(18,'candidate.screen','matching','Screen matched candidates','2026-11-05 05:23:44'),(19,'candidate.shortlist','matching','Shortlist candidates for interview','2026-11-05 05:23:44'),(20,'interview.schedule','interviews','Schedule candidate interview','2026-11-05 05:23:44'),(21,'interview.feedback','interviews','Submit interview evaluation feedback','2026-11-05 05:23:44'),(22,'selection.create','selections','Select candidate and define terms','2026-11-05 05:23:44'),(23,'offer.issue','selections','Issue offer letter','2026-11-05 05:23:44'),(24,'joining.verify','joinings','Verify candidate reporting and joining','2026-11-05 05:23:44'),(25,'reports.view','reports','View analytics dashboards and reports','2026-11-05 05:23:44'),(26,'reports.export','reports','Export analytical reports','2026-11-05 05:23:44'),(27,'audit.view','audit','Inspect immutable audit logs','2026-11-05 05:23:44'),(28,'settings.manage','settings','Configure system settings','2026-11-05 05:23:44');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recruitment_stage_history`
--

DROP TABLE IF EXISTS `recruitment_stage_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recruitment_stage_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `match_id` int(10) unsigned NOT NULL,
  `previous_stage` varchar(50) DEFAULT NULL,
  `new_stage` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `changed_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_rsh_user` (`changed_by`),
  KEY `idx_rsh_match` (`match_id`),
  KEY `idx_rsh_stage` (`new_stage`),
  KEY `idx_rsh_created` (`created_at`),
  CONSTRAINT `fk_rsh_match` FOREIGN KEY (`match_id`) REFERENCES `candidate_matches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rsh_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=246 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recruitment_stage_history`
--

LOCK TABLES `recruitment_stage_history` WRITE;
/*!40000 ALTER TABLE `recruitment_stage_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `recruitment_stage_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requirement_sequences`
--

DROP TABLE IF EXISTS `requirement_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requirement_sequences` (
  `year_month` varchar(6) NOT NULL,
  `last_sequence` int(10) unsigned NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`year_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requirement_sequences`
--

LOCK TABLES `requirement_sequences` WRITE;
/*!40000 ALTER TABLE `requirement_sequences` DISABLE KEYS */;
INSERT INTO `requirement_sequences` VALUES ('202611',174,'2026-11-06 10:47:50');
/*!40000 ALTER TABLE `requirement_sequences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requirement_skills`
--

DROP TABLE IF EXISTS `requirement_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requirement_skills` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requirement_id` int(10) unsigned NOT NULL,
  `skill_name` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rs_skill` (`skill_name`),
  KEY `idx_rs_req` (`requirement_id`),
  CONSTRAINT `fk_rs_requirement` FOREIGN KEY (`requirement_id`) REFERENCES `manpower_requirements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requirement_skills`
--

LOCK TABLES `requirement_skills` WRITE;
/*!40000 ALTER TABLE `requirement_skills` DISABLE KEYS */;
INSERT INTO `requirement_skills` VALUES (82,232,'Blueprint Reading'),(83,232,'Workshop Safety'),(84,232,'Hand Tool Maintenance'),(85,241,'Blueprint Reading'),(86,241,'Workshop Safety'),(87,241,'Hand Tool Maintenance');
/*!40000 ALTER TABLE `requirement_skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requirement_status_history`
--

DROP TABLE IF EXISTS `requirement_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requirement_status_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requirement_id` int(10) unsigned NOT NULL,
  `previous_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` int(10) unsigned DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_rsh_changed_by` (`changed_by`),
  KEY `idx_rsh_req` (`requirement_id`),
  CONSTRAINT `fk_rsh_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rsh_requirement` FOREIGN KEY (`requirement_id`) REFERENCES `manpower_requirements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=199 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requirement_status_history`
--

LOCK TABLES `requirement_status_history` WRITE;
/*!40000 ALTER TABLE `requirement_status_history` DISABLE KEYS */;
INSERT INTO `requirement_status_history` VALUES (139,232,NULL,'PENDING_REVIEW',251,'Requirement submitted and forwarded to Super Admin for allocation review.','2026-11-06 08:34:42'),(162,241,NULL,'PENDING_REVIEW',251,'Requirement submitted and forwarded to Super Admin for allocation review.','2026-11-06 09:04:32');
/*!40000 ALTER TABLE `requirement_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requirement_trades`
--

DROP TABLE IF EXISTS `requirement_trades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requirement_trades` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requirement_id` int(10) unsigned NOT NULL,
  `trade_name` varchar(150) NOT NULL,
  `vacancies` int(10) unsigned DEFAULT 1,
  `qualification` varchar(100) DEFAULT NULL,
  `board_type` varchar(50) DEFAULT 'Either',
  `passing_year_from` varchar(50) DEFAULT NULL,
  `passing_year_to` varchar(50) DEFAULT NULL,
  `required_skills` varchar(255) DEFAULT NULL,
  `min_experience` varchar(50) DEFAULT 'Fresher',
  `max_experience` varchar(50) DEFAULT NULL,
  `max_age` int(10) unsigned DEFAULT NULL,
  `gender` varchar(50) DEFAULT 'Any',
  `fresher_allowed` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_rt_trade` (`trade_name`),
  KEY `idx_rt_req` (`requirement_id`),
  CONSTRAINT `fk_rt_requirement` FOREIGN KEY (`requirement_id`) REFERENCES `manpower_requirements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=360 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requirement_trades`
--

LOCK TABLES `requirement_trades` WRITE;
/*!40000 ALTER TABLE `requirement_trades` DISABLE KEYS */;
INSERT INTO `requirement_trades` VALUES (286,232,'Fitter',5,'ITI','Either','2022','2026','Blue prijnt','Fresher','',NULL,'Any',0),(313,241,'Fitter',5,'ITI','Either','2022','2026','Blue prijnt','Fresher','',NULL,'Any',0);
/*!40000 ALTER TABLE `requirement_trades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requirement_validation_history`
--

DROP TABLE IF EXISTS `requirement_validation_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requirement_validation_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requirement_id` int(10) unsigned NOT NULL,
  `validation_run_id` varchar(64) NOT NULL,
  `rule_id` varchar(50) NOT NULL,
  `rule_name` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `severity` enum('ERROR','WARNING','INFO','MANUAL_REVIEW') NOT NULL,
  `result` enum('PASS','FAIL','WARNING','REVIEW_REQUIRED') NOT NULL,
  `field_name` varchar(100) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `details_json` text DEFAULT NULL,
  `validation_version` varchar(20) DEFAULT 'ALG-VAL-2026-V1.0',
  `triggered_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_val_hist_user` (`triggered_by`),
  KEY `idx_val_req` (`requirement_id`),
  KEY `idx_val_run` (`validation_run_id`),
  KEY `idx_val_rule` (`rule_id`),
  KEY `idx_val_severity` (`severity`),
  KEY `idx_val_created` (`created_at`),
  CONSTRAINT `fk_val_hist_req` FOREIGN KEY (`requirement_id`) REFERENCES `manpower_requirements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_val_hist_user` FOREIGN KEY (`triggered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1605 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requirement_validation_history`
--

LOCK TABLES `requirement_validation_history` WRITE;
/*!40000 ALTER TABLE `requirement_validation_history` DISABLE KEYS */;
INSERT INTO `requirement_validation_history` VALUES (1228,232,'VAL-20261106-093442-ce6d6ffa','BR-REG-001','Company Entity Existence','BASIC_INFO','INFO','PASS','company_id',NULL,'{\"suggested_correction\":null,\"current_value\":\"Absolute Motion Private Limited\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1229,232,'VAL-20261106-093442-ce6d6ffa','BR-REG-003','Company Operational Status','BASIC_INFO','INFO','PASS','verification_status',NULL,'{\"suggested_correction\":null,\"current_value\":\"Pending\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1230,232,'VAL-20261106-093442-ce6d6ffa','BR-VER-001','Commercial Verification Status','BASIC_INFO','MANUAL_REVIEW','REVIEW_REQUIRED','verification_status','Employer is Pending. Requisition requires Super Admin vetting prior to release.','{\"suggested_correction\":\"Submit enterprise accreditation documents for Super Admin verification.\",\"current_value\":\"Pending\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1231,232,'VAL-20261106-093442-ce6d6ffa','ERR-TIT-01','Requirement Title Length','BASIC_INFO','INFO','PASS','title',NULL,'{\"suggested_correction\":null,\"current_value\":\"Electrician\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1232,232,'VAL-20261106-093442-ce6d6ffa','ERR-DSC-01','Job Description Completeness','BASIC_INFO','INFO','PASS','job_description',NULL,'{\"suggested_correction\":null,\"current_value\":\"avghdskjldfgydhsufyghbsjhufdghvbsjhg...\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1233,232,'VAL-20261106-093442-ce6d6ffa','ERR-SEC-01','Industry Sector Specification','BASIC_INFO','INFO','PASS','industry_sector',NULL,'{\"suggested_correction\":null,\"current_value\":\"Industrial Automation & Robotics\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1234,232,'VAL-20261106-093442-ce6d6ffa','ERR-PRI-01','Hiring Priority Specification','BASIC_INFO','INFO','PASS','priority',NULL,'{\"suggested_correction\":null,\"current_value\":\"Medium\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1235,232,'VAL-20261106-093442-ce6d6ffa','ERR-QTY-01','Vacancy Headcount Bound','TRADES','INFO','PASS','quantity_required',NULL,'{\"suggested_correction\":null,\"current_value\":\"5\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1236,232,'VAL-20261106-093442-ce6d6ffa','ERR-TRD-02','Trade Specification Validity','TRADES','INFO','PASS','trades',NULL,'{\"suggested_correction\":null,\"current_value\":\"1 trade rows\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1237,232,'VAL-20261106-093442-ce6d6ffa','ERR-TRD-03','Trade Vacancy Sum Parity','TRADES','INFO','PASS','quantity_required',NULL,'{\"suggested_correction\":null,\"current_value\":\"Sum: 5 = Total: 5\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1238,232,'VAL-20261106-093442-ce6d6ffa','ERR-QLF-01','Qualification Level Validity','QUALIFICATION','INFO','PASS','qualification',NULL,'{\"suggested_correction\":null,\"current_value\":\"ITI Passed\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1239,232,'VAL-20261106-093442-ce6d6ffa','ERR-BRD-01','Board Certification Check','QUALIFICATION','INFO','PASS','board_type',NULL,'{\"suggested_correction\":null,\"current_value\":\"Either\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1240,232,'VAL-20261106-093442-ce6d6ffa','ERR-SAL-03','Salary Remuneration Type','SALARY','INFO','PASS','salary_type',NULL,'{\"suggested_correction\":null,\"current_value\":\"Monthly\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1241,232,'VAL-20261106-093442-ce6d6ffa','ERR-SAL-01','Minimum Salary Bound','SALARY','INFO','PASS','min_salary',NULL,'{\"suggested_correction\":null,\"current_value\":\"₹15,000\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1242,232,'VAL-20261106-093442-ce6d6ffa','ERR-SAL-02','Salary Inversion Check','SALARY','INFO','PASS','max_salary',NULL,'{\"suggested_correction\":null,\"current_value\":\"₹20,000\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1243,232,'VAL-20261106-093442-ce6d6ffa','ERR-DAT-01','Application Deadline Chronology','DATES','INFO','PASS','application_deadline',NULL,'{\"suggested_correction\":null,\"current_value\":\"2026-11-21\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1244,232,'VAL-20261106-093442-ce6d6ffa','ERR-DAT-02','Interview vs Deadline Chronology','DATES','INFO','PASS','interview_date',NULL,'{\"suggested_correction\":null,\"current_value\":\"2026-11-26\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1245,232,'VAL-20261106-093442-ce6d6ffa','ERR-DAT-03','Joining Milestone Chronology','DATES','INFO','PASS','target_joining_date',NULL,'{\"suggested_correction\":null,\"current_value\":\"2026-12-06\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1246,232,'VAL-20261106-093442-ce6d6ffa','ERR-LOC-03','Deployment Location Specification','LOCATION','INFO','PASS','job_location_state',NULL,'{\"suggested_correction\":null,\"current_value\":\"Navi Mumbai, Maharashtra\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1247,232,'VAL-20261106-093442-ce6d6ffa','ERR-AGE-01','Statutory Minimum Age Compliance','RECRUITMENT','INFO','PASS','min_age',NULL,'{\"suggested_correction\":null,\"current_value\":\"18\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:04:42'),(1332,241,'VAL-20261106-100432-d0da124f','BR-REG-001','Company Entity Existence','BASIC_INFO','INFO','PASS','company_id',NULL,'{\"suggested_correction\":null,\"current_value\":\"Absolute Motion Private Limited\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1333,241,'VAL-20261106-100432-d0da124f','BR-REG-003','Company Operational Status','BASIC_INFO','INFO','PASS','verification_status',NULL,'{\"suggested_correction\":null,\"current_value\":\"Pending\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1334,241,'VAL-20261106-100432-d0da124f','BR-VER-001','Commercial Verification Status','BASIC_INFO','MANUAL_REVIEW','REVIEW_REQUIRED','verification_status','Employer is Pending. Requisition requires Super Admin vetting prior to release.','{\"suggested_correction\":\"Submit enterprise accreditation documents for Super Admin verification.\",\"current_value\":\"Pending\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1335,241,'VAL-20261106-100432-d0da124f','ERR-TIT-01','Requirement Title Length','BASIC_INFO','INFO','PASS','title',NULL,'{\"suggested_correction\":null,\"current_value\":\"Electrician\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1336,241,'VAL-20261106-100432-d0da124f','ERR-DSC-01','Job Description Completeness','BASIC_INFO','INFO','PASS','job_description',NULL,'{\"suggested_correction\":null,\"current_value\":\"avghdskjldfgydhsufyghbsjhufdghvbsjhg...\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1337,241,'VAL-20261106-100432-d0da124f','ERR-SEC-01','Industry Sector Specification','BASIC_INFO','INFO','PASS','industry_sector',NULL,'{\"suggested_correction\":null,\"current_value\":\"Industrial Automation & Robotics\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1338,241,'VAL-20261106-100432-d0da124f','ERR-PRI-01','Hiring Priority Specification','BASIC_INFO','INFO','PASS','priority',NULL,'{\"suggested_correction\":null,\"current_value\":\"Medium\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1339,241,'VAL-20261106-100432-d0da124f','ERR-QTY-01','Vacancy Headcount Bound','TRADES','INFO','PASS','quantity_required',NULL,'{\"suggested_correction\":null,\"current_value\":\"5\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1340,241,'VAL-20261106-100432-d0da124f','ERR-TRD-02','Trade Specification Validity','TRADES','INFO','PASS','trades',NULL,'{\"suggested_correction\":null,\"current_value\":\"1 trade rows\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1341,241,'VAL-20261106-100432-d0da124f','ERR-TRD-03','Trade Vacancy Sum Parity','TRADES','INFO','PASS','quantity_required',NULL,'{\"suggested_correction\":null,\"current_value\":\"Sum: 5 = Total: 5\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1342,241,'VAL-20261106-100432-d0da124f','ERR-QLF-01','Qualification Level Validity','QUALIFICATION','INFO','PASS','qualification',NULL,'{\"suggested_correction\":null,\"current_value\":\"ITI Passed\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1343,241,'VAL-20261106-100432-d0da124f','ERR-BRD-01','Board Certification Check','QUALIFICATION','INFO','PASS','board_type',NULL,'{\"suggested_correction\":null,\"current_value\":\"Either\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1344,241,'VAL-20261106-100432-d0da124f','ERR-SAL-03','Salary Remuneration Type','SALARY','INFO','PASS','salary_type',NULL,'{\"suggested_correction\":null,\"current_value\":\"Monthly\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1345,241,'VAL-20261106-100432-d0da124f','ERR-SAL-01','Minimum Salary Bound','SALARY','INFO','PASS','min_salary',NULL,'{\"suggested_correction\":null,\"current_value\":\"₹15,000\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1346,241,'VAL-20261106-100432-d0da124f','ERR-SAL-02','Salary Inversion Check','SALARY','INFO','PASS','max_salary',NULL,'{\"suggested_correction\":null,\"current_value\":\"₹20,000\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1347,241,'VAL-20261106-100432-d0da124f','ERR-DAT-01','Application Deadline Chronology','DATES','INFO','PASS','application_deadline',NULL,'{\"suggested_correction\":null,\"current_value\":\"2026-11-21\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1348,241,'VAL-20261106-100432-d0da124f','ERR-DAT-02','Interview vs Deadline Chronology','DATES','INFO','PASS','interview_date',NULL,'{\"suggested_correction\":null,\"current_value\":\"2026-11-26\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1349,241,'VAL-20261106-100432-d0da124f','ERR-DAT-03','Joining Milestone Chronology','DATES','INFO','PASS','target_joining_date',NULL,'{\"suggested_correction\":null,\"current_value\":\"2026-12-06\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1350,241,'VAL-20261106-100432-d0da124f','ERR-LOC-03','Deployment Location Specification','LOCATION','INFO','PASS','job_location_state',NULL,'{\"suggested_correction\":null,\"current_value\":\"Navi Mumbai, Maharashtra\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32'),(1351,241,'VAL-20261106-100432-d0da124f','ERR-AGE-01','Statutory Minimum Age Compliance','RECRUITMENT','INFO','PASS','min_age',NULL,'{\"suggested_correction\":null,\"current_value\":\"18\",\"trigger_type\":\"SUBMISSION\",\"validation_status\":\"VALIDATED\"}','ALG-VAL-2026-V1.0',251,'2026-11-06 04:34:32');
/*!40000 ALTER TABLE `requirement_validation_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `role_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `fk_rp_permission` (`permission_id`),
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),(1,11),(1,12),(1,13),(1,14),(1,15),(1,16),(1,17),(1,18),(1,19),(1,20),(1,21),(1,22),(1,23),(1,24),(1,25),(1,26),(1,27),(1,28),(2,2),(2,3),(2,10),(2,11),(2,12),(2,13),(2,21),(2,24),(2,25),(3,2),(3,6),(3,7),(3,8),(3,11),(3,14),(3,15),(3,16),(3,17),(3,18),(3,19),(3,20),(3,21),(3,22),(3,23),(3,24),(3,25),(3,26),(4,11),(4,17),(4,18),(4,19),(4,20),(4,21),(4,22),(4,23),(4,24),(4,25);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_roles_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'super_admin','Super Administrator','Unrestricted governance, company verification, user and system management.','2026-11-05 05:23:44','2026-11-05 05:23:44'),(2,'industry_admin','Industry Partner Admin','Industrial employer managing company profile and manpower requisitions.','2026-11-05 05:23:44','2026-11-05 05:23:44'),(3,'recruitment_admin','Recruitment Operations Lead','Operational lead managing talent matching and recruiter work orders.','2026-11-05 05:23:44','2026-11-05 05:23:44'),(4,'recruiter','Recruiter / HR Specialist','Tactical recruiter conducting candidate screening, interviews, and joining verification.','2026-11-05 05:23:44','2026-11-05 05:23:44'),(5,'industry_user','Industry Team Member','Company team member assisting with plant hiring and candidate review','2026-11-05 06:23:07','2026-11-05 06:23:07');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schema_migrations`
--

DROP TABLE IF EXISTS `schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schema_migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(100) NOT NULL,
  `migration_file` varchar(255) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `version` (`version`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schema_migrations`
--

LOCK TABLES `schema_migrations` WRITE;
/*!40000 ALTER TABLE `schema_migrations` DISABLE KEYS */;
INSERT INTO `schema_migrations` VALUES (1,'001','001_create_schema_migrations_and_roles_permissions.sql','2026-11-05 05:22:22'),(2,'002','002_create_companies_and_users.sql','2026-11-05 05:22:37'),(3,'003','003_create_manpower_requirements_and_subtables.sql','2026-11-05 05:22:37'),(4,'004','004_create_candidate_matches_and_pipeline.sql','2026-11-05 05:22:37'),(5,'005','005_create_notifications_and_audit_logs.sql','2026-11-05 05:22:37'),(6,'006','006_create_password_resets.sql','2026-11-05 05:30:13'),(7,'007','007_update_company_statuses.sql','2026-11-05 05:58:37'),(8,'008','008_create_industry_portal_tables.sql','2026-11-05 06:23:07'),(9,'009','009_extend_requirement_wizard_fields.sql','2026-11-05 06:40:07'),(10,'010','010_create_validation_history_and_notification_enhancements.sql','2026-11-05 06:56:00'),(11,'011','011_candidate_matching_engine.sql','2026-11-05 07:15:35'),(12,'012','012_recruitment_pipeline_enhancements.sql','2026-11-05 07:29:04');
/*!40000 ALTER TABLE `schema_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `selections`
--

DROP TABLE IF EXISTS `selections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `selections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `match_id` int(10) unsigned NOT NULL,
  `offered_designation` varchar(255) NOT NULL,
  `plant_id` int(10) unsigned DEFAULT NULL,
  `offered_ctc` decimal(10,2) NOT NULL,
  `offer_date` date NOT NULL,
  `target_joining_date` date NOT NULL,
  `selection_status` enum('Selected','Rejected','On Hold','Offer Released','Offer Accepted','Offer Declined') DEFAULT 'Selected',
  `decision_remarks` text DEFAULT NULL,
  `selected_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `match_id` (`match_id`),
  KEY `fk_sel_selected_by` (`selected_by`),
  KEY `idx_sel_status` (`selection_status`),
  KEY `fk_sel_plant` (`plant_id`),
  CONSTRAINT `fk_sel_match` FOREIGN KEY (`match_id`) REFERENCES `candidate_matches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sel_plant` FOREIGN KEY (`plant_id`) REFERENCES `manufacturing_plants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sel_selected_by` FOREIGN KEY (`selected_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `selections`
--

LOCK TABLES `selections` WRITE;
/*!40000 ALTER TABLE `selections` DISABLE KEYS */;
/*!40000 ALTER TABLE `selections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_settings_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=223 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'org_name','PRAYAS – The Automation Academy','Official organization name',1,'2026-11-05 05:23:44'),(2,'portal_title','Industry Manpower Requirement & Recruitment Portal','Portal title for display',1,'2026-11-05 05:23:44'),(3,'min_candidate_age','18','Statutory minimum age for factory deployment',1,'2026-11-05 05:23:44'),(4,'auto_approval_max_quantity','50','Maximum headcount for auto-approval without manual review',0,'2026-11-05 05:23:44'),(5,'session_timeout_minutes','30','Session inactivity timeout in minutes',0,'2026-11-05 11:05:26'),(6,'matching_threshold_tier1','85.0','Score cutoff for Tier 1 Top Match',0,'2026-11-05 05:23:44'),(7,'matching_threshold_tier2','70.0','Score cutoff for Tier 2 Strong Match',0,'2026-11-05 05:23:44'),(8,'matching_threshold_min','50.0','Minimum score required to display candidate match',0,'2026-11-05 05:23:44'),(9,'buffer_selection_percentage','20','Buffer percentage allowed over total quantity required',0,'2026-11-05 05:23:44'),(25,'smtp_host','localhost','SMTP Server Hostname',0,'2026-11-05 06:56:00'),(26,'smtp_port','1025','SMTP Server Port (e.g. 1025, 587, 465)',0,'2026-11-05 06:56:00'),(27,'smtp_user','','SMTP Username / Account',0,'2026-11-05 06:56:00'),(28,'smtp_pass','','SMTP Password / API Key',0,'2026-11-05 06:56:00'),(29,'smtp_secure','tls','SMTP Encryption (tls, ssl, none)',0,'2026-11-05 06:56:00'),(30,'mail_from_address','noreply@prayas-academy.org','Sender Email Address for Notifications',1,'2026-11-05 06:56:00'),(31,'mail_from_name','PRAYAS – The Automation Academy','Sender Display Name for Notifications',1,'2026-11-05 06:56:00'),(32,'mail_enabled','0','Toggle email dispatch (0=In-App Only/Simulated, 1=Live SMTP)',0,'2026-11-05 06:56:00'),(57,'company_limits_139','{\"max_users\":2,\"max_requirements\":2,\"max_plants\":1,\"max_vacancies\":50}','Allocated feature limits for company #139 (Gov Test Corp 1793875676)',0,'2026-11-05 10:47:56'),(67,'company_limits_158','{\"max_users\":2,\"max_requirements\":2,\"max_plants\":1,\"max_vacancies\":50}','Allocated feature limits for company #158 (Gov Test Corp 1793876658)',0,'2026-11-05 11:04:18'),(69,'plant_meta_54','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 12:05:16\"}','Extended operational metadata for manufacturing plant #54',0,'2026-11-05 11:05:16'),(70,'plant_meta_55','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 12:05:16\"}','Extended operational metadata for manufacturing plant #55',0,'2026-11-05 11:05:16'),(73,'plant_meta_56','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 12:12:47\"}','Extended operational metadata for manufacturing plant #56',0,'2026-11-05 11:12:47'),(85,'plant_meta_59','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 12:13:23\"}','Extended operational metadata for manufacturing plant #59',0,'2026-11-05 11:13:23'),(86,'plant_meta_60','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 12:13:23\"}','Extended operational metadata for manufacturing plant #60',0,'2026-11-05 11:13:23'),(105,'plant_meta_66','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 12:32:08\"}','Extended operational metadata for manufacturing plant #66',0,'2026-11-05 11:32:08'),(106,'plant_meta_67','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 12:32:59\"}','Extended operational metadata for manufacturing plant #67',0,'2026-11-05 11:32:59'),(107,'plant_meta_68','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 12:32:59\"}','Extended operational metadata for manufacturing plant #68',0,'2026-11-05 11:32:59'),(118,'plant_meta_71','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 12:33:12\"}','Extended operational metadata for manufacturing plant #71',0,'2026-11-05 11:33:12'),(119,'plant_meta_72','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 12:33:12\"}','Extended operational metadata for manufacturing plant #72',0,'2026-11-05 11:33:12'),(121,'plant_meta_73','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 12:33:27\"}','Extended operational metadata for manufacturing plant #73',0,'2026-11-05 11:33:27'),(122,'plant_meta_74','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 12:33:27\"}','Extended operational metadata for manufacturing plant #74',0,'2026-11-05 11:33:27'),(132,'plant_meta_77','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 12:33:29\"}','Extended operational metadata for manufacturing plant #77',0,'2026-11-05 11:33:29'),(133,'plant_meta_78','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 12:33:29\"}','Extended operational metadata for manufacturing plant #78',0,'2026-11-05 11:33:29'),(134,'plant_meta_79','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 12:37:12\"}','Extended operational metadata for manufacturing plant #79',0,'2026-11-05 11:37:12'),(135,'plant_meta_80','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 12:37:12\"}','Extended operational metadata for manufacturing plant #80',0,'2026-11-05 11:37:12'),(137,'plant_meta_87','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 13:03:59\"}','Extended operational metadata for manufacturing plant #87',0,'2026-11-05 12:03:59'),(138,'plant_meta_88','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 13:04:00\"}','Extended operational metadata for manufacturing plant #88',0,'2026-11-05 12:04:00'),(148,'plant_meta_91','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 13:04:03\"}','Extended operational metadata for manufacturing plant #91',0,'2026-11-05 12:04:03'),(149,'plant_meta_92','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 13:04:03\"}','Extended operational metadata for manufacturing plant #92',0,'2026-11-05 12:04:03'),(151,'plant_meta_109','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 13:35:11\"}','Extended operational metadata for manufacturing plant #109',0,'2026-11-05 12:35:11'),(152,'plant_meta_110','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 13:35:11\"}','Extended operational metadata for manufacturing plant #110',0,'2026-11-05 12:35:11'),(162,'plant_meta_113','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 13:35:43\"}','Extended operational metadata for manufacturing plant #113',0,'2026-11-05 12:35:43'),(163,'plant_meta_114','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-05 13:35:43\"}','Extended operational metadata for manufacturing plant #114',0,'2026-11-05 12:35:43'),(164,'plant_meta_125','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 06:55:36\"}','Extended operational metadata for manufacturing plant #125',0,'2026-11-06 05:55:36'),(165,'plant_meta_126','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 06:55:36\"}','Extended operational metadata for manufacturing plant #126',0,'2026-11-06 05:55:36'),(167,'plant_meta_127','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 08:28:56\"}','Extended operational metadata for manufacturing plant #127',0,'2026-11-06 07:28:56'),(168,'plant_meta_128','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 08:28:56\"}','Extended operational metadata for manufacturing plant #128',0,'2026-11-06 07:28:56'),(169,'plant_meta_129','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 08:28:57\"}','Extended operational metadata for manufacturing plant #129',0,'2026-11-06 07:28:57'),(170,'plant_meta_130','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 08:28:57\"}','Extended operational metadata for manufacturing plant #130',0,'2026-11-06 07:28:57'),(180,'company_limits_267','{\"max_users\":5,\"max_requirements\":5,\"max_plants\":2,\"max_vacancies\":100}','Allocated feature limits for company #267 (P12 Integration Automotive Ltd 80bfd5)',0,'2026-11-06 08:00:50'),(181,'company_limits_268','{\"max_users\":5,\"max_requirements\":5,\"max_plants\":2,\"max_vacancies\":100}','Allocated feature limits for company #268 (P12 Integration Automotive Ltd aaac07)',0,'2026-11-06 08:06:01'),(182,'plant_meta_137','{\"taluka\":\"Khed\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"plant_chk@testauto.com\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"Assembly, Quality, Maintenance\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 09:06:01\"}','Extended operational metadata for manufacturing plant #137',0,'2026-11-06 08:06:01'),(183,'company_limits_270','{\"max_users\":5,\"max_requirements\":5,\"max_plants\":2,\"max_vacancies\":100}','Allocated feature limits for company #270 (P12 Integration Automotive Ltd 50d883)',0,'2026-11-06 08:08:52'),(184,'plant_meta_138','{\"taluka\":\"Khed\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"plant_chk@testauto.com\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"Assembly, Quality, Maintenance\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 09:08:52\"}','Extended operational metadata for manufacturing plant #138',0,'2026-11-06 08:08:52'),(186,'plant_meta_139','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 09:09:15\"}','Extended operational metadata for manufacturing plant #139',0,'2026-11-06 08:09:15'),(187,'plant_meta_140','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 09:09:16\"}','Extended operational metadata for manufacturing plant #140',0,'2026-11-06 08:09:16'),(197,'plant_meta_143','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 09:09:29\"}','Extended operational metadata for manufacturing plant #143',0,'2026-11-06 08:09:29'),(198,'plant_meta_144','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 09:09:29\"}','Extended operational metadata for manufacturing plant #144',0,'2026-11-06 08:09:29'),(199,'plant_meta_149','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 09:41:13\"}','Extended operational metadata for manufacturing plant #149',0,'2026-11-06 08:41:13'),(200,'plant_meta_150','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 09:41:13\"}','Extended operational metadata for manufacturing plant #150',0,'2026-11-06 08:41:13'),(201,'company_limits_288','{\"max_users\":5,\"max_requirements\":5,\"max_plants\":2,\"max_vacancies\":100}','Allocated feature limits for company #288 (P12 Integration Automotive Ltd 991043)',0,'2026-11-06 08:41:15'),(202,'plant_meta_151','{\"taluka\":\"Khed\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"plant_chk@testauto.com\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"Assembly, Quality, Maintenance\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 09:41:15\"}','Extended operational metadata for manufacturing plant #151',0,'2026-11-06 08:41:15'),(203,'plant_meta_152','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 10:02:31\"}','Extended operational metadata for manufacturing plant #152',0,'2026-11-06 09:02:31'),(204,'plant_meta_153','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 10:02:31\"}','Extended operational metadata for manufacturing plant #153',0,'2026-11-06 09:02:31'),(205,'company_limits_292','{\"max_users\":5,\"max_requirements\":5,\"max_plants\":2,\"max_vacancies\":100}','Allocated feature limits for company #292 (P12 Integration Automotive Ltd 904fcf)',0,'2026-11-06 09:02:41'),(206,'plant_meta_154','{\"taluka\":\"Khed\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"plant_chk@testauto.com\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"Assembly, Quality, Maintenance\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 10:02:41\"}','Extended operational metadata for manufacturing plant #154',0,'2026-11-06 09:02:41'),(207,'plant_meta_155','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 10:08:55\"}','Extended operational metadata for manufacturing plant #155',0,'2026-11-06 09:08:55'),(208,'plant_meta_156','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 10:08:55\"}','Extended operational metadata for manufacturing plant #156',0,'2026-11-06 09:08:55'),(209,'plant_meta_159','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 10:44:31\"}','Extended operational metadata for manufacturing plant #159',0,'2026-11-06 09:44:31'),(210,'plant_meta_160','{\"taluka\":\"\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 10:44:31\"}','Extended operational metadata for manufacturing plant #160',0,'2026-11-06 09:44:31'),(211,'company_limits_301','{\"max_users\":5,\"max_requirements\":5,\"max_plants\":2,\"max_vacancies\":100}','Allocated feature limits for company #301 (P12 Integration Automotive Ltd 292309)',0,'2026-11-06 10:09:13'),(212,'plant_meta_161','{\"taluka\":\"Khed\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"plant_chk@testauto.com\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"Assembly, Quality, Maintenance\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 11:09:13\"}','Extended operational metadata for manufacturing plant #161',0,'2026-11-06 10:09:13'),(213,'company_limits_303','{\"max_users\":5,\"max_requirements\":5,\"max_plants\":2,\"max_vacancies\":100}','Allocated feature limits for company #303 (P12 Integration Automotive Ltd b4b39a)',0,'2026-11-06 10:16:39'),(214,'plant_meta_162','{\"taluka\":\"Khed\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"plant_chk@testauto.com\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"Assembly, Quality, Maintenance\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 11:16:42\"}','Extended operational metadata for manufacturing plant #162',0,'2026-11-06 10:16:42'),(215,'company_limits_305','{\"max_users\":5,\"max_requirements\":5,\"max_plants\":2,\"max_vacancies\":100}','Allocated feature limits for company #305 (P12 Integration Automotive Ltd 9e5e0d)',0,'2026-11-06 10:20:08'),(216,'plant_meta_163','{\"taluka\":\"Khed\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"plant_chk@testauto.com\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"Assembly, Quality, Maintenance\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 11:20:08\"}','Extended operational metadata for manufacturing plant #163',0,'2026-11-06 10:20:08'),(217,'company_limits_307','{\"max_users\":5,\"max_requirements\":5,\"max_plants\":2,\"max_vacancies\":100}','Allocated feature limits for company #307 (P12 Integration Automotive Ltd 20e5c6)',0,'2026-11-06 10:25:48'),(218,'plant_meta_164','{\"taluka\":\"Khed\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"plant_chk@testauto.com\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"Assembly, Quality, Maintenance\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 11:25:48\"}','Extended operational metadata for manufacturing plant #164',0,'2026-11-06 10:25:48'),(219,'company_limits_309','{\"max_users\":5,\"max_requirements\":5,\"max_plants\":2,\"max_vacancies\":100}','Allocated feature limits for company #309 (P12 Integration Automotive Ltd f36916)',0,'2026-11-06 10:35:06'),(220,'plant_meta_165','{\"taluka\":\"Khed\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"plant_chk@testauto.com\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"Assembly, Quality, Maintenance\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 11:35:06\"}','Extended operational metadata for manufacturing plant #165',0,'2026-11-06 10:35:06'),(221,'company_limits_311','{\"max_users\":5,\"max_requirements\":5,\"max_plants\":2,\"max_vacancies\":100}','Allocated feature limits for company #311 (P12 Integration Automotive Ltd 1a4ce8)',0,'2026-11-06 10:47:50'),(222,'plant_meta_166','{\"taluka\":\"Khed\",\"country\":\"India\",\"google_maps_url\":\"\",\"contact_designation\":\"\",\"contact_email\":\"plant_chk@testauto.com\",\"alternate_contact_number\":\"\",\"manufacturing_activity\":\"\",\"production_category\":\"\",\"departments\":\"Assembly, Quality, Maintenance\",\"shifts_count\":3,\"working_days_per_week\":6,\"workforce_size\":\"\",\"establishment_year\":\"\",\"description\":\"\",\"assigned_admin_user_id\":null,\"updated_at\":\"2026-11-06 11:47:50\"}','Extended operational metadata for manufacturing plant #166',0,'2026-11-06 10:47:50');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(10) unsigned NOT NULL,
  `company_id` int(10) unsigned DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `must_change_password` tinyint(1) DEFAULT 0,
  `status` enum('Active','Inactive','Suspended') DEFAULT 'Active',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_email` (`email`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_company` (`company_id`),
  KEY `idx_users_status` (`status`),
  CONSTRAINT `fk_users_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=265 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,NULL,'PRAYAS Master Administrator','admin@prayas-academy.internal','9999999999','$2y$12$2MZ1iDwluo044QdtGS6qv.2boErBq2mEO7txrW42gx5btnzm0dmMu',0,'Active','2026-11-12 10:15:10','2026-11-05 05:23:44','2026-11-12 04:45:10'),(251,2,285,'naved','hr@absolutemotion.in','7039229649','$2y$12$oGpxle7v1iFVKJwSeN89ie999fy3wOt066nBjSJ3tAI.Xo6HC17hu',0,'Active','2026-11-12 10:20:39','2026-11-06 08:32:05','2026-11-12 04:50:39'),(264,2,314,'naved','admin@absolute.in','7039229649','$2y$12$q4X2CS6YbMFW2Nr3mD0q.OMAOM.QGcJJm/mLjsI/HnxlOQjQqdtqa',0,'Active','2026-11-12 10:24:08','2026-11-12 04:54:04','2026-11-12 04:54:08');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-12 11:17:09
