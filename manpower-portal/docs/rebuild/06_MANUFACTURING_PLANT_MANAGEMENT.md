# Part 06 — Manufacturing Plant Management Architecture
**PRAYAS — The Automation Academy**  
*Industry Manpower Requirement Portal*  
**Document Version:** 2.0  
**Phase:** Cycle 2.0 Modernization & Stabilization  

---

## 1. Physical Facility Model & Plant Management

In the PRAYAS portal, a manufacturing plant represents a physical industrial factory, workshop, or tooling facility operated by a corporate employer. Manpower requisitions are designated to specific plants to match candidates according to geographical proximity and localized shift patterns.

---

## 2. Plant Directory Specifications (`views/industry/plants.php`)

The Plant Directory provides a centralized management console with:
- **Unique Plant Identifier & Code:** System ID and unique corporate code (e.g. `PLT-PUN-01`).
- **Plant Name & Facility Type:** Physical unit label and classification (e.g. Automated Assembly Line, CNC Machine Shop, Heavy Fabrication Shop).
- **Geographic Coordinates:** City, Taluka, District, State, and PIN code.
- **Plant Placement Coordinator:** Local HR lead or plant manager responsible for receiving candidate batches.
- **Assigned Platform Administrator:** Authenticated user designated as the facility coordinator.
- **Live Requisition Count:** Real-time subquery calculating active requisitions designated for this facility.
- **Operational Status:** `Active` (operational) vs `Inactive` (idle / maintenance hold).
- **Actions:** View Dossier, Edit Facility, Assign Coordinator modal, Soft Toggle Status.

---

## 3. Five-Section Plant Registration Wizard (`views/industry/plant_form.php`)

### Section A — Plant Information
- **Plant Name / Facility Label:** Minimum 2 characters.
- **Plant Code:** Alphanumeric unique code enforced per company.
- **Establishment Year:** Year facility was constructed/commissioned.
- **Plant Facility Type:** Categorization (Assembly, CNC Shop, Tool Room, R&D).
- **Manufacturing Sector:** Sector specialization (Automotive, Defense, Precision Engineering).
- **Plant Description:** Detailed operational summary.

### Section B — Location & Coordinates
- **Physical Plant Address / Plot Details:** Industrial estate/plot number.
- **City / Town:** Municipality or urban center.
- **Taluka / Sub-District:** Administrative sub-district.
- **District & State:** State and District coordinates.
- **PIN Code:** 6-digit Indian Postal PIN code.
- **Country:** Default `India`.
- **Google Maps Location URL:** Optional deep link for candidate directions.

### Section C — Contact Details & Coordinators
- **Plant Contact Person:** Local plant manager or HR officer.
- **Designation / Role Title:** Corporate position.
- **Official Email Address:** Plant communications email.
- **Primary Contact Number:** Phone number ($\ge 7$ digits).
- **Alternate Contact Number:** Landline / secondary mobile.

### Section D — Manufacturing Operations
- **Manufacturing Activity:** Operational focus (e.g. Sheet Metal Stamping, Robotic Welding).
- **Production Category:** Line style (Serial Line Production, High Precision Batch).
- **Active Departments:** Comma-separated list (Press Shop, Assembly, Quality, Maintenance).
- **Production Shifts:** 1 to 4 shifts per day.
- **Working Days:** 5, 6, or 7 days per week.
- **Approximate Workforce Size:** Headcount bracket (<100, 100-250, 250-500, 500-1000, 1000+).
- **Operational Status:** Active Operational vs Inactive Standby.

### Section E — Review & Commit
- Validation of all required fields on the server.
- Backend verification of company quota limits (`max_plants`).
- Persistent storage of base record in `manufacturing_plants` and operational metadata in `system_settings` (`plant_meta_{id}`).
- Dispatch of in-app notification to company administrators.

---

## 4. Dedicated Plant Dossier View (`views/industry/plant_details.php`)

The dossier view renders comprehensive facility intelligence:
1. **Facility Coordinates Dossier:** Physical plot address, PIN code, Taluka, Google Maps directions.
2. **Operations Profile:** Active departments, shift schedules, working days, approximate workforce tier.
3. **Coordinators Profile:** Designated placement lead and assigned platform administrator.
4. **Linked Requisitions Roster:** Formatted data table of all historical and active manpower requirements designated for this physical site, showing vacancies, on-ground joinings, deadlines, and status.
5. **Assigned Plant Team:** Roster of company team members restricted to or coordinating this site.

