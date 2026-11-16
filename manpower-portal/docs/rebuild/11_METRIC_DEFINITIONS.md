# Metric Definitions & Mathematical Formulations
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal

### 1. KPI Mathematical Formulas

#### 1. Requirement Fulfillment Rate (%)
$$\text{Fulfillment Rate (\%)} = \left( \frac{\sum \text{Joined Placements}}{\sum \text{Vacancies Demanded}} \right) \times 100$$
- **Numerator:** Count of candidates in `candidate_matches` where `status = 'Joined'` (or `joinings.joining_status = 'Joined'`).
- **Denominator:** `SUM(manpower_requirements.quantity_required)`.

#### 2. Offer Acceptance Rate (%)
$$\text{Offer Acceptance Rate (\%)} = \left( \frac{\text{Accepted Offers}}{\text{Released Offers}} \right) \times 100$$
- **Numerator:** Count of offer letters where `status = 'Accepted'`.
- **Denominator:** Total count of offer letters with status `Released`, `Accepted`, or `Declined`.

#### 3. Shortlist Conversion Rate (%)
$$\text{Shortlist Rate (\%)} = \left( \frac{\text{Shortlisted Candidates}}{\text{Total Sourced Candidates}} \right) \times 100$$

#### 4. Selection Conversion Rate (%)
$$\text{Selection Rate (\%)} = \left( \frac{\text{Selected Candidates}}{\text{Interviewed Candidates}} \right) \times 100$$

#### 5. Joining Conversion Rate (%)
$$\text{Joining Conversion Rate (\%)} = \left( \frac{\text{Joined Candidates}}{\text{Selected Candidates}} \right) \times 100$$

#### 6. Turnaround Time (TAT - Days)
$$\text{TAT}_{\text{Total}} = \text{TAT}_{\text{Sourced}\to\text{Shortlist}} + \text{TAT}_{\text{Shortlist}\to\text{Interview}} + \text{TAT}_{\text{Interview}\to\text{Selection}} + \text{TAT}_{\text{Selection}\to\text{Offer}} + \text{TAT}_{\text{Offer}\to\text{Joining}}$$
- Calculated using `TIMESTAMPDIFF(DAY, start_date, end_date)` across stage timestamps recorded in `recruitment_stage_history`.
