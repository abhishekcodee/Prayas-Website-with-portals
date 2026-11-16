<?php
session_start();
require_once "db.php";

if(!isset($_SESSION['verify_email'])){
    header("Location: register.php");
    exit;
}

$message = "";

if(isset($_POST['verify'])){

    $otp = trim($_POST['otp']);
    $email = $_SESSION['verify_email'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE email=?
        AND otp=?
    ");

    $stmt->execute([$email,$otp]);

    if($stmt->rowCount()==1){

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if(strtotime($user['otp_expiry']) < time()){

            $message = "OTP has expired.";

        }else{

            $pdo->prepare("
                UPDATE users
                SET otp_verified=1,
                    status='Active',
                    otp=NULL,
                    otp_expiry=NULL
                WHERE id=?
            ")->execute([$user['id']]);

            // If this was an OTP Login attempt or user already completed registration
            if (isset($_SESSION['login_otp_mode']) && $_SESSION['login_otp_mode'] === true) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['full_name'];
                $_SESSION['registration_no'] = $user['registration_no'];
                $_SESSION['device_hash'] = md5($_SERVER['HTTP_USER_AGENT'] . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));
                
                unset($_SESSION['login_otp_mode']);
                unset($_SESSION['verify_email']);
                unset($_SESSION['login_otp_sent']);

                if ($user['registration_completed'] == 1) {
                    header("Location: dashboard.php");
                    exit;
                }

                switch ($user['current_step']) {
                    case 1: header("Location: student/resume.php"); break;
                    case 2: header("Location: student/camera.php"); break;
                    case 3: header("Location: student/personal.php"); break;
                    case 4: header("Location: aptitude/start-exam.php"); break;
                    case 5: header("Location: video/introduction.php"); break;
                    default: header("Location: student/resume.php"); break;
                }
                exit;
            }

            $_SESSION['create_password_email'] = $email;
            unset($_SESSION['verify_email']);
            unset($_SESSION['login_otp_sent']);

            header("Location: create-password.php");
            exit;

        }

    }else{

        $message = "Invalid OTP.";

    }

}

$fallback_otp = $_SESSION['login_otp_sent'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification | PRAYAS Student Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>

<header class="prayas-header">
    <div class="prayas-header-inner">
        <a href="../index.html" class="prayas-brand">
            <img src="assets/images/prayas-logo.png" alt="PRAYAS - The Automation Academy" class="prayas-logo">
        </a>

        <nav class="nav__links" aria-label="Main Navigation">
            <a href="../index.html">Home</a>
            <a href="../about.html">About Us</a>
            <a href="../employers.html">Employers &amp; MSMEs</a>
            <a href="../students.html">Students</a>
            <a href="../contact.html">Contact</a>
        </nav>

        <a href="../index.html" class="btn-back-website">
            <i class="fa-solid fa-arrow-left"></i> Back to Website
        </a>
    </div>
</header>

<main class="portal-main-wrapper">
    <div class="container">
        <div class="card-icon-badge">
            <i class="fa-solid fa-shield-halved"></i>
        </div>

        <h2>OTP Verification</h2>
        <p class="card-subtitle">Enter the 6-digit verification code sent to your registered email</p>

        <?php if(!empty($_SESSION['verify_email'])): ?>
            <div style="background: #F0FDF4; border: 1px solid #BBF7D0; color: #166534; padding: 12px 16px; border-radius: 8px; font-size: 0.88rem; margin-bottom: 16px; text-align: center;">
                <i class="fa-solid fa-paper-plane" style="margin-right: 6px; color: #16A34A;"></i>
                <span>Verification OTP code sent to <strong><?php echo htmlspecialchars($_SESSION['verify_email']); ?></strong>. Please check your email inbox!</span>
            </div>
        <?php endif; ?>

        <?php if(!empty($message)): ?>
            <div class="alert-message">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group-icon">
                <i class="fa-solid fa-key input-icon"></i>
                <input
                    type="text"
                    name="otp"
                    maxlength="6"
                    placeholder="Enter 6-Digit OTP Code"
                    required
                    autofocus>
            </div>

            <button type="submit" name="verify" class="btn-portal-primary">
                <span>Verify OTP &amp; Proceed</span>
                <i class="fa-solid fa-circle-check"></i>
            </button>

            <div style="text-align:center; margin-top: 24px; font-size: 0.88rem; color: var(--text-muted);">
                <span id="timer" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: var(--primary);">
                    <i class="fa-solid fa-clock"></i> Resend OTP in 60 sec
                </span>

                <a href="resend-otp.php" id="resendLink" style="display:none; color: var(--primary); font-weight: 700; text-decoration: none;">
                    <i class="fa-solid fa-rotate-right"></i> Resend OTP Code
                </a>
            </div>
        </form>
    </div>
</main>
<script>

let seconds = 60;

const timer = document.getElementById("timer");

const resend = document.getElementById("resendLink");

const interval = setInterval(function(){

    seconds--;

    timer.innerHTML="Resend OTP in "+seconds+" sec";

    if(seconds<=0){

        clearInterval(interval);

        timer.style.display="none";

        resend.style.display="inline";

    }

},1000);

</script>
</body>
</html>