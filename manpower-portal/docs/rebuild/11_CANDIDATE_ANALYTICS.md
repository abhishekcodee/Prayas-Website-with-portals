# Candidate Sourcing & ITI Analytics
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal

### 1. Overview
The Candidate Analytics engine connects industry manpower requirements with PRAYAS student repositories and authorized ITI candidate pools, providing deep visibility into talent availability, geographic distribution, and quality tiers.

### 2. Candidate Sourcing Reports
1. **Candidate Sourcing Consolidated Report (`getCandidateSourcingReport`):**
   - Displays matched candidates, match score %, trade, passing year, current recruitment stage, and target requisition.
2. **ITI / College-Wise Sourcing Repository (`getItiWiseCandidateReport`):**
   - Queries student repository (`prayas_iti_dashboard.students`) via read-only PDO connection.
   - Summarizes total available students and trades offered per technical institute.
3. **District-Wise Candidate Sourcing Distribution (`getDistrictWiseSourcingReport`):**
   - Maps student geographic concentrations across Pune, Nashik, Aurangabad, Satara, Kolhapur, etc.
4. **Candidate Quality Tiers Report (`getCandidateQualityTiersReport`):**
   - Segregates candidate matches into 5 score tiers:
     - Tier 1: 90%–100% (Exceptional Match)
     - Tier 2: 80%–89% (High Match)
     - Tier 3: 70%–79% (Moderate Match)
     - Tier 4: 60%–69% (Basic Match)
     - Tier 5: < 60% (Low Match)
   - Tracks conversion rates by quality tier.
5. **Trade-Wise Candidate Supply & Match Report (`getTradeWiseCandidateReport`):**
   - Analyzes talent depth and average match scores per trade.
