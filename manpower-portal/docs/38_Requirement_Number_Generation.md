# 38 — Concurrency-Safe Requirement Number Generation Specification

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Concurrency-Safe Sequence & Code Generator  
**Document ID:** DOC-REQ-38  
**Status:** Approved & Implemented  

---

## 1. Requirement Code Format

Every requirement admitted into the portal is assigned an immutable, human-readable requisition code:

$$\text{Format: } \mathbf{REQ-YYYYMM-XXXX}$$

* **REQ:** Standard static brand prefix.
* **YYYYMM:** Four-digit calendar year and two-digit calendar month of generation (e.g. `202611`).
* **XXXX:** Concurrency-safe, zero-padded 4-digit sequential integer (e.g. `0001`, `0042`, `0999`).

---

## 2. Concurrency Safety Defect in Naive Approaches

In high-concurrency environments, relying on naive SQL approaches such as:
```sql
-- DANGEROUS: Susceptible to race conditions & duplicate key violations
SELECT COUNT(*) + 1 FROM manpower_requirements WHERE requirement_id LIKE 'REQ-202611-%';
```
results in identical IDs being computed simultaneously by parallel web requests, leading to collision errors and transaction rollbacks.

---

## 3. Database-Backed Atomic Sequence Engine

The platform implements an atomic sequence generator using a dedicated tracking table (`requirement_sequences`):

```sql
CREATE TABLE IF NOT EXISTS `requirement_sequences` (
    `year_month` VARCHAR(6) NOT NULL PRIMARY KEY,
    `last_sequence` INT UNSIGNED NOT NULL DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Atomic Increment Routine (`RequirementController::generateRequirementCode`)
```php
$yearMonth = date('Ym');

// 1. Atomic increment with row-level lock
$this->pdo->exec("INSERT INTO `requirement_sequences` (`year_month`, `last_sequence`) 
    VALUES ('{$yearMonth}', 1) 
    ON DUPLICATE KEY UPDATE `last_sequence` = `last_sequence` + 1");

// 2. Fetch the newly locked unique sequence integer
$stmt = $this->pdo->prepare("SELECT `last_sequence` FROM `requirement_sequences` WHERE `year_month` = :ym");
$stmt->execute([':ym' => $yearMonth]);
$seq = (int)$stmt->fetchColumn();

// 3. Format composite string
return sprintf('REQ-%s-%04d', $yearMonth, $seq);
```

### Security & Operational Guarantees:
- **Zero Race Conditions:** The InnoDB engine executes `ON DUPLICATE KEY UPDATE` atomically on the primary key row.
- **Server-Side Generation Only:** Requirement numbers are never generated or influenced by frontend JavaScript.
- **Uniqueness Guaranteed:** Supported by a `UNIQUE` index on `manpower_requirements.requirement_id`.

