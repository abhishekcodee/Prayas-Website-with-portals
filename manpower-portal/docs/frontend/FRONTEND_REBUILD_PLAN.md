# PRAYAS — Frontend Rebuild Implementation Plan
**Platform**: PRAYAS Industry Manpower Requirement Portal  
**Cycle**: Frontend Rebuild 3.0 (Powered by Google Stitch)  
**Execution Strategy**: Systematic Stage-by-Stage Implementation with Strict Backend Preservation

---

## 1. Core Principles & Safety Boundaries

1. **Frontend-ONLY Execution**:
   - Zero modifications to SQL schemas, migrations, or database tables.
   - Zero modifications to controllers, models, or business logic (`RequirementController`, `IndustryController`, `ValidationEngine`, etc.).
   - Zero changes to authentication guards, CSRF validation, or multi-tenant company isolation logic.
   - Zero framework replacements (preserves native PHP views + CSS/JS).

2. **Google Stitch As Single Source of Design Truth**:
   - Stitch Project: `projects/15435729254100010793`
   - Design System Asset: `assets/860f401e0734484681d2048a05082c09`
   - Generated reference screens utilized for exact layouts, typography, spacing, and micro-interactions.

3. **Responsive Discipline**:
   - Mobile responsiveness implemented with non-destructive CSS media queries and drawer sidebars.
   - The desktop view is preserved and verified to never collapse or wrap unintentionally on screens ≥ 1200px.

---

## 2. Stage Breakdown & Execution Roadmap

### Stage 1: Existing Frontend Audit & Screen Inventory (COMPLETED)
- Complete inventory of all 35+ portal views across roles (Public/Auth, Industry Admin, Super Admin, Recruiter).
- Mapped all backend variables, form handlers, and controllers to prevent regressions.
- Created `docs/frontend/FRONTEND_SCREEN_INVENTORY.md`.

### Stage 2: Stitch Design System & Reference Screens (COMPLETED)
- Created dedicated Stitch Project `projects/15435729254100010793`.
- Uploaded brand `DESIGN.md` containing PRAYAS colors, typography, components, and layout tokens.
- Generated Stitch Design System `assets/860f401e0734484681d2048a05082c09`.
- Generated 4 high-fidelity Stitch screens:
  1. Executive Dashboard (Desktop)
  2. Candidate Sourcing & Matching Talent Pool (Desktop)
  3. Multi-Step Manpower Requisition Wizard (Desktop)
  4. Mobile Responsive Dashboard & Navigation (Mobile)
- Created `docs/frontend/STITCH_DESIGN_SYSTEM.md`.

### Stage 3: Core Design CSS Framework & Shared Layout Shell
- Modernize `assets/css/dashboard.css` with the exact Stitch CSS custom properties, utility classes, and components.
- Upgrade `includes/header.php`, `includes/sidebar.php`, `includes/navbar.php`, `includes/alerts.php`, and `includes/footer.php` with Stitch styling.
- Implement mobile off-canvas drawer navigation for tablet/mobile viewports.

### Stage 4: Authentication & Public Screens
- Rebuild `views/auth/login.php` with premium corporate split layout or centered glass card, PRAYAS logo, and validation states.
- Rebuild `views/auth/register.php` with multi-step company onboarding form.
- Rebuild `views/auth/forgot_password.php`, `reset_password.php`, and `change_password.php`.

### Stage 5: Industry Dashboard & Requisitions Management
- Rebuild `views/industry/dashboard.php` using the Stitch Executive Dashboard layout (KPI cards, trade filter chips, active requisitions table).
- Rebuild `views/industry/requirements.php` with modern search, status filter pills, trade tags, and pagination.
- Rebuild `views/industry/requirement_details.php` with clean tabbed specification dossier, timeline view, and validation audit log.

### Stage 6: Manpower Requisition Creation Wizard
- Rebuild `views/industry/requirement_form.php` using the Stitch 8-Step Wizard design.
- Implement dynamic trade allocation auto-sum table, salary range sliders, and sticky live summary card.
- Rebuild `views/industry/requirement_review.php` (Step 8 confirmation screen).
- Rebuild `views/industry/validation_summary.php` diagnostic defect report.

### Stage 7: ITI Candidate Sourcing & Matching Engine UI
- Rebuild `views/industry/candidate_matches.php` and `views/recruiter/candidate_matching.php` using the Stitch Talent Pool design.
- Implement top recommended candidate highlight cards, match score badges (98%, 94%, etc.), technical skill chips, and candidate profile drawer.

### Stage 8: Recruitment Pipeline & Lifecycle Tracking UI
- Rebuild `views/industry/recruitment_pipeline.php` and `views/recruiter/recruitment_pipeline.php` with 16-stage Kanban board and list view.
- Rebuild `views/industry/offer_letter_view.php` with formal corporate printable offer letter styling.

### Stage 9: Industry Plants & Team Management
- Rebuild `views/industry/plants.php`, `plant_form.php`, and `plant_details.php`.
- Rebuild `views/industry/team_members.php` and `invite_member.php`.
- Rebuild `views/industry/company_profile.php` and `my_profile.php`.

### Stage 10: Reports & Executive Analytics Dashboard
- Rebuild `views/industry/reports.php`, `views/super_admin/reports.php`, and `views/recruiter/recruitment_reports.php`.
- Integrate modern Chart.js visual styles matching PRAYAS palette (`#EF6B6B`, `#0F172A`, `#16A34A`, `#2563EB`).

### Stage 11: Super Admin Governance Suite
- Rebuild `views/super_admin/dashboard.php`, `companies.php`, `company_details.php`, `company_form.php`, and `company_limits.php`.
- Rebuild `views/super_admin/users.php`, `user_details.php`, `user_form.php`, and `roles_permissions.php`.
- Rebuild `views/super_admin/audit_logs.php`, `system_settings.php`, and `industries.php`.

### Stage 12: End-to-End Responsive QA & Browser Verification
- Cross-browser testing on Chrome, Edge, Firefox, and Safari viewports.
- Responsive audit across Desktop (1920px, 1440px), Laptop (1280px), Tablet (768px), and Mobile (390px).
- Verify zero console errors, zero layout breaking, and 100% backend form handling compatibility.
