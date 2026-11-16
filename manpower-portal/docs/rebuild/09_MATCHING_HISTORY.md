# PART 09 — MATCHING RUN HISTORY & AUDIT LEDGER ARCHITECTURE

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Matching Run Logging & Recruiter Actions Ledger  
**Status:** COMPLETED & VERIFIED (Part 09 Rebuild)  

---

## 1. Matching Run Audit Schema (`candidate_matching_runs`)

Every automated or recruiter-initiated matching execution creates an indelible historical record in `candidate_matching_runs`:

| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | `INT UNSIGNED AUTO_INCREMENT PRIMARY KEY` | Internal run identifier |
| `requirement_id` | `INT UNSIGNED` | Foreign key referencing `manpower_requirements.id` |
| `run_identifier` | `VARCHAR(64)` | High-entropy unique run code (`RUN-YYYYMMDD-HHMMSS-xxxxxx`) |
| `total_candidates_scanned` | `INT UNSIGNED` | Total records queried from external repositories |
| `eligible_candidates_found` | `INT UNSIGNED` | Total candidates passing all mandatory gates |
| `ineligible_candidates_count` | `INT UNSIGNED` | Total candidates rejected by mandatory gates |
| `weights_json` | `TEXT` | Exact 7-factor weights snapshot used for this run |
| `filters_applied_json` | `TEXT` | Requisition search parameters and filter criteria snapshot |
| `triggered_by` | `INT UNSIGNED` | User ID who initiated the execution (or NULL if system) |
| `created_at` | `DATETIME / TIMESTAMP` | Server timestamp of execution |

---

## 2. Recruiter Action Audit Schema (`candidate_shortlist_actions`)

Every state mutation performed by recruiters on matched candidate records is logged in `candidate_shortlist_actions`:

| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | `INT UNSIGNED AUTO_INCREMENT PRIMARY KEY` | Action record ID |
| `match_id` | `INT UNSIGNED` | Foreign key referencing `candidate_matches.id` |
| `action` | `ENUM('SHORTLIST','REJECT','HOLD','RESET','SHARE_WITH_INDUSTRY')` | Action classification |
| `remarks` | `TEXT` | Screening comments or mandatory rejection rationale |
| `performed_by` | `INT UNSIGNED` | User ID of the recruiter |
| `created_at` | `TIMESTAMP` | Timestamp of action |

---

## 3. Auditing & Re-run Verification Rules

1. **Rejection Rationale Enforcement:** Calling `rejectCandidate()` without a substantive reason is rejected by the backend model and throws an error.
2. **Re-Run Idempotence:** Re-running algorithmic matching updates scores and ranks without erasing previous shortlisted states or purging recruiter historical audit records.
3. **Traceability:** Platform administrators can reconstruct the precise algorithm configuration (weights and filters) for any matching run conducted in the portal's history.

