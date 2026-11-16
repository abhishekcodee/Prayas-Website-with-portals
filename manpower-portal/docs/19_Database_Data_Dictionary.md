# 19 — Comprehensive Database Data Dictionary

## Document Identifier: DIC-PRA-2026-V1.0
**Project Title:** Industry Manpower Requirement & ITI Candidate Recruitment Portal  
**Organization:** PRAYAS – The Automation Academy  
**Database Name:** `manpower_portal_db`  
**Character Set:** `utf8mb4`  
**Collation:** `utf8mb4_unicode_ci`  
**Total Tables Documented:** 20  
**Date:** 2026-11-05  

---

## 1. Table Index

| # | Table Name | Category | Purpose | Primary Key | Foreign Keys |
| :---: | :--- | :--- | :--- | :---: | :---: |
| 1 | `schema_migrations` | Governance | Tracks applied versioned SQL migration scripts | `id` | None |
| 2 | `roles` | RBAC | Defines system roles (super_admin, industry_admin, etc.) | `id` | None |
| 3 | `permissions` | RBAC | Defines atomic system capabilities | `id` | None |
| 4 | `role_permissions` | RBAC | Maps permissions to roles (Many-to-Many) | `(role_id, permission_id)` | `role_id`, `permission_id` |
| 5 | `system_settings` | Configuration | System-wide thresholds, cutoffs, and constants | `id` | None |
| 6 | `companies` | Multi-Tenant | Industrial employer profiles and plant locations | `id` | `verified_by` |
| 7 | `users` | Identity | User credentials, roles, and company affiliations | `id` | `role_id`, `company_id` |
| 8 | `manpower_requirements`| Requisitions | Core manpower demand requisitions | `id` | `company_id`, `approved_by`, `created_by` |
| 9 | `requirement_trades` | Requisitions | ITI trades requested per requisition | `id` | `requirement_id` |
| 10 | `requirement_skills` | Requisitions | Technical skill tags required | `id` | `requirement_id` |
| 11 | `requirement_status_history`| Audit | Audit trail of requisition state transitions | `id` | `requirement_id`, `changed_by` |
| 12 | `candidate_matches` | Recruitment | Matched ITI candidates with compatibility scores | `id` | `requirement_id` |
| 13 | `candidate_screening` | Recruitment | Recruiter screening observations and outcomes | `id` | `match_id`, `screener_id` |
| 14 | `interviews` | Recruitment | Scheduled technical/plant interview rounds | `id` | `match_id`, `scheduled_by` |
| 15 | `interview_feedback` | Recruitment | Multi-point evaluation rubrics and recommendation | `id` | `interview_id`, `submitted_by` |
| 16 | `selections` | Recruitment | Selected candidates and compensation terms | `id` | `match_id`, `selected_by` |
| 17 | `offer_letters` | Recruitment | Formal offer documents and references | `id` | `selection_id` |
| 18 | `joinings` | Fulfillment | Confirmed on-site plant reporting records | `id` | `selection_id`, `verified_by` |
| 19 | `notifications` | Engagement | In-app alerts for users across pipeline milestones | `id` | `recipient_user_id` |
| 20 | `audit_logs` | Governance | Immutable activity and security audit ledger | `id` | `user_id`, `company_id` |

---

## 2. Table-by-Table Schema Specification

### 2.1 Table: `schema_migrations`
* **Purpose:** Audit ledger recording versioned SQL migrations.
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `version`: VARCHAR(100), NOT NULL, UNIQUE. Migration sequential prefix (e.g. '001', '002').
  - `migration_file`: VARCHAR(255), NOT NULL. Full SQL script filename.
  - `applied_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP. Timestamp applied.

---

### 2.2 Table: `roles`
* **Purpose:** Defines the 4 canonical user roles in the PRAYAS ecosystem.
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `name`: VARCHAR(50), NOT NULL, UNIQUE. System slug (`super_admin`, `industry_admin`, `recruitment_admin`, `recruiter`).
  - `label`: VARCHAR(100), NOT NULL. Human-readable role label.
  - `description`: TEXT, NULLABLE. Detailed explanation of role scope.
  - `created_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP.
  - `updated_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP.
* **Indexes:** `idx_roles_name` (`name`).

---

### 2.3 Table: `permissions`
* **Purpose:** Defines atomic operational capabilities.
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `name`: VARCHAR(100), NOT NULL, UNIQUE. Permission slug (`requirement.approve`, `company.verify`).
  - `module`: VARCHAR(50), NOT NULL. Grouping module (`companies`, `requirements`, etc.).
  - `description`: VARCHAR(255), NULLABLE. Capability description.
  - `created_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP.
