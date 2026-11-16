# 01 — Complete Project Analysis

## Project: Industry Manpower Requirement Portal
**Organization:** PRAYAS – The Automation Academy  
**Analysis Date:** 2026-11-05  
**Lead Architect & Analyst:** Senior Full-Stack Architect  
**Project Phase:** Part 01 — Project Analysis & Safe Development Foundation  

---

## 1. Project Context & Environment Inspection

A comprehensive inspection of the workspace `C:\Manpower Portal` and surrounding XAMPP environment was conducted on 2026-11-05.

### Environment & Server Characteristics:
- **Operating System:** Windows 10/11 Professional
- **Web Server:** Apache 2.4.58 (Win64) configured under `C:\xampp\apache`
- **PHP Interpreter:** PHP 8.2.12 (CLI / Apache Handler) configured under `C:\xampp\php`
- **Database Engine:** MariaDB 10.4.32 / MySQL Community Server running on `localhost:3306`
- **Active Workspace:** `C:\Manpower Portal`
- **Sibling Ecosystem (Read-Only Inspection):**
  - `C:\xampp\htdocs\Prayas The Automation Academy` — Public web application, employer portal copy, and visual asset repositories.
  - `C:\xampp\htdocs\prayas-portal` — Student aptitude assessment and student candidate database portal.
  - `C:\xampp\htdocs\student-portal` — Student profile and resume builder portal.

---

## 2. Detailed Workspace Inventory

The `C:\Manpower Portal` workspace currently contains the foundational architecture and backup mechanism established for PRAYAS:

```
c:\Manpower Portal\
├── api/                     (API endpoint directory for validation and candidate matching)
├── assets/
│   ├── css/                 (Custom and framework styling directory)
│   ├── js/                  (Interactive client-side scripting directory)
│   └── images/              (Brand assets: logo.png, favicon.png)
├── config/                  (Application constants, database, and security configurations)
├── controllers/             (MVC Application controllers)
├── database/
│   ├── backups/             (Timestamped SQL safety dumps)
│   │   ├── itistudents_backup_20261105_103327.sql (54.3 KB)
│   │   └── prayas_iti_dashboard_backup_20261105_103327.sql (963.8 KB)
│   ├── migrations/          (Idempotent SQL schema migrations)
│   ├── seeders/             (Initial seed data)
│   └── run_backup.bat       (Non-destructive automated backup execution script)
├── docs/                    (System and architectural documentation)
├── includes/                (Shared view partials, auth guards, headers, and footers)
├── models/                  (Data access layer and matching calculation models)
├── views/
│   ├── auth/                (Login, registration, password recovery views)
│   ├── industry/            (Industry Admin requirement submission and tracking)
│   ├── recruiter/           (Recruiter screening, interview feedback, and joining tracking)
│   ├── recruitment_admin/   (Recruitment team management, pipeline governance)
│   └── super_admin/         (System administration, company approvals, audit logs)
└── CHANGELOG.md             (Version and audit modification log)
```

---

## 3. Implementation Status Assessment

| Classification | Identified Modules / Components | Current State & Impact |
| :--- | :--- | :--- |
| **Already Implemented** | Directory layout, branding assets (`logo.png`, `favicon.png`), backup scripts (`run_backup.bat`), baseline SQL dumps of external candidate databases. | Foundation operational; provides a clean, safe scaffold. |
| **Partially Implemented** | Architectural design documents and business rule definitions. | High-level rules defined; requires translation into executable PHP code and SQL migrations. |
| **Missing (To Be Built)** | Database schema (`manpower_portal_db`), authentication & session controllers, RBAC authorization guards, Manpower Requirement submission form, server-side auto-validation engine, candidate matching engine, interview scheduling, offer & joining tracking, responsive views. | Core portal features to be built systematically in subsequent development parts. |
| **Needs Improvement** | Unified schema bridge connecting `itistudents.candidates` and `prayas_iti_dashboard.students` safely without duplicating or modifying existing records. | Requires read-only PDO abstraction models. |
| **Must NOT Be Modified** | External MySQL databases: `itistudents`, `prayas_iti_dashboard`, `prayas_portal`, `hrms_db`, and sibling directories under `C:\xampp\htdocs\`. | Must remain 100% read-only and immutable. |

---

## 4. Key Architectural Findings

1. **Non-Destructive Integration Strategy:**
   * PRAYAS has existing verified candidate repositories (`itistudents.candidates` with 56 candidates and `prayas_iti_dashboard.students` with 1,292 students).
   * The Industry Manpower Requirement Portal must never alter or truncate these tables. It will reference them via read-only foreign keys or source IDs stored in `candidate_matches`.
2. **Dedicated Database Instance:**
   * A completely isolated database named `manpower_portal_db` will be provisioned in Phase 3 to house industry accounts, requirements, interviews, and audit logs.
3. **Enterprise UI Guidelines:**
   * The UI must reflect PRAYAS corporate branding: Deep Navy (`#0F2042`), Deep Blue (`#003366`), Amber Gold (`#E89218`), and clean neutral slate backgrounds.

