# Interview, Offer & Joining Reports
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal

### 1. Functional Scope
This module tracks the terminal stages of the recruitment lifecycle: telephonic/technical screening, interview rounds, panel evaluations, offer letter issuance, offer acceptance, and physical on-site plant joining.

### 2. Available Reports
1. **Screening, Assessment & Interview Report (`getScreeningAssessmentInterviewReport`):**
   - Telephonic screening outcomes, relocation willingness, and candidate notes.
   - Interview scheduling details, rounds (Technical / HR / Practical), evaluation scores (1–5 scale), and panel recommendations.

2. **Interview & Selection Consolidated Report (`getInterviewSelectionReport`):**
   - Evaluated candidates, ratings, offered designations, offered compensation (CTC), and selection decisions.

3. **Joining Status & Onboarding Report (`getJoiningStatusReport`):**
   - Physical joining records from the `joinings` table.
   - Offer letter reference numbers (`OL-YYYYMM-XXXX`).
   - Actual joining dates, plant reporting unit, delay reasons, and onboarding remarks.
   - Status indicators: `Joined`, `Delayed`, `Did Not Join`, `Pending`.
