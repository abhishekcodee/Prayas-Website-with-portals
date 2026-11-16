# 22 — Centralized Route & Permission Engine

## Document Identifier: RTE-PRA-2026-V1.0
**Project Title:** Industry Manpower Requirement & ITI Candidate Recruitment Portal  
**Organization:** PRAYAS – The Automation Academy  
**Components:** `includes/permissions.php`, `includes/auth_guard.php`, `includes/csrf.php`  
**Date:** 2026-11-05  

---

## 1. Architectural Design

Authorization is never delegated solely to presentation layers (such as hiding buttons or links). Every backend route, view, and API endpoint independently invokes centralized security barriers before executing business logic.

```
Incoming Request
       │
       ├── requireAuth()           --> Validates active session & forces password change if flagged
       ├── requireCSRF()           --> Validates 64-char hex token on POST/PUT/DELETE
       ├── requireRole($roles)     --> Enforces role boundary (e.g. 'super_admin')
       ├── requirePermission($perm)--> Enforces granular capability (e.g. 'requirement.create')
       └── requireCompanyVerified()--> Confirms verified industrial partner status
```

---

## 2. Granular Permission Helper API

The portal provides an expressive API via `includes/permissions.php`:

### 2.1 `hasRole(string|array $roles): bool`
Verifies whether the authenticated user has a specific role or belongs to an allowed set:
```php
if (hasRole(['super_admin', 'recruitment_admin'])) {
    // Render administrative work order controls
}
```

### 2.2 `hasPermission(string $permissionName): bool`
Verifies atomic capability. Super Admin accounts automatically bypass permission checks via universal override:
```php
if (hasPermission('requirement.approve')) {
    // Render approval action button
}
```

### 2.3 `getCompanyId(): ?int`
Enforces multi-tenant data isolation. **Never** reads company identifiers from `$_POST` or `$_GET`:
```php
$stmt = $pdo->prepare("SELECT * FROM manpower_requirements WHERE id = :id AND company_id = :session_cid");
$stmt->execute([':id' => $reqId, ':session_cid' => getCompanyId()]);
```

---

## 3. Enforcement Guard Functions

### 3.1 `requireAuth()`
Halts unauthenticated access and redirects to `/views/auth/login.php` with the current URI stored in `return_url`.

### 3.2 `requireRole(string|array $roles)`
Invokes `requireAuth()`, then verifies role membership. If unauthorized, halts with HTTP 403 Forbidden and writes an intrusion attempt entry to `audit_logs`.

### 3.3 `requirePermission(string $permissionName)`
Validates that the authenticated role contains the specific permission slug in `$_SESSION['permissions']`.

### 3.4 `requireCompanyVerified()`
For `industry_admin` users, checks whether `companies.verification_status` is `Verified`. If pending or rejected, redirects to the company profile view with a notification banner.

---

## 4. Multi-Tenant IDOR Defense

Insecure Direct Object Reference (IDOR) attacks are eliminated by enforcing two rules across all controllers:
1. **Rule 1 (Session Primacy):** The `company_id` column in all queries executed for industrial employers is bound to `getCompanyId()`.
2. **Rule 2 (Server Ownership Check):** If a user attempts to view a requisition by ID (`?id=45`), the query requires both the primary key and the session tenant ID:
   ```sql
   SELECT * FROM manpower_requirements WHERE id = :id AND company_id = :cid;
   ```
   If zero rows return, access is refused even if requirement #45 exists for another organization.

