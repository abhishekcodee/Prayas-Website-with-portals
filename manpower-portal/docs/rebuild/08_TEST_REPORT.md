# Part 08 — Test Execution Report: Validation & Alert Engine

**Project:** PRAYAS – The Automation Academy | Industry Manpower Requirement Portal  
**Document Code:** `PRY-REBUILD-08-TEST-REPORT`  
**Test Suite:** `tests/test_auto_validation_alert_part08_suite.php`  
**Execution Timestamp:** 2026-11-05 13:03:35  
**Result:** 48 / 48 Tests Passed (100% Success Rate)  

---

## 1. Test Execution Breakdown

### Section 1: Rule Validation by Category (21 Tests)
- `Test 1` [PASS] Missing title triggers `ERR_TITLE_REQUIRED`
- `Test 2` [PASS] Short title (< 5 chars) triggers `ERR-TIT-01` length defect
- `Test 3` [PASS] Brief job description (< 20 chars) triggers `ERR-DSC-01` defect
- `Test 4` [PASS] Zero quantity required triggers `ERR-QTY-01` bound error
- `Test 5` [PASS] Negative quantity triggers fatal validation defect
- `Test 6` [PASS] Trade vacancies sum != total quantity triggers `ERR-TRD-03` sum parity error
- `Test 7` [PASS] Duplicate trade lines trigger `WARN-TRD-01` duplicate warning
- `Test 8` [PASS] Minimum age < 18 triggers statutory `ERR-AGE-01` compliance error
- `Test 9` [PASS] Inverted age range (max < min) triggers `ERR-AGE-02` error
- `Test 10` [PASS] Inverted passing year (from > to) triggers `ERR-YEA-03` range error
- `Test 11` [PASS] Salary inversion (max < min) triggers `ERR-SAL-02` error
- `Test 12` [PASS] Salary < 12,000 INR triggers `WARN-SAL-01` benchmark advisory warning
- `Test 13` [PASS] Application deadline in the past triggers `ERR-DAT-01` chronology error
- `Test 14` [PASS] Interview date before deadline triggers `ERR-DAT-02` chronology error
- `Test 15` [PASS] Joining date before interview triggers `ERR-DAT-03` milestone error
- `Test 16` [PASS] Rush turnaround (< 7 days) triggers `WARN-DAT-01` velocity advisory
- `Test 17` [PASS] Unspecified city/state without plant triggers `ERR-LOC-03` warning
- `Test 18` [PASS] Unrecognized employment type triggers `ERR-EMP-01` advisory
- `Test 19` [PASS] Plant belonging to Company B used by Company A triggers `ERR-LOC-01` ownership error
- `Test 20` [PASS] Unrecognized priority specification triggers `ERR-PRI-01` advisory
- `Test 21` [PASS] Malformed contact email triggers `ERR-CEM-01` format advisory

### Section 2: Result Classification & Decision Logic (5 Tests)
- `Test 22` [PASS] Returns `VALIDATION_FAILED` and `is_valid=false` when fatal defects present
- `Test 23` [PASS] Returns `VALIDATION_WARNING` and `is_valid=true` when only advisory warnings present
- `Test 24` [PASS] Returns `VALIDATED` and `is_valid=true` when all rules pass cleanly
- `Test 25` [PASS] Every rule result contains `suggested_correction` and `current_value`
- `Test 26` [PASS] Business Governance: `VALIDATED` does NOT automatically set status to `APPROVED`

### Section 3: Validation History Persistence & Revalidation (6 Tests)
- `Test 27` [PASS] Successful submission logs structured telemetry to `requirement_validation_history`
- `Test 28` [PASS] Helper `getLatestValidationRun` decodes `details_json` and fields correctly
- `Test 29` [PASS] Helper `getValidationRunsSummary` returns aggregated history telemetry
- `Test 30` [PASS] Controller `revalidateRequirement` executes full check and returns structured report
- `Test 31` [PASS] Multi-tenant Security: Company B cannot revalidate Company A's requirement
- `Test 32` [PASS] RBAC Governance: Super Admin (company_id=0) can revalidate any company requirement

### Section 4: Alert Engine & Notification Service (7 Tests)
- `Test 33` [PASS] `dispatchValidationFailed` creates in-app notification and email log
- `Test 34` [PASS] `dispatchValidationWarning` creates in-app advisory notice
- `Test 35` [PASS] `dispatchRequirementUnderReview` notifies user and broadcasts to Super Admin
- `Test 36` [PASS] `dispatchRequirementRevised` notifies user of requisition update
- `Test 37` [PASS] `dispatchRequirementOverdue` records overdue notification
- `Test 38` [PASS] Notification Query: `getUserNotifications` correctly filters by status and unread count
- `Test 39` [PASS] Notification Mutation: `markAsRead` updates notification state and read timestamp

### Section 5: Deadline Reminder & Cron Scheduling (6 Tests)
- `Test 40` [PASS] `dispatchDeadlineReminder` creates 7-day milestone alert
- `Test 41` [PASS] Idempotency: Inserting duplicate record in `deadline_reminder_logs` throws unique violation
- `Test 42` [PASS] Idempotency Revision: When target date changes, a fresh reminder can be dispatched
- `Test 43` [PASS] Cron CLI: Scheduled reminder script runs cleanly with `--dry-run`
- `Test 44` [PASS] Cron Lock: Script respects lock file and prevents overlapping executions
- `Test 45` [PASS] Cron Filtering: Requirements in `DRAFT`, `NEEDS_CORRECTION`, or `CLOSED` are strictly ignored

### Section 6: Candidate Database Immutability (3 Tests)
- `Test 46` [PASS] Safety Gate: `prayas_iti_dashboard.students` remains intact (1,292 records)
- `Test 47` [PASS] Safety Gate: `itistudents.candidates` remains intact (56 records)
- `Test 48` [PASS] Safety Gate: Core schema tables remain unmodified and intact

---

## 2. Regression Suite Summary

| Suite Name | Scope | Total Tests | Passed | Failed |
| :--- | :--- | :--- | :--- | :--- |
| `test_super_admin_governance_part05_suite.php` | Part 05 Super Admin Governance | 38 | 38 | 0 |
| `test_industry_plant_management_part06_suite.php` | Part 06 Industry & Plant Admin | 50 | 50 | 0 |
| `test_manpower_requirement_part07_suite.php` | Part 07 Requirement Management Engine | 66 | 66 | 0 |
| `test_auto_validation_alert_part08_suite.php` | Part 08 Validation & Alert Engine | 48 | 48 | 0 |
| **Total Cumulative Automated Tests** | **Parts 05, 06, 07, 08** | **202** | **202** | **0** |