* **Indexes:** `idx_permissions_module` (`module`), `idx_permissions_name` (`name`).

---

### 2.4 Table: `role_permissions`
* **Purpose:** Many-to-Many mapping associating capabilities to system roles.
* **Columns:**
  - `role_id`: INT UNSIGNED, NOT NULL, PRIMARY KEY (composite). Foreign Key $\to$ `roles.id` ON DELETE CASCADE.
  - `permission_id`: INT UNSIGNED, NOT NULL, PRIMARY KEY (composite). Foreign Key $\to$ `permissions.id` ON DELETE CASCADE.

---

### 2.5 Table: `system_settings`
* **Purpose:** Global platform configuration, compliance cutoffs, and scoring thresholds.
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `setting_key`: VARCHAR(100), NOT NULL, UNIQUE.
  - `setting_value`: TEXT, NULLABLE.
  - `description`: VARCHAR(255), NULLABLE.
  - `is_public`: TINYINT(1), NOT NULL, DEFAULT 0. Whether readable without administrative login.
  - `updated_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP.
* **Indexes:** `idx_settings_key` (`setting_key`).

---

### 2.6 Table: `companies`
* **Purpose:** Profile, plant location, and statutory details of industrial employer partners.
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `company_name`: VARCHAR(255), NOT NULL. Registered corporate or factory entity name.
  - `registration_number`: VARCHAR(100), NULLABLE, UNIQUE. CIN / LLPIN / GSTIN.
  - `industry_type`: VARCHAR(100), NOT NULL. Automotive, Automation, Heavy Machinery, Electronics, etc.
  - `address`: TEXT, NOT NULL. Complete postal address of manufacturing plant or office.
  - `state`: VARCHAR(100), NOT NULL. Geographic state (e.g. Maharashtra).
  - `district`: VARCHAR(100), NOT NULL. Industrial district (e.g. Pune, Nashik, Aurangabad).
  - `city`: VARCHAR(100), NOT NULL. City / Industrial MIDC area.
  - `pincode`: VARCHAR(20), NOT NULL. 6-digit postal code.
  - `hr_contact_name`: VARCHAR(255), NOT NULL. Primary HR contact person.
  - `hr_contact_phone`: VARCHAR(50), NOT NULL. 10-digit mobile number.
  - `hr_contact_email`: VARCHAR(255), NOT NULL, UNIQUE. Official email.
  - `verification_status`: ENUM('Pending', 'Verified', 'Rejected', 'Suspended'), DEFAULT 'Pending'.
  - `verified_by`: INT UNSIGNED, NULLABLE. Foreign Key $\to$ `users.id` ON DELETE SET NULL.
  - `verified_at`: DATETIME, NULLABLE. Timestamp of verification.
  - `rejection_reason`: TEXT, NULLABLE. Mandatory reason if verification was rejected.
  - `created_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP.
  - `updated_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP.
* **Indexes:** `idx_companies_status`, `idx_companies_district`, `idx_companies_state`.

---

### 2.7 Table: `users`
* **Purpose:** Authentication credentials, RBAC assignment, and tenant affiliation.
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `role_id`: INT UNSIGNED, NOT NULL. Foreign Key $\to$ `roles.id` ON DELETE RESTRICT.
  - `company_id`: INT UNSIGNED, NULLABLE. Foreign Key $\to$ `companies.id` ON DELETE SET NULL (Populated for Industry Admins; NULL for PRAYAS staff).
  - `full_name`: VARCHAR(255), NOT NULL. Full name of user.
  - `email`: VARCHAR(255), NOT NULL, UNIQUE. Login email.
  - `phone`: VARCHAR(50), NULLABLE.
  - `password_hash`: VARCHAR(255), NOT NULL. BCRYPT hash (cost 12).
  - `must_change_password`: TINYINT(1), NOT NULL, DEFAULT 0. Enforces password change on next login.
  - `status`: ENUM('Active', 'Inactive', 'Suspended'), DEFAULT 'Active'.
  - `last_login_at`: DATETIME, NULLABLE. Timestamp of most recent login.
  - `created_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP.
  - `updated_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP.
* **Indexes:** `idx_users_email`, `idx_users_role`, `idx_users_company`, `idx_users_status`.

---

### 2.8 Table: `manpower_requirements`
* **Purpose:** Core manpower demand requisitions submitted by industry partners.
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `requirement_id`: VARCHAR(50), NOT NULL, UNIQUE. Format: `REQ-YYYYMM-XXXX`.
  - `company_id`: INT UNSIGNED, NOT NULL. Foreign Key $\to$ `companies.id` ON DELETE RESTRICT.
  - `title`: VARCHAR(255), NOT NULL. Requisition headline.
  - `quantity_required`: INT UNSIGNED, NOT NULL. Vacancies requested ($Q \ge 1$).
  - `qualification`: VARCHAR(100), NOT NULL. e.g. 'ITI Passed', 'Diploma', 'Apprentice'.
  - `board_type`: ENUM('NCVT', 'SCVT', 'Either'), DEFAULT 'Either'.
  - `passing_year_from`: VARCHAR(50), NULLABLE. e.g. '2023'.
  - `passing_year_to`: VARCHAR(50), NULLABLE. e.g. '2025'.
  - `experience_required`: VARCHAR(100), NOT NULL. 'Freshers', '1-2 Years', '3+ Years'.
  - `job_location_state`: VARCHAR(100), NOT NULL.
  - `job_location_district`: VARCHAR(100), NOT NULL.
  - `job_location_city`: VARCHAR(100), NOT NULL.
  - `job_description`: TEXT, NOT NULL.
  - `min_age`: INT UNSIGNED, NOT NULL, DEFAULT 18.
  - `max_age`: INT UNSIGNED, NULLABLE.
  - `gender_preference`: ENUM('Any', 'Male', 'Female'), DEFAULT 'Any'.
  - `min_salary`: DECIMAL(10,2), NOT NULL.
  - `max_salary`: DECIMAL(10,2), NOT NULL.
  - `salary_type`: ENUM('Monthly', 'Stipend', 'Annual'), DEFAULT 'Monthly'.
  - `employment_type`: ENUM('Full Time', 'Apprenticeship', 'Fixed Term', 'OJT'), DEFAULT 'Full Time'.
  - `working_hours`: VARCHAR(50), DEFAULT '8 Hours'.
  - `shift_details`: VARCHAR(100), DEFAULT 'General Shift'.
  - `accommodation_provided`: ENUM('Yes', 'No', 'Subsidized'), DEFAULT 'No'.
  - `food_facility`: ENUM('Yes', 'No', 'Subsidized'), DEFAULT 'No'.
  - `transportation_provided`: ENUM('Yes', 'No', 'Subsidized'), DEFAULT 'No'.
  - `pf_esic_benefit`: ENUM('Yes', 'No'), DEFAULT 'Yes'.
  - `submission_date`: DATE, NOT NULL.
  - `application_deadline`: DATE, NOT NULL.
  - `interview_date`: DATE, NOT NULL.
  - `target_joining_date`: DATE, NOT NULL.
  - `priority`: ENUM('Low', 'Medium', 'High', 'Urgent'), DEFAULT 'Medium'.
  - `status`: ENUM('DRAFT', 'SUBMITTED', 'UNDER_VALIDATION', 'NEEDS_CORRECTION', 'PENDING_REVIEW', 'APPROVED', 'RECRUITMENT_IN_PROGRESS', 'PARTIALLY_FULFILLED', 'FULFILLED', 'CLOSED', 'CANCELLED', 'REJECTED'), DEFAULT 'DRAFT'.
  - `approved_by`: INT UNSIGNED, NULLABLE. Foreign Key $\to$ `users.id` ON DELETE SET NULL.
  - `approved_at`: DATETIME, NULLABLE.
  - `created_by`: INT UNSIGNED, NOT NULL. Foreign Key $\to$ `users.id` ON DELETE RESTRICT.
  - `created_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP.
  - `updated_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP.
* **Indexes:** `idx_req_company`, `idx_req_status`, `idx_req_district`, `idx_req_priority`, `idx_req_dates`.

---

### 2.9 Table: `requirement_trades`
* **Purpose:** Maps specific ITI trades requested per requisition.
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `requirement_id`: INT UNSIGNED, NOT NULL. Foreign Key $\to$ `manpower_requirements.id` ON DELETE CASCADE.
  - `trade_name`: VARCHAR(150), NOT NULL. e.g. 'Electrician', 'Fitter', 'Wireman'.
  - `vacancies`: INT UNSIGNED, DEFAULT 1. Headcount for this specific trade.
* **Indexes:** `idx_rt_trade`, `idx_rt_req`.

---

### 2.10 Table: `requirement_skills`
* **Purpose:** Technical skill tags demanded by employer (e.g. PLC, CNC, Metrology).
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `requirement_id`: INT UNSIGNED, NOT NULL. Foreign Key $\to$ `manpower_requirements.id` ON DELETE CASCADE.
  - `skill_name`: VARCHAR(150), NOT NULL.
* **Indexes:** `idx_rs_skill`, `idx_rs_req`.

---

### 2.11 Table: `requirement_status_history`
* **Purpose:** Immutable audit trail recording state transitions across the requirement lifecycle.
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `requirement_id`: INT UNSIGNED, NOT NULL. Foreign Key $\to$ `manpower_requirements.id` ON DELETE CASCADE.
  - `previous_status`: VARCHAR(50), NULLABLE.
  - `new_status`: VARCHAR(50), NOT NULL.
  - `changed_by`: INT UNSIGNED, NULLABLE. Foreign Key $\to$ `users.id` ON DELETE SET NULL.
  - `remarks`: TEXT, NULLABLE.
  - `created_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP.
