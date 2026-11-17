<?php
session_start();
require_once "db.php";
require_once "config.php";

require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";
$success_msg = "";

if (isset($_POST['request_reset'])) {
    $account_input = trim($_POST['account_input']);

    if (empty($account_input)) {
        $message = "Please enter your registered Email or Mobile number.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? OR mobile=?");
        $stmt->execute([$account_input, $account_input]);

        if ($stmt->rowCount() == 0) {
            $message = "No registered candidate account found for '$account_input'.";
        } else {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $otp = rand(100000, 999999);
            $otp_expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));

            $update = $pdo->prepare("UPDATE users SET otp=?, otp_expiry=? WHERE id=?");
            $update->execute([$otp, $otp_expiry, $user['id']]);

            // Attempt to send email via PHPMailer
            $mail_sent = false;
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'amanupadhyay2037@gmail.com';
                $mail->Password = 'vazo npfq gtaa shoe';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('amanupadhyay2037@gmail.com', 'PRAYAS Student Portal');
                $mail->addAddress($user['email'], $user['full_name']);
                $mail->isHTML(true);

                $mail->Subject = "Password Reset Request - PRAYAS Student Portal";
                $mail->Body = "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #1E293B;'>
                    <h2 style='color: #EF6B6B;'>PRAYAS Student Portal</h2>
                    <p>Hello <b>" . htmlspecialchars($user['full_name']) . "</b>,</p>
                    <p>You requested a password reset for your student candidate account.</p>
                    <div style='background: #F8FAFC; border: 2px dashed #EF6B6B; padding: 15px; border-radius: 8px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 4px; color: #0F172A;'>
                        $otp
                    </div>
                    <p style='font-size: 0.9rem; color: #64748B; margin-top: 15px;'>This verification code is valid for 15 minutes. If you did not request this, please ignore this message.</p>
                </div>";
                
                $mail->send();
                $mail_sent = true;
            } catch (Exception $e) {
                // Email sending failed - fallback notice
            }

            $_SESSION['reset_user_email'] = $user['email'];
            $_SESSION['reset_otp_sent'] = $otp; // Fallback helper for local testing
            header("Location: reset-password.php?sent=1");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | PRAYAS Student Portal</title>
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
                <span class="portal-tag-badge">ACCOUNT RECOVERY</span>
                <h3>Reset Your Candidate Password</h3>
                <p>Verify your registered email or mobile to create a new password securely.</p>
            </div>
        </div>

        <!-- Right Form Panel -->
        <div class="split-form-panel">
            <h2>Forgot Password</h2>
            <p style="font-size: 0.95rem; color: #64748B; margin-bottom: 24px;">Enter your registered Email or Mobile number below to receive a 6-digit OTP code.</p>

            <?php if(!empty($message)): ?>
                <div class="alert-message">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <label class="input-label">Registered Email or Mobile</label>
                <div class="form-group-icon" style="margin-bottom: 24px;">
                    <i class="fa-solid fa-user-shield input-icon"></i>
                    <input
                        type="text"
                        name="account_input"
                        placeholder="e.g. email@example.com or 9876543210"
                        required
                        autofocus>
                </div>

                <button type="submit" name="request_reset" class="btn-portal-primary">
                    <span>Send Verification OTP</span>
                    <i class="fa-solid fa-paper-plane"></i>
                </button>

                <div class="split-form-footer" style="margin-top: 20px;">
                    <span>Remember your password?</span>
                    <a href="login.php">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</main>

</body>
</html>
