# 20 — Authentication Architecture & Identity Governance

## Document Identifier: AUT-PRA-2026-V1.0
**Project Title:** Industry Manpower Requirement & ITI Candidate Recruitment Portal  
**Organization:** PRAYAS – The Automation Academy  
**Authentication Standard:** Core PHP 8.2 + BCRYPT + Session RBAC  
**Date:** 2026-11-05  

---

## 1. Authentication Architecture Overview

The authentication engine implements defense-in-depth identity verification designed to isolate multi-tenant industrial employers while providing seamless, role-tailored workspaces for PRAYAS recruitment personnel.

```mermaid
flowchart TD
    Req[Incoming User Request] --> CSRF{CSRF Token Valid?}
    CSRF -- Invalid --> Block403[403 Forbidden + Audit Log]
    CSRF -- Valid --> CheckUser{Account Exists & Active?}
    
    CheckUser -- No / Inactive --> FailLogin[Return Generic Error + Audit Log]
    CheckUser -- Yes --> VerifyPass{password_verify == TRUE?}
    
    VerifyPass -- No --> FailLogin
    VerifyPass -- Yes --> RegenSession[session_regenerate_id(true)]
    
    RegenSession --> LoadPerms[Load Role & Atomic Permissions into Session]
    LoadPerms --> ForceChange{must_change_password == 1?}
    
    ForceChange -- Yes --> RedirectChange[/views/auth/change_password.php]
    ForceChange -- No --> DispatchRole{Role Target Dispatch}
    
    DispatchRole --> SA[/views/super_admin/dashboard.php]
    DispatchRole --> IA[/views/industry/dashboard.php]
    DispatchRole --> RA[/views/recruitment_admin/dashboard.php]
    DispatchRole --> REC[/views/recruiter/dashboard.php]
```

---

## 2. Authentication Capabilities Implemented

### 2.1 User Login (`AuthController::login()`)
* **Endpoint:** `/views/auth/login.php`
* **Credential Verification:** Compares client plain password against stored hash using PHP `password_verify($password, $user['password_hash'])`.
* **Account Status Gate:** Rejects accounts marked `Inactive` or `Suspended`.
* **Audit Logging:** Every successful and failed attempt is logged with IP and user agent in `audit_logs`.
* **Session Initialization:** Regenerates session ID and populates role, company association, and permissions.

### 2.2 Industrial Employer Registration (`AuthController::registerIndustry()`)
* **Endpoint:** `/views/auth/register.php`
* **Scope:** Permits industrial manufacturing partners to onboard their plants.
* **Security Guard:** Hardcodes role resolution strictly to `industry_admin`. Public users can never assign themselves administrative or recruiter roles.
* **Corporate Onboarding State:** Company created with `verification_status = 'Pending'`. Super Admins are notified automatically.

### 2.3 Password Modification (`AuthController::changePassword()`)
* **Endpoint:** `/views/auth/change_password.php`
* **Verification:** Requires current password verification.
* **Password Policy:** Minimum 8 characters containing both letters and numbers.
* **Enforced Change Flag:** Clears `must_change_password = 0` upon successful update.

### 2.4 Password Recovery Workflow (`AuthController::requestPasswordReset()`, `resetPassword()`)
* **Endpoints:** `/views/auth/forgot_password.php`, `/views/auth/reset_password.php`
* **Token Cryptography:** Generates a 64-character hex token via `random_bytes(32)`.
* **Database Storage:** Stored as SHA-256 hash in `password_resets` with a 1-hour expiration.
* **One-Time Token Enforcement:** Token is marked with `used_at = NOW()` immediately upon successful reset; cannot be replayed.
* **User Enumeration Prevention:** Returns identical confirmation message whether email exists or not.
* **SMTP Offline Handling:** In local development without an active SMTP gateway, the token link is logged securely for developer testing.

