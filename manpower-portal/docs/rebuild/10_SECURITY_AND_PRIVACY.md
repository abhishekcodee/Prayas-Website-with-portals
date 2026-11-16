# 10 — SECURITY, TENANT ISOLATION & CANDIDATE PRIVACY (DPDP)
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal
### Cycle 2.0 Modernization & Enterprise Rebuild

---

## 1. Multi-Tenant Data Isolation

The portal enforces multi-tenant boundaries between employer companies:
- **Tenant Scope Binding**: Every SQL query and controller action in `PipelineController` and `views/industry/recruitment_pipeline.php` binds `mr.company_id = :cid`.
- **IDOR Defense**: All interview feedback submissions, joining confirmations, offer letter views, and timeline AJAX requests verify that the underlying requisition belongs to the caller's authorized company. Unauthorized attempts result in immediate HTTP 403 / access denial.
- **Cross-Company Isolation Test**: Verified by `test_recruitment_pipeline_part10_suite.php` (Section 9), proving Company B cannot view or manipulate Company A's pipeline or offer letters.

---

## 2. DPDP Candidate Privacy Compliance

To adhere to the Digital Personal Data Protection Act (DPDP):
1. **PII Masking**: In employer-facing views, direct candidate names are replaced with standardized anonymous identifiers:
   $$\mathbf{PRAYAS\text{-}ITI\text{-}XXXX}$$
2. **Redaction of Sensitive Identifiers**: Aadhaar numbers, PAN cards, personal phone numbers, and domestic home addresses are completely excluded from the projection sent to industry dashboards.
3. **Sharing Authorization Gate**: Unshared candidates in the initial `Matched` stage are completely invisible to employer portals (`cm.is_shared_with_industry = 1 OR cm.status != 'Matched'`).
4. **External Database Immutability**: All external candidate databases (`prayas_iti_dashboard` and `itistudents`) are strictly read-only and immutable. Zero `UPDATE`, `INSERT`, `DELETE`, or `ALTER` statements are permitted on external candidate tables.
