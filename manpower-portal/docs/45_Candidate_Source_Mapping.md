# 45 — Candidate Source Data Mapping & Field Normalization

## Document Identifier: SPEC-SRC-MAP-2026-V1.0
**Project Title:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Access Model:** [`CandidateSourceModel`](file:///c:/Manpower%20Portal/models/CandidateSourceModel.php)  
**Read-Only Sources:** `prayas_iti_dashboard.students` (1,292 records) & `itistudents.candidates` (56 records)  

---

## 1. Architectural Purpose & Data Invariance

The PRAYAS Industry Manpower Requirement Portal bridges two external, heterogeneous candidate repositories into a unified, normalized representation for recruitment sourcing without performing any schema mutations, record insertions, updates, or deletions.

### Strict Data Invariance Guarantees:
- **Read-Only Access:** All interactions with candidate databases are strictly limited to `SELECT` queries executed via PDO.
- **No Data Fabrication:** When an attribute is unavailable in a source table, the normalized value is flagged as `null`. No artificial percentages or test data are fabricated.
- **Deduplication Engine:** If a candidate exists in both repositories, the deduplication engine reconciles records using normalized NCVT registration numbers (`REG:<REG_NO>`) or sanitized compound signatures (`NAME:<FULL_NAME>|<DOB>`).

---

## 2. Field Mapping Cross-Walk Matrix

| Standard Normalized Field | Type | Source 1: `prayas_iti_dashboard.students` | Source 2: `itistudents.candidates` | Normalization & Formatting Rules |
| :--- | :--- | :--- | :--- | :--- |
| `candidate_source` | `ENUM` | Fixed: `'prayas_dashboard'` | Fixed: `'itistudents'` | Distinguishes originating source repository. |
| `candidate_source_id` | `INT` | `students.id` | `candidates.id` | Source primary key identifier. |
| `registration_no` | `VARCHAR` | `students.registration_no` | `candidates.candidate_registration_no` | Uppercase trimmed NCVT/MIS registration code. |
| `serial_no` | `VARCHAR` | *Unavailable (`null`)* | `candidates.candidate_serial_no` | Batch serial index. |
| `full_name` | `VARCHAR` | `students.name` | `candidates.full_name` | Trimmed, title-cased candidate name. |
| `trade` | `VARCHAR` | `students.trade` | `candidates.iti_trade` | Standardized trade title (e.g. Electrician, Fitter). |
| `college_name` | `VARCHAR` | `students.iti` | `candidates.iti_college_name` | Accredited ITI institute name. |
| `passing_year` | `INT` | Extracted 4-digit regex from `students.passing_year` (e.g. `'2016-2018'` $\rightarrow$ `2018`) | Extracted 4-digit regex from `candidates.year_of_passing` | Standardized 4-digit integer graduation year. |
| `passing_month` | `VARCHAR` | *Unavailable (`null`)* | `candidates.passing_month` | 2-digit month string (e.g. `'06'`). |
| `qualification` | `VARCHAR` | Default: `'ITI Passed'` | Default: `'ITI Passed'` | Formal academic qualification tier. |
| `ssc_percentage` | `DECIMAL`| `students.ssc` | Extracted numeric float from `candidates.tenth_percentage` | Cleaned decimal (e.g. `'63.4%'` $\rightarrow$ `63.40`). |
| `hsc_percentage` | `DECIMAL`| `students.hsc` | *Unavailable (`null`)* | Cleaned decimal percentage where available. |
| `iti_percentage` | `DECIMAL`| *Unavailable (`null`)* | Extracted numeric float from `candidates.iti_percentage_grade` | Cleaned decimal ITI cumulative percentage. |
| `gender` | `VARCHAR` | `students.gender` | `candidates.gender` | `'Male'`, `'Female'`, or `'Any'`. |
| `date_of_birth` | `VARCHAR` | `students.dob` | `candidates.date_of_birth` | Raw date of birth string. |
| `district` | `VARCHAR` | `students.district` | `candidates.preferred_job_location` | Primary home or operational district. |
| `state` | `VARCHAR` | Fixed: `'Maharashtra'` | Fixed: `'Maharashtra'` | Default regional state. |
| `experience` | `VARCHAR` | `students.experience` | `candidates.experience` | Standardized experience string (e.g. `'Fresher'`). |
| `previous_company` | `VARCHAR` | `students.previous_company` | `candidates.previous_company` | Prior employer name if experienced. |
| `preferred_location`| `VARCHAR` | `students.preferred_location` | `candidates.preferred_job_location` | Preferred deployment industrial zone. |
| `expected_salary` | `VARCHAR` | `students.expected_salary` | `candidates.expected_salary` | Candidate expected monthly stipend/salary. |
| `computer_knowledge`| `VARCHAR` | `students.computer_knowledge` | `candidates.computer_knowledge` | IT literacy and software skills. |
| `languages_known` | `VARCHAR` | `students.languages_known` | `candidates.languages_known` | Spoken and technical languages. |
| `driving_licence` | `VARCHAR` | `students.driving_licence` | `candidates.driving_licence` | Light motor vehicle license status. |
| `availability_date` | `VARCHAR` | *Unavailable (`null`)* | `candidates.expected_joining_date` | Date available to join on ground. |
| `result_status` | `VARCHAR` | `students.result` (e.g. `'PASS'`, `'FAIL'`) | Default: `'PASS'` | Examination pass standing. |
| `evaluator_feedback`| `TEXT` | `students.remarks` | `candidates.feedback` | Internal academic recruiter assessment notes. |

---

## 3. Missing Data Policy

1. **Zero Inflation:** If a candidate record lacks `ssc_percentage` or `computer_knowledge`, no assumptions are made, and no artificial score bonus is awarded.
2. **Transparent Indicators:** The UI explicitly displays `N/A` or `Unspecified` for missing attributes.
3. **Redaction of Government IDs:** Attributes such as `students.aadhaar_no` are completely excluded from the projection query to prevent sensitive PII leakage.

