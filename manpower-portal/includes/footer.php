<?php
/**
 * Shared Footer Partial — Modern Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 */
?>
    <footer class="mt-auto py-3 bg-white border-top text-center text-muted small">
        <div class="container-fluid">
            &copy; <?= date('Y') ?> <strong>PRAYAS – The Automation Academy</strong>. Industry Manpower Requirement Portal. All Rights Reserved.
        </div>
    </footer>
</div> <!-- /#main-content -->
</div> <!-- /.app-layout -->

<!-- Bootstrap 5.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Sidebar Collapse & Responsive Layout Controller -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const mobileToggleBtn = document.getElementById('sidebarToggle');
        const mobileCloseBtn = document.getElementById('sidebarClose');
        const desktopCollapseBtn = document.getElementById('sidebarCollapseBtn');
        const body = document.body;

        // Restore desktop collapsed preference
        if (localStorage.getItem('prayas_sidebar_collapsed') === 'true' && window.innerWidth >= 992) {
            sidebar?.classList.add('collapsed');
            body.classList.add('sidebar-collapsed');
        }

        // Desktop collapse toggle
        if (desktopCollapseBtn && sidebar) {
            desktopCollapseBtn.addEventListener('click', function () {
                sidebar.classList.toggle('collapsed');
                body.classList.toggle('sidebar-collapsed');
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('prayas_sidebar_collapsed', isCollapsed);
            });
        }

        // Mobile drawer open
        if (mobileToggleBtn && sidebar) {
            mobileToggleBtn.addEventListener('click', function () {
                sidebar.classList.add('show');
                backdrop?.classList.add('show');
            });
        }

        // Mobile drawer close
        const closeMobileSidebar = function () {
            sidebar?.classList.remove('show');
            backdrop?.classList.remove('show');
        };

        if (mobileCloseBtn) {
            mobileCloseBtn.addEventListener('click', closeMobileSidebar);
        }
        if (backdrop) {
            backdrop.addEventListener('click', closeMobileSidebar);
        }

        // Initialize Bootstrap tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    });
</script>
</body>
</html>
