# Part 08 — Comprehensive Validation Rule Catalog

**Project:** PRAYAS – The Automation Academy | Industry Manpower Requirement Portal  
**Document Code:** `PRY-REBUILD-08-RULE-CATALOG`  
**Classification:** Business Rules & Data Dictionary  
**Algorithm Reference:** `ALG-VAL-2026-V1.0`  

---

## 1. Catalog Summary

The PRAYAS Requirement Validation Engine evaluates 25 distinct rules across 7 core categories. The table below outlines each rule identifier, description, severity level, failure outcome, and suggested remediation.

---

## 2. Category 1: Basic Requisition & Company Verification (`BASIC_INFO`)

| Rule ID | Rule Name | Severity | Evaluation Logic | Suggested Correction |
| :--- | :--- | :--- | :--- | :--- |
| `BR-REG-001` | Company Entity Existence | Critical | Verified employer entity exists in `companies`. | Verify employer accreditation credentials in PRAYAS portal. |
| `BR-REG-003` | Operational Status Check | Critical | Employer `verification_status != 'Suspended'`. | Contact PRAYAS administration to resolve account suspension. |
| `BR-VER-001` | Commercial Verification | Review | Flag if `verification_status` is not Verified or Active. | Submit accreditation documents for Super Admin verification. |
| `ERR-TIT-01` | Title Length Bounds | High | `strlen(title) >= 5 && strlen(title) <= 255`. | Provide an informative job role title between 5 and 255 characters. |
| `ERR-DSC-01` | Description Completeness | Medium | `strlen(job_description) >= 20`. | Detail operating machinery, shift duties, and expected technical work. |
| `ERR-SEC-01` | Industry Sector Mandatory | High | `!empty(industry_sector)`. | Select or specify your industrial manufacturing sector. |
| `ERR-PRI-01` | Priority Classification | Low (Warn) | Priority in `[Low, Medium, High, Urgent]`. | Select one of Low, Medium, High, or Urgent. |
| `ERR-CEM-01` | Contact Email Format | Low (Warn) | If provided, must pass `FILTER_VALIDATE_EMAIL`. | Provide a valid corporate email format (e.g. hr@company.com). |

---

## 3. Category 2: Trade-wise Manpower Demand (`TRADES`)

| Rule ID | Rule Name | Severity | Evaluation Logic | Suggested Correction |
| :--- | :--- | :--- | :--- | :--- |
| `ERR-QTY-01` | Total Headcount Bounds | Critical | `quantity_required >= 1 && quantity_required <= 500`. | Enter total vacancies between 1 and 500. |
| `ERR-TRD-01` | Mandatory Trade Selection | Critical | `count(trades) >= 1`. | Add at least one technical trade row in Step 2 of the wizard. |
| `ERR-TRD-02` | Trade Spec Validity | High | Trade name $\ge 2$ chars, trade vacancies $\ge 1$. | Ensure every trade discipline has a title and $\ge 1$ vacancies. |
| `WARN-TRD-01`| Duplicate Trade Detection | Low (Warn) | No duplicate trade names across rows. | Consolidate duplicate trade allocations into a single row. |
| `ERR-TRD-03` | Trade Sum Parity | Critical | `sum(trade_vacancies) == quantity_required`. | Adjust total required vacancies to match trade allocation sum. |
| `BR-APP-002` | High-Volume Threshold | Review | If `quantity_required > 50`, route to human review. | Super Administration will review batch allocation before sourcing. |

---

## 4. Category 3: Qualification & Educational Criteria (`QUALIFICATION`)

| Rule ID | Rule Name | Severity | Evaluation Logic | Suggested Correction |
| :--- | :--- | :--- | :--- | :--- |
| `ERR-QLF-01` | Qualification Tier Check | Medium (Warn) | Qualification in standard list (`ITI Passed`, `Diploma`, etc.). | Select one of standard tiers: ITI Passed, Diploma, Apprentice. |
| `ERR-BRD-01` | Board Certification | Low (Warn) | Board in `[Either, NCVT, SCVT]`. | Select Either, NCVT, or SCVT certification board. |
| `ERR-YEA-01` | Passing Year From Bounds | High | Year between 2010 and current year + 2. | Set passing year from between 2010 and current year + 2. |
| `ERR-YEA-02` | Passing Year To Bounds | High | Year between 2010 and current year + 2. | Set passing year to between 2010 and current year + 2. |
| `ERR-YEA-03` | Passing Year Chronology | High | `passing_year_to >= passing_year_from`. | Ensure passing year to is $\ge$ passing year from. |

