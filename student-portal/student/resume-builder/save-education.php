<?php
session_start();
require_once "../../db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if($_SERVER['REQUEST_METHOD']=="POST"){

    $experience_type = $_POST['experience_type'];
    $_SESSION['experience_type'] = $experience_type;

    $qualification   = $_POST['qualification'];
  $specialization = $_POST['specialization'] ?? [];
    $institute       = $_POST['institute'];
    $board           = $_POST['board'];
    $passing_year    = $_POST['passing_year'];
    $percentage      = $_POST['percentage'];
    $status          = $_POST['status'];

    $delete = $pdo->prepare("DELETE FROM education WHERE user_id=?");
    $delete->execute([$user_id]);

    $sql = $pdo->prepare("
        INSERT INTO education
        (
            user_id,
            qualification,
            specialization,
            institute,
            board,
            passing_year,
            percentage,
            status
        )
        VALUES
        (
            ?,?,?,?,?,?,?,?
        )
    ");

    for($i=0;$i<count($qualification);$i++){

        $sql->execute([
            $user_id,
            $qualification[$i],
            $specialization[$i] ?? '',
            $institute[$i],
            $board[$i],
            $passing_year[$i],
            $percentage[$i],
            $status[$i]
        ]);

    }

    if($experience_type == "Experienced"){

        header("Location: experience-details.php");

    }else{

        header("Location: skills.php");

    }

    exit;
}
?>