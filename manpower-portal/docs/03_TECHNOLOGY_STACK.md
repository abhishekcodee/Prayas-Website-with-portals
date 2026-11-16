# 03 — Technology Stack Confirmation

## Organization: PRAYAS – The Automation Academy
**Document Reference:** DOC-PRA-03-V1  
**Verification Date:** 2026-11-05  

---

## 1. Technology Stack Verification Matrix

The existing development environment on the local machine was examined against the expected project requirements.

| Layer | Expected Technology | Verified Environment Version | Status & Alignment |
| :--- | :--- | :--- | :---: |
| **Backend Language** | Core PHP | **PHP 8.2.12** (ZTS Visual C++ 2019 x64) | **Confirmed & Ready** |
| **Web Server** | Apache / XAMPP | **Apache 2.4.58** (Win64) / XAMPP | **Confirmed & Ready** |
| **Database Engine** | MySQL / MariaDB | **MariaDB 10.4.32 / MySQL Community** | **Confirmed & Ready** |
| **Database Driver** | PDO (PHP Data Objects) | **PDO MySQL Driver Enabled** | **Confirmed & Ready** |
| **Frontend Framework**| Bootstrap 5 | **Bootstrap 5.3 (CSS & JS)** | **Confirmed & Ready** |
| **Markup & Styling** | HTML5 / CSS3 | Modern Semantic HTML5 & CSS3 | **Confirmed & Ready** |
| **Client Scripting** | JavaScript (ES6+) | Vanilla JavaScript (ES6+ Standards) | **Confirmed & Ready** |
| **Session & Auth** | PHP Sessions & RBAC | Native secure sessions (`HttpOnly`, `Strict`) | **Confirmed & Ready** |

---

## 2. Environment Configurations Verified

1. **PHP Core (`php.exe`):**
   * Path: `C:\xampp\php\php.exe`
   * Version: PHP 8.2.12
   * Extensions verified active: `pdo_mysql`, `mbstring`, `openssl`, `json`, `session`, `curl`.
2. **Database Engine (`mysqld.exe`):**
   * Port: `3306`
   * Protocol: Socket / TCP connection via `localhost`
   * Root User: Passwordless standard local developer configuration (`root` with blank password) or configurable via `config/database.php`.
3. **Web Server Root:**
   * Apache DocumentRoot: `C:\xampp\htdocs`
   * Active Project Root: `C:\Manpower Portal`
   * Development options: PHP built-in web server (`php -S localhost:8080`) or Apache virtual host / alias.

---

## 3. Technology Governance & Migration Restrictions

- **NO Framework Migrations:** The portal will **NOT** be converted to Laravel, CodeIgniter, Symfony, Node.js, Express, React, or Vue.
- **Pure Core PHP + PDO Standards:**
  * Clean object-oriented or structured modular controllers.
  * 100% Parameterized queries with prepared statements.
  * No external heavyweight package managers required for runtime execution (zero vendor lock-in).

