# Part 03 — Database Migration & Rollback Strategy Plan
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Database Schema:** `manpower_portal_db`  
**Document Code:** `DOC-REBUILD-03-MIGRATION-PLAN`  
**Policy Status:** Strict Non-Destructive Migration Protocol

---

## 1. Migration Safety & Governance Policy

To guarantee 100% operational uptime, referential integrity, and zero data loss, the following database migration governance rules are enforced across all phases:

1. **Pre-Execution Backup Gate:** A point-in-time SQL snapshot of `manpower_portal_db` must be generated and verified before running any migration script.
2. **Read-Only External Databases:** Under no circumstances will migration scripts target, alter, or touch `prayas_iti_dashboard` or `itistudents`.
3. **Idempotency Mandate:** Every migration statement must be safe to execute multiple times without throwing duplicate key/table errors (`CREATE OR REPLACE VIEW`, `ADD COLUMN IF NOT EXISTS`, conditional schema procedures).
4. **Mandatory Rollback Pairing:** Every migration script must include a fully documented, tested reversal block.
5. **No Automatic Execution in Part 03:** In adherence to Part 03 instructions, migration scripts are generated and cataloged only. Live execution is held awaiting explicit user sign-off.

---

## 2. Migration Registry & Sequence Taxonomy

All migration scripts are stored in [`database/migrations/`](file:///c:/Manpower%20Portal/database/migrations/) using strict sequential three-digit prefixes:

| Version | Migration Script Name | Type | Status |
|:---:|---|:---:|:---:|
| `001` | `001_create_schema_migrations_and_roles_permissions.sql` | Baseline Schema | Applied |
| `002` | `002_create_companies_and_users.sql` | Governance Master | Applied |
| `003` | `003_create_manpower_requirements_and_subtables.sql` | Requisition Engine | Applied |
| `004` | `004_create_candidate_matches_and_pipeline.sql` | Pipeline Baseline | Applied |
| `005` | `005_create_notifications_and_audit_logs.sql` | Notifications & Audit | Applied |
| `006` | `006_create_password_resets.sql` | Auth Security | Applied |
| `007` | `007_update_company_statuses.sql` | Status Workflow | Applied |
| `008` | `008_create_industry_portal_tables.sql` | Multi-Plant & Invites | Applied |
| `009` | `009_extend_requirement_wizard_fields.sql` | Wizard Expansion | Applied |
| `010` | `010_create_validation_history_and_notification_enhancements.sql` | Validation Engine | Applied |
| `011` | `011_candidate_matching_engine.sql` | 100-Pt Scoring Schema | Applied |
| `012` | `012_recruitment_pipeline_enhancements.sql` | 16-Stage Pipeline Tables | Applied |
| `013` | **`013_schema_alignment_and_reporting_views.sql`** | **Reporting Views & Aliases** | **Authored (Pending Cutover)** |

---

## 3. Migration 013 Detailed Specification

**File Location:** [`database/migrations/013_schema_alignment_and_reporting_views.sql`](file:///c:/Manpower%20Portal/database/migrations/013_schema_alignment_and_reporting_views.sql)

### Deliverables Included:
1. **Analytical Views for High-Efficiency Reporting:**
   - `v_active_requisitions_summary`: Consolidates requirement headers, plant locations, total vacancies, and real-time candidate counts by stage (`count_matched`, `count_shortlisted`, `count_interviewed`, `count_selected`, `count_joined`).
   - `v_recruitment_funnel_summary`: Merges match details, candidate names, 100-point scores, offer codes, CTC, and joining statuses into a unified pipeline stream.
2. **Business Nomenclature Aliasing Views:**
   - `v_portal_users`: Maps high-level `portal_users` references to `users` with joined role and company labels.
   - `v_candidate_selections`: 1-to-1 view alias for `selections`.
   - `v_candidate_joinings`: 1-to-1 view alias for `joinings`.
   - `v_requirement_validation_logs`: 1-to-1 view alias for `requirement_validation_history`.

---

## 4. Step-by-Step Execution Runbook (When Authorized)

When the user grants explicit approval to apply Migration 013:

### Step 1: Execute Snapshot Backup
```bash
mysqldump -u root -p --default-character-set=utf8mb4 --routines --triggers --single-transaction --result-file="database/backups/pre_migration_013_snapshot.sql" manpower_portal_db
```

### Step 2: Apply Migration Script
```bash
mysql -u root -p manpower_portal_db < database/migrations/013_schema_alignment_and_reporting_views.sql
```

### Step 3: Record Version in `schema_migrations` Table
```sql
INSERT INTO `schema_migrations` (`version`, `migration_file`, `applied_at`) 
VALUES ('013', '013_schema_alignment_and_reporting_views.sql', NOW());
```

### Step 4: Verification
Execute database verification script:
```bash
php tests/test_performance_suite.php
```

---

## 5. Emergency Rollback Protocol

If any view or query behaves unexpectedly:
```bash
mysql -u root -p manpower_portal_db << 'EOF'
DROP VIEW IF EXISTS `v_active_requisitions_summary`;
DROP VIEW IF EXISTS `v_recruitment_funnel_summary`;
DROP VIEW IF EXISTS `v_portal_users`;
DROP VIEW IF EXISTS `v_candidate_selections`;
DROP VIEW IF EXISTS `v_candidate_joinings`;
DROP VIEW IF EXISTS `v_requirement_validation_logs`;
DELETE FROM `schema_migrations` WHERE `version` = '013';
EOF
```
Because Migration 013 introduces purely additive views, rolling back requires zero table locks and risks zero user data loss.

