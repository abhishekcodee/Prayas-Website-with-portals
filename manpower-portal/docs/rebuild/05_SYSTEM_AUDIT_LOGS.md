# Part 05 — Centralized System Audit Logs
**PRAYAS — The Automation Academy**  
*Industry Manpower Requirement Portal*  
**Document Version:** 2.0  
**Phase:** Cycle 2.0 Modernization & Stabilization  

---

## 1. Overview & Regulatory Compliance

The PRAYAS Industry Manpower Requirement Portal maintains a centralized, tamper-evident, append-only security audit ledger stored in `audit_logs`.

The ledger records all state-altering administrative decisions, authentication milestones, quota changes, and route access violations.

---

## 2. Super Admin Tracked Audit Actions

| Action Key | Trigger Context | Details Stored |
| :--- | :--- | :--- |
| `COMPANY_CREATED_BY_ADMIN` | Super Admin registers partner company | Company Name, Verification Status |
| `COMPANY_UPDATED` | Company profile modified | Fields updated |
| `COMPANY_STATUS_CHANGED` | Approval, rejection, review, or suspension | Old Status, New Status, Rejection Reason, Approval Remarks |
| `COMPANY_LIMITS_CHANGED` | Quota capacity adjusted | Old Limits, New Limits |
| `USER_CREATED_BY_ADMIN` | User or Company Admin provisioned | Full Name, Email, Assigned Role, Company Affiliation |
| `USER_UPDATED` | User account modified | Fields updated, Role assigned |
| `USER_STATUS_CHANGED` | User suspended or activated | Target User ID, New Status |
| `USER_PASSWORD_RESET_BY_ADMIN`| Super Admin direct password reset | Target User ID, Flag `must_change_password=1` |
| `SYSTEM_SETTING_UPDATED` | Global platform settings modified | Setting Key, New Value |
| `TENANT_ISOLATION_VIOLATION` | Attempted cross-company IDOR access | Target Company, Auth Company, Request URI |
| `ROUTE_ACCESS_FORBIDDEN` | Unauthorized role access attempted | Role, Request URI, Rejection reason |

---

## 3. Audit Log Inspector Features (`views/super_admin/audit_logs.php`)

1. **Multi-Vector Filtering:**
   - Free-text keyword search (action, IP, details JSON, actor name, company name).
   - Distinct Action Event dropdown.
   - User / Initiator dropdown.
   - Target Company dropdown.
   - Date range selector (`date_from`, `date_to`).
2. **Payload Inspect Modals:** Structured formatting of `details_json` with user agent inspection.
3. **CSV Export:** Filtered audit records can be exported directly via `?export=csv` for compliance and legal archival.
4. **Append-Only Immutability:** Normal application endpoints provide zero facility for updating or erasing audit log records.
5. **PII & Credential Scrubbing:** Passwords, tokens, and sensitive payment data are strictly prohibited from appearing in audit payloads.

