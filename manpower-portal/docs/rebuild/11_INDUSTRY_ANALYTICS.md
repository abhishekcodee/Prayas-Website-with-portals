# Industry Partner Analytics Console
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal

### 1. Overview
The Industry Partner Analytics Console (`views/industry/reports.php`) gives authorized Industry Administrators and Team Members secure, real-time insights into their specific organization's manpower requisitions, trade allocations, plant distributions, and candidate sourcing velocity.

### 2. Tenant Boundary & Security
- **Strict Session Scoping:** `$companyId = getCompanyId()` is strictly required. Non-authenticated or cross-tenant attempts trigger an immediate redirect.
- **Candidate Privacy (DPDP Compliance):** In adherence to the Digital Personal Data Protection Act, candidate names are masked as `PRAYAS-ITI-XXXX` (e.g. `PRAYAS-ITI-0042`) in all employer tables, exports, and previews.
- **Formula Injection Defense:** All CSV and Excel exports are processed through `ReportController::sanitizeExportCell()` to neutralize malicious Excel formulas.

### 3. Key Components
1. **Executive KPI Cards:** Company Vacancy Demand, Matched Talent, Evaluated & Selected, Fulfillment Rate %.
2. **Interactive Visualizations:**
   - My Company Vacancies by Technical Trade (Donut chart).
   - Recruitment Pipeline Conversion Funnel (Bar chart).
3. **Plant Demand Breakdown:** Table detailing requisitions, demand, and placements by manufacturing plant facility.
4. **Trade Demand & Placements:** Table comparing trade-level demand vs. sourcing matches.
5. **Requisition Lifecycle Status Table:** Complete listing of all company requisitions with real-time fulfillment progress bars.