* **Indexes:** `idx_rsh_req`.

---

### 2.12 Table: `candidate_matches`
* **Purpose:** Records candidates sourced from external ITI pools along with compatibility scores.
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `requirement_id`: INT UNSIGNED, NOT NULL. Foreign Key $\to$ `manpower_requirements.id` ON DELETE CASCADE.
  - `candidate_source`: ENUM('prayas_dashboard', 'itistudents'), NOT NULL.
  - `candidate_source_id`: INT UNSIGNED, NOT NULL. Primary key in source table.
  - `candidate_name`: VARCHAR(255), NOT NULL.
  - `trade`: VARCHAR(150), NOT NULL.
  - `passing_year`: VARCHAR(50), NULLABLE.
  - `match_score`: DECIMAL(5,2), NOT NULL. Score from 0.00 to 100.00.
  - `match_details_json`: TEXT, NULLABLE. Breakdown of constituent parameter scores.
  - `status`: ENUM('Matched', 'Shortlisted', 'Screened', 'Scheduled', 'Selected', 'Joined', 'Rejected', 'On Hold'), DEFAULT 'Matched'.
  - `rejection_reason`: VARCHAR(255), NULLABLE.
  - `matched_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP.
* **Unique Key:** `uk_req_candidate` (`requirement_id`, `candidate_source`, `candidate_source_id`).
* **Indexes:** `idx_cm_status`, `idx_cm_score`, `idx_cm_trade`.

---

### 2.13 Table: `candidate_screening`
* **Purpose:** Telephonic/technical screening notes captured by Recruiters.
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `match_id`: INT UNSIGNED, NOT NULL. Foreign Key $\to$ `candidate_matches.id` ON DELETE CASCADE.
  - `screener_id`: INT UNSIGNED, NOT NULL. Foreign Key $\to$ `users.id` ON DELETE RESTRICT.
  - `screening_outcome`: ENUM('Passed', 'On Hold', 'Rejected'), NOT NULL.
  - `screening_notes`: TEXT, NULLABLE.
  - `screened_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP.
