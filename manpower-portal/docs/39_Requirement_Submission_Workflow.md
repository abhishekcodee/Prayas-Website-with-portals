# 39 — Requirement Submission, State Transitions & Audit Workflow

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Requisition Lifecycle & Audit Ledger Engine  
**Document ID:** DOC-REQ-39  
**Status:** Approved & Implemented  

---

## 1. Requirement State Machine

Every requirement created within the portal transitions through the following approved states:

```mermaid
stateDiagram-v2
    [*] --> DRAFT: Save Draft (Wizard Step 1-7)
    DRAFT --> DRAFT: Resume / Update Draft
    DRAFT --> SUBMITTED: Formal Submit Action
    SUBMITTED --> UNDER_VALIDATION: Validation Pipeline Invoked
    UNDER_VALIDATION --> NEEDS_CORRECTION: Validation Defects Found
    NEEDS_CORRECTION --> DRAFT: Employer Edits Parameters
    UNDER_VALIDATION --> PENDING_REVIEW: Volume > 50 or Unverified Employer
    UNDER_VALIDATION --> APPROVED: Auto-Approval Policy Satisfied
    PENDING_REVIEW --> APPROVED: Super Admin Allocation Signoff
    PENDING_REVIEW --> REJECTED: Disapproved by PRAYAS
    APPROVED --> RECRUITMENT_IN_PROGRESS: Candidates Sourced & Screened
    RECRUITMENT_IN_PROGRESS --> PARTIALLY_FULFILLED: Batches Joined
    PARTIALLY_FULFILLED --> FULFILLED: 100% Vacancies Placed
    APPROVED --> CANCELLED: Employer Cancels Requisition
    RECRUITMENT_IN_PROGRESS --> CANCELLED: Employer Cancels Requisition
    FULFILLED --> CLOSED: Commercial Signoff & Archival
```

---

## 2. Requirement Modification & Cancellation Policies

1. **Modification Rules:**
   - Permitted while in `DRAFT`, `NEEDS_CORRECTION`, or `SUBMITTED`.
   - Modifying an already `APPROVED` requisition resets its status to `UNDER_VALIDATION` or `PENDING_REVIEW` to re-verify operational feasibility.
   - Modifications are permanently blocked once a requirement is `FULFILLED`, `CLOSED`, or `CANCELLED`.

2. **Cancellation Rules:**
   - Industry Admins can cancel active or pending requirements by providing a mandatory cancellation rationale ($\ge 5$ characters).
   - Cancellation transitions the state to `CANCELLED`, halts candidate matching, logs the rationale in `requirement_status_history`, and dispatches an audit event.

---

## 3. Comprehensive Audit Trail & Notification Dispatch

Every transition records an immutable ledger entry in `requirement_status_history` and `audit_logs`:
- **Recorded Event Taxonomy:**
  - `REQUIREMENT_DRAFT_CREATED`
  - `REQUIREMENT_DRAFT_UPDATED`
  - `REQUIREMENT_SUBMITTED`
  - `REQUIREMENT_APPROVED`
  - `REQUIREMENT_CORRECTION_REQUESTED`
  - `REQUIREMENT_CANCELLED`
- **Real-Time Notification Alerts:**
  - When a requirement is auto-approved, an instant in-app notification is dispatched to the employer with the generated `REQ-YYYYMM-XXXX` code.
  - When routed to `PENDING_REVIEW` or `NEEDS_CORRECTION`, explanatory messages guide the user on next steps.

