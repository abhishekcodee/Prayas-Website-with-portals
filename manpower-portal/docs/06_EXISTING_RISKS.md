# 06 — Technical & Operational Risk Assessment

## Organization: PRAYAS – The Automation Academy
**Document Reference:** DOC-PRA-06-V1  
**Assessment Date:** 2026-11-05  

---

## 1. Technical Risk Matrix

| Risk ID | Risk Description | Severity | Likelihood | Mitigation Strategy |
| :--- | :--- | :---: | :---: | :--- |
| **TR-01** | Accidental mutation or loss of candidate data in external databases (`prayas_iti_dashboard`, `itistudents`) | **CRITICAL** | Very Low | Enforce read-only PDO connection modes for external queries. Execute automated SQL dumps prior to running migrations. |
| **TR-02** | Cross-company data leak (Tenant boundary violation) | **HIGH** | Low | Hardcode server-side isolation using session `company_id` on all industry operations (`WHERE company_id = :session_cid`). Prohibit client-supplied company IDs. |
| **TR-03** | Auto-approval race condition or duplicate Requirement ID generation | **MEDIUM** | Low | Use MySQL transaction isolation with `FOR UPDATE` or lock-free sequential formatting (`REQ-YYYYMM-XXXX`) driven by atomic autoincrement sequence IDs. |
| **TR-04** | Inconsistent candidate recruitment states across pipeline steps | **HIGH** | Low | Implement rigid state machine transition rules in PHP controllers, backed by foreign-key cascading and status audit logs. |
| **TR-05** | SQL Injection or Cross-Site Scripting (XSS) in requirement forms | **HIGH** | Low | 100% PDO prepared statements. All dynamic view outputs passed through `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`. |
| **TR-06** | CSRF attacks on critical actions (Approvals, offer issues) | **HIGH** | Low | Strict CSRF token validation on every `POST`/`PUT`/`DELETE` request via session tokens. |
| **TR-07** | High-latency queries when matching against large candidate pools | **MEDIUM** | Medium | Maintain indexed columns on candidate trades, passing years, districts, and status; query only active records. |

---

## 2. Operational & Business Process Risks

1. **Unverified Industry Submissions:**
   * *Risk:* Malicious or fake industry accounts submitting dummy requisitions.
   * *Mitigation:* Require explicit Super Admin verification of newly registered companies before the manpower submission form is unlocked.
2. **Partial Sourcing Fulfillment Tracking:**
   * *Risk:* Mathematical misalignment between candidate offers, confirmed joinings, and remaining open positions.
   * *Mitigation:* Mathematical formula enforced at the database and model layer:
     $$\text{Remaining Vacancies} = \max(0, \text{Quantity Required} - \text{Total Joined})$$
     Status dynamically transitions to `Partially Fulfilled` or `Fulfilled` based on actual joining confirmations.

