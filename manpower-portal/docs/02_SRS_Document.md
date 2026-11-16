# Software Requirements Specification (SRS)

## Document Identifier: SRS-PRA-2026-V2.0
**Project Title:** Industry Manpower Requirement & ITI Candidate Recruitment Portal  
**Organization:** PRAYAS – The Automation Academy  
**Date of Release:** 2026-11-05  
**Version:** 2.0.0 (Comprehensive Architectural Baseline)  
**Status:** Approved for Architecture & Business Rules Codification  

---

## 1. Project Overview & Organizational Background

**PRAYAS – The Automation Academy** is an industry-integrated vocational excellence institution that addresses the critical gap between technical institutions (ITIs, Polytechnic Colleges) and industrial manufacturing, automotive, automation, robotics, and advanced engineering clusters. 

The **Industry Manpower Requirement Portal** serves as the authoritative, secure, enterprise-grade digital exchange connecting industrial employers with skilled ITI technicians. It automates the complete lifecycle:
- Onboarding and verifying industrial manufacturing partners.
- Ingesting structured manpower requisitions with multi-criteria specifications (Trades, batches, shifts, compensation, amenities).
- Applying deterministic server-side validation and auto-approval workflows.
- Sourcing candidates non-destructively from verified ITI student talent pools (`prayas_iti_dashboard.students` and `itistudents.candidates`).
- Executing an intelligent mathematical matching algorithm ($0 - 100\%$).
- Managing recruitment pipelines: Screening, Interview Scheduling, Evaluation Scoring, Selection, Offer Issuance, and Joining Verification.
- Monitoring partial fulfillment and automated requirement closure.

---

## 2. Project Objectives

1. **Eliminate Sourcing & Retraining Tax:** Deliver shop-floor-ready ITI technicians to manufacturing partners within contracted turnaround timeframes.
2. **Standardize Industry Requisitions:** Enforce rigid data schemas capturing shift rotations, statutory benefits (PF/ESIC), accommodation/canteen amenities, and trade-specific skill requirements.
3. **Automate Validation & Compliance:** Guarantee that 100% of requisitions satisfy regulatory compensation benchmarks and valid calendar milestone chronologies before reaching recruiters.
4. **Non-Destructive Talent Pool Integration:** Tap into existing verified student databases without data duplication, data corruption, or schema mutation.
5. **Real-Time Operational Transparency:** Provide industrial employers with real-time pipeline visibility while isolating private candidate contact details and multi-tenant company records.

---

## 3. Project Scope

### 3.1 In-Scope Capabilities
- Multi-role role-based access control (Super Admin, Industry Admin, Recruitment Admin, Recruiter).
- Company onboarding, document vetting, and Super Admin verification workflow.
- Multi-step Manpower Requirement submission form with instant validation.
- Auto-approval heuristic assigning unique sequential IDs: `REQ-YYYYMM-XXXX`.
- 8-parameter candidate compatibility scoring algorithm.
- Recruiter work order distribution and recruitment pipeline tracking.
- Structured multi-point interview feedback capture.
- Joining verification with mathematical partial fulfillment calculation.
- Granular immutable audit trails and system activity logging.
- Executive analytics, turnaround time metrics, and trade demand-supply reporting.

### 3.2 Out-of-Scope (Future Iterations)
- Direct payroll disbursement or biometric attendance logging (handled by standalone HRMS).
- Third-party video conferencing SDK embedding (interviews store URLs/links to external tools like Google Meet or Zoom).
- Direct SMS gateway billing integration (in-app notifications and email logs are in-scope).

---

## 4. Stakeholders & User Roles

| Role Code | Role Name | System Scope & Authorization Boundary |
| :--- | :--- | :--- |
| `ROLE-SA` | **Super Admin** | Full governance; platform configuration; company verification; user management; audit inspection; override authority across all workflows. |
| `ROLE-IA` | **Industry Admin** | Strictly company-scoped; manages company profile; submits requisitions; tracks own requirement pipeline; views authorized candidate statuses. |
| `ROLE-RA` | **Recruitment Admin**| Operational lead; views approved requisitions; allocates requirements to Recruiters; governs candidate matching pool; pipeline escalation. |
| `ROLE-REC`| **Recruiter / HR** | Execution specialist; manages assigned requisitions; screens candidates; schedules interviews; logs evaluation feedback; tracks joinings. |

---

## 5. Functional Requirements (FR)

### Module 1: Industry Onboarding & Verification
- **FR-001:** The system shall provide an Industry Partner Registration interface capturing Company Name, Registration/CIN/GST, Industry Type, Registered Address, State, District, City, Pincode, HR Contact Name, HR Phone, and HR Email.
- **FR-002:** The system shall enforce unique email and registration number constraints to prevent duplicate company profiles.
- **FR-003:** Upon registration, the system shall assign the company a status of `Pending Verification` and prevent requirement submission until verified.
- **FR-004:** The system shall provide Super Admins with a verification console allowing them to verify, reject (with mandatory reason), or suspend company accounts.
- **FR-005:** The system shall trigger an email/notification to the Industry Admin upon status verification.

