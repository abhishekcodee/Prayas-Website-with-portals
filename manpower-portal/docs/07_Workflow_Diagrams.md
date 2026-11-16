# 07 — Workflow Diagrams

## Organization: PRAYAS – The Automation Academy
**Portal:** Industry Manpower Requirement & Recruitment Portal  

---

## 1. Complete End-to-End Recruitment Workflow

```mermaid
flowchart TD
    Start([Industry Partner Registration]) --> Verification{Super Admin Verifies?}
    Verification -- No --> Rejected([Company Rejected / Re-submit])
    Verification -- Yes --> LoggedIn[Industry Login to Portal]
    
    LoggedIn --> SubmitReq[Submit Manpower Requirement Form]
    SubmitReq --> AutoVal{Server-Side Automatic Validation}
    
    AutoVal -- Failed --> ShowErrors[Highlight Missing / Invalid Fields]
    ShowErrors --> SubmitReq
    
    AutoVal -- Passed --> GenID[Generate Unique ID: REQ-YYYYMM-XXXX]
    GenID --> AutoApprove[Auto-Approve Requirement & Update Status]
    AutoApprove --> AlertRec[Dispatch Notification to Recruitment Admin]
    
    AlertRec --> MatchEngine[Run Candidate Matching Engine]
    MatchEngine --> PoolSelect[(Query prayas_iti_dashboard & itistudents)]
    PoolSelect --> RankCandidates[Rank Candidates by Compatibility Score]
    
    RankCandidates --> AssignRecruiter[Assign Requirement to Recruiter / HR]
    AssignRecruiter --> Screening[Candidate Phone / Video Screening]
    
    Screening --> ScreenPass{Screening Result?}
    ScreenPass -- Reject --> ArchiveCand[Mark as Not Suitable]
    ScreenPass -- Shortlist --> ScheduleInterview[Schedule Technical / Plant Interview]
    
    ScheduleInterview --> ConductInterview[Conduct Interview & Capture Feedback]
    ConductInterview --> Evaluation{Interview Passed?}
    Evaluation -- No --> FeedbackReject[Log Feedback & Update Match Status]
    Evaluation -- Yes --> SelectCandidate[Candidate Selected & Salary Finalized]
    
    SelectCandidate --> IssueOffer[Generate & Dispatch Offer Letter]
    IssueOffer --> OfferAccepted{Offer Accepted?}
    OfferAccepted -- Declined --> FallbackMatch[Re-open Slot & Source Backup]
    OfferAccepted -- Accepted --> AwaitJoining[Monitor Joining Timeline]
    
    AwaitJoining --> ReportingVerification{Candidate Joined on Target Date?}
    ReportingVerification -- No Show --> LogNoShow[Mark No-Show & Trigger Replacement]
    ReportingVerification -- Joined --> ConfirmJoining[Confirm Joining & Record Verification]
    
    ConfirmJoining --> CalcRemaining[Recalculate Remaining = Total - Joined]
    CalcRemaining --> FullCheck{Joined == Required?}
    FullCheck -- No --> InProgress[Status: Partially Fulfilled / Sourcing Active]
    InProgress --> MatchEngine
    FullCheck -- Yes --> MarkFulfilled[Status: Requirement Fulfilled]
    MarkFulfilled --> EndProcess([Requirement Successfully Closed])
```

---

## 2. Partial Fulfillment State Machine

```mermaid
stateDiagram-v2
    [*] --> Submitted
    Submitted --> Approved : Auto-Validation Passed
    Approved --> InProgress : Recruiter Assigned & Matching Sourced
    InProgress --> PartiallyFulfilled : First Candidate(s) Confirmed Joined
    PartiallyFulfilled --> InProgress : Sourcing Remaining Vacancies
    PartiallyFulfilled --> Fulfilled : Total Joined >= Total Required
    Fulfilled --> Closed : Final Handover & Audit Logged
    Closed --> [*]
```

