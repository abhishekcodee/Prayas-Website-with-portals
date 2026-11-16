# Part 06 — Industry Management Architecture
**PRAYAS — The Automation Academy**  
*Industry Manpower Requirement Portal*  
**Document Version:** 2.0  
**Phase:** Cycle 2.0 Modernization & Stabilization  

---

## 1. Architectural Scope & Industry Governance

The Industry Management module enables institutional administrators and enterprise partners to govern industrial entities, industrial sector categorizations, assigned administrators, and company-affiliated manufacturing networks.

### Hierarchy Model
```
SUPER ADMIN CONSOLE (Universal Platform Governance)
       |
COMPANY / CORPORATE PARENT ENTITY (Legal Entity, CIN, Corporate HQ)
       |
INDUSTRY PARTNER / DIVISION (Sector Focus, E.g., Automotive, Robotics, Tooling)
       |
MANUFACTURING PLANT / FACILITY (Physical Sites, Assembly Lines, CNC Tool Rooms)
       |
AUTHORIZED INDUSTRY USERS & COORDINATORS (Plant Admins, Recruiters, Interviewers)
```

---

## 2. Industry Management Module Specifications

### 2.1 Industry Directory Roster (`views/super_admin/industries.php`)
The Super Admin directory displays all registered industry partner entities with real-time database-backed attributes:
- **Industry ID:** Database primary key identifier (`#ID`).
- **Industry Code:** Standardized unique identifier (`IND-XXXX`).
- **Industry / Company Name:** Legal registered entity name.
- **Industry Sector / Type:** Industrial vertical (e.g. Automotive Manufacturing, Heavy Engineering, CNC Tooling).
- **Primary Contact Person & Designation:** Institutional point-of-contact.
- **Official Email & Phone Number:** Corporate communications channel.
- **Number of Manufacturing Plants:** Real-time query counting active and total manufacturing plants linked to this entity.
- **Assigned Industry Administrator:** Displays primary account administrator (`role_id = 2`).
- **Industry Operational Status:** Status pill (`Active`, `Verified`, `Pending`, `Suspended`).
- **Registration Date:** Timestamp of onboarding.
- **Administrative Actions:** View dossier, edit profile, inspect plants, toggle operational status.

### 2.2 Multi-Vector Filtering Engine
The directory provides simultaneous multi-criteria filtering:
- Keyword full-text search across legal name, registration number, contact person, email, and city.
- Sector dropdown filter (Automotive, Heavy Engineering, CNC, Robotics, Electronics, etc.).
- Operational status filter (`Active`, `Verified`, `Pending`, `Suspended`).
- Clean reset action returning to baseline query state.

---

## 3. Five-Step Industry Registration Workflow (`views/super_admin/industry_form.php`)

The onboarding wizard partitions entity data collection into 5 logical stages:

### Step 1 — Basic Information
- Industry / Legal Company Name
- Industry Code / CIN (Enforces global uniqueness)
- Year of Establishment
- Industry Sector / Vertical Type
- Official Corporate Website URL
- Plant Overview & Description

### Step 2 — Contact Information
- Primary Contact Person
- Corporate Designation
- Official Email Address (Enforces RFC-5322 format)
- Primary Contact Phone Number ($\ge 7$ digits)
- Alternate Contact Number

### Step 3 — Registered Corporate Address
- Address Line 1 & Line 2
- City & Taluka / Sub-District
- District & State
- PIN Code (Strict 6-digit Indian PIN format validation)
- Country (Default: India)

### Step 4 — Industry Administration & Policy
- Initial Verification & Operational Status (`Active`, `Pending`, `Suspended`)
- Administrative Access Policy (Direct linking to user assignment console)

### Step 5 — Review and Submission
- Complete server-side validation.
- Form review before persistent commit.
- Immediate dispatch of in-app notification to Super Admin console upon successful onboarding.

---

## 4. Soft Deactivation Policy

Permanent database deletion (`DROP` / `DELETE`) is strictly prohibited as a normal management action. When an industry is deactivated:
- Status transitions to `Suspended` or `Inactive`.
- All historical manpower requirements, candidate matches, interviews, and on-ground joinings remain permanently connected to the industry ID.
- Access to post new requisitions or schedule candidates is temporarily halted until reactivated.

