# Part 07 — Dynamic Trade-Wise Demand Engine
**PRAYAS — The Automation Academy**  
*Industry Manpower Requirement Portal*  
**Document Version:** 1.0  
**Subsystem:** Trade Demand Sub-Engine  

---

## 1. Architectural Purpose

Modern industrial manufacturing environments rarely hire a single monolithic technician profile. A robotic assembly line or automotive manufacturing unit requires a balanced allocation across disciplines: Fitters for structural assembly, Electricians for motor drives, Welders for high-pressure joints, and Machinists for CNC operations.

The **Dynamic Trade-Wise Demand Engine** enables companies to break down a single high-level requirement into granular, trade-specific demand rows.

---

## 2. Data Structure & Sub-Table Architecture

Trade records are stored in `requirement_trades`, linked via foreign key to `manpower_requirements.id`:

```sql
CREATE TABLE IF NOT EXISTS `requirement_trades` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `requirement_id` INT NOT NULL,
  `trade_name` VARCHAR(100) NOT NULL,
  `vacancies` INT NOT NULL DEFAULT 1,
  `qualification` VARCHAR(100) DEFAULT 'ITI',
  `board_type` ENUM('NCVT','SCVT','Either') DEFAULT 'Either',
  `passing_year_from` VARCHAR(10) NULL,
  `passing_year_to` VARCHAR(10) NULL,
  `required_skills` TEXT NULL,
  `min_experience` VARCHAR(50) DEFAULT 'Fresher',
  `max_experience` VARCHAR(50) NULL,
  `fresher_allowed` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`requirement_id`) REFERENCES `manpower_requirements`(`id`) ON DELETE CASCADE
);
```

---

## 3. Supported Technical ITI Trades

The engine provides out-of-the-box standardization for core industrial trades:
- **Fitter**
- **Electrician**
- **Machinist**
- **Turner**
- **Welder (Gas & Electric)**
- **Instrument Mechanic**
- **Tool & Die Maker**
- **Electronics Mechanic**
- **Wireman**
- **Mechanic Motor Vehicle (MMV)**
- **COPA (Computer Operator & Programming Assistant)**
- **Refrigeration & AC Mechanic**
- **Sheet Metal Worker**
- **Draughtsman (Mechanical)**

---

## 4. Vacancy Sum Parity Algorithm

The engine enforces strict parity between trade allocations and the total requirement capacity:

$$\text{quantity\_required} = \sum_{i=1}^{N} \text{vacancies}_i$$

- **Client-Side:** JavaScript listeners on `.trade-vacancies` inputs automatically update the visual vacancy badge and the hidden `quantity_required` field in real-time.
- **Server-Side:** `RequirementValidationEngine` computes the sum of all trade row allocations and verifies equality with `quantity_required`. If any trade row has $\le 0$ vacancies or if the sum does not match `quantity_required`, submission is rejected with validation error `ERR-TRD-03`.

