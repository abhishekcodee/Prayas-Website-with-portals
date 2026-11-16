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

/* ===========================
   Father
=========================== */

$father_name          = trim($_POST['father_name']);
$father_age           = !empty($_POST['father_age']) ? $_POST['father_age'] : NULL;
$father_qualification = trim($_POST['father_qualification']);
$father_occupation    = trim($_POST['father_occupation'] ?? '');
$father_income        = !empty($_POST['father_income']) ? $_POST['father_income'] : NULL;

/* ===========================
   Mother
=========================== */

$mother_name          = trim($_POST['mother_name']);
$mother_age           = !empty($_POST['mother_age']) ? $_POST['mother_age'] : NULL;
$mother_qualification = trim($_POST['mother_qualification']);
$mother_occupation    = trim($_POST['mother_occupation'] ?? '');
$mother_income        = !empty($_POST['mother_income']) ? $_POST['mother_income'] : NULL;

/* ===========================
   Validation
=========================== */

if (empty($father_name) || empty($father_qualification)) {
    die("Please fill Father Details.");
}

if (empty($mother_name) || empty($mother_qualification)) {
    die("Please fill Mother Details.");
}

/* ===========================
   Update
=========================== */

$stmt = $pdo->prepare("
UPDATE student_profile
SET
father_name=?,
father_age=?,
father_qualification=?,
father_occupation=?,
father_income=?,

mother_name=?,
mother_age=?,
mother_qualification=?,
mother_occupation=?,
mother_income=?

WHERE user_id=?
");

$stmt->execute([
    $father_name,
    $father_age,
    $father_qualification,
    $father_occupation,
    $father_income,

    $mother_name,
    $mother_age,
    $mother_qualification,
    $mother_occupation,
    $mother_income,

    $user_id
]);
/* ===========================
   Save Brother / Sister
=========================== */

// Delete old records
$pdo->prepare("
DELETE FROM student_siblings
WHERE user_id=?
")->execute([$user_id]);

// Insert new records
if(!empty($_POST['siblings'])){

    $stmt = $pdo->prepare("
    INSERT INTO student_siblings
    (
        user_id,
        relation,
        name,
        age,
        qualification,
        occupation,
        annual_income
    )
    VALUES
    (?,?,?,?,?,?,?)
    ");

    foreach($_POST['siblings'] as $row){

        if(empty($row['relation']) || empty($row['name'])){
            continue;
        }

        $stmt->execute([

            $user_id,

            trim($row['relation']),

            trim($row['name']),

            !empty($row['age']) ? $row['age'] : NULL,

            trim($row['qualification'] ?? ''),

            trim($row['occupation'] ?? ''),

            !empty($row['annual_income']) ? $row['annual_income'] : NULL

        ]);

    }

}

header("Location: candidate-summary.php");
exit;