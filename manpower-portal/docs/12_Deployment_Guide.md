# 12. Production Deployment & Operations Guide

## Project Identity
- **Project:** Industry Manpower Requirement Portal
- **Organization:** PRAYAS – The Automation Academy
- **Target OS:** Ubuntu 22.04 LTS / Debian 12 / RHEL 9 (or Windows Server 2022 IIS/Apache)
- **Runtime:** PHP 8.2+ with PDO MySQL & FPM, Apache 2.4+ / Nginx

---

## 1. Production Prerequisites

### Software Dependencies
- **Web Server:** Apache 2.4+ with `mod_rewrite`, `mod_headers`, `mod_ssl`
- **PHP Runtime:** PHP 8.2 or 8.3
  - Extensions required: `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `json`, `curl`, `session`, `fileinfo`
- **Database Server:** MySQL 8.0+ or MariaDB 10.6+
- **SSL Certificate:** TLS 1.3 certificate (Let's Encrypt / DigiCert)
- **SMTP Server:** TLS mail transfer agent (Google Workspace / Amazon SES / SendGrid)

---

## 2. Server Configuration

### A. Recommended `php.ini` Settings
```ini
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/prayas_errors.log
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 5M
post_max_size = 8M
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = "Lax"
session.use_strict_mode = 1
session.use_only_cookies = 1
session.gc_maxlifetime = 1800
```

### B. Apache VirtualHost Hardening
```apache
<VirtualHost *:443>
    ServerName manpower.prayas.org.in
    DocumentRoot /var/www/prayas-manpower-portal

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/manpower.prayas.org.in/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/manpower.prayas.org.in/privkey.pem
    SSLProtocol -all +TLSv1.2 +TLSv1.3

    <Directory /var/www/prayas-manpower-portal>
        AllowOverride All
        Require all granted
    </Directory>

    # Block direct HTTP access to internal system folders
    RedirectMatch 403 ^/(config|database|docs|tests)/

    ErrorLog ${APACHE_LOG_DIR}/prayas_error.log
    CustomLog ${APACHE_LOG_DIR}/prayas_access.log combined
</VirtualHost>
```

---

## 3. Database Migration Deployment Workflow

Execute database migrations sequentially using `database/migrate.php` or native MySQL CLI:

```bash
# 1. Verify MySQL connection
mysql -u prayas_portal_user -p -h localhost -e "SELECT VERSION();"

# 2. Run Idempotent Migrations
php database/migrate.php

# 3. Verify Database Seeders
mysql -u prayas_portal_user -p manpower_portal_db -e "SELECT count(*) FROM roles; SELECT count(*) FROM users;"
```

---

## 4. File Permissions & Directory Ownership

```bash
# Set ownership to web server user (www-data)
sudo chown -R www-data:www-data /var/www/prayas-manpower-portal

# Directory permissions (755) and file permissions (644)
find /var/www/prayas-manpower-portal -type d -exec chmod 755 {} \;
find /var/www/prayas-manpower-portal -type f -exec chmod 644 {} \;

# Secure environment file (600 - readable only by web server)
chmod 600 /var/www/prayas-manpower-portal/.env
```

---

## 5. Automated Cron Job Configuration

Configure automated deadline reminders to execute daily at 08:00 AM IST:

```bash
# Edit crontab for www-data
sudo crontab -u www-data -e

# Daily Deadline Reminder Check (08:00 AM)
0 8 * * * /usr/bin/php /var/www/prayas-manpower-portal/cron/deadline_reminders.php >> /var/log/prayas/cron_reminders.log 2>&1
```

---

## 6. Backup & Disaster Recovery Protocol

### Daily Automated MySQL Dump
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/prayas_portal"
mkdir -p $BACKUP_DIR

# Dump Portal Database
mysqldump -u prayas_portal_user -p'PASSWORD' --single-transaction --quick --routines manpower_portal_db | gzip > $BACKUP_DIR/manpower_portal_db_$DATE.sql.gz

# Delete backups older than 30 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete
```

---

## 7. Rollback Procedure

If a deployment error occurs:
1. Revert web server DocumentRoot to previous release symlink:
   ```bash
   ln -sfn /var/www/releases/prayas_previous /var/www/prayas-manpower-portal
   ```
2. If database schema was altered, restore pre-deployment MySQL dump:
   ```bash
   gunzip < /var/backups/prayas_portal/pre_deploy_backup.sql.gz | mysql -u prayas_portal_user -p manpower_portal_db
   ```
3. Restart Apache and PHP-FPM:
   ```bash
   sudo systemctl restart apache2 php8.2-fpm
   ```

