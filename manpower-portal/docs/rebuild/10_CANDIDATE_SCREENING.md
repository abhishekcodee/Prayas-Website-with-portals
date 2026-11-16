# 10 — CANDIDATE SCREENING & PRE-ASSESSMENT
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal
### Cycle 2.0 Modernization & Enterprise Rebuild

---

## 1. Candidate Screening Objectives

Telephonic and preliminary screening evaluates non-academic factors essential for industrial plant retention:
1. Willingness to relocate to plant site / factory town.
2. Immediate candidate availability (Immediate, 7 Days, 15 Days, 30 Days).
3. Salary / monthly stipend expectations vs requirement budget.
4. Candidate communication, shift flexibility (Day/Night rotations), and physical fitness readiness.

---

## 2. Screening Outcomes & Lifecycle Routing

Screening results are recorded via `PipelineController::recordScreening()` into the `candidate_screening` table:

| Screening Outcome | Pipeline Status | Operational Result |
|---|---|---|
| `Passed` | `Screened` | Candidate cleared for technical interview scheduling. |
| `On Hold` | `On Hold` | Pending trade certificate clarification, parent consent, or medical recovery. |
| `Needs Re-Screening` | `Shortlisted` | Telephonic line dropped or candidate asked for callback; stays in review queue. |
| `Failed` | `Rejected` | Candidate unwilling to relocate, salary mismatch, or unresponsive. Requires explicit notes. |

---

## 3. Database Schema: `candidate_screening`

```sql
CREATE TABLE `candidate_screening` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `screener_id` int(11) NOT NULL,
  `screening_outcome` enum('Passed','Failed','On Hold','Needs Re-Screening') NOT NULL,
  `willing_to_relocate` enum('Yes','No','Conditional') NOT NULL DEFAULT 'Yes',
  `candidate_availability` enum('Immediate','Within 7 Days','Within 15 Days','Within 30 Days') NOT NULL DEFAULT 'Immediate',
  `expected_salary` decimal(10,2) DEFAULT NULL,
  `screening_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_match` (`match_id`)
);
```
