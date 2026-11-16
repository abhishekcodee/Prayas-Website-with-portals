# Business Rules Specification (BRD)

## Document Identifier: BRD-PRA-2026-V2.0
**Project Title:** Industry Manpower Requirement & ITI Candidate Recruitment Portal  
**Organization:** PRAYAS – The Automation Academy  
**Date:** 2026-11-05  
**Version:** 2.0.0  
**Status:** Approved Architectural Baseline  

---

## 1. Governance Architecture

This document formalizes all business rules across the 22 operational dimensions ($A$ through $V$) governing the portal. Every business rule follows a standardized specification schema:
- **Rule ID:** Standardized unique identifier (`BR-<CATEGORY>-<SEQ>`).
- **Rule Name:** Concise operational title.
- **Description:** Purpose and context.
- **Conditions:** Pre-requisites and input state.
- **Expected Result:** Target system outcome.
- **Validation Logic:** Deterministic evaluation rules.
- **Exception Handling:** Failure mode and recovery actions.

---

## Category A: Industry Registration

### BR-REG-001: Unique Corporate Identity Rule
* **Rule ID:** `BR-REG-001`
* **Rule Name:** Corporate Entity Uniqueness Verification
* **Description:** Prevents duplicate onboarding of the same industrial entity.
* **Conditions:** An industry user attempts to register an organization.
* **Expected Result:** Organization is registered with status `Pending Verification` if registration number (CIN/LLPIN/GSTIN) and primary HR email are unique.
* **Validation Logic:**
  - `companies.registration_number` must not exist in `companies`.
  - `companies.hr_contact_email` must not exist in `companies` or `users.email`.
* **Exception Handling:** Reject registration; highlight the conflicting field; display message: *"A company or user with this registration number or email already exists. Please sign in or contact PRAYAS administration."*

### BR-REG-002: Mandatory Corporate Geographic Profiling
* **Rule ID:** `BR-REG-002`
* **Rule Name:** Geographic & Contact Completeness
* **Description:** Ensures complete plant and registered office information is captured for statutory and logistics matching.
* **Conditions:** Company registration payload submitted.
* **Expected Result:** Company accepted only if State, District, City, 6-digit Pincode, and 10-digit mobile number are valid.
* **Validation Logic:**
  - `pincode` must match regex `/^[1-9][0-9]{5}$/`.
  - `hr_contact_phone` must match regex `/^[6-9][0-9]{9}$/`.
* **Exception Handling:** Reject submission; mark invalid input fields with actionable validation prompts.

---

## Category B: Company Verification

### BR-VER-001: Pre-Submission Verification Gate
* **Rule ID:** `BR-VER-001`
* **Rule Name:** Commercial Verification Enforcement
* **Description:** Disallows unverified corporate accounts from submitting manpower requisitions.
* **Conditions:** Company account status is `Pending Verification`, `Rejected`, or `Suspended`.
* **Expected Result:** Block navigation to `/views/industry/create_requirement.php` and display a prominent verification banner.
* **Validation Logic:**
  ```php
  if ($company['verification_status'] !== 'Verified') {
      denyAccess("Your company profile is currently pending verification by PRAYAS administration.");
  }
  ```
* **Exception Handling:** User is redirected to `/views/industry/profile.php` with contact information for PRAYAS partner desk.

### BR-VER-002: Verification Auditability
* **Rule ID:** `BR-VER-002`
* **Rule Name:** Super Admin Verification Accountability
* **Description:** Ensures every status transition of a company is bound to an authorized Super Admin with reason logging.
* **Conditions:** Super Admin alters company verification status.
* **Expected Result:** `companies.verification_status` updated; `verified_by`, `verified_at` recorded; if rejected, `rejection_reason` is mandatory.
* **Validation Logic:** Rejection requires `length(rejection_reason) >= 10`.
* **Exception Handling:** Form rejected if reason is blank on rejection; transaction aborted.

---

## Category C: User Registration

