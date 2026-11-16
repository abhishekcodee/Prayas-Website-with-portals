<?php

session_start();
require_once "../../db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* Delete previous records */
$delete = $pdo->prepare("DELETE FROM experience WHERE user_id=?");
$delete->execute([$user_id]);

$total = count($_POST['company_name']);

for($i=0;$i<$total;$i++){

    if(trim($_POST['company_name'][$i])==""){
        continue;
    }

    $currentlyWorking = ($_POST['currently_working'][$i]=="Yes") ? 1 : 0;

    $sql = $pdo->prepare("
        INSERT INTO experience
        (
            user_id,
            company_name,
            designation,
            employment_type,
            start_date,
            end_date,
            currently_working,
            job_description,
            achievement
        )
        VALUES
        (
            ?,?,?,?,?,?,?,?,?
        )
    ");

    $sql->execute([

        $user_id,

        $_POST['company_name'][$i],

        $_POST['designation'][$i],

        $_POST['employment_type'][$i],

        $_POST['start_date'][$i],

        $_POST['end_date'][$i],

        $currentlyWorking,

        $_POST['description'][$i],

        $_POST['achievement'][$i]

    ]);

}

/* Move Resume Builder to next step */
$update = $pdo->prepare("
UPDATE users
SET current_step = current_step + 1
WHERE id=?
");

$update->execute([$user_id]);

header("Location: skills.php");
exit;