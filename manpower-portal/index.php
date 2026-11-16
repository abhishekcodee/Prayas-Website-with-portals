<?php
/**
 * Application Gateway Entry Point — Portable Router
 * Organization: PRAYAS – The Automation Academy
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/permissions.php';

if (isAuthenticated()) {
    $role = getRoleName();
    $target = match ($role) {
        'super_admin'       => '/views/super_admin/dashboard.php',
        'industry_admin'    => '/views/industry/dashboard.php',
        'recruitment_admin' => '/views/recruitment_admin/dashboard.php',
        'recruiter'         => '/views/recruiter/dashboard.php',
        default             => '/views/auth/login.php'
    };
    header("Location: " . app_url($target));
    exit;
}

// Redirect unauthenticated visitors to login
header("Location: " . app_url('/views/auth/login.php'));
exit;
