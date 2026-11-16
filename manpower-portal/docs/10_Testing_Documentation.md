# 10 — Testing Documentation & Quality Assurance Plan

## Organization: PRAYAS – The Automation Academy
**Module:** QA & Test Execution Protocol  
**Version:** 1.0.0  

---

## 1. Test Suite Categories

1. **Authentication & Session Security Tests:**
   * Password hashing verification (`password_verify()`).
   * CSRF token enforcement on all POST endpoints.
   * Multi-role authorization barriers (ensuring Industry Admin cannot reach Super Admin routes).
2. **Company Data Isolation Tests:**
   * SQL injection resistance on company identifiers.
   * Verifying that modifying URL parameters or POST bodies does not expose other companies' requirements.
3. **Validation & Business Rule Tests:**
   * Negative candidate count rejection (`quantity_required <= 0`).
   * Chronological sequence validation (ensuring invalid dates are stopped before saving).
   * Salary sanity checks (`min_salary > max_salary` rejection).
4. **Auto-Approval & ID Sequence Generation Tests:**
   * Verification that active verified companies receive auto-approval and sequential IDs.
   * Verification of conflict-free unique ID generation under concurrent requests.
5. **Candidate Matching Engine Tests:**
   * Query execution against `itistudents.candidates` and `prayas_iti_dashboard.students` without altering their schema or data.
   * Accuracy of scoring algorithm (40 trade, 20 year, 15 location, 10 gender, 10 salary, 5 experience).
6. **Recruitment Pipeline & Math Fulfillment Tests:**
   * Verification of partial fulfillment arithmetic: $\text{Remaining} = \text{Total} - \text{Joined}$.
   * State transitions from `Approved` $\to$ `In Progress` $\to$ `Partially Fulfilled` $\to$ `Fulfilled` $\to$ `Closed`.

---

## 2. Test Execution Log Matrix

| Test ID | Module | Scenario | Expected Result | Status |
| :--- | :--- | :--- | :--- | :---: |
| `TC-AUTH-01` | Auth | Valid Super Admin Login | Session initialized, redirected to `/views/super_admin/dashboard.php` | Planned |
| `TC-AUTH-02` | Auth | Unauthenticated access to dashboard | Redirected to `/views/auth/login.php` with error notice | Planned |
| `TC-RBAC-01` | RBAC | Industry Admin accessing Super Admin route | HTTP 403 Forbidden alert and audit log created | Planned |
| `TC-VAL-01`  | Validation | Max salary less than min salary | Form rejected with field error banner | Planned |
| `TC-VAL-02`  | Validation | Target joining date earlier than deadline | Form rejected with date sequence notice | Planned |
| `TC-REQ-01`  | Requirement | Auto-approval of valid requirement | State set to `Approved`, `REQ-YYYYMM-XXXX` assigned | Planned |
| `TC-MAT-01`  | Matching | Sourcing candidates across ITI tables | Ranked candidate matches displayed safely | Planned |
| `TC-FUL-01`  | Pipeline | Candidate marks 'Joined' | Remaining count decrements by 1; math updated | Planned |

