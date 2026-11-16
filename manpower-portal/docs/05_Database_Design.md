# 05 — Database Design & Schema Implementation

## Organization: PRAYAS – The Automation Academy
**Database Name:** `manpower_portal_db`  
**Engine:** InnoDB  
**Collation:** `utf8mb4_unicode_ci`  
**Implementation Phase:** Part 03 — Applied & Verified  
**Date:** 2026-11-05  

---

## 1. Implementation Summary

The dedicated database `manpower_portal_db` has been created and provisioned on MySQL/MariaDB with 20 tables:

```
Core Identity & RBAC:
├── schema_migrations          (Migration audit tracking)
├── roles                      (super_admin, industry_admin, recruitment_admin, recruiter)
├── permissions                (28 granular atomic permissions)
├── role_permissions           (65 active role-permission mappings)
├── system_settings            (Platform thresholds and configurations)
├── companies                  (Industrial employer profiles and plant locations)
└── users                      (Portal user accounts with BCRYPT credentials)

Requisition Management:
├── manpower_requirements      (Main requisition records with REQ-YYYYMM-XXXX IDs)
├── requirement_trades         (Specific requested ITI trades and vacancies)
├── requirement_skills         (Demanded technical skill tags)
└── requirement_status_history (Audit log of state transitions)

Candidate Matching & Sourcing:
└── candidate_matches          (Sourced candidates with 8-parameter match scores)

Recruitment Pipeline:
├── candidate_screening        (Recruiter screening notes & recommendations)
├── interviews                 (Scheduled interview rounds and locations)
├── interview_feedback         (4-point evaluation rubrics)
├── selections                 (Candidate terms and offered compensation)
├── offer_letters              (Offer issue records and reference numbers)
└── joinings                   (Confirmed candidate reporting & fulfillment accounting)

System Governance:
├── notifications              (In-app user alerts)
└── audit_logs                 (Immutable legal & security activity ledger)
```

---

## 2. External Database Read-Only Bridges

In accordance with strict non-destructive policies, existing candidate pools are accessed via read-only abstraction:
- **`prayas_iti_dashboard.students` (1,292 records):** Sourced via `Database::queryCandidateReadOnly()`.
- **`itistudents.candidates` (56 records):** Sourced via `Database::queryCandidateReadOnly()`.
- **Security Guard Verified:** Write statements (`INSERT`, `UPDATE`, `DELETE`, `DROP`, `ALTER`, `TRUNCATE`) are blocked by the driver layer, throwing a `SecurityException`.

---

## 3. Related Documentation

- For the step-by-step migration guide: [`docs/18_Database_Migration_Guide.md`](file:///c:/Manpower%20Portal/docs/18_Database_Migration_Guide.md)
- For the complete column-by-column dictionary: [`docs/19_Database_Data_Dictionary.md`](file:///c:/Manpower%20Portal/docs/19_Database_Data_Dictionary.md)
- For the full visual entity relationships: [`docs/06_ER_Diagram.md`](file:///c:/Manpower%20Portal/docs/06_ER_Diagram.md)
