# Part 08 — UI Notification Integration & Layout Guidelines

**Project:** PRAYAS – The Automation Academy | Industry Manpower Requirement Portal  
**Document Code:** `PRY-REBUILD-08-NOTIF-INTEGRATION`  
**Classification:** Frontend Design System & View Specifications  

---

## 1. Design System Alignment

In strict compliance with Part 02 Enterprise Design System specifications:
- **Root Layout:** Pages utilize `<main class="page-container">` with flexbox sticky footer and responsive sidebars.
- **Color Hierarchy:**
  - Success/Validated: PRAYAS Emerald (`bg-success`, `#10B981`)
  - Advisory Warnings: PRAYAS Amber (`bg-warning`, `#F59E0B`)
  - Defects / Failures: PRAYAS Coral Red (`bg-danger`, `#EF6B6B`)
  - Administrative / Dark: Deep Navy (`#0F172A`)
- **Typography:** Urbanist font family with strict font-size clamping (`0.75rem` for sub-labels to `1.5rem` for KPI counters).

---

## 2. Integrated Views

### A. Automated Validation Dossier (`views/industry/validation_summary.php`)
- **Executive KPI Cards:** Total Rules Evaluated, Passed, Advisory Warnings, Defects / Errors, and Critical Gatekeepers.
- **Category Accordion:** 7 collapsible sections grouping individual rule evaluations. Each failed item displays the rule code, observed defect, current parameter value, and an actionable **Suggested Correction** box.
- **Direct Wizard Shortcuts:** Each non-passing rule features a direct "Fix in Step X" button linking to `requirement_form.php?id={id}&step={X}`.
- **On-Demand Revalidation:** Top-bar action button triggering a CSRF-protected POST revalidation without reloading the requisition page.
- **Validation History Table:** Audit list of previous validation runs with timestamps and trigger mechanisms.

### B. Requirement Details Dossier (`views/industry/requirement_details.php`)
- **Top Bar Integration:** Direct link button `<a class="btn btn-sm btn-outline-info">Validation Dossier</a>`.
- **Validation Status Summary Card:** Embedded in the right column above administrative audit data, showing real-time pass/warn/error counts and linking to the full dossier.

### C. Industry Notification Console (`views/industry/notifications.php`)
- Type-filtered alert stream with responsive badge pills.
- Action deep-links constructed via `app_url()`.
- CSRF-protected "Mark as Read" and "Mark All as Read" operations.

### D. Super Admin Alerts Console (`views/super_admin/notifications.php`)
- Centralized administrative stream alerting administrators when high-volume or commercial review triggers require human evaluation.

