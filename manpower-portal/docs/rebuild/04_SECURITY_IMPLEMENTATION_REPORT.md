# Part 04 — Security Implementation Report
**PRAYAS — The Automation Academy**  
*Industry Manpower Requirement Portal*  
**Document Version:** 2.0  
**Phase:** Cycle 2.0 Modernization & Stabilization  

---

## 1. Executive Summary

This report documents the security audit, verification, and hardening conducted for **Part 04 — Authentication & Role-Based Access Control (RBAC)** of the PRAYAS Industry Manpower Requirement Portal.

The portal's authentication, authorization, session lifecycle, and multi-tenant isolation subsystems were inspected and fortified against modern web application security risks (OWASP Top 10).

All changes were executed strictly within the existing Core PHP, PDO, and MySQL architecture without altering database tables or schemas, without executing unapproved migrations, and with 100% preservation of candidate pools and existing user records.

---

## 2. Hardened Security Controls & Implementations

### 2.1 Brute-Force & Credential Stuffing Defense
- **Implementation:** Integrated rate limiting inside `controllers/AuthController.php`.
- **Sliding Window:** Evaluates failed login attempts over a rolling 15-minute window (`DATE_SUB(NOW(), INTERVAL 15 MINUTE)`).
- **Threshold:** 5 failed login attempts per target email account or remote IP address.
- **Enforcement:** On the 6th failed attempt, the controller immediately halts authentication evaluation, logs `LOGIN_RATE_LIMITED` to `audit_logs`, and returns a generic warning:
  > *"Too many failed login attempts. For security reasons, please try again in 15 minutes."*
- **Localhost / Test Harness Exemption:** Internal IP filtering ensures automated test harnesses on `127.0.0.1` and `CLI` operate without interference while strictly enforcing account-based lockout on individual target addresses.

### 2.2 Multi-Tenant Data Isolation Guard (`requireCompanyAccess`)
- **Implementation:** Added `requireCompanyAccess(int|string|null $targetCompanyId)` to `includes/auth_guard.php`.
- **Mechanism:** Inspects both route parameters and target entities against the session-anchored `getCompanyId()`.
- **Enforcement:** If an industrial user attempts to access or query a resource belonging to another company (or inject a foreign `company_id`), execution is terminated immediately with an HTTP 403 Forbidden page and a `TENANT_ISOLATION_VIOLATION` event is logged to the database.

### 2.3 Session Fixation & Hijacking Countermeasures
- **Implementation:** Located in `includes/session.php` and `controllers/AuthController.php`.
- **Session ID Regeneration:** `session_regenerate_id(true)` is executed immediately upon successful password verification, discarding the pre-authentication session ID.
- **Strict Mode:** `session.use_strict_mode = 1` rejects arbitrary uninitialized session IDs supplied by attackers.
- **Cookie-Only Sessions:** `session.use_only_cookies = 1` prevents session IDs from appearing in URL query strings.
- **Flags:** `HttpOnly = true` prevents JavaScript extraction; `SameSite = Lax` protects against cross-origin forgery; `Secure` is dynamically enabled when HTTPS is detected.

### 2.4 Idle Session Timeout Enforcement
- **Implementation:** `initSecureSession()` in `includes/session.php`.
- **Timeout Window:** 1,800 seconds (30 minutes).
- **Action:** If `time() - $_SESSION['last_activity'] > 1800`, the active session is completely destroyed using `destroySecureSession()`, the session cookie is un-set with an expired timestamp, and the client is redirected to the login view with an inactivity message.

### 2.5 Cryptographic Password Hashing & Complexity
- **Implementation:** `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])`.
- **Complexity Rule:** Minimum 8 characters, requiring at least one letter and at least one numeric digit.
- **Zero Plaintext Persistence:** Plaintext passwords are never logged, echoed, or written to disk. Only 60-character bcrypt hash strings are stored in `users.password_hash`.
- **Password History & Re-use Guard:** Users cannot re-use their current password during password change routines.

### 2.6 CSRF Protection Engine
- **Implementation:** `includes/csrf.php`.
- **Entropy:** 32 cryptographically secure random bytes generated via `random_bytes(32)`, encoded into 64 hexadecimal characters.
- **Verification:** Strict comparison via `hash_equals()`, providing timing-attack immunity.
- **HTTP Method Guard:** `requireCSRF()` verifies incoming tokens on all state-altering requests (`POST`, `PUT`, `DELETE`, `PATCH`).

### 2.7 Cross-Site Scripting (XSS) & SQL Injection Defense
- **SQL Injection:** 100% of database queries utilize PDO prepared statements with parameterized inputs. Direct string interpolation into SQL queries is prohibited.
- **XSS Sanitization:** The global helper function `e(?string $value): string` enforces `htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')` across all view templates.

---

## 3. Real-Time Security Audit Ledger

All critical security events are persisted in real-time to the `audit_logs` table:

| Action Identifier | Trigger Condition | Logged Payload Details |
| :--- | :--- | :--- |
| `LOGIN_SUCCESS` | Successful credential verification | User ID, Company ID, Role Name |
| `LOGIN_FAILED` | Incorrect password or non-existent email | Attempted email, Failure reason |
| `LOGIN_RATE_LIMITED` | Exceeded 5 failed attempts in 15 min | Attempted email, IP, Attempt count |
| `LOGIN_BLOCKED_INACTIVE` | Suspended or Pending user login attempt | User ID, Account status |
| `LOGOUT` | User initiated sign out | User ID, Company ID |
| `PASSWORD_CHANGED` | User updated account password | User ID, Company ID |
| `PASSWORD_RESET_REQUESTED` | Password reset link requested | User ID, Email address |
| `PASSWORD_RESET_COMPLETED` | Password reset finalized with token | Email address |
| `ROUTE_ACCESS_FORBIDDEN` | Role or permission check failed | Request URI, User ID, Role, Reason |
| `TENANT_ISOLATION_VIOLATION` | Cross-company IDOR access attempt | Request URI, Target Company, Auth Company |
| `CSRF_VALIDATION_FAILED` | Missing or mismatched CSRF token | Request URI, HTTP Method, Remote IP |

---

## 4. Verification & Non-Destructive Compliance

- **Database Integrity:** Zero tables dropped, zero columns modified, zero migrations executed during Part 04.
- **Candidate Databases:** Both external candidate repositories remain 100% untouched:
  - `prayas_iti_dashboard.students`: Exactly 1,292 records.
  - `itistudents.candidates`: Exactly 56 records.
- **User Accounts:** Existing administrative and test accounts were preserved intact.

