# 15 — Candidate Matching Algorithm Specification

## Document Identifier: ALG-MAT-2026-V1.0
**Project Title:** Industry Manpower Requirement & ITI Candidate Recruitment Portal  
**Organization:** PRAYAS – The Automation Academy  
**Mathematical Model:** Multi-Attribute Utility Scoring Engine ($S \in [0, 100]$)  
**Date:** 2026-11-05  

---

## 1. Overview & Objective

The Candidate Matching Engine is an explainable, deterministic mathematical scoring algorithm that computes a compatibility score $S \in [0, 100]$ between an approved Manpower Requirement and candidates sourced from verified PRAYAS candidate repositories (`prayas_iti_dashboard.students` and `itistudents.candidates`).

The algorithm ensures:
1. **Zero Data Modification:** External candidate tables are read via non-destructive `SELECT` queries.
2. **Explainability:** Every score can be unpacked into constituent points for recruiters and employers.
3. **Configurable Weighting:** Parameter weights sum to exactly $100\%$.

---

## 2. Parameter Weighting Matrix

The overall compatibility score $S$ is defined as the weighted linear combination:

$$S = \sum_{i=1}^{8} w_i \cdot s_i$$

Where $w_i$ is the parameter weight ($\sum w_i = 100\%$) and $s_i \in [0, 1]$ is the normalized sub-score.

| Index $i$ | Parameter Name | Weight ($w_i$) | Maximum Points | Sub-Score Calculation Description |
| :---: | :--- | :---: | :---: | :--- |
| **1** | **ITI Trade Match** | **35%** | **35** | Exact or allied technical trade correspondence. |
| **2** | **Qualification / Board Match** | **15%** | **15** | ITI completion, NCVT vs SCVT board alignment. |
| **3** | **Passing Batch / Year Match** | **15%** | **15** | Alignment with requested graduation year or window. |
| **4** | **Location & Relocation Match** | **10%** | **10** | District proximity or candidate willingness to relocate. |
| **5** | **Experience Level Match** | **10%** | **10** | Freshers vs. 1-2 years vs. 3+ years shop-floor exposure. |
| **6** | **Salary Compatibility** | **5%** | **5** | Candidate salary expectation vs. requisition budget. |
| **7** | **Technical Skills Match** | **5%** | **5** | Overlap between candidate competencies and requirement tags. |
| **8** | **Candidate Availability** | **5%** | **5** | Candidate status ('Active') and joining readiness. |
| **Total**| — | **100%** | **100 Points** | Fully normalized composite score. |

---

## 3. Mandatory Eligibility Conditions (Hard Filters)

Before computing $S$, candidate records must pass the following **Hard Eligibility Filters**:

$$\text{Eligible}(C) = \begin{cases} 
\text{TRUE} & \text{if } C.\text{status} = \text{'Active'} \land \text{Age}(C) \ge 18 \land \text{TradeEligible}(C) = \text{TRUE} \\
\text{FALSE} & \text{otherwise}
\end{cases}$$

1. **Active Status:** Candidate must be marked `status = 'Active'` in `prayas_iti_dashboard.students` or `verification_status = 'VERIFIED'` in `itistudents.candidates`.
2. **Statutory Age Limit:** Candidate age must be $\ge 18$ years as required by Indian industrial factory regulations.
3. **Trade Domain Compatibility:** Candidate must possess an ITI trade related to the industrial requirement (e.g. Mechanical/Electrical trades cannot be matched with Tailoring). If trade match is zero, candidate is disqualified.

---

## 4. Parameter Sub-Score Formulation

### 4.1 Parameter 1: ITI Trade Match ($w_1 = 35$)
Let $T_{\text{req}}$ be the set of acceptable trades in the requirement and $t_{\text{cand}}$ be candidate's trade.

