# 10 — RECRUITMENT PROGRESS & DASHBOARD KPI METRICS
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal
### Cycle 2.0 Modernization & Enterprise Rebuild

---

## 1. 14 Database-Backed KPI Metrics

The Industry Recruitment Dashboard exposes fourteen (14) real-time statistics computed live from transactional MySQL records:

| Index | Metric Label | Database Query Aggregation |
|---|---|---|
| **1** | Matched Pool | `COUNT(cm.id)` where candidate matches active requisition. |
| **2** | Shortlisted | `SUM(cm.status = 'Shortlisted')` |
| **3** | Screened | `SUM(cm.status = 'Screened')` |
| **4** | Scheduled Interviews | `SUM(cm.status = 'Scheduled')` |
| **5** | Completed Interviews | `SUM(cm.status = 'Completed')` |
| **6** | Selected Candidates | `SUM(cm.status = 'Selected')` |
| **7** | Offers Released | `SUM(cm.status = 'Offer Released')` |
| **8** | Offers Accepted | `SUM(cm.status = 'Offer Accepted')` |
| **9** | Offers Declined | `SUM(cm.status = 'Offer Declined')` |
| **10** | Joining Pending | `SUM(cm.status = 'Joining Pending')` |
| **11** | Joined & Onboarded | `SUM(cm.status = 'Joined')` |
| **12** | Dropouts / No Show | `SUM(cm.status = 'No Show')` |
| **13** | Total Rejections | `SUM(cm.status = 'Rejected')` |
| **14** | Positions Fulfilled | $\sum N_{\text{joined}} / \sum N_{\text{vacancies}}$ across active requisitions. |

---

## 2. Multi-Dimensional Progress Tracking

1. **Requisition Breakdown**: Detailed progress table tracking each requisition code, title, plant, trade, vacancies, matched count, screened count, selected count, and percentage fulfilled.
2. **Plant-Level Progress**: Vacancies demanded vs candidates joined across each individual manufacturing plant unit.
3. **Trade-Wise Distribution**: Fulfillment progress broken down by vocational discipline (Fitter, Electrician, Welder, Machinist, Turner, COPA).
