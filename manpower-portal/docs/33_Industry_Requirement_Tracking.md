# 33 — Industry Manpower Requirement Tracking Architecture

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Requisition Lifecycle & Fulfillment Tracking  
**Document ID:** DOC-IND-33  
**Status:** Approved & Implemented  

---

## 1. Overview & Capabilities

The **Requirement Tracking Module** (`views/industry/my_requirements.php`, `requirement_details.php`, `create_requirement.php`) gives corporate employers end-to-end visibility into job order validation, recruiter assignments, and hiring progress.

---

## 2. Supported State Machine & Progression

Every requirement progresses through the approved state machine without deviations:

```
[ DRAFT ] 
    |
    v
[ SUBMITTED ] ---> [ UNDER_VALIDATION ] ---> [ APPROVED ] ---> [ RECRUITMENT_IN_PROGRESS ]
                         |                         |                    |
                         v                         v                    v
                 [ NEEDS_CORRECTION ]        [ REJECTED ]      [ PARTIALLY_FULFILLED ]
                                                                        |
                                                                        v
                                                                 [ FULFILLED ]
```

### Self-Approval Prevention Rule
Industry Administrators **CANNOT** approve their own requirements.
Approval transitions are strictly gated to PRAYAS Super Administrators and Recruitment Operations Leads.

---

## 3. Requirement Dossier & Inspection Features

- **Requisition Code Identification:** Structured format `REQ-YYYYMM-XXXX`.
- **Target Trade Breakdown:** Granular vacancy allocation across trades (`requirement_trades`).
- **Remuneration & Perks Transparency:** Stipend/Salary range, accommodation, canteen, transport, and PF/ESIC benefits.
- **Facility Association:** Work location coordinates mapped to specific manufacturing plant units.
- **State History Audit Log:** Full chronological ledger (`requirement_status_history`) showing previous state, new state, user responsible, and rationale.
- **Candidate Funnel:** Real-time counters showing Matched $\rightarrow$ Shortlisted $\rightarrow$ Scheduled $\rightarrow$ Selected $\rightarrow$ Joined.

