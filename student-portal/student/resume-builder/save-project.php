<?php

session_start();
require_once "../../db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../../login.php");
    exit;
}

$user_id=$_SESSION['user_id'];

$pdo->prepare("DELETE FROM projects WHERE user_id=?")->execute([$user_id]);

$total=count($_POST['project_title']);

for($i=0;$i<$total;$i++){

    if(trim($_POST['project_title'][$i])==""){
        continue;
    }

    $sql=$pdo->prepare("
    INSERT INTO projects
    (
        user_id,
        project_title,
        technologies,
        project_description,
        github_link,
        live_link,
        duration
    )
    VALUES(?,?,?,?,?,?,?)
    ");

    $sql->execute([

        $user_id,

        $_POST['project_title'][$i],

        $_POST['technologies'][$i],

        $_POST['description'][$i],

        $_POST['github'][$i],

        $_POST['live'][$i],

        $_POST['duration'][$i]

    ]);

}

$pdo->prepare("UPDATE users SET current_step=current_step+1 WHERE id=?")->execute([$user_id]);

header("Location: hobbies.php");
exit;
?>