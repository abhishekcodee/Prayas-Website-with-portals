<?php
session_start();
require_once "db.php";

// If already logged in as Admin, redirect to Admin Dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: admin/dashboard.php");
    exit;
}

// If already logged in as Student, redirect to Student Dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

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

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!$pdo) {
        $message = "Database connection error: " . ($db_error ?? "Unable to connect to MySQL database.");
    } else {
    // 1. Check if login is for Admin
    try {
        $admin_stmt = $pdo->prepare("SELECT id, name, email, password FROM admin WHERE email = ? LIMIT 1");
        $admin_stmt->execute([$username]);
        
        if ($admin_stmt->rowCount() == 1) {
            $admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);
            $admin_password_valid = false;
            
            if (isset($admin['password']) && password_verify($password, $admin['password'])) {
                $admin_password_valid = true;
            } elseif (isset($admin['password']) && hash_equals((string)$admin['password'], (string)$password)) {
                $admin_password_valid = true;
            }
            
            if ($admin_password_valid) {
                session_regenerate_id(true);
                $_SESSION["admin_id"] = $admin["id"];
                $_SESSION["admin_name"] = $admin["name"] ?? "Administrator";
                $_SESSION["admin_email"] = $admin["email"] ?? $username;
                header("Location: admin/dashboard.php");
                exit;
            }
        }
    } catch (PDOException $e) {
        // Continue to student check
    }

    // 2. Check if login is for Student Candidate
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? OR mobile=?");
    $stmt->execute([$username, $username]);

    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($password, $user['password'])) {
            if ($user['status'] != "Active") {
                $message = "Your account is not active.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['full_name'];
                $_SESSION['registration_no'] = $user['registration_no'];

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
        } else {
            $message = "Invalid Password.";
        }
    } else {
        $message = "Invalid Email/Mobile or Password.";
    }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login | PRAYAS Student Portal</title>
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
            <a href="../employers.html">Employers & MSMEs</a>
            <a href="../students.html">Students</a>
            <a href="../contact.html">Contact</a>
        </nav>

        <a href="../index.html" class="btn-back-website">
            <i class="fa-solid fa-arrow-left"></i> Back to Website
        </a>
    </div>
</header>

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
            <h2>Login</h2>

            <?php if(isset($_GET['msg']) && $_GET['msg'] === 'reset_success'): ?>
                <div style="background: #F0FDF4; border: 1px solid #BBF7D0; color: #166534; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">
                    <i class="fa-solid fa-circle-check" style="margin-right: 6px;"></i>
                    Password reset successfully! Please login with your new password.
                </div>
            <?php endif; ?>

            <?php if(!empty($message)): ?>
                <div class="alert-message">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <label class="input-label">Email</label>
                <div class="form-group-icon">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input
                        type="text"
                        name="username"
                        placeholder="Enter your Email"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? $google_attempted_email); ?>"
                        required
                        autofocus>
                </div>

                <label class="input-label">Password</label>
                <div class="form-group-icon password-box">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required>
                    <span class="toggle-password" onclick="togglePassword()" title="Toggle Password Visibility">
                        <i class="fa-solid fa-eye-slash"></i>
                    </span>
                </div>

                <a href="forgot-password.php" class="forgot-password-link">Forgot password?</a>

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

                <div class="split-form-footer">
                    <span>Don't have an account ?</span>
                    <a href="register.php">Register here</a>
                </div>

                <button type="submit" name="login" class="btn-portal-primary">
                    <span>Login</span>
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

        <h3>Sign in with Google</h3>
        <p>Authorize &amp; enter your registered Google Account email to log in</p>

        <?php if ($is_google_error && !empty($message)): ?>
            <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #DC2626; padding: 10px 14px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 14px; text-align: left; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <form action="google-login.php?mode=login" method="POST" autocomplete="off">
            <div class="form-group-icon" style="margin-bottom: 14px;">
                <i class="fa-solid fa-envelope input-icon"></i>
                <input type="email" name="google_email" value="<?php echo htmlspecialchars($google_attempted_email); ?>" placeholder="Enter your registered Google Email" required style="border-color: #E2E8F0;">
            </div>

            <div style="margin-bottom: 18px; text-align: left; font-size: 0.85rem; color: #475569;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; user-select: none;">
                    <input type="checkbox" name="device_authorize" value="1" checked required style="accent-color: #2563EB; width: 16px; height: 16px;">
                    <span>Authorize device account access for this session</span>
                </label>
            </div>

            <button type="submit" class="btn-google-submit">
                <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#FFF" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#FFF" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/></svg>
                <span>Login with Google</span>
            </button>
        </form>
    </div>
</div>

<script>
function togglePassword(){
    let password = document.getElementById("password");
    let icon = document.querySelector(".toggle-password i");

    if(password.type === "password"){
        password.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    }else{
        password.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

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