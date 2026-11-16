# 10 — INTERVIEW EVALUATION FEEDBACK & CANDIDATE SCORING
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal
### Cycle 2.0 Modernization & Enterprise Rebuild

---

## 1. Multi-Factor Evaluation Architecture

Interview feedback captures structured ratings from factory technical evaluators, preventing ambiguous or subjective hiring decisions. Governed by `PipelineController::recordInterviewFeedback()`.

### Evaluation Dimensions (1–5 Scale):
1. **Technical Knowledge Rating (1–5)**: Understanding of trade fundamentals, diagrams, and components.
2. **Practical Skills Rating (1–5)**: Machine handling, tooling setup speed, accuracy, and workpiece finish.
3. **Trade Knowledge Rating (1–5)**: Standards compliance, tooling numbers, and diagnostic methods.
4. **Communication Rating (1–5)**: Team coordination, clarity of thought, and reporting capability.
5. **Industrial Safety Rating (1–5)**: PPE compliance, lockout-tagout (LOTO) knowledge, and machine hazard alertness.
6. **Overall Rating (1–5)**: Composite suitability score.

---

## 2. Hiring Recommendations

The evaluator must provide an explicit recommendation:
- `Strongly Recommended`: Priority candidate for immediate selection and offer release.
- `Recommended`: Approved for selection.
- `Hold`: Standby pool candidate; held for comparison.
- `Not Recommended`: Rejected; moves pipeline stage to `Rejected` with recorded feedback rationale.

---

## 3. Database Schema: `interview_feedback`

```sql
CREATE TABLE `interview_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `interview_id` int(11) NOT NULL,
  `technical_rating` tinyint(4) NOT NULL DEFAULT 3,
  `practical_rating` tinyint(4) NOT NULL DEFAULT 3,
  `trade_knowledge_rating` tinyint(4) NOT NULL DEFAULT 3,
  `communication_rating` tinyint(4) NOT NULL DEFAULT 3,
  `safety_rating` tinyint(4) NOT NULL DEFAULT 3,
  `overall_rating` tinyint(4) NOT NULL DEFAULT 3,
  `recommendation` enum('Strongly Recommended','Recommended','Hold','Not Recommended') NOT NULL,
  `feedback_comments` text DEFAULT NULL,
  `evaluated_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_interview` (`interview_id`)
);
```
