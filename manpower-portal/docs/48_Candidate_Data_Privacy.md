# 48 — Candidate Data Privacy & Redaction Standards

## Document Identifier: SPEC-PRIVACY-2026-V1.0
**Project Title:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Compliance Standards:** Digital Personal Data Protection (DPDP) Act & Fair Recruitment Standards  

---

## 1. Statutory Privacy Mandate

To safeguard student privacy and prevent discriminatory profiling, the PRAYAS portal adheres to strict data minimization, role-based redaction, and multi-tenant isolation protocols across all sourcing and matching operations.

---

## 2. Redaction & Projection Rules

| Data Attribute | Source Field | Visibility: Recruiter Console | Visibility: Industry Portal | Redaction Policy |
| :--- | :--- | :---: | :---: | :--- |
| **Aadhaar Number** | `students.aadhaar_no` | **REDACTED** | **REDACTED** | Stripped at the SQL projection layer; never retrieved into application memory. |
| **Primary Phone** | `students.mobile`, `candidates.mobile_number` | Visible for Outreach | **REDACTED** | Industry users cannot view personal phone numbers until formal interview scheduling. |
| **Parents Phone** | `students.parents_mobile` | **REDACTED** | **REDACTED** | Internal emergency contact only; excluded from all portal views. |
| **Full Residential Address** | `students.address`, `candidates.address` | Generalized | City & District Only | Full residential address suppressed to protect student domestic privacy. |
| **Candidate Name** | `students.name`, `candidates.full_name` | Full Name | Standard Name | Full legal name displayed only for shared and verified candidates. |
| **Internal Evaluator Notes** | `candidates.feedback` | Visible to Recruiters | **REDACTED** | Internal academic feedback is strictly restricted from external employer view. |

---

## 3. Multi-Tenant Scoping & Anti-IDOR Enforcement

1. **Company Isolation:** Industry Partner users can only query candidate matches where `manpower_requirements.company_id = $_SESSION['company_id']`. Cross-tenant candidate exploration is blocked at the database query level.
2. **Release Gate Control:** Unreleased matches (`is_shared_with_industry = 0`) are filtered out from the employer's dashboard until authorized by an academic recruiter.
3. **No Automated Harvesting:** Bulk extraction of student contact rosters via automated scraping is prevented by paginated limits and session token verification.

