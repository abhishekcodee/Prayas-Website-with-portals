# PRAYAS — Frontend Screen Inventory & View Mapping
**Portal**: PRAYAS – The Automation Academy | Industry Manpower Requirement Portal  
**Cycle**: Frontend Rebuild 3.0 (Powered by Google Stitch)  
**Date**: November 2026  
**Status**: Stage 1 Audit Complete

---

## 1. Inventory Summary by Role

| View Group / User Role | Total Views | File Location | Primary Workflow / Responsibility |
|:---|:---:|:---|:---|
| **Public & Authentication** | 5 | `views/auth/` | Sign-in, registration, password recovery, session control |
| **Industry / Company Admin** | 18 | `views/industry/` | Requirement lifecycle, plant management, candidate shortlisting, pipeline tracking |
| **Super Admin Governance** | 15 | `views/super_admin/` | Multi-company governance, system audits, user administration, global reports |
| **Recruiter Management** | 4 | `views/recruiter/` | ITI sourcing pools, interview assessments, pipeline facilitation |
| **Shared Partials & Shell** | 6 | `includes/` | Header, sidebar, navbar, alert notices, footer, pagination |
| **Total Core Screens** | **35+** | Project-wide | Full enterprise recruitment lifecycle |

---

## 2. Authentication Views (`views/auth/`)

| File Name | Route / URL | Form Actions & POST Handlers | Key View Variables & Dependencies |
|:---|:---|:---|:---|
| `login.php` | `/views/auth/login.php` | `POST /views/auth/login.php` (`email`, `password`, `csrf_token`) | `$_SESSION['flash_error']`, `$_SESSION['flash_success']`, `return_url` |
| `register.php` | `/views/auth/register.php` | `POST /views/auth/register.php` (company & admin registration) | `$industries`, `$companyTypes`, `$csrf_token`, errors array |
| `forgot_password.php` | `/views/auth/forgot_password.php` | `POST /views/auth/forgot_password.php` (`email`) | `$successMessage`, `$errorMessage` |
| `reset_password.php` | `/views/auth/reset_password.php` | `POST /views/auth/reset_password.php` (`token`, `new_password`) | `$tokenValid`, `$userId`, validation states |
| `change_password.php` | `/views/auth/change_password.php` | `POST /views/auth/change_password.php` (`current_pw`, `new_pw`) | `$user`, auth session guard |

---

## 3. Industry Admin Views (`views/industry/`)

| File Name | Route / URL | Core Responsibilities | Backend Variables & Controllers |
|:---|:---|:---|:---|
| `dashboard.php` | `/views/industry/dashboard.php` | Executive KPI cards, recent requisitions, recruitment pipeline overview | `$stats`, `$recentReqs`, `$activePipelines`, `$company` |
| `requirements.php` | `/views/industry/requirements.php` | Requisition directory, status filter pills, vacancy progress | `$requirements`, `$filterStatus`, `$tradeFilter`, `$plants` |
| `requirement_form.php` | `/views/industry/requirement_form.php` | 8-step requisition wizard, auto-sum trade builder, drafting | `$plants`, `$company`, `$existingReq`, `$initialStep`, `$fData` |
| `requirement_details.php` | `/views/industry/requirement_details.php` | Requisition specification, trade breakdown, timeline, validation log | `$requirement`, `$trades`, `$history`, `$validationSummary` |
| `requirement_review.php` | `/views/industry/requirement_review.php` | Step 8 review screen, final submission gate, validation checklist | `$requirement`, `$plant`, `$trades`, `$validationErrors` |
| `validation_summary.php` | `/views/industry/validation_summary.php` | Diagnostic report of validation rules, warnings, and suggested fixes | `$reqId`, `$validationRun`, `$defects`, `$advisories` |
| `candidate_matches.php` | `/views/industry/candidate_matches.php` | ITI sourcing engine, trade filters, match scores (80%+), actions | `$requirement`, `$matchedCandidates`, `$matchThreshold`, `$filters` |
| `recruitment_pipeline.php` | `/views/industry/recruitment_pipeline.php` | 16-stage recruitment tracker, candidate Kanban/list, stage transitions | `$stages`, `$candidatesByStage`, `$requirementFilter` |
| `offer_letter_view.php` | `/views/industry/offer_letter_view.php` | Formal offer letter document, salary breakdown, joining terms | `$offer`, `$candidate`, `$requirement`, `$company` |
| `plants.php` | `/views/industry/plants.php` | Manufacturing plants list, active status, capacity limits | `$plants`, `$companyId`, `$plantCtrl` |
| `plant_form.php` | `/views/industry/plant_form.php` | Create/edit plant facility, industrial zone, contact lead | `$plant`, `$action`, `$isEdit` |
| `plant_details.php` | `/views/industry/plant_details.php` | Plant overview, active requisitions in plant, staff | `$plant`, `$plantReqs`, `$plantStats` |
| `team_members.php` | `/views/industry/team_members.php` | Company user directory, role assignments (`industry_user`) | `$teamMembers`, `$currentUserRole` |
| `invite_member.php` | `/views/industry/invite_member.php` | Send invitation link or create user credentials | `$roles`, `$assignedPlants` |
| `company_profile.php` | `/views/industry/company_profile.php` | Corporate details, CIN/GSTIN, logo, verification badges | `$company`, `$industryType`, `$profileCompleteness` |
| `my_profile.php` | `/views/industry/my_profile.php` | Personal credentials, email, password update, activity log | `$user`, `$recentActivities` |
| `notifications.php` | `/views/industry/notifications.php` | In-app alerts, validation notices, reminder feeds | `$notifications`, `$unreadCount` |
| `reports.php` | `/views/industry/reports.php` | Recruitment velocity, trade demand charts, fulfillment rates | `$tradeReports`, `$fulfillmentRates`, `$exportUrl` |

