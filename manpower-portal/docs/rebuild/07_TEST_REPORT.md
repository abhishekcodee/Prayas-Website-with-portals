# Part 07 — Dedicated Test Execution Report
**PRAYAS — The Automation Academy**  
*Industry Manpower Requirement Portal*  
**Document Version:** 1.0  
**Test Suite:** `tests/test_manpower_requirement_part07_suite.php`  
**Execution Timestamp:** 2026-11-05  

---

## 1. Test Summary

```
========================================================
Part 07 Manpower Requirement Engine Test Summary:
Total Tests: 66 | Passed: 66 | Failed: 0
Success Rate: 100%
========================================================
```

---

## 2. Granular Test Group Results

### Group 1: Concurrency-Safe Requirement ID Generation
- `[PASS]` Requirement Code matches format `REQ-YYYYMM-XXXX` (Generated: `REQ-202611-0065`)
- `[PASS]` Consecutive Requirement Codes are strictly sequential (`REQ-202611-0065` $\rightarrow$ `REQ-202611-0066`)
- `[PASS]` Database sequence reflects current high-water mark in `requirement_sequences`

### Group 2: Multi-Step Draft Creation & Wizard Persistence
- `[PASS]` Draft initialized successfully (`REQ-202611-0067`)
- `[PASS]` Draft assigned valid REQ code
- `[PASS]` Draft persisted with status `'DRAFT'`
- `[PASS]` Draft `wizard_step` stored accurately
- `[PASS]` Draft total vacancies matches trade sum ($6 + 4 = 10$)
- `[PASS]` Draft trades sub-table populated correctly
- `[PASS]` Draft updated successfully at higher step
- `[PASS]` Updated wizard step is 4
- `[PASS]` Updated title persisted

### Group 3: Dynamic Trade-Wise Demand Builder & Auto-Sum
- `[PASS]` Multi-trade requisition draft created
- `[PASS]` Auto-sum calculated total vacancies ($8 + 5 + 7 = 20$)
- `[PASS]` Exactly 3 distinct trade rows recorded in `requirement_trades`
- `[PASS]` Trades include Fitter, Electrician, and Welder

### Group 4: Server-Side Validation Rules
- `[PASS]` Rejection of title under 5 characters
- `[PASS]` Rejection of description under 20 characters
- `[PASS]` Rejection of zero vacancy allocation
- `[PASS]` Rejection when total vacancies $\ne$ sum of trade allocations
- `[PASS]` Rejection of inverted salary range ($\text{Max} < \text{Min}$)
- `[PASS]` Rejection of non-positive minimum salary
- `[PASS]` Rejection of candidate age under statutory minimum 18 (Factories Act)
- `[PASS]` Rejection when passing year to $<$ passing year from

### Group 5: Chronological Timeline Validation
- `[PASS]` Rejection of application deadline in the past
- `[PASS]` Rejection when interview date precedes application deadline
- `[PASS]` Rejection when target joining precedes interview date
- `[PASS]` Clean chronological timeline passes validation

### Group 6: Strict Multi-Tenant & Plant Isolation
- `[PASS]` Company A blocked from assigning Company B's plant in draft
- `[PASS]` Company A blocked from submitting requisition for Company B's plant
- `[PASS]` Company B cannot read Company A's requirement (Strict `null` return)
- `[PASS]` Company B cannot modify Company A's draft
- `[PASS]` Company B cannot cancel Company A's requirement
- `[PASS]` Company B cannot duplicate Company A's requirement
- `[PASS]` Company B requirement directory strictly isolated from Company A

### Group 7: Requirement Status Lifecycle & Revision History
- `[PASS]` Requirement submitted successfully (`APPROVED`)
- `[PASS]` Status updated to `'APPROVED'`
- `[PASS]` Wizard step set to 8 (Completed)
- `[PASS]` Draft payload cleared upon submission
- `[PASS]` Requirement status history recorded submission transition
- `[PASS]` Latest history record matches new status `'APPROVED'`
- `[PASS]` Transition to `'RECRUITMENT_IN_PROGRESS'`
- `[PASS]` Transition to `'PARTIALLY_FULFILLED'`
- `[PASS]` Transition to `'FULFILLED'`
- `[PASS]` Final status reflects `'FULFILLED'`
- `[PASS]` History contains `'DRAFT'`
- `[PASS]` History contains `'RECRUITMENT_IN_PROGRESS'`
- `[PASS]` History contains `'PARTIALLY_FULFILLED'`
- `[PASS]` History contains `'FULFILLED'`

### Group 8: Requirement Duplication Engine
- `[PASS]` Requirement duplication succeeds (`REQ-202611-0069`)
- `[PASS]` Duplicated requirement has a fresh, distinct REQ code
- `[PASS]` Duplicated requirement status defaults to `'DRAFT'`
- `[PASS]` Duplicated requirement wizard_step resets to 1
- `[PASS]` Duplicated requirement title prefixed with `'Copy of '`
- `[PASS]` Duplicated requirement clones trade allocation rows
- `[PASS]` Duplicated requirement preserves same plant facility
- `[PASS]` Duplicated requirement creates initial history record
- `[PASS]` Cancellation of duplicate draft succeeds
- `[PASS]` Cancelled requirement status is `'CANCELLED'`

### Group 9: Dashboard Metrics & Capacity Limits
- `[PASS]` Dashboard stats return `total_requirements >= 2`
- `[PASS]` Dashboard stats return `fulfilled >= 1`
- `[PASS]` Dashboard stats return `closed_or_cancelled >= 1`
- `[PASS]` Dashboard stats sum `total_vacancies` accurately
- `[PASS]` Excess vacancy submission rejected by capacity validator

### Group 10: External Candidate Databases Immutability Audit
- `[PASS]` External `prayas_iti_dashboard.students` remains intact (1,292 rows)
- `[PASS]` External `itistudents.candidates` remains intact (56 rows)

---

## 3. Total Regression Test Suite Status

| Part | Test Suite File | Passed | Failed | Success Rate |
|---|---|---|---|---|
| Part 04 | `test_security_audit_part04_suite.php` / `test_auth_suite.php` | 44 | 0 | 100% |
| Part 05 | `test_super_admin_governance_part05_suite.php` | 38 | 0 | 100% |
| Part 06 | `test_industry_plant_management_part06_suite.php` | 50 | 0 | 100% |
| Part 07 | `test_manpower_requirement_part07_suite.php` | 66 | 0 | 100% |
| Candidate DB Integrity | Verified in all suites | 1,348 rows | 0 | 100% Intact |
| **Total Cumulative** | | **268+** | **0** | **100%** |

