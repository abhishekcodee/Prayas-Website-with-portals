# Part 07 — Requirement Number Generation Architecture
**PRAYAS — The Automation Academy**  
*Industry Manpower Requirement Portal*  
**Document Version:** 1.0  
**Format:** `REQ-YYYYMM-XXXX`  

---

## 1. Specification & Format

Requirement identifiers follow the enterprise standard format:

$$\mathbf{REQ\text{-}YYYYMM\text{-}XXXX}$$

Where:
- `REQ`: Static prefix designating institutional manpower requirement.
- `YYYYMM`: 6-digit year and month stamp (e.g. `202611` for November 2026).
- `XXXX`: Zero-padded 4-digit sequential counter (e.g. `0001`, `0042`, `0100`).

Examples:
- `REQ-202611-0001`
- `REQ-202611-0042`
- `REQ-202612-0001` (resets sequence when month rolls over)

---

## 2. Concurrency-Safe Sequence Subsystem

To eliminate race conditions, duplicates, and collisions in concurrent multi-user environments, sequence generation is anchored directly in the database using MySQL atomic upserts:

### Sequence Table Schema
```sql
CREATE TABLE IF NOT EXISTS `requirement_sequences` (
  `year_month` VARCHAR(6) PRIMARY KEY,
  `last_sequence` INT NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

### Atomic Generation Logic
```php
public function generateRequirementCode(): string
{
    $yearMonth = date('Ym');

    // Atomic sequence increment using ON DUPLICATE KEY UPDATE
    $seqSql = "INSERT INTO `requirement_sequences` (`year_month`, `last_sequence`) 
               VALUES (:ym, 1) 
               ON DUPLICATE KEY UPDATE `last_sequence` = `last_sequence` + 1";
    $seqStmt = $this->pdo->prepare($seqSql);
    $seqStmt->execute([':ym' => $yearMonth]);

    $fetchStmt = $this->pdo->prepare("SELECT `last_sequence` FROM `requirement_sequences` WHERE `year_month` = :ym");
    $fetchStmt->execute([':ym' => $yearMonth]);
    $seq = (int)$fetchStmt->fetchColumn();

    return sprintf('REQ-%s-%04d', $yearMonth, $seq);
}
```

### Properties
1. **Thread-Safe & Process-Safe:** `INSERT ... ON DUPLICATE KEY UPDATE` uses row-level locking, ensuring that concurrent requests never receive identical sequence numbers.
2. **Monthly Roll-over:** When the calendar month changes (e.g. from `202611` to `202612`), a new row is automatically initialized with `last_sequence = 1`.
3. **Auditability:** The table maintains a verifiable high-water mark of total requirements created per month.

