# Part 08 — Centralized Alert Engine Architecture

**Project:** PRAYAS – The Automation Academy | Industry Manpower Requirement Portal  
**Document Code:** `PRY-REBUILD-08-ALERT-ENGINE`  
**Classification:** Event Dispatch & Notification Architecture  

---

## 1. Executive Summary

The **PRAYAS Centralized Alert Engine** (`includes/notification_service.php`) coordinates real-time user engagement, multi-channel notifications, and administrative escalations. It guarantees that all validation decisions, review milestones, and operational notices reach authorized stakeholders without human delay.

---

## 2. Dispatch Channels

1. **In-App Notification Console (`notifications`):**
   - High-performance, indexed table delivering badge counts, toast alerts, and persistent notification consoles for both Industry Partners and Super Administrators.
   - Strict multi-tenant row isolation: Users only query notifications where `recipient_user_id = :uid`.
2. **Email Delivery & Simulation (`email_logs`):**
   - Renders branded responsive HTML templates (using PRAYAS deep navy `#0D2847` and gold accents) with plain-text fallback.
   - When live SMTP is inactive (`system_settings.mail_enabled = '0'`), delivery is simulated and logged to `email_logs` with status `SIMULATED` to maintain complete audit traceability.

---

## 3. Supported Event Dispatchers

| Dispatcher Method | Notification Type | Trigger Event | Primary Recipient |
| :--- | :--- | :--- | :--- |
| `dispatchValidationFailed` | `VALIDATION_FAILED` | Fatal validation defects detected during submission. | Submitting User |
| `dispatchValidationWarning` | `VALIDATION_WARNING` | Advisory warnings generated (non-blocking). | Submitting User |
| `dispatchRequirementUnderReview` | `REQUIREMENT_UNDER_MANUAL_REVIEW` / `REQUIREMENT_NEEDS_MANUAL_REVIEW` | Headcount > 50, wage < ₹12k, or turnaround < 7d. | User & Super Admins |
| `dispatchRequirementRevised` | `REQUIREMENT_REVISED` | Requisition parameters updated & re-validated. | Plant HR Coordinator |
| `dispatchRequirementOverdue` | `REQUIREMENT_OVERDUE` | Milestone window or application deadline passed. | Creator & Plant Admins |
| `dispatchDeadlineReminder` | `DEADLINE_*` / `INTERVIEW_*` / `JOINING_*` | Time-window milestone alerts via scheduled cron. | Plant Admins |

---

## 4. UI Actions & Direct Deep-Linking

Every generated notification includes a context-aware `action_url` constructed via `app_url()`:
- `VALIDATION_FAILED` &rarr; `/views/industry/validation_summary.php?id={req_id}`
- `REQUIREMENT_NEEDS_CORRECTION` &rarr; `/views/industry/requirement_form.php?id={req_id}`
- `REQUIREMENT_UNDER_MANUAL_REVIEW` &rarr; `/views/industry/requirement_details.php?id={req_id}`
- `NEW_APPROVED_REQUIREMENT` &rarr; `/views/super_admin/requirements.php?id={req_id}`

