<?php
/**
 * Seeder: Roles and Permissions
 * Database: manpower_portal_db
 * Organization: PRAYAS – The Automation Academy
 */

function seedRolesAndPermissions(PDO $pdo): void {
    echo "[+] Seeding Roles and Permissions..." . PHP_EOL;

    // 1. Roles
    $roles = [
        ['name' => 'super_admin', 'label' => 'Super Administrator', 'description' => 'Unrestricted governance, company verification, user and system management.'],
        ['name' => 'industry_admin', 'label' => 'Industry Partner Admin', 'description' => 'Industrial employer managing company profile and manpower requisitions.'],
        ['name' => 'recruitment_admin', 'label' => 'Recruitment Operations Lead', 'description' => 'Operational lead managing talent matching and recruiter work orders.'],
        ['name' => 'recruiter', 'label' => 'Recruiter / HR Specialist', 'description' => 'Tactical recruiter conducting candidate screening, interviews, and joining verification.']
    ];

    $roleStmt = $pdo->prepare("INSERT INTO `roles` (`name`, `label`, `description`) 
        VALUES (:name, :label, :description)
        ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `description` = VALUES(`description`)");

    foreach ($roles as $role) {
        $roleStmt->execute($role);
    }

    // Retrieve role IDs
    $roleMap = [];
    $rows = $pdo->query("SELECT id, name FROM `roles`")->fetchAll();
    foreach ($rows as $row) {
        $roleMap[$row['name']] = (int)$row['id'];
    }

    // 2. Permissions
    $permissions = [
        // Companies
        ['name' => 'company.create', 'module' => 'companies', 'description' => 'Register new company'],
        ['name' => 'company.read', 'module' => 'companies', 'description' => 'View company details'],
        ['name' => 'company.update', 'module' => 'companies', 'description' => 'Update company profile'],
        ['name' => 'company.delete', 'module' => 'companies', 'description' => 'Delete or suspend company'],
        ['name' => 'company.verify', 'module' => 'companies', 'description' => 'Verify or reject company onboarding'],

        // Users
        ['name' => 'user.create', 'module' => 'users', 'description' => 'Create new user account'],
        ['name' => 'user.read', 'module' => 'users', 'description' => 'View user list and details'],
        ['name' => 'user.update', 'module' => 'users', 'description' => 'Update user details'],
        ['name' => 'user.delete', 'module' => 'users', 'description' => 'Deactivate or delete user'],

        // Requirements
        ['name' => 'requirement.create', 'module' => 'requirements', 'description' => 'Submit manpower requisition'],
        ['name' => 'requirement.read', 'module' => 'requirements', 'description' => 'View manpower requirements'],
        ['name' => 'requirement.update', 'module' => 'requirements', 'description' => 'Update manpower requirement'],
        ['name' => 'requirement.cancel', 'module' => 'requirements', 'description' => 'Cancel manpower requirement'],
        ['name' => 'requirement.approve', 'module' => 'requirements', 'description' => 'Approve or auto-approve requirement'],
        ['name' => 'requirement.reject', 'module' => 'requirements', 'description' => 'Reject manpower requirement'],
        ['name' => 'requirement.assign', 'module' => 'requirements', 'description' => 'Assign requirement to recruiters'],

        // Matching & Screening
        ['name' => 'candidate.match', 'module' => 'matching', 'description' => 'Execute candidate matching algorithm'],
        ['name' => 'candidate.screen', 'module' => 'matching', 'description' => 'Screen matched candidates'],
        ['name' => 'candidate.shortlist', 'module' => 'matching', 'description' => 'Shortlist candidates for interview'],

        // Interviews & Feedback
        ['name' => 'interview.schedule', 'module' => 'interviews', 'description' => 'Schedule candidate interview'],
        ['name' => 'interview.feedback', 'module' => 'interviews', 'description' => 'Submit interview evaluation feedback'],

        // Selection & Joining
        ['name' => 'selection.create', 'module' => 'selections', 'description' => 'Select candidate and define terms'],
        ['name' => 'offer.issue', 'module' => 'selections', 'description' => 'Issue offer letter'],
        ['name' => 'joining.verify', 'module' => 'joinings', 'description' => 'Verify candidate reporting and joining'],

        // System Governance & Reports
        ['name' => 'reports.view', 'module' => 'reports', 'description' => 'View analytics dashboards and reports'],
        ['name' => 'reports.export', 'module' => 'reports', 'description' => 'Export analytical reports'],
        ['name' => 'audit.view', 'module' => 'audit', 'description' => 'Inspect immutable audit logs'],
        ['name' => 'settings.manage', 'module' => 'settings', 'description' => 'Configure system settings']
    ];

    $permStmt = $pdo->prepare("INSERT INTO `permissions` (`name`, `module`, `description`) 
        VALUES (:name, :module, :description)
        ON DUPLICATE KEY UPDATE `module` = VALUES(`module`), `description` = VALUES(`description`)");

    foreach ($permissions as $perm) {
        $permStmt->execute($perm);
    }

    // Retrieve permission IDs
    $permMap = [];
    $rows = $pdo->query("SELECT id, name FROM `permissions`")->fetchAll();
    foreach ($rows as $row) {
        $permMap[$row['name']] = (int)$row['id'];
    }

    // 3. Role-Permission Mappings
    $rolePermStmt = $pdo->prepare("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES (:r_id, :p_id)");

    // Super Admin: All permissions
    if (isset($roleMap['super_admin'])) {
        $saId = $roleMap['super_admin'];
        foreach ($permMap as $pId) {
            $rolePermStmt->execute([':r_id' => $saId, ':p_id' => $pId]);
        }
    }

    // Industry Admin
    if (isset($roleMap['industry_admin'])) {
        $iaId = $roleMap['industry_admin'];
        $iaPerms = [
            'company.read', 'company.update',
            'requirement.create', 'requirement.read', 'requirement.update', 'requirement.cancel',
            'interview.feedback', 'joining.verify', 'reports.view'
        ];
        foreach ($iaPerms as $pName) {
            if (isset($permMap[$pName])) {
                $rolePermStmt->execute([':r_id' => $iaId, ':p_id' => $permMap[$pName]]);
            }
        }
    }

    // Recruitment Admin
    if (isset($roleMap['recruitment_admin'])) {
        $raId = $roleMap['recruitment_admin'];
        $raPerms = [
            'company.read', 'user.read', 'user.create', 'user.update',
            'requirement.read', 'requirement.approve', 'requirement.reject', 'requirement.assign',
            'candidate.match', 'candidate.screen', 'candidate.shortlist',
            'interview.schedule', 'interview.feedback',
            'selection.create', 'offer.issue', 'joining.verify',
            'reports.view', 'reports.export'
        ];
        foreach ($raPerms as $pName) {
            if (isset($permMap[$pName])) {
                $rolePermStmt->execute([':r_id' => $raId, ':p_id' => $permMap[$pName]]);
            }
        }
    }

    // Recruiter
    if (isset($roleMap['recruiter'])) {
        $recId = $roleMap['recruiter'];
        $recPerms = [
            'requirement.read',
            'candidate.match', 'candidate.screen', 'candidate.shortlist',
            'interview.schedule', 'interview.feedback',
            'selection.create', 'offer.issue', 'joining.verify',
            'reports.view'
        ];
        foreach ($recPerms as $pName) {
            if (isset($permMap[$pName])) {
                $rolePermStmt->execute([':r_id' => $recId, ':p_id' => $permMap[$pName]]);
            }
        }
    }

    echo "    Roles and Permissions seeded successfully." . PHP_EOL;
}

