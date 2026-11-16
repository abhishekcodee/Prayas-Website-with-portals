# Part 03 — Database Entity Relationship (ER) Diagram
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Database Schema:** `manpower_portal_db`  
**Diagram Type:** Actual Physical Schema (Mermaid ER)  
**Document Code:** `DOC-REBUILD-03-ER-DIAGRAM`

---

## 1. Executive Summary

This document presents the complete Entity-Relationship (ER) model representing the **actual physical schema** of `manpower_portal_db`. All tables, keys, cardinalities, and relationship types (1:1, 1:N, N:M) reflect the live inspected database without invention or distortion.

---

## 2. Mermaid Entity-Relationship Diagram

```mermaid
erDiagram
    COMPANIES ||--o{ USERS : "employs"
    COMPANIES ||--o{ MANUFACTURING_PLANTS : "operates"
    COMPANIES ||--o{ COMPANY_INVITATIONS : "issues"
    COMPANIES ||--o{ MANPOWER_REQUIREMENTS : "submits"
    COMPANIES ||--o{ NOTIFICATIONS : "receives"
    COMPANIES ||--o{ AUDIT_LOGS : "scoped_to"

    ROLES ||--o{ USERS : "assigned_to"
    ROLES ||--o{ ROLE_PERMISSIONS : "has"
    PERMISSIONS ||--o{ ROLE_PERMISSIONS : "belongs_to"

    MANUFACTURING_PLANTS ||--o{ MANPOWER_REQUIREMENTS : "hosts"
    MANUFACTURING_PLANTS ||--o{ SELECTIONS : "allocates"

    USERS ||--o{ MANPOWER_REQUIREMENTS : "creates"
    USERS ||--o{ AUDIT_LOGS : "performs"
    USERS ||--o{ NOTIFICATIONS : "receives"
    USERS ||--o{ CANDIDATE_SCREENING : "screens"
    USERS ||--o{ INTERVIEWS : "schedules"
    USERS ||--o{ INTERVIEW_FEEDBACK : "evaluates"
    USERS ||--o{ SELECTIONS : "selects"
    USERS ||--o{ JOININGS : "verifies"

    MANPOWER_REQUIREMENTS ||--o{ REQUIREMENT_TRADES : "allocates"
    MANPOWER_REQUIREMENTS ||--o{ REQUIREMENT_SKILLS : "specifies"
    MANPOWER_REQUIREMENTS ||--o{ REQUIREMENT_STATUS_HISTORY : "tracks"
    MANPOWER_REQUIREMENTS ||--o{ REQUIREMENT_VALIDATION_HISTORY : "validates"
    MANPOWER_REQUIREMENTS ||--o{ CANDIDATE_MATCHES : "matches"
    MANPOWER_REQUIREMENTS ||--o{ CANDIDATE_MATCHING_RUNS : "executes"
    MANPOWER_REQUIREMENTS ||--o{ DEADLINE_REMINDER_LOGS : "monitors"

    CANDIDATE_MATCHES ||--o{ CANDIDATE_SHORTLIST_ACTIONS : "actioned_by"
    CANDIDATE_MATCHES ||--o{ CANDIDATE_SCREENING : "assessed_in"
    CANDIDATE_MATCHES ||--o{ INTERVIEWS : "scheduled_for"
    CANDIDATE_MATCHES ||--|| SELECTIONS : "results_in"
    CANDIDATE_MATCHES ||--o{ RECRUITMENT_STAGE_HISTORY : "progresses_through"

    INTERVIEWS ||--o{ INTERVIEW_FEEDBACK : "evaluated_by"

    SELECTIONS ||--|| OFFER_LETTERS : "issues"
    SELECTIONS ||--|| JOININGS : "confirms"

    COMPANIES {
        int id PK
        string company_name
        string registration_number UK
        string industry_type
        string state
        string district
        string hr_contact_email UK
        string verification_status
    }

    USERS {
        int id PK
        int role_id FK
        int company_id FK
        string full_name
        string email UK
        string password_hash
        string status
    }

    MANUFACTURING_PLANTS {
        int id PK
        int company_id FK
        string plant_name
        string plant_code
        string city
        string state
        string status
    }

    MANPOWER_REQUIREMENTS {
        int id PK
        string requirement_id UK
        int company_id FK
        int plant_id FK
        string title
        int quantity_required
        string status
        date application_deadline
        date target_joining_date
        int created_by FK
        int approved_by FK
    }

    REQUIREMENT_TRADES {
        int id PK
        int requirement_id FK
        string trade_name
        int vacancies
        string qualification
        string board_type
        string min_experience
    }

    CANDIDATE_MATCHES {
        int id PK
        int requirement_id FK
        string candidate_source
        int candidate_source_id
        string candidate_name
        string trade
        decimal match_score
        string eligibility_status
        string status
        boolean is_shared_with_industry
    }

    INTERVIEWS {
        int id PK
        int match_id FK
        string interview_round
        string interview_mode
        datetime interview_datetime
        string status
        int scheduled_by FK
    }

    SELECTIONS {
        int id PK
        int match_id FK "UK"
        string offered_designation
        int plant_id FK
        decimal offered_ctc
        date target_joining_date
        string selection_status
        int selected_by FK
    }

    OFFER_LETTERS {
        int id PK
        int selection_id FK "UK"
        string letter_reference_no UK
        decimal offered_ctc
        date offer_valid_until
        string status
    }

    JOININGS {
        int id PK
        int selection_id FK "UK"
        date actual_joining_date
        string assigned_unit
        string joining_status
        int verified_by FK
    }

    NOTIFICATIONS {
        int id PK
        int recipient_user_id FK
        int company_id FK
        string notification_type
        string title
        text message
        boolean is_read
    }

    AUDIT_LOGS {
        int id PK
        int user_id FK
        int company_id FK
        string action
        string ip_address
        text details_json
        timestamp created_at
    }
```