* **Indexes:** `idx_cs_outcome`.

---

### 2.14 Table: `interviews`
* **Purpose:** Schedules for plant walk-in or virtual interview rounds.
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `match_id`: INT UNSIGNED, NOT NULL. Foreign Key $\to$ `candidate_matches.id` ON DELETE CASCADE.
  - `interview_datetime`: DATETIME, NOT NULL.
  - `venue_or_link`: TEXT, NOT NULL. Plant address or video link.
  - `interview_round`: VARCHAR(100), DEFAULT 'Round 1 - Technical'.
  - `interviewer_name`: VARCHAR(255), NULLABLE.
  - `status`: ENUM('Scheduled', 'Completed', 'Cancelled', 'Rescheduled', 'No Show'), DEFAULT 'Scheduled'.
  - `scheduled_by`: INT UNSIGNED, NOT NULL. Foreign Key $\to$ `users.id` ON DELETE RESTRICT.
  - `created_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP.
* **Indexes:** `idx_int_datetime`, `idx_int_status`.

---

### 2.15 Table: `interview_feedback`
* **Purpose:** Structured evaluation rubrics (technical, practical, communication, punctuality).
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `interview_id`: INT UNSIGNED, NOT NULL. Foreign Key $\to$ `interviews.id` ON DELETE CASCADE.
  - `technical_rating`: TINYINT UNSIGNED, DEFAULT 3.
  - `practical_rating`: TINYINT UNSIGNED, DEFAULT 3.
  - `communication_rating`: TINYINT UNSIGNED, DEFAULT 3.
  - `punctuality_rating`: TINYINT UNSIGNED, DEFAULT 3.
  - `recommendation`: ENUM('Selected', 'Rejected', 'On Hold'), NOT NULL.
  - `feedback_comments`: TEXT, NULLABLE.
  - `submitted_by`: INT UNSIGNED, NOT NULL. Foreign Key $\to$ `users.id` ON DELETE RESTRICT.
  - `submitted_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP.
