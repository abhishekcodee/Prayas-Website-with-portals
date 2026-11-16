# PRAYAS — Google Stitch Design System Specification
**Project**: PRAYAS Industry Manpower Requirement Portal  
**Organization**: PRAYAS – The Automation Academy  
**Stitch Project**: `projects/15435729254100010793` (`PRAYAS Industry Manpower Portal - Enterprise UI`)  
**Design System Asset ID**: `assets/860f401e0734484681d2048a05082c09`  
**Generated Reference Screens in Stitch**:
1. `67f80381dca347238b8cf76ff5007f69` — Enterprise Executive Dashboard (Desktop)
2. `5354c5229be74bbab0fcd3b337111f07` — Candidate Sourcing & Matching Talent Pool (Desktop)
3. `506b7d41378d4ab98e41b186f3d00dde` — Multi-Step Manpower Requisition Wizard (Desktop)
4. `80a4aa6360d34b6ab6859c37892380ef` — Mobile Responsive Dashboard & Docked Navigation (Mobile)

---

## 1. Color System & Tokens

### Primary & Accent Palette
- `--color-primary`: `#EF6B6B` (Primary Coral Brand Accent, Primary CTAs, active indicators)
- `--color-primary-hover`: `#E25555` (Hover states for primary buttons)
- `--color-primary-subtle`: `#FEF2F2` (Soft pink/coral tint for active pills, badge backgrounds, focus rings)
- `--color-primary-container`: `#EF6B6B`
- `--color-on-primary`: `#FFFFFF`

### Dark Shell & Sidebar Palette
- `--color-deep-navy`: `#0F172A` (Sidebar background, top navigation bar dark accents, high-contrast titles)
- `--color-deep-navy-light`: `#1E293B` (Sidebar active item backgrounds, subtle dark borders)
- `--color-navy-muted`: `#334155` (Dividers in dark contexts)

### Neutral & Surface Palette
- `--color-canvas-bg`: `#F8FAFC` (Main app layout background)
- `--color-surface-white`: `#FFFFFF` (Card surfaces, dropdowns, modal windows, table rows)
- `--color-steel-gray`: `#475569` (Body text, subheadings, form labels)
- `--color-muted-slate`: `#64748B` (Table headers, placeholder text, secondary icons)
- `--color-border-default`: `#E2E8F0` (Default borders, card outlines, table row dividers)
- `--color-border-focus`: `#EF6B6B` (Active input ring & focus outline)

### Semantic Status Palette
- **Success / Active / Approved**:
  - Main: `#16A34A`
  - Background Tint: `#F0FDF4`
  - Border: `#BBF7D0`
- **Warning / Under Review / Pending**:
  - Main: `#F59E0B`
  - Background Tint: `#FFFBEB`
  - Border: `#FDE68A`
- **Danger / Needs Correction / Rejected / Overdue**:
  - Main: `#DC2626`
  - Background Tint: `#FEF2F2`
  - Border: `#FECACA`
- **Info / Matching Pool / Sourced**:
  - Main: `#2563EB`
  - Background Tint: `#EFF6FF`
  - Border: `#BFDBFE`

---

## 2. Typography Hierarchy

| Style Token | Font Family | Size | Weight | Line Height | Usage |
|:---|:---|:---:|:---:|:---:|:---|
| `font-headline-lg` | `'Urbanist', sans-serif` | 28px | 700 (Bold) | 36px | Main page titles, dashboard greeting headers |
| `font-headline-md` | `'Urbanist', sans-serif` | 20px | 600 (Semi-Bold) | 28px | Section headings, card titles, modal titles |
| `font-headline-sm` | `'Urbanist', sans-serif` | 16px | 600 (Semi-Bold) | 24px | Sub-section titles, widget headers |
| `font-body-medium` | `'Inter', sans-serif` | 14px | 500 (Medium) | 20px | Button labels, active tab items, emphasis text |
| `font-body` | `'Inter', sans-serif` | 14px | 400 (Regular) | 20px | Standard body prose, table cell values |
| `font-label` | `'Inter', sans-serif` | 12px | 500 (Medium) | 16px | Form input labels, badge text, tooltips |
| `font-table-header`| `'Inter', sans-serif` | 11px | 700 (Bold) | 16px | Uppercase table headers (`letter-spacing: 0.05em`) |

---

## 3. Component Library Specifications

