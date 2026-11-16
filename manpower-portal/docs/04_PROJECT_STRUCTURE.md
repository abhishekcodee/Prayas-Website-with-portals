# 04 — Project Directory Structure Specification

## Organization: PRAYAS – The Automation Academy
**Document Reference:** DOC-PRA-04-V1  
**Target Path:** `C:\Manpower Portal`  

---

## 1. Directory Tree & Architecture

The project directory structure is strictly modular, role-isolated, and designed for enterprise readability:

```
C:\Manpower Portal\
│
├── api/                                    # Lightweight JSON endpoints for AJAX operations
│   ├── candidate_matching.php              # Real-time scoring & matching query
│   ├── check_requirement_status.php       # Live status & timeline query
│   ├── get_districts.php                   # Cascading state/district dropdowns
│   ├── get_trades.php                      # Dynamic trade category endpoint
│   └── validate_requirement.php           # Pre-submission form validation
│
├── assets/                                 # Static web assets
│   ├── css/
│   │   ├── bootstrap.min.css               # Bootstrap 5.3 Framework
│   │   ├── custom.css                      # Corporate styling for PRAYAS
│   │   └── dashboard.css                   # Enterprise dashboard & table layout
│   ├── js/
│   │   ├── bootstrap.bundle.min.js         # Bootstrap UI interactive components
│   │   ├── custom.js                       # Shared UI behaviors, alerts, and toasts
│   │   └── validation.js                   # Client-side form feedback handler
│   └── images/
│       ├── logo.png                        # Official PRAYAS emblem
│       └── favicon.png                     # Browser favicon
│
├── config/                                 # Core configuration files
│   ├── constants.php                       # System enums, status codes, salary ranges
│   ├── database.php                        # PDO database connection manager
│   └── security.php                        # CSRF token generator & XSS sanitizers
│
├── controllers/                            # Application logic controllers
│   ├── AuthController.php                  # Authentication, login, logout, password resets
│   ├── CandidateController.php             # Candidate sourcing & matching logic
│   ├── CompanyController.php               # Industry verification & profile management
│   ├── InterviewController.php             # Interview scheduling & feedback capture
│   ├── JoiningController.php               # Candidate joining & math fulfillment tracking
│   ├── ReportController.php                # Analytics & reporting metrics
│   ├── RequirementController.php           # Requisition lifecycle & auto-validation
│   └── SelectionController.php             # Selection & offer letter governance
│
├── database/                               # Database management
│   ├── backups/                            # Dated SQL dumps & recovery points
│   │   ├── itistudents_backup_*.sql        # Candidate pool backup
│   │   └── prayas_iti_dashboard_backup_*.sql # Candidate dashboard backup
│   ├── migrations/                         # Idempotent versioned SQL migration scripts
│   ├── seeders/                            # Default roles, permissions, and admin user
│   └── run_backup.bat                      # One-click backup orchestration script
│
├── docs/                                   # Complete system documentation suite
│   ├── 01_PROJECT_ANALYSIS.md
│   ├── 02_EXISTING_FUNCTIONALITIES.md
│   ├── 03_TECHNOLOGY_STACK.md
│   ├── 04_PROJECT_STRUCTURE.md
│   ├── 05_DATABASE_ANALYSIS.md
│   ├── 06_EXISTING_RISKS.md
│   ├── 07_DEVELOPMENT_PLAN.md
│   └── 08_BACKUP_STRATEGY.md
│
├── includes/                               # Shared partials and security middleware
│   ├── alerts.php                          # Flash message component
│   ├── auth_guard.php                      # Role barrier & session guard
│   ├── footer.php                          # Global footer
│   ├── header.php                          # Global HTML head & meta tags
│   ├── navbar.php                          # Top navigation & user profile dropdown
│   └── sidebar.php                         # Role-aware dynamic navigation menu
│
├── models/                                 # Data access abstractions
│   ├── CandidateModel.php                  # Cross-database reader for ITI candidates
│   ├── CompanyModel.php                    # Industry entity model
│   ├── InterviewModel.php                  # Interview & feedback model
│   ├── RequirementModel.php                # Requisition entity model
│   └── UserModel.php                       # User credentials & RBAC model
│
├── views/                                  # Presentation views organized by role
│   ├── auth/                               # Public authentication screens
│   │   ├── login.php                       # Role-based login screen
│   │   ├── register.php                    # Industry partner registration
│   │   └── forgot_password.php             # Password recovery request
│   ├── industry/                           # Industry Admin portal
│   │   ├── dashboard.php                   # Company overview & active orders
│   │   ├── create_requirement.php          # Manpower requirement submission
│   │   ├── view_requirements.php           # Requirement listings
│   │   ├── requirement_details.php         # Detail view with candidate status
│   │   └── profile.php                     # Company profile & verification status
│   ├── recruiter/                          # Recruiter / HR portal
│   │   ├── dashboard.php                   # Assigned requisitions overview
│   │   ├── assigned_requirements.php       # Requisitions list
│   │   ├── candidate_screening.php         # Screening & evaluation interface
│   │   ├── interview_schedule.php          # Interview scheduling & feedback form
│   │   └── joining_tracker.php             # Candidate reporting & verification
│   ├── recruitment_admin/                  # Recruitment Operations Admin portal
│   │   ├── dashboard.php                   # Operational pipeline dashboard
│   │   ├── manage_requirements.php         # Approved requirements list
│   │   ├── candidate_matching.php          # Matching engine runner & scoring
│   │   ├── assign_recruiter.php            # Work order assignment
│   │   └── pipeline_reports.php            # Sourcing & fulfillment analytics
│   └── super_admin/                        # Super Admin master governance portal
│       ├── dashboard.php                   # Master system KPIs & quick actions
│       ├── companies.php                   # Company approval & vetting
│       ├── users.php                       # System user & role management
│       ├── requirements.php                # Global requirement monitoring
│       ├── audit_logs.php                  # Immutable activity & security log
│       └── system_settings.php             # Platform settings & thresholds
│
├── CHANGELOG.md                            # Versioned modification ledger
├── PROJECT_MASTER_TRACKER.md               # Master phase & module tracker
└── index.php                               # Central entry point & route dispatcher
```

