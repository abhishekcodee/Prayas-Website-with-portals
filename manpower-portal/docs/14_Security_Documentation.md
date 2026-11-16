# 14 — Security Documentation & Governance Standards

## Organization: PRAYAS – The Automation Academy
**Product:** Industry Manpower Requirement Portal  
**Compliance Standard:** OWASP Top 10 Aligned Defense-in-Depth  
**Status:** Part 04 Implemented & Tested  
**Date:** 2026-11-05  

---

## 1. Authentication & Password Security
* **Password Hashing:** Implemented via PHP `password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12])`.
* **Verification:** Validated strictly using `password_verify($inputPassword, $storedHash)`.
* **Session Fixation Prevention:** Automatic invocation of `session_regenerate_id(true)` upon successful user authentication.
* **Session Configuration:**
  - `session.cookie_httponly = 1` (Blocks JavaScript theft of cookie tokens).
  - `session.cookie_samesite = 'Lax'` (Protects cross-site cookie leakage).
  - `session.use_only_cookies = 1`.
  - 30-minute idle session timeout.

---

## 2. Multi-Tenant Data Isolation
* Industry users are strictly bound to their assigned `company_id`.
* Every SQL query executed within the Industry panel injects `WHERE company_id = :session_company_id` via `getCompanyId()`.
* Never trust client-submitted form IDs or query string IDs without server-side ownership validation.

---

## 3. SQL Injection Defense
* **Rule:** 100% Parameterized Prepared Statements via PDO.
* **Prohibited:** No raw SQL concatenation (`"SELECT * FROM users WHERE email = '" . $email . "'"` is strictly forbidden).

---

## 4. Cross-Site Scripting (XSS) Mitigation
* Output encoding helper function: `e($string)`:
  ```php
  function e(?string $str): string {
      return htmlspecialchars((string)$str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  }
  ```
* All user-rendered fields (Company names, candidate names, addresses, descriptions) must pass through `e()`.

---

## 5. Cross-Site Request Forgery (CSRF) Mitigation
* A cryptographically secure random token (`bin2hex(random_bytes(32))`) is stored in the user session.
* Every HTTP `POST`, `PUT`, `DELETE` form contains a hidden `<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">` or provides the `X-CSRF-TOKEN` HTTP header for AJAX calls.
* Missing or mismatched tokens abort execution with an HTTP 403 response and log a security alert to `audit_logs`.

---

## 6. Audit Logging & Non-Repudiation
* Every sensitive action (Login success, login failure, status transition, password change, route forbidden event) writes an immutable record to the `audit_logs` table:
  - `user_id`
  - `company_id` (if applicable)
  - `action` (e.g. `LOGIN_SUCCESS`, `PASSWORD_CHANGED`, `ROUTE_ACCESS_FORBIDDEN`)
  - `ip_address`
  - `user_agent`
  - `details_json` (Contextual snapshot)
  - `created_at`
