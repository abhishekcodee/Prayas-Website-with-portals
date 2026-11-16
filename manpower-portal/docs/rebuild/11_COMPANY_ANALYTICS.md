# Company Consolidation & Performance Analytics
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal

### 1. Functional Scope
The Company Consolidation Analytics module consolidates all operational metrics on an employer-by-employer basis, enabling PRAYAS leadership and Key Account Managers to monitor employer engagement, demand scale, hiring velocity, and placement fulfillment.

### 2. Company Analytics Data Model
Metrics are computed dynamically via `ReportController::getCompanyWiseRequirementReport()`:
- **Company Identity:** Company Legal Name, Sector, City, District, Verification Status.
- **Requisition Volume:** Total requisitions posted, active requisitions, fulfilled requisitions.
- **Vacancy Aggregate:** Total vacancies requested across all manufacturing plants under the company.
- **Sourcing Velocity:** Total matched candidates, shortlisted candidates, and interviews completed.
- **Hiring Velocity:** Total joined candidates and placement fulfillment percentage.

### 3. Multi-Tenant Protection
When invoked by an Industry Admin or Company Admin, the controller strictly injects `:cid = session_company_id`, preventing any employer from viewing competitors' demand data, trade splits, or candidate allocations.
