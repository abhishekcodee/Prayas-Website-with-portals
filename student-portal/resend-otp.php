<?php
session_start();
require_once "db.php";
require_once "config.php";

if(!isset($_SESSION['verify_email'])){
    header("Location: register.php");
    exit;
}

$email = $_SESSION['verify_email'];

// User Details
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE email=?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$name = $user['full_name'] ?? 'Candidate';

// New OTP
$otp = rand(100000,999999);
$otp_expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

// Update OTP
$pdo->prepare("
UPDATE users
SET otp=?,
    otp_expiry=?
WHERE email=?
")->execute([
    $otp,
    $otp_expiry,
    $email
]);

sendOtpEmail($email, $name, $otp, "PRAYAS Portal - Resent Verification OTP Code");

header("Location: verify-otp.php");
exit;