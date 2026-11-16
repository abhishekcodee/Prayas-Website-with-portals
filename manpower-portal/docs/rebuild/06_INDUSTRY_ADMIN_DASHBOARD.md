# Part 06 — Industry Admin Dashboard Architecture
**PRAYAS — The Automation Academy**  
*Industry Manpower Requirement Portal*  
**Document Version:** 2.0  
**Phase:** Cycle 2.0 Modernization & Stabilization  

---

## 1. Executive Summary

The Industry Admin Dashboard (`views/industry/dashboard.php`) provides industrial employers with operational oversight across their physical manufacturing facilities, designated coordinators, and active manpower requisitions.

The dashboard enforces strict company-level multi-tenant isolation: an authenticated user sees only data belonging to their session-anchored company ID (`getCompanyId()`).

---

## 2. Summary Cards (Exact 6 Database Metrics)

All summary cards compute real-time metrics dynamically via `IndustryController::getDashboardMetrics()` without hardcoded or placeholder values:

| # | Metric Label | Database Source Query | Business Definition |
| :-: | :--- | :--- | :--- |
| **1** | **Total Manufacturing Plants** | `COUNT(*)` in `manufacturing_plants` where `company_id = :cid` | Total physical plant facilities registered under the corporate entity. |
| **2** | **Active Manufacturing Plants** | `COUNT(*)` in `manufacturing_plants` where `company_id = :cid AND status = 'Active'` | Operational manufacturing units currently open for candidate placements. |
| **3** | **Inactive Manufacturing Plants** | `total_plants - active_plants` | Idle, standby, or decommissioned plant units. |
| **4** | **Industry Team Members** | `COUNT(*)` in `users` where `company_id = :cid AND status = 'Active'` | Total authenticated active team members and plant coordinators. |
| **5** | **Total Departments** | Aggregated unique departments in `system_settings` (`plant_meta_*`) | Distinct production departments (Press, Assembly, CNC, Quality, Tool Room, Maintenance) across all active plants. |
| **6** | **Active Manpower Requirements** | `COUNT(*)` in `manpower_requirements` where `company_id = :cid AND status IN ('APPROVED', 'RECRUITMENT_IN_PROGRESS')` | Live requisitions actively being sourced by PRAYAS academic recruiters. |

---

## 3. Seven Functional Dashboard Sections

### Section 1: Industry Profile Summary Banner
- Displays company legal entity name, assigned industry code (`IND-XXXX`), sector badge, city/state coordinates, and corporate registration number (CIN).
- Interactive profile completion meter (0–100%) tracking statutory completeness.
- Institutional verification badge (`Verified`, `Active`, `Under Review`, `Rejected`, `Suspended`).

### Section 2: Quick Actions Bar
Direct one-click navigation shortcuts tailored to authorized employer tasks:
- **View Industry Profile:** Navigates to [`company_profile.php`](file:///c:/Manpower%20Portal/views/industry/company_profile.php).
- **Manage Manufacturing Plants:** Navigates to [`plants.php`](file:///c:/Manpower%20Portal/views/industry/plants.php).
- **Manage Industry Team:** Navigates to [`team_members.php`](file:///c:/Manpower%20Portal/views/industry/team_members.php).
- **View Notifications:** Navigates to [`notifications.php`](file:///c:/Manpower%20Portal/views/industry/notifications.php).
- **Add Manufacturing Plant:** One-click shortcut to [`plant_form.php`](file:///c:/Manpower%20Portal/views/industry/plant_form.php).
- **Create New Requisition:** One-click shortcut to [`requirement_form.php`](file:///c:/Manpower%20Portal/views/industry/requirement_form.php).

### Section 3: Manufacturing Plant Overview
- Formatted table listing recent manufacturing plant facilities.
- Displays facility code, plant name, plant type, geographic coordinates, primary coordinator, open requirement count, and status pill.
- Direct row-level action triggers to View Dossier and Edit Plant.

### Section 4: Plant Status & Capacity Overview
- Visual gauge displaying operational utilization percentage (`active_plants / total_plants * 100`).
- Summary breakdown of manufacturing sectors, distinct departments, and authorized plant users.
- Quick link to manage all facility units.

### Section 5: Industry Team Overview
- Team roster table displaying active team members, designations, assigned plant facilities, role badges, and account status.
- Direct link to manage team invitations and granular permissions.

### Section 6: Recent Industry Activities (Audit Trail Feed)
- Chronological timeline displaying recent security and administrative operations executed within this company tenant (`audit_logs` where `company_id = :cid`).
- Renders action category, actor user attribution, and relative timestamp.

### Section 7: Relevant Notifications & Requisitions
- Live stream of unread in-app alerts and notifications targeting this company.
- Recent manpower requisitions table with code, job role, vacancies, deadline, and live status.

