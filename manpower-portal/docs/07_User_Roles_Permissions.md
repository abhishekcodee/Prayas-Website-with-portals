# 07 — User Roles & Permissions Matrix

## Document Identifier: RBAC-PRA-2026-V2.0
**Project Title:** Industry Manpower Requirement & ITI Candidate Recruitment Portal  
**Organization:** PRAYAS – The Automation Academy  
**Access Model:** Role-Based Access Control (RBAC) with Multi-Tenant Scoping  
**Date:** 2026-11-05  

---

## 1. System Role Definitions & Scope

| Role Code | Role Identifier | Primary Scope | Tenant Boundary |
| :--- | :--- | :--- | :--- |
| `ROLE-SA` | `super_admin` | Master Administrative Governance | Unrestricted (Global system visibility across all companies and users). |
| `ROLE-IA` | `industry_admin` | Industrial Employer Representative | **Strictly Isolated** (Confined exclusively to user's assigned `company_id`). |
| `ROLE-RA` | `recruitment_admin`| Operational Recruitment Head | Operations-wide (Manages all verified requirements, recruiter assignments, pipelines). |
| `ROLE-REC`| `recruiter` | Execution Recruiter / HR Specialist | Work-Order Bound (Manages requisitions and candidate matches specifically assigned to them). |

---

## 2. Granular Action Matrix

Every capability in the system is mapped to one of the 8 canonical action primitives:
1. **Create ($C$):** Ability to author and persist new records.
2. **Read ($R$):** Ability to view records (scoped by tenant rules).
3. **Update ($U$):** Ability to modify existing records.
4. **Delete ($D$):** Ability to soft-delete or remove records.
5. **Approve ($A$):** Authority to formally approve an entity or state transition.
6. **Reject ($X$):** Authority to formally veto or reject an entity.
7. **Export ($E$):** Authority to export data as CSV, Excel, or PDF.
8. **Assign ($S$):** Authority to allocate work orders or candidate pipelines.

---

## 3. Comprehensive Permission Matrix

| Module & Entity | Action Primitive | Super Admin (`ROLE-SA`) | Industry Admin (`ROLE-IA`) | Recruitment Admin (`ROLE-RA`) | Recruiter / HR (`ROLE-REC`) | Multi-Tenant Scoping Rule |
| :--- | :---: | :---: | :---: | :---: | :---: | :--- |
| **Companies** | Create | Yes | Self-Register | No | No | Industry self-registration defaults to `Pending`. |
| | Read | Global (All) | Own Company Only | Verified Only | Assigned Companies | Industry query forces `WHERE id = :cid`. |
| | Update | Global | Own Profile Only | No | No | Industry cannot alter verification status. |
| | Delete | Yes (Soft) | No | No | No | Super Admin privilege only. |
| | Approve | Yes | No | No | No | Verification authority reserved for Super Admin. |
| | Reject | Yes | No | No | No | Requires recorded `rejection_reason`. |
| | Export | Global CSV | Own Profile PDF | Verified List | No | Company data isolation strictly enforced. |
| **Users** | Create | Any Role | Company Staff | Recruiters Only | No | Cannot escalate roles. |
| | Read | Global | Company Users | Recruitment Team | Self Profile | Users cannot inspect higher-tier roles. |
| | Update | Any User | Company Staff | Recruiters | Self Profile | Password updates require current password check. |
| | Delete | Yes | Company Staff | No | No | Permanent deletion restricted. |
| **Requirements** | Create | Yes | Own Company | No | No | Verified company status mandatory. |
| | Read | Global | Own Company Only | All Approved | Assigned Only | Cross-company reading strictly blocked. |
| | Update | Global | Draft / Correction | Status updates | Status updates | Cannot edit core terms once sourcing begins. |
| | Delete / Cancel| Yes | Cancel (Own) | Cancel (Internal) | No | Fulfilled vacancies remain immutable. |
| | Approve | Yes (Manual) | Auto-Eligible | Yes (Review) | No | Auto-approved if validation passes. |
| | Reject | Yes | No | Yes | No | Mandatory rejection reason. |
| | Export | Global CSV | Own Requisitions | Pipeline Export | Assigned List | Financial terms isolated to employer. |
| | Assign | Yes | No | Yes (To Recruiter)| No | Distributes requirements to recruiters. |
| **Candidate Matches** | Read | Global | Matched (Filtered)| Global | Assigned Matches | Industry views sanitized profile (no direct phone/address until selected). |
| | Run Matching | Yes | View Results | Yes | Yes (Assigned) | Executes non-destructive matching algorithm. |
| | Screen | Yes | No | Yes | Yes (Assigned) | Records screening rubric notes. |
| | Shortlist | Yes | Acknowledge | Yes | Yes (Assigned) | Moves candidate to interview scheduling. |
| **Interviews** | Create | Yes | Request Date | Yes | Yes | Scheduled with $\ge 2$ hours advance notice. |
| | Read | Global | Own Requisition | Global | Assigned Requisitions| Includes venue, plant contact, or meeting link. |
| | Update | Yes | No | Yes | Yes | Reschedule requires candidate notification. |
| | Feedback | Yes | Plant Feedback | Yes | Yes | 4-point rating rubric. |
| **Selections** | Create | Yes | Recommend | Yes | Yes | Headcount check against quantity required. |
| | Read | Global | Own Requisition | Global | Assigned Requisitions| Captures offered CTC and designation. |
| | Offer Letter | Yes | Upload / Review | Yes | Yes | PDF generation and archival. |
| **Joinings** | Confirm | Yes | Plant Verify | Yes | Yes | Triggers partial fulfillment recalculation. |
| | Read | Global | Own Company | Global | Assigned Requisitions| Live dashboard counters updated. |
| **Reports** | Read / Export | Global Analytics | Company Reports | Sourcing Metrics | Sourcing Metrics | Industry restricted to own hiring velocity. |
| **Audit Logs** | Read / Inspect| Full Inspection | No Access | No Access | No Access | Immutable system audit ledger. |

