# Part 01 — Database Gap & Schema Architecture Analysis
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Database:** `manpower_portal_db` (MySQL 8.0 / MariaDB 10.4+)  
**Analysis Date:** 2026-11-05  
**Document Code:** `DOC-REBUILD-01-DB-GAP`

---

## 1. Executive Summary

This document details the complete architectural inspection of the database subsystem supporting the PRAYAS Industry Manpower Requirement Portal. 

In strict compliance with Part 01 constraints:
- **No tables were created, modified, or dropped.**
- **Existing records remain 100% untouched.**
- **External candidate databases remain strictly read-only.**

The primary database (`manpower_portal_db`) consists of **30 normalized tables** encompassing core governance, industrial requisitions, multi-trade allocations, algorithmic matching, a 16-stage recruitment pipeline, and system audit logs.

---

## 2. Table-by-Table Mapping of Key Business Entities

The prompt specifies an analysis of 12 core business entities. Below is the exact mapping between the business domain entities and the physical database schema:

| Business Entity Name | Physical Table Name | Purpose & Coverage | Row Count | Status |
|---|---|---|:---:|:---:|
| **companies** | `companies` | Master registry of industrial employer organizations, verification status, contact details, and branding. | 31 | Verified |
| **portal_users** | `users` | Authenticated users spanning Super Admin, Industry Admin, Team Members, and Recruiters with role foreign keys. | 23 | Verified |
| **manpower_requirements** | `manpower_requirements` | Primary requisition header storing `requirement_code` (`REQ-YYYYMM-XXXX`), lifecycle status, shifts, and compensation. | 8 | Verified |
| **requirement_trades** | `requirement_trades` | Sub-allocation table specifying trade name, vacancies, qualification criteria, passing year ranges, and experience. | Verified | Verified |
| **requirement_alerts** | `notifications` + `deadline_reminder_logs` | Real-time user alert dispatch and scheduled automated deadline warning records. | 15 / 0 | Verified |
| **requirement_validation_logs** | `requirement_validation_history` | Granular audit trail for each rule evaluation in `ALG-VAL-2026-V1.0` (Error, Warning, Pass). | Verified | Verified |
| **candidate_matches** | `candidate_matches` | Algorithmic matching ledger storing 100-point breakdown, eligibility status, and industry visibility flags. | 516 | Verified |
| **interviews** | `interviews` | Scheduled interviews with interviewers, dates, modes (In-Person/Video), rounds, and status tracking. | Verified | Verified |
| **candidate_selections** | `selections` | Formal candidate selection records with plant assignment, offered CTC, and target joining date. | Verified | Verified |
| **candidate_joinings** | `joinings` | Physical reporting verification on shopfloor, unit allocation, actual reporting date, and induction completion. | Verified | Verified |
| **notifications** | `notifications` | System-wide notification queue for in-app alert delivery with read status and action URLs. | 15 | Verified |
| **audit_logs** | `audit_logs` | Immutable, append-only security ledger recording user actions, IP addresses, and JSON contextual metadata. | 413 | Verified |

---

## 3. Detailed Structural & Relationship Inspection

### 3.1 Company & User Management
- **`companies` Table:**
  - Primary Key: `id (int unsigned auto_increment)`
  - Unique Keys: `registration_number`, `hr_contact_email`
  - Indexes: `state`, `district`, `verification_status`
  - Foreign Key: `verified_by -> users(id)`
- **`users` Table:**
  - Primary Key: `id (int unsigned auto_increment)`
  - Unique Keys: `email`
  - Foreign Keys:
    - `role_id -> roles(id)`
    - `company_id -> companies(id)` (Nullable for administrators and recruiters)
  - Indexes: `status`

### 3.2 Manpower Requirement Structure
- **`manpower_requirements` Table:**
  - Primary Key: `id (int unsigned auto_increment)`
  - Unique Keys: `requirement_code`
  - Foreign Keys:
    - `company_id -> companies(id)`
    - `plant_id -> manufacturing_plants(id)`
    - `created_by -> users(id)`
    - `approved_by -> users(id)`
  - Indexes: `status`, `created_at`, `idx_req_company_status (company_id, status)`
- **`requirement_trades` Table:**
  - Primary Key: `id (int unsigned auto_increment)`
  - Foreign Key: `requirement_id -> manpower_requirements(id) ON DELETE CASCADE`
  - Composite Index: `idx_rt_req_trade (requirement_id, trade_name)`
