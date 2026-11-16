# 36 — Multi-Step Requirement Creation Wizard Specification

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** 8-Step Guided Requisition Authoring Wizard  
**Document ID:** DOC-REQ-36  
**Status:** Approved & Implemented  

---

## 1. Wizard Structure & Form Steps

The creation wizard (`views/industry/requirement_form.php`) guides industrial employers through an 8-stage specification pipeline:

### Step 1: General Requirement Information
- **Title / Role Designation:** Minimum 5 characters, e.g. "Junior Robotics Assembly Technician".
- **Company Association:** Read-only, bound strictly to the authenticated employer.
- **Manufacturing Plant:** Dropdown populated exclusively from the company's active registered facilities.
- **Department & Industry Sector:** Internal production department (e.g. "Fabrication Shop 2").
- **Employment Type:** Full Time, Apprenticeship, Fixed Term, or On-the-Job Training (OJT).
- **Hiring Priority:** Low, Medium, High, or Urgent.
- **Requirement Purpose & Description:** Contextual explanation of the hiring demand (minimum 20 characters).

### Step 2: Trade & Vacancy Distribution
- **Multi-Trade Support:** Employers can dynamically add multiple trade positions to a single demand ticket.
- **Per-Trade Fields:**
  - ITI Trade Discipline (Fitter, Electrician, Machinist, Turner, Welder, Instrument Mechanic, etc.).
  - Required Quantity.
  - Educational Qualification & Board Preference (NCVT / SCVT / Either).
  - Passing Batch Year Range (From &mdash; To).
  - Specific Trade Operational Skills.
  - Experience Criteria (Fresher vs Experienced).
- **Automatic Headcount Calculation:** The cumulative vacancy count is dynamically updated on the client and verified on the server.

### Step 3: Candidate Eligibility & Qualifications
- Standard qualification level and board preferences.
- Statutory Age Controls: $\text{min\_age} \ge 18$ (Factories Act compliance); optional $\text{max\_age} \le 60$.
- Key Technical Skills list for automated matching scoring.
- Gender Preference: Default "Any" under Equal Opportunity employment guidelines.

### Step 4: Location & Work Conditions
- Plant City, District, State, and 6-Digit PIN Code.
- Working Hours, Weekly Working Days (5 or 6 days), and Rotational Shift parameters.
- Welfare Facilities: Accommodation (Free/Subsidized/None), Canteen Food (Free/Subsidized/Self), Transport (Company Bus/Subsidized/None), and Personal Protective Equipment (PPE).

### Step 5: Salary & Compensation
- Minimum and Maximum Salary/Stipend values.
- Remuneration Frequency (Monthly Gross, Apprenticeship Monthly Stipend, Annual CTC).
- In-Hand Take-Home estimate.
- Statutory PF & ESIC coverage.
- Overtime (OT) availability, festival bonuses, and career progression terms.

### Step 6: Important Recruitment Dates
- Application Deadline Date ($D_{\text{deadline}} \ge D_{\text{today}}$).
- Interview Start Date & Interview End Date ($D_{\text{interview}} \ge D_{\text{deadline}}$).
- Target Joining Date ($D_{\text{joining}} \ge D_{\text{interview}}$).
- Requirement Expiry Date.

### Step 7: Interview & Logistics Details
- Interview Mode: Plant Campus Walk-in, Face to Face, Virtual Video, or Hybrid.
- Interview Location & Process Guidelines.
- Mandatory Checkpoints: Technical Round, Practical Machine Assessment, Original Document Verification.
- On-Site Recruitment Coordinator: Contact Name, Email, and Phone.

### Step 8: Review & Final Submission
- Consolidated live preview summarizing all parameters.
- Edit previous steps capability.
- Explicit confirmation prompt before submission into the validation pipeline.

---

## 2. Draft Persistence & Interruption Recovery

- Industry Admins can click **"Save Draft & Resume Later"** at any step of the wizard.
- The system serializes the in-progress form inputs into `draft_payload_json` and updates `wizard_step`.
- When resuming from the directory, the wizard automatically restores the user to their last active step with pre-filled inputs.

