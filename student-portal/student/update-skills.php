<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] != "POST") {
    header("Location: candidate-summary.php");
    exit;
}

/* Delete Old Skills */

$delete = $pdo->prepare("
DELETE FROM skills
WHERE user_id = ?
");
$delete->execute([$user_id]);

/* Get Form Data */

$skill_names  = $_POST['skill_name'] ?? [];
$skill_levels = $_POST['skill_level'] ?? [];

/* Save Skills */

foreach ($skill_names as $index => $skill_name) {

    $skill_name = trim($skill_name);

    if ($skill_name == "") {
        continue;
    }

    $skill_level = $skill_levels[$index] ?? "Beginner";

    $insert = $pdo->prepare("
        INSERT INTO skills
        (
            user_id,
            skill_name,
            skill_level
        )
        VALUES
        (?,?,?)
    ");

    $insert->execute([
        $user_id,
        $skill_name,
        $skill_level
    ]);
}

/* Back to Summary */

header("Location: candidate-summary.php");
exit;