### BR-USR-001: Role-Bound User Creation
* **Rule ID:** `BR-USR-001`
* **Rule Name:** Role Constraint on Account Creation
* **Description:** Ensures public registration only creates `industry_admin` accounts. `super_admin`, `recruitment_admin`, and `recruiter` accounts can only be provisioned internally.
* **Conditions:** New user registers via public interface.
* **Expected Result:** Account created with `role_id` explicitly resolving to `industry_admin`.
* **Validation Logic:** Request cannot supply or override `role_id`.
* **Exception Handling:** Hardcode `role_id` resolution in `AuthController::register()`.

---

## Category D: Authentication & Session Security

### BR-AUT-001: Cryptographic Password Verification
* **Rule ID:** `BR-AUT-001`
* **Rule Name:** BCRYPT Password Hashing & Verification
* **Description:** Protects all user credentials from exposure.
* **Conditions:** User attempts login.
* **Expected Result:** Password verified via `password_verify($input, $hash)`.
* **Validation Logic:** Plain text password is never stored or logged.
* **Exception Handling:** Failed verification increments failed attempt counter; generic message: *"Invalid email or password."*

### BR-AUT-002: Session Fixation Defense
* **Rule ID:** `BR-AUT-002`
* **Rule Name:** Mandatory Post-Authentication Session Regeneration
* **Description:** Eliminates session fixation vulnerabilities.
* **Conditions:** User successfully authenticates.
* **Expected Result:** Previous session ID destroyed and new session token generated via `session_regenerate_id(true)`.
* **Validation Logic:** Session ID change verified before initializing user context.
* **Exception Handling:** If regeneration fails, terminate session and prompt re-login.

---

## Category E: Manpower Requirement Submission

### BR-REQ-001: Minimum Headcount Requisition Rule
* **Rule ID:** `BR-REQ-001`
* **Rule Name:** Minimum Vacancy Threshold
* **Description:** Ensures requisitions represent meaningful industrial batches.
* **Conditions:** Requirement form submitted.
* **Expected Result:** Accepted if `quantity_required >= 1` and is an integer.
* **Validation Logic:** `is_int($quantity) && $quantity >= 1`.
* **Exception Handling:** Reject; display *"Requirement must specify at least 1 candidate."*

### BR-REQ-002: Trade & Qualification Association
* **Rule ID:** `BR-REQ-002`
* **Rule Name:** Mandatory ITI Trade Selection
* **Description:** Ensures requirement specifies at least one recognized technical trade.
* **Conditions:** Requisition submission.
* **Expected Result:** Requirement accepted only if $\ge 1$ recognized trade is linked.
* **Validation Logic:** `count($trades) >= 1` where each trade exists in recognized ITI trades list.
* **Exception Handling:** Reject; highlight trade selector: *"Please select at least one ITI trade."*

---

## Category F: Requirement Validation

### BR-VAL-001: Remuneration Range Integrity
* **Rule ID:** `BR-VAL-001`
* **Rule Name:** Compensation Consistency Check
* **Description:** Guarantees minimum salary is less than or equal to maximum salary and exceeds statutory minimums.
* **Conditions:** Salary parameters submitted.
* **Expected Result:** Accepted if `min_salary > 0` and `max_salary >= min_salary`.
* **Validation Logic:**
  $$\text{min\_salary} > 0 \quad \land \quad \text{max\_salary} \ge \text{min\_salary}$$
* **Exception Handling:** Reject; error message: *"Maximum salary must be greater than or equal to minimum salary."*

### BR-VAL-002: Chronological Milestone Coherence
* **Rule ID:** `BR-VAL-002`
* **Rule Name:** Recruitment Timeline Feasibility
* **Description:** Prevents physically impossible or contradictory recruitment dates.
* **Conditions:** Dates submitted for Requisition.
* **Expected Result:** Accepted if dates follow strict chronological sequence:
  $$\text{Current Date} \le \text{Application Deadline} \le \text{Interview Date} \le \text{Target Joining Date}$$