### 3.1 Sidebar Navigation Shell
- **Width**: `260px` fixed desktop width.
- **Background**: Deep Navy `#0F172A`.
- **Branding Header**: PRAYAS logo mark with Coral `#EF6B6B` square dot, white bold title, muted slate subtitle.
- **Item States**:
  - Default: Text `#94A3B8`, subtle padding `10px 16px`, rounded `8px`.
  - Hover: Text `#FFFFFF`, background `#1E293B`.
  - Active: Text `#EF6B6B`, background `#1E293B`, `4px` solid `#EF6B6B` left accent border or pill highlight.
- **Accreditation Seal**: Bottom widget highlighting `Certified ITI Partner (NSDC & NCVT Validated)`.
- **Mobile Responsive**: Off-canvas slide-over drawer triggered by hamburger toggle on `< 992px` screens.

### 3.2 Top Header Navbar
- **Height**: `64px`.
- **Background**: Surface White `#FFFFFF` with `1px solid #E2E8F0` bottom border.
- **Plant Context Chip**: Pill container displaying active plant (`Tata Motors - Pune Plant`) with green status dot.
- **Global Search**: Soft `#F8FAFC` background input with magnifying glass icon and `#64748B` placeholder.
- **Notification Center**: Bell icon with Coral `#EF6B6B` badge count, smooth dropdown containing recent alerts.
- **User Profile**: Rounded avatar, name, role pill, dropdown for profile and sign-out.

### 3.3 KPI Metric Stat Cards
- **Container**: White `#FFFFFF` surface, `12px` rounded border radius (`rounded-xl`), `1px solid #E2E8F0`, subtle shadow `0 1px 3px rgba(0,0,0,0.05)`.
- **Icon Container**: `36px x 36px` rounded `8px` container in pastel tint (e.g. `#FEF2F2` for Reqs, `#FFFBEB` for Vacancies, `#EFF6FF` for Candidates, `#F0FDF4` for Fulfillment).
- **Number Metric**: Large `28px` bold (`font-headline-lg`), Deep Navy `#0F172A`.
- **Trend Indicator**: Pill badge with green/amber arrow (`+3 this week`, `88% fulfillment`).

### 3.4 Data Tables
- **Header Row**: Soft `#F8FAFC` background, uppercase micro-headers (`11px`, `700` weight, `#64748B` color).
- **Rows**: White `#FFFFFF` background, hover highlight `#F8FAFC/60`, border-bottom `1px solid #E2E8F0`.
- **Trade Badges**: Color-coded trade chips:
  - Fitter (`FIT`): `#FEF2F2` bg, `#EF6B6B` text.
  - Electrician (`ELE`): `#FFFBEB` bg, `#D97706` text.
  - Welder (`WLD`): `#FEF2F2` bg, `#DC2626` text.
  - Machinist (`MCH`): `#EFF6FF` bg, `#2563EB` text.
  - Turner (`TRN`): `#F1F5F9` bg, `#475569` text.
- **Status Badges**:
  - Approved / Active: `#F0FDF4` bg, `#16A34A` text, `#BBF7D0` border.
  - Under Review / Pending: `#FFFBEB` bg, `#F59E0B` text, `#FDE68A` border.
  - Needs Correction / Overdue: `#FEF2F2` bg, `#DC2626` text, `#FECACA` border.

### 3.5 Forms & Multi-Step Wizard
- **Inputs**: White surface, `#E2E8F0` border, `8px` rounded corners, padding `10px 14px`, `#EF6B6B` focus outline.
- **Labels**: `#475569`, 12px uppercase or 14px medium, required asterisk in `#EF6B6B`.
- **Progress Stepper**: 8 circular milestone badges connected by progress line:
  - Completed: Green `#16A34A` checkmark icon.
  - Active: Coral `#EF6B6B` with subtle outer halo ring.
  - Upcoming: Muted Slate `#94A3B8` outline.
- **Live Summary Sticky Card**: Requisition summary card on right column (32% width) displaying live match estimation, vacancies sum, and plant unit details.

### 3.6 Buttons & Action Controls
- **Primary**: Solid Coral `#EF6B6B`, hover `#E25555`, text `#FFFFFF`, rounded `8px`, shadow `0 1px 2px rgba(0,0,0,0.05)`.
- **Secondary / Outline**: White `#FFFFFF` surface, border `1px solid #E2E8F0`, text `#475569`, hover `#F8FAFC`.
- **Ghost / Icon**: Transparent surface, hover background `#F1F5F9`, text `#64748B`.
- **Danger**: Solid `#DC2626`, hover `#B91C1C`, text `#FFFFFF`.
