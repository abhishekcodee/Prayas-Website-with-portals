# 26 — Role & Permission (RBAC) Governance Architecture

**Project:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Component:** Role-Based Access Control (RBAC) Management  
**Document ID:** DOC-RBC-26  
**Status:** Approved & Implemented  

---

## 1. Overview & Architectural Principles

The portal implements a dynamic, database-backed **Role-Based Access Control (RBAC)** architecture configured via `views/super_admin/roles_permissions.php`.

The system decouples users from capabilities through permission assignments:
1. **Roles:** Defined in `roles` (`super_admin`, `recruiter`, `industry_admin`, `industry_user`).
2. **Permissions:** Defined in `permissions` categorized into modules (Requirements, Sourcing, Interviews, Placements, Companies, Users, Audit, Settings).
3. **Role-Permission Mappings:** Stored in `role_permissions` join table.

---

## 2. Interactive Permission Matrix

The management interface presents a granular, grouped permission matrix allowing Super Admins to dynamically grant or revoke capabilities across all roles:

```
+-------------------------------------------------------------------------------+
| Module / Capability Name          | super_admin | recruiter | ind_admin | user|
+-------------------------------------------------------------------------------+
| [Company Management]              |             |           |           |     |
|   company.view                    |    [ X ]    |   [ X ]   |   [ X ]   | [X] |
|   company.create                  |    [ X ]    |   [   ]   |   [   ]   | [ ] |
|   company.verify (CRITICAL)       |    [ L ]    |   [   ]   |   [   ]   | [ ] |
|   company.suspend (CRITICAL)      |    [ L ]    |   [   ]   |   [   ]   | [ ] |
| [Requirements Management]         |             |           |           |     |
|   requirement.create              |    [ X ]    |   [   ]   |   [ X ]   | [ ] |
|   requirement.approve             |    [ X ]    |   [ X ]   |   [   ]   | [ ] |
| [Settings & Audit]                |             |           |           |     |
|   settings.manage (CRITICAL)      |    [ L ]    |   [   ]   |   [   ]   | [ ] |
|   audit.view (CRITICAL)           |    [ L ]    |   [   ]   |   [   ]   | [ ] |
+-------------------------------------------------------------------------------+
Legend: [ X ] Active Checkbox | [ L ] Locked Immutable Checkbox (Lockout Guard)
```

---

## 3. Super Admin Lockout Guard

### The Security Risk
In flexible dynamic RBAC systems, an administrator may inadvertently uncheck critical administrative permissions from the `super_admin` role, resulting in an unrecoverable system lockout.

### The Lockout Prevention Mechanism
The system enforces a **Zero-Lockout Security Guard** at both the UI layer and the backend controller layer:

1. **Immutable Essential Permissions:**
   The following permissions are permanently locked for `super_admin` and cannot be toggled off:
   - `settings.manage`
   - `audit.view`
   - `user.update`
   - `user.delete`
   - `company.verify`
   - `company.suspend`
   - `role.manage`

2. **Backend Guard in `roles_permissions.php`:**
   ```php
   $essential_super_admin_permissions = [
       'settings.manage', 'audit.view', 'user.update', 
       'company.verify', 'company.suspend'
   ];
   
   // Hard enforcement: re-inject essential permissions before saving
   foreach ($essential_super_admin_permissions as $perm_slug) {
       if (isset($perm_ids_by_slug[$perm_slug])) {
           $super_admin_assigned[] = $perm_ids_by_slug[$perm_slug];
       }
   }
   ```

3. **Atomic Transaction Replacement:**
   Permission updates for any role execute inside an atomic database transaction (`DELETE FROM role_permissions WHERE role_id = ?` followed by bulk insertion of selected permissions) preventing half-applied permission states.

---

## 4. Audit & Cache Invalidation

Any modification to role permissions triggers:
1. Recording of `ROLE_PERMISSIONS_UPDATED` in `audit_logs` detailing the role modified and the permission delta.
2. Invalidation of active permission session caches, forcing dynamic re-evaluation on next authenticated request.