### Module 2: Authentication & Access Control
- **FR-006:** The system shall authenticate users via email and password using `PASSWORD_BCRYPT` (cost factor 12).
- **FR-007:** The system shall regenerate session IDs upon login (`session_regenerate_id(true)`) to prevent session fixation attacks.
- **FR-008:** The system shall enforce cryptographic CSRF tokens on all mutable HTTP requests (`POST`, `PUT`, `DELETE`).
- **FR-009:** The system shall isolate company-specific queries using server-side session variable `company_id`.
- **FR-010:** The system shall terminate inactive sessions after 30 minutes of inactivity and require re-authentication.

### Module 3: Manpower Requirement Submission
- **FR-011:** The system shall provide a multi-section submission form capturing:
  - Requirement Title, Description, Quantity Required ($Q \ge 1$).
  - ITI Trades (Multi-select: Electrician, Fitter, Turner, Welder, Machinist, Wireman, COPA, Instrument Mechanic, etc.).
  - Technical Board (NCVT, SCVT, Either).
  - Passing Batch Range (Year From, Year To).
  - Experience Required (Freshers, 1-2 Years, 3+ Years).
  - Required Technical Skills (Tag list).
  - Job Location (State, District, City, Pincode).
  - Minimum Age ($\ge 18$) and Maximum Age.
  - Gender Preference (Any, Male, Female — validated against statutory industrial provisions).
  - Compensation (Minimum Salary, Maximum Salary, Salary Type: Monthly/Stipend).
  - Employment Type (Full Time, Apprenticeship/NAPS, Fixed Term Contract, OJT).
  - Working Hours and Shift Pattern (General Shift, Rotational 3 Shifts).
  - Amenities (Accommodation: Yes/No/Subsidized, Food Facility, Transportation, PF/ESIC).
  - Critical Dates (Submission Date, Application Deadline, Interview Date, Target Joining Date).
  - Priority Level (Low, Medium, High, Urgent).
- **FR-012:** The system shall permit saving requirements in `DRAFT` status without triggering validation.

### Module 4: Automated Validation & Auto-Approval Engine
- **FR-013:** The system shall validate that `quantity_required` is an integer greater than zero.
- **FR-014:** The system shall validate that `min_salary <= max_salary` and both are greater than zero.
- **FR-015:** The system shall validate chronological milestone coherence:
  $$\text{submission\_date} \le \text{application\_deadline} \le \text{interview\_date} \le \text{target\_joining\_date}$$
- **FR-016:** If all validation rules pass and the company status is `Verified`, the system shall automatically approve the requisition and set status to `APPROVED`.
- **FR-017:** Upon auto-approval, the system shall generate a sequential alphanumeric Requirement ID formatted as `REQ-YYYYMM-XXXX`.
- **FR-018:** If any mandatory parameter fails validation, the system shall set status to `NEEDS_CORRECTION`, return detailed field-level error messages, and block approval.
- **FR-019:** The system shall notify the Recruitment Admin team immediately upon requisition approval.

### Module 5: Candidate Sourcing & Matching Algorithm
- **FR-020:** The system shall query active ITI candidate pools across `prayas_iti_dashboard.students` and `itistudents.candidates` via read-only abstraction.
- **FR-021:** The system shall compute a mathematical match score $S \in [0, 100]$ based on Trade (40%), Passing Batch (15%), Location (15%), Skills (10%), Experience (10%), and Salary (10%).
- **FR-022:** The system shall filter out candidates who do not meet mandatory eligibility constraints (Active status, Minimum Age 18).
- **FR-023:** The system shall rank candidates in descending order of match score and provide a breakdown explaining score components.
- **FR-024:** The system shall record candidate matches in `candidate_matches` referencing external IDs without duplicating candidate profile tables.

### Module 6: Recruitment Pipeline Governance
- **FR-025:** Recruitment Admins shall be able to assign approved requirements to one or more Recruiters.
- **FR-026:** Recruiters shall be able to perform candidate screening and record screening notes and status (`Screened`, `Under Review`, `Rejected`).
- **FR-027:** Recruiters shall be able to shortlist screened candidates for interviews.
- **FR-028:** The system shall record interview schedules with Date, Time, Venue/Video Link, and Interviewer instructions.
- **FR-029:** The system shall capture multi-criteria interview evaluation feedback (Technical Aptitude, Practical Knowledge, Attitude, Punctuality) and final recommendation (`Selected`, `Rejected`, `Hold`).
- **FR-030:** For selected candidates, the system shall record selection details, offered designation, offered CTC, and upload or issue offer letters.
- **FR-031:** The system shall record joining verification with actual reporting date, confirmed status (`Joined`, `No Show`, `Offer Declined`), and onboarding remarks.

### Module 7: Partial Fulfillment & Requirement Closure
- **FR-032:** The system shall recalculate remaining vacancies upon every joining confirmation:
  $$\text{Remaining Positions} = \max(0, \text{Quantity Required} - \text{Total Joined})$$
