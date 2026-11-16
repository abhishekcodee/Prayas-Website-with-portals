# PART 09 — 100-POINT SCORING MODEL & SCORE CLASSIFICATION TIERS

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Granular Scoring Engine (`CandidateMatchingModel.php`)  
**Status:** COMPLETED & VERIFIED (Part 09 Rebuild)  

---

## 1. 7-Factor Weighted Scoring Framework

The PRAYAS Matching Score is normalized to a **0.00 – 100.00** scale, calculated across seven distinct dimensions:

| Dimension | Max Points | Weight % | Evaluation Logic |
| :--- | :---: | :---: | :--- |
| **1. ITI Trade Alignment** | **30.0** | **30%** | &bull; Exact trade match: **30.0 pts**<br>&bull; Related trade cross-walk match: **20.0 pts**<br>&bull; Unmatched trade: **0.0 pts** (also triggers Gate 2 failure) |
| **2. Qualification Tier** | **15.0** | **15%** | &bull; ITI Passed (NCVT / SCVT): **15.0 pts**<br>&bull; Diploma / Apprentice / Certificate: **12.0 pts**<br>&bull; Other: **7.5 pts** |
| **3. Passing Batch Year** | **15.0** | **15%** | &bull; Exact year or within batch window: **15.0 pts**<br>&bull; Adjacent batch (&plusmn;1 year): **10.0 pts**<br>&bull; &plusmn;2 years: **5.0 pts**<br>&bull; Year unconstrained by requirement: **15.0 pts**<br>&bull; Unknown year: **6.0 pts** baseline |
| **4. Technical Skills Alignment** | **20.0** | **20%** | Evaluates keyword token match across candidate skills, computer knowledge, languages, experience, and feedback notes:<br>&bull; Match Ratio $\ge 1.0$: **20.0 pts**<br>&bull; Partial match: **20.0 &times; max(0.40, ratio) pts**<br>&bull; No specific skills demanded: **20.0 pts** baseline |
| **5. Location Proximity** | **10.0** | **10%** | &bull; Same city / plant locality: **10.0 pts**<br>&bull; Same district (e.g. Pune, Raigad, Mumbai): **10.0 pts**<br>&bull; Preferred location match: **8.0 pts**<br>&bull; Same state (Maharashtra jurisdiction): **6.0 pts**<br>&bull; Out of state / Other: **3.0 pts** |
| **6. Work Experience** | **5.0** | **5%** | &bull; Fresher matching Fresher requirement: **5.0 pts**<br>&bull; Experienced candidate matching experience demand: **5.0 pts**<br>&bull; Experienced candidate applying for Fresher role: **5.0 pts**<br>&bull; Mismatch: **3.0 pts** |
| **7. Joining Availability** | **5.0** | **5%** | &bull; Immediate availability (accredited active ITI students): **5.0 pts** |
| **TOTAL** | **100.0** | **100%** | Sum of points awarded, capped at 100.00 |

---

## 2. Quality Classification Tiers

Scores are mapped into four operational tiers for recruiters and employers:

| Quality Tier | Score Range | Badge Color | Interpretation |
| :--- | :---: | :---: | :--- |
| **Excellent Match** | **90.00 – 100.00** | Green (`bg-success`) | Candidate satisfies primary trade, exact batch, high technical skill alignment, and local proximity. Recommended for direct shortlisting. |
| **Strong Match** | **75.00 – 89.99** | Blue (`bg-primary`) | Strong technical and trade fit; minor deviation in passing batch or regional proximity. Ideal candidate pool. |
| **Possible Match** | **60.00 – 74.99** | Info/Cyan (`bg-info`) | Related trade cross-walk or adjacent batch year; candidate requires preliminary technical screening. |
| **Low Match** | **0.01 – 59.99** | Muted Grey (`bg-secondary`) | Marginal fit; substantial variance in location, skills, or experience. |
| **Not Eligible** | **0.00** | Red (`bg-danger`) | Failed one or more mandatory eligibility gates (Trade mismatch, Exam fail, Gender mismatch, or Qualification tier). Gated out. |

---

## 3. Score Breakdown Transparency

For every matched candidate, a complete JSON diagnostic breakdown is recorded in `candidate_matches.match_details_json` and surfaced via interactive modal:
```json
{
  "category": "Excellent Match",
  "is_eligible": true,
  "total_score": 95.0,
  "trade": { "score": 30.0, "max": 30.0, "notes": "Exact Trade Match" },
  "batch": { "score": 15.0, "max": 15.0, "notes": "Batch: 2024" },
  "qual": { "score": 15.0, "max": 15.0, "notes": "ITI Passed" },
  "skills": { "score": 20.0, "max": 20.0, "notes": "Technical alignment: 3 of 3 skills" },
  "location": { "score": 10.0, "max": 10.0, "notes": "Pune" },
  "experience": { "score": 5.0, "max": 5.0, "notes": "Fresher" },
  "availability": { "score": 5.0, "max": 5.0, "notes": "Standard Joining Availability" }
}
```