---

## 5. Category 4: Compensation & Statutory Benefits (`SALARY`)

| Rule ID | Rule Name | Severity | Evaluation Logic | Suggested Correction |
| :--- | :--- | :--- | :--- | :--- |
| `ERR-SAL-03` | Remuneration Frequency | High | Salary type in `[Monthly, Stipend, Annual, Daily, Per Hour]`. | Select a valid salary type: Monthly, Stipend, or Annual. |
| `ERR-SAL-01` | Minimum Salary Positive | Critical | `min_salary > 0`. | Specify a valid positive minimum monthly salary amount. |
| `ERR-SAL-02` | Salary Range Inversion | Critical | `max_salary >= min_salary`. | Set maximum salary greater than or equal to minimum salary. |
| `WARN-SAL-01`| Technician Benchmark | Medium (Warn) | Alert if `min_salary < 12000` INR. | Consider raising minimum salary to at least ₹12,000. |

---

## 6. Category 5: Chronology & Milestone Deadlines (`DATES`)

| Rule ID | Rule Name | Severity | Evaluation Logic | Suggested Correction |
| :--- | :--- | :--- | :--- | :--- |
| `ERR-DAT-01` | Application Deadline Future | Critical | `application_deadline >= today`. | Set application deadline to today or a future calendar date. |
| `ERR-DAT-02` | Interview After Deadline | High | `interview_date >= application_deadline`. | Schedule interview on or after application deadline. |
| `ERR-DAT-04` | Interview End Span | High | `interview_end_date >= interview_date`. | Set interview end date on or after interview start date. |
| `ERR-DAT-03` | Target Joining Milestone | High | `target_joining_date >= interview_date`. | Ensure target joining date is after interview/deadline milestones. |
| `WARN-DAT-01`| Turnaround Velocity | Low (Warn) | Alert if `target_joining - today < 7 days`. | Allowing $\ge 7$ days enables standard screening and mobilization. |

---

## 7. Category 6: Plant Facility & Location (`LOCATION`)

| Rule ID | Rule Name | Severity | Evaluation Logic | Suggested Correction |
| :--- | :--- | :--- | :--- | :--- |
| `ERR-LOC-01` | Plant Ownership Check | Critical | Plant must belong to employer company. | Select a manufacturing plant registered under your company. |
| `ERR-LOC-02` | Plant Active Status | High | Plant status must be `Active`. | Activate plant in Plant Management before creating requirements. |
| `ERR-LOC-03` | Location Completeness | Low (Warn) | City/State or registered Plant specified. | Specify city/state deployment location or link a registered plant. |
| `ERR-EMP-01` | Employment Type Validity | Low (Warn) | Type in `[Full Time, Apprenticeship, Fixed Term, OJT]`. | Select Full Time, Apprenticeship, Fixed Term, or OJT. |

---

## 8. Category 7: Recruitment & Statutory Eligibility (`RECRUITMENT`)

| Rule ID | Rule Name | Severity | Evaluation Logic | Suggested Correction |
| :--- | :--- | :--- | :--- | :--- |
| `ERR-AGE-01` | Statutory Age Compliance | Critical | `min_age >= 18` (Factories Act 1948). | Set minimum candidate age to 18 or above for statutory compliance. |
| `ERR-AGE-02` | Age Range Inversion | High | `max_age >= min_age`. | Ensure maximum age is greater than or equal to minimum age. |
| `ERR-INT-01` | Interview Mode Validity | Low (Warn) | Mode in standard industry options. | Select Plant Campus Walk-in, Face to Face, Virtual, or Hybrid. |

