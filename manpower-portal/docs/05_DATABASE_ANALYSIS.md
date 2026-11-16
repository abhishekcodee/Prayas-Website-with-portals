# 05 — Database Inspection & Architectural Analysis

## Organization: PRAYAS – The Automation Academy
**Document Reference:** DOC-PRA-05-V1  
**Inspection Date:** 2026-11-05  

---

## 1. Existing MySQL Environment Inspection

An exhaustive database inspection was performed on the active MySQL/MariaDB server (`localhost:3306`). The server hosts multiple existing databases, which were thoroughly analyzed to identify existing candidate assets and isolation boundaries.

### 1.1 Complete Database Inventory
1. `itistudents` — ITI candidate repository with verified technical credentials.
2. `prayas_iti_dashboard` — PRAYAS ITI student and institute repository.
3. `prayas_portal` — PRAYAS aptitude examination, testing, and user profile database.
4. `student_portal` — Student profile and project repository.
5. `hrms_db` — Enterprise human resource management tables.
6. System databases: `mysql`, `information_schema`, `performance_schema`, `phpmyadmin`, `test`.

---

## 2. Deep Schema Analysis of External Candidate Pools

### 2.1 Database: `itistudents`
* **Table:** `candidates`
* **Total Records:** 56
* **Primary Key:** `id` (int(10) unsigned auto_increment)
* **Key Columns:**
  - `candidate_serial_no` (varchar(100), unique)
  - `candidate_registration_no` (varchar(150), indexed)
  - `full_name` (varchar(255), indexed)
  - `date_of_birth` (varchar(100))
  - `mobile_number` (varchar(50), indexed)
  - `email_id` (varchar(255))
  - `iti_trade` (varchar(150), indexed) — Values e.g. "Electrician", "Wireman", "Fitter"
  - `iti_college_name` (varchar(255), indexed)
  - `year_of_passing` (varchar(50), indexed) — Values e.g. "2024", "2025"
  - `experience` (varchar(100))
  - `preferred_job_location` (varchar(255)) — Values e.g. "Mumbai", "Pune", "Nashik"
  - `can_relocate` (varchar(50))
  - `expected_salary` (varchar(100))
  - `verification_status` (varchar(50), default 'VERIFIED')
  - `status` (varchar(50), default 'Active')
  - `gender` (varchar(20), default 'Male')
* **Assessment:** High data quality; ideal for precision matching on trade, passing year, and relocation willingness.

### 2.2 Database: `prayas_iti_dashboard`
* **Table:** `students`
* **Total Records:** 1,292
* **Primary Key:** `id` (int(10) unsigned auto_increment)
* **Key Columns:**
  - `name` (varchar(255), not null, indexed)
  - `trade` (varchar(150), indexed) — Broad range of technical trades
  - `passing_year` (varchar(50), indexed) — e.g. "2016-2018", "2022-2024", "2025"
  - `district` (varchar(100), indexed) — Geographic district in Maharashtra/India
  - `gender` (varchar(20), indexed) — "Male", "Female"
  - `mobile` (varchar(50)), `email` (varchar(255))
  - `experience` (varchar(100)), `preferred_location` (varchar(255))
  - `expected_salary` (varchar(100))
  - `status` (varchar(50), default 'Active')
* **Associated Tables:** `institute_trades`, `institute_contacts`, `upload_history`.
* **Assessment:** Substantial pool of 1,292 candidates across diverse engineering trades.

---

## 3. Dedicated Portal Database: `manpower_portal_db`

To strictly adhere to non-destructive principles:
1. **No Modifications to External Databases:** No tables in `itistudents`, `prayas_iti_dashboard`, or `prayas_portal` will ever be altered, dropped, or modified.
2. **Dedicated Target Database:** A new database `manpower_portal_db` will be created via versioned migrations in Phase 3.
3. **Cross-Database Abstraction:**
   The portal's `CandidateModel.php` will issue safe `SELECT` queries across database boundaries:
   ```sql
   SELECT id, name AS candidate_name, trade, passing_year, district, gender, mobile, email, status
   FROM prayas_iti_dashboard.students
   WHERE status = 'Active' ...
   ```
4. **Reference-Based Matching:**
   The portal table `candidate_matches` will store:
   - `candidate_source`: `'prayas_dashboard'` or `'itistudents'`
   - `candidate_source_id`: The primary key in the external table
   - `match_score`: The computed compatibility percentage
   This ensures zero data duplication while tracking candidate recruitment stages independently.

