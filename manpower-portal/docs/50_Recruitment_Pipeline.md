# 50. Recruitment Pipeline Management System

## Project Identity
- **Project:** Industry Manpower Requirement Portal
- **Organization:** PRAYAS – The Automation Academy
- **Module:** Recruitment Pipeline (Screening to Joining)
- **Part:** Part 10

---

## 1. Overview & Architecture
The Recruitment Pipeline Engine coordinates the end-to-end recruitment lifecycle for accredited ITI graduates sourced from the PRAYAS Candidate Database.

```
Requirement Approved
       ↓
Candidate Matching (Part 09)
       ↓
Candidate Shortlisting
       ↓
Candidate Screening (Stage 3)
       ↓
Interview Scheduling (Stage 4)
       ↓
Interview Feedback Evaluation (Stage 5 & 6)
       ↓
Candidate Selection Decision (Stage 7)
       ↓
Offer Letter Release (Stage 8)
       ↓
Offer Acceptance / Response (Stage 9)
       ↓
Ground Physical Joining (Stage 10 & 11)
       ↓
Recruitment Closure
```

---

## 2. Standardized Pipeline Stage Taxonomy
The pipeline supports 16 lifecycle stages strictly defined in `candidate_matches.status` and `PipelineController::STAGES`:

| Code | Stage Label | Description | Next Permitted Stages |
| :--- | :--- | :--- | :--- |
| `SOURCED` | **Matched** | Algorithmic match identified | `Shortlisted`, `Rejected` |
| `SHORTLISTED` | **Shortlisted** | Recruiter approved candidate for assessment | `Screened`, `Rejected`, `On Hold` |
| `SCREENING` | **Screened** | Telephonic screening assessment completed | `Scheduled`, `Rejected`, `On Hold` |
| `INTERVIEW_SCHEDULED`| **Scheduled** | Formal technical interview scheduled with client | `Completed`, `Cancelled`, `No Show` |
| `INTERVIEW_COMPLETED`| **Completed** | Evaluation feedback & rating recorded | `Selected`, `Rejected`, `On Hold` |
| `SELECTED` | **Selected** | Official employment approval by industry/recruiter | `Offer Released`, `On Hold` |
| `OFFER_RELEASED` | **Offer Released** | Formal offer letter generated with reference no | `Offer Accepted`, `Offer Declined`, `Withdrawn` |
| `OFFER_ACCEPTED` | **Offer Accepted** | Candidate accepted terms & signed offer | `Joining Pending`, `Joined`, `Withdrawn` |
| `OFFER_DECLINED` | **Offer Declined** | Candidate declined employment offer | `Closed`, `Matched` (alternative req) |
| `JOINING_PENDING` | **Joining Pending** | Candidate in transit / reporting window | `Joined`, `No Show`, `Withdrawn` |
| `JOINED` | **Joined** | Candidate reported on shopfloor & commenced shift | `Closed` |
| `REJECTED` | **Rejected** | Candidate disqualified with mandatory reason | Terminal / Re-match |
| `ON_HOLD` | **On Hold** | Talent reserve / standby status | `Shortlisted`, `Selected`, `Rejected` |
| `WITHDRAWN` | **Withdrawn** | Candidate or employer revoked candidature | Terminal |
| `NO_SHOW` | **No Show** | Candidate failed to report for interview or joining | Terminal |
| `CLOSED` | **Closed** | Requisition fulfilled / recruitment completed | Terminal |

---

## 3. Database Schema
Pipeline data models implemented in Migration 012:
- `recruitment_stage_history`: Immutable chronological audit ledger of all candidate stage transitions.
- `candidate_screening`: Telephonic pre-interview assessment records.
- `interviews`: Scheduled rounds, modes, dates, and venue coordinates.
- `interview_feedback`: Multi-factor 5-point evaluation ratings and recommendation.
- `selections`: Confirmed selection decisions, designations, and target joining dates.
- `offer_letters`: Formally released offer reference numbers, compensation, and validity.
- `joinings`: Ground reporting confirmations, manufacturing units, and onboarding notes.

---

## 4. Multi-Tenant Privacy & Sharing Barrier
1. Recruiters and Super Admins manage candidates internally across all pipeline stages.
2. Industry Employers only see candidates matched to their approved requisitions whose `is_shared_with_industry = 1`.
3. Aadhaar numbers, personal phone numbers, and internal screening notes remain strictly redacted from employer views.

