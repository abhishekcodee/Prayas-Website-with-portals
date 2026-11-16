# Part 08 — Scheduled Task (Cron) Architecture & Operations Guide

**Project:** PRAYAS – The Automation Academy | Industry Manpower Requirement Portal  
**Document Code:** `PRY-REBUILD-08-CRON-SCHEDULING`  
**Classification:** Operational Runbook & Server Daemon Guide  
**Script Location:** `database/cron/requirement_deadline_reminders.php`  

---

## 1. CLI Execution Command & Options

The background deadline reminder worker is executed from the portal root using the PHP CLI interpreter:

```bash
# Standard Production Run
php database/cron/requirement_deadline_reminders.php

# Dry-run Inspection (Simulated dispatch, no DB mutations)
php database/cron/requirement_deadline_reminders.php --dry-run

# Verbose Diagnostic Run (Outputs per-record details)
php database/cron/requirement_deadline_reminders.php --verbose

# Combined Diagnostic Dry-run
php database/cron/requirement_deadline_reminders.php --dry-run --verbose
```

---

## 2. Single-Instance Execution Guard (Lock File)

To eliminate race conditions and avoid concurrent execution during high system load, the script acquires an exclusive non-blocking lock on a temporary lock file:

- **Lock File Path:** `sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prayas_cron_reminders.lock'`
- **Acquisition Mechanism:** `flock($fp, LOCK_EX | LOCK_NB)`
- **Conflict Handling:** If another process currently holds the lock, the script logs an informational notice and exits gracefully with exit code `0`.
- **Automatic Teardown:** A registered PHP shutdown function automatically unlocks, closes, and unlinks the lock file upon process termination.

---

## 3. Server Crontab & Task Scheduler Configuration

### Linux (crontab -e)
Execute daily at 06:00 AM server local time:
```cron
0 6 * * * /usr/bin/php /var/www/html/manpower_portal/database/cron/requirement_deadline_reminders.php >> /var/log/prayas_reminders.log 2>&1
```

### Windows (Task Scheduler CLI)
```cmd
schtasks /create /tn "PRAYAS_Requirement_Reminders" /tr "C:\xampp\php\php.exe C:\Manpower_Portal\database\cron\requirement_deadline_reminders.php" /sc daily /st 06:00 /ru SYSTEM
```

### Secure Webhook Access (Optional)
If invoked via external HTTP heartbeat or monitoring ping, the script verifies a secure query token:
```
GET /database/cron/requirement_deadline_reminders.php?secret=prayas_cron_secure_token
```
Requests lacking this token or not running in `cli` SAPI are immediately rejected with HTTP `403 Forbidden`.

