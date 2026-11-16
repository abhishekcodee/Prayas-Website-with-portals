# 11 — Deployment Guide & Environment Setup

## Organization: PRAYAS – The Automation Academy
**Platform:** Industry Manpower Requirement Portal  
**Target Environment:** Local XAMPP & Production Linux/Apache  

---

## 1. System Requirements

* **Operating System:** Windows 10/11 (Local XAMPP) or Linux Ubuntu 22.04 LTS (Production)
* **Web Server:** Apache 2.4+ (`mod_rewrite` enabled)
* **PHP Engine:** PHP 8.2 or higher
  * Required Extensions: `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `json`, `session`, `curl`
* **Database Engine:** MySQL 8.0+ or MariaDB 10.4+

---

## 2. Local XAMPP Setup Instructions

1. **Verify Environment Services:**
   * Open XAMPP Control Panel.
   * Ensure **Apache** and **MySQL** services are active.
2. **Database Provisioning:**
   * Open terminal / command prompt.
   * Run the migration runner located at `database/migrations/run_migrations.php` or import `database/migrations/001_initial_schema.sql` into MySQL.
3. **Application Access Options:**
   * **Option A (PHP Built-in Server for quick test/verification):**
     ```bash
     cd "C:\Manpower Portal"
     C:\xampp\php\php.exe -S localhost:8080
     ```
     Access via browser: `http://localhost:8080`
   * **Option B (Apache VirtualHost / Alias):**
     Create an Apache alias in `C:\xampp\apache\conf\httpd.conf`:
     ```apache
     Alias /manpower-portal "C:/Manpower Portal"
     <Directory "C:/Manpower Portal">
         Options Indexes FollowSymLinks
         AllowOverride All
         Require all granted
     </Directory>
     ```
     Access via browser: `http://localhost/manpower-portal`

---

## 3. Production Deployment Checklist

- [ ] Disable PHP error display in `php.ini` (`display_errors = Off`, `log_errors = On`).
- [ ] Ensure `config/database.php` uses dedicated, non-root database credentials.
- [ ] Verify SSL certificate (HTTPS) and set `session.cookie_secure = 1`.
- [ ] Run `database/run_backup.bat` to verify automated backup cron task.
- [ ] Verify folder permissions for document uploads (Offer letters, candidate resumes).

