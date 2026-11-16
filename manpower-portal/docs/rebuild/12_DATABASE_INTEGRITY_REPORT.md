# PART 12 — DATABASE INTEGRITY & IMMUTABILITY REPORT
## PRAYAS Industry Manpower Requirement Portal
**Organization:** PRAYAS – The Automation Academy  
**Document Code:** PRAYAS-DOC-P12-004  
**Audit Date:** 2026-11-06  
**Status:** 100% Intact & Verified  

---

## 1. Core Operational Schema Inventory (`manpower_portal_db`)

The platform's primary relational database schema consists of **30 verified operational tables**, designed with relational foreign keys, cascade safety, and query indexes:

| # | Table Name | Storage Engine | Purpose | Record Count Baseline |
|---|---|---|---|---|
| 1 | `users` | InnoDB | User authentication, password hashes, role assignment | Verified |
| 2 | `companies` | InnoDB | Industry companies, CIN, verification statuses | Verified |
| 3 | `company_limits` | InnoDB | Max plants, requirements, and vacancies quotas | Verified |
| 4 | `company_verifications` | InnoDB | Verification notes, license documents, audit logs | Verified |
| 5 | `manufacturing_plants` | InnoDB | Plant locations, shifts, operational departments | Verified |
| 6 | `industries` | InnoDB | Industry classification taxonomy and metadata | Verified |
| 7 | `manpower_requirements` | InnoDB | Core requisition entity with atomic REQ-YYYYMM-XXXX | Verified |
| 8 | `requirement_trades` | InnoDB | Trade-wise vacancy distribution (Fitter, etc.) | Verified |
| 9 | `requirement_skills` | InnoDB | Required and optional technical skills | Verified |
| 10 | `requirement_status_history` | InnoDB | Requisition lifecycle audit trail | Verified |
| 11 | `requirement_validation_history` | InnoDB | Validation engine run logs and defect telemetry | Verified |
| 12 | `candidate_matches` | InnoDB | 100-point algorithmic match results & 7 subscores | Verified |
| 13 | `candidate_matching_runs` | InnoDB | Batch matching execution logs and candidate tallies | Verified |
| 14 | `candidate_screening` | InnoDB | Pre-interview screening outcomes & notes | Verified |
| 15 | `interviews` | InnoDB | Scheduled interview rounds, venues, dates | Verified |
| 16 | `interview_feedback` | InnoDB | Evaluator scoring (technical, practical, safety) | Verified |
| 17 | `selections` | InnoDB | Final selection decisions, offered designation, CTC | Verified |
| 18 | `offer_letters` | InnoDB | Formal offer letters with reference numbers | Verified |
| 19 | `joinings` | InnoDB | Ground physical joining and reporting confirmations | Verified |
| 20 | `recruitment_stage_history` | InnoDB | 16-stage pipeline transition audit ledger | Verified |
| 21 | `notifications` | InnoDB | User in-app notifications and alerts | Verified |
| 22 | `email_logs` | InnoDB | Outgoing email transmission logs | Verified |
| 23 | `audit_logs` | InnoDB | Security and administrative audit trail | Verified |
| 24 | `deadline_reminder_logs` | InnoDB | Cron deadline milestone alert tracking | Verified |
| 25 | `system_settings` | InnoDB | Extended operational configuration & metadata | Verified |
| 26 | `password_resets` | InnoDB | Secure single-use password reset tokens | Verified |
| 27 | `login_attempts` | InnoDB | Rate limiting and brute force protection counters | Verified |
| 28 | `user_roles` | InnoDB | RBAC role definitions | Verified |
| 29 | `role_permissions` | InnoDB | Role-to-permission capability matrix | Verified |
| 30 | `portal_migrations` | InnoDB | Schema migration tracking ledger | Verified |

---

## 2. External Candidate Database Safety Audit

A foundational project rule mandates that candidate data in external repositories must remain completely **read-only and immutable**.

### Audit Verification:

| Database | Table | Baseline Row Count | Post-Audit Row Count | Delta | Status |
|---|---|---|---|---|---|
| `prayas_iti_dashboard` | `students` | **1,292** | **1,292** | **0** | **100% UNTOUCHED** |
| `itistudents` | `candidates` | **56** | **56** | **0** | **100% UNTOUCHED** |

**Zero mutation operations** (`INSERT`, `UPDATE`, `DELETE`, `DROP`, `ALTER`, `TRUNCATE`) were performed on these databases. All candidate matching and pipeline operations write exclusively to `manpower_portal_db`.

---

## 3. Relational Foreign Key Integrity

Foreign keys enforce referential integrity across the operational schema:
- `manufacturing_plants.company_id` $\to$ `companies.id` (`ON DELETE CASCADE`)
- `manpower_requirements.company_id` $\to$ `companies.id` (`ON DELETE RESTRICT`)
- `manpower_requirements.plant_id` $\to$ `manufacturing_plants.id` (`ON DELETE RESTRICT`)
- `candidate_matches.requirement_id` $\to$ `manpower_requirements.id` (`ON DELETE CASCADE`)
- `selections.match_id` $\to$ `candidate_matches.id` (`ON DELETE CASCADE`)
- `offer_letters.selection_id` $\to$ `selections.id` (`ON DELETE CASCADE`)
- `joinings.selection_id` $\to$ `selections.id` (`ON DELETE CASCADE`)

All tables use the `InnoDB` storage engine with `utf8mb4_unicode_ci` character encoding.
