# 12. Performance & Benchmark Testing Report

## Project Identity
- **Project:** Industry Manpower Requirement Portal
- **Organization:** PRAYAS – The Automation Academy
- **Module:** Performance Benchmarks & Query Optimization
- **Test File:** `tests/test_performance_suite.php`

---

## 1. Executive Performance Summary

Performance benchmark tests were conducted under local hardware conditions running Core PHP 8.2 with MariaDB/MySQL on Windows. The portal demonstrates execution speeds across large dataset aggregations, multi-factor candidate scoring, and pagination.

All 8 benchmarks passed within their target latency thresholds.

---

## 2. Benchmark Execution Results

| Benchmark ID | Operation & Target | Target Threshold | Actual Elapsed Time | Status |
| :---: | :--- | :---: | :---: | :---: |
| **BM-01** | **Consolidated Executive Metrics:** Aggregating 14 KPIs across companies, requirements, candidates, and joinings | < 200.00 ms | **3.75 ms** | **PASS (53x faster)** |
| **BM-02** | **Algorithmic Sourcing & Matching Scan:** Sourcing across 1,348 accredited ITI students with full 100-point multi-factor scoring | < 1,800.00 ms | **176.83 ms** | **PASS (10x faster)** |
| **BM-03A**| **Requirement-Wise Vacancy Report Query:** Aggregating demand, fulfillment, and status across all companies | < 150.00 ms | **2.52 ms** | **PASS (59x faster)** |
| **BM-03B**| **Company-Wise Consolidation Report Query:** Grouping requirements, vacancies, and hiring metrics by company | < 150.00 ms | **2.90 ms** | **PASS (51x faster)** |
| **BM-03C**| **Candidate Sourcing Report Query (500 records):** Dimension subscores, eligibility, and requirement linkage | < 150.00 ms | **4.51 ms** | **PASS (33x faster)** |
| **BM-04A**| **Pagination Query — Page 1 (Offset 0, Limit 20):** Primary dashboard candidate listing | < 50.00 ms | **0.82 ms** | **PASS (60x faster)** |
| **BM-04B**| **Pagination Query — Page 20 (Deep Offset 400, Limit 20):** Deep table pagination | < 50.00 ms | **1.56 ms** | **PASS (32x faster)** |
| **BM-05** | **Audit Log Ledger Write Throughput:** 5 consecutive transactional INSERT operations with IP and JSON payload | < 100.00 ms | **30.09 ms** | **PASS (3.3x faster)** |

---

## 3. Query Optimization Architecture

1. **Compound Multi-Column Indexes:**
   - `idx_cm_status_score` (`candidate_matches`): Optimizes stage filtering and tier ranking.
   - `idx_req_company_status` (`manpower_requirements`): Accelerates tenant-scoped dashboard queries.
   - `idx_rt_req_trade` (`requirement_trades`): Speeds up multi-trade vacancy summations.
2. **Selective Projection (`SELECT column1, column2`):**
   - High-volume listing queries avoid wildcard (`SELECT *`) transfers, reducing memory footprints.
3. **Prepared Statements with Zero Emulation:**
   - Parameterized queries eliminate SQL parsing overhead on repeated execution.
4. **Client-Side Rendering of Charts:**
   - Chart.js generates Canvas visuals on the client side from JSON arrays, eliminating server-side image processing bottlenecks.

