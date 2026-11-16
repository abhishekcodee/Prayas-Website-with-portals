# Part 01 — Feature Gap & Functional Comparison Analysis
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Analysis Date:** 2026-11-05  
**Document Code:** `DOC-REBUILD-01-FEATURE-GAP`

---

## 1. Executive Summary

This document performs an exhaustive gap analysis between the existing PRAYAS Manpower Portal implementation and the full target functional requirements. The analysis classifies system features into:
- Critical features that must be strictly preserved without regression.
- Components requiring UI/UX visual and ergonomic redesign.
- Modules requiring functional optimization.
- Missing capabilities to be introduced.
- Identified technical inconsistencies, security refinements, and responsiveness issues.

---

## 2. Feature Classification Matrix

| Feature Domain | Existing State | Target Rebuild State | Action / Category | Priority |
|---|---|---|:---:|:---:|
| **Authentication & Session** | BCRYPT, role routing, idle timeout (1800s), CSRF | Add "Remember Me" option, dynamic base URL redirect | Functional Improvement | High |
| **User Role Taxonomy** | 5 roles (Super Admin, Industry Admin, Member, Recruiter Admin, Recruiter) | Preserve all 5 roles and permissions | **Must Preserve** | Critical |
| **Multi-Tenancy Scoping** | Session-bound `company_id` on all queries | Preserve 100% strict tenant isolation | **Must Preserve** | Critical |
| **External Candidate DBs** | Read-only bridge (`1,292` + `56` rows) | Maintain absolute read-only immutability | **Must Preserve** | Critical |
| **Company Verification** | Pending &rarr; Review &rarr; Verified / Rejected / Suspended | Add automated welcome email & checklist modal | UI Redesign / Polish | Medium |
| **Requirement Wizard** | 8-step wizard with draft save | Modularize wizard JS, improve mobile trade table layout | UI Redesign / Improvement | High |
| **Auto-Validation Engine** | `ALG-VAL-2026-V1.0` (Error, Warning, Auto-Approval) | Real-time asynchronous AJAX field validation | Functional Improvement | High |
| **Missing Field Alerts** | Validation history recorded; alerts in notification table | Dedicated top-bar badge counter & alert dismissal | Functional Improvement | Medium |
| **Algorithmic Matching** | 100-pt multi-factor scoring (`ALG-MAT-2026-V1.0`) | Add score sensitivity slider in recruiter console | UI / Ergonomics | Medium |
| **Candidate Privacy** | `is_shared_with_industry` flag hides unapproved | Preserve DPDP candidate redaction | **Must Preserve** | Critical |
| **16-Stage Pipeline** | Full FSM with screening, interview, offer, joining | Add bulk stage transition actions | Functional Improvement | Medium |
| **Interview Scheduling** | Future date validation, multi-mode (In-person, Video) | Add calendar .ics download link | Missing Feature | Low |
| **Offer Letter Generation** | `OFFER-YYYYMM-XXXX` code generator, terms review | Standardized PDF offer preview modal | UI Redesign | Medium |
| **Executive Reports** | 14 KPIs, Chart.js, Excel/CSV export | Add customizable date-range picker | Functional Improvement | Medium |
| **Subfolder URL Handling** | Hardcoded root paths (`/views/...`) fail in XAMPP subfolders | Universal `app_url()` helper across all links | **Broken Functionality Fix** | Critical |

---

## 3. Detailed Gap Findings

### 3.1 Features That Must Be Preserved (Zero Regression)
1. **Multi-Tenant Isolation Architecture:**
   - Any query fetching company requisitions, matches, team members, or exports must strictly resolve `company_id` from the authenticated session. Under no circumstances should client-supplied parameters alter tenant boundaries.
2. **External ITI Candidate Repositories Immutability:**
   - `prayas_iti_dashboard.students` (1,292 rows) and `itistudents.candidates` (56 rows) must never be modified, updated, or written to.
3. **100-Point Algorithmic Matching Scoring Engine:**
   - The multi-factor scoring model (`ALG-MAT-2026-V1.0`) incorporating Trade (35), Batch (20), Qualification (15), Skills (15), Location (10), and Experience (5).
