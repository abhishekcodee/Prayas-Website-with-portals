<?php
// Calculate relative path to site root dynamically
$current_script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$script_dir = trim(dirname($current_script), '/.');
if ($script_dir === '') {
    $site_root = './';
} else {
    $depth = count(explode('/', $script_dir));
    $site_root = str_repeat('../', $depth);
}

$url_home = $site_root . 'index.html';
$url_about = $site_root . 'about.html';
$url_students = $site_root . 'students.html';
$url_employers = $site_root . 'employers.html';
$url_resources = $site_root . 'resources.html';
$url_contact = $site_root . 'contact.html';
$url_manpower = $site_root . 'manpower-portal/';
$url_student_portal = $site_root . 'student-portal/';
$url_logo = $site_root . 'images/logo.png';
$url_student_login = $site_root . 'student-portal/login.php';
$url_student_register = $site_root . 'student-portal/register.php';
$url_student_dashboard = $site_root . 'student-portal/dashboard.php';
$url_student_logout = $site_root . 'student-portal/logout.php';

$is_student_logged_in = !empty($_SESSION['user_id']) || !empty($_SESSION['logged_in']);
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700;800&family=Noto+Sans+Devanagari:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="<?= $site_root ?>assets/i18n/i18n.css">

<style>
header.website-header {
  position: sticky;
  top: 0;
  left: 0;
  right: 0;
  width: 100%;
  z-index: 9999;
  background: rgba(251, 250, 247, 0.96) !important;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-bottom: 1px solid #e4ded2 !important;
  font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
  box-sizing: border-box;
}
header.website-header .nav-row {
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  padding: 15px 24px !important;
  max-width: 1200px !important;
  margin: 0 auto !important;
  box-sizing: border-box !important;
}
header.website-header .logo {
  display: inline-flex !important;
  align-items: center !important;
  text-decoration: none !important;
}
header.website-header .logo img {
  height: 56px !important;
  width: auto !important;
  display: block !important;
  object-fit: contain !important;
}
@media (max-width: 768px) {
  header.website-header .logo img { height: 48px !important; }
}
@media (max-width: 540px) {
  header.website-header .nav-row { padding: 13px 18px !important; }
  header.website-header .logo img { height: 42px !important; }
}
header.website-header nav.desktop-nav {
  display: flex !important;
  gap: 32px !important;
  font-weight: 600 !important;
  font-size: 14.5px !important;
  align-items: center !important;
  margin: 0 !important;
  padding: 0 !important;
}
header.website-header nav.desktop-nav a {
  color: #0b1220 !important;
  text-decoration: none !important;
  transition: color .15s ease !important;
  white-space: nowrap !important;
}
header.website-header nav.desktop-nav a:hover,
header.website-header nav.desktop-nav a.active {
  color: #e8622c !important;
}
header.website-header .dd {
  position: relative !important;
  display: inline-flex !important;
  align-items: center !important;
}
header.website-header .dd > div {
  display: none;
  position: absolute !important;
  top: 100% !important;
  left: 0 !important;
  background: #ffffff !important;
  min-width: 180px !important;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
  border-radius: 8px !important;
  padding: 8px 0 !important;
  z-index: 10001 !important;
  border: 1px solid #e4ded2 !important;
}
header.website-header .dd:hover > div {
  display: flex !important;
  flex-direction: column !important;
}
header.website-header .dd > div a {
  padding: 8px 16px !important;
  font-size: 13.5px !important;
  color: #0b1220 !important;
  text-decoration: none !important;
  display: block !important;
}
header.website-header .dd > div a:hover {
  background: #f1ede4 !important;
  color: #e8622c !important;
}
header.website-header .nav-ctas {
  display: flex !important;
  gap: 10px !important;
  align-items: center !important;
}
header.website-header .nav-ctas .btn {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 8px !important;
  border-radius: 999px !important;
  font-weight: 700 !important;
  font-size: 13px !important;
  cursor: pointer !important;
  white-space: nowrap !important;
  text-decoration: none !important;
  padding: 9px 16px !important;
  line-height: 1.2 !important;
  transition: transform .15s ease, box-shadow .15s ease !important;
  box-sizing: border-box !important;
}
header.website-header .nav-ctas .btn:hover {
  transform: translateY(-1px) !important;
}
header.website-header .nav-ctas .btn-primary {
  background: #e8622c !important;
  color: #ffffff !important;
  border: none !important;
  box-shadow: 0 4px 14px rgba(232, 98, 44, 0.35) !important;
}
header.website-header .nav-ctas .btn-secondary {
  background: transparent !important;
  color: #0f4c81 !important;
  border: 1.5px solid #0f4c81 !important;
}
header.website-header .nav-ctas .btn-secondary:hover {
  background: rgba(15, 76, 129, 0.05) !important;
}
header.website-header .hamburger {
  display: none;
  flex-direction: column !important;
  gap: 5px !important;
  background: none !important;
  border: none !important;
  cursor: pointer !important;
  padding: 6px !important;
}
header.website-header .hamburger span {
  width: 24px !important;
  height: 2.5px !important;
  background: #0b1220 !important;
  border-radius: 2px !important;
  display: block !important;
}
@media (max-width: 900px) {
  header.website-header nav.desktop-nav { display: none !important; }
  header.website-header .hamburger { display: flex !important; }
}
header.website-header .mobile-menu {
  display: none;
  flex-direction: column !important;
  background: #fbfaf7 !important;
  border-bottom: 1px solid #e4ded2 !important;
  padding: 8px 24px 20px !important;
}
header.website-header .mobile-menu.open {
  display: flex !important;
}
header.website-header .mobile-menu a {
  padding: 12px 4px !important;
  font-weight: 600 !important;
  color: #0b1220 !important;
  text-decoration: none !important;
  border-bottom: 1px solid #e4ded2 !important;
}
</style>

