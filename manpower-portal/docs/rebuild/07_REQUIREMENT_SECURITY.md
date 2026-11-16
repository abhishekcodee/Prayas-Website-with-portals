# Part 07 — Requirement Security & Multi-Tenant Isolation
**PRAYAS — The Automation Academy**  
*Industry Manpower Requirement Portal*  
**Document Version:** 1.0  
**Domain:** Security, Isolation & Validation  

---

## 1. Multi-Tenant Scoping Principles

The Manpower Requirement Engine enforces isolation across companies and plants:

```
[Session Auth: getCompanyId()]
          │
          ▼
┌────────────────────────────────────────────────────────┐
│ RequirementController & Backend Isolation Guards       │
│  - company_id always comes from authenticated session  │
│  - plant_id verified against company ownership         │
│  - all SQL queries anchored with WHERE company_id = ?  │
└────────────────────────────────────────────────────────┘
          │
          ├── Company A Requisitions & Drafts
          │     └── Plant A1, Plant A2
          └── Company B Requisitions & Drafts
                └── Plant B1, Plant B2
```

### Core Security Rules

1. **Client Input Distrust:**
   Browser-submitted `company_id` parameters in POST/GET requests are ignored. The controller exclusively binds operations to the session company ID resolved by `getCompanyId()`.

2. **Plant Facility Ownership Verification:**
   Before a requisition draft is saved or formally submitted, the controller verifies plant ownership:
   ```sql
   SELECT id FROM manufacturing_plants WHERE id = :plant_id AND company_id = :company_id
   ```
   If a user from Company A attempts to submit a requirement referencing Plant B, the operation is blocked and rolls back immediately.

3. **Cross-Company Query Isolation:**
   All read operations (`getRequirementById()`, `getRequirements()`, `getRequirementDashboardStats()`) enforce `WHERE mr.company_id = :cid`. Attempts by Company B to query Company A's requirement return `null`.

4. **Cross-Company Mutation Guard:**
   `saveDraft()`, `submitRequirement()`, `cancelRequirement()`, `duplicateRequirement()`, and `updateRequirementStatus()` strictly check:
   ```sql
   SELECT id FROM manpower_requirements WHERE id = :id AND company_id = :cid
   ```
   Foreign modifications are blocked.

---

## 2. Server-Side Validation Rules (Engine `ALG-VAL-2026-V1.0`)

| Rule Code | Category | Condition | Enforcement |
|---|---|---|---|
| `ERR-TIT-01` | Structural | Title length must be 5–255 characters | Rejection |
| `ERR-DSC-01` | Structural | Job description must be $\ge 20$ characters | Rejection |
| `ERR-QTY-01` | Structural | Total vacancies must be $1 \le \text{qty} \le 500$ | Rejection |
| `ERR-TRD-01` | Trades | At least one ITI trade must be assigned | Rejection |
| `ERR-TRD-02` | Trades | Every trade row must have title and vacancies $\ge 1$ | Rejection |
| `ERR-TRD-03` | Trades | Sum of trade vacancies must equal total vacancies | Rejection |
| `ERR-SAL-01` | Salary | Minimum salary must be $> 0$ | Rejection |
| `ERR-SAL-02` | Salary | Maximum salary must be $\ge$ Minimum salary | Rejection |
| `WARN-SAL-01` | Salary | Minimum salary $< ₹12,000$ (Industrial benchmark) | Warning |
| `ERR-AGE-01` | Statutory | Minimum candidate age must be $\ge 18$ (Factories Act) | Rejection |
| `ERR-YEA-03` | Academic | Passing Year To cannot precede Passing Year From | Rejection |
| `ERR-DAT-01` | Chronological | Application deadline cannot be in the past | Rejection |
| `ERR-DAT-02` | Chronological | Interview start date must be $\ge$ Application deadline | Rejection |
| `ERR-DAT-03` | Chronological | Target joining date must be $\ge$ Interview date | Rejection |
| `WARN-DAT-01` | Chronological | Joining date within 7 days | Expedited Warning |
| `ERR-LOC-01` | Facility | Plant must exist and belong to company | Rejection |
| `ERR-LOC-02` | Facility | Selected plant must have status 'Active' | Rejection |

---

## 3. Candidate Database Immutability

The external candidate databases:
- `prayas_iti_dashboard.students` (1,292 records)
- `itistudents.candidates` (56 records)

are kept read-only. The requirement engine writes strictly to `manpower_portal_db` (`manpower_requirements`, `requirement_trades`, `requirement_skills`, `requirement_status_history`, `requirement_sequences`, `audit_logs`).