4. **Finite State Machine Lifecycle Rules:**
   - Requisition status lifecycle (`DRAFT` &rarr; `SUBMITTED` &rarr; `VALIDATED` &rarr; `APPROVED` &rarr; `ACTIVE` &rarr; `FULFILLED` / `CLOSED` / `CANCELLED`).
   - Candidate pipeline lifecycle (`Matched` &rarr; `Shortlisted` &rarr; `Screened` &rarr; `Scheduled` &rarr; `Completed` &rarr; `Selected` &rarr; `Offer Released` &rarr; `Offer Accepted` &rarr; `Joining Pending` &rarr; `Joined`).
5. **Atomic Requisition Numbering:**
   - Year-month sequence persistence via `requirement_sequences` ensuring zero duplicate codes under concurrent submissions.

---

### 3.2 Features Requiring UI / UX Redesign
1. **Subfolder Portability & Asset Linking:**
   - **Issue:** All views and includes currently reference root-level assets (`href="/views/..."`, `src="/assets/..."`). When hosted inside a subfolder (such as `http://localhost/Manpower%20Portal/` on standard XAMPP), all links break and result in HTTP 404.
   - **Remedy:** Implement a centralized `app_url($path)` helper in `includes/session.php` or `config/database.php` that dynamically computes the base path from `$_SERVER['SCRIPT_NAME']`.
2. **Inlined JavaScript Decoupling:**
   - **Issue:** View files (e.g., `views/industry/requirement_form.php` [70 KB] and `views/recruiter/recruitment_pipeline.php` [71 KB]) contain extensive inlined `<script>` tags mixed with PHP templates.
   - **Remedy:** Extract clean, modular JavaScript files into `assets/js/modules/` with structured event delegation.
3. **Responsive Mobile Shopfloor Tables:**
   - **Issue:** On viewports under 768px, wide tables in candidate matching and recruitment pipeline require excessive horizontal scrolling.
   - **Remedy:** Add responsive card layouts for mobile viewports using Bootstrap utility classes (`d-md-none` card views vs `d-none d-md-table` tabular views).

---

### 3.3 Features Requiring Functional Improvements
1. **Asynchronous Real-Time Field Validation:**
   - **Current State:** Validation runs server-side on full form submission.
   - **Target State:** Add lightweight debounced AJAX endpoint (`/api/validate_field.php` or controller method) that gives instant feedback while filling the 8-step requirement wizard.
2. **Dedicated Requirement Alert Counters:**
   - **Current State:** Alerts are dispatched into general `notifications` and logged in `requirement_validation_history`.
   - **Target State:** Add a specialized alert indicator in the industry navigation bar distinguishing informational notifications from action-required validation blockers.
3. **Automated Stage Progress Calculations:**
   - **Current State:** Requisition fulfillment percentage is calculated upon report query execution.
   - **Target State:** Requisition card headers should visually render a dynamic progress bar showing `Filled Vacancies / Total Vacancies` in real-time.

---

### 3.4 Missing Features Identified
1. **Calendar File Export (.ics) for Scheduled Interviews:**
   - Allow recruiters and interviewers to download an `.ics` calendar invitation file when an interview is scheduled.
2. **Batch / Bulk Candidate Pipeline Actions:**
   - Recruiter interface currently advances candidates individually. A multi-select checkbox feature to bulk-shortlist or bulk-screen qualified cohorts will drastically accelerate processing.
3. **Candidate Offer Letter PDF Template:**
   - Direct browser print styling works well, but a server-side generated downloadable PDF offer attachment adds institutional polish.

---

### 3.5 Database & Vocabulary Inconsistencies
- High-level specifications frequently refer to `portal_users`, `candidate_selections`, and `candidate_joinings`.
- The physical database uses clean normalized names: `users`, `selections`, and `joinings`.
- *Resolution:* Maintain physical table names for database efficiency while providing explicit documentation mapping and controller alias methods to prevent confusion.

---

### 3.6 Security & Hardening Refinements
1. **File Upload MIME-Type Validation:**
   - Ensure company logo uploads strictly inspect binary file signatures (`finfo_open(FILEINFO_MIME_TYPE)`) in addition to file extension checks.
2. **Content Security Policy (CSP):**
   - Enhance `.htaccess` to include a strict `Content-Security-Policy` header restricting script origins to self and approved CDNs (Bootstrap, Chart.js).

