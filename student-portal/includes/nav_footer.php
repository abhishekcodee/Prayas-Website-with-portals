<?php
$script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$in_student_subfolder = (strpos($script_path, '/student/') !== false || strpos($script_path, '/aptitude/') !== false || strpos($script_path, '/video/') !== false || strpos($script_path, '/admin/') !== false);
$rel_site_prefix = $in_student_subfolder ? "../../" : "../";
?>
<footer class="prayas-unified-footer">
    <div class="footer-container">
        <div class="footer-col brand-col">
            <div class="footer-logo">
                <span class="logo-title">PRAYAS</span>
                <span class="logo-sub">Building India's Manufacturing Workforce</span>
            </div>
            <p>Transforming ITI, diploma and engineering graduates into industry-ready shop-floor technicians.</p>
        </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="<?php echo $rel_site_prefix; ?>index.html">Home</a></li>
                <li><a href="<?php echo $rel_site_prefix; ?>about.html">About Us</a></li>
                <li><a href="<?php echo $rel_site_prefix; ?>students.html">For Students</a></li>
                <li><a href="<?php echo $rel_site_prefix; ?>employers.html">For Employers</a></li>
                <li><a href="<?php echo $rel_site_prefix; ?>services.html">Services & Pathways</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Student Portal</h4>
            <ul>
                <li><a href="<?php echo $in_student_subfolder ? '../' : './'; ?>register.php?source=website">Student Registration</a></li>
                <li><a href="<?php echo $in_student_subfolder ? '../' : './'; ?>login.php">Candidate Login</a></li>
                <li><a href="<?php echo $in_student_subfolder ? '../' : './'; ?>dashboard.php">Student Dashboard</a></li>
                <li><a href="<?php echo $rel_site_prefix; ?>terms-and-conditions.html">Terms & Conditions</a></li>
                <li><a href="<?php echo $rel_site_prefix; ?>privacy-policy.html">Privacy Policy</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Contact Support</h4>
            <p><i class="fa-solid fa-location-dot"></i> PRAYAS - The Automation Academy</p>
            <p><i class="fa-solid fa-envelope"></i> support@prayasacademy.com</p>
            <p><i class="fa-solid fa-phone"></i> +91 98765 43210</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> PRAYAS — The Automation Academy. All Rights Reserved.</p>
    </div>
</footer>
