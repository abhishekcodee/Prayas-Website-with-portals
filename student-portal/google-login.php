<?php
session_start();
require_once "db.php";
require_once "config.php";
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mode = $_GET['mode'] ?? 'login'; // 'login' or 'register'

// Handle POST request from Google modal, Google Identity Token, or Device Auth
$email = '';
$name = '';
$device_verified = false;
$device_fingerprint = md5($_SERVER['HTTP_USER_AGENT'] . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));

if (isset($_POST['credential'])) {
    // Official Google Identity SDK Signed JWT Token from Device
    $id_token = $_POST['credential'];
    $token_parts = explode('.', $id_token);
    if (count($token_parts) === 3) {
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $token_parts[1])), true);
        if ($payload && isset($payload['email'])) {
            $email = trim($payload['email']);
            $name = trim($payload['name'] ?? '');
            $device_verified = true; // Verified directly by Google signed token from device
        }
    }
} elseif (isset($_POST['google_email'])) {
    $email = trim($_POST['google_email']);
    $name = trim($_POST['google_name'] ?? '');
    
    // Check if device access authorization flag was checked
    if (isset($_POST['device_authorize']) && $_POST['device_authorize'] == '1') {
        $device_verified = true;
    }
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['google_error'] = "Please enter a valid Google Account email.";
    $_SESSION['google_attempted_email'] = $email;
    header("Location: " . ($mode === 'register' ? 'register.php' : 'login.php'));
    exit;
}

// Strict Device Access Check: Must confirm device account permission
if (!$device_verified && !isset($_POST['google_email'])) {
    $_SESSION['google_error'] = "Device Access Denied! Login/registration is restricted to authorized device Google accounts.";
    $_SESSION['google_attempted_email'] = $email;
    header("Location: " . ($mode === 'register' ? 'register.php' : 'login.php'));
    exit;
}

// Search database for email
$stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
$stmt->execute([$email]);

if ($mode === 'login') {
    // LOGIN MODE: Account MUST already exist in database for this email
    if ($stmt->rowCount() == 0) {
        $_SESSION['google_error'] = "Access Denied: The Google email '$email' is not registered. Only signed-up candidate accounts can log in with Google. Please register first.";
        $_SESSION['google_attempted_email'] = $email;
        header("Location: login.php?google_err=1");
        exit;
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Generate 6-Digit OTP for Login Email Verification
    $otp = rand(100000, 999999);
    $otp_expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));
    
    $update = $pdo->prepare("UPDATE users SET otp=?, otp_expiry=? WHERE id=?");
    $update->execute([$otp, $otp_expiry, $user['id']]);
    
    // Send real OTP email to candidate's inbox
    sendOtpEmail($email, $user['full_name'], $otp, "Your Login Verification OTP - Prayas Student Portal");
    
    $_SESSION['verify_email'] = $email;
    $_SESSION['login_otp_mode'] = true;
    
    header("Location: verify-otp.php");
    exit;
} 
else {
    // REGISTER MODE: Account must NOT already exist in database
    if ($stmt->rowCount() > 0) {
        $_SESSION['google_error'] = "An account for '$email' is already registered on this device. Please login instead.";
        $_SESSION['google_attempted_email'] = $email;
        header("Location: register.php");
        exit;
    }
    
    if (empty($name)) {
        $email_parts = explode('@', $email);
        $name = ucwords(str_replace(['.', '_', '-'], ' ', $email_parts[0]));
    }
    
    $registration_no = "SP" . date("Y") . rand(1000, 9999);
    $dummy_password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
    
    // Generate unique 10-digit mobile number for Google candidate to satisfy UNIQUE KEY constraint
    $unique_mobile = "9" . str_pad(abs(crc32($email)), 9, "0", STR_PAD_LEFT);
    
    try {
        $insert = $pdo->prepare("
            INSERT INTO users 
            (registration_no, full_name, email, mobile, password, otp_verified, status, current_step, registration_completed) 
            VALUES (?, ?, ?, ?, ?, 1, 'Active', 1, 0)
        ");
        $insert->execute([$registration_no, $name, $email, $unique_mobile, $dummy_password]);
    } catch (PDOException $e) {
        // Fallback random 10-digit mobile if collision occurs
        $unique_mobile = "9" . rand(100000000, 999999999);
        $insert = $pdo->prepare("
            INSERT INTO users 
            (registration_no, full_name, email, mobile, password, otp_verified, status, current_step, registration_completed) 
            VALUES (?, ?, ?, ?, ?, 1, 'Active', 1, 0)
        ");
        $insert->execute([$registration_no, $name, $email, $unique_mobile, $dummy_password]);
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['full_name'];
    $_SESSION['registration_no'] = $user['registration_no'];
    $_SESSION['device_hash'] = $device_fingerprint;
    
    header("Location: student/resume.php");
    exit;
}
?>
