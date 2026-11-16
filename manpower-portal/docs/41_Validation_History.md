# 41 — Persistent Validation History Audit Architecture

## Document Identifier: SPEC-VAL-HIST-2026-V1.0
**Project Title:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Database Table:** `requirement_validation_history`  
**Migration:** `010_create_validation_history_and_notification_enhancements.sql`  

---

## 1. Architectural Purpose

To ensure total transparency, compliance accountability, and regulatory explainability, the PRAYAS portal maintains an immutable, persistent audit log of every algorithmic validation decision executed on a manpower requirement.

Unlike ephemeral session validation, **`requirement_validation_history`** ensures that:
- Every pass, warning, failure, and manual review flag is preserved across time.
- Historical validation runs are never overwritten or mutated.
- When an employer edits or resubmits a requirement, a new `validation_run_id` is created, allowing side-by-side progression analysis.
- Super Admins and academic evaluators can inspect exactly why a requisition was auto-approved or routed for manual review.

---

## 2. Database Schema Specification

```sql
CREATE TABLE IF NOT EXISTS `requirement_validation_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `requirement_id` INT UNSIGNED NOT NULL,
    `validation_run_id` VARCHAR(64) NOT NULL,
    `rule_id` VARCHAR(50) NOT NULL,
    `rule_name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `severity` ENUM('ERROR', 'WARNING', 'INFO', 'MANUAL_REVIEW') NOT NULL,
    `result` ENUM('PASS', 'FAIL', 'WARNING', 'REVIEW_REQUIRED') NOT NULL,
    `field_name` VARCHAR(100) NULL,
    `error_message` TEXT NULL,
    `details_json` TEXT NULL,
    `validation_version` VARCHAR(20) DEFAULT 'ALG-VAL-2026-V1.0',
    `triggered_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_val_hist_req` FOREIGN KEY (`requirement_id`) REFERENCES `manpower_requirements` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_val_hist_user` FOREIGN KEY (`triggered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_val_req` (`requirement_id`),
    INDEX `idx_val_run` (`validation_run_id`),
    INDEX `idx_val_rule` (`rule_id`),
    INDEX `idx_val_severity` (`severity`),
    INDEX `idx_val_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 3. Data Dictionary

| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `INT UNSIGNED` | Primary Key, Auto Increment | Unique record identifier. |
| `requirement_id` | `INT UNSIGNED` | Foreign Key (`manpower_requirements.id`) | Target requirement being audited. |
| `validation_run_id`| `VARCHAR(64)` | Indexed | UUID/timestamp identifying the atomic validation execution. |
| `rule_id` | `VARCHAR(50)` | Indexed | Standardized rule identifier (e.g. `ERR-TIT-01`, `BR-VAL-001`). |
| `rule_name` | `VARCHAR(255)` | Not Null | Human-readable title of the business constraint. |
| `category` | `VARCHAR(50)` | Not Null | Operational domain: `COMPANY`, `REQUIREMENT`, `SALARY`, `DATE`, `LOCATION`, `RECRUITMENT`. |
| `severity` | `ENUM` | `ERROR`, `WARNING`, `INFO`, `MANUAL_REVIEW` | System impact classification. |
| `result` | `ENUM` | `PASS`, `FAIL`, `WARNING`, `REVIEW_REQUIRED` | Outcome of rule evaluation. |
| `field_name` | `VARCHAR(100)` | Nullable | Specific form/column attribute evaluated. |
| `error_message` | `TEXT` | Nullable | Explainable failure or warning text returned to user. |
| `details_json` | `TEXT` | Nullable | Contextual telemetry (e.g. calculated totals, threshold values). |
| `validation_version`| `VARCHAR(20)` | Default `'ALG-VAL-2026-V1.0'` | Codebase algorithm specification version. |
| `triggered_by` | `INT UNSIGNED` | Foreign Key (`users.id`) | User whose action initiated the validation. |
| `created_at` | `TIMESTAMP` | Default `CURRENT_TIMESTAMP` | Wall-clock timestamp of the validation run. |

---

## 4. Querying & Reporting API

The `RequirementValidationEngine` provides built-in query helpers:

```php
// 1. Retrieve all chronological records for a requirement
$history = $engine->getValidationHistory($requirementId);

// 2. Retrieve high-level summary grouped by atomic run
$runs = $engine->getValidationRunsSummary($requirementId);
// Returns: [validation_run_id, run_time, total_rules, failures, warnings, reviews, triggered_by_name]
```

