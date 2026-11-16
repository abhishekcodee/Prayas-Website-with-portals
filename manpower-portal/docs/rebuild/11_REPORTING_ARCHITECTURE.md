# Part 11 — Reporting & Executive Analytics Architecture
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal

### 1. Architectural Overview
The PRAYAS Reporting & Executive Analytics subsystem provides real-time, database-backed operational intelligence, executive visibility, and recruitment lifecycle tracking across all stakeholder roles (Super Admin, Industry Admin, Plant In-Charge, and Recruiters).

```
+---------------------------------------------------------------------------------------+
|                                    PRESENTATION LAYER                                 |
|  views/super_admin/reports.php  |  views/industry/reports.php  |  views/recruiter/... |
|  - 14 KPI Dashboard Cards       |  - Company-Scoped Analytics  |  - Funnel Conversion |
|  - 10 Interactive Chart.js      |  - Plant & Trade Demands     |  - Turnaround Times  |
|  - Multi-Tab Master Tables      |  - DPDP PII Masking          |  - Stage Attrition   |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
|                                  CONTROLLER LAYER                                     |
|                               controllers/ReportController.php                        |
|  - getComprehensiveDashboardMetrics()    - getCandidateSourcingReport()               |
|  - 10 Interactive Chart Generators       - getRecruitmentEfficiencyMetrics()          |
|  - getRequirementWiseVacancyReport()     - getPendingAndOverdueRequirementsReport()   |
|  - getTradeWiseRequirementReport()       - sanitizeExportCell() [Formula Injection]   |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
|                                   DATA LAYER (MySQL)                                  |
|  Internal Operational Tables:                                                         |
|  companies, manufacturing_plants, manpower_requirements, requirement_trades,          |
|  candidate_matches, candidate_screening, interviews, selections, offer_letters,       |
|  joinings, recruitment_stage_history, requirement_status_history                      |
|                                                                                       |
|  External Candidate Repositories (Strictly Read-Only & Immutable):                    |
|  prayas_iti_dashboard.students (1,292 rows)  |  itistudents.candidates (56 rows)     |
+---------------------------------------------------------------------------------------+
```

### 2. Core Architectural Principles
1. **Zero Hardcoded Statistics:** All KPIs, charts, and metrics are derived directly from live database queries using PDO prepared statements.
2. **Multi-Tenant Isolation:** Industry users are locked strictly to their session `company_id`. Zero cross-tenant data leakage is permitted.
3. **DPDP Compliance (Candidate Privacy):** Candidate names in employer/industry views are masked to standardized identifiers (`PRAYAS-ITI-XXXX`).
4. **Formula Injection Defense:** All exported cells (CSV / Excel) starting with `=`, `+`, `-`, `@`, `\t`, or `\r` are prepended with `'` to prevent remote execution in spreadsheet software.
5. **Zero Database Alterations:** Built entirely using existing relational tables without mutating the production schema.
6. **Candidate Database Immutability:** External candidate databases remain 100% read-only and immutable.
