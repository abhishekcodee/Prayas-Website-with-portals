# 31 — Manufacturing Plant Management & Facility Architecture

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Distributed Manufacturing Facilities Module  
**Document ID:** DOC-IND-31  
**Status:** Approved & Implemented  

---

## 1. Overview & Business Requirements

Large industrial enterprises typically operate across multiple factory units, assembly complexes, fabrication tool rooms, and regional logistics hubs.

The **Manufacturing Plant Management Module** (`views/industry/plants.php`, `plant_form.php`) allows companies to register, maintain, and link individual manufacturing plants to manpower requirements.

---

## 2. Plant Entity Schema

Stored in `manufacturing_plants`:

| Column | Type | Constraints / Description |
|:---|:---|:---|
| `id` | `INT UNSIGNED AUTO_INCREMENT` | Primary Key |
| `company_id` | `INT UNSIGNED NOT NULL` | FK to `companies.id` ON DELETE CASCADE |
| `plant_name` | `VARCHAR(255) NOT NULL` | Plant facility label |
| `plant_code` | `VARCHAR(50) NOT NULL` | Internal plant code (e.g. `PL-PUN-01`) |
| `plant_address` | `TEXT NOT NULL` | Physical industrial plot / street address |
| `state` | `VARCHAR(100) NOT NULL` | State |
| `district` | `VARCHAR(100) NOT NULL` | District |
| `city` | `VARCHAR(100) NOT NULL` | City / Industrial zone |
| `pincode` | `VARCHAR(20) NOT NULL` | 6-digit Indian PIN code |
| `contact_person` | `VARCHAR(255) NOT NULL` | Local plant placement coordinator / HR |
| `contact_number` | `VARCHAR(50) NOT NULL` | Direct phone / mobile line |
| `plant_type` | `VARCHAR(100)` | Manufacturing Unit, Tool Room, Assembly Line, Logistics Hub |
| `manufacturing_sector`| `VARCHAR(100) NULL` | Sector (Automotive, Heavy Eng, CNC, Robotics) |
| `status` | `ENUM('Active', 'Inactive')` | Active status toggle |
| `created_at` / `updated_at` | `TIMESTAMP` | Timestamps |

### Key Constraints
- **Multi-Tenant Uniqueness:** `UNIQUE KEY uk_company_plant_code (company_id, plant_code)`. Plant codes must be unique within an individual company while allowing different companies to use standard plant nomenclature.
- **Strict Tenant Boundary:** Every query validates `company_id = ?` to prevent cross-company access or modification.

---

## 3. Workflows & Lifecycle Operations

1. **Add New Plant:** Validates address, PIN code format, unique plant code, and assigns to the authenticated enterprise.
2. **Edit Plant:** Allows modifying plant contact leads and address coordinates with IDOR validation.
3. **Status Toggle:** Active plants are presented in the requirement creation dropdown; inactive plants are retained for historical audit trails without accepting new job requisitions.
4. **Requisition Linkage:** Manpower requirements record `plant_id`, allowing candidates and recruiters to see the exact plant work site.

