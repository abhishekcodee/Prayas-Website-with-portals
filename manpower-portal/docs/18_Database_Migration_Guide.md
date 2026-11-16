# 18 — Database Migration Guide & Operations Manual

## Document Identifier: MIG-PRA-2026-V1.0
**Project Title:** Industry Manpower Requirement & ITI Candidate Recruitment Portal  
**Organization:** PRAYAS – The Automation Academy  
**Database Name:** `manpower_portal_db`  
**Collation:** `utf8mb4_unicode_ci`  
**Storage Engine:** `InnoDB`  
**Date:** 2026-11-05  

---

## 1. Migration Architecture & Safe Execution Principles

The migration engine for PRAYAS – The Automation Academy adheres to four non-negotiable principles:
1. **Zero Downtime & Zero Data Loss:** Existing candidate databases (`itistudents`, `prayas_iti_dashboard`, `prayas_portal`) are never altered or dropped.
2. **Deterministic Sequence:** Migrations are sequentially numbered (`001_...`, `002_...`, `003_...`).
3. **Idempotency:** Every DDL uses `CREATE TABLE IF NOT EXISTS`, preventing failures on repeated executions.
4. **Execution Tracking:** All applied migrations are recorded in the `schema_migrations` audit table.

---

## 2. Inventory of Migration Files

The following migration files reside in `database/migrations/`:

| Version | Migration File Name | Target Tables | Purpose | Status |
| :---: | :--- | :--- | :--- | :---: |
| **001** | `001_create_schema_migrations_and_roles_permissions.sql` | `schema_migrations`, `roles`, `permissions`, `role_permissions`, `system_settings` | Core RBAC and tracking foundation. | **Applied** |
| **002** | `002_create_companies_and_users.sql` | `companies`, `users` | Multi-tenant employer accounts and portal users. | **Applied** |
| **003** | `003_create_manpower_requirements_and_subtables.sql` | `manpower_requirements`, `requirement_trades`, `requirement_skills`, `requirement_status_history` | Requisition ingestion and trade/skill mappings. | **Applied** |
| **004** | `004_create_candidate_matches_and_pipeline.sql` | `candidate_matches`, `candidate_screening`, `interviews`, `interview_feedback`, `selections`, `offer_letters`, `joinings` | Sourcing, screening, interview, and joining pipeline. | **Applied** |
| **005** | `005_create_notifications_and_audit_logs.sql` | `notifications`, `audit_logs` | In-app alerts and immutable security audit ledger. | **Applied** |

---

## 3. How to Execute Migrations

### Method A: Automated PHP Runner (Recommended)
From project root:
```bash
C:\xampp\php\php.exe "C:\Manpower Portal\database\migrations\migrate.php"
```
Output confirms each file applied or skipped if already present.

### Method B: Manual MySQL CLI
```bash
"C:\xampp\mysql\bin\mysql.exe" -u root manpower_portal_db < "database/migrations/001_create_schema_migrations_and_roles_permissions.sql"
"C:\xampp\mysql\bin\mysql.exe" -u root manpower_portal_db < "database/migrations/002_create_companies_and_users.sql"
"C:\xampp\mysql\bin\mysql.exe" -u root manpower_portal_db < "database/migrations/003_create_manpower_requirements_and_subtables.sql"
"C:\xampp\mysql\bin\mysql.exe" -u root manpower_portal_db < "database/migrations/004_create_candidate_matches_and_pipeline.sql"
"C:\xampp\mysql\bin\mysql.exe" -u root manpower_portal_db < "database/migrations/005_create_notifications_and_audit_logs.sql"
```

---

## 4. How to Execute Seeders

After applying migrations, execute the seeder pipeline to populate initial roles, permissions, settings, and the Super Admin account:
```bash
C:\xampp\php\php.exe "C:\Manpower Portal\database\seeders\seed.php"
```
Seeders are fully idempotent. Re-running will not create duplicate records.

---

## 5. Verification Test Suite

Verify all connections, table counts, foreign keys, and read-only candidate bridges:
```bash
C:\xampp\php\php.exe "C:\Manpower Portal\database\test_connections.php"
```
All 7 automated tests must pass.