$$s_1 = \begin{cases} 
1.0 & \text{if } t_{\text{cand}} \in T_{\text{req}} \quad (\text{Exact Trade Match}) \\
0.5 & \text{if } \text{AlliedTrade}(t_{\text{cand}}, T_{\text{req}}) = \text{TRUE} \quad (\text{e.g. Electrician } \leftrightarrow \text{ Wireman}) \\
0.0 & \text{otherwise}
\end{cases}$$

$$\text{Score}_1 = 35 \times s_1$$

### 4.2 Parameter 2: Qualification / Board Match ($w_2 = 15$)
Let $B_{\text{req}}$ be the board required (`NCVT`, `SCVT`, or `Either`):

$$s_2 = \begin{cases} 
1.0 & \text{if } B_{\text{req}} = \text{'Either'} \lor B_{\text{cand}} = B_{\text{req}} \\
0.7 & \text{if } B_{\text{req}} = \text{'NCVT'} \land B_{\text{cand}} = \text{'SCVT'} \quad (\text{Valid State Council}) \\
0.5 & \text{if } B_{\text{cand}} \text{ is unstated but candidate is ITI certified} \\
0.0 & \text{if candidate lacks ITI certification}
\end{cases}$$

$$\text{Score}_2 = 15 \times s_2$$

### 4.3 Parameter 3: Passing Batch / Year Match ($w_3 = 15$)
Let $[Y_{\min}, Y_{\max}]$ be the target batch years requested by the employer and $y_{\text{cand}}$ be the candidate's passing year:

$$s_3 = \begin{cases} 
1.0 & \text{if } Y_{\min} \le y_{\text{cand}} \le Y_{\max} \quad (\text{In Target Batch}) \\
0.7 & \text{if } |y_{\text{cand}} - Y_{\text{req}}| = 1 \text{ year} \quad (\text{Within } \pm 1 \text{ year}) \\
0.4 & \text{if } |y_{\text{cand}} - Y_{\text{req}}| = 2 \text{ years} \\
0.0 & \text{if } |y_{\text{cand}} - Y_{\text{req}}| > 2 \text{ years}
\end{cases}$$

$$\text{Score}_3 = 15 \times s_3$$

### 4.4 Parameter 4: Location & Relocation Match ($w_4 = 10$)
Let $D_{\text{req}}$ be the job district and $D_{\text{cand}}$ be candidate's home district:

$$s_4 = \begin{cases} 
1.0 & \text{if } D_{\text{cand}} = D_{\text{req}} \quad (\text{Same District / Local Sourcing}) \\
0.8 & \text{if } D_{\text{cand}} \ne D_{\text{req}} \land C.\text{can\_relocate} = \text{'Yes'} \quad (\text{Willing to relocate}) \\
0.4 & \text{if candidate is in adjacent district in same state} \\
0.1 & \text{if candidate is in different state and relocation status unknown}
\end{cases}$$

$$\text{Score}_4 = 10 \times s_4$$

### 4.5 Parameter 5: Experience Level Match ($w_5 = 10$)
Let $E_{\text{req}}$ be the required experience (Freshers, 1-2 years, 3+ years) and $E_{\text{cand}}$ be candidate's recorded experience:

$$s_5 = \begin{cases} 
1.0 & \text{if candidate experience meets or exceeds requirement} \\
0.7 & \text{if requirement is Freshers and candidate has 6-12 months apprentice experience} \\
0.4 & \text{if requirement is 1-2 years and candidate has apprentice-only experience} \\
0.0 & \text{if requirement is 3+ years and candidate is absolute fresher}
\end{cases}$$

$$\text{Score}_5 = 10 \times s_5$$

### 4.6 Parameter 6: Salary Compatibility ($w_6 = 5$)
Let $S_{\max}$ be the maximum budget offered and $S_{\text{exp}}$ be candidate's expected monthly compensation:

$$s_6 = \begin{cases} 
1.0 & \text{if } S_{\text{exp}} \le S_{\max} \quad (\text{Well within budget}) \\
0.7 & \text{if } S_{\max} < S_{\text{exp}} \le 1.10 \times S_{\max} \quad (\text{Within 10\% negotiation buffer}) \\
0.3 & \text{if } 1.10 \times S_{\max} < S_{\text{exp}} \le 1.25 \times S_{\max} \\
0.0 & \text{if } S_{\text{exp}} > 1.25 \times S_{\max} \quad (\text{Expectation too high})
\end{cases}$$

