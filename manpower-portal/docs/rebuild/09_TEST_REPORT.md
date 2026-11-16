# PART 09 — CANDIDATE SOURCING & MATCHING ENGINE TEST REPORT

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Test Suite (`tests/test_candidate_matching_part09_suite.php`)  
**Execution Date:** 2026-11-05 13:34:50  
**Test Engine:** Core PHP CLI + PDO + MySQL on Windows  
**Overall Status:** PASSED (51 / 51 Tests — 100% Success Rate)  

---

## 1. Test Suite Execution Summary

```
====================================================================
 PRAYAS PORTAL — PART 09 TEST SUITE: CANDIDATE SOURCING & MATCHING
 Started at: 2026-11-05 13:34:49
====================================================================

External Candidate Repositories Baseline:
 - prayas_iti_dashboard.students: 1292 rows
 - itistudents.candidates:        56 rows

Total Tests Executed: 51
Tests Passed:         51 (100.0%)
Tests Failed:         0  (0.0%)
Regressions:          0
Duration:             ~1.2 seconds
```

---

## 2. Granular Test Breakdown by Section

### Section 1: Candidate Source Model & Data Projection (Tests 1–5)
- **Test 1:** CandidateSourceModel instantiation succeeds &rarr; **PASS**
- **Test 2:** Returns non-empty list of available candidate sources (`prayas_dashboard`, `itistudents`) &rarr; **PASS**
- **Test 3:** Accurately counts available candidates across both external repositories (1,292 + 56 = 1,348) &rarr; **PASS**
- **Test 4:** `fetchCandidates()` returns projected records with privacy fields (Aadhaar, PAN, address) stripped &rarr; **PASS**
- **Test 5:** Standardizes trades into canonical forms (e.g. Fitter, Electrician, Welder) &rarr; **PASS**

### Section 2: Scoring Engine & Algorithm Weights (Tests 6–13)
- **Test 6:** Scoring weights configuration matches approved 7-factor distribution totaling exactly 100.0% &rarr; **PASS**
- **Test 7:** Classifies scores into 4 distinct quality tiers correctly &rarr; **PASS**
- **Test 8:** Exact trade match awarded full 30.0 points &rarr; **PASS**
- **Test 9:** Related trade cross-walk awarded partial points (20.0 pts) &rarr; **PASS**
- **Test 10:** Exact passing batch year awarded full 15.0 points &rarr; **PASS**
- **Test 11:** Adjacent passing year (&plusmn;1 year) awarded 10.0 points &rarr; **PASS**
- **Test 12:** Technical skills matching evaluated up to 20.0 points &rarr; **PASS**
- **Test 13:** Location proximity evaluated accurately (same district 10.0 pts, same state 6.0 pts) &rarr; **PASS**

### Section 3: Mandatory Eligibility Gates (Tests 14–19)
- **Test 14:** Gate 1: Completely unrelated trade triggers `NOT_ELIGIBLE` and forces total score to 0.00 &rarr; **PASS**
- **Test 15:** Gate 2: Candidate with exam status FAIL triggers `NOT_ELIGIBLE` and forces total score to 0.00 &rarr; **PASS**
- **Test 16:** Gate 3: Gender preference mismatch triggers `NOT_ELIGIBLE` &rarr; **PASS**
- **Test 17:** Gate 4: Mandatory Diploma requirement gates out candidates with ITI only &rarr; **PASS**
- **Test 18:** Active Requirement Check rejects DRAFT requisitions &rarr; **PASS**
- **Test 19:** Active Requirement Check rejects CLOSED requisitions &rarr; **PASS**

### Section 4: Matching Execution & Audit Logging (Tests 20–24)
- **Test 20:** `executeMatchingRun` on APPROVED requisition runs successfully &rarr; **PASS**
- **Test 21:** Logs execution record into `candidate_matching_runs` table with weights and filters snapshots &rarr; **PASS**
- **Test 22:** Populates `candidate_matches` table with ranked records &rarr; **PASS**
- **Test 23:** Populates transparent 7-dimension score fields and details JSON in match record &rarr; **PASS**
- **Test 24:** `getMatchingMetrics()` calculates summary KPIs accurately &rarr; **PASS**

### Section 5: Deduplication & Re-run Safety (Tests 25–26)
- **Test 25:** Unique Compound Constraint `uk_req_candidate` prevents duplicate rows on matching re-runs &rarr; **PASS**
- **Test 26:** Cross-source deduplication removes duplicate student records by NCVT registration/Name &rarr; **PASS**

