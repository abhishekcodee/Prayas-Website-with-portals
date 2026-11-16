# Part 06 — Industry Team Management & Granular Permissions
**PRAYAS — The Automation Academy**  
*Industry Manpower Requirement Portal*  
**Document Version:** 2.0  
**Phase:** Cycle 2.0 Modernization & Stabilization  

---

## 1. Industry Team Management Specifications

The Industry Team Management module (`views/industry/team_members.php`) enables company administrators to invite, organize, and configure permissions for corporate collaborators, plant coordinators, and interview leads within their authorized company tenant.

---

## 2. Team Member Roster Data Model

Each team member is displayed with:
- **Employee / User ID:** Primary system key (`users.id`).
- **Name & Contact:** Full legal name and contact phone number.
- **Email Address:** Authenticated login corporate email address.
- **Designation:** Corporate title (e.g. Senior Plant HR Manager, Sourcing Lead).
- **Assigned Facility:** Restricted manufacturing plant unit or "All Facilities / Central HQ".
- **Assigned Role:** `industry_admin` (Administrator) or `industry_user` (Team Member).
- **Account Status:** `Active` (authorized) vs `Suspended` (access restricted).
- **Registration Date:** Account creation timestamp.

---

## 3. Granular RBAC Permissions Architecture

Industry Administrators possess full administrative authority within their company. For Industry Team Members (`industry_user`), the portal enforces a granular capability matrix stored in `system_settings` (`user_perms_{userId}`):

| Permission Key | Capability Description | Default Admin | Default Team Member |
| :--- | :--- | :---: | :---: |
| `view_profile` | View company and industry legal profile information | Granted | Granted |
| `edit_profile` | Update corporate information, logo, and address | Granted | Restricted |
| `view_plants` | Inspect manufacturing plant roster and facility coordinates | Granted | Granted |
| `manage_plants` | Add, edit, or toggle status of manufacturing plants | Granted | Restricted |
| `view_requirements` | View posted manpower requisitions and hiring deadlines | Granted | Granted |
| `manage_requirements` | Create, submit, or edit manpower requisitions | Granted | Restricted |
| `view_candidates` | Access matched candidate profiles, screening, and interview status | Granted | Restricted |
| `view_reports` | Generate hiring fulfillment and analytical reports | Granted | Restricted |

### Server-Side Enforcement Policy
- Client-submitted permissions are sanitized and persisted using PDO prepared statements.
- Direct URL access and API endpoints verify capabilities using `IndustryController::getUserPermissions()`.
- Modifying permissions automatically records a `USER_PERMISSIONS_UPDATED` entry in `audit_logs` and dispatches an in-app alert to the affected user.

---

## 4. Team Member Invitation Workflow (`views/industry/invite_member.php`)

1. **Role Boundary Enforcement:** Industry Administrators can only invite `industry_admin` or `industry_user` accounts. Escalation attempts to invite `super_admin` or `recruiter` are blocked at the controller layer.
2. **Quota Verification:** Server-side verification confirms that current active users do not exceed the company's `max_users` limit.
3. **Cryptographic Tokens:** Single-use 64-character hexadecimal tokens generated with `random_bytes(32)` valid for 7 days.
4. **Lifecycle Actions:** Pending invitations can be extended (`resend_invitation`) or revoked (`revoke_invitation`) at any time before acceptance.

