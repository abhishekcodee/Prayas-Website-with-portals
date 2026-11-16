# Super Admin Executive Analytics Console
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal

### 1. Executive Mission
The Super Admin Executive Analytics Console (`views/super_admin/reports.php`) gives platform leadership complete cross-sectional visibility into all partner industries, manufacturing plants, trade demands, sourcing funnels, and operational efficiencies.

### 2. 14 Live Database KPI Cards
1. **Total Registered Companies:** Count of all enterprise employer accounts (`companies`).
2. **Total Requisitions:** Count of all submitted and active manpower requirements (`manpower_requirements`).
3. **Active Requisitions:** Requirements currently in active sourcing, screening, or interview phases (`APPROVED`, `RECRUITMENT_IN_PROGRESS`, `PARTIALLY_FULFILLED`).
4. **Pending Review Requisitions:** Requirements awaiting administrative review and approval (`DRAFT`, `SUBMITTED`, `UNDER_VALIDATION`, `PENDING_REVIEW`).
5. **Total Vacancy Demand:** Sum of all quantities demanded across requirements (`quantity_required`).
6. **Active Vacancies:** Sum of open vacancies in active requisitions.
7. **Candidates Joined (Fulfilled):** Verified ground physical joinings at plant sites (`joinings` with status `Joined`).
8. **Total Sourced & Matched:** ITI candidates matched across active requisitions (`candidate_matches`).
9. **Candidates Shortlisted:** Candidates progressed through preliminary shortlisting.
10. **Candidates Interviewed:** Candidates evaluated via technical or HR interview rounds.
11. **Candidates Selected:** Final offers approved for candidate selection.
12. **Offer Release & Acceptance:** Tracking released offer letters and confirmed candidate acceptances (`offer_letters`).
13. **Requirement Fulfillment Rate %:** Calculated as `(Total Joined / Total Vacancies Demanded) * 100`.
14. **Average Turnaround Time (TAT):** Average elapsed days from requirement requisition to physical joining.

### 3. Interactive Chart.js Visualizations (10 Charts)
- **Chart 1: Monthly Demand Trend:** 6-month historical trajectory of demanded vacancies.
- **Chart 2: Trade-Wise Vacancy Distribution:** Donut visualization of top technical ITI trades.
- **Chart 3: Industry Sector Distribution:** Breakdown across Automotive, Engineering, Electronics, Pharma, etc.
- **Chart 4: Plant-Wise Manpower Demand:** Horizontal bar comparing plant-level staffing requirements.
- **Chart 5: Requirement Status Breakdown:** PolarArea chart of current status lifecycle states.
- **Chart 6: Candidate Matching Score Bands:** Distribution across Tier 1 (90-100%) to Tier 5 (<60%).
- **Chart 7: Recruitment Pipeline Funnel:** 5-stage and 16-stage conversion funnels.
- **Chart 8: Monthly Joining Trend:** Time-series of physical student placements.
- **Chart 9: Offer Release vs. Acceptance:** Grouped bar chart monitoring offer acceptance efficiency.
- **Chart 10: Vacancy Fulfillment Trend:** Area chart visualizing fulfillment percentage over time.
