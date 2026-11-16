# 09 — API & Asynchronous Endpoint Documentation

## Organization: PRAYAS – The Automation Academy
**Module:** Internal JSON / AJAX Service Endpoints  
**Base URL:** `/api/`  
**Authentication:** PHP Session Cookie (`PHPSESSID`) + `X-CSRF-TOKEN` Header  

---

## 1. Endpoints Overview

| Endpoint | Method | Role Allowed | Purpose |
| :--- | :---: | :---: | :--- |
| `/api/validate_requirement.php` | `POST` | `industry_admin`, `super_admin` | Real-time pre-flight validation of requirement form fields before submission. |
| `/api/candidate_matching.php` | `GET` / `POST` | `recruitment_admin`, `recruiter`, `super_admin` | Queries the candidate matching algorithm and returns ranked compatibility list. |
| `/api/check_requirement_status.php`| `GET` | All authenticated roles | Fetches live stage progress, fulfillment count, and timeline milestones. |
| `/api/get_districts.php` | `GET` | Public / Authenticated | Returns list of districts and major ITI clusters for selected state. |
| `/api/get_trades.php` | `GET` | Public / Authenticated | Returns standard ITI engineering and non-engineering trades. |

---

## 2. Sample Request & Response Payloads

### 2.1 `/api/validate_requirement.php`
**Request Payload (JSON or `application/x-www-form-urlencoded`):**
```json
{
  "csrf_token": "a4f89d...7b12",
  "title": "Junior Automation Maintenance Technician",
  "quantity_required": 15,
  "trades": ["Electrician", "Wireman"],
  "qualification": "ITI Passed",
  "board_type": "NCVT",
  "passing_year_from": "2023",
  "passing_year_to": "2025",
  "min_salary": 18000,
  "max_salary": 24000,
  "job_location_state": "Maharashtra",
  "job_location_district": "Pune",
  "submission_date": "2026-11-05",
  "application_deadline": "2026-11-20",
  "interview_date": "2026-11-25",
  "target_joining_date": "2026-12-05"
}
```

**Successful Response:**
```json
{
  "status": "success",
  "valid": true,
  "auto_approval_eligible": true,
  "preview_id": "REQ-202611-0012",
  "message": "All parameters passed validation successfully."
}
```

**Validation Error Response:**
```json
{
  "status": "error",
  "valid": false,
  "errors": {
    "max_salary": "Maximum salary must be greater than or equal to minimum salary.",
    "target_joining_date": "Target joining date must occur after interview date."
  }
}
```

---

### 2.2 `/api/candidate_matching.php`
**Response Payload:**
```json
{
  "status": "success",
  "requirement_id": "REQ-202611-0012",
  "total_matches": 38,
  "candidates": [
    {
      "source": "prayas_dashboard",
      "candidate_id": 104,
      "name": "Digambar Sayjirao Raut",
      "trade": "Electrician",
      "passing_year": "2025",
      "district": "Pune",
      "gender": "Male",
      "match_score": 95.0,
      "score_breakdown": {
        "trade": 40,
        "batch": 20,
        "location": 15,
        "gender": 10,
        "salary": 10
      }
    }
  ]
}
```

