# Part 01 — Rebuild Implementation Plan & Workflow Specification
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Planning Date:** 2026-11-05  
**Document Code:** `DOC-REBUILD-01-PLAN`

---

## 1. Executive Summary

This document specifies the comprehensive implementation roadmap for the controlled rebuild and progressive modernization of the **PRAYAS Industry Manpower Requirement Portal**. 

The rebuild plan guarantees:
- **Zero data loss** and absolute preservation of the existing 30-table schema.
- Total immutability of the external candidate databases (`prayas_iti_dashboard` [1,292 records] and `itistudents` [56 records]).
- Strict multi-tenant data isolation.
- Progressive refactoring in modular phases with mandatory test verification gates.

---

## 2. Step 3 — Reference Workflow Specifications

The reference workflow embodies a 10-stage end-to-end recruitment lifecycle bridging industrial hiring demands with verified ITI graduates:

```
[Industry Employer]
        │
        ▼
 (1) Manpower Requirement Creation (8-Step Wizard)
        │
        ▼
 (2) Automatic Field Validation (ALG-VAL-2026-V1.0)
       ├─── [Missing/Invalid Fields Detected] ──► (3) Missing Field Detection
       │                                                    │
       │                                                    ▼
       │                                           (4) Requirement Alerts (Email & In-App)
       │                                                    │
       │                                                    ▼
       │                                           [Employer Rectification]
       │
       └─── [All Mandatory Rules Satisfied]
                    │
                    ▼
          (5) Automatic Requirement Approval (or Super Admin manual override)
                    │
                    ▼
          (6) Requirement Activation (Status: ACTIVE, Requisition Code: REQ-YYYYMM-XXXX)
                    │
                    ▼
          (7) Algorithmic ITI Candidate Matching (100-Pt Scoring: ALG-MAT-2026-V1.0)
                    │
                    ▼
          [Recruiter Console: Shortlist & Release]
                    │
                    ▼
          (8) Recruitment Pipeline Progression (Screening ➔ Interview Scheduling ➔ Feedback)
                    │
                    ▼
          (9) Candidate Selection & Offer Issuance (OFFER-YYYYMM-XXXX)
                    │
                    ▼
          (10) Shopfloor Joining Confirmation & Requirement Fulfillment
```

### Detailed Workflow Step Breakdown:
1. **Industry Manpower Requirement Creation:** Employer accesses the 8-step wizard specifying job title, plant facility, trade allocations (Welder, Fitter, Electrician, etc.), vacancies, minimum salary/stipend, working shifts, accommodation/food amenities, and required qualifications. Drafts can be saved at any step.
2. **Automatic Field Validation:** Engine evaluates rule set `ALG-VAL-2026-V1.0` verifying plant existence, positive vacancy count, statutory minimum wages, valid trade names, and required fields.
3. **Missing Field Detection:** If mandatory attributes (e.g. salary, trade qualification, shift timing) are missing or invalid, errors and warnings are categorized with field-specific identifiers.
4. **Requirement Alerts:** In-app visual notification banners and automated email dispatches alert the employer of specific validation blockers requiring rectification.
5. **Automatic Requirement Approval:** Requisitions passing all validation gates without critical errors and meeting verified employer criteria transition automatically to `APPROVED`. Unverified companies or flagged requisitions enter `PENDING_REVIEW` for Super Admin approval.
6. **Requirement Activation:** The requisition status updates to `ACTIVE`, and an atomic sequence generator assigns a permanent reference code formatted as `REQ-YYYYMM-XXXX`.
7. **ITI Candidate Matching:** The 100-point multi-factor engine scans candidate repositories, evaluating trade match (35 pts), batch year (20 pts), education (15 pts), skills (15 pts), location (10 pts), and experience (5 pts). Candidates are categorized as `ELIGIBLE` or `NOT_ELIGIBLE`. Recruiter reviews matches and selectively marks candidate profiles as shared (`is_shared_with_industry = 1`).
8. **Recruitment Pipeline Progression:** Candidates progress through telephonic screening, round-by-round interview scheduling with temporal validation, and 5-factor rubric evaluations.
9. **Candidate Selection & Offer Issuance:** Accepted candidates are formally selected, assigned to a specific plant unit, and issued an official offer letter with unique code `OFFER-YYYYMM-XXXX`.
10. **Joining Confirmation & Requirement Fulfillment:** Upon physical reporting to the industrial plant, the candidate's induction and joining date are verified (`Joined`). The requirement's fulfilled vacancy count increments. When filled vacancies equal total required vacancies, the requirement status transitions to `FULFILLED`.