* **Validation Logic:** Convert dates to Unix timestamps; verify $T_{\text{today}} \le T_{\text{deadline}} \le T_{\text{interview}} \le T_{\text{joining}}$.
* **Exception Handling:** Highlight erroneous date field; display exact ordering error.

---

## Category G: Automatic Approval

### BR-APP-001: Autonomous Requirement Approval & ID Generation
* **Rule ID:** `BR-APP-001`
* **Rule Name:** Deterministic Auto-Approval Heuristic
* **Description:** Automatically approves compliant requisitions from verified employers to expedite sourcing.
* **Conditions:** Requisition submitted by a verified company passes 100% of validation rules (`BR-VAL-001`, `BR-VAL-002`, `BR-REQ-001`, `BR-REQ-002`).
* **Expected Result:**
  1. Requisition status set to `APPROVED`.
  2. Sequential ID assigned: `REQ-YYYYMM-XXXX`.
  3. Recruitment Admin notified.
* **Validation Logic:**
  ```php
  if ($company['verification_status'] === 'Verified' && $validationErrors->isEmpty()) {
      $requirement->status = 'APPROVED';
      $requirement->requirement_id = RequirementIDGenerator::generate();
  }
  ```
* **Exception Handling:** If validation fails, set status to `NEEDS_CORRECTION` and save as draft revision.

---

## Category H: Requirement Rejection

### BR-REJ-001: Administrative Requirement Rejection
* **Rule ID:** `BR-REJ-001`
* **Rule Name:** Manual Veto Authority
* **Description:** Allows Super Admin or Recruitment Admin to reject non-viable requisitions.
* **Conditions:** Admin reviews pending or approved requirement and detects commercial/legal conflict.
* **Expected Result:** Status updated to `REJECTED`; notification dispatched to Industry Admin with formal reason.
* **Validation Logic:** Rejection reason text is required ($\ge 15$ characters).
* **Exception Handling:** Rejection blocked without reason text.

---

## Category I: Requirement Modification

### BR-MOD-001: Mutation Boundary by State
* **Rule ID:** `BR-MOD-001`
* **Rule Name:** Requisition Edit Boundary
* **Description:** Protects active recruitment pipelines from unilateral employer alterations.
* **Conditions:** Industry Admin attempts to edit a requirement.
* **Expected Result:**
  - Allowed if status is `DRAFT`, `NEEDS_CORRECTION`, or `APPROVED` (before recruiter assignment).
  - Blocked if status is `RECRUITMENT_IN_PROGRESS`, `PARTIALLY_FULFILLED`, or `FULFILLED`.
* **Validation Logic:**
  ```php
  if (in_array($req['status'], ['RECRUITMENT_IN_PROGRESS', 'PARTIALLY_FULFILLED', 'FULFILLED'])) {
      denyAction("Requirement cannot be modified once recruitment activities have commenced.");
  }
  ```
* **Exception Handling:** Display error banner with option to contact assigned recruitment lead.

---

## Category J: Requirement Cancellation

### BR-CAN-001: Order Cancellation Protocol
* **Rule ID:** `BR-CAN-001`
* **Rule Name:** Controlled Employer Cancellation
* **Description:** Allows companies to withdraw unfulfilled requisitions while logging reasons.
* **Conditions:** Company withdraws requirement before completion.
* **Expected Result:** Status set to `CANCELLED`; any pending interviews marked `Cancelled`; active candidate matches archived.
* **Validation Logic:** Requires employer confirmation and cancellation reason.
* **Exception Handling:** Already fulfilled positions remain verified and immutable.

---

## Category K: Candidate Matching

