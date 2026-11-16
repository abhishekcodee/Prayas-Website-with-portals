# 44 — Deadline Reminder System & Cron Task Architecture

## Document Identifier: SPEC-REM-2026-V1.0
**Project Title:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Script Path:** [`database/cron/requirement_deadline_reminders.php`](file:///c:/Manpower%20Portal/database/cron/requirement_deadline_reminders.php)  
**Database Table:** `deadline_reminder_logs`  

---

## 1. Architectural Purpose & Overview

The **Deadline Reminder System** runs as a decoupled, idempotent scheduled background process that audits all active requirements (`APPROVED`, `IN_PROGRESS`, `PARTIALLY_FULFILLED`) and issues proactive milestone notifications before deadlines lapse.

### Critical Invariants:
1. **Zero Duplicate Alerts:** The table `deadline_reminder_logs` enforces a compound unique constraint:
   $$\text{uk\_req\_rem\_user\_date} = (\text{requirement\_id}, \text{reminder\_type}, \text{target\_date}, \text{recipient\_user_id})$$
   Even if the cron script runs every 5 minutes or experiences network retries, it is physically impossible to send duplicate reminders for the same event and target date.
2. **Terminal Status Exclusion:** Requirements in terminal or inactive states (`DRAFT`, `NEEDS_CORRECTION`, `CANCELLED`, `CLOSED`, `FULFILLED`) are excluded from milestone monitoring.
3. **Decoupled from Web Requests:** Reminders are executed via CLI cron or authorized webhook; they never introduce latency or blocking loops inside frontend user page requests.

---

## 2. Monitored Milestone Thresholds

| Milestone Event | Monitored Attribute | Trigger Threshold Window | Notification Severity |
| :--- | :--- | :--- | :---: |
| `DEADLINE_APPROACHING` | `application_deadline` | $D_{\text{today}} \le D_{\text{deadline}} \le D_{\text{today}} + 3\text{ days}$ | `WARNING` |
| `REQUIREMENT_EXPIRY` | `application_deadline` | $D_{\text{deadline}} < D_{\text{today}}$ | `INFO` |
| `INTERVIEW_UPCOMING` | `interview_date` | $D_{\text{today}} \le D_{\text{interview}} \le D_{\text{today}} + 2\text{ days}$ | `INFO` |
| `JOINING_UPCOMING` | `target_joining_date` | $D_{\text{today}} \le D_{\text{joining}} \le D_{\text{today}} + 3\text{ days}$ | `INFO` |

---

## 3. Scheduled Cron Configuration Guide

### A. Windows Server & XAMPP Environment (Task Scheduler)
To run the reminder process daily at 08:00 AM on Windows / XAMPP:
1. Open **Windows Task Scheduler** (`taskschd.msc`).
2. Create Basic Task named: `PRAYAS Requirement Deadline Monitor`.
3. Set Trigger: **Daily at 08:00:00**.
4. Set Action: **Start a program**.
   - **Program/script:** `C:\xampp\php\php.exe`
   - **Add arguments:** `C:\Manpower Portal\database\cron\requirement_deadline_reminders.php --verbose`
   - **Start in:** `C:\Manpower Portal`

### B. Linux Production Hosting (crontab)
To configure via standard Linux crontab:
```bash
# Run daily at 08:00 AM
0 8 * * * /usr/bin/php /var/www/html/database/cron/requirement_deadline_reminders.php >> /var/log/prayas_reminders.log 2>&1
```

### C. Webhook Fallback (cURL / External Monitor)
If CLI execution is unavailable in shared hosting:
```bash
curl -s "https://portal.prayas-academy.org/database/cron/requirement_deadline_reminders.php?secret=prayas_cron_secure_token"
```

---

## 4. Execution Telemetry & Logging

When run via CLI, the script produces standardized execution telemetry:

```text
========================================================
 PRAYAS Portal — Requirement Deadline & Milestone Monitor
 Started at: 2026-11-05 08:00:00
 Mode: LIVE DISPATCH
========================================================
[+] Scanned 14 active requisitions.
    [+] Dispatched DEADLINE_APPROACHING for req 'REQ-202611-0010' on 2026-11-08 to user #4
    [~] Skipped duplicate INTERVIEW_UPCOMING for req #REQ-202611-0006 user #4
========================================================
 Execution Complete Summary:
   Requisitions Scanned: 14
   Reminders Sent:       1
   Duplicates Skipped:   1
   Errors:               0
 Finished at: 2026-11-05 08:00:01
========================================================
```

