# 37 — Server-Side Validation Rules & Auto-Approval Policy

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Deterministic Requirement Validation Engine  
**Document ID:** DOC-REQ-37  
**Status:** Approved & Implemented  

---

## 1. Multi-Stage Validation Architecture

The backend validation engine (`RequirementController::validateRequirement()`) executes a 5-stage verification sequence before any requisition is admitted into the platform:

```
[ Stage 1: Structural Checks ] -> [ Stage 2: Trade & Headcount ] -> [ Stage 3: Chronology ] -> [ Stage 4: Compensation ] -> [ Stage 5: Approval Policy ]
```

### Stage 1: Structural & Field Validation
- `title`: Length between 5 and 255 characters.
- `job_description`: Length $\ge 20$ characters.
- `employment_type`: Must be one of `['Full Time', 'Apprenticeship', 'Fixed Term', 'OJT']`.
- `priority`: Must be one of `['Low', 'Medium', 'High', 'Urgent']`.

### Stage 2: Trade & Headcount Integrity
- Trades array must contain $\ge 1$ item with valid trade name and vacancies $\ge 1$.
- Cumulative vacancy check: $\text{quantity\_required} == \sum \text{trade\_vacancies}$.
- Quantity upper ceiling: $1 \le \text{quantity\_required} \le 500$.

### Stage 3: Age & Statutory Eligibility
- Statutory Age: $\text{min\_age} \ge 18$ (Factories Act safety baseline).
- Age Range: If $\text{max\_age}$ provided, $\text{min\_age} \le \text{max\_age} \le 60$.
- Certification: Must specify `NCVT`, `SCVT`, or `Either`.

### Stage 4: Compensation & Salary Bounds
- Positive Remuneration: $\text{min\_salary} > 0$.
- Salary Monotonicity: $\text{min\_salary} \le \text{max\_salary}$.
- Benchmark Warning: If $\text{min\_salary} < 12,000\text{ INR}$, log advisory warning.

### Stage 5: Chronological Feasibility
Let $D_{\text{today}}$ be today's date, $D_{\text{deadline}}$ application deadline, $D_{\text{interview}}$ interview date, and $D_{\text{joining}}$ target joining date:
$$\text{Feasible} = (D_{\text{today}} \le D_{\text{deadline}}) \land (D_{\text{deadline}} \le D_{\text{interview}}) \land (D_{\text{interview}} \le D_{\text{joining}})$$
- Violation of any date constraint marks the payload `NEEDS_CORRECTION`.

---

## 2. Automatic Approval vs. Review Routing Policy

In strict accordance with the approved business rules, **automatic approval is not granted purely because fields are syntactically valid.**

A requirement transitions to `APPROVED` only if **ALL** the following criteria are met:

| Criterion | Automatic Approval Requirement | Routing if Failed |
|:---|:---|:---|
| **Company Accreditation** | `verification_status` is `Verified` or `Active` | Routed to `PENDING_REVIEW` |
| **Account Standing** | `status` is `Active` (not `Suspended`) | Blocked (`NEEDS_CORRECTION`) |
| **Volume Quota** | $\text{quantity\_required} \le 50$ positions | Routed to `PENDING_REVIEW` |
| **Turnaround Feasibility** | $D_{\text{joining}} - D_{\text{today}} \ge 7 \text{ days}$ | Routed to `PENDING_REVIEW` |
| **Minimum Wage** | $\text{min\_salary} \ge 12,000\text{ INR}$ | Routed to `PENDING_REVIEW` |

If routed to `PENDING_REVIEW`, the requisition is highlighted in the Super Admin console for allocation evaluation.

