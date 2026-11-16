# Part 01 — Existing Project Analysis & Architectural Inspection
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Analysis Date:** 2026-11-05  
**Auditor / Architect:** Senior Enterprise Software Architect & PHP Specialist  
**Document Code:** `DOC-REBUILD-01-ANALYSIS`

---

## 1. Executive Overview

This document presents a comprehensive, non-destructive audit and structural analysis of the existing **PRAYAS Industry Manpower Requirement Portal**. The application is an enterprise-grade platform developed in Core PHP (PHP 8.2+), native PDO, and MySQL, designed to bridge ITI candidate talent with industrial manufacturing recruitment demands.

As mandated by Part 01 rebuild planning rules:
- **Zero code deletion or destructive modifications** were performed.
- **Zero database modifications** were enacted.
- The entire application structure, controllers, models, views, database schema, authentication, RBAC, business workflows, automated tests, and configuration were cataloged and analyzed.

---

## 2. Codebase Architecture & Directory Taxonomy

The current application adheres to a modular MVC-style architecture without third-party frameworks:

```
c:\Manpower Portal\
│
├── api/                                # Reserved for REST API micro-endpoints
├── assets/                             # Static frontend assets
│   ├── css/dashboard.css               # Core portal styling & theme variables
│   ├── images/                         # Official PRAYAS branding assets (logo.png, favicon.png)
│   └── js/                             # Modular JavaScript utilities
├── config/                             # Core configuration
│   └── database.php                    # PDO Connection pool & read-only candidate bridge
├── controllers/                        # Backend business logic controllers
│   ├── AuthController.php              # Login, registration, password lifecycle, CSRF
│   ├── CompanyController.php           # Super Admin company governance, verification
│   ├── IndustryController.php          # Industry Admin portal, plant & team management
│   ├── PipelineController.php          # 16-stage recruitment pipeline operations
│   ├── ReportController.php            # 14 KPI aggregations & 4 reporting groups
│   ├── RequirementController.php       # 8-step wizard, state transitions, validation
│   └── UserController.php              # Super Admin user management & RBAC overrides
├── database/                           # Database infrastructure & assets
│   ├── backups/                        # SQL snapshots (including verified cutover dump)
│   ├── cron/                           # Scheduled CLI task scripts
│   ├── migrations/                     # 12 sequential idempotent schema migrations
│   └── seeders/                        # Seeders for roles, permissions, settings, initial admin
├── docs/                               # 58+ technical documentation specs & runbooks
├── includes/                           # Shared runtime utilities, security guards, partials
│   ├── alerts.php                      # Flash message session renderers
│   ├── auth_guard.php                  # Authentication & permission firewall
│   ├── csrf.php                        # CSRF token generation & hash_equals verification
│   ├── footer.php                      # Global footer HTML
│   ├── header.php                      # Global HTML header, meta, CDN stylesheets
│   ├── navbar.php                      # Top navigation bar with notifications dropdown
│   ├── notification_service.php        # In-app and email dispatch engine
│   ├── permissions.php                 # Permission resolution & RBAC matrix checks
│   ├── requirement_validation_engine.php # Validation engine (ALG-VAL-2026-V1.0)
│   ├── session.php                     # Secure cookie configuration, idle timeout (1800s)
│   └── sidebar.php                     # Dynamic role-based navigation sidebar
├── models/                             # Data layer models
│   ├── CandidateMatchingModel.php      # 100-point multi-factor matching engine (ALG-MAT-2026)
│   └── CandidateSourceModel.php        # Read-only dual repository adapter (1,348 records)
├── tests/                              # Automated PHP test suites (11 suites, 357 assertions)
├── uploads/logos/                      # Storage for verified company branding logos
├── views/                              # Presentation layer organized by user roles
│   ├── auth/                           # Login, register, forgot/reset password
│   ├── industry/                       # Industry Admin portal & requirement wizard
│   ├── recruiter/                      # Candidate sourcing, matching, pipeline consoles
│   └── super_admin/                    # Governance dashboards, company review, audit logs
├── .env.production.example             # Hardened production environment template
├── .htaccess                           # Apache security rules, header injection, access blocks
└── index.php                           # Application gateway and role-aware routing dispatcher
```

---

## 3. Subsystem-by-Subsystem Technical Audit

