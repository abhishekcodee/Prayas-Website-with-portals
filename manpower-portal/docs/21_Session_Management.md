# 21 — Session Security & Lifecycle Management

## Document Identifier: SES-PRA-2026-V1.0
**Project Title:** Industry Manpower Requirement & ITI Candidate Recruitment Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** `includes/session.php`  
**Date:** 2026-11-05  

---

## 1. Session Configuration & Hardening

Session governance is enforced uniformly across the portal via `includes/session.php`.

### 1.1 Cookie Security Parameters
The session cookie is configured before initialization:
* `httponly = true`: Restricts cookie access to HTTP requests only, preventing theft via Cross-Site Scripting (XSS).
* `samesite = 'Lax'`: Protects against Cross-Site Request Forgery (CSRF) during external cross-site navigations while supporting standard POST returns.
* `secure`: Enabled automatically whenever connection is HTTPS (`$_SERVER['HTTPS'] === 'on'`).
* `use_only_cookies = 1`: Disallows URL session parameter injection (`PHPSESSID` in query strings).
* `use_strict_mode = 1`: Rejects uninitialized session IDs provided by client.

---

## 2. Session Fixation Defense

Session fixation attacks are mitigated at the authentication transition boundary:
```php
if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
    @session_regenerate_id(true);
}
```
The previous session identifier is destroyed and replaced with an entirely new cryptographically random ID immediately upon successful credential validation.

---

## 3. Inactivity Idle Timeout

To prevent unauthorized access on unattended terminals:
- `SESSION_TIMEOUT_SECONDS`: Set to 1,800 seconds (30 minutes).
- Every authenticated request inspects:
  $$\Delta t = T_{\text{current}} - T_{\text{last\_activity}}$$
- If $\Delta t > 1800\text{ seconds}$, `destroySecureSession()` is invoked, terminating the user session and redirecting to the login screen with an expiration alert banner.
- If $\Delta t \le 1800\text{ seconds}$, $T_{\text{last\_activity}}$ is updated to $T_{\text{current}}$.

---

## 4. Secure Session Invalidation (Logout)

When a user initiates sign out via `/views/auth/logout.php`:
1. `$_SESSION` array is purged: `$_SESSION = []`.
2. Session cookie is expired in client browser with a negative timestamp:
   ```php
   setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
   ```
3. `session_destroy()` is invoked on the server.
4. An audit log entry (`USER_LOGOUT`) is written with the departing user ID.

