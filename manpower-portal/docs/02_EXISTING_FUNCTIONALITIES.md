# 02 — Existing Functionalities & Environmental Assets

## Organization: PRAYAS – The Automation Academy
**Document Reference:** DOC-PRA-02-V1  
**Inspection Date:** 2026-11-05  

---

## 1. Verified Existing Functional Assets in Workspace (`C:\Manpower Portal`)

### 1.1 Automated Backup & Disaster Recovery Mechanism
- **Asset:** `database/run_backup.bat`
- **Functionality:** Executes timestamped `mysqldump` backups of active databases (`itistudents`, `prayas_iti_dashboard`, and future `manpower_portal_db`) to `database/backups/`.
- **Status:** Verified working; executed initial snapshot successfully.

### 1.2 Official Visual Branding Assets
- **Assets:** `assets/images/logo.png` (Official PRAYAS Academy emblem), `assets/images/favicon.png`.
- **Functionality:** Provides brand consistency across all upcoming authentication and dashboard views.
- **Status:** Integrated from verified production source.

### 1.3 Documentation Framework & Standards
- **Assets:** Complete set of initial architecture documents and `CHANGELOG.md`.
- **Functionality:** Tracks system versioning, non-destructive safety constraints, and mathematical business rules.
- **Status:** Active baseline.

---

## 2. Verified External Candidate Repositories (Read-Only)

Inspection of the local MySQL instance confirmed the presence of live ITI candidate records belonging to the PRAYAS ecosystem:

### 2.1 `itistudents.candidates` (56 Records)
* **Verified Fields:** `id`, `candidate_serial_no`, `candidate_registration_no`, `full_name`, `date_of_birth`, `mobile_number`, `email_id`, `address`, `iti_trade`, `iti_college_name`, `year_of_passing`, `tenth_percentage`, `iti_percentage_grade`, `experience`, `previous_company`, `preferred_job_location`, `can_relocate`, `expected_salary`, `computer_knowledge`, `languages_known`, `driving_licence`, `verification_status`, `gender`.
* **Nature:** Verified, detailed technical student records.

### 2.2 `prayas_iti_dashboard.students` (1,292 Records)
* **Verified Fields:** `id`, `name`, `trade`, `passing_year`, `district`, `hsc`, `ssc`, `gender`, `institute_id`, `iti`, `mobile`, `email`, `address`, `dob`, `experience`, `previous_company`, `preferred_location`, `expected_salary`, `computer_knowledge`, `status`, `job_role`, `category`.
* **Associated Tables:** `institute_trades`, `institute_contacts`, `upload_history`.
* **Nature:** Bulk repository of candidates spanning diverse ITI trades (Electrician, Wireman, Fitter, Turner, Welder, Machinist, COPA, etc.).

---

## 3. Functionalities Identified as NOT YET Implemented in Portal

The following core modules are not yet written and will be systematically developed in subsequent parts:

1. **Industry Registration & Verification Flow:** No company registration UI or verification logic exists in `C:\Manpower Portal`.
2. **Authentication & Session RBAC:** No login scripts, session validators, or role middleware exist yet.
3. **Manpower Requirement Submission Engine:** No requirement creation form or persistence logic exists yet.
4. **Server-Side Automatic Validation:** No business validation rules engine is yet implemented in code.
5. **Candidate Matching Engine:** The algorithm matching requirements to ITI candidates has not yet been codified.
6. **Recruitment Pipeline Management:** Screening, interview scheduling, feedback capture, selections, offer letters, and joining verification modules are not yet written.
7. **Reports & Executive Analytics:** No reporting views or metrics aggregation queries exist yet.

