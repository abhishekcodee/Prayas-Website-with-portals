# Part 02 — UI/UX Design System Specification
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Specification Version:** `2.0.0`  
**Document Code:** `DOC-REBUILD-02-DESIGN-SYSTEM`  
**Design Target:** Modern Enterprise HRMS & Industrial Recruitment SaaS Platform

---

## 1. Design System Principles & Philosophy

The **PRAYAS Design System 2.0** provides an enterprise-grade visual, typographic, and component foundation engineered specifically for manufacturing talent acquisition, multi-tenant employer administration, and ITI candidate tracking.

### Core Principles:
- **Clarity & Information Density:** Interfaces prioritize operational legibility over decorative embellishments. Metrics and tables are concise, high-contrast, and scannable.
- **Visual Hierarchy:** Distinct visual weight between primary actions, secondary operations, and destructive triggers.
- **Zero Generic AI Aesthetics:** Clean, purpose-built layout without excessive blur filters, oversized gradients, or non-functional spacing.
- **Portability & Determinism:** Layout components adapt seamlessly across viewport sizes from 320px mobile screens to 4K desktop displays.

---

## 2. Branding & Centralized Color System

The color system is defined via standard CSS variables in [`assets/css/dashboard.css`](file:///c:/Manpower%20Portal/assets/css/dashboard.css). All components across the portal must reference these variables:

| Token Name | Hex Value | Semantic / Functional Role |
|---|:---:|---|
| `--primary` | `#EF6B6B` | Brand primary accent, active links, primary CTA buttons, focus outlines |
| `--primary-hover` | `#E25555` | Darkened state for primary button and interactive link hovers |
| `--primary-light` | `rgba(239, 107, 107, 0.12)` | Subtle tinted background for active sidebar items and stat cards |
| `--deep-navy` | `#0F172A` | Left sidebar background, primary typography, high-contrast headers |
| `--navy-surface` | `#1E293B` | Sidebar headers, secondary cards, dark mode surfaces |
| `--secondary-text`| `#475569` | Table subheaders, form labels, supporting descriptions |
| `--muted-text` | `#64748B` | Timestamp notes, metadata captions, disabled indicators |
| `--background` | `#F8FAFC` | Platform application background canvas |
| `--card-background`| `#FFFFFF` | Elevated white card and data container backgrounds |
| `--border` | `#E2E8F0` | Structural dividing borders, card borders, table line rules |
| `--border-light` | `#F1F5F9` | Table hover fills, background highlights |
| `--success` | `#16A34A` | Verification approved, vacancy fulfilled, joined candidate status |
| `--warning` | `#F59E0B` | Requisition pending review, interview scheduled, draft alerts |
| `--danger` | `#DC2626` | Requisition cancellation, candidate rejection, validation errors |
| `--info` | `#2563EB` | Active recruitment progress, telephonic screening, matching runs |

---

## 3. Typography Hierarchy

The portal standardizes on the **Urbanist** geometric sans-serif typeface, loaded with fallback to modern system fonts:
```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
```

### Typographic Scale:

| Typography Role | CSS Class / Element | Font Size | Weight | Line Height | Color |
|---|---|:---:|:---:|:---:|---|
| **Dashboard Title** | `h1`, `.dashboard-title` | **26px** (1.625rem) | 700 (Bold) | 1.25 | `#0F172A` |
| **Section Title** | `h2`, `.section-title` | **19px** (1.20rem) | 600 (Semibold) | 1.35 | `#0F172A` |
| **Card / Widget Title** | `h3`, `.card-title` | **16px** (1.00rem) | 600 (Semibold) | 1.40 | `#0F172A` |
| **Body Text** | `p`, `body`, `.text-body-custom`| **14px** (0.875rem) | 400 (Regular) | 1.50 | `#0F172A` |
| **Supporting / Meta Text**| `.supporting-text` | **13px** (0.8125rem)| 500 (Medium) | 1.40 | `#475569` |
| **Micro Caption** | `.micro-text` | **11.5px** (0.72rem)| 600 (Semibold) | 1.20 | `#64748B` |

---

## 4. Application Layout Specifications

### 4.1 Left Sidebar (`#sidebar`)
- **Width:** `260px` (Expanded) / `80px` (Collapsed Desktop) / `0px` (Hidden Drawer on Mobile).
- **Background:** Deep Navy (`#0F172A`) with subtle vertical separator (`border-right: 1px solid rgba(255,255,255,0.06)`).
- **Header:** Contains official PRAYAS logo, brand subtitle ("Automation Academy"), and mobile dismiss button.
- **Active State:** Left border pill (`border-left: 3px solid #EF6B6B`), tinted background (`rgba(239, 107, 107, 0.12)`), white text.
- **Collapsible Mode:** Desktop toggle (`#sidebarCollapseBtn`) compresses sidebar into an icon-only dock (`80px`), remembering preference via `localStorage`.

### 4.2 Top Header (`.top-navbar`)
- **Height:** `64px` fixed height, sticky positioned at `top: 0`, white surface, border bottom `#E2E8F0`.
- **Breadcrumb Navigation:** Renders hierarchical trail (`PRAYAS / Portal / Page Title`).
- **Global Search:** Integrated search input with magnifying glass icon and subtle focus glow.
- **Notification Bell:** Dropdown menu with unread badge counter, showing recent 5 alert items.
- **User Profile Pill:** Displays user initials in circular avatar badge, user full name, assigned role, and quick actions (Change Password, Sign Out).

### 4.3 Main Content Area (`#main-content`, `.page-container`)
- **Margins:** Dynamically adjusts to sidebar width (`margin-left: 260px` or `margin-left: 80px`).
- **Container Padding:** 24px desktop, 16px tablet, 12px mobile.

---

## 5. Reusable Component Catalog

### 5.1 Buttons
- **Primary:** `.btn-primary` (Background: `#EF6B6B`, Hover: `#E25555`, Focus ring: `rgba(239, 107, 107, 0.25)`).
- **Secondary:** `.btn-secondary` (White fill, border `#E2E8F0`, text `#475569`, hover `#F1F5F9`).
- **Danger:** `.btn-danger` (Background: `#DC2626`, Hover: `#B91C1C`).
- **Icon Button:** `.btn-icon` (36×36px square or rounded action button).

### 5.2 Form Inputs & Controls
- **Inputs & Selects:** `.form-control`, `.form-select` (Height: 40px, border-radius: 8px, border `#E2E8F0`, focus border `#EF6B6B`).
- **Required Indicator:** `.form-label.required::after` renders red asterisk `*`.
- **Toggle Switches:** `.form-switch .form-check-input` with brand primary toggle accent.

### 5.3 Status Badges (`.badge-status`)
All statuses render with semantic pastel backgrounds and high-contrast text:
- `.badge-verified`, `.badge-active`, `.badge-approved`: Soft Green (`#DCFCE7` text `#166534`).
- `.badge-pending`, `.badge-scheduled`: Soft Amber (`#FEF3C7` text `#92400E`).
- `.badge-rejected`: Soft Red (`#FEE2E2` text `#991B1B`).
- `.badge-under-review`, `.badge-shortlisted`: Soft Indigo (`#E0E7FF` text `#3730A3`).
- `.badge-in-progress`: Soft Blue (`#DBEAFE` text `#1E40AF`).
- `.badge-fulfilled`, `.badge-joined`: Emerald Green (`#D1FAE5` text `#065F46`).

### 5.4 Summary & KPI Cards (`.summary-card`)
- Clean white card, border `#E2E8F0`, border-radius 12px, padding 20px.
- Metric value rendered in 28px bold, metric label in 12.5px uppercase medium.
- Icon container with tinted semantic background (`.icon-box-primary`, `.icon-box-success`, etc.).

### 5.5 Enterprise Data Tables (`.table-prayas`, `.card-table`)
- Table headers: Uppercase 12px bold, background `#F8FAFC`, border bottom `#E2E8F0`.
- Table rows: 13px vertical padding, subtle hover highlight `#F8FAFC`.
- Overflow container: Responsive horizontal scrolling enabled via `.table-responsive`.

### 5.6 Empty State (`.empty-state`)
- Centered circular icon container (64×64px).
- Bold empty state title and explanatory subtext.
- Action call-to-action button to initiate record creation.

---

## 6. Responsive Breakpoint Standards

- **Desktop (>= 992px):** Expanded 260px sidebar (or 80px collapsed), full top search bar, multi-column KPI grids (4 columns), full tabular data views.
- **Tablet (768px – 991.98px):** Sidebar hidden off-canvas with backdrop toggle, 2-column KPI cards, full table scrolling.
- **Mobile (< 768px):** Drawer sidebar with slide-in animation, single-column KPI cards, compact table padding, mobile header with hamburger menu.

---

## 7. Accessibility & Ergonomics

- **Color Contrast:** All text tokens meet WCAG 2.1 AA minimum contrast ratios (> 4.5:1 for normal text, > 3:1 for large text).
- **Keyboard Focus:** Visible focus indicator rings (`0 0 0 3px rgba(239, 107, 107, 0.25)`) implemented across all interactive form controls and buttons.
- **Semantic Tags:** Headers utilize `<header>`, navigation `<nav>`, main content `<main>`, footers `<footer>`.