$$\text{Score}_6 = 5 \times s_6$$

### 4.7 Parameter 7: Technical Skills Match ($w_7 = 5$)
Let $K_{\text{req}}$ be set of required skill tags (e.g. PLC, CNC, Wiring, Welding 4G, Metrology) and $K_{\text{cand}}$ be candidate skills:

$$s_7 = \frac{|K_{\text{cand}} \cap K_{\text{req}}|}{|K_{\text{req}}|}, \quad \text{Score}_7 = 5 \times s_7$$
*(If requirement specifies no custom skill tags, $s_7$ defaults to 1.0)*

### 4.8 Parameter 8: Candidate Availability & Readiness ($w_8 = 5$)
Evaluates candidate expected joining timeframe:

$$s_8 = \begin{cases} 
1.0 & \text{if expected joining date } \le \text{requirement target joining date} \\
0.6 & \text{if expected joining date is within 15 days of target date} \\
0.2 & \text{if joining date is unconfirmed but candidate is active}
\end{cases}$$

$$\text{Score}_8 = 5 \times s_8$$

---

## 5. Total Compatibility Score & Interpretation

The final composite score is calculated as:

$$S = \text{Score}_1 + \text{Score}_2 + \text{Score}_3 + \text{Score}_4 + \text{Score}_5 + \text{Score}_6 + \text{Score}_7 + \text{Score}_8$$

| Composite Score ($S$) | Tier Classification | Badge Label | Operational Action |
| :---: | :---: | :---: | :--- |
| **$85.0 \le S \le 100.0$** | **Tier 1** | `Top Match (Tier 1)` | Priority automatic shortlisting; immediate interview scheduling. |
| **$70.0 \le S < 85.0$** | **Tier 2** | `Strong Match (Tier 2)` | Recommended for screening call by assigned Recruiter. |
| **$50.0 \le S < 70.0$** | **Tier 3** | `Moderate Match (Tier 3)`| Reserve pool for backup candidate sourcing. |
| **$S < 50.0$** | **Sub-threshold** | `Low Compatibility` | Hidden by default; accessible only via manual recruiter search. |

---

## 6. Tie-Breaking & Missing Data Rules

### 6.1 Tie-Breaking Heuristic
When two or more candidates produce identical score $S$:
1. **Primary Tie-Breaker:** Higher ITI Percentage / Grade (`tenth_percentage` or `iti_percentage_grade` in `itistudents.candidates` or `hsc`/`ssc` in `prayas_iti_dashboard.students`).
2. **Secondary Tie-Breaker:** District Proximity (Candidate residing in job district ranks higher).
3. **Tertiary Tie-Breaker:** Earlier Candidate Registration Date (Seniority in PRAYAS talent network).

### 6.2 Missing Data Handling
- If `expected_salary` is `NULL`, candidate receives $s_6 = 0.5$ (neutral mid-point assumption).
- If `can_relocate` is `NULL`, candidate receives $s_4 = 0.4$ if in adjacent district, $0.1$ if distant.
- If `passing_year` has range format (e.g. `"2022-2024"`), algorithm extracts completion year `2024` for comparison.

---

## 7. Rejection & Disqualification Logging

When a candidate fails the matching engine or is rejected during review, the system logs a standardized reason code in `candidate_matches.rejection_reason`:
- `REJ-TRD-01`: Trade domain mismatch.
- `REJ-AGE-01`: Below statutory minimum age (18 years).
- `REJ-SAL-01`: Candidate salary expectation exceeds budget ceiling by $> 25\%$.
- `REJ-LOC-01`: Candidate unwilling to relocate to job plant district.
- `REJ-STS-01`: Candidate already placed or marked inactive.

