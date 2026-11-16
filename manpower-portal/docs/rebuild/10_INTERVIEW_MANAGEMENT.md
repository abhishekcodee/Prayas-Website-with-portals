# 10 — INTERVIEW MANAGEMENT & SCHEDULING
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal
### Cycle 2.0 Modernization & Enterprise Rebuild

---

## 1. Interview Scheduling Architecture

Interview scheduling connects qualified ITI candidates with factory technical panels. It is governed by `PipelineController::scheduleInterview()` and persists in the `interviews` table.

### Strict Scheduling Constraints:
- **Past-Date Prohibition**: Interviews cannot be scheduled in the past. Date must be $\ge$ current server timestamp.
- **Venue / Link Requirement**: Physical plant address (e.g. "Plant Gate 2, Chakan, Pune") or virtual video meeting link must be provided.
- **Interview Modes Supported**:
  - `In-Person`: On-site plant assessment or PRAYAS Academy drive.
  - `Online Video`: Google Meet or Zoom interview.
  - `Telephonic`: Remote audio technical interview.
- **Multi-Round Support**:
  - `Round 1 - Technical Assessment`
  - `Round 2 - Plant Ground Practical`
  - `Round 3 - HR & Fitment`
  - `Final Comprehensive Drive`

---

## 2. Notification Dispatch

Upon scheduling an interview, the notification engine automatically:
1. Dispatches an In-App Notification (`INTERVIEW_SCHEDULED`) to the designated Industry Admin.
2. Updates candidate match status to `Scheduled`.
3. Records stage transition in `recruitment_stage_history`.
4. Adds the scheduled event to the Industry Dashboard "Upcoming Interviews (Next 7 Days)" widget.
