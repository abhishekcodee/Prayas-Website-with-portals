# 11. Reports & Executive Analytics Documentation

## Project Identity
- **Project:** Industry Manpower Requirement Portal
- **Organization:** PRAYAS – The Automation Academy
- **Module:** Executive Analytics & Reporting Engine
- **Part:** Part 11

---

## 1. Executive Dashboard Architecture

The Executive Analytics Dashboard aggregates real-time metrics across registered industries, manpower requisitions, candidate sourcing, and ground onboarding fulfillment:

1. **Total Industry Companies:** Distinct verified & active employer accounts.
2. **Total Manpower Requirements:** Cumulative requisition volume across all statuses.
3. **Total Vacancies:** Aggregate headcount demand requested by industries.
4. **Active Requirements:** Requisitions in `APPROVED`, `RECRUITMENT_IN_PROGRESS`, and `PARTIALLY_FULFILLED`.
5. **Closed Requirements:** Requisitions in `FULFILLED`, `CLOSED`, and `CANCELLED`.
6. **Total Candidates Matched:** Candidates identified by the Algorithmic Matching Engine.
7. **Candidates Shortlisted:** Sourced candidates approved for further evaluation.
8. **Candidates Interviewed:** Candidates undergoing or completing technical/practical interviews.
9. **Candidates Selected:** Confirmed employment approvals.
10. **Candidates Joined:** Ground reporting confirmations on shopfloor units.
11. **Pending Requirements:** Requisitions in `DRAFT`, `SUBMITTED`, `UNDER_VALIDATION`, `PENDING_REVIEW`.
12. **Requirements Near Deadline:** Active requisitions with `application_deadline` within 7 days.
13. **Requirement Fulfillment Percentage:**
    $$\text{Fulfillment \%} = \left(\frac{\text{Candidates Joined}}{\text{Total Vacancies}}\right) \times 100$$
14. **Average Time to Fulfill (TAT in Days):**
    $$\text{Average TAT} = \frac{1}{N} \sum (\text{Actual Joining Date} - \text{Requirement Creation Date})$$

---

## 2. Reporting Modules Catalog

### A. Manpower Requirement Reports
1. **Requirement-Wise Vacancy Report:** Granular tracking of each requisition's vacancies, city, district, deadline, sourced count, shortlisted count, and joined fulfillment.
2. **Company-Wise Requirement Report:** Consolidated employer requisition volume, active vs. fulfilled breakdown, and candidate hiring count.
3. **Trade-Wise Requirement Report:** Demand aggregations across ITI technical trades (Fitter, Electrician, Machinist, Welder, COPA, Turner).
4. **Batch-Wise Requirement Report:** Cohort-based passing year demand windows.
5. **Plant/Location-Wise Requirement Report:** Facility-level demand across manufacturing units and MIDC industrial zones.
6. **Pending & Fulfilled Status Report:** Aggregates requisitions across all state machine lifecycle phases.

### B. Candidate Reports
1. **Candidate Sourcing Consolidated Report:** Comprehensive candidate profiles with 7-dimension subscores and matching tier.
2. **ITI-Wise Candidate Sourcing Report:** Institute-level distribution queried safely from external candidate repositories.
3. **Trade-Wise Candidate Report:** Technical trade supply volume, average matching score, and placement counts.
4. **Passing-Year Wise Candidate Report:** Cohort breakdown of student candidates.
5. **Shortlisted Candidate Report:** Candidate pool approved for telephonic and ground evaluation.
6. **Interview & Selection Report:** Schedules, rounds, 5-point evaluation ratings, and designations.
7. **Joining Status Report:** Actual reporting dates, plant unit assignments, and onboarding notes.

### C. Recruitment Pipeline Reports
1. **Recruitment Funnel Report:** Stage-by-stage attrition from Sourced $\rightarrow$ Shortlisted $\rightarrow$ Interviewed $\rightarrow$ Selected $\rightarrow$ Joined.
2. **Stage-Wise Candidate Counts:** Active headcount per pipeline stage.
3. **Stage-Wise Conversion Percentage:** Ratios measuring recruitment velocity and conversion efficiency.
4. **Rejected Candidate Report:** Disqualified profiles with recorded rejection rationales.
5. **Dropout Candidate Report:** Captures attrition events (`No Show`, `Withdrawn`, `Offer Declined`).

### D. Industry Reports
1. **Company-Wise Hiring Report:** Velocity metrics for placements and average match scores.
2. **Company-Wise Vacancy Fulfillment:** Percentage of requested headcount physically joined.
3. **Industry Sector Demand:** Demand distributed by sector (Automotive, Heavy Engineering, Electronics, Plastics).
4. **Company Requirement History:** Chronological timeline of all historical requisitions per company.

---

## 3. Interactive Analytics & Visualizations

Utilizing Chart.js (v4.4.1), the system renders 4 interactive visual analytics widgets:
1. **Trade-Wise Vacancy Distribution (Donut Chart):** Visualizes top technical trades demanded by industries.
2. **Monthly Manpower Demand Trend (Bar Chart):** 6-month historical trajectory of requested vacancies.
3. **Recruitment Funnel Progression (Horizontal Bar Chart):** Stage attrition from Sourced to Joined.
4. **Company-Wise Hiring Velocity (Bar Chart):** Top employers by candidates physically joined.

---

## 4. Multi-Tenant Role-Based Access Control

The reporting engine enforces strict multi-tenant isolation:
- **Super Administrator:** Full visibility across all registered companies, consolidated analytics, and cross-company comparisons.
- **Recruiter:** Visibility across candidate sourcing, matching, screening, interviews, and pipeline status.
- **Industry Administrator:** Strict tenant isolation (`company_id` parameter binding). When viewed from `/views/industry/reports.php`, queries are strictly filtered by session `company_id`. Zero foreign company data can be accessed.

---

## 5. Export Functionality

1. **CSV Export:** Streams clean UTF-8 CSV via `php://output` respecting all active filters.
2. **Excel Export (`.xls`):** Generates structured spreadsheet XML/HTML format with formal headers.
3. **PDF / Print View:** Formatted with `@media print` CSS rules, removing navigation and rendering high-contrast tabular reports for print or PDF saving.

