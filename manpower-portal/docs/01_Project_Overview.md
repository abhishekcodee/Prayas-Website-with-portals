# 01 — Project Overview

## Organization: PRAYAS – The Automation Academy
**Project Title:** Industry Manpower Requirement & ITI Candidate Recruitment Portal  
**Platform Version:** 1.0.0-PROD  
**Target Environment:** XAMPP (Apache 2.4, PHP 8.2+, MariaDB/MySQL 10.4+)  
**Lead Architectural Lead:** Senior Full-Stack Engineering Team  

---

## 1. Executive Summary

**PRAYAS – The Automation Academy** is a premier technical training and vocational excellence initiative bridging the critical talent gap between technical training institutes (ITIs, Polytechnic Colleges) and industrial manufacturing, automotive, automation, and MSME clusters. 

The **Industry Manpower Requirement Portal** is an enterprise-grade digital platform designed to streamline and automate:
1. **Industry Partner Engagement:** Seamless onboarding, company verification, and structured submission of skilled manpower requirements.
2. **Automated Requirement Validation:** Rigorous server-side business validation and intelligent auto-approval workflows with unique tracking identifiers (`REQ-YYYYMM-XXXX`).
3. **Multi-Source ITI Candidate Sourcing & Matching:** Non-destructive integration with pre-existing candidate repositories (`itistudents` and `prayas_iti_dashboard`), matching trades, batches, gender, age, location, and salary benchmarks.
4. **End-to-End Recruitment Management:** Complete pipeline governance across candidate screening, interview scheduling, structured interview feedback, selections, offer letters, joining tracking, and partial requirement fulfillment.
5. **Auditing & Governance:** 100% role-based access control (RBAC), multi-tenant isolation, audit logging, and responsive executive analytics.

---

## 2. Core Stakeholders & User Roles

| Role | Scope & Permissions | Key Responsibilities |
| :--- | :--- | :--- |
| **Super Admin** | Full System & Governance Control | Platform management, company verifications, audit logs, system configurations, master reports, override authority. |
| **Industry Admin** | Company-Isolated Portal | Profile management, requirement submission, real-time stage monitoring, candidate status visibility (authorized). |
| **Recruitment Admin** | Operations & Talent Sourcing Lead | Work order distribution, recruiter assignment, matching engine governance, pipeline escalation, hiring analytics. |
| **Recruiter / HR** | Tactical Sourcing & Operations | Candidate screening, shortlisting, interview scheduling, feedback capture, offer issuance, joining verification. |

---

## 3. Guiding Principles & Non-Destructive Safe Engineering

1. **Zero Data Loss:** Existing candidate records (1,292 records in `prayas_iti_dashboard.students` and 56 records in `itistudents.candidates`) are treated as immutable external records. No deletion, alteration, or schema corruption.
2. **Multi-Tenant Data Isolation:** Industry users are restricted to their company's data via server-side session checks (`company_id`).
3. **Stateless Scalability:** Clear modular directory structure (Controllers, Views, Models, API endpoints) built on Core PHP 8.2 with PDO prepared statements and Bootstrap 5.3.
4. **Enterprise Auditability:** Every stage change, approval, interview outcome, and offer issuance is recorded in an immutable audit ledger (`audit_logs` and `requirement_status_history`).

