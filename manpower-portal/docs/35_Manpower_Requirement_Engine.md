# 35 — Manpower Requirement Engine Architecture Specification

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Enterprise Requisition Engine & Demand Sourcing System  
**Document ID:** DOC-REQ-35  
**Status:** Approved & Implemented  

---

## 1. Overview & System Mission

The **Manpower Requirement Engine** (`controllers/RequirementController.php`, `views/industry/requirements.php`, `requirement_form.php`, `requirement_details.php`, `requirement_review.php`) serves as the core demand-side intake mechanism for PRAYAS – The Automation Academy.

It transforms informal corporate hiring requests into highly structured, machine-matchable requisition blueprints specifying:
1. Multi-trade technician quantities and qualifications.
2. Technical skill matrices and board certification preferences (NCVT / SCVT).
3. Factory shop floor physical working conditions, shifts, and safety protocols.
4. Transparent remuneration, stipends, and statutory welfare benefits (PF/ESIC).
5. Chronologically feasible recruitment, interview, and joining schedules.

---

## 2. Multi-Tenant Requisition Isolation

- **Absolute Employer Boundary:** Requisition creation, modification, and inspection strictly derive company ownership from `$_SESSION['company_id']`.
- **Frontend Immutability:** Any frontend payload or parameter attempting to pass or alter `company_id` is discarded by the backend controller.
- **Plant Association:** Job requisitions can link directly to registered manufacturing plant facilities (`manufacturing_plants.id`), validating that the facility belongs to the authenticated employer.

---

## 3. Core Functional Capabilities

```
+-----------------------------------------------------------------------------------+
|                           MANPOWER REQUIREMENT ENGINE                             |
+-----------------------------------------------------------------------------------+
|  [Multi-Step Creation Wizard]  -->  [Live Client Validation]                      |
|  - 8 Form Steps                     - Numerical, Date & Trade Rules               |
|  - Multi-Trade Allocation           - Save Draft at Any Step                      |
|                                                                                   |
|  [Server-Side Validation Engine]  -->  [Decision & Auto-Approval Logic]           |
|  - 5-Stage Algorithmic Filter         - Auto-Approved (Clean, Verified, <=50 qty) |
|  - Factories Act (Age >= 18)          - Routed to PENDING_REVIEW (High volume)    |
|  - Chronological Feasibility          - Marked NEEDS_CORRECTION (Data defect)     |
|                                                                                   |
|  [Concurrency-Safe ID Generator]  -->  [Audit Logging & Status Ledger]            |
|  - Sequence: REQ-YYYYMM-XXXX          - requirement_status_history                |
|  - requirement_sequences Table        - System Notifications Dispatch             |
+-----------------------------------------------------------------------------------+
```

### Key Modules:
1. **Multi-Step Guided Wizard:** 8 logically segmented panes with live stepper navigation and dynamic trade addition.
2. **Draft Persistence & Resumption:** In-progress requisitions can be saved as drafts at any stage, stored in `draft_payload_json` and `wizard_step`, and resumed without data loss.
3. **Multi-Trade Allocation:** Multiple ITI disciplines (e.g. 10 Fitters, 5 Electricians, 5 Machinists) configured in a single requisition with dynamic total headcount summation.
4. **Deterministic Approval Routing:** Automatically distinguishes between clean, standard requisitions and high-volume ($>50$) or special-case demands.

