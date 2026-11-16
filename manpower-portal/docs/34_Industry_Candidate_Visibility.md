# 34 — Candidate Visibility & Privacy Protection Standards

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Anonymized Candidate Explorer & Privacy Engine  
**Document ID:** DOC-IND-34  
**Status:** Approved & Implemented  

---

## 1. Overview & Privacy Principles

The **Candidate Visibility Module** (`views/industry/candidate_matches.php`) gives registered companies visibility into ITI technician candidates matched to their approved requirements while strictly safeguarding candidate personal data.

---

## 2. Privacy-First Projection Architecture

In compliance with data protection principles and industrial apprenticeship norms:

1. **Information Disclosed to Industry:**
   - Candidate Full Name / Reference Label.
   - ITI Trade Specialization (e.g., Fitter, Electrician, Machinist, Welder).
   - ITI Passing Batch Year.
   - Calculated Match Score percentage (0% to 100%).
   - Current Pipeline Stage (`Matched`, `Shortlisted`, `Scheduled`, `Selected`, `Joined`).
   - Matched Timestamp.

2. **Information Strictly Redacted & Hidden:**
   - **No Aadhaar Numbers:** Never exposed to industry users.
   - **No PAN Numbers or Bank Details:** Handled exclusively inside PRAYAS administration.
   - **No Personal Mobile Numbers / Personal Home Addresses:** Disclosed only after interview scheduling is formally authorized.
   - **No Unrelated Candidates:** Industries cannot browse the general ITI talent pool (`itistudents.candidates` or `prayas_iti_dashboard.students`). They can only inspect candidates matched to their own valid requirements.

---

## 3. Candidate Matching Quality Tiers

Candidates are presented with visual quality tiers based on the 8-parameter scoring algorithm:
- **Tier 1 (Elite Match: $\ge 85\%$):** Full trade, skill, and location alignment.
- **Tier 2 (High Potential: $70\% - 84\%$):** Trade aligned, minor experience or proximity variance.
- **Tier 3 (Baseline Eligible: $50\% - 69\%$):** Trade eligible, requires on-the-job training.

