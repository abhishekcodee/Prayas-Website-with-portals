# 08 — User Roles & Permissions Reference

## Organization: PRAYAS – The Automation Academy
**Implementation Status:** Fully Codified & Verified (Part 04)  
**Access Model:** Role-Based Access Control (RBAC)  

---

## 1. Primary Documentation References

* **Complete RBAC Matrix & Action Primitives:**  
  [`docs/07_User_Roles_Permissions.md`](file:///c:/Manpower%20Portal/docs/07_User_Roles_Permissions.md)
* **Authentication Architecture:**  
  [`docs/20_Authentication_Architecture.md`](file:///c:/Manpower%20Portal/docs/20_Authentication_Architecture.md)
* **Session Lifecycle & Hardening:**  
  [`docs/21_Session_Management.md`](file:///c:/Manpower%20Portal/docs/21_Session_Management.md)
* **Route Permission Engine & IDOR Defense:**  
  [`docs/22_Route_Permission_Engine.md`](file:///c:/Manpower%20Portal/docs/22_Route_Permission_Engine.md)

---

## 2. Active RBAC Engine API Summary

All application routes and controllers use the following verified functions from `includes/permissions.php` and `includes/auth_guard.php`:

```php
// Authentication & Barrier Enforcement
requireAuth();                        // Halts unauthenticated access
requireRole('super_admin');            // Enforces specific role membership
requireRole(['super_admin', 'recruitment_admin']); // Enforces role set
requirePermission('requirement.create'); // Enforces granular permission slug
requireCompanyVerified();              // Enforces verified industrial status

// Contextual Helpers
isAuthenticated();                     // Returns boolean
getAuthUser();                         // Returns active user profile array
getUserId();                           // Returns integer user ID
getCompanyId();                        // Returns tenant company ID from session
getRoleName();                         // Returns 'super_admin', 'industry_admin', etc.
hasRole($role);                        // Returns boolean
hasPermission($perm);                  // Returns boolean (Super Admin always TRUE)
e($string);                            // HTML5 XSS escaping helper
```
