# 10 — SELECTION DECISION MANAGEMENT
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal
### Cycle 2.0 Modernization & Enterprise Rebuild

---

## 1. Official Selection Decision Workflow

Once a candidate successfully completes technical rounds, the official selection decision is finalized via `PipelineController::recordSelectionDecision()`.

- **Input Parameters**:
  - `selection_status`: `Selected`, `On Hold`, or `Rejected`.
  - `offered_designation`: Official appointment title (e.g. "Junior CNC Operator / Technician").
  - `offered_ctc`: Monthly gross compensation (₹).
  - `target_joining_date`: Expected reporting date (defaults to $+15$ days from selection).
  - `decision_remarks`: Technical justification notes from interview panel.

- **Pipeline Transition**:
  - Updates `candidate_matches.status` to `Selected`.
  - Upserts record into `selections` table.
  - Automatically returns unique `selection_id` for downstream offer letter creation.
  - Dispatches `CANDIDATE_SELECTED` notification to industry admin.

---

## 2. Database Schema: `selections`

```sql
CREATE TABLE `selections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `offered_designation` varchar(150) NOT NULL,
  `offered_ctc` decimal(10,2) NOT NULL,
  `offer_date` date NOT NULL,
  `target_joining_date` date NOT NULL,
  `selection_status` enum('Selected','Offer Released','Offer Accepted','Offer Declined','Joined','Withdrawn','Rejected') NOT NULL DEFAULT 'Selected',
  `decision_remarks` text DEFAULT NULL,
  `selected_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_match_selection` (`match_id`)
);
```
