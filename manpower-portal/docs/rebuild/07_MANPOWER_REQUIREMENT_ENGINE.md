# Part 07 — Manpower Requirement Management Engine
**PRAYAS — The Automation Academy**  
*Industry Manpower Requirement Portal*  
**Document Version:** 1.0  
**Status:** Completed & Verified  

---

## 1. Architectural Overview

The **Manpower Requirement Management Engine** is the core demand-side subsystem of the PRAYAS Industry Manpower Requirement Portal. It enables verified industrial enterprises to define, calibrate, submit, track, and fulfill shop-floor manpower demands across multiple manufacturing facilities.

The engine coordinates multiple sub-components:
1. **Concurrency-Safe Sequence Generator:** Emits collision-proof `REQ-YYYYMM-XXXX` identifiers using atomic database sequences.
2. **Multi-Step Requirement Wizard:** Guides industry administrators through an 8-pane creation process covering general details, trade allocations, candidate specifications, work conditions, salary, milestones, and interview logistics.
3. **Dynamic Trade-Wise Demand Sub-Engine:** Allows allocating vacancies across multiple ITI technical trades, calculating headcount sums automatically.
4. **Requirement Validation & Auto-Approval Engine (`ALG-VAL-2026-V1.0`):** Enforces 20+ algorithmic, statutory, and chronological validation checks before permitting submission.
5. **Requirement Status Lifecycle Engine:** Manages transitions across 10+ standard states with mandatory revision remarks and immutable history tracking in `requirement_status_history`.
6. **Multi-Tenant Scoping & Capacity Guard:** Guarantees strict multi-tenant isolation, preventing cross-tenant access, plant hijacking, and quota violations.

---

## 2. Core Controller Specifications (`controllers/RequirementController.php`)

| Method | Visibility | Description |
|---|---|---|
| `generateRequirementCode()` | `public` | Generates next sequential `REQ-YYYYMM-XXXX` code using atomic `INSERT ... ON DUPLICATE KEY UPDATE` on `requirement_sequences`. |
| `validateRequirement(array, int, ?int, ?int)` | `public` | Runs payload against centralized validation engine rules and checks company limits. |
| `saveDraft(int, array, ?int)` | `public` | Saves or updates draft requirement without enforcing final validation; records wizard step progress. Enforces plant ownership. |
| `submitRequirement(int, array, ?int)` | `public` | Validates, verifies plant ownership, checks quotas, updates status, saves sub-trades & skills, records history, and dispatches notifications. |
| `getRequirements(int, array, int, int)` | `public` | Retrieves paginated list of company-scoped requirements with keyword search, status, plant, and trade filters. |
| `getRequirementById(int, int)` | `public` | Retrieves detailed dossier for a specific requirement with sub-trades, skills, and status history under strict tenant isolation. |
| `getRequirementDashboardStats(int)` | `public` | Computes live summary metrics (total, drafts, pending validation, approved, in progress, fulfilled, closed, total vacancies) for the company. |
| `duplicateRequirement(int, int)` | `public` | Clones an existing requirement into a new `DRAFT` with fresh REQ code, cloning trades, skills, and resetting wizard step to 1. |
| `updateRequirementStatus(int, int, string, string, ?int)` | `public` | Updates lifecycle status with audit logging, timestamping, and entry in `requirement_status_history`. |
| `cancelRequirement(int, int, string)` | `public` | Soft-cancels an active or pending requirement with mandatory cancellation rationale. |

---

## 3. Database Subsystem Alignment

The engine utilizes existing tables without requiring schema modifications:

1. **`manpower_requirements`**: Primary requisition records containing title, plant facility, capacity metrics, salary bounds, chronological dates, and lifecycle status.
2. **`requirement_trades`**: Sub-table holding multi-trade technician allocations per requirement (trade title, vacancies, board type, passing years, experience criteria).
3. **`requirement_skills`**: Technical skill tags required for matching candidate profiles.
4. **`requirement_status_history`**: Audit trail of every lifecycle transition, previous status, new status, actor, and remarks.
5. **`requirement_sequences`**: Atomic sequence tracking table keyed by `year_month` (`YYYYMM`) with `last_sequence` counter.
6. **`manufacturing_plants`**: Source of physical manufacturing plant sites bound to authenticated companies.

---

## 4. Multi-Tenant Enforcement & Isolation

- **Session Binding:** Requisitions are immutably tied to authenticated `company_id`.
- **Plant Facility Guard:** Requisitions can only reference plants whose `company_id` matches the requisition's `company_id`.
- **Zero Cross-Company Exposure:** All read, write, draft, duplicate, and cancel operations enforce `WHERE company_id = :cid`. Foreign queries return `null` or explicit access denial.
- **Candidate Database Protection:** External databases (`prayas_iti_dashboard.students` and `itistudents.candidates`) remain 100% read-only.