<header class="website-header">
  <div class="nav-row">
    <a href="<?= $url_home ?>" class="logo">
      <img src="<?= $url_logo ?>" alt="PRAYAS Logo">
    </a>
    <nav class="desktop-nav">
      <a href="<?= $url_home ?>" data-i18n="k003">Home</a>
      <span class="dd">
        <a href="<?= $url_about ?>" data-i18n="k004">About Us &#9662;</a>
        <div>
          <a href="<?= $url_about ?>#about-why" data-i18n="k005">Why PRAYAS?</a>
          <a href="<?= $url_about ?>#about-vision" data-i18n="k006">Vision &amp; Purpose</a>
          <a href="<?= $url_about ?>#about-mission" data-i18n="k007">Mission &amp; Impact</a>
          <a href="<?= $url_about ?>#about-collaboration" data-i18n="k008">Collaboration</a>
          <a href="<?= $url_about ?>#about-career" data-i18n="k009">Career</a>
          <a href="<?= $url_contact ?>" data-i18n="k010">Contact Us</a>
        </div>
      </span>
      <a href="<?= $url_students ?>" data-i18n="k011">Student</a>
      <a href="<?= $url_employers ?>" data-i18n="k012">MSME</a>
      <span class="dd">
        <a href="<?= $url_resources ?>" data-i18n="k013">Resources &#9662;</a>
        <div>
          <a href="<?= $url_resources ?>#vlog" data-i18n="k014">Vlog</a>
          <a href="<?= $url_resources ?>#blog" data-i18n="k015">Blog</a>
          <a href="<?= $url_resources ?>#study" data-i18n="k016">Study Material</a>
          <a href="<?= $url_resources ?>#events" data-i18n="k017">Events</a>
          <a href="<?= $url_resources ?>#gallery" data-i18n="k018">Gallery</a>
        </div>
      </span>
      <a href="<?= $url_contact ?>" data-i18n="k019">Contact</a>
    </nav>
    <div class="nav-ctas">
      <?php if (!empty($is_student_logged_in) && basename($_SERVER['PHP_SELF'] ?? '') === 'dashboard.php'): ?>
        <?php if (!empty($user['full_name'])): ?>
          <div style="display:inline-flex;align-items:center;gap:8px;padding:6px 14px;background:#f1ede4;border-radius:999px;font-size:12.5px;font-weight:600;color:#0b1220;border:1px solid #e4ded2;">
            <i class="fa-solid fa-circle-user" style="color:#e8622c;font-size:16px;"></i>
            <span><?= htmlspecialchars($user['full_name']) ?></span>
          </div>
        <?php endif; ?>
        <a href="<?= $url_student_logout ?>" class="btn btn-primary btn-small" title="Sign Out"><i class="fa-solid fa-right-from-bracket"></i> <span data-i18n="portal.logout">Logout</span></a>
      <?php else: ?>
        <a href="<?= $url_manpower ?>" class="btn btn-secondary btn-small" data-i18n="k020">Hire Talent</a>
        <a href="<?= $url_student_portal ?>" class="btn btn-primary btn-small" data-i18n="k021">Enroll Now</a>
      <?php endif; ?>
      <div class="lang-switcher" data-i18n-switcher><button type="button" class="lang-btn" aria-haspopup="listbox" aria-expanded="false" aria-label="Language: English"><svg class="lang-globe" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg><span class="lang-current">EN</span><span class="lang-caret" aria-hidden="true">▾</span></button><div class="lang-menu" role="listbox" aria-label="Select language"><button type="button" class="lang-option" role="option" data-lang="en" aria-selected="true"><span>English</span><span class="lang-code">EN</span></button><button type="button" class="lang-option" role="option" data-lang="hi" aria-selected="false"><span>हिंदी</span><span class="lang-code">HI</span></button><button type="button" class="lang-option" role="option" data-lang="mr" aria-selected="false"><span>मराठी</span><span class="lang-code">MR</span></button></div></div>
      <button class="hamburger" onclick="const m = document.getElementById('student-mmenu'); if (m) m.classList.toggle('open');" aria-label="Menu" data-i18n-aria="k022">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
  <div class="mobile-menu" id="student-mmenu">
    <a href="<?= $url_home ?>" data-i18n="k003">Home</a>
    <a href="<?= $url_about ?>" data-i18n="k023">About Us</a>
    <a href="<?= $url_students ?>" data-i18n="k011">Student</a>
    <a href="<?= $url_employers ?>" data-i18n="k012">MSME</a>
    <a href="<?= $url_resources ?>" data-i18n="k024">Resources</a>
    <a href="<?= $url_contact ?>" data-i18n="k019">Contact</a>
    <?php if (!empty($is_student_logged_in)): ?>
      <a href="<?= $url_student_dashboard ?>" style="color:#e8622c;font-weight:700">Dashboard</a>
      <a href="<?= $url_student_logout ?>" style="color:#dc2626;font-weight:700" data-i18n="portal.logout">Logout</a>
    <?php else: ?>
      <a href="<?= $url_student_login ?>" style="color:#e8622c;font-weight:700" data-i18n="k025">Student Portal Login</a>
      <a href="<?= $url_student_register ?>" style="color:#0f4c81;font-weight:700">Student Register</a>
    <?php endif; ?>
  </div>
</header>
<script src="<?= $site_root ?>assets/i18n/en.js"></script>
<script src="<?= $site_root ?>assets/i18n/hi.js"></script>
<script src="<?= $site_root ?>assets/i18n/mr.js"></script>
<script src="<?= $site_root ?>assets/i18n/i18n.js"></script>
