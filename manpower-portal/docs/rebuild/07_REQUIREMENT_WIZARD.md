# Part 07 — Multi-Step Requirement Wizard
**PRAYAS — The Automation Academy**  
*Industry Manpower Requirement Portal*  
**Document Version:** 1.0  
**View Path:** `views/industry/requirement_form.php`  

---

## 1. Overview

The **Multi-Step Requirement Wizard** provides a structured, responsive interface for industrial employers to configure and submit factory technician demands.

The wizard supports both draft creation and progressive completion. Users can save work at any step and resume later. The wizard interface features a visual stepper bar, real-time client-side validation, dynamic sub-forms, and a consolidated summary before submission.

---

## 2. Step Structure & Pane Breakdown

```
[1. General] -> [2. Trades] -> [3. Eligibility] -> [4. Location] -> [5. Salary] -> [6. Dates] -> [7. Interview] -> [8. Review]
```

### Step 1: General Requirement Information
- **Fields:**
  - *Requirement Title / Designation:* Minimum 5 characters, e.g., "CNC Machine Operator & Tool Setter".
  - *Associated Company:* Read-only display of authenticated enterprise name.
  - *Manufacturing Plant Facility:* Mandatory selection from active plants owned by company.
  - *Department / Production Shop:* Internal department (e.g. "Fabrication Shop, Line 2").
  - *Industry Sector:* Auto-filled from company profile.
  - *Employment Type:* "Full Time", "Apprenticeship", "Fixed Term", "OJT".
  - *Hiring Priority:* "Low", "Medium", "High", "Urgent".
  - *Requirement Purpose:* Project rationale or background context.
  - *Job Description:* Minimum 20 characters of detailed responsibilities.

### Step 2: Target ITI Trades & Vacancy Allocation
- Dynamic trade builder with real-time recalculation of total vacancies.
- Allows configuring multiple trade rows with:
  - ITI Trade Discipline dropdown (Fitter, Electrician, Machinist, Welder, etc.).
  - Allocated Vacancies (integer $\ge 1$).
  - Board Certification (Either NCVT/SCVT, NCVT Only, SCVT Only).
  - Passing Batch Range (Year From, Year To).
  - Required Skills per trade.
  - Experience Requirement.

### Step 3: Candidate Requirements & Eligibility
- Educational qualification tier (default: ITI Passed).
- Board certification preference.
- Experience criteria ("Fresher", "1 - 2 Years", "2+ Years").
- Statutory candidate age criteria: Minimum age $\ge 18$ (statutory compliance with Factories Act), maximum age limit.
- Technical skills keywords (comma-separated).
- Gender preference ("Any", "Male", "Female").

### Step 4: Work Location & Plant Conditions
- Plant City, District, State, and PIN code coordinates.
- Shift timing, rotational shift policy, and weekly working days (5 or 6 days).
- On-site candidate facilities: Accommodation, Canteen/Food, Transport, PPE & Safety equipment.

### Step 5: Salary, Compensation & Statutory Benefits
- Minimum Salary ($\ge$ ₹5,000, recommended $\ge$ ₹12,000).
- Maximum Salary ($\ge$ Minimum Salary).
- In-Hand Salary estimate.
- Remuneration type ("Monthly", "Stipend", "Annual").
- Statutory Benefits: PF / ESIC compliance indicator.
- Overtime availability and bonus/incentive details.

### Step 6: Recruitment Timelines & Important Dates
- Application Deadline Date (Must be today or in future).
- Interview Start Date (Must be $\ge$ Application Deadline).
- Interview End Date (Optional).
- Target Joining Date (Must be $\ge$ Interview Date).
- Expiry Date.

### Step 7: Interview Logistics & Recruitment Process
- Interview Mode ("Plant Campus Walk-in", "Face to Face", "Virtual / Online", "Hybrid").
- Number of evaluation rounds (1 to 5).
- Mandatory checkpoints: Technical Interview, Practical Machine Test, Document Verification.
- On-site recruitment coordinator contact details (Name, Email, Phone).
- Detailed selection process guidelines.

### Step 8: Consolidated Review & Submission
- Dynamic compilation of all input sections into a clean structured review card.
- Displays consolidated trade badges, salary range, dates, and facility amenities.
- "Save Draft & Resume Later" preserves progress in `DRAFT` status at the current step.
- "Confirm & Submit Requirement" triggers full server validation, limit checks, and forwards requisition for sourcing.

---

## 3. UI/UX Design System Conformance

- **Color Palette:** Strict usage of PRAYAS Design System colors (`--primary: #EF6B6B`, `--secondary: #475569`, `--navy: #0F172A`).
- **Typography:** Urbanist font family throughout all inputs, badges, and headers.
- **Routing:** All form actions, back links, and redirects utilize `app_url()` to support standard and subdirectory deployments.

