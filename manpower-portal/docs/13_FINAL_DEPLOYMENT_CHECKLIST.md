# Part 13 — Final Production Deployment Checklist & Cutover Manual

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Release:** **v1.0.0 (Release Candidate)**  
**Target Environment:** Production Host (Linux / Cloud VPS / On-Premise)  
**Security Level:** Enterprise Production (Zero-Trust Multi-Tenancy)

---

## 1. Pre-Deployment Infrastructure Prerequisites

Ensure the target production server satisfies all environmental dependencies before initiating deployment:

| Component | Minimum Specification | Recommended Specification | Verified Status |
|---|---|---|:---:|
| **Operating System** | Ubuntu 22.04 LTS / RHEL 9 / Debian 12 | Ubuntu 24.04 LTS | [ ] |
| **PHP Runtime** | PHP 8.2.0 or higher | PHP 8.2.18+ / 8.3 | [ ] |
| **PHP Extensions** | `pdo_mysql`, `mbstring`, `openssl`, `json`, `curl`, `ctype`, `tokenizer`, `gd`, `zip` | All enabled with opcache enabled | [ ] |
| **Database Server** | MySQL 8.0.30+ or MariaDB 10.4.30+ | MySQL 8.0.36+ (InnoDB engine) | [ ] |
| **Web Server** | Apache 2.4+ (with `mod_rewrite`, `mod_headers`) or Nginx 1.24+ | Apache 2.4.58+ with SSL/TLS | [ ] |
| **Memory / CPU** | 2 vCPU / 4 GB RAM | 4 vCPU / 8 GB RAM | [ ] |
| **Storage** | 20 GB SSD Available | 50 GB NVMe SSD | [ ] |
| **TLS/SSL Certificate** | Valid CA Certificate (Let's Encrypt / Corporate PKI) | Automated auto-renewal certbot | [ ] |
| **External Candidate DBs** | `prayas_iti_dashboard` (1,292 rows), `itistudents` (56 rows) | Read-only MySQL credentials assigned | [ ] |

---

## 2. Phase-by-Phase Deployment Checklist

### Phase 1: Codebase Preparation & Transfer
- [ ] 1.1 Verify no Git untracked temporary files or local cache artifacts are copied to production.
- [ ] 1.2 Clone or rsync codebase to `/var/www/manpower-portal/`.
- [ ] 1.3 Ensure `.git` directory is deleted or strictly blocked from web serving.
- [ ] 1.4 Set directory permissions:
  ```bash
  chown -R www-data:www-data /var/www/manpower-portal
  find /var/www/manpower-portal -type d -exec chmod 755 {} \;
  find /var/www/manpower-portal -type f -exec chmod 644 {} \;
  ```
- [ ] 1.5 Secure writeable directories (logs, session storage, uploads if applicable):
  ```bash
  chmod -R 775 /var/www/manpower-portal/storage/
  ```

### Phase 2: Database Schema & Baseline Migration
- [ ] 2.1 Create clean production database:
  ```sql
  CREATE DATABASE manpower_portal_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  ```
- [ ] 2.2 Create dedicated least-privilege application user:
  ```sql
  CREATE USER 'prayas_portal_app'@'localhost' IDENTIFIED BY 'STRONG_SECRET_PASSWORD_HERE';
  GRANT SELECT, INSERT, UPDATE, DELETE ON manpower_portal_db.* TO 'prayas_portal_app'@'localhost';
  -- Read-Only grants on external candidate databases
  GRANT SELECT ON prayas_iti_dashboard.students TO 'prayas_portal_app'@'localhost';
  GRANT SELECT ON itistudents.candidates TO 'prayas_portal_app'@'localhost';
  FLUSH PRIVILEGES;
  ```
- [ ] 2.3 Import baseline production schema from verified backup:
  ```bash
  mysql -u prayas_portal_app -p manpower_portal_db < /var/www/manpower-portal/database/backups/manpower_portal_db_final_cutover_20261105.sql
  ```
- [ ] 2.4 Verify all 30 tables are present in `manpower_portal_db`.

### Phase 3: Production Configuration & Secret Management
- [ ] 3.1 Copy `.env.production.example` to `.env`:
  ```bash
  cp /var/www/manpower-portal/.env.production.example /var/www/manpower-portal/.env
  chmod 600 /var/www/manpower-portal/.env
  ```
- [ ] 3.2 Update database connection settings with live credentials:
  - `DB_HOST=localhost`
  - `DB_PORT=3306`
  - `DB_NAME=manpower_portal_db`
  - `DB_USER=prayas_portal_app`
  - `DB_PASS=<ACTUAL_STRONG_PASSWORD>`
- [ ] 3.3 Set `APP_ENV=production` and `APP_DEBUG=false`.
- [ ] 3.4 Set `APP_URL=https://manpower.prayas-academy.org` (or customer production FQDN).
- [ ] 3.5 Configure production SMTP credentials for live email delivery:
  - `SMTP_HOST=smtp.mailgun.org` (or SMTP provider)
  - `SMTP_PORT=587`
  - `SMTP_USER=postmaster@prayas-academy.org`
  - `SMTP_PASS=<PRODUCTION_SMTP_PASSWORD>`
  - `SMTP_ENCRYPTION=tls`
  - `MAIL_FROM_ADDRESS=manpower-notifications@prayas-academy.org`
  - `MAIL_FROM_NAME="PRAYAS – The Automation Academy"`

### Phase 4: Web Server Hardening & Security Configuration
- [ ] 4.1 Confirm `.htaccess` is active and `AllowOverride All` is set in Apache VirtualHost.
- [ ] 4.2 Verify directory browsing is disabled (`Options -Indexes`).
- [ ] 4.3 Verify that navigating to `/config/`, `/database/`, `/docs/`, and `/tests/` returns HTTP 403 Forbidden.
- [ ] 4.4 Verify that requesting `.env` directly returns HTTP 403 Forbidden.
- [ ] 4.5 Confirm all HTTP Security Headers are returned on all responses:
  - `X-Frame-Options: SAMEORIGIN`
  - `X-Content-Type-Options: nosniff`
  - `X-XSS-Protection: 1; mode=block`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy: geolocation=(), camera=(), microphone=()`
- [ ] 4.6 Enforce HTTP to HTTPS automatic redirection (301 Permanent).

### Phase 5: Automated Cron Job Scheduling
- [ ] 5.1 Open crontab for web application user:
  ```bash
  crontab -u www-data -e
  ```
- [ ] 5.2 Add daily automated deadline notification runner (Runs every morning at 08:00):
  ```bash
  0 8 * * * /usr/bin/php /var/www/manpower-portal/cron/deadline_reminders.php >> /var/log/manpower_portal_cron.log 2>&1
  ```
- [ ] 5.3 Verify that log file `/var/log/manpower_portal_cron.log` is writable by `www-data`.
- [ ] 5.4 Test-run the cron script once manually to ensure exit code 0.

### Phase 6: Post-Deployment Smoke Test & Sanity Verification
- [ ] 6.1 Access public home page (`/`) via HTTPS — confirms clean load, no PHP warnings.
- [ ] 6.2 Test Super Admin login with master credentials — confirms session creation and dashboard load.
- [ ] 6.3 Test Industry Admin login with verified company — confirms multi-tenant scoped dashboard.
- [ ] 6.4 Test Recruiter login — confirms candidate pipeline access.
- [ ] 6.5 Verify 14 executive metrics render without PHP/SQL exceptions.
- [ ] 6.6 Verify Chart.js visualizations render properly.
- [ ] 6.7 Verify audit log records all administrative logins.
- [ ] 6.8 Confirm external databases remain strictly read-only.

---

## 3. Emergency Rollback Protocol

If an unexpected critical fault occurs during production cutover, execute the following contingency protocol:

1. **Activate Maintenance Splash Screen:**
   Place maintenance lock flag in storage directory:
   ```bash
   touch /var/www/manpower-portal/storage/maintenance.lock
   ```
2. **Revert Application Code:**
   Check out the previous stable release tag:
   ```bash
   git checkout tags/v0.11.0-stable
   ```
3. **Restore Database from Verified Backup:**
   Execute clean restore from pre-cutover backup:
   ```bash
   mysql -u root -p manpower_portal_db < /var/www/manpower-portal/database/backups/manpower_portal_db_final_cutover_20261105.sql
   ```
4. **Flush Web Server Cache & Restart:**
   ```bash
   systemctl restart php8.2-fpm
   systemctl reload apache2
   ```
5. **Deactivate Maintenance Splash:**
   ```bash
   rm -f /var/www/manpower-portal/storage/maintenance.lock
   ```

---

## 4. Production Deployment Signoff Signatures

| Role | Signoff Representative | Decision | Date & Timestamp |
|---|---|:---:|---|
| **Lead Software Architect** | Verified | **APPROVED** | 2026-11-05 13:53 IST |
| **Security & Compliance Auditor**| Verified | **APPROVED** | 2026-11-05 13:53 IST |
| **Database Administrator (DBA)** | Verified | **APPROVED** | 2026-11-05 13:53 IST |
| **PRAYAS Operations Executive** | Pending User Confirmation | **AWAITING USER SIGN-OFF** | Pending |

