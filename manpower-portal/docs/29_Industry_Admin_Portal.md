# 29 — Industry Admin Portal Architecture & Dashboard Specification

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Industry Partner Workspace & Executive Dashboard  
**Document ID:** DOC-IND-29  
**Status:** Approved & Implemented  

---

## 1. Overview & Objectives

The **Industry Admin Portal** (`views/industry/dashboard.php`, `controllers/IndustryController.php`) provides an enterprise workspace for corporate employers partnering with PRAYAS – The Automation Academy.

It serves as the secure interface through which manufacturing enterprises:
1. Track corporate profile completeness and statutory accreditation.
2. Register and configure distributed manufacturing plants.
3. Manage plant recruitment coordinators and team members.
4. Submit and track manpower requisitions across their complete lifecycle.
5. Review anonymized, matched ITI technician candidates.
6. Monitor candidate interview, selection, and plant joining milestones.

---

## 2. Multi-Tenant Isolation & Security Engine

### Absolute Tenant Scoping
- Tenant isolation is strictly enforced via `getCompanyId()`, which extracts `$_SESSION['company_id']`.
- Frontend form submissions, URL query parameters, or client headers attempting to pass `company_id` are ignored and never trusted.
- All database queries across companies, manufacturing plants, team members, manpower requirements, and candidate matches strictly bind `:cid` to `getCompanyId()`.
- Cross-company IDOR (Insecure Direct Object Reference) attempts result in immediate access rejection and security event recording in `audit_logs`.

---

## 3. Real-Time Dashboard KPI Metrics

The Industry Admin Dashboard computes 14 real-time operational indicators:

| Metric Indicator | Description | Computation Query Source |
|:---|:---|:---|
| **Profile Completion %** | Completeness score across 14 statutory & branding attributes | `companies` filled attributes / 14 |
| **Total Requirements** | Aggregate demand requisitions logged by this company | `COUNT(*) FROM manpower_requirements WHERE company_id = ?` |
| **Draft Requisitions** | Unsubmitted job drafts undergoing internal review | `status = 'DRAFT'` |
| **Submitted Requisitions** | Requisitions awaiting PRAYAS admin validation | `status IN ('SUBMITTED', 'UNDER_VALIDATION', 'PENDING_REVIEW')` |
| **Approved Requisitions** | Formally validated requisitions approved for sourcing | `status = 'APPROVED'` |
| **Active Sourcing** | Requisitions currently being fulfilled by recruiters | `status = 'RECRUITMENT_IN_PROGRESS'` |
| **Partially Fulfilled** | Requisitions where initial batches joined | `status = 'PARTIALLY_FULFILLED'` |
| **Fulfilled Requisitions** | Requisitions where 100% vacancies placed | `status = 'FULFILLED'` |
| **Total Vacancies** | Cumulative positions requested | `SUM(quantity_required)` |
| **Remaining Requirement** | Open unfilled positions | `MAX(0, total_vacancies - candidates_joined)` |
| **Candidates Matched** | ITI candidates matched by PRAYAS matching engine | `COUNT(cm.id) WHERE mr.company_id = ?` |
| **Candidates Shortlisted**| Candidates advancing past preliminary screening | `cm.status = 'Shortlisted'` |
| **Candidates Interviewed**| Candidates undergoing or completed technical rounds | `cm.status IN ('Scheduled', 'Screened')` |
| **Candidates Joined** | Placed apprentices/technicians reporting to plant | `cm.status = 'Joined'` |

---

## 4. Navigation & Layout Framework

The Industry workspace integrates with the unified layout system (`includes/header.php`, `includes/sidebar.php`, `includes/navbar.php`):
- Navigation menu cleanly partitions **Company Workspace**, **Plant Management**, **Manpower Demands**, **Recruitment Pipeline**, **Notifications**, and **Reports**.
- Responsive layout with Bootstrap 5.3 grids optimized for desktop, tablet, and mobile field operations.

