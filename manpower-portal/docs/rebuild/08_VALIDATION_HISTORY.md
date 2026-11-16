# Part 08 — Requirement Validation History & Telemetry Persistence

**Project:** PRAYAS – The Automation Academy | Industry Manpower Requirement Portal  
**Document Code:** `PRY-REBUILD-08-VAL-HISTORY`  
**Classification:** Database Telemetry & Audit Documentation  
**Version:** `ALG-VAL-2026-V1.0`  

---

## 1. Storage Schema (`requirement_validation_history`)

Validation runs are permanently logged into the `requirement_validation_history` table. Each individual rule evaluation is captured as a distinct row linked by a common `validation_run_id` (format: `VAL-YYYYMMDD-HHMMSS-XXXX`).

```sql
CREATE TABLE IF NOT EXISTS `requirement_validation_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `requirement_id` INT UNSIGNED NOT NULL,
    `validation_run_id` VARCHAR(64) NOT NULL,
    `rule_id` VARCHAR(50) NOT NULL,
    `rule_name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `severity` ENUM('ERROR','WARNING','INFO','MANUAL_REVIEW') NOT NULL,
    `result` ENUM('PASS','FAIL','WARNING','REVIEW_REQUIRED') NOT NULL,
    `field_name` VARCHAR(100) NULL,
    `error_message` TEXT NULL,
    `details_json` TEXT NULL,
    `validation_version` VARCHAR(20) DEFAULT 'ALG-VAL-2026-V1.0',
    `triggered_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_req_val` (`requirement_id`),
    KEY `idx_run_id` (`validation_run_id`),
    KEY `idx_rule_id` (`rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 2. Telemetry Payload in `details_json`

To avoid breaking schema changes while supporting rich analytics and modern UI rendering, contextual telemetry is serialized into `details_json`:

```json
{
  "suggested_correction": "Adjust total required vacancies (10) to match trade allocation sum (8).",
  "current_value": "Total: 10, Sum: 8",
  "trigger_type": "MANUAL_REVALIDATION",
  "validation_status": "VALIDATION_FAILED",
  "client_ip": "127.0.0.1",
  "execution_duration_ms": 3.4
}
```

### Trigger Types Supported
- `SUBMISSION`: Invoked during final form submission (Step 8 of wizard).
- `DRAFT_SAVE`: Invoked when saving draft progress.
- `MANUAL_REVALIDATION`: Invoked on-demand by an Industry Admin or Super Admin via the Validation Dossier UI.
- `SCHEDULED_CRON`: Invoked by background audit workers.

---

## 3. Data Retrieval API

The `RequirementValidationEngine` provides two optimized retrieval methods:

1. `getLatestValidationRun(int $requirementId): ?array`  
   Aggregates the most recent run, tallying passed, warning, and error counts, and returns decoded rule items with suggestions and current values for direct UI binding.

2. `getValidationRunsSummary(int $requirementId): array`  
   Returns a high-level timeline of all historical validation runs for a requirement, detailing who triggered each run, when it executed, the trigger mechanism, and pass/fail ratios.

