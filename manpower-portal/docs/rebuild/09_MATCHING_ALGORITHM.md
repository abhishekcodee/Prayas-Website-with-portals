# PART 09 — CANDIDATE MATCHING ALGORITHM ARCHITECTURE & WORKFLOW

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Matching Algorithm & Service Architecture (`CandidateMatchingModel.php`)  
**Status:** COMPLETED & VERIFIED (Part 09 Rebuild)  

---

## 1. Algorithm Overview

The PRAYAS Matching Algorithm is a deterministic, multi-factor ranking pipeline designed to evaluate accredited ITI students against approved industry requisitions with multi-tenant company data isolation and zero external database mutation.

```
+-------------------------------------------------------------------------------+
|                       APPROVED MANPOWER REQUISITION                          |
|         (Trade demand, Qualification, Batch, Skills, Location, Exp)          |
+-------------------------------------------------------------------------------+
                                      |
                                      v
+-------------------------------------------------------------------------------+
|                        PHASE 1: SEARCH & FETCH                                |
|   Query prayas_iti_dashboard.students (1,292) & itistudents.candidates (56)   |
|   (Filter by trade family & passing exam status; keep location/batch broad)   |
+-------------------------------------------------------------------------------+
                                      |
                                      v
+-------------------------------------------------------------------------------+
|                      PHASE 2: DEDUPLICATION PIPELINE                          |
|   Reconcile cross-source duplicates by NCVT Registration No & Full Name|Trade |
+-------------------------------------------------------------------------------+
                                      |
                                      v
+-------------------------------------------------------------------------------+
|                   PHASE 3: MANDATORY ELIGIBILITY GATING                       |
|   Check Trade, Exam Result, Gender Preference, and Qualification Level        |
|        [FAIL] ---> Set ELIGIBLE=0, Score=0.00, Record Gate Failure Reason     |
+-------------------------------------------------------------------------------+
                                      |
                                  [PASS]
                                      v
+-------------------------------------------------------------------------------+
|                    PHASE 4: 7-FACTOR WEIGHTED SCORING                         |
|   Trade (30%) + Qual (15%) + Batch (15%) + Skills (20%)                       |
|   + Location (10%) + Experience (5%) + Availability (5%)                      |
+-------------------------------------------------------------------------------+
                                      |
                                      v
+-------------------------------------------------------------------------------+
|                   PHASE 5: QUALITY TIER CLASSIFICATION                        |
|   >=90%: Excellent Match | 75-89%: Strong Match | 60-74%: Possible Match      |
|   <60%: Low Match        | Gate Failed: Not Eligible                          |
+-------------------------------------------------------------------------------+
                                      |
                                      v
+-------------------------------------------------------------------------------+
|               PHASE 6: AUDIT LOGGING & IDEMPOTENT UPSERT                      |
|   Insert into candidate_matching_runs                                         |
|   Upsert into candidate_matches ON DUPLICATE KEY UPDATE                       |
+-------------------------------------------------------------------------------+
```

---

## 2. Execution Entrypoints

1. **Recruiter Manual Trigger:**
   Initiated via `views/recruiter/candidate_matching.php` with CSRF protection and role check (`recruiter`, `super_admin`).
2. **Automatic Post-Validation Trigger:**
   Initiated automatically by `RequirementController` when a requisition achieves approved status (`APPROVED`, `IN_PROGRESS`).
3. **Programmatic API:**
   ```php
   $matchingModel = new CandidateMatchingModel($pdo);
   $result = $matchingModel->executeMatchingRun(
       $requirementId, 
       $triggeredByUserId, 
       $companyId // Enforces multi-tenant boundary
   );
   ```

---

## 3. Concurrency & Idempotency Safeguards

- **Transactional Safety:** Candidate scanning, scoring evaluation, run logging, and upsert are executed inside a single database transaction (`beginTransaction()` / `commit()`).
- **Conflict Prevention:** Uses compound unique key `uk_req_candidate (requirement_id, candidate_source, candidate_source_id)`.
- **Workflow State Preservation:** If a candidate was previously `Shortlisted` or `Shared with Industry`, re-running matching updates the score and breakdown explanation without resetting recruiter workflow status back to `Matched`.

