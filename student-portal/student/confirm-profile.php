<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* Final Confirmation */

$stmt = $pdo->prepare("
UPDATE users
SET
    profile_confirmed = 1,
    current_step = 5
WHERE id=?
");

$stmt->execute([$user_id]);

header("Location: ../aptitude/start-exam.php");
exit;