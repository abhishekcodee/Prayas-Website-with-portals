# PRAYAS — Frontend Rebuild Implementation Tracker
**Platform**: PRAYAS Industry Manpower Requirement Portal  
**Cycle**: Frontend Rebuild 3.0 (Powered by Google Stitch)  
**Status**: Stage 1 & 2 Complete | Stage 3 Ready for Implementation

---

## Stage Progress Summary

- [x] **Stage 1: Existing Frontend Audit & Screen Inventory** (100%)
- [x] **Stage 2: Google Stitch Design System & Reference Screens** (100%)
- [ ] **Stage 3: Shared Layout Shell & CSS Framework Modernization** (0%)
- [ ] **Stage 4: Public & Authentication Screens** (0%)
- [ ] **Stage 5: Industry Dashboard & Requisitions Directory** (0%)
- [ ] **Stage 6: Multi-Step Manpower Requisition Wizard** (0%)
- [ ] **Stage 7: ITI Candidate Sourcing & Matching Talent Pool** (0%)
- [ ] **Stage 8: Recruitment Pipeline & Offer Letter UI** (0%)
- [ ] **Stage 9: Manufacturing Plants & Team Management** (0%)
- [ ] **Stage 10: Reports & Executive Analytics Dashboard** (0%)
- [ ] **Stage 11: Super Admin Governance Suite** (0%)
- [ ] **Stage 12: End-to-End Responsive QA & Cross-Browser Verification** (0%)

---

## Detailed Screen Checklist

### 1. Shared Partials & Layout Shell
- [ ] `assets/css/dashboard.css` (Stitch CSS custom properties, responsive layout engine)
- [ ] `includes/header.php` (Fonts, viewport meta, CSS link)
- [ ] `includes/sidebar.php` (Deep navy `#0F172A`, coral active indicators, role logic)
- [ ] `includes/navbar.php` (Breadcrumbs, plant selector, notifications, user dropdown)
- [ ] `includes/alerts.php` (Clean non-blocking dismissible notification banners)
- [ ] `includes/footer.php` (Enterprise footer, accreditation badge, JS scripts)
- [ ] `includes/pagination.php` (Modern page jumper, showing X-Y of Z records)

### 2. Public & Authentication Views (`views/auth/`)
- [ ] `views/auth/login.php` (Executive split login screen with PRAYAS branding)
- [ ] `views/auth/register.php` (Company registration with industry sector selector)
- [ ] `views/auth/forgot_password.php` (Clean recovery request card)
- [ ] `views/auth/reset_password.php` (Secure password reset with strength meter)
- [ ] `views/auth/change_password.php` (Account security password update)

### 3. Industry Admin Views (`views/industry/`)
- [ ] `views/industry/dashboard.php` (Executive KPI cards, trade quick filter, active reqs)
- [ ] `views/industry/requirements.php` (Filter pills, search, requisition cards/table)
- [ ] `views/industry/requirement_form.php` (8-step wizard, auto-sum trade builder, live summary)
- [ ] `views/industry/requirement_details.php` (Specification tabs, trade breakdown, timeline)
- [ ] `views/industry/requirement_review.php` (Step 8 final review & auto-validation checklist)
- [ ] `views/industry/validation_summary.php` (Defects, warnings, suggested fixes)
- [ ] `views/industry/candidate_matches.php` (Top matches, trade filters, match scores 80%+)
- [ ] `views/industry/recruitment_pipeline.php` (16-stage Kanban board and candidate table)
- [ ] `views/industry/offer_letter_view.php` (Corporate printable offer letter document)
- [ ] `views/industry/plants.php` (Plant facilities card grid, capacity meters)
- [ ] `views/industry/plant_form.php` (Plant creation/edit with geo coordinates)
- [ ] `views/industry/plant_details.php` (Plant profile, assigned staff, active requisitions)
- [ ] `views/industry/team_members.php` (User roster, plant assignments, status toggles)
- [ ] `views/industry/invite_member.php` (Invitation dialog with role selector)
- [ ] `views/industry/company_profile.php` (Company dossier, CIN/GSTIN, plant stats)
- [ ] `views/industry/my_profile.php` (Personal settings, notifications preference)
- [ ] `views/industry/notifications.php` (Notification feed, mark-as-read controls)
- [ ] `views/industry/reports.php` (Recruitment velocity, fulfillment rate charts)

### 4. Recruiter Views (`views/recruiter/`)
- [ ] `views/recruiter/candidate_matching.php` (Candidate sourcing search & filtering)
- [ ] `views/recruiter/recruitment_pipeline.php` (Recruiter interview scheduling & feedback)
- [ ] `views/recruiter/recruitment_reports.php` (Recruiter throughput & time-to-fill)
- [ ] `views/recruiter/offer_letter_view.php` (Offer generation & joining verification)

### 5. Super Admin Governance Views (`views/super_admin/`)
- [ ] `views/super_admin/dashboard.php` (Multi-tenant global KPI cards, system telemetry)
- [ ] `views/super_admin/companies.php` (Enterprise tenant directory, verification toggles)
- [ ] `views/super_admin/company_details.php` (Deep company audit, plants, compliance)
- [ ] `views/super_admin/company_form.php` (Create/edit tenant, quota configuration)
- [ ] `views/super_admin/company_limits.php` (Quota allocation for plants & vacancies)
- [ ] `views/super_admin/users.php` (Global user accounts, lockout controls)
- [ ] `views/super_admin/user_details.php` (User profile dossier, login audit logs)
- [ ] `views/super_admin/user_form.php` (Create/edit administrative users)
- [ ] `views/super_admin/roles_permissions.php` (RBAC permission matrix with toggle chips)
- [ ] `views/super_admin/audit_logs.php` (Immutable security audit logs, actor filters)
- [ ] `views/super_admin/notifications.php` (System broadcast composer & delivery logs)
- [ ] `views/super_admin/system_settings.php` (Cron health, email gateway, platform config)
- [ ] `views/super_admin/industries.php` (Industrial sectors directory)
- [ ] `views/super_admin/industry_form.php` (Industrial category editor)
- [ ] `views/super_admin/reports.php` (Macro industry reports, trade demand analytics)
