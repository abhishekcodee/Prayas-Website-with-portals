# 54. Joining Management & Onboarding Confirmation

## Project Identity
- **Project:** Industry Manpower Requirement Portal
- **Organization:** PRAYAS – The Automation Academy
- **Module:** Ground Physical Joining & Onboarding Verification

---

## 1. Overview
Stages 10 & 11 track the physical presence and reporting of selected candidates at the employer's manufacturing plant facility.

---

## 2. On-Site Reporting Verification
When a candidate arrives on site:
1. Recruiter or plant HR verifier enters actual reporting date.
2. Designates specific plant reporting unit (e.g., "Robotics Assembly Cell B", "Toolroom Unit 1").
3. Records induction observations (biometric punch, PPE issue, safety induction completion).
4. Stage transitions to `Joined`.

---

## 3. Handling Joining Delays & No-Shows
If a candidate does not report on the scheduled date:
- **Joining Delayed:** If the candidate requested an extension (e.g., ITI marksheet verification delay or travel issues), the status is set to `Joining Delayed` and candidate stage transitions to `Joining Pending` with recorded delay reason.
- **No Show:** If the candidate failed to communicate or appear, the status is set to `No Show` and the stage transitions to `No Show`, triggering an alert to the recruiter desk to source a replacement.
- **Did Not Join / Cancelled:** Status transitions to `Withdrawn`.

---

## 4. Requirement Fulfillment Integration
When candidate joining is confirmed (`Joined`), the requirement fulfillment progress counter is updated in real time on both Super Admin and Industry Admin dashboards.

