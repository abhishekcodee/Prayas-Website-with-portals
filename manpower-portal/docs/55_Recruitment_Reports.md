# 55. Recruitment Pipeline Analytics & Reports

## Project Identity
- **Project:** Industry Manpower Requirement Portal
- **Organization:** PRAYAS – The Automation Academy
- **Module:** Pipeline Reports & Analytics

---

## 1. Overview
The Recruitment Reports subsystem (`views/recruiter/recruitment_reports.php`) aggregates funnel progression, attrition points, conversion rates, and requisition fulfillment health across all active industries and candidates.

---

## 2. Real-Time Conversion Metrics & Mathematical Formulas
The system computes 5 vital recruitment efficiency ratios:
1. **Shortlist Rate (%):**
   $$\text{Shortlist Rate} = \left(\frac{\text{Shortlisted Candidates}}{\text{Total Sourced}}\right) \times 100$$
2. **Interview Rate (%):**
   $$\text{Interview Rate} = \left(\frac{\text{Interviewed Candidates}}{\text{Total Sourced}}\right) \times 100$$
3. **Selection Ratio (%):**
   $$\text{Selection Ratio} = \left(\frac{\text{Selected Candidates}}{\text{Interviewed Candidates}}\right) \times 100$$
4. **Offer Acceptance Rate (%):**
   $$\text{Offer Acceptance Rate} = \left(\frac{\text{Offers Accepted}}{\text{Offers Released}}\right) \times 100$$
5. **Joining Conversion Rate (%):**
   $$\text{Joining Conversion Rate} = \left(\frac{\text{Physically Joined}}{\text{Selected Candidates}}\right) \times 100$$

---

## 3. Requisition-Level Fulfillment Progress
For every requirement, the report displays:
- Requisition Code, Job Title, Employer Name
- Quantity Required (Vacancies Demand)
- Candidates Sourced, Screened, Interviewed, Selected, Offered, Joined
- Current Fulfillment Percentage ($\text{Joined} / \text{Demand} \times 100$)
- Direct link to requisition-filtered pipeline console

---

## 4. CSV Export Capability
Authorised recruiters and administrators can export the complete pipeline report as a structured CSV file:
- Streams directly via `php://output`
- Includes candidate name, trade, batch, requisition code, employer, match score, pipeline stage, interview details, offer reference, compensation, and joining status.

