# PART 12 — BACKUP & DISASTER RECOVERY PLAN
## PRAYAS Industry Manpower Requirement Portal
**Organization:** PRAYAS – The Automation Academy  
**Document Code:** PRAYAS-DOC-P12-007  
**Effective Date:** 2026-11-06  
**Status:** Approved Operational Guide  

---

## 1. Backup Strategy Overview

To protect institutional data and maintain high availability, the portal specifies an automated 3-tier backup strategy:

```
[Operational Database: manpower_portal_db]
       │
       ├─► Daily Incremental Binary Logs (Point-in-Time Recovery)
       ├─► Nightly Full Schema & Data SQL Dump (Gzip Compressed)
       └─► Weekly Off-Site Encrypted Archive (Cold Storage / Cloud Bucket)
```

> **IMPORTANT RULE:** External candidate databases (`prayas_iti_dashboard` and `itistudents`) are external institutional data sources. They are backed up by their respective IT administrators and are accessed in strict read-only mode by this portal.

---

## 2. Automated Backup Execution Procedures

### 2.1 Daily Database Dump Command
```bash
mysqldump -u [DB_USER] -p[DB_PASSWORD] \
  --single-transaction \
  --quick \
  --routines \
  --triggers \
  --databases manpower_portal_db | gzip > /var/backups/prayas/manpower_portal_$(date +\%Y\%m\%d_\%H\%M\%S).sql.gz
```

### 2.2 Uploaded Document Backup
Uploaded company verification documents and offer letter PDFs located in `/storage/uploads/` are synchronized daily:
```bash
rsync -avz --delete /var/www/manpower_portal/storage/uploads/ /var/backups/prayas/uploads/
```

---

## 3. Disaster Recovery & Restoration Protocol

### Recovery Time Objective (RTO): `< 60 Minutes`
### Recovery Point Objective (RPO): `< 24 Hours` (Nightly full) / `< 1 Hour` (with Binary Logs)

### Restoration Steps:
1. **Provision Fresh Environment:**
   Ensure PHP 8.x, MySQL 8.x, and web server (Nginx/Apache) are configured with required extensions (`pdo_mysql`, `mbstring`, `openssl`).
2. **Decompress Backup Archive:**
   ```bash
   gunzip < /var/backups/prayas/manpower_portal_YYYYMMDD_HHMMSS.sql.gz > restore.sql
   ```
3. **Import Database:**
   ```bash
   mysql -u root -p manpower_portal_db < restore.sql
   ```
4. **Verify Database Integrity:**
   Run the verification test suite:
   ```bash
   php tests/test_backup_restore_verification.php
   php tests/test_full_system_integration_part12.php
   ```
5. **Restore Web Assets & Permissions:**
   Ensure write permissions on `/storage/` and verify `.env` / `config/database.php` credentials.
