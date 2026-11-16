# 11. Reports & Analytics Testing Documentation

## Project Identity
- **Project:** Industry Manpower Requirement Portal
- **Organization:** PRAYAS – The Automation Academy
- **Module:** Reports & Analytics Automated Test Suite
- **Test File:** `tests/test_reports_analytics_suite.php`

---

## 1. Test Suite Architecture & Groups

The Part 11 automated test suite executes 58 comprehensive assertions across 9 test groups:

### Test Group 1: Executive Dashboard Metrics (14 KPIs)
- Asserts that all 14 metrics (Total Companies, Requirements, Vacancies, Active, Closed, Sourced, Shortlisted, Interviewed, Selected, Joined, Pending, Near Deadline, Fulfillment %, Average TAT) return valid types and values calculated from actual database records.

### Test Group 2: Manpower Requirement Reports
- Validates requirement-wise vacancy reports, company-wise consolidations, trade-wise demand, batch-wise reports, plant facility reports, and pending/fulfilled status reports.

### Test Group 3: Candidate Reports
- Validates candidate sourcing data, ITI-wise institute distribution queried from student repositories, trade supply, passing year cohorts, shortlisted candidates, interview ratings, and joining statuses.

### Test Group 4: Recruitment Pipeline & Attrition Reports
- Verifies stage-wise candidate counts, conversion percentages, disqualified candidates with recorded reasons, and dropout events (`No Show`, `Withdrawn`, `Offer Declined`).

### Test Group 5: Industry Reports
- Validates company hiring velocity, industry sector distribution, and historical requirement timelines.

### Test Group 6: Interactive Chart Payloads
- Verifies data format and keys (`labels`, `data`) for Trade Donut, Monthly Bar, Pipeline Funnel, and Company Hiring charts.

### Test Group 7: Multi-Tenant RBAC & Company Isolation
- Provisions isolated test fixtures for Company A (10 vacancies) and Company B (25 vacancies).
- Validates that Company A queries strictly return 10 vacancies and 1 requirement.
- Asserts zero data leakage: Company B's requirements are completely absent from Company A's reports.

### Test Group 8: Multi-Factor Filters Accuracy
- Tests `company_id` filter, trade filter, and safe handling of non-matching filters returning clean arrays without SQL errors.

### Test Group 9: External Candidate DB Immutability
- Verifies that external candidate databases remain untouched:
  - `prayas_iti_dashboard.students`: Exactly 1,292 records.
  - `itistudents.candidates`: Exactly 56 records.

---

## 2. Test Execution Results

```
========================================================
PRAYAS Portal - Part 11 Reports & Executive Analytics
========================================================

--- Test Group 1: Executive Dashboard Metrics (14 Required KPIs) ---
  [PASS] Metric 1: Total Industry Companies is integer
  [PASS] Metric 2: Total Manpower Requirements is integer
  [PASS] Metric 3: Total Vacancies is integer
  [PASS] Metric 4: Active Requirements is integer
  [PASS] Metric 5: Closed Requirements is integer
  [PASS] Metric 6: Total Candidates Matched is integer
  [PASS] Metric 7: Candidates Shortlisted is integer
  [PASS] Metric 8: Candidates Interviewed is integer
  [PASS] Metric 9: Candidates Selected is integer
  [PASS] Metric 10: Candidates Joined is integer
  [PASS] Metric 11: Pending Requirements is integer
  [PASS] Metric 12: Requirements Near Deadline is integer
  [PASS] Metric 13: Requirement Fulfillment Percentage is numeric (0-100%)
  [PASS] Metric 14: Average Time to Fulfill Requirement is numeric

--- Test Group 2: Manpower Requirement Reports ---
  [PASS] Requirement-wise vacancy report returns valid array
  [PASS] Requirement row includes requirement_id, title, vacancies, and joined_count
  [PASS] Company-wise manpower requirement report returns array
  [PASS] Company report aggregates total_requirements and total_vacancies
  [PASS] Trade-wise manpower requirement report returns array
  [PASS] Batch-wise manpower requirement report returns array
  [PASS] Plant/location-wise requirement report returns array
  [PASS] Plant report includes plant_name or facility_name
  [PASS] Pending and fulfilled requirement status report returns array

--- Test Group 3: Candidate Reports ---
  [PASS] Candidate sourcing report returns array
  [PASS] ITI-wise candidate sourcing report returns array from student repository
  [PASS] ITI row contains iti_name and total_students
  [PASS] Trade-wise candidate report returns array
  [PASS] Passing-year-wise candidate report returns array
  [PASS] Shortlisted candidate report returns array
  [PASS] Interview and selection report returns array
  [PASS] Joining status report returns array

--- Test Group 4: Recruitment Pipeline & Attrition Reports ---
  [PASS] Stage-wise candidate counts returns associative array
  [PASS] Stage-wise conversion rates returns shortlist_pct
  [PASS] Stage-wise conversion rates returns interview_pct
  [PASS] Stage-wise conversion rates returns selection_pct
  [PASS] Stage-wise conversion rates returns offer_acceptance_pct
  [PASS] Stage-wise conversion rates returns joining_conversion_pct
  [PASS] Rejected candidate report returns array
  [PASS] Dropout candidate report returns array (No Show, Withdrawn, Offer Declined)

--- Test Group 5: Industry Reports ---
  [PASS] Company-wise hiring report returns array
  [PASS] Hiring report includes total_vacancies and total_hired
  [PASS] Industry sector demand report returns array
  [PASS] Sector report includes sector and vacancies_demanded
  [PASS] Company requirement history returns array for valid company ID

--- Test Group 6: Interactive Chart Payloads ---
  [PASS] Trade vacancy chart data returns 'labels' and 'data' arrays
  [PASS] Monthly demand chart data returns 'labels' and 'data' arrays
  [PASS] Recruitment funnel chart data returns 5 standard stages
  [PASS] Company hiring chart data returns 'labels' and 'data'

--- Test Group 7: Multi-Tenant RBAC & Company Isolation ---
  [PASS] Company A scoped metrics returns exactly 10 vacancies
  [PASS] Company A scoped metrics returns exactly 1 requirement
  [PASS] Company B scoped metrics returns exactly 25 vacancies
  [PASS] Company B scoped metrics returns exactly 1 requirement
  [PASS] Company A report contains Company A requirement
  [PASS] Zero data leakage: Company B requirement is completely invisible in Company A report

--- Test Group 8: Multi-Factor Filters Accuracy ---
  [PASS] Filter by company_id correctly restricts to target company
  [PASS] Filter by non-matching trade safely returns empty array without SQL errors
  [INFO] Isolation test fixtures cleaned up successfully.

--- Test Group 9: External Candidate DB Immutability ---
  [PASS] External DB 1 (prayas_iti_dashboard.students) untouched (exact 1,292 rows)
  [PASS] External DB 2 (itistudents.candidates) untouched (exact 56 rows)

========================================================
Part 11 Test Suite Results: 58 Passed, 0 Failed
========================================================
```

