# 10 — GROUND JOINING CONFIRMATION & ONBOARDING
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal
### Cycle 2.0 Modernization & Enterprise Rebuild

---

## 1. Physical Joining Workflow

The final operational step in the recruitment lifecycle is verifying that the candidate physically reported to the manufacturing plant and commenced shift duties. Governed by `PipelineController::confirmJoining()`.

### Supported Reporting Statuses:
- `Joined`: Successfully reported, completed day-1 safety orientation, and commenced shift.
- `Joining Delayed`: Candidate requested transit extension (e.g. ITI exam certificate delay, travel constraint).
- `No Show`: Failed to report on scheduled date without notice.
- `Did Not Join`: Formally opted out or revoked acceptance prior to onboarding.

---

## 2. Automatic Requirement Fulfillment Synchronization

When a candidate is marked `Joined`, the pipeline automatically calculates total confirmed joinings for the underlying requisition:

$$N_{\text{joined}} = \sum \mathbf{1}_{(\text{status} = \text{'Joined'})}$$

If $N_{\text{joined}} \ge N_{\text{vacancies}}$, the requirement status is automatically updated:
$$\text{Status} \leftarrow \mathbf{FULFILLED}$$
Otherwise, if $N_{\text{joined}} \ge 1$:
$$\text{Status} \leftarrow \mathbf{PARTIALLY\_FULFILLED}$$

Every automatic fulfillment transition records an audit entry in `requirement_status_history` documenting:
$$\text{"Requirement fulfillment update: } N_{\text{joined}}/N_{\text{vacancies}}\text{ vacancies confirmed joined."}$$
