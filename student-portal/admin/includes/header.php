<header class="admin-header">

    <div class="header-left">

        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle navigation menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>

        <div class="page-heading">
            <h1><?= $pageTitle ?? 'Dashboard'; ?></h1>
            <p>Student Recruitment Portal</p>
        </div>

    </div>

    <div class="header-right">

        <div class="admin-profile">

            <div class="admin-avatar">
                <?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)); ?>
                <span class="avatar-status-online" title="Online"></span>
            </div>

            <div class="admin-info">
                <strong>
                    <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator'); ?>
                </strong>

                <span>Administrator</span>
            </div>

        </div>

    </div>

</header>