---

## 4. Super Admin Governance Views (`views/super_admin/`)

| File Name | Route / URL | Core Responsibilities | Backend Variables & Controllers |
|:---|:---|:---|:---|
| `dashboard.php` | `/views/super_admin/dashboard.php` | Global multi-company KPI counters, candidate pool stats, alerts | `$globalStats`, `$pendingApprovals`, `$platformHealth` |
| `companies.php` | `/views/super_admin/companies.php` | All registered manufacturing companies, verification status | `$companies`, `$industryFilter`, `$statusFilter` |
| `company_details.php` | `/views/super_admin/company_details.php` | In-depth company dossier, plants, requisitions, compliance | `$company`, `$plants`, `$requirements`, `$users` |
| `company_form.php` | `/views/super_admin/company_form.php` | Create/edit enterprise tenant, configure subscription tiers | `$companyData`, `$industries`, `$mode` |
| `company_limits.php` | `/views/super_admin/company_limits.php` | Manage vacancy quotas, active requisition caps, plant limits | `$companyLimits`, `$usageTelemetry` |
| `users.php` | `/views/super_admin/users.php` | Global user directory across all tenants, lockout controls | `$users`, `$roles`, `$tenantFilter` |
| `user_details.php` | `/views/super_admin/user_details.php` | User audit dossier, permission grants, recent auth history | `$targetUser`, `$loginLogs`, `$roleInfo` |
| `user_form.php` | `/views/super_admin/user_form.php` | Create/edit super admin or assign cross-tenant permissions | `$userData`, `$allRoles`, `$tenantList` |
| `roles_permissions.php` | `/views/super_admin/roles_permissions.php` | RBAC matrix, permission toggles, role hierarchy | `$rolesMatrix`, `$permissionsList` |
| `audit_logs.php` | `/views/super_admin/audit_logs.php` | Immutable system event log, IP addresses, actor IDs, actions | `$auditLogs`, `$dateFilter`, `$actorFilter`, `$pagination` |
| `notifications.php` | `/views/super_admin/notifications.php` | Broadcast alerts, platform notifications, delivery logs | `$systemAlerts`, `$broadcastHistory` |
| `system_settings.php` | `/views/super_admin/system_settings.php` | Platform settings, WhatsApp/Email gateway status, cron status | `$configs`, `$cronHealth`, `$emailConfig` |
| `industries.php` | `/views/super_admin/industries.php` | Industrial sector catalog (Automotive, Heavy Machinery, etc.) | `$industriesList`, `$activeCompanyCounts` |
| `industry_form.php` | `/views/super_admin/industry_form.php` | Add/edit industrial sector definitions | `$industryData`, `$isEdit` |
| `reports.php` | `/views/super_admin/reports.php` | Cross-company executive analytics, trade demand trends | `$macroAnalytics`, `$tradeDistribution` |

---

## 5. Recruiter Portal Views (`views/recruiter/`)

| File Name | Route / URL | Core Responsibilities | Backend Variables |
|:---|:---|:---|:---|
| `candidate_matching.php` | `/views/recruiter/candidate_matching.php` | Direct ITI student matching against requisitions | `$candidates`, `$openReqs`, `$matchEngine` |
| `recruitment_pipeline.php` | `/views/recruiter/recruitment_pipeline.php` | Recruiter stage execution (Interviews, assessments) | `$activePipelines`, `$interviewSchedules` |
| `recruitment_reports.php` | `/views/recruiter/recruitment_reports.php` | Recruiter throughput metrics, offer acceptance rate | `$recruiterStats`, `$timeToFill` |
| `offer_letter_view.php` | `/views/recruiter/offer_letter_view.php` | Recruiter offer verification & candidate dispatch | `$offer`, `$candidateBioData` |

---

## 6. Shared Layout Shell (`includes/`)

1. `includes/header.php`: HTML5 `<head>`, Google Fonts (`Urbanist`, `Inter`), responsive `<meta>`, PRAYAS Design CSS & Icons.
2. `includes/sidebar.php`: Fixed 260px Deep Navy (`#0F172A`) enterprise navigation, role-based item visibility, active coral pill indicator.
3. `includes/navbar.php`: Top sticky white navbar, breadcrumbs, plant context pill, global search, notifications badge, user menu.
4. `includes/alerts.php`: Clean non-blocking dismissible notice banners (Success, Warning, Danger, Info).
5. `includes/footer.php`: Enterprise copyright, PRAYAS accreditation seal, version signature, JavaScript bundle loader.
6. `includes/pagination.php`: Modern page jumper, showing X-Y of Z records, disabled states, items per page selector.