---

## 3. Relationship Multiplicity & Cardinality Summary

| Parent Entity | Child Entity | Relationship Type | Enforced FK Constraint | Action on Delete |
|---|---|:---:|---|:---:|
| `companies` | `users` | 1 to Many (1:N) | `users.company_id` | RESTRICT |
| `companies` | `manufacturing_plants` | 1 to Many (1:N) | `manufacturing_plants.company_id` | CASCADE |
| `companies` | `manpower_requirements` | 1 to Many (1:N) | `manpower_requirements.company_id` | RESTRICT |
| `roles` | `role_permissions` | 1 to Many (1:N) | `role_permissions.role_id` | CASCADE |
| `permissions` | `role_permissions` | 1 to Many (1:N) | `role_permissions.permission_id` | CASCADE |
| `manpower_requirements` | `requirement_trades` | 1 to Many (1:N) | `requirement_trades.requirement_id` | CASCADE |
| `manpower_requirements` | `candidate_matches` | 1 to Many (1:N) | `candidate_matches.requirement_id` | RESTRICT |
| `candidate_matches` | `interviews` | 1 to Many (1:N) | `interviews.match_id` | CASCADE |
| `candidate_matches` | `selections` | 1 to 1 (1:1 Unique) | `selections.match_id` | RESTRICT |
| `selections` | `offer_letters` | 1 to 1 (1:1 Unique) | `offer_letters.selection_id` | RESTRICT |
| `selections` | `joinings` | 1 to 1 (1:1 Unique) | `joinings.selection_id` | RESTRICT |
| `interviews` | `interview_feedback` | 1 to Many (1:N) | `interview_feedback.interview_id` | CASCADE |
| `users` | `audit_logs` | 1 to Many (1:N) | `audit_logs.user_id` | SET NULL |

---

## 4. Integrity Analysis & Referential Security

1. **Cascade Boundaries:** Critical operational master records (`companies`, `users`, `manpower_requirements`) enforce `RESTRICT` on delete, completely eliminating accidental cascading destruction of active hiring data.
2. **Sub-item Cascade Cleanup:** Sub-allocations (`requirement_trades`, `requirement_skills`, `candidate_screening`, `interview_feedback`) enforce `CASCADE` on delete, ensuring clean garbage collection if draft requisitions or test matching fixtures are explicitly removed.
3. **1-to-1 Pipeline Enforcement:** The candidate pipeline strictly enforces 1:1 progression from `candidate_matches` to `selections`, `offer_letters`, and `joinings` via unique keys on `match_id` and `selection_id`. A candidate cannot be selected or offered twice for the same match.

