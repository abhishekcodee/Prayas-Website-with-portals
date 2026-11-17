<?php
session_start();

require_once "db.php";
require_once "config.php";
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";
$is_google_error = false;
$google_attempted_email = "";

if (isset($_SESSION['google_error'])) {
    $message = $_SESSION['google_error'];
    $is_google_error = true;
    unset($_SESSION['google_error']);
}

if (isset($_SESSION['google_attempted_email'])) {
    $google_attempted_email = $_SESSION['google_attempted_email'];
    unset($_SESSION['google_attempted_email']);
}

if (isset($_POST['register'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);

    // Validation
    if (empty($name) || empty($email) || empty($mobile)) {
        $message = "All fields are required!";
    } else {

        // Check Email and Mobile
        $check = $pdo->prepare("SELECT id FROM users WHERE email=? OR mobile=?");
        $check->execute([$email, $mobile]);

        if ($check->rowCount() > 0) {
            $message = "Email or Mobile already exists.";
        } else {

            // Registration ID
            $registration_no = "SP" . date("Y") . rand(1000, 9999);

            // Generate OTP
            $otp = rand(100000, 999999);

            // OTP Expiry (10 Minutes)
            $otp_expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

            $insert = $pdo->prepare("
                INSERT INTO users
                    (
                    registration_no,
                    full_name,
                    email,
                    mobile,
                    otp,
                    otp_expiry,
                    otp_verified,
                    status,
                    first_login
                    )
                    VALUES
                    (
                    ?, ?, ?, ?, ?,
                    ?,
                    0,
                    'Pending',
                    1
                    )
                ");

            if ($insert->execute([
                $registration_no,
                $name,
                $email,
                $mobile,
                $otp,
                $otp_expiry
            ])) {

                if (sendOtpEmail($email, $name, $otp, "Prayas Student Portal - Registration OTP Verification")) {
                    $_SESSION['verify_email'] = $email;
                    header("Location: verify-otp.php");
                    exit;
                } else {
                    $message = "OTP Email could not be sent.";
                }
            } else {
                $message = "Something went wrong.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration | PRAYAS Student Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>

<?php require_once __DIR__ . '/includes/nav_header.php'; ?>

<main class="portal-main-wrapper">
    <div class="portal-split-card">
        <!-- Left Visual Architectural Panel with PRAYAS Logo Showcase -->
        <div class="split-visual-panel">
            <div class="split-divider-line"></div>
            <div class="portal-brand-overlay-card">
                <div class="portal-logo-glow-box">
                    <img src="assets/images/prayas-logo.png" alt="PRAYAS Logo" class="portal-overlay-logo">
                </div>
                <span class="portal-tag-badge">THE AUTOMATION ACADEMY</span>
                <h3>Building India's Industrial Workforce</h3>
                <p>100% Real Industrial Hardware • 415V Control Panels • Day-One Shop Floor Readiness</p>
            </div>
        </div>

        <!-- Right Form Panel -->
        <div class="split-form-panel">
            <h2>Register</h2>

            <?php if(!empty($message)): ?>
                <div class="alert-message">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <label class="input-label">Full Name</label>
                <div class="form-group-icon">
                    <i class="fa-solid fa-user input-icon"></i>
                    <input
                        type="text"
                        name="name"
                        placeholder="Enter your Full Name"
                        required
                        autofocus>
                </div>

                <label class="input-label">Email</label>
                <div class="form-group-icon">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input
                        type="email"
                        name="email"
                        placeholder="Enter your Email"
                        required>
                </div>

                <label class="input-label">Mobile Number</label>
                <div class="form-group-icon">
                    <i class="fa-solid fa-phone input-icon"></i>
                    <input
                        type="text"
                        name="mobile"
                        placeholder="Enter 10-Digit Mobile"
                        required>
                </div>

                <div class="auth-divider">
                    <span>or continue with</span>
                </div>

                <button type="button" class="btn-google-auth" id="btnGoogleAuth" title="Sign in with Google Account">
                    <svg width="22" height="22" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                </button>

                <div class="split-form-footer" style="margin-top: 10px;">
                    <span>Already have an account ?</span>
                    <a href="login.php">Login here</a>
                </div>

                <button type="submit" name="register" class="btn-portal-primary">
                    <span>Create Account</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</main>

<!-- Google Account Modal Overlay -->
<div class="google-modal-overlay" id="googleModalOverlay">
    <div class="google-modal-card">
        <button class="google-modal-close" onclick="closeGoogleModal()"><i class="fa-solid fa-xmark"></i></button>
        
        <div class="google-badge-icon">
            <svg width="28" height="28" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
        </div>

        <h3>Register with Google</h3>
        <p>Authorize &amp; enter your Google Account email to create your student account</p>

        <?php if ($is_google_error && !empty($message)): ?>
            <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #DC2626; padding: 10px 14px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 14px; text-align: left; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <form action="google-login.php?mode=register" method="POST" autocomplete="off">
            <div class="form-group-icon" style="margin-bottom: 14px;">
                <i class="fa-solid fa-envelope input-icon"></i>
                <input type="email" name="google_email" value="<?php echo htmlspecialchars($google_attempted_email); ?>" placeholder="Enter your Google Email" required style="border-color: #E2E8F0;">
            </div>

            <div class="form-group-icon" style="margin-bottom: 14px;">
                <i class="fa-solid fa-user input-icon"></i>
                <input type="text" name="google_name" value="" placeholder="Full Name" required style="border-color: #E2E8F0;">
            </div>

            <div style="margin-bottom: 18px; text-align: left; font-size: 0.85rem; color: #475569;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; user-select: none;">
                    <input type="checkbox" name="device_authorize" value="1" checked required style="accent-color: #2563EB; width: 16px; height: 16px;">
                    <span>Authorize device account access for registration</span>
                </label>
            </div>

            <button type="submit" class="btn-google-submit">
                <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#FFF" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#FFF" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/></svg>
                <span>Register with Google</span>
            </button>
        </form>
    </div>
</div>

<script>
const btnGoogle = document.getElementById("btnGoogleAuth");
const googleOverlay = document.getElementById("googleModalOverlay");

<?php if ($is_google_error): ?>
googleOverlay.classList.add("active");
<?php endif; ?>

btnGoogle.addEventListener("click", () => {
    googleOverlay.classList.add("active");
});

function closeGoogleModal() {
    googleOverlay.classList.remove("active");
}

googleOverlay.addEventListener("click", (e) => {
    if (e.target === googleOverlay) closeGoogleModal();
});
</script>
</body>
</html>