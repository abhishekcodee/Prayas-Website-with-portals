# Reporting Security, RBAC & Privacy Architecture
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal

### 1. Role-Based Access Control (RBAC)
Access to analytics consoles is strictly governed by role middleware (`requireRole()`):
- **Super Admin (`super_admin`):** Unrestricted access across all partner companies, manufacturing plants, candidate sourcing repositories, and system audit logs.
- **Recruitment Admin / Recruiter (`recruiter`, `recruitment_admin`):** Access to pipeline telemetry, candidate evaluation data, and fulfillment reports.
- **Industry Admin (`industry_admin`):** Restricted strictly to their own company's requirements, plant facilities, and masked candidate profiles.
- **Industry Team Member (`industry_user`):** Limited to permissions assigned by their Industry Administrator (`view_reports` flag).

### 2. Multi-Tenant Data Isolation
- **SQL Scoping:** Every query executed within the Industry Portal enforces `WHERE mr.company_id = :scoped_cid`.
- **IDOR Defense:** Direct object references in export queries validate user company affiliation before returning rows.

### 3. DPDP Act Candidate Privacy Protection
- Full candidate names are masked as `PRAYAS-ITI-XXXX` (e.g. `PRAYAS-ITI-0042`) in all employer/industry tables and downloads.
- Direct contact details (Aadhaar, PAN, personal mobile number, full residential address) are completely excluded from industry report queries.

### 4. CSV Formula Injection Defense
- Every output cell in CSV and Excel downloads is processed through `ReportController::sanitizeExportCell()`.
- Cells beginning with `=`, `+`, `-`, `@`, `\t`, or `\r` are prefixed with `'` to prevent command execution in Excel/Calc.
