# Part 08 — Deadline & Milestone Reminder Integration

**Project:** PRAYAS – The Automation Academy | Industry Manpower Requirement Portal  
**Document Code:** `PRY-REBUILD-08-DEADLINE-INTEGRATION`  
**Classification:** Scheduled Service & Lifecycle Integration  

---

## 1. Overview

The **Deadline Reminder Integration** monitors the temporal lifecycle of active manpower requisitions. It proactively alerts enterprise HR coordinators and plant supervisors as critical recruitment dates approach or expire, preventing unmonitored requisition stall.

---

## 2. Monitored Milestone Windows

The engine continuously evaluates active requisitions (`APPROVED`, `IN_PROGRESS`, `PARTIALLY_FULFILLED`) across three key milestone axes:

### A. Application Deadline Windows
- **7 Days Prior (`DEADLINE_7_DAYS`):** Initial advance reminder to evaluate candidate pool volume.
- **3 Days Prior (`DEADLINE_3_DAYS`):** Mid-tier reminder to commence interview shortlisting.
- **1 Day Prior (`DEADLINE_1_DAY`):** Urgent countdown notice preceding application closure.
- **Due Date / Today (`DEADLINE_TODAY`):** Final call for candidate shortlisting.
- **Expired / Overdue (`DEADLINE_OVERDUE`):** Action required notice to transition requisition or extend deadline.

### B. Plant Interview Windows
- **3 Days Prior (`INTERVIEW_3_DAYS`):** Notice to confirm plant interview venue and technical panels.
- **Due Date / Today (`INTERVIEW_TODAY`):** Live walk-in or interview day activation alert.

### C. Target Joining Milestone Windows
- **7 Days Prior (`JOINING_7_DAYS`):** Advance notice to finalize document verification and plant safety gear.
- **Due Date / Today (`JOINING_TODAY`):** Candidate onboarding day activation alert.

---

## 3. Idempotency & Duplicate Suppression

To prevent redundant alerts when cron jobs execute multiple times per day, the system leverages table `deadline_reminder_logs`:

```sql
UNIQUE KEY `uk_req_rem_user_date` (`requirement_id`, `reminder_type`, `target_date`, `recipient_user_id`)
```

### Idempotency Principles:
1. **Atomic Guard:** The system inserts into `deadline_reminder_logs` before dispatching in-app alerts. Duplicate triggers for the same user on the same calendar date are safely skipped.
2. **Dynamic Date Revision Handling:** If an HR coordinator reschedules an interview from June 20 to June 25, the new target date automatically permits fresh reminder generation while preserving historical logs.
3. **Terminal State Exclusion:** Requisitions in terminal states (`DRAFT`, `NEEDS_CORRECTION`, `CANCELLED`, `CLOSED`, `FULFILLED`) are excluded from active scans at the database query level.