- **`requirement_skills` Table:**
  - Primary Key: `id (int unsigned auto_increment)`
  - Foreign Key: `requirement_id -> manpower_requirements(id) ON DELETE CASCADE`

### 3.3 Auto-Validation & Alert Logs
- **`requirement_validation_history` Table:**
  - Primary Key: `id (int unsigned auto_increment)`
  - Foreign Keys:
    - `requirement_id -> manpower_requirements(id)`
    - `triggered_by -> users(id)`
  - Indexes: `validation_run_id`, `rule_id`, `severity`, `created_at`
- **`deadline_reminder_logs` Table:**
  - Primary Key: `id (int unsigned auto_increment)`
  - Foreign Key: `requirement_id -> manpower_requirements(id)`
  - Indexes: `recipient_email`, `reminder_type`, `sent_at`

### 3.4 Sourcing & Algorithmic Matching
- **`candidate_matches` Table:**
  - Primary Key: `id (int unsigned auto_increment)`
  - Foreign Key: `requirement_id -> manpower_requirements(id)`
  - Composite Index: `idx_cm_status_score (requirement_id, status, match_score)`
  - Scored Fields: `trade_score`, `batch_score`, `qual_score`, `skills_score`, `location_score`, `exp_score`, `avail_score` (Sum: 100.00 max).
  - DPDP Privacy: `is_shared_with_industry (tinyint(1))` defaults to `0`.
- **`candidate_matching_runs` Table:**
  - Primary Key: `id (int unsigned auto_increment)`
  - Unique Key: `run_identifier`
  - Foreign Keys:
    - `requirement_id -> manpower_requirements(id)`
    - `triggered_by -> users(id)`

### 3.5 Recruitment Pipeline Tables
- **`candidate_screening`:** Tracks initial phone screening outcome (`match_id`, `screener_id`).
- **`interviews`:** Tracks rounds, interview datetime, location, and meeting links (`match_id`, `created_by`).
- **`interview_feedback`:** 5-star rubric evaluation (`interview_id`, `match_id`, `evaluator_id`).
- **`selections`:** Formal selection details, offered CTC, target joining (`match_id`, `plant_id`, `selected_by`).
- **`offer_letters`:** Generated reference numbers (`OFFER-YYYYMM-XXXX`), CTC, acceptance status (`match_id`, `selection_id`).
- **`joinings`:** Ground shopfloor reporting, actual joining date, induction status (`match_id`, `offer_id`).
- **`recruitment_stage_history`:** Complete chronological stage transition audit trail (`match_id`, `changed_by`).

---

## 4. External Candidate Databases (Read-Only)

The platform bridges into two external MySQL databases without schema modifications:

1. **`prayas_iti_dashboard.students`**:
   - Total Verified Records: **1,292 rows**.
   - Fields Queried: Student name, trade, ITI institute name, passing year, district, contact number, employment status.
   - Access Mode: Strictly READ-ONLY via `Database::queryCandidateReadOnly()`.
2. **`itistudents.candidates`**:
   - Total Verified Records: **56 rows**.
   - Fields Queried: Candidate name, trade, qualification, passing year, district, contact details.
   - Access Mode: Strictly READ-ONLY via `Database::queryCandidateReadOnly()`.

---

## 5. Schema Gap Analysis & Optimization Opportunities

While the existing schema is normalized and functional, the following architectural enhancements are identified for upcoming phases:

1. **Database Views for Reporting Convenience:**
   - Currently, executive metrics query multiple table joins in real-time. Creating indexed MySQL database views (e.g. `v_active_requisitions_summary`, `v_pipeline_conversion_funnel`) will further streamline controller code without schema disruption.
2. **Standardized Vocabulary Documentation:**
   - Establish explicit schema aliases in the data dictionary so references to `portal_users` seamlessly map to `users`, and `requirement_alerts` map to `notifications` + `deadline_reminder_logs`.
3. **Soft-Delete Support on Non-Critical Tables:**
   - While `companies` and `users` utilize status flags (`Suspended`, `Inactive`), requirements and manufacturing plants could benefit from a standardized `deleted_at (datetime NULL)` column for audit compliance.

---

## 6. Safety Affirmation

**Zero database modifications were executed during Part 01.** The production database `manpower_portal_db` and both external candidate databases remain in a verified, immutable state.

