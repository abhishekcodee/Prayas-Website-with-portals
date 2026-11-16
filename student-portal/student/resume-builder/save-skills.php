<?php

session_start();

require_once "../../db.php";

$user_id=$_SESSION['user_id'];

$delete=$pdo->prepare("DELETE FROM skills WHERE user_id=?");

$delete->execute([$user_id]);

$sql=$pdo->prepare("

INSERT INTO skills

(user_id,skill_name,skill_level)

VALUES

(?,?,?)

");

foreach($_POST['skill_name'] as $key=>$skill){

$sql->execute([

$user_id,

$skill,

$_POST['skill_level'][$key]

]);

}

if(isset($_GET['from'])){

header("Location: ../candidate-summary.php");

}else{

header("Location: certificates.php");

}

exit;

?>