<?php
/**
 * Dynamic Role-Aware Sidebar Navigation — Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/permissions.php';

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$role = getRoleName();

function isActiveNav(string $path, string $currentUri): string {
    return str_contains($currentUri, $path) ? 'active' : '';
}
?>
<!-- Sidebar Backdrop for Mobile -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Left Sidebar Navigation -->
<nav id="sidebar">
    <div class="brand-header d-flex align-items-center justify-content-between">
        <a href="<?= app_url('/index.php') ?>" class="d-flex align-items-center text-decoration-none">
            <img src="<?= app_url('/assets/images/logo.png') ?>" alt="PRAYAS" class="me-2">
            <div class="brand-title">
                <div class="fw-bold text-white small" style="letter-spacing: 0.5px;">PRAYAS</div>
                <div style="font-size: 0.68rem; color: var(--primary);">Automation Academy</div>
            </div>
        </a>
        <button type="button" class="btn btn-sm btn-outline-light d-lg-none" id="sidebarClose" aria-label="Close Sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="py-2">
        <?php if ($role === 'super_admin'): ?>
            <!-- ================= SUPER ADMIN NAVIGATION ================= -->
            <div class="nav-label">Core Navigation</div>
            <a href="<?= app_url('/views/super_admin/dashboard.php') ?>" class="nav-link <?= isActiveNav('/views/super_admin/dashboard.php', $currentUri) ?>" title="Dashboard">
                <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
            </a>

            <div class="nav-label">Company & Industry Governance</div>
            <a href="<?= app_url('/views/super_admin/companies.php') ?>" class="nav-link <?= ($currentUri === app_url('/views/super_admin/companies.php')) ? 'active' : '' ?>" title="All Companies">
                <i class="bi bi-buildings"></i> <span>Company Management</span>
            </a>
            <a href="<?= app_url('/views/super_admin/industries.php') ?>" class="nav-link <?= ($currentUri === app_url('/views/super_admin/industries.php')) ? 'active' : '' ?>" title="Industry Management">
                <i class="bi bi-building-gear"></i> <span>Industry Management</span>
            </a>
            <a href="<?= app_url('/views/super_admin/companies.php?status=Pending') ?>" class="nav-link <?= str_contains($currentUri, 'status=Pending') ? 'active' : '' ?>" title="Company Verification">
                <i class="bi bi-hourglass-split"></i> <span>Company Verification</span>
            </a>
            <a href="<?= app_url('/views/super_admin/company_limits.php') ?>" class="nav-link <?= isActiveNav('/views/super_admin/company_limits.php', $currentUri) ?>" title="Company Limits">
                <i class="bi bi-sliders"></i> <span>Company Limits</span>
            </a>
            <a href="<?= app_url('/views/super_admin/company_form.php') ?>" class="nav-link <?= isActiveNav('/views/super_admin/company_form.php', $currentUri) ?>" title="Add Company">
                <i class="bi bi-plus-circle"></i> <span>Add Company</span>
            </a>

            <div class="nav-label">User Administration</div>
            <a href="<?= app_url('/views/super_admin/users.php?role=industry_admin') ?>" class="nav-link <?= str_contains($currentUri, 'role=industry_admin') ? 'active' : '' ?>" title="Company Administrators">
                <i class="bi bi-person-badge"></i> <span>Company Administrators</span>
            </a>
            <a href="<?= app_url('/views/super_admin/users.php') ?>" class="nav-link <?= ($currentUri === app_url('/views/super_admin/users.php')) ? 'active' : '' ?>" title="All Users">
                <i class="bi bi-people"></i> <span>All Users</span>
            </a>
            <a href="<?= app_url('/views/super_admin/user_form.php') ?>" class="nav-link <?= isActiveNav('/views/super_admin/user_form.php', $currentUri) ?>" title="Add User">
                <i class="bi bi-person-plus"></i> <span>Add User</span>
            </a>
            <a href="<?= app_url('/views/super_admin/roles_permissions.php') ?>" class="nav-link <?= isActiveNav('/views/super_admin/roles_permissions.php', $currentUri) ?>" title="Roles & Permissions">
                <i class="bi bi-shield-lock"></i> <span>Roles & Permissions</span>
            </a>

            <div class="nav-label">Manpower Demand & Talent</div>
            <a href="<?= app_url('/views/super_admin/requirements.php') ?>" class="nav-link <?= ($currentUri === app_url('/views/super_admin/requirements.php')) ? 'active' : '' ?>" title="All Requirements">
                <i class="bi bi-briefcase"></i> <span>All Requirements</span>
            </a>
            <a href="<?= app_url('/views/recruiter/candidate_matching.php') ?>" class="nav-link <?= isActiveNav('/views/recruiter/candidate_matching.php', $currentUri) ?>" title="Candidate Matching">
                <i class="bi bi-cpu"></i> <span>Candidate Matching</span>
            </a>
            <a href="<?= app_url('/views/recruiter/recruitment_pipeline.php') ?>" class="nav-link <?= isActiveNav('/views/recruiter/recruitment_pipeline.php', $currentUri) ?>" title="Recruitment Pipeline">
                <i class="bi bi-diagram-3"></i> <span>Recruitment Pipeline</span>
            </a>

            <div class="nav-label">Reports & Analytics</div>
            <a href="<?= app_url('/views/super_admin/reports.php?report=executive') ?>" class="nav-link <?= (str_contains($currentUri, 'reports.php') && (str_contains($currentUri, 'report=executive') || !str_contains($currentUri, 'report='))) ? 'active' : '' ?>" title="Executive Analytics">
                <i class="bi bi-bar-chart-line"></i> <span>Executive Reports</span>
            </a>
            <a href="<?= app_url('/views/super_admin/reports.php?report=company_requirements') ?>" class="nav-link <?= str_contains($currentUri, 'report=company_requirements') ? 'active' : '' ?>" title="Industry Reports">
                <i class="bi bi-graph-up"></i> <span>Industry Reports</span>
            </a>

            <div class="nav-label">System & Audit</div>
            <a href="<?= app_url('/views/super_admin/notifications.php') ?>" class="nav-link <?= isActiveNav('/views/super_admin/notifications.php', $currentUri) ?>" title="Notifications">
                <i class="bi bi-bell"></i> <span>Notifications</span>
            </a>
            <a href="<?= app_url('/views/super_admin/system_settings.php') ?>" class="nav-link <?= isActiveNav('/views/super_admin/system_settings.php', $currentUri) ?>" title="System Settings">
                <i class="bi bi-sliders"></i> <span>Settings</span>
            </a>
            <a href="<?= app_url('/views/super_admin/audit_logs.php') ?>" class="nav-link <?= isActiveNav('/views/super_admin/audit_logs.php', $currentUri) ?>" title="Audit Logs">
                <i class="bi bi-journal-text"></i> <span>Audit Logs</span>
            </a>

        <?php elseif ($role === 'recruiter' || $role === 'recruitment_admin'): ?>
            <!-- ================= RECRUITER NAVIGATION ================= -->
            <div class="nav-label">Core Navigation</div>
            <a href="<?= app_url('/views/super_admin/dashboard.php') ?>" class="nav-link <?= isActiveNav('/views/super_admin/dashboard.php', $currentUri) ?>" title="Dashboard">
                <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
            </a>

            <div class="nav-label">Sourcing & Pipeline</div>
            <a href="<?= app_url('/views/recruiter/candidate_matching.php') ?>" class="nav-link <?= isActiveNav('/views/recruiter/candidate_matching.php', $currentUri) ?>" title="Candidate Matching">
                <i class="bi bi-cpu"></i> <span>Candidate Matching</span>
            </a>
            <a href="<?= app_url('/views/recruiter/recruitment_pipeline.php') ?>" class="nav-link <?= isActiveNav('/views/recruiter/recruitment_pipeline.php', $currentUri) ?>" title="Recruitment Pipeline">
                <i class="bi bi-diagram-3"></i> <span>Recruitment Pipeline</span>
            </a>
            <a href="<?= app_url('/views/recruiter/recruitment_reports.php') ?>" class="nav-link <?= isActiveNav('/views/recruiter/recruitment_reports.php', $currentUri) ?>" title="Pipeline Analytics">
                <i class="bi bi-graph-up-arrow"></i> <span>Pipeline Analytics</span>
            </a>
            <a href="<?= app_url('/views/super_admin/requirements.php') ?>" class="nav-link <?= ($currentUri === app_url('/views/super_admin/requirements.php')) ? 'active' : '' ?>" title="Requisitions Queue">
                <i class="bi bi-briefcase"></i> <span>Requisitions Queue</span>
            </a>

            <div class="nav-label">System</div>
            <a href="<?= app_url('/views/super_admin/notifications.php') ?>" class="nav-link <?= isActiveNav('/views/super_admin/notifications.php', $currentUri) ?>" title="Notifications">
                <i class="bi bi-bell"></i> <span>Notifications</span>
            </a>

        <?php elseif ($role === 'industry_admin' || $role === 'industry_user'): ?>
            <!-- ================= INDUSTRY PARTNER NAVIGATION ================= -->
            <div class="nav-label">Core Navigation</div>
            <a href="<?= app_url('/views/industry/dashboard.php') ?>" class="nav-link <?= isActiveNav('/views/industry/dashboard.php', $currentUri) ?>" title="Dashboard">
                <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
            </a>

            <div class="nav-label">Company & Team</div>
            <a href="<?= app_url('/views/industry/company_profile.php') ?>" class="nav-link <?= isActiveNav('/views/industry/company_profile.php', $currentUri) ?>" title="Company Profile">
                <i class="bi bi-building"></i> <span>Company Profile</span>
            </a>
            <a href="<?= app_url('/views/industry/plants.php') ?>" class="nav-link <?= isActiveNav('/views/industry/plants.php', $currentUri) ?>" title="Manufacturing Plants">
                <i class="bi bi-geo-alt"></i> <span>Manufacturing Plants</span>
            </a>
            <?php if ($role === 'industry_admin'): ?>
            <a href="<?= app_url('/views/industry/team_members.php') ?>" class="nav-link <?= isActiveNav('/views/industry/team_members.php', $currentUri) ?>" title="Team Members">
                <i class="bi bi-people"></i> <span>Team Management</span>
            </a>
            <?php endif; ?>

            <div class="nav-label">Manpower Requirements</div>
            <a href="<?= app_url('/views/industry/requirement_form.php') ?>" class="nav-link <?= isActiveNav('/views/industry/requirement_form.php', $currentUri) ?>" title="Create Requirement">
                <i class="bi bi-file-earmark-plus"></i> <span>New Requisition</span>
            </a>
            <a href="<?= app_url('/views/industry/requirements.php') ?>" class="nav-link <?= ($currentUri === app_url('/views/industry/requirements.php')) ? 'active' : '' ?>" title="My Requirements">
                <i class="bi bi-card-checklist"></i> <span>All Requirements</span>
            </a>
            <a href="<?= app_url('/views/industry/requirements.php?status=APPROVED') ?>" class="nav-link <?= str_contains($currentUri, 'status=APPROVED') ? 'active' : '' ?>" title="Active Requisitions">
                <i class="bi bi-activity"></i> <span>Requirement Tracking</span>
            </a>

            <?php if (false): // Candidate Management hidden per client request ?>
            <div class="nav-label">Candidate Management</div>
            <a href="<?= app_url('/views/industry/recruitment_pipeline.php') ?>" class="nav-link <?= isActiveNav('/views/industry/recruitment_pipeline.php', $currentUri) ?>" title="Recruitment Pipeline">
                <i class="bi bi-diagram-3"></i> <span>Recruitment Pipeline</span>
            </a>
            <a href="<?= app_url('/views/industry/candidate_matches.php') ?>" class="nav-link <?= ($currentUri === app_url('/views/industry/candidate_matches.php')) ? 'active' : '' ?>" title="Candidate Matches">
                <i class="bi bi-person-check"></i> <span>Candidate Matches</span>
            </a>
            <a href="<?= app_url('/views/industry/candidate_matches.php?status=Shortlisted') ?>" class="nav-link <?= str_contains($currentUri, 'status=Shortlisted') ? 'active' : '' ?>" title="Shortlisted Candidates">
                <i class="bi bi-bookmark-check"></i> <span>Shortlisted</span>
            </a>
            <a href="<?= app_url('/views/industry/candidate_matches.php?status=Scheduled') ?>" class="nav-link <?= str_contains($currentUri, 'status=Scheduled') ? 'active' : '' ?>" title="Interview Status">
                <i class="bi bi-calendar-event"></i> <span>Interviews</span>
            </a>
            <a href="<?= app_url('/views/industry/candidate_matches.php?status=Selected') ?>" class="nav-link <?= str_contains($currentUri, 'status=Selected') ? 'active' : '' ?>" title="Selection Status">
                <i class="bi bi-award"></i> <span>Selections</span>
            </a>
            <a href="<?= app_url('/views/industry/candidate_matches.php?status=Joined') ?>" class="nav-link <?= str_contains($currentUri, 'status=Joined') ? 'active' : '' ?>" title="Joining Status">
                <i class="bi bi-person-fill-check"></i> <span>Joinings</span>
            </a>
            <?php endif; ?>

            <div class="nav-label">System & Reports</div>
            <a href="<?= app_url('/views/industry/reports.php') ?>" class="nav-link <?= isActiveNav('/views/industry/reports.php', $currentUri) ?>" title="Reports & Analytics">
                <i class="bi bi-bar-chart-line"></i> <span>Reports & Analytics</span>
            </a>
            <a href="<?= app_url('/views/industry/notifications.php') ?>" class="nav-link <?= isActiveNav('/views/industry/notifications.php', $currentUri) ?>" title="Notifications">
                <i class="bi bi-bell"></i> <span>Notifications</span>
            </a>
        <?php endif; ?>

        <!-- Shared Account Options -->
        <div class="nav-label">Account</div>
        <a href="<?= app_url('/views/industry/my_profile.php') ?>" class="nav-link <?= isActiveNav('/views/industry/my_profile.php', $currentUri) ?>" title="My Profile">
            <i class="bi bi-person-circle"></i> <span>My Profile</span>
        </a>
        <a href="<?= app_url('/views/auth/change_password.php') ?>" class="nav-link <?= isActiveNav('/views/auth/change_password.php', $currentUri) ?>" title="Change Password">
            <i class="bi bi-key"></i> <span>Change Password</span>
        </a>
        <a href="<?= app_url('/views/auth/logout.php') ?>" class="nav-link text-danger" title="Sign Out">
            <i class="bi bi-box-arrow-right text-danger"></i> <span>Sign Out</span>
        </a>
    </div>
</nav>
