# 10 — PIPELINE STAGE TAXONOMY & STATE MACHINE MATRIX
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal
### Cycle 2.0 Modernization & Enterprise Rebuild

---

## 1. 16-Stage Lifecycle Taxonomy

The recruitment lifecycle is structured around sixteen (16) standardized stages defined in `PipelineController::STAGES` and matching the MySQL `candidate_matches.status` ENUM definition:

| Sequence | Stage Code | Stage Category | Functional Meaning |
|---|---|---|---|
| **1** | `Matched` | Sourcing | Candidate algorithmically matched against an active approved requirement. |
| **2** | `Shortlisted` | Pre-Selection | Candidate reviewed and selected by recruiter for preliminary screening. |
| **3** | `Screened` | Pre-Selection | Candidate completed telephonic vetting, relocation confirmation, and salary expectation check. |
| **4** | `Scheduled` | Evaluation | Interview date, time, mode, and plant venue/link assigned and confirmed. |
| **5** | `Completed` | Evaluation | Interview rounds conducted and multi-factor evaluation feedback recorded. |
| **6** | `Selected` | Selection | Candidate cleared technical/practical evaluations and approved for hiring. |
| **7** | `Offer Released` | Onboarding | Formal offer letter generated with reference `OFFER-YYYYMM-XXXX` and issued. |
| **8** | `Offer Accepted` | Onboarding | Candidate formally accepted offer terms and agreed to proposed joining date. |
| **9** | `Offer Declined` | Exception | Candidate declined offer due to higher counter-offer, location, or personal constraints. |
| **10** | `Joining Pending` | Onboarding | Candidate in transit, documentation preparation, or awaiting reporting day. |
| **11** | `Joined` | Terminal Success | Candidate physically reported to manufacturing plant and commenced shift duties. |
| **12** | `Rejected` | Terminal Exception | Candidate disqualified during screening, practical trade test, or document verification. |
| **13** | `On Hold` | Auxiliary | Candidate placed in reserve talent pool pending requisition clarification or vacancy adjustments. |
| **14** | `Withdrawn` | Exception | Candidate withdrew application or opted out of recruitment drive. |
| **15** | `No Show` | Exception | Candidate accepted offer or scheduled interview but failed to report. |
| **16** | `Closed` | Terminal Archive | Candidate record archived upon requisition fulfillment or cancellation. |

---

## 2. State Machine Transition Matrix (`ALLOWED_TRANSITIONS`)

The state machine strictly governs valid transitions between stages. Illegal leaps (e.g. `Matched -> Joined` or `Shortlisted -> Selected`) are rejected by `PipelineController::canTransition()`.

```php
private const ALLOWED_TRANSITIONS = [
    'Matched' => [
        'Shortlisted', 'Screened', 'Rejected', 'On Hold', 'Withdrawn'
    ],
    'Shortlisted' => [
        'Screened', 'Scheduled', 'Rejected', 'On Hold', 'Withdrawn'
    ],
    'Screened' => [
        'Scheduled', 'Shortlisted', 'Rejected', 'On Hold', 'Withdrawn'
    ],
    'Scheduled' => [
        'Completed', 'Scheduled', 'No Show', 'Rejected', 'On Hold', 'Withdrawn'
    ],
    'Completed' => [
        'Selected', 'Scheduled', 'Rejected', 'On Hold', 'Withdrawn'
    ],
    'Selected' => [
        'Offer Released', 'Joining Pending', 'Rejected', 'On Hold', 'Withdrawn'
    ],
    'Offer Released' => [
        'Offer Accepted', 'Offer Declined', 'Joining Pending', 'Withdrawn', 'Rejected'
    ],
    'Offer Accepted' => [
        'Joining Pending', 'Joined', 'No Show', 'Withdrawn'
    ],
    'Offer Declined' => [
        'Shortlisted', 'Closed', 'Withdrawn'
    ],
    'Joining Pending' => [
        'Joined', 'No Show', 'Joining Pending', 'Withdrawn', 'Rejected'
    ],
    'Joined' => [
        'Closed'
    ],
    'Rejected' => [
        'Shortlisted', 'Closed'
    ],
    'On Hold' => [
        'Shortlisted', 'Screened', 'Scheduled', 'Selected', 'Closed'
    ],
    'Withdrawn' => [
        'Closed'
    ],
    'No Show' => [
        'Scheduled', 'Joining Pending', 'Closed'
    ],
    'Closed' => []
];
```

---

## 3. Transition Rules & Edge Case Guarantees

1. **Terminal State Immutability**: `Joined` and `Closed` records cannot regress back to pre-screening stages (`Matched`, `Shortlisted`).
2. **Interview Gate Enforcement**: Candidates cannot transition directly from `Shortlisted` to `Selected` without passing an evaluation stage (`Scheduled` / `Completed`).
3. **Audit Trail Completeness**: Every state change executed via `transitionStage()` logs an immutable row in `recruitment_stage_history` capturing `match_id`, `previous_stage`, `new_stage`, `notes`, `changed_by`, and timestamp.
4. **Bypass Flag for Administrative Corrections**: The `bypassCheck` boolean flag exists exclusively for authorized Super Admin corrections with mandatory justification notes.
