# PART 12 — SECURITY AUDIT & VULNERABILITY REPORT
## PRAYAS Industry Manpower Requirement Portal
**Organization:** PRAYAS – The Automation Academy  
**Document Code:** PRAYAS-DOC-P12-003  
**Audit Standard:** OWASP Top 10 (2021) + DPDP Act (2023) Guidelines  
**Audit Date:** 2026-11-06  
**Auditor:** Automated Integration & Security Engine  

---

## 1. Vulnerability Assessment Matrix

| OWASP Category | Vulnerability Type | Portal Defense Implementation | Test Verification | Status |
|---|---|---|---|---|
| **A01:2021** | Broken Access Control (IDOR) | Tenant boundary checks anchored to session `company_id`. Strict cross-company blocking. | Foreign plant & requirement access blocked (`test_full_system_integration_part12.php`, Section I) | **SECURE** |
| **A02:2021** | Cryptographic Failures | Passwords hashed using BCRYPT (cost 12). SSL/TLS session cookies with `HttpOnly`, `SameSite=Lax`. | Hashing algorithm verified BCRYPT; cookies strictly inspected | **SECURE** |
| **A03:2021** | Injection (SQL Injection) | 100% Parameterized PDO prepared statements. Zero dynamic SQL string concatenation. | Tested against standard `' OR '1'='1' --` payloads | **SECURE** |
| **A03:2021** | Injection (Formula Injection) | All CSV/Excel exports sanitize leading characters (`=`, `+`, `-`, `@`, `\t`, `\r`) with single quotes. | Neutralization verified for 4 malicious formula vectors | **SECURE** |
| **A03:2021** | Injection (Cross-Site Scripting) | Output sanitized using `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')`. | View templates audited across all 12 modules | **SECURE** |
| **A04:2021** | Insecure Design | Automatic validation engine rejects invalid business parameters (e.g. inverted salary, age < 18). | 6 invalid constraint defect tests pass cleanly | **SECURE** |
| **A05:2021** | Security Misconfiguration | `session.use_only_cookies = 1`, `session.use_strict_mode = 1`, debug stack traces disabled in production. | Session configuration assertions pass | **SECURE** |
| **A07:2021** | Identification & Auth Failures | Sequential failed login attempts track brute-force counters; generic error responses prevent username enumeration. | Non-existent user login returns generic message | **SECURE** |
| **A08:2021** | Software & Data Integrity | CSRF token validation on all state-altering POST/PUT mutations. | CSRF test passes across auth & actions | **SECURE** |
| **DPDP 2023** | Candidate PII Privacy | Direct candidate names and contact details masked (`PRAYAS-ITI-XXXX`) until explicit employer release. | Masking and unshared candidate privacy tests pass | **SECURE** |

---

## 2. Multi-Tenant Data Isolation Analysis

In a multi-company manufacturing platform, data leakage between competitor industries is a critical risk. The PRAYAS portal implements strict defense-in-depth isolation:

1. **Session-Anchored Company Binding:**
   - Client-provided `company_id` values in `$_GET` or `$_POST` are never trusted for company-scoped actions.
   - The helper `getCompanyId()` resolves strictly to `$_SESSION['company_id']`.
2. **Controller-Level Ownership Verification:**
   - Every fetch, update, cancel, or status transition query includes `WHERE company_id = :cid`.
   - When Company B attempts to access Company A's plant or requirement, the system returns `null` or `['success' => false, 'message' => 'Requirement not found or access denied.']`.
3. **Database Relational Constraints:**
   - Foreign keys bind `manufacturing_plants` and `manpower_requirements` to `companies(id)`.
   - Foreign key integrity ensures orphaned multi-tenant records cannot exist.

---

## 3. Candidate Privacy & DPDP Compliance

In compliance with India's Digital Personal Data Protection (DPDP) Act:

- **Redaction of Sensitive Identifiers:**
  Aadhaar numbers, exact dates of birth, residential street addresses, and candidate personal contact numbers are strictly excluded from employer-facing matching queries.
- **Controlled Release Workflow:**
  Candidates matched by the algorithmic engine remain anonymized as `PRAYAS-ITI-XXXX` until the platform administrator or authorized recruiter performs the **"Share with Industry"** action.
- **Read-Only Candidate Sourcing:**
  The portal never writes, modifies, or deletes records in `prayas_iti_dashboard.students` or `itistudents.candidates`.

---

## 4. Audit Conclusion

The security posture of the PRAYAS Industry Manpower Requirement Portal conforms to enterprise standards. No high or critical vulnerabilities were identified during penetration testing and automated security audits.
