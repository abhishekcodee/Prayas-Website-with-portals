# 28 — Platform Settings & Operational Governance Engine

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Global System Configuration Console  
**Document ID:** DOC-SET-28  
**Status:** Approved & Implemented  

---

## 1. Overview & Architecture

The **Platform Settings Module** (`views/super_admin/system_settings.php`) allows Super Admins to configure global runtime behavior, recruitment rules, matching parameters, session durations, and communication settings without modifying application code.

Settings are persisted in the `system_settings` table using typed values (`string`, `integer`, `boolean`, `json`) and cached in memory or loaded per application lifecycle.

---

## 2. Configuration Groups & System Settings

The configuration engine organizes settings into distinct operational domains:

### Group A: General Identity & Branding
- `platform_name`: Official platform title ("PRAYAS – The Automation Academy").
- `platform_tagline`: Subtitle / mission statement ("Industry Manpower Requirement Portal").
- `contact_email`: Platform contact and escalations inbox (`contact@prayas-academy.internal`).
- `support_phone`: Operational assistance phone line.
- `maintenance_mode`: Global toggle (`0` or `1`) putting public and industry portals into maintenance while preserving Super Admin access.

### Group B: Sourcing & Requirement Governance
- `default_requirement_expiry_days`: Duration (e.g. 45 days) after which unfulfilled requirements transition to `expired`.
- `auto_approval_threshold`: Minimum company trust score or criteria required for automated requirement approval.
- `max_active_requirements_per_company`: Quota ceiling (e.g. 20) preventing single enterprises from flooding the sourcing engine.
- `candidate_matching_min_score`: Baseline composite threshold (e.g. 60%) below which candidates will not be presented to industry recruiters.

### Group C: Security & Session Policies
- `session_lifetime_minutes`: Session timeout duration (default: 30 minutes).
- `max_login_attempts`: Maximum consecutive failures before triggering account lockout (default: 5 attempts).
- `lockout_duration_minutes`: Duration an IP or account is held in lockout (default: 15 minutes).
- `require_password_change_days`: Mandatory password rotation cycle (default: 90 days; 0 disables).

### Group D: Communications & Notifications
- `email_notifications_enabled`: Global email notification toggle (`1` = enabled, `0` = disabled).
- `notify_admin_on_new_company`: Triggers alert to Super Admin whenever a new enterprise registers.
- `notify_admin_on_new_requirement`: Triggers alert to Recruiter pool whenever a new requisition is submitted.

---

## 3. Dynamic Setting Validation & Persistence

1. **Validation Engine:**
   Before updating `system_settings`, inputs are sanitized and validated against their expected types (e.g. integer bounds, email formatting, boolean toggles).
2. **Atomic Ledger Tracking:**
   Every setting modification compares old vs new values and dispatches an event `SYSTEM_SETTINGS_UPDATED` into `audit_logs` capturing the specific keys modified.
3. **Application Integration:**
   Settings can be queried globally via a simple helper function:
   ```php
   $min_score = get_setting('candidate_matching_min_score', 60);
   ```

