<?php

$adminBase = "/student-portal/admin";
$siteBase  = "/student-portal";

$currentPage = $_SERVER['PHP_SELF'];

?>

<aside class="admin-sidebar">

    <!-- =========================
         BRAND
    ========================== -->

    <div class="sidebar-brand">

        <div class="brand-logo">

            <img
                src="../../assets/images/prayas-logo.png"
                alt="PRAYAS">

        </div>

        <div class="brand-text">

            <h2>PRAYAS</h2>

            <span>Admin Console</span>

        </div>

    </div>


    <!-- =========================
         NAVIGATION
    ========================== -->

    <nav class="sidebar-nav">


        <!-- OVERVIEW -->

        <div class="nav-label">
            OVERVIEW
        </div>


        <a
            href="<?= $adminBase ?>/dashboard.php"

            class="nav-item
            <?= strpos(
                $currentPage,
                '/admin/dashboard.php'
            ) !== false
                ? 'active'
                : ''
            ?>">

            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </span>

            <span>
                Dashboard
            </span>

        </a>


        <!-- =========================
             RECRUITMENT
        ========================== -->

        <div class="nav-label">
            RECRUITMENT
        </div>


        <!-- CANDIDATES -->

        <a
            href="<?= $adminBase ?>/candidates/index.php"

            class="nav-item
            <?= strpos(
                $currentPage,
                '/admin/candidates/'
            ) !== false
                ? 'active'
                : ''
            ?>">

            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>

            <span>
                Candidates
            </span>

        </a>


        <!-- SHORTLISTED -->

        <a
            href="<?= $adminBase ?>/recruitment/shortlisted.php"

            class="nav-item
            <?= strpos(
                $currentPage,
                '/recruitment/shortlisted.php'
            ) !== false
                ? 'active'
                : ''
            ?>">

            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </span>

            <span>
                Shortlisted
            </span>

        </a>


        <!-- SELECTED -->

        <a
            href="<?= $adminBase ?>/recruitment/selected.php"

            class="nav-item
            <?= strpos(
                $currentPage,
                '/recruitment/selected.php'
            ) !== false
                ? 'active'
                : ''
            ?>">

            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            </span>

            <span>
                Selected
            </span>

        </a>


        <!-- REJECTED -->

        <a
            href="<?= $adminBase ?>/recruitment/rejected.php"

            class="nav-item
            <?= strpos(
                $currentPage,
                '/recruitment/rejected.php'
            ) !== false
                ? 'active'
                : ''
            ?>">

            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </span>

            <span>
                Rejected
            </span>

        </a>


        <!-- =========================
             ASSESSMENT
        ========================== -->

        <div class="nav-label">
            ASSESSMENT
        </div>


        <!-- APTITUDE RESULTS -->

        <a
            href="<?= $adminBase ?>/aptitude/results.php"

            class="nav-item
            <?= strpos(
                $currentPage,
                '/aptitude/results.php'
            ) !== false
                ? 'active'
                : ''
            ?>">

            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            </span>

            <span>
                Aptitude Results
            </span>

        </a>


        <!-- QUESTION BANK -->

        <a
            href="<?= $adminBase ?>/aptitude/questions.php"

            class="nav-item
            <?= strpos(
                $currentPage,
                '/aptitude/questions.php'
            ) !== false
                ? 'active'
                : ''
            ?>">

            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </span>

            <span>
                Question Bank
            </span>

        </a>


        <!-- =========================
             SYSTEM
        ========================== -->

        <div class="nav-label">
            SYSTEM
        </div>


        <!-- REPORTS -->

        <a
            href="<?= $adminBase ?>/reports/export.php"

            class="nav-item
            <?= strpos(
                $currentPage,
                '/admin/reports/'
            ) !== false
                ? 'active'
                : ''
            ?>">

            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            </span>

            <span>
                Reports
            </span>

        </a>


        <!-- LOGOUT -->

        <a
            href="<?= $adminBase ?>/logout.php"
            class="nav-item logout-link">

            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </span>

            <span>
                Logout
            </span>

        </a>


    </nav>

</aside>