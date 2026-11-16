# 51. Candidate Screening Assessment

## Project Identity
- **Project:** Industry Manpower Requirement Portal
- **Organization:** PRAYAS – The Automation Academy
- **Module:** Pre-Interview Screening Subsystem

---

## 1. Purpose & Workflow
Candidate screening represents Stage 3 of the PRAYAS Recruitment Lifecycle. Before committing industry technical interviewers, recruiter personnel conduct structured telephonic assessments to verify:
1. Candidate willingness to relocate to plant site.
2. Immediate or expected availability window.
3. Salary / stipend alignment against requisition budget.
4. Basic technical comprehension and trade grounding.
5. Soft skills, shift flexibility, and communication readiness.

---

## 2. Assessment Parameters & Data Capture
The `candidate_screening` table records:
- `match_id`: Foreign key referencing `candidate_matches.id`.
- `screener_id`: User ID of the recruiter conducting screening.
- `screening_outcome`: `Passed`, `Failed`, `On Hold`, `Needs Re-Screening`.
- `willing_to_relocate`: `Yes`, `No`, `Conditional`.
- `candidate_availability`: `Immediate`, `Within 7 Days`, `Within 15 Days`, `Within 30 Days`.
- `expected_salary`: Target monthly compensation expected by candidate.
- `verified_trade_skills`: Boolean flag confirming basic practical checks.
- `screening_notes`: Detailed qualitative observations.
- `screened_at`: Timestamp of screening execution.

---

## 3. Automated Stage Transition Logic
Based on the `screening_outcome`:
- `Passed`: Candidate status transitions to `Screened`, unlocking the "Schedule Interview" workflow.
- `Failed`: Candidate status transitions to `Rejected`, logging recruiter observations into stage history.
- `On Hold`: Candidate placed on standby in talent reserve.
- `Needs Re-Screening`: Reverts to `Shortlisted` for follow-up verification.

