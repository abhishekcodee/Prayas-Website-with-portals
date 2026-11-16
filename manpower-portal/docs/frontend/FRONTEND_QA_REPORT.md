# PRAYAS — Frontend QA & Visual Verification Rubric
**Platform**: PRAYAS Industry Manpower Requirement Portal  
**Cycle**: Frontend Rebuild 3.0 (Powered by Google Stitch)  
**Status**: Baseline Quality Standard & Audit Framework

---

## 1. Visual Verification Rubric

Each rebuilt screen is audited against these 7 mandatory quality criteria:

1. **Brand Identity & Color Fidelity**:
   - Primary accents strictly use PRAYAS Coral `#EF6B6B` (hover `#E25555`).
   - Dark elements and sidebar strictly use Deep Navy `#0F172A` (active item `#1E293B`).
   - Background canvas strictly uses `#F8FAFC`.
   - Card surfaces strictly use `#FFFFFF` with `#E2E8F0` border.
   - Status colors strictly adhere to semantic definitions (Green `#16A34A`, Amber `#F59E0B`, Red `#DC2626`, Blue `#2563EB`).

2. **Typography Consistency**:
   - Headings rendered in Google Font **Urbanist** (weights 600, 700).
   - Body prose and tabular data rendered in Google Font **Inter** (weights 400, 500, 600).
   - Table headers formatted in uppercase, 11px, bold, with 0.05em tracking.

3. **Desktop Preservation & Layout Stability**:
   - The desktop layout (1440px - 1920px) must remain completely stable without text overflow, misaligned flex containers, or awkward gaps.
   - Fixed sidebar (260px) stays anchored; main content occupies remaining viewport fluidly.

4. **Mobile & Tablet Responsiveness**:
   - Screens adapt smoothly across:
     - Large Desktop: `≥ 1400px`
     - Desktop / Laptop: `1024px – 1399px`
     - Tablet: `768px – 1023px` (Sidebar collapses to slide-over drawer)
     - Mobile: `< 768px` (Touch-friendly 44px tap targets, stacked tables or scrollable containers, docked bottom bar where appropriate)
   - Mobile changes must NEVER compromise or degrade the desktop experience.

5. **Backend Logic & Form Integrity**:
   - All native PHP POST actions, form field `name` attributes, CSRF tokens, and validation errors are preserved 100%.
   - No broken redirects, missing session notices, or undefined variables.

6. **Micro-Interactions & Usability**:
   - Smooth hover states on buttons, table rows, and sidebar links (`transition: all 0.15s ease-in-out`).
   - Interactive modal popups with backdrop blur and accessible close buttons.
   - Form fields show visible `#EF6B6B` focus rings without default browser outline artifacts.

7. **Zero Console Errors**:
   - Clean browser console without missing resource 404s, JavaScript runtime exceptions, or CSS syntax errors.

---

## 2. Test Execution Checklist

| Category | Test Item | Verification Tool / Command | Pass Criteria | Status |
|:---|:---|:---|:---|:---:|
| **Design System** | Color token mapping | CSS Inspect | Matches `#EF6B6B`, `#0F172A`, `#F8FAFC` | PASS |
| **Typography** | Font asset loading | Network tab / curl | Urbanist & Inter loaded via Google Fonts | PASS |
| **Backend Guard** | Session and auth guards | PHP CLI / curl | `requireRole`, `requireCSRF` uncompromised | PASS |
| **Data Integrity** | Existing MySQL data | PHP PDO scripts | 100% untouched database | PASS |
| **Apache Routing** | Directory junctions | HTTP status | All routes resolve 200/302 | PASS |