### 3.1 Authentication & RBAC Engine
- **Implementation:** [`controllers/AuthController.php`](file:///c:/Manpower%20Portal/controllers/AuthController.php), [`includes/session.php`](file:///c:/Manpower%20Portal/includes/session.php), [`includes/auth_guard.php`](file:///c:/Manpower%20Portal/includes/auth_guard.php).
- **Password Security:** Hashes passwords using `PASSWORD_BCRYPT` with cost factor 12. Password history and mandatory reset flags supported.
- **Session Security:** `session_set_cookie_params()` enforces `HttpOnly`, `SameSite=Lax`, and `Secure` (in HTTPS). Session fixation eliminated via `session_regenerate_id(true)`. 30-minute idle session timeout enforced.
- **CSRF Defense:** Cryptographic tokens generated via `bin2hex(random_bytes(32))` and validated using timing-safe `hash_equals()`.
- **Role Taxonomy:** 5 distinct system roles:
  1. `super_admin`: Universal administrative governance.
  2. `industry_admin`: Primary industrial partner representative.
  3. `industry_member`: Delegated company staff with scoped view/create rights.
  4. `recruitment_admin`: Recruitment director overseeing matching and allocations.
  5. `recruiter`: Talent sourcing specialist conducting screening and pipeline staging.

### 3.2 Super Admin Governance & Audit Logging
- **Implementation:** [`controllers/CompanyController.php`](file:///c:/Manpower%20Portal/controllers/CompanyController.php), [`controllers/UserController.php`](file:///c:/Manpower%20Portal/controllers/UserController.php), [`views/super_admin/`](file:///c:/Manpower%20Portal/views/super_admin/).
- **Company Management:** Full workflow for review, verification, rejection (with mandatory justification), and suspension.
- **Audit Ledger:** Centralized, immutable ledger in `audit_logs` tracking user ID, company ID, action type, client IP address, user agent, and contextual JSON parameters.

### 3.3 Industry Admin Portal & Multi-Tenancy
- **Implementation:** [`controllers/IndustryController.php`](file:///c:/Manpower%20Portal/controllers/IndustryController.php), [`views/industry/`](file:///c:/Manpower%20Portal/views/industry/).
- **Tenant Scoping:** Direct object references are defended by strictly binding queries to `$_SESSION['company_id']`. Form parameter tampering is completely ignored.
- **Sub-entities:** Manufacturing plant facilities (`manufacturing_plants`), team member invitations (`company_invitations`), and company profiles.

### 3.4 Manpower Requirement Wizard & Engine
- **Implementation:** [`controllers/RequirementController.php`](file:///c:/Manpower%20Portal/controllers/RequirementController.php), [`views/industry/requirement_form.php`](file:///c:/Manpower%20Portal/views/industry/requirement_form.php).
- **8-Step Wizard:** Covers Basic Info, Plant Selection, Multi-Trade Allocations, Salary & Stipend Structures, Shift Timings, Facility Offerings, Candidate Eligibility Criteria, and Final Review.
- **Atomic Code Generator:** Generates formatted requirement codes (`REQ-YYYYMM-XXXX`) using atomic database locks on `requirement_sequences` to eliminate race conditions.
- **Draft Persistence:** Unfinished requisitions can be saved as `DRAFT` and resumed seamlessly.

### 3.5 Auto-Validation & Deadline Notification Engine
- **Implementation:** [`includes/requirement_validation_engine.php`](file:///c:/Manpower%20Portal/includes/requirement_validation_engine.php), [`cron/deadline_reminders.php`](file:///c:/Manpower%20Portal/cron/deadline_reminders.php).
- **Validation Standard:** Centralized algorithm (`ALG-VAL-2026-V1.0`) executing mandatory presence checks, trade allocation validation, numeric salary sanity checks, and auto-approval qualification.
- **Notification Multi-Channel:** Emits real-time in-app alerts (`notifications`), logs outgoing email notifications (`email_logs`), and records deadline tracking in `deadline_reminder_logs`.

### 3.6 Candidate Sourcing & Algorithmic Matching (100-Point Scoring)
- **Implementation:** [`models/CandidateMatchingModel.php`](file:///c:/Manpower%20Portal/models/CandidateMatchingModel.php), [`models/CandidateSourceModel.php`](file:///c:/Manpower%20Portal/models/CandidateSourceModel.php).
- **Candidate Data Sources:** Dual read-only database bridge:
  - `prayas_iti_dashboard.students`: 1,292 active records.
  - `itistudents.candidates`: 56 active records.
- **Multi-Factor Scoring (`ALG-MAT-2026-V1.0`):**
  - Trade Alignment: Up to 35 Points.
  - Passing Year / Batch Cohort: Up to 20 Points.
  - Educational Qualification: Up to 15 Points.
  - Required Technical Skills: Up to 15 Points.
  - Location & Relocation Willingness: Up to 10 Points.
  - Experience / Freshness: Up to 5 Points.
  - Total Potential Score: **100 Points**.
- **Privacy & DPDP Compliance:** Unshared candidate profiles (`is_shared_with_industry = 0`) are shielded from employer view until approved by a recruiter.

### 3.7 16-Stage Recruitment Pipeline
- **Implementation:** [`controllers/PipelineController.php`](file:///c:/Manpower%20Portal/controllers/PipelineController.php), [`views/recruiter/recruitment_pipeline.php`](file:///c:/Manpower%20Portal/views/recruiter/recruitment_pipeline.php).
- **Full Stage State Machine:** `Matched` &rarr; `Shortlisted` &rarr; `Screened` &rarr; `Scheduled` &rarr; `Completed` &rarr; `Selected` &rarr; `Offer Released` &rarr; `Offer Accepted` / `Offer Declined` &rarr; `Joining Pending` &rarr; `Joined` (plus `Rejected`, `On Hold`, `Withdrawn`, `No Show`, `Closed`).
- **Operational Artifacts:** Standardized telephonic screening (`candidate_screening`), temporal-validated interview scheduling (`interviews`), 5-star rubric feedback (`interview_feedback`), selections (`selections`), offer letters formatted as `OFFER-YYYYMM-XXXX` (`offer_letters`), and ground reporting verification (`joinings`).

### 3.8 Reports & Executive Analytics Engine
- **Implementation:** [`controllers/ReportController.php`](file:///c:/Manpower%20Portal/controllers/ReportController.php), [`views/super_admin/reports.php`](file:///c:/Manpower%20Portal/views/super_admin/reports.php), [`views/industry/reports.php`](file:///c:/Manpower%20Portal/views/industry/reports.php).
- **KPI Metrics:** 14 real-time indicators including Fulfillment %, Average TAT Days, Vacancies, Sourced, and Joined counts.
- **Visualizations:** Chart.js 4.4.1 donut, bar, and pipeline funnel charts.
- **Exports:** CSV and XML-based Excel streaming with strict multi-tenant scoping.

---

## 4. Existing Test Harness & Quality Assurance Audit

The platform possesses an extensive automated test suite located in `tests/`:
1. `tests/test_auth_suite.php`: 33 assertions passed.
2. `tests/test_super_admin_suite.php`: 32 assertions passed.
3. `tests/test_industry_suite.php`: 38 assertions passed.
4. `tests/test_requirement_suite.php`: 31 assertions passed.
5. `tests/test_validation_notification_suite.php`: 33 assertions passed.
6. `tests/test_candidate_matching_suite.php`: 34 assertions passed.
7. `tests/test_recruitment_pipeline_suite.php`: 61 assertions passed.
8. `tests/test_reports_analytics_suite.php`: 59 assertions passed.
9. `tests/test_multi_company_isolation_suite.php`: 21 assertions passed.
10. `tests/test_performance_suite.php`: 8 assertions passed.
11. `tests/test_backup_restore_verification.php`: 7 assertions passed.
- **Total:** **357 Automated Assertions passing with 100% success rate.**

---

## 5. Architectural Findings & Rebuild Target Priorities

1. **Root-Relative Path Dependency:**
   - The application heavily utilizes root-relative paths (`/views/auth/login.php`, `/assets/images/logo.png`). While suitable for dedicated virtual hosts (`http://localhost:8000/` or production domains), running in an Apache subfolder (such as `http://localhost/Manpower%20Portal/` inside XAMPP) causes broken routing unless a virtual host is provisioned.
   - *Rebuild Opportunity:* Introduce a dynamic base URL helper (`app_url($path)`) in `includes/session.php` to allow zero-configuration portability in both root domains and arbitrary subdirectories.
2. **Inlined Script Consolidation:**
   - Complex JavaScript logic (e.g., dynamic row addition in trade tables, Chart.js renderers, modal triggers) is currently embedded directly within PHP view templates.
   - *Rebuild Opportunity:* Extract reusable frontend scripts into modular files in `assets/js/` (e.g., `assets/js/requirement-wizard.js`, `assets/js/pipeline.js`, `assets/js/analytics.js`).
3. **Database Vocabulary Alignment:**
   - Standardize table naming clarity between high-level business entity specifications (`portal_users`, `candidate_selections`, `candidate_joinings`) and the underlying schema (`users`, `selections`, `joinings`).

---

## 6. Conclusion

The current codebase is robust, securely structured, and fully functional. The rebuild strategy must focus on non-destructive modernization, modular frontend decoupling, portability enhancements, and strict adherence to PRAYAS institutional requirements.

