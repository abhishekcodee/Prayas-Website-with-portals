# 23 — Super Admin Dashboard Architecture & Metrics Specification

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Super Admin Command & Analytics Center  
**Document ID:** DOC-SAD-23  
**Status:** Approved & Implemented  

---

## 1. Overview & Purpose

The **Super Admin Dashboard** (`views/super_admin/dashboard.php`) serves as the central administrative intelligence hub for PRAYAS – The Automation Academy. It gives executive administrators complete visibility and governance over platform onboarding, industry company status, manpower requirement lifecycle, candidate sourcing throughput, and recent auditable activities.

---

## 2. Real-Time Dashboard KPI Metrics

The dashboard calculates and renders 12 real-time operational metrics grouped into three executive cards:

### A. Industry & Partner Metrics
1. **Total Registered Industries:** Total count of unique enterprise profiles in `companies`.
2. **Active Verified Industries:** Companies where `verification_status = 'verified'` and `status = 'active'`.
3. **Pending Approval:** Registered companies awaiting document verification (`verification_status = 'pending'`).
4. **Suspended / Rejected:** Non-operational enterprises flagged for compliance or administrative holds (`status = 'suspended'` or `verification_status = 'rejected'`).

### B. Manpower Requirement Metrics
5. **Total Requirements:** Aggregate demand tickets recorded across all industries in `manpower_requirements`.
6. **Pending Review:** Newly submitted job orders in `draft` or `submitted` status awaiting administrative validation.
7. **Approved Requirements:** Validated demands ready for or undergoing candidate sourcing (`approved`).
8. **In Sourcing / Active Recruitment:** Requisitions currently matched and being acted upon by recruiters (`in_progress`).
9. **Fulfilled Requisitions:** Completed requirements (`fulfilled` or `partially_fulfilled`) indicating successful candidate placements.

### C. System & Sourcing Metrics
10. **Total System Users:** All registered identities across all roles in `users`.
11. **Active Recruiters:** Placement coordinators actively driving candidate matching (`role = 'recruiter'` and `status = 'active'`).
12. **Candidate Talent Pool:** Read-only aggregate count from external operational databases (`prayas_iti_dashboard.students` + `itistudents.candidates`), safely computed without mutating external data.

---

## 3. Visual Widgets & Operational Feeds

The dashboard layout is structured using a responsive Bootstrap 5 grid:

```
+-----------------------------------------------------------------------------------+
|  Topbar Navigation (User Info, Notifications, Logout)                             |
+-----------------------------------------------------------------------------------+
|  [Sidebar Navigation]  |  Metric Highlights Grid (12 KPI Cards)                   |
|                        |                                                          |
|                        |  [Chart / Progress Breakdown]    [Industry Breakdown]     |
|                        |  - Requirements by Status        - Verification Status   |
|                        |                                                          |
|                        |  [Recent Company Registrations]  [Recent Requirements]   |
|                        |  - Verification Actions          - Priority & Vacancies  |
|                        |                                                          |
|                        |  [Real-Time System Audit Stream]                         |
+-----------------------------------------------------------------------------------+
```

### 1. Requisition Pipeline Distribution
- Interactive status pill chart visualizing the transition of job demands from `Submitted` $\rightarrow$ `Under Review` $\rightarrow$ `Approved` $\rightarrow$ `In Sourcing` $\rightarrow$ `Fulfilled`.

### 2. Recent Company Onboarding Table
- Displays the 5 latest registered corporate employers, including Company Name, Industry Sector, Registration Date, Verification Status pill, and direct deep links to Company Review (`company_details.php?id=X`).

### 3. Recent Manpower Requisitions Feed
- Shows the latest 5 submitted requirements with Requisition Code (`REQ-YYYYMM-XXXX`), Company, Job Title, Vacancy Count, Deadline, Status, and Action buttons.

### 4. Real-Time Audit Activity Feed
- Real-time stream of the latest 10 system mutations from `audit_logs` showing Initiator, Event Type, Entity Affected, IP Address, and Relative Timestamp.

---

## 4. Query Optimization & Resilience

All dashboard metrics are queried through optimized SQL queries using indexed columns (`status`, `verification_status`, `role_id`, `created_at`).

```sql
-- Single-pass Requisition Metrics Aggregation
SELECT 
    COUNT(*) AS total_reqs,
    SUM(CASE WHEN status IN ('draft', 'submitted') THEN 1 ELSE 0 END) AS pending_reqs,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_reqs,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS active_sourcing,
    SUM(CASE WHEN status IN ('fulfilled', 'partially_fulfilled') THEN 1 ELSE 0 END) AS fulfilled_reqs
FROM manpower_requirements;
```

Candidate pool queries leverage `Database::queryCandidateReadOnly()` with strict read-only guarantees, ensuring the Super Admin dashboard never blocks or modifies candidate records.