- **FR-033:** When $0 < \text{Total Joined} < \text{Quantity Required}$, the system shall transition status to `PARTIALLY_FULFILLED`.
- **FR-034:** When $\text{Total Joined} \ge \text{Quantity Required}$, the system shall transition status to `FULFILLED`.
- **FR-035:** Authorized administrators shall be able to transition a requirement to `CLOSED` after final reconciliation.

### Module 8: Notifications & Activity Logging
- **FR-036:** The system shall dispatch in-app notifications for requirement submission, approval, interview scheduling, selection, and joining milestones.
- **FR-037:** The system shall record all critical operations in an immutable `audit_logs` table capturing `user_id`, `company_id`, `action`, `ip_address`, `details_json`, and `created_at`.

---

## 6. Non-Functional Requirements (NFR)

- **NFR-001 (Security - Cryptography):** Passwords must be hashed using PHP `password_hash()` with `PASSWORD_BCRYPT` (cost 12).
- **NFR-002 (Security - Injection Defense):** 100% of database queries must use PDO prepared statements with parameter binding. No string concatenation in SQL.
- **NFR-003 (Security - XSS Defense):** All dynamic view outputs must pass through `htmlspecialchars(..., ENT_QUOTES | ENT_HTML5, 'UTF-8')`.
- **NFR-004 (Security - CSRF):** All mutable state requests must validate a 64-character hex CSRF token.
- **NFR-005 (Multi-Tenant Privacy):** An Industry Admin must never be able to read or mutate another company's data under any condition.
- **NFR-006 (Performance - Response Time):** Dashboard views and list views must render in $< 500\text{ ms}$ under normal local server loads.
- **NFR-007 (Performance - Matching Engine):** Candidate matching calculation for pools up to 5,000 candidates must execute in $< 1.5\text{ seconds}$.
- **NFR-008 (Reliability - ACID Compliance):** All multi-table operations (Requirement creation with trade tags; Selection with joining creation) must execute inside database transactions (`beginTransaction()`, `commit()`, `rollBack()`).
- **NFR-009 (Usability - Responsiveness):** The UI must be fully responsive across mobile ($320\text{px}+$), tablet ($768\text{px}+$), and desktop ($1024\text{px}+$) using Bootstrap 5.3.
- **NFR-010 (Auditability):** Audit log entries must be strictly append-only; no `UPDATE` or `DELETE` operations are permitted on `audit_logs`.

---

## 7. System Constraints & Assumptions

1. **Host Environment:** XAMPP on Windows with PHP 8.2+ and MariaDB/MySQL 10.4+.
2. **Framework Freedom:** Pure Core PHP architecture; no third-party framework runtime (No Laravel, Symfony, React, or Node.js runtime).
3. **External Candidate Databases:** `itistudents.candidates` and `prayas_iti_dashboard.students` are read-only.
4. **Candidate Age Limit:** Candidates must be at least 18 years of age at the time of employment in manufacturing plants in accordance with the Factories Act.

---

## 8. User Stories & Acceptance Criteria

### US-01: Industry Requirement Submission
* **As an** Industry Admin,  
* **I want to** submit our factory's ITI technician requirements with specific trade, shift, and compensation terms,  
* **So that** PRAYAS can source qualified technicians matching our shop floor.
* **Acceptance Criteria:**
  1. The form validates all required fields, positive salary, and chronological dates.
  2. If the company is verified, the requirement auto-approves and receives an ID like `REQ-202611-0001`.
  3. If invalid, exact field error messages are highlighted without losing form input.

### US-02: Candidate Matching Engine
* **As a** Recruitment Admin,  
* **I want to** run the matching engine against an approved requirement,  
* **So that** I receive a ranked list of verified ITI candidates scored from 0 to 100%.
* **Acceptance Criteria:**
  1. Candidates matching trade, batch, and location receive high scores ($\ge 80\%$).
  2. Match breakdown explains the exact score contribution of trade, batch, location, and salary.
  3. No changes occur in external candidate tables.

### US-03: Joining & Partial Fulfillment Tracking
* **As a** Recruiter,  
* **I want to** mark a candidate as 'Joined' upon plant reporting,  
* **So that** the portal automatically decrements open vacancies and updates fulfillment progress.
* **Acceptance Criteria:**
  1. Joining records verification date and confirmed status.
  2. Requirement status transitions to `PARTIALLY_FULFILLED` if vacancies remain, or `FULFILLED` if complete.
  3. Company dashboard immediately reflects updated joining numbers.

---

## 9. Error Handling & Exception Standards

1. **Validation Failures:** Return associative array of field-level errors with HTTP 422 (or standard form feedback with preserved input values).
2. **Unauthorized Access:** HTTP 403 Forbidden with audit logging of user ID, IP address, and attempted URI.
3. **Resource Not Found:** HTTP 404 with contextual user-friendly return link.
4. **Database Exception:** Catch `PDOException`, log exact technical details to server log, and display generic safe error message ("A system error occurred. Please try again.").