### BR-MAT-001: Read-Only Candidate Pool Sourcing
* **Rule ID:** `BR-MAT-001`
* **Rule Name:** Non-Destructive Sourcing Abstraction
* **Description:** Guarantees external student databases are never altered during matching.
* **Conditions:** Matching engine executes against `itistudents.candidates` and `prayas_iti_dashboard.students`.
* **Expected Result:** Read-only data ingested; computed scores stored exclusively in `manpower_portal_db.candidate_matches`.
* **Validation Logic:** Database connection user for candidate queries has only `SELECT` privileges on external tables.
* **Exception Handling:** Abort matching if write query attempted on external table.

---

## Category L: Candidate Shortlisting

### BR-SHL-001: Threshold-Based Shortlisting
* **Rule ID:** `BR-SHL-001`
* **Rule Name:** Minimum Match Score for Shortlisting
* **Description:** Prevents dispatching unqualified candidates to employers.
* **Conditions:** Recruiter shortlists candidate from matched pool.
* **Expected Result:** Candidate status updated to `Shortlisted` if candidate match score $S \ge 50\%$ or explicit recruiter override justification is entered.
* **Validation Logic:** If $S < 50\%$, override justification text is mandatory ($\ge 20$ chars).
* **Exception Handling:** Block shortlisting with prompt to supply justification.

---

## Category M: Interview Scheduling

### BR-INT-001: Future-Dated Interview Scheduling
* **Rule ID:** `BR-INT-001`
* **Rule Name:** Interview Schedule Feasibility
* **Description:** Ensures candidates and plant interviewers have reasonable notice.
* **Conditions:** Recruiter schedules interview round.
* **Expected Result:** Interview created if `interview_datetime >= NOW() + 2 hours` and venue/mode is specified.
* **Validation Logic:** `strtotime($datetime) >= time() + 7200`.
* **Exception Handling:** Display error: *"Interview must be scheduled at least 2 hours in advance."*

---

## Category N: Candidate Selection

### BR-SEL-001: Headcount Selection Limit
* **Rule ID:** `BR-SEL-001`
* **Rule Name:** Headcount Selection Ceiling
* **Description:** Prevents selecting more candidates than authorized by the requirement.
* **Conditions:** Candidate recommended for selection.
* **Expected Result:** Selection accepted if:
  $$\text{Current Selections} < \text{Quantity Required} \times 1.2 \quad (\text{permits 20\% offer buffer})$$
* **Validation Logic:** Buffer ratio check against `quantity_required`.
* **Exception Handling:** Alert admin: *"Selection limit reached for this requirement."*

---

## Category O: Offer Letter

### BR-OFR-001: Mandatory Offer Terms
* **Rule ID:** `BR-OFR-001`
* **Rule Name:** Minimum Offer Documentation
* **Description:** Ensures candidates receive verified compensation terms.
* **Conditions:** Offer letter issued/uploaded.
* **Expected Result:** Offer record created with Offered Salary, Designation, Reporting Date, and Offer Validity Date.
* **Validation Logic:** `offered_salary >= req.min_salary` and `reporting_date <= req.target_joining_date + 15 days`.
* **Exception Handling:** Flag discrepancy if offered salary is lower than requirement minimum.

---

## Category P: Joining

### BR-JOI-001: Physical / Formal Joining Verification
* **Rule ID:** `BR-JOI-001`
* **Rule Name:** Ground Reporting Verification
* **Description:** Confirms candidate presence at the plant.
* **Conditions:** Recruiter or Industry Admin marks candidate as `Joined`.
* **Expected Result:** `joinings` record created; actual joining date recorded; candidate marked `Joined` in candidate matches.
* **Validation Logic:** Verification requires joining date $\le \text{Current Date}$.
* **Exception Handling:** Cannot mark joining with a future date.

---

## Category Q: Partial Fulfillment

### BR-FUL-001: Mathematical Fulfillment Accounting
* **Rule ID:** `BR-FUL-001`
* **Rule Name:** Dynamic Vacuum Calculation
* **Description:** Accurately tallies open positions upon every joining event.
* **Conditions:** A candidate is marked `Joined`.
* **Expected Result:**
  $$\text{Remaining Positions} = \max(0, \text{Quantity Required} - \text{Total Joined})$$
  - If $\text{Total Joined} == \text{Quantity Required}$, transition requirement status to `FULFILLED`.
  - If $0 < \text{Total Joined} < \text{Quantity Required}$, transition requirement status to `PARTIALLY_FULFILLED`.