### Section 6: Recruiter Workflow Actions (Tests 27–30)
- **Test 27:** Identifies first matched candidate for workflow testing &rarr; **PASS**
- **Test 28:** Recruiter action: `shortlistCandidate()` updates status to Shortlisted and logs action to audit ledger &rarr; **PASS**
- **Test 29:** Recruiter action: `shareWithIndustry()` authorizes profile release to industry portal &rarr; **PASS**
- **Test 30:** Recruiter action: `rejectCandidate()` requires non-empty rationale and updates status to Rejected &rarr; **PASS**

### Section 7: Multi-Tenant Isolation & Data Leakage Prevention (Tests 31–33)
- **Test 31:** Company A Industry Controller `getCandidateMatches()` returns matches for Company A requisition &rarr; **PASS**
- **Test 32:** Cross-Company Security Gate: Company B Industry Controller cannot view Company A candidate matches &rarr; **PASS**
- **Test 33:** `executeMatchingRun()` verifies Company Boundary isolation before execution &rarr; **PASS**

### Section 8: Candidate Privacy & DPDP Compliance (Tests 34–35)
- **Test 34:** Industry Portal query projection redacts Aadhaar, phone numbers, and full domestic addresses &rarr; **PASS**
- **Test 35:** Industry Portal candidate matching modal payload preserves transparent 7-dimension scoring breakdown &rarr; **PASS**

### Section 9: Database Immutability Integrity Verification (Tests 36–37)
- **Test 36:** External `prayas_iti_dashboard.students` remains 100% immutable (1,292 rows baseline & post-flight) &rarr; **PASS**
- **Test 37:** External `itistudents.candidates` remains 100% immutable (56 rows baseline & post-flight) &rarr; **PASS**

### Section 10: Advanced Recruiter Search, Filtering & Auditing (Tests 38–51)
- **Test 38:** Score Tier: Threshold $\ge 90.0$ maps strictly to Excellent Match &rarr; **PASS**
- **Test 39:** Score Tier: Threshold $75.0 - 89.9$ maps strictly to Strong Match &rarr; **PASS**
- **Test 40:** Score Tier: Threshold $60.0 - 74.9$ maps strictly to Possible Match &rarr; **PASS**
- **Test 41:** Score Tier: Threshold $< 60.0$ maps strictly to Low Match &rarr; **PASS**
- **Test 42:** Zero Score Guarantee: Ineligible candidate receives 0.00 score despite high skill/location matches &rarr; **PASS**
- **Test 43:** Recruiter action: `holdCandidate()` updates status to On Hold and logs audit event &rarr; **PASS**
- **Test 44:** Recruiter Console: `getMatchesForRequirement()` enforces pagination limit and offset &rarr; **PASS**
- **Test 45:** Recruiter Console: `getMatchesForRequirement()` filters accurately by status = 'Shortlisted' &rarr; **PASS**
- **Test 46:** Recruiter Console: `getMatchesForRequirement()` filters accurately by score tier = 'tier1' &rarr; **PASS**
- **Test 47:** Recruiter Console: `getMatchesForRequirement()` search by candidate name returns matching subset &rarr; **PASS**
- **Test 48:** Recruiter Console: `getMatchesForRequirement()` search by trade returns trade subset &rarr; **PASS**
- **Test 49:** Re-running `executeMatchingRun()` preserves existing Shortlisted candidates without resetting to Matched &rarr; **PASS**
- **Test 50:** Transparent Scoring Explanation contains complete 7-dimension diagnostic structure &rarr; **PASS**
- **Test 51:** Verification that candidate tables in external DBs had zero schema modifications &rarr; **PASS**

---

## 3. Regression Audit Results Across Rebuild Suites

| Suite | Focus Area | Assertions | Result |
| :--- | :--- | :---: | :---: |
| **Part 05 Suite** | Super Admin Governance & Multi-Tenant Company Limits | 38 / 38 | **100% PASS** |
| **Part 06 Suite** | Industry & Manufacturing Plant Management Lifecycle | 50 / 50 | **100% PASS** |
| **Part 07 Suite** | Manpower Requirement Engine & Trade Allocation | 66 / 66 | **100% PASS** |
| **Part 08 Suite** | Automatic Validation & Alert Engine | 48 / 48 | **100% PASS** |
| **Part 09 Suite** | ITI Candidate Sourcing & Algorithmic Matching Engine | 51 / 51 | **100% PASS** |
| **TOTAL** | **Cumulative Platform Regression Testing** | **253 / 253** | **100% PASS** |

