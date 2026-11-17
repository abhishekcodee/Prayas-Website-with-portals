<?php
session_start();
require_once "db.php";
require_once "config.php";

$message = "";
$preset_email = $_SESSION['reset_user_email'] ?? '';
$fallback_otp = $_SESSION['reset_otp_sent'] ?? '';

if (isset($_POST['reset_password'])) {
    $email = trim($_POST['email']);
    $otp = trim($_POST['otp']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($email) || empty($otp) || empty($new_password) || empty($confirm_password)) {
        $message = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $message = "New Password and Confirm Password do not match.";
    } elseif (strlen($new_password) < 6) {
        $message = "Password must be at least 6 characters long.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? OR mobile=?");
        $stmt->execute([$email, $email]);

        if ($stmt->rowCount() == 0) {
            $message = "No account found matching this email/mobile.";
        } else {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check OTP & Expiry
            $now = date("Y-m-d H:i:s");
            if ($user['otp'] !== $otp) {
                $message = "Invalid 6-digit OTP code entered.";
            } elseif ($user['otp_expiry'] < $now) {
                $message = "The OTP code has expired. Please request a new one.";
            } else {
                // Update Password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password=?, otp=NULL, otp_expiry=NULL, status='Active', otp_verified=1 WHERE id=?");
                $update->execute([$hashed_password, $user['id']]);

                unset($_SESSION['reset_user_email']);
                unset($_SESSION['reset_otp_sent']);

                header("Location: login.php?msg=reset_success");
                exit;
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
    <title>Set New Password | PRAYAS Student Portal</title>
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
                <span class="portal-tag-badge">SECURITY VERIFICATION</span>
                <h3>Create Your New Password</h3>
                <p>Enter the 6-digit OTP code sent to your registered account and choose a new strong password.</p>
            </div>
        </div>

        <!-- Right Form Panel -->
        <div class="split-form-panel">
            <h2>Set New Password</h2>

            <?php if(isset($_GET['sent'])): ?>
                <div style="background: #F0FDF4; border: 1px solid #BBF7D0; color: #166534; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">
                    <i class="fa-solid fa-circle-check" style="margin-right: 6px;"></i>
                    Verification OTP code has been sent to your registered account.
                    <?php if (!empty($fallback_otp)): ?>
                        <br><small style="color: #15803D;"><strong>Test Helper OTP Code:</strong> <?php echo htmlspecialchars($fallback_otp); ?></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if(!empty($message)): ?>
                <div class="alert-message">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <label class="input-label">Registered Email or Mobile</label>
                <div class="form-group-icon">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input
                        type="text"
                        name="email"
                        value="<?php echo htmlspecialchars($preset_email); ?>"
                        placeholder="Enter registered Email or Mobile"
                        required>
                </div>

                <label class="input-label">6-Digit Verification OTP</label>
                <div class="form-group-icon">
                    <i class="fa-solid fa-key input-icon"></i>
                    <input
                        type="text"
                        name="otp"
                        placeholder="Enter 6-digit OTP"
                        maxlength="6"
                        required>
                </div>

                <label class="input-label">New Password</label>
                <div class="form-group-icon">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input
                        type="password"
                        name="new_password"
                        placeholder="Enter new password (min. 6 chars)"
                        required>
                </div>

                <label class="input-label">Confirm New Password</label>
                <div class="form-group-icon" style="margin-bottom: 24px;">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input
                        type="password"
                        name="confirm_password"
                        placeholder="Confirm new password"
                        required>
                </div>

                <button type="submit" name="reset_password" class="btn-portal-primary">
                    <span>Update Password &amp; Login</span>
                    <i class="fa-solid fa-check-circle"></i>
                </button>

                <div class="split-form-footer" style="margin-top: 20px;">
                    <span>Didn't receive code?</span>
                    <a href="forgot-password.php">Request New OTP</a>
                </div>
            </form>
        </div>
    </div>
</main>

</body>
</html>
