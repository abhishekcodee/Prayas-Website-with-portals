# 27 — Immutable Audit Log System & Compliance Inspection

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Security Ledger & Audit Explorer  
**Document ID:** DOC-AUD-27  
**Status:** Approved & Implemented  

---

## 1. Overview & Regulatory Rationale

The **Audit Log System** (`views/super_admin/audit_logs.php`, `models/AuditLog.php`) maintains an immutable, append-only security ledger of every significant business event and state mutation within the portal.

It provides non-repudiation, administrative accountability, forensic incident investigation, and regulatory compliance with industrial recruitment standards.

---

## 2. Audit Event Schema

Audit logs are recorded in the `audit_logs` table using the following schema:

| Column | Type | Description |
|:---|:---|:---|
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | Primary key |
| `user_id` | `BIGINT UNSIGNED NULL` | Initiator ID (`NULL` for unauthenticated events like failed login attempts) |
| `action` | `VARCHAR(100)` | Standardized event identifier (e.g., `USER_LOGIN_FAILED`, `COMPANY_STATUS_CHANGED`) |
| `entity_type` | `VARCHAR(50)` | Affected domain entity (`company`, `user`, `requirement`, `interview`, `system_setting`) |
| `entity_id` | `BIGINT UNSIGNED NULL` | Primary key of the affected entity |
| `old_values` | `JSON NULL` | Pre-mutation snapshot of modified attributes |
| `new_values` | `JSON NULL` | Post-mutation snapshot of modified attributes |
| `ip_address` | `VARCHAR(45)` | IPv4 / IPv6 of the client request |
| `user_agent` | `VARCHAR(255)` | Browser / client runtime signature |
| `created_at` | `TIMESTAMP` | Immutable timestamp (Server IST) |

---

## 3. Standardized Event Taxonomy

| Module | Event Action Key | Description |
|:---|:---|:---|
| **Auth** | `USER_LOGIN_SUCCESS` | Successful authentication session created |
| | `USER_LOGIN_FAILED` | Invalid credentials submitted |
| | `USER_LOGOUT` | Session intentionally destroyed |
| | `PASSWORD_CHANGED` | User voluntarily updated password |
| | `PASSWORD_RESET_REQUESTED` | Password reset link issued |
| | `PASSWORD_RESET_FINALIZED` | Password reset completed via valid token |
| **Company** | `COMPANY_CREATED` | New company registered |
| | `COMPANY_UPDATED` | Company profile or statutory details modified |
| | `COMPANY_STATUS_CHANGED` | Approval, rejection, or verification change |
| | `COMPANY_SUSPENDED` | Company access blocked |
| **User** | `USER_CREATED` | Account provisioned |
| | `USER_UPDATED` | Profile or contact updated |
| | `USER_STATUS_CHANGED` | Account activated or suspended |
| | `USER_PASSWORD_RESET_ADMIN` | Super Admin reset user password |
| **RBAC** | `ROLE_PERMISSIONS_UPDATED` | Capability mapping altered for role |
| **Requisition** | `REQUIREMENT_CREATED` | New requirement submitted |
| | `REQUIREMENT_STATUS_CHANGED` | State machine transition |
| **Settings** | `SYSTEM_SETTINGS_UPDATED` | Platform configuration changed |

---

## 4. Audit Explorer Features (`audit_logs.php`)

### A. Advanced Search & Filtering
- Filter by **Action Category** (Auth, Company, User, Requisition, Settings).
- Filter by **Initiating User**.
- Filter by **Date Range** (From Date $\rightarrow$ To Date).
- Search query matching action name, IP address, or JSON attributes.

### B. Deep State Inspection Modal
- Administrators can click any log record to open an interactive JSON diff viewer displaying `old_values` vs `new_values` side-by-side.

### C. Live CSV Audit Export
- Super Admins can export filtered audit trails directly to CSV format via `export=csv`.
- The export automatically formats timestamps, resolves User IDs to Full Names and Emails, and structures JSON diffs for external audit analysis.

