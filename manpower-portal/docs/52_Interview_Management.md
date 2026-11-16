# 52. Interview Management & Technical Evaluation

## Project Identity
- **Project:** Industry Manpower Requirement Portal
- **Organization:** PRAYAS – The Automation Academy
- **Module:** Interview Scheduling & Evaluation Subsystem

---

## 1. Overview
The Interview Management subsystem manages Stage 4 (Scheduling) and Stages 5 & 6 (Evaluation Feedback) of the recruitment pipeline.

---

## 2. Interview Scheduling Protocol
Recruiters schedule interviews with the following constraints:
1. **Temporal Validation:** Interview datetime must strictly be in the future (`interview_datetime > NOW()`). Past scheduling attempts are immediately rejected.
2. **Venue Specification:** Physical shopfloor/center venue address or valid online meeting URL is mandatory.
3. **Interview Modes:**
   - `In-Person`: Plant shopfloor, technical lab, or PRAYAS Academy center.
   - `Online Video`: Google Meet, Zoom, or Microsoft Teams.
   - `Telephonic`: Preliminary technical discussion.
4. **Rounds Taxonomy:**
   - Round 1: Technical Assessment
   - Round 2: Plant Ground Practical / Machine Test
   - Round 3: HR & Cultural Fitment
   - Final Comprehensive Drive

---

## 3. Technical Evaluation & 5-Factor Scoring
The evaluation panel grades candidates on a standardized 1 to 5 scale across 5 distinct competencies:
1. **Technical Theory Rating (1-5):** Engineering concepts, blueprint reading, component specifications.
2. **Practical Machining Rating (1-5):** Machine operation, tool setup, dimensional tolerance holding.
3. **Trade Knowledge Rating (1-5):** Standards, materials, metallurgy, maintenance practices.
4. **Communication Rating (1-5):** Clarity, responsiveness, team interaction.
5. **Industrial Safety Rating (1-5):** PPE compliance, hazard identification, 5S awareness.

### Overall Rating & Recommendations:
- Overall rating: 1 to 5 stars.
- Final Recommendations:
  - `Strongly Recommended`: Advance directly to official selection.
  - `Recommended`: Standard positive recommendation.
  - `Hold`: Standby pool.
  - `Not Recommended`: Transition candidate to `Rejected`.

---

## 4. Automatic Notifications
Scheduling an interview or submitting final evaluation triggers automated in-app notifications to the target Industry Admin with candidate details, date/time, and venue.