* **Validation Logic:** Aggregate count query on `joinings` WHERE `joining_status = 'Joined'`.
* **Exception Handling:** Re-evaluate and persist status within the same database transaction.

---

## Category R: Requirement Closure

### BR-CLS-001: Formal Requirement Closure
* **Rule ID:** `BR-CLS-001`
* **Rule Name:** End of Life Cycle Closure
* **Description:** Transitions fulfilled or expired requisitions to archival state.
* **Conditions:** Super Admin or Recruitment Admin closes requirement.
* **Expected Result:** Status updated to `CLOSED`; open candidate matches marked `Closed`; audit entry written.
* **Validation Logic:** Can only close if status is `FULFILLED`, `PARTIALLY_FULFILLED`, or `CANCELLED`.
* **Exception Handling:** Disallow closure of active requirement without confirmation.

---

## Category S: Notifications

### BR-NOT-001: Event-Driven Notification Dispatch
* **Rule ID:** `BR-NOT-001`
* **Rule Name:** Real-Time Stakeholder In-App Alerts
* **Description:** Keeps all stakeholders synchronized on pipeline updates.
* **Conditions:** Key state transitions occur (Approved, Interview Scheduled, Selected, Joined).
* **Expected Result:** Record inserted into `notifications` table for relevant recipient user IDs.
* **Validation Logic:** Notification recipient must have active user status.
* **Exception Handling:** Fail gracefully without interrupting primary database transaction.

---

## Category T: Reports & Analytics

### BR-REP-001: Multi-Tenant Report Sanitization
* **Rule ID:** `BR-REP-001`
* **Rule Name:** Tenant Filter on Analytical Reports
* **Description:** Guarantees companies cannot see aggregate statistics of competitor industries.
* **Conditions:** Industry user requests export or views reporting dashboard.
* **Expected Result:** Query strictly bounded by `company_id = :session_cid`.
* **Validation Logic:** Global analytics endpoints reject non-Super-Admin roles.
* **Exception Handling:** Return HTTP 403 for unauthorized report parameter tampering.

---

## Category U: Company Data Isolation

### BR-ISO-001: Strict Server-Side Tenant Boundary
* **Rule ID:** `BR-ISO-001`
* **Rule Name:** Multi-Tenant Isolation Barrier
* **Description:** Fundamental security rule forbidding cross-tenant data access.
* **Conditions:** Any database query executed on behalf of an Industry Admin.
* **Expected Result:** Query strictly forces `company_id = $_SESSION['company_id']`. Form values or URL IDs representing company are strictly ignored.
* **Validation Logic:**
  ```php
  $stmt = $pdo->prepare("SELECT * FROM manpower_requirements WHERE id = :id AND company_id = :cid");
  $stmt->execute(['id' => $reqId, 'cid' => $_SESSION['company_id']]);
  ```
* **Exception Handling:** Log security intrusion attempt; terminate request with HTTP 403.

---

## Category V: Audit Logging

### BR-AUD-001: Non-Repudiation Audit Ledger
* **Rule ID:** `BR-AUD-001`
* **Rule Name:** Immutable Activity Logging
* **Description:** Maintains complete legal and operational traceability.
* **Conditions:** Any state change, approval, rejection, or role assignment.
* **Expected Result:** Insert record into `audit_logs` with `user_id`, `company_id`, `action`, `ip_address`, `details_json`, `created_at`.
* **Validation Logic:** `audit_logs` table has no `UPDATE` or `DELETE` permissions granted.
* **Exception Handling:** If logging fails, capture error in PHP error log; primary transaction is not rolled back unless critical compliance action.
