# Manpower Demand Reports Catalog
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal

### 1. Overview
The Manpower Demand Reporting suite provides granular demand forecasting and vacancy allocation reports across multiple dimensions.

### 2. Available Demand Reports
1. **Requirement-Wise Vacancy & Progress Report (`getRequirementWiseVacancyReport`):**
   - Tracks individual requisition codes (`REQ-YYYYMM-XXXX`), job titles, employers, locations, total vacancies, status, and pipeline stages (sourced, shortlisted, selected, joined).
   - Real-time fulfillment percentage indicator.

2. **Company-Wise Requirement Consolidation Report (`getCompanyWiseRequirementReport`):**
   - Aggregates demand across all partner companies.
   - Highlights high-volume vs. low-volume hiring industries.

3. **Trade-Wise Demand vs. Supply Report (`getTradeWiseRequirementReport`):**
   - Breaks down demand by ITI Technical Trade (Fitter, Electrician, Machinist, Welder, Turner, COPA, Diesel Mechanic, etc.).
   - Computes Supply Coverage Ratio (`Matched Candidates / Vacancies Demanded`).

4. **Batch & Passing-Year Wise Demand Report (`getBatchWiseRequirementReport`):**
   - Categorizes demand by requested passing year ranges (e.g., 2023–2025).

5. **Plant & Location Wise Demand Report (`getPlantLocationWiseRequirementReport`):**
   - Compares staffing demands across manufacturing plants and industrial corridors (Chakan, Bhosari, Talegaon, Kurkumbh, Ranjangaon, etc.).

6. **Pending vs. Fulfilled Status Report (`getPendingAndFulfilledReport`):**
   - Classifies requisitions by lifecycle state (`DRAFT`, `SUBMITTED`, `APPROVED`, `RECRUITMENT_IN_PROGRESS`, `PARTIALLY_FULFILLED`, `FULFILLED`, `CLOSED`, `CANCELLED`).
