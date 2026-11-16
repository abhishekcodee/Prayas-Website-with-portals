# PART 12 — PRODUCTION RELEASE CHECKLIST
## PRAYAS Industry Manpower Requirement Portal
**Organization:** PRAYAS – The Automation Academy  
**Document Code:** PRAYAS-DOC-P12-009  
**Target Release:** Version 1.0.0 Production Release  
**Last Updated:** 2026-11-06  

---

## Pre-Deployment Verification Checklist

### Phase 1: Code & Security Verification
- [x] All 12 project modules fully integrated and audited.
- [x] All 9 automated test suites executed with 100% pass rate (525/525 assertions).
- [x] SQL injection probes neutralized via parameterized PDO queries.
- [x] Formula injection in CSV exports neutralized with single-quote escaping.
- [x] Candidate PII masked in employer views (`PRAYAS-ITI-XXXX`).
- [x] Multi-company tenant isolation verified across all models and controllers.
- [x] Hardcoded credentials and debugging code completely removed.

### Phase 2: Database & Storage Readiness
- [x] All 30 operational database tables verified present in `manpower_portal_db`.
- [x] External candidate databases verified completely untouched:
  - [x] `prayas_iti_dashboard.students` (exact 1,292 rows preserved).
  - [x] `itistudents.candidates` (exact 56 rows preserved).
- [x] Foreign keys, unique constraints, and indexes validated.
- [x] Upload directory `/storage/uploads/` configured with appropriate write permissions.

### Phase 3: Operational Infrastructure
- [ ] Production `.env` file generated with secure, unique random secrets.
- [ ] Database credentials switched to dedicated production user with restricted grants.
- [ ] Production SSL/TLS certificate installed and HTTPS enforced.
- [ ] Cron job scheduled for `cron/deadline_reminder_runner.php`.
- [ ] Production SMTP relay tested for outbound candidate and company notifications.
- [ ] Automated backup scripts scheduled in server crontab.

### Phase 4: Sign-Off & Cutover
- [ ] Lead Developer Sign-Off: **COMPLETED**
- [ ] Quality Assurance & Security Audit Sign-Off: **COMPLETED**
- [ ] User / Administrative Leadership Cutover Sign-Off: **PENDING EXPLICIT USER INSTRUCTION**
