# Recruitment Funnel & Conversion Analytics
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal

### 1. 16-Stage Taxonomy Alignment
In accordance with Part 10 architecture (`docs/50_Recruitment_Pipeline.md`), candidate progression follows the approved 16-stage taxonomy:
1. `Matched`
2. `Shortlisted`
3. `Screened`
4. `Aptitude Test`
5. `Technical Test`
6. `Scheduled`
7. `Completed`
8. `Feedback Given`
9. `Evaluated`
10. `Selected`
11. `Offer Released`
12. `Offer Accepted`
13. `Joining Pending`
14. `Joined`
15. `Rejected`
16. `Withdrawn`

### 2. Funnel Efficiency & Conversion Ratios
The conversion analytics engine (`ReportController::getStageWiseConversionRates()`) monitors:
- **Shortlist Rate %:** `(Shortlisted / Total Sourced) * 100`
- **Interview Conversion Rate %:** `(Interviewed / Total Sourced) * 100`
- **Selection Ratio %:** `(Selected / Interviewed) * 100`
- **Offer Acceptance Rate %:** `(Accepted / Released) * 100`
- **Joining Conversion Rate %:** `(Joined / Selected) * 100`

### 3. Attrition & Dropout Analytics
- **Disqualified / Rejected Report (`getRejectedCandidateReport`):** Documents candidates disqualified during screening or interviews along with mandatory rejection reasons.
- **Dropouts & No-Shows Report (`getDropoutCandidateReport`):** Tracks candidates who withdrew, failed to appear for interviews, or declined offer letters, capturing documented root causes.
