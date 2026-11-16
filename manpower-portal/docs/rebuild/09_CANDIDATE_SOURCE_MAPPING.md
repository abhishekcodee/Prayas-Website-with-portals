# PART 09 — CANDIDATE SOURCE IDENTIFICATION & FIELD MAPPING SPECIFICATION

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Candidate Source Integration & Data Access Model (`CandidateSourceModel.php`)  
**Status:** COMPLETED & VERIFIED (Part 09 Rebuild)  

---

## 1. Executive Summary & Source Architecture

The PRAYAS Candidate Sourcing Engine sources accredited ITI graduates from two primary external MySQL databases residing on the same host:
1. **`prayas_iti_dashboard.students`**: The internal PRAYAS ITI Student Management System, containing **1,292 accredited student records**.
2. **`itistudents.candidates`**: The central ITI Graduate Sourcing Repository, containing **56 candidate profiles**.

Together, these represent an addressable pool of **1,348 accredited candidates**.

### Immutability & Zero-Mutation Mandate
- Both external databases are treated as strictly **READ-ONLY**.
- The `CandidateSourceModel` executes only `SELECT` projections.
- No `INSERT`, `UPDATE`, `DELETE`, `ALTER`, `DROP`, or `TRUNCATE` statements are permitted or executed against either database.
- Row counts and table definitions are audited pre-flight and post-execution in the Part 09 automated test suite (`tests/test_candidate_matching_part09_suite.php`).

---

## 2. Source-Level Schema Analysis & Field Cross-Walk

| Canonical Standard Field | `prayas_iti_dashboard.students` | `itistudents.candidates` | Normalization / Parsing Logic |
| :--- | :--- | :--- | :--- |
| **`source`** | Hardcoded `'prayas_dashboard'` | Hardcoded `'itistudents'` | Source identifier token |
| **`source_id`** | `id` (int) | `id` (int) | Primary key in source DB |
| **`candidate_name` / `full_name`** | `name` (varchar) | `full_name` (varchar) | Sanitized trimmed string |
| **`registration_no`** | `registration_no` (varchar) | `candidate_registration_no` (varchar) | Normalized uppercase or NULL |
| **`serial_no`** | NULL | `candidate_serial_no` (varchar) | Cleaned string or NULL |
| **`trade`** | `trade` (varchar) | `iti_trade` (varchar) | Cross-walked via standardization table |
| **`college_name`** | `iti` (varchar) | `iti_college_name` (varchar) | Trimmed institute name |
| **`passing_year`** | `passing_year` (varchar, e.g. `'2022-2024'`) | `year_of_passing` (varchar, e.g. `'2024'`) | Regex extracted 4-digit graduation year |
| **`passing_month`** | NULL | `passing_month` (varchar) | Preserved or NULL |
| **`qualification`** | Constant `'ITI Passed'` | Constant `'ITI Passed'` | Standardized technical qualification |
| **`ssc_percentage`** | `ssc` (varchar/decimal) | `tenth_percentage` (varchar) | Cleaned numeric float value |
| **`hsc_percentage`** | `hsc` (varchar/decimal) | NULL | Cleaned numeric float value |
| **`iti_percentage`** | NULL | `iti_percentage_grade` (varchar) | Cleaned numeric float value |
| **`gender`** | `gender` (`Male`/`Female`/`Other`) | `gender` (`Male`/`Female`) | Normalized case string |
| **`date_of_birth`** | `dob` (date/varchar) | `date_of_birth` (date/varchar) | Standardized YYYY-MM-DD format |
| **`district`** | `district` (varchar) | `preferred_job_location` (varchar) | Geographic district in Maharashtra |
| **`state`** | Imputed `'Maharashtra'` | Imputed `'Maharashtra'` | Default state jurisdiction |
| **`preferred_location`** | `preferred_location` (varchar) | `preferred_job_location` (varchar) | Candidate preference text |
| **`experience`** | `experience` (varchar) | `experience` (varchar) | Normalized ('Fresher', '1 Year', etc.) |
| **`skills` / Technical Context** | `computer_knowledge`, `languages_known`, `remarks` | `computer_knowledge`, `languages_known`, `feedback` | Aggregated searchable text token pool |
| **`is_passed`** | `result != 'FAIL'` | Constant `1` | Binary exam result clearance |
| **`result_status`** | `result` (e.g. `'PASS'`, `'FAIL'`) | Constant `'PASS'` | Examination clearance string |

---

## 3. Passing Year Parsing & Normalization

In `prayas_iti_dashboard.students`, passing years are frequently recorded as academic batch spans (e.g., `'2020-2022'`, `'2022-2024'`).
The normalization routine applies regular expression parsing:
```php
if (preg_match('/\b(20\d{2})\b/', $rawYear, $matches)) {
    $parsedYear = (int)$matches[1];
}
```
When a batch range like `'2022-2024'` is provided, the terminal completion year `2024` is extracted as the graduation year, aligning accurately with requirement batch criteria.

---

## 4. Candidate Deduplication Strategy

When scanning across both repositories, candidates may appear in both databases or multiple times within a single source due to repeat batch enrollments:
1. **Deduplication Signature:** Candidates are keyed by:
   - Primary: Normalized NCVT Registration Number (`REG:<REG_NO>`) when available.
   - Secondary: Normalized Name + Trade compound key (`<FULL_NAME>|<TRADE>`).
2. **Database Integrity:** The `candidate_matches` table enforces a compound unique constraint:
   ```sql
   UNIQUE KEY `uk_req_candidate` (`requirement_id`, `candidate_source`, `candidate_source_id`)
   ```
3. **Idempotent Re-Runs:** Re-executing algorithmic matching updates scoring attributes and timestamps via `ON DUPLICATE KEY UPDATE` without generating duplicate match rows.

