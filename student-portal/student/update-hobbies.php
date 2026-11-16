<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$hobbies  = trim($_POST['hobbies'] ?? '');
$interests = trim($_POST['interests'] ?? '');

// Check if record exists
$check = $pdo->prepare("
SELECT id
FROM hobbies
WHERE user_id=?
");
$check->execute([$user_id]);

if ($check->rowCount() > 0) {

    // Update
    $stmt = $pdo->prepare("
    UPDATE hobbies
    SET
        hobbies=?,
        interests=?
    WHERE user_id=?
    ");

    $stmt->execute([
        $hobbies,
        $interests,
        $user_id
    ]);

} else {

    // Insert
    $stmt = $pdo->prepare("
    INSERT INTO hobbies
    (
        user_id,
        hobbies,
        interests
    )
    VALUES
    (
        ?,
        ?,
        ?
    )
    ");

    $stmt->execute([
        $user_id,
        $hobbies,
        $interests
    ]);

}

header("Location: candidate-summary.php");
exit;