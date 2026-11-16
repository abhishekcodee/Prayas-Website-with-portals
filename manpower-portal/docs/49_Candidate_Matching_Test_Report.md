# 49 — Candidate Matching Engine Test & Verification Report

## Document Identifier: TEST-REP-MAT-2026-V1.0
**Project Title:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Test Suite Path:** [`tests/test_candidate_matching_suite.php`](file:///c:/Manpower%20Portal/tests/test_candidate_matching_suite.php)  
**Execution Date:** 2026-11-05  

---

## 1. Test Execution Summary

The Candidate Sourcing & Algorithmic Matching test suite verifies the end-to-end functionality, mathematical calibration, eligibility gate enforcement, deduplication, recruiter shortlisting, and candidate database zero-mutation guarantees.

```text
========================================================
PRAYAS Portal - Part 09 Candidate Matching Engine Tests
========================================================
Total Test Groups:   6
Total Test Assertions: 34
Total Passed:        34
Total Failed:        0
Success Rate:        100.0%
Zero-Mutation Audit: PASSED (1,292 + 56 rows intact)
========================================================
```

---

## 2. Test Group Breakdown & Assertions Matrix

| Group # | Test Group Title | Assertions | Result | Key Verified Behaviors |
| :---: | :--- | :---: | :---: | :--- |
| **01** | **Candidate Source Connection & Read-Only Access** | 6 | **PASS** | Read-only connection to `prayas_iti_dashboard.students` (1,292 rows) and `itistudents.candidates` (56 rows). Normalized field schema mappings. |
| **02** | **Algorithmic Scoring Subsystem (100 Pts)** | 6 | **PASS** | Exact trade match (30.0 pts), related trade cross-walk (20.0 pts), exact batch (20.0 pts), adjacent batch (12.0 pts), same city (10.0 pts), tier 1 score calibration ($\ge 85\%$). |
| **03** | **Mandatory Eligibility Gates** | 5 | **PASS** | Unrelated trade fails gate (`NOT_ELIGIBLE`), score grounded to 0.00%, diagnostic rationale recorded, exam `FAIL` status gate, gender mismatch gate. |
| **04** | **End-to-End Matching Run & DB Persistence** | 6 | **PASS** | Atomic matching run execution, scan of real candidates, candidate ranking, persistence to `candidate_matches` and `candidate_matching_runs`. |
| **05** | **Recruiter Shortlisting & Industry Visibility** | 8 | **PASS** | Recruiter shortlist action, action audit in `candidate_shortlist_actions`, privacy isolation (`is_shared_with_industry = 0`), authorized release (`is_shared_with_industry = 1`), rejection with mandatory reason. |
| **06** | **Candidate Databases Safety & Clean Cleanup** | 3 | **PASS** | Safe cleanup of test artifacts. Zero-mutation validation: Source 1 (1,292 rows), Source 2 (56 rows) completely untouched. |

---

## 3. Zero-Mutation Database Verification

Before and after test execution, the candidate repositories were audited for record counts and table integrity:

$$\text{prayas\_iti\_dashboard.students: } 1,292 \rightarrow 1,292 \quad (\Delta = 0)$$
$$\text{itistudents.candidates: } 56 \rightarrow 56 \quad (\Delta = 0)$$

Zero records inserted, updated, altered, or deleted in the external candidate databases.

