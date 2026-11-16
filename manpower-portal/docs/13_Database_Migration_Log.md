# 13 — Database Migration Log

## Organization: PRAYAS – The Automation Academy
**Target Database:** `manpower_portal_db`  

---

## Migration History

| Version | Migration File | Date Executed | Status | Description |
| :---: | :--- | :---: | :---: | :--- |
| **001** | `001_create_core_tables.sql` | *Scheduled for Phase 3* | Planned | Creates `roles`, `permissions`, `role_permissions`, `companies`, `users`. |
| **002** | `002_create_requirement_tables.sql` | *Scheduled for Phase 3* | Planned | Creates `manpower_requirements`, `requirement_trades`, `requirement_skills`, `requirement_status_history`. |
| **003** | `003_create_recruitment_tables.sql` | *Scheduled for Phase 3* | Planned | Creates `candidate_matches`, `candidate_screening`, `interviews`, `interview_feedback`, `selections`, `offer_letters`, `joinings`. |
| **004** | `004_create_governance_tables.sql` | *Scheduled for Phase 3* | Planned | Creates `notifications`, `audit_logs`. |
| **005** | `005_seed_initial_data.sql` | *Scheduled for Phase 3* | Planned | Seeds default system roles, initial permissions, and Super Admin user. |

---

## Safety Standards
1. All migrations must be idempotent (`CREATE TABLE IF NOT EXISTS`, `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`).
2. No `DROP DATABASE` or `DROP TABLE` operations on production candidate tables.
3. Every migration file is paired with an audit entry in `database_migrations` tracking table.

