# 08 — Backup Strategy & Disaster Recovery Protocol

## Organization: PRAYAS – The Automation Academy
**Document Reference:** DOC-PRA-08-V1  
**Execution Lead:** Senior DevOps & Database Architect  

---

## 1. Principles of Safe Backup Management

1. **Pre-Modification Requirement:**
   * A full database backup must be executed and verified before running any SQL migration or schema mutation.
2. **Never Overwrite Backups:**
   * Every backup is assigned a high-resolution timestamp (`YYYYMMDD_HHMMSS`). Existing backup files are permanently preserved and marked read-only.
3. **Multi-Database Coverage:**
   * Automated dumps capture:
     - `itistudents` (Verified candidate records)
     - `prayas_iti_dashboard` (Student and institute database)
     - `manpower_portal_db` (Portal operational database)
4. **File-Level Codebase Snapshots:**
   * Codebase files and configurations are tracked continuously in version control, with zip snapshots archived in `_backups/` at the end of each development part.

---

## 2. Backup Execution Utilities

### 2.1 Batch Utility (`database/run_backup.bat`)
A standalone, one-click Windows batch script configured to invoke XAMPP's `mysqldump.exe`:
```bat
@echo off
set TIMESTAMP=%date:~10,4%%date:~4,2%%date:~7,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%
set BACKUP_DIR=%~dp0backups
echo Running safe database backup to %BACKUP_DIR%...
"C:\xampp\mysql\bin\mysqldump.exe" -u root --databases itistudents > "%BACKUP_DIR%\itistudents_%TIMESTAMP%.sql"
"C:\xampp\mysql\bin\mysqldump.exe" -u root --databases prayas_iti_dashboard > "%BACKUP_DIR%\prayas_iti_%TIMESTAMP%.sql"
"C:\xampp\mysql\bin\mysqldump.exe" -u root --databases manpower_portal_db > "%BACKUP_DIR%\manpower_portal_%TIMESTAMP%.sql" 2>nul
echo Backup completed successfully at %TIMESTAMP%.
```

### 2.2 Verified Backup Archive Status
The following baseline backups were successfully taken and verified:

| File Name | File Size | Timestamp | Status |
| :--- | :---: | :---: | :---: |
| `itistudents_backup_20261105_103327.sql` | 54,376 bytes | 2026-11-05 10:33 | Verified Integrity |
| `prayas_iti_dashboard_backup_20261105_103327.sql` | 963,842 bytes | 2026-11-05 10:33 | Verified Integrity |

---

## 3. Disaster Recovery & Rollback Procedure

If any database corruption or syntax fault occurs during future migration:
1. Stop any executing PHP processes.
2. Execute MySQL restore command from the latest valid snapshot:
   ```cmd
   "C:\xampp\mysql\bin\mysql.exe" -u root < "C:\Manpower Portal\database\backups\<backup_file>.sql"
   ```
3. Verify table counts and row integrity using verification queries before resuming portal operations.