* **Indexes:** `idx_fb_recommendation`.

---

### 2.16 Table: `selections`
* **Purpose:** Candidate selection records with offered compensation terms.
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `match_id`: INT UNSIGNED, NOT NULL, UNIQUE. Foreign Key $\to$ `candidate_matches.id` ON DELETE CASCADE.
  - `offered_designation`: VARCHAR(255), NOT NULL. e.g. 'Junior Technician'.
  - `offered_ctc`: DECIMAL(10,2), NOT NULL.
  - `offer_date`: DATE, NOT NULL.
  - `target_joining_date`: DATE, NOT NULL.
  - `selection_status`: ENUM('Selected', 'Offer Accepted', 'Offer Declined'), DEFAULT 'Selected'.
  - `selected_by`: INT UNSIGNED, NOT NULL. Foreign Key $\to$ `users.id` ON DELETE RESTRICT.
  - `created_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP.
* **Indexes:** `idx_sel_status`.

---

### 2.17 Table: `offer_letters`
* **Purpose:** Formal offer documentation and validity tracking.
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `selection_id`: INT UNSIGNED, NOT NULL, UNIQUE. Foreign Key $\to$ `selections.id` ON DELETE CASCADE.
  - `letter_reference_no`: VARCHAR(100), NOT NULL, UNIQUE.
  - `document_path`: VARCHAR(255), NULLABLE. Relative path to PDF.
  - `issue_date`: DATE, NOT NULL.
  - `validity_date`: DATE, NOT NULL.
  - `status`: ENUM('Issued', 'Signed', 'Expired', 'Revoked'), DEFAULT 'Issued'.
  - `created_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP.
* **Indexes:** `idx_ol_status`.

---

### 2.18 Table: `joinings`
* **Purpose:** Verified on-site plant reporting and mathematical fulfillment accounting.
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `selection_id`: INT UNSIGNED, NOT NULL, UNIQUE. Foreign Key $\to$ `selections.id` ON DELETE CASCADE.
  - `actual_joining_date`: DATE, NOT NULL. Confirmed reporting date.
  - `plant_reporting_unit`: VARCHAR(255), NULLABLE. Specific factory division / shop floor.
  - `joining_status`: ENUM('Joined', 'No Show', 'Withdrawn'), DEFAULT 'Joined'.
  - `onboarding_remarks`: TEXT, NULLABLE.
  - `verified_by`: INT UNSIGNED, NOT NULL. Foreign Key $\to$ `users.id` ON DELETE RESTRICT.
  - `created_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP.
* **Indexes:** `idx_joi_status`, `idx_joi_date`.

---

### 2.19 Table: `notifications`
* **Purpose:** In-app user notifications for pipeline milestones and approvals.
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `recipient_user_id`: INT UNSIGNED, NOT NULL. Foreign Key $\to$ `users.id` ON DELETE CASCADE.
  - `title`: VARCHAR(255), NOT NULL.
  - `message`: TEXT, NOT NULL.
  - `action_url`: VARCHAR(255), NULLABLE.
  - `is_read`: TINYINT(1), NOT NULL, DEFAULT 0.
  - `created_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP.
* **Indexes:** `idx_notif_user`, `idx_notif_read`.

---

### 2.20 Table: `audit_logs`
* **Purpose:** Immutable legal and administrative activity ledger.
* **Columns:**
  - `id`: INT UNSIGNED, NOT NULL, AUTO_INCREMENT, PRIMARY KEY.
  - `user_id`: INT UNSIGNED, NULLABLE. Foreign Key $\to$ `users.id` ON DELETE SET NULL.
  - `company_id`: INT UNSIGNED, NULLABLE. Foreign Key $\to$ `companies.id` ON DELETE SET NULL.
  - `action`: VARCHAR(100), NOT NULL. e.g. `REQ_AUTO_APPROVED`.
  - `ip_address`: VARCHAR(50), NULLABLE.
  - `user_agent`: VARCHAR(255), NULLABLE.
  - `details_json`: TEXT, NULLABLE. Before/after state snapshot.
  - `created_at`: TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP.
* **Indexes:** `idx_audit_action`, `idx_audit_user`, `idx_audit_company`, `idx_audit_created`.

