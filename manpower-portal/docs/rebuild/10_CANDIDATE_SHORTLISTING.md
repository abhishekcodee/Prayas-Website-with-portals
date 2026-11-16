# 10 — CANDIDATE SHORTLISTING & BULK ACTION ENGINE
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal
### Cycle 2.0 Modernization & Enterprise Rebuild

---

## 1. Shortlisting Workflow

Candidate shortlisting is the initial gate where recruiter talent managers evaluate algorithmically matched candidates against client hiring guidelines.

- **Pre-requisite**: Candidate must be in `Matched` status with `eligibility_status = 'ELIGIBLE'`.
- **Target Stage**: `Shortlisted`.
- **Action Records**: Each shortlisting action is written to `candidate_shortlist_actions` with action code `SHORTLIST` and recruiter remarks.
- **Employer Visibility**: Candidates remain internal to the recruitment desk until explicitly shared (`is_shared_with_industry = 1`) or advanced past preliminary vetting.

---

## 2. Safe Bulk Actions Architecture

The bulk action engine (`PipelineController::executeBulkAction()`) processes batches of candidates safely, idempotently, and transactionally.

### Supported Bulk Operations:
1. `bulk_shortlist`: Advances multiple `Matched` candidates to `Shortlisted`.
2. `bulk_advance`: Advances each candidate to their natural sequential pipeline stage.
3. `bulk_share`: Updates `is_shared_with_industry = 1`, making candidate summaries visible in the employer dashboard.
4. `bulk_hold`: Places selected candidates into `On Hold` talent reserve.
5. `bulk_schedule_interview`: Creates bulk interview appointments with uniform datetime and venue.

### Safety & Resiliency Guarantees:
- **Eligibility Validation**: Any candidate marked `NOT_ELIGIBLE` is skipped with an explicit skipped reason.
- **Tenant Scope Enforcement**: When executed in an employer context, `$companyId` scoping ensures only matches belonging to the company's requisitions are processed.
- **Detailed Telemetry Return**: The engine returns an associative payload with `success_count`, `skipped_count`, and an array of individual skip reasons for recruiter transparency.
- **Audit Logging**: Each successful bulk transition writes a corresponding event in `recruitment_stage_history`.
