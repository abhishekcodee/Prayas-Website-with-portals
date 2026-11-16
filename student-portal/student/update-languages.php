<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* Delete Old Languages */

$pdo->prepare("
DELETE FROM student_languages
WHERE user_id=?
")->execute([$user_id]);

$languages = $_POST['language_id'] ?? [];

foreach ($languages as $language_id => $value) {

    $can_read  = isset($_POST['can_read'][$language_id]) ? 1 : 0;
    $can_write = isset($_POST['can_write'][$language_id]) ? 1 : 0;
    $can_speak = isset($_POST['can_speak'][$language_id]) ? 1 : 0;

    $stmt = $pdo->prepare("
        INSERT INTO student_languages
        (
            user_id,
            language_id,
            can_read,
            can_write,
            can_speak
        )
        VALUES (?,?,?,?,?)
    ");

    $stmt->execute([
        $user_id,
        $language_id,
        $can_read,
        $can_write,
        $can_speak
    ]);
}

header("Location: candidate-summary.php");
exit;
?>