---

## 3. Progressive Rebuild Sequence Roadmap

The rebuild is scheduled across **controlled sequential parts**:

| Part # | Rebuild Phase Title | Focus Area & Key Deliverables | Safety & Verification Criteria |
|:---:|---|---|---|
| **01** | **Analysis & Planning** *(Current)* | Non-destructive audit, gap analysis, database mapping, and rebuild master plan. | Zero code modification, zero DB changes. |
| **02** | **SRS, Architecture & UI Specs** | Update architectural specifications, ER diagrams, FSM charts, and wireframes. | Pure documentation phase; no code changes. |
| **03** | **Database Alignment & Safety** | Schema standardization, view creation, and backup scripts. | All migrations idempotent; zero data loss. |
| **04** | **Auth, Session & Portability** | Implement dynamic base URL (`app_url()`), session hardening, and subfolder fix. | Pass `test_auth_suite.php` (33 tests). |
| **05** | **Super Admin Governance** | Polish Super Admin dashboard, company approvals, audit logs, and system settings. | Pass `test_super_admin_suite.php` (32 tests). |
| **06** | **Industry Admin & Plants** | Company portal UX overhaul, plant management, team member invites, and profile. | Pass `test_industry_suite.php` (38 tests). |
| **07** | **Requirement Engine & Wizard** | Modernize 8-step wizard, modularize JS, atomic sequence locking, draft UX. | Pass `test_requirement_suite.php` (31 tests). |
| **08** | **Auto-Validation & Real-Time Alerts** | Centralized validation engine, real-time AJAX feedback, top-bar alert badge counters. | Pass `test_validation_notification_suite.php` (33 tests). |
| **09** | **Algorithmic Matching Engine** | 100-pt scoring console, DPDP privacy guards, candidate shortlist controls. | Pass `test_candidate_matching_suite.php` (34 tests). |
| **10** | **Recruitment Pipeline (16 Stages)** | Screening modals, interview scheduler, offer generator, ground joining confirmation. | Pass `test_recruitment_pipeline_suite.php` (61 tests). |
| **11** | **Reports & Executive Analytics** | Chart.js 4.4 widgets, 14 KPIs, multi-tenant isolation, Excel/CSV exports. | Pass `test_reports_analytics_suite.php` (59 tests). |
| **12** | **Final System Testing & Cutover** | Full regression suite (357 tests), security penetration audit, backup verification. | 100% pass rate across all suites. |

---

## 4. Part 02 Target Scope & Files Expected to Be Touched

In **Part 02**, the architectural documentation and technical specifications will be established. 
- **Files to be Created / Modified in Part 02:**
  - `docs/rebuild/02_SRS_DOCUMENTATION.md`
  - `docs/rebuild/02_BUSINESS_RULES_SPECIFICATION.md`
  - `docs/rebuild/02_WORKFLOW_STATE_MACHINE.md`
  - `docs/rebuild/02_UI_UX_DESIGN_SPECIFICATIONS.md`
  - `PROJECT_MASTER_TRACKER.md` (Update progress tracking)
  - `CHANGELOG.md` (Document Part 02 specifications)
- **Constraint:** Part 02 is strictly a specifications and documentation phase; no production code or database changes will be introduced without prior review.

---

## 5. Comprehensive Quality Assurance & Testing Strategy

Each phase of the progressive rebuild must pass its corresponding automated verification suite before proceeding:

1. **Unit & Functional Suites:** Re-execute existing 10 regression test suites (`tests/test_*_suite.php`) after every refactoring milestone.
2. **Multi-Tenant Isolation Assurance:** Dedicated execution of [`tests/test_multi_company_isolation_suite.php`](file:///c:/Manpower%20Portal/tests/test_multi_company_isolation_suite.php) ensuring zero cross-tenant contamination.
3. **Performance Benchmarking:** Execution of [`tests/test_performance_suite.php`](file:///c:/Manpower%20Portal/tests/test_performance_suite.php) to enforce latency SLA (< 200ms aggregations, < 1800ms candidate matching scans).
4. **Candidate Database Immutability Verification:** Strict programmatic assertion confirming that `prayas_iti_dashboard.students` (1,292 rows) and `itistudents.candidates` (56 rows) remain 100% immutable throughout all phases.

