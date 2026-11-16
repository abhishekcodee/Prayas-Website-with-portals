# 10 — RECRUITMENT PIPELINE ARCHITECTURE & LIFECYCLE
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal
### Cycle 2.0 Modernization & Enterprise Rebuild

---

## 1. Executive Summary

The **PRAYAS Recruitment Pipeline Management System** governs the end-to-end recruitment journey of ITI candidates matched against authorized industry manpower requirements. Spanning from algorithmic sourcing through screening, interview evaluations, selection, formal offer letter issuance, and physical ground joining confirmation, the pipeline serves as the operational operational backbone connecting vocational talent from PRAYAS Academy with manufacturing plant demand.

The architecture enforces strict state machine rules across a 16-stage taxonomy, provides enterprise dashboards for both recruiters and industry employers, maintains immutable audit trails of all status transitions, and enforces multi-tenant data boundaries with Digital Personal Data Protection (DPDP) candidate privacy compliance.

---

## 2. Core Architectural Pillars

```
+---------------------------------------------------------------------------------------------------+
|                                     PRAYAS RECRUITMENT DESK                                       |
+---------------------------------------------------------------------------------------------------+
|  1. Matched (Sourced)                                                                             |
|      v                                                                                            |
|  2. Shortlisted  -----------------------------> [Bulk Action Engine: Advance / Share / Hold]      |
|      v                                                                                            |
|  3. Screened (Telephonic & Relocation Vetting)                                                    |
|      v                                                                                            |
|  4. Scheduled (Technical & Practical Rounds)                                                      |
|      v                                                                                            |
|  5. Completed (Multi-Factor Evaluator Ratings: Technical, Practical, Safety, Communication)       |
|      v                                                                                            |
|  6. Selected (Designation, CTC, Joining Target)                                                   |
|      v                                                                                            |
|  7. Offer Released (Unique Ref: OFFER-YYYYMM-XXXX, Statutory Benefits, Plant Terms)               |
|      v                                                                                            |
|  8. Offer Accepted / Offer Declined                                                               |
|      v                                                                                            |
|  9. Joining Pending (Transit & Document Verification)                                             |
|      v                                                                                            |
|  10. Joined (Physical Plant Shift Commencement -> Triggers Requirement Fulfillment Calculation)   |
|      v                                                                                            |
|  Terminal & Exception States: Rejected | On Hold | Withdrawn | No Show | Closed                  |
+---------------------------------------------------------------------------------------------------+
```

---

## 3. System Interfaces & Consoles

1. **Recruiter Pipeline Console (`views/recruiter/recruitment_pipeline.php`)**:
   - Central recruitment management cockpit for PRAYAS recruitment administrators and talent desk officers.
   - Comprehensive multi-factor filtering: Target Requisition, Client Company, 16 Stages, Trade, Candidate Search.
   - Real-time 6-pillar Funnel Metrics KPI Bar (Sourced, Shortlisted, Screened, Interviewed, Selected, Joined).
   - Safe Bulk Action Toolbar with live selected counters and transactional execution.
   - Stage action modals with pre-populated candidate telemetry.
   - Visual 16-Stage Audit Trail Modal with AJAX lazy-loader.

2. **Industry Recruitment Dashboard (`views/industry/recruitment_pipeline.php`)**:
   - Secure employer console scoped strictly to the authenticated company's manufacturing plants and active requisitions.
   - 14 database-backed KPI statistics covering candidate pools, screening, interviews, offers, and ground joinings.
   - 16-stage funnel progression visualization.
   - Upcoming activity widgets: scheduled interviews (next 7 days) and approaching milestones.
   - Plant-wise and trade-wise fulfillment progress bars.
   - Candidate listing with DPDP-compliant PII masking (`PRAYAS-ITI-XXXX`).
   - Direct interview feedback evaluation and ground onboarding confirmation.

3. **Formal Offer Letter Dossiers (`views/recruiter/offer_letter_view.php` & `views/industry/offer_letter_view.php`)**:
   - Formal executive offer letter document with print and PDF optimization (`@media print`).
   - Comprehensive appointment terms, monthly compensation, statutory benefits (PF, ESIC), plant reporting location, and verification criteria.
   - Strict tenant boundary checks prevent unauthorized cross-company document access.

---

## 4. Database Schema Alignment

The pipeline operates across existing, pre-aligned relational tables without schema alterations:

| Table Name | Primary Responsibility | Key Fields |
|---|---|---|
| `candidate_matches` | Candidate lifecycle state and scores | `id`, `requirement_id`, `status`, `match_score`, `trade`, `is_shared_with_industry` |
| `candidate_screening` | Pre-interview telephonic vetting | `match_id`, `screening_outcome`, `willing_to_relocate`, `expected_salary`, `screening_notes` |
| `interviews` | Scheduled interview drives | `match_id`, `interview_datetime`, `interview_mode`, `interview_round`, `venue_or_link`, `status` |
| `interview_feedback` | Multi-dimension evaluation ratings | `interview_id`, `technical_rating`, `practical_rating`, `safety_rating`, `recommendation` |
| `selections` | Official appointment decisions | `match_id`, `offered_designation`, `offered_ctc`, `target_joining_date`, `selection_status` |
| `offer_letters` | Formal offer letters | `selection_id`, `letter_reference_no`, `salary_offered`, `validity_date`, `proposed_joining_date` |
| `joinings` | Ground factory reporting | `selection_id`, `actual_joining_date`, `plant_reporting_unit`, `joining_status`, `onboarding_remarks` |
| `recruitment_stage_history` | Immutable 16-stage audit log | `match_id`, `previous_stage`, `new_stage`, `notes`, `changed_by`, `created_at` |
| `candidate_shortlist_actions` | Recruiter shortlisting actions | `match_id`, `action`, `remarks`, `performed_by`, `created_at` |

---

## 5. Security, Isolation & DPDP Compliance

- **Multi-Tenant Scoping**: All queries in employer-facing views explicitly bind `mr.company_id = :cid`. Cross-tenant IDOR attacks are blocked at both SQL and controller layers.
- **Candidate Privacy Protection**: Candidate direct names, phone numbers, Aadhaar numbers, and domestic addresses are completely redacted from industry views prior to authorized release. Unshared matched candidates remain strictly invisible to employers.
- **External Candidate Repositories**: External student databases (`prayas_iti_dashboard.students` [1,292 records] and `itistudents.candidates` [56 records]) remain 100% read-only and immutable.
