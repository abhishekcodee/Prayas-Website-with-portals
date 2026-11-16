# 53. Offer Management Subsystem

## Project Identity
- **Project:** Industry Manpower Requirement Portal
- **Organization:** PRAYAS – The Automation Academy
- **Module:** Candidate Selection, Offer Generation & Acceptance

---

## 1. Overview
The Offer Management Subsystem encompasses:
- Stage 7: Official Candidate Selection Decision
- Stage 8: Formal Offer Letter Issuance
- Stage 9: Candidate Offer Response Tracking

---

## 2. Selection Decision Workflow
Following successful interview completion, authorized recruiters or industry administrators record the selection decision:
- Confirmed designation (e.g., "Junior CNC Operator / Technician")
- Offered monthly gross CTC / stipend
- Offer issuance date
- Target joining date
- Justification remarks

Upon selection confirmation, candidate stage transitions to `Selected` and an alert is dispatched to the employer.

---

## 3. Offer Letter Generation & Unique Reference Coding
When releasing an offer letter:
- Reference Number Auto-Generation: `OFFER-YYYYMM-XXXX` (where `YYYYMM` is current year/month and `XXXX` is zero-padded selection ID).
- Statutory Benefits Summary: PF, ESIC, Industrial Transport, Subsidized Canteen, PPE Kit.
- Work Location: Manufacturing plant address or facility unit.
- Validity Date: Expiration date for candidate acceptance (default: +7 days).
- Candidate Match Stage transitions to `Offer Released`.

---

## 4. Candidate Offer Response Tracking
Candidate response is recorded through the pipeline console:
- **Accepted:** Candidate signed and confirmed intention to report. Stage transitions to `Offer Accepted` (and automatically queues for joining).
- **Declined:** Candidate opted out or chose another opportunity. Stage transitions to `Offer Declined`, freeing up the requirement vacancy.

