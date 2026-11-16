<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['logged_in']);
$current_page = basename($_SERVER['PHP_SELF']);

$script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$in_student_subfolder = (strpos($script_path, '/student/') !== false || strpos($script_path, '/aptitude/') !== false || strpos($script_path, '/video/') !== false || strpos($script_path, '/admin/') !== false);
$rel_prefix = $in_student_subfolder ? "../" : "./";
$rel_site_prefix = $in_student_subfolder ? "../../" : "../";

$url_home = $rel_site_prefix . "index.html";
$url_about = $rel_site_prefix . "about.html";
$url_careers = $rel_site_prefix . "students.html";
$url_contact = $rel_site_prefix . "contact.html";

$url_portal_dashboard = $rel_prefix . "dashboard.php";
$url_portal_login = $rel_prefix . "login.php";
$url_portal_register = $rel_prefix . "register.php?source=website";
$url_portal_logout = $rel_prefix . "logout.php";
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<header class="prayas-unified-header">
    <div class="nav-container">
        <a href="<?php echo $url_home; ?>" class="prayas-logo-link">
            <img src="<?php echo $rel_prefix; ?>assets/images/prayas-logo.png" alt="PRAYAS Logo" class="prayas-logo-img" onerror="this.onerror=null; this.src='<?php echo $rel_site_prefix; ?>images/logo-mark.png';">
            <div class="logo-text-group">
                <span class="logo-title">PRAYAS</span>
                <span class="logo-sub">Building India's Manufacturing Workforce</span>
            </div>
        </a>

        <button class="nav-hamburger" id="navHamburger" aria-label="Toggle Navigation">
            <span></span><span></span><span></span>
        </button>

        <nav class="portal-nav-links" id="portalNavLinks">
            <a href="<?php echo $url_home; ?>">Home</a>
            <a href="<?php echo $url_about; ?>">About</a>
            <a href="<?php echo $url_careers; ?>">Careers / Apply</a>
            <a href="<?php echo $url_portal_dashboard; ?>" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">Student Portal</a>
            <?php if ($is_logged_in): ?>
                <a href="<?php echo $url_portal_dashboard; ?>" class="nav-user-badge"><i class="fa-solid fa-user-circle"></i> Dashboard</a>
                <a href="<?php echo $url_portal_logout; ?>" class="btn-nav-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            <?php else: ?>
                <a href="<?php echo $url_portal_login; ?>" class="<?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">Login</a>
                <a href="<?php echo $url_portal_register; ?>" class="btn-nav-apply <?php echo ($current_page == 'register.php') ? 'active' : ''; ?>">Register</a>
            <?php endif; ?>
            <a href="<?php echo $url_contact; ?>">Contact</a>
        </nav>
    </div>
</header>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.getElementById('navHamburger');
    const navLinks = document.getElementById('portalNavLinks');
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', function() {
            navLinks.classList.toggle('nav-open');
            hamburger.classList.toggle('active');
        });
    }
});
</script>
