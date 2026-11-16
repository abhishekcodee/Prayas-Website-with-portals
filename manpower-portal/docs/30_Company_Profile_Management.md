# 30 — Company Profile Management & Verification Workflow

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Company Self-Service Profile & Compliance Management  
**Document ID:** DOC-IND-30  
**Status:** Approved & Implemented  

---

## 1. Overview & Capabilities

The **Company Profile Management Module** (`views/industry/company_profile.php`) empowers industry employers to manage their public profile, corporate overview, statutory numbers, and HR contacts.

### Managed Attributes
- **Corporate Legal Identity:** Registered Legal Name, Registration Number (CIN / LLPIN / Udyam), Industry Sector.
- **Head Office Coordinates:** Address, State, District, City, PIN Code.
- **Digital Branding:** Company Website URL, Corporate Logo (PNG, JPG, WebP $\le$ 2MB), Company Description & Manufacturing Capabilities.
- **Primary HR Contact:** Contact Person, Official Email, Direct Phone Number.

---

## 2. Verification Security & Re-Verification Guard

### Administrative Verification Isolation
1. **Immutable Verification Status:**
   Industry users and administrators **CANNOT** modify `verification_status`, `verified_by`, `verified_at`, or `rejection_reason`.
   The profile update controller (`IndustryController::updateCompanyProfile`) explicitly ignores any client attempt to supply verification attributes.
   Only Super Admins can alter verification states via `CompanyController::updateVerificationStatus()`.

2. **Sensitive Details Modification Detection:**
   If an already **Verified / Active** enterprise modifies statutory legal fields (`company_name` or `registration_number`), the platform executes the following automated compliance routine:
   - Sets `sensitive_modified_at = NOW()` on the `companies` record.
   - Logs an audit event `COMPANY_PROFILE_UPDATED` with `sensitive_modified = true`.
   - Dispatches a high-priority system notification to all Super Admins alerting them to review the modified corporate identity.
   - Informs the user via UI banner that statutory changes have triggered a re-verification review.

---

## 3. Logo Upload Security Protocol

- Handled strictly via server-side file inspection:
  - Allowed MIME types: `image/jpeg`, `image/png`, `image/webp`.
  - Maximum file size: 2,097,152 bytes (2MB).
  - Sanitized target path: `uploads/logos/logo_{companyId}_{randomHex}.{ext}`.
  - Prevents executable script upload, directory traversal, and MIME spoofing.

