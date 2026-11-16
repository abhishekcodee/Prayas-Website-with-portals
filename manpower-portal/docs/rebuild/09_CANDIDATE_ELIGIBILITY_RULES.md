# PART 09 — CANDIDATE ELIGIBILITY RULES & MANDATORY GATING CATALOG

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Eligibility Rules Engine (`CandidateMatchingModel.php`)  
**Status:** COMPLETED & VERIFIED (Part 09 Rebuild)  

---

## 1. Overview of Two-Tier Evaluation Architecture

The PRAYAS Matching Engine operates on a two-tier evaluation architecture:
1. **Tier 1: Mandatory Eligibility Gating** (Binary Pass/Fail). If any mandatory gate fails, the candidate is classified as `NOT_ELIGIBLE`, assigned a total match score of `0.00`, and gated out of active recruiter pipelines.
2. **Tier 2: Weighted 100-Point Scoring Model** (Granular Points Allocation). Executed only for candidates who pass all Tier 1 gates.

---

## 2. Mandatory Gate Catalog

### Gate 1: Requisition Status Gate
- **Rule ID:** `GATE-REQ-STATUS`
- **Condition:** Automated candidate sourcing and matching is restricted exclusively to approved and active requisitions.
- **Allowed Statuses:** `APPROVED`, `IN_PROGRESS`, `PARTIALLY_FULFILLED`, `RECRUITMENT_IN_PROGRESS`.
- **Blocked Statuses:** `DRAFT`, `NEEDS_CORRECTION`, `UNDER_REVIEW`, `CANCELLED`, `CLOSED`, `FULFILLED`.
- **System Action:** Execution terminates early with an explanatory rejection message if requisition is not active.

### Gate 2: Trade Alignment Gate
- **Rule ID:** `GATE-TRADE-MATCH`
- **Condition:** Candidate's trade must exactly match one of the requirement's trades OR belong to an authorized related trade cross-walk family.
- **Related Trade Cross-Walk Matrix:**
  - `Electrician` &harr; `Wireman`, `Electronic Mechanic`, `Instrument Mechanic`
  - `Fitter` &harr; `Turner`, `Machinist`, `Tool & Die Maker`, `Welder`
  - `Turner` &harr; `Fitter`, `Machinist`
  - `Machinist` &harr; `Turner`, `Fitter`, `Tool & Die Maker`, `CNC Operator`
  - `Welder` &harr; `Fitter`, `Sheet Metal Worker`
  - `Mechanic Motor Vehicle` &harr; `Diesel Mechanic`, `Tractor Mechanic`
- **Failure Consequence:** Candidate marked `NOT_ELIGIBLE` with reason: `"Trade '<trade>' does not match requirement trades."`. Total score forced to `0.00`.

### Gate 3: Examination Clearance Gate
- **Rule ID:** `GATE-EXAM-RESULT`
- **Condition:** Candidates with recorded examination result of `FAIL` or `is_passed = 0` cannot be matched.
- **Failure Consequence:** Candidate marked `NOT_ELIGIBLE` with reason: `"Candidate ITI examination status is marked FAIL."`. Total score forced to `0.00`.

### Gate 4: Gender Preference Compliance Gate
- **Rule ID:** `GATE-GENDER-PREF`
- **Condition:** If requisition specifies `Female Only` or `Male Only`, candidates whose gender contradicts the requirement are strictly excluded. If requirement specifies `Any`, candidates of all genders are eligible.
- **Failure Consequence:** Candidate marked `NOT_ELIGIBLE` with reason: `"Requirement specifies <Pref>; candidate gender is <CandGender>."`. Total score forced to `0.00`.

### Gate 5: Mandatory Qualification Level Gate
- **Rule ID:** `GATE-QUAL-LEVEL`
- **Condition:** If requisition explicitly demands a higher qualification tier (e.g. `Diploma / Polytechnic`), candidates holding only ITI certification are gated out.
- **Failure Consequence:** Candidate marked `NOT_ELIGIBLE` with reason: `"Requirement mandates qualification '<ReqQual>'; candidate holds '<CandQual>'."`. Total score forced to `0.00`.

---

## 3. Telemetry & Diagnostic Feedback

When a candidate fails any mandatory gate:
- `eligibility_status` is persisted as `'NOT_ELIGIBLE'` in `candidate_matches`.
- `eligibility_reasons` stores the semicolon-delimited rationale for transparency.
- In both Recruiter and Industry views, an explicit gate failure banner displays the diagnostic rationale upon opening the candidate explanation modal.

