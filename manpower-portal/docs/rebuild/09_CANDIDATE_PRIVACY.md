# PART 09 — CANDIDATE PRIVACY PROTECTION & DPDP COMPLIANCE SPECIFICATION

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Data Privacy & Anonymization Engine (`IndustryController.php`, `CandidateSourceModel.php`)  
**Compliance Standard:** Digital Personal Data Protection (DPDP) Act & Enterprise Multi-Tenant Isolation  
**Status:** COMPLETED & VERIFIED (Part 09 Rebuild)  

---

## 1. Regulatory Context & Privacy Objectives

The Industry Manpower Requirement Portal facilitates sourcing between vocational students from accredited ITI colleges and industrial manufacturing companies. Under Indian data privacy regulations (Digital Personal Data Protection Act - DPDP) and strict enterprise privacy standards:
1. **Personally Identifiable Information (PII)** of students must never be indiscriminately leaked or made accessible to third-party industrial employers prior to explicit recruiter screening and authorization.
2. Sensitive identity numbers (Aadhaar, PAN, Bank Details) must **never** be projected to industry portals under any circumstances.
3. Multi-tenant company isolation must strictly prevent Company A from inspecting Company B's matched candidate pool.

---

## 2. Redaction & Field Sanitization Matrix

| Data Category | Field Name | Recruiter Portal View | Industry Employer View | Protection Mechanism |
| :--- | :--- | :---: | :---: | :--- |
| **Government Identifiers** | `aadhaar_no`, `pan_card` | **REDACTED** | **COMPLETELY EXCLUDED** | Excluded at database query projection level in `CandidateSourceModel` |
| **Direct Contact** | `mobile_number`, `phone_number` | Visible for phone screening | **REDACTED / MASKED** | Excluded in `IndustryController::getCandidateMatches()` |
| **Email Address** | `email` | Visible for candidate alerts | **REDACTED / MASKED** | Excluded in `IndustryController::getCandidateMatches()` |
| **Full Residential Address** | `permanent_address`, `address_line1` | Visible | **REDACTED** (Only District & State shown) | Sanitized in query projection |
| **Candidate Identity** | Full Legal Name | Visible | **Anonymized Reference (`PRAYAS-ITI-XXXX`)** | Default masked in initial view until explicit recruiter profile release |
| **Recruiter Screening Notes** | `evaluator_feedback`, `remarks` | Visible to recruiter | **EXCLUDED** | Stored in recruiter audit table |
| **Technical Attributes** | Trade, Batch, Skills, Score | Visible | **FULL TRANSPARENCY** | 7-factor breakdown displayed in explanation modal |

---

## 3. Recruiter Profile Authorization Workflow

Candidate profiles in `candidate_matches` start with `is_shared_with_industry = 0`.
Only after a recruiter screens the candidate and executes `shareWithIndustry($matchId)`:
- `is_shared_with_industry` transitions to `1`.
- `shared_at` timestamp is permanently recorded.
- Audit entry is logged in `candidate_shortlist_actions` with `action = 'SHARE_WITH_INDUSTRY'`.
- The profile becomes viewable by the designated manufacturing company in their candidate matches portal.

---

## 4. Multi-Tenant Cross-Company Access Prevention (IDOR Safeguards)

In `IndustryController.php`:
```php
$conditions = [
    "mr.company_id = :cid",
    "(cm.is_shared_with_industry = 1 OR cm.status != 'Matched')"
];
```
- The query binds the authenticated company ID from the secure server session (`$_SESSION['company_id']`).
- Attempts by Company B to query candidates matched to Company A's requirements return an empty dataset (`0 records`), completely mitigating Insecure Direct Object Reference (IDOR) attacks.

