<?php

session_start();
require_once "../../db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../../login.php");
    exit;
}

$user_id=$_SESSION['user_id'];

$pdo->prepare("DELETE FROM hobbies WHERE user_id=?")->execute([$user_id]);

$sql=$pdo->prepare("
INSERT INTO hobbies
(
user_id,
hobbies,
interests
)
VALUES
(
?,?,?
)
");

$sql->execute([

$user_id,

$_POST['hobbies'],

$_POST['interests']

]);

$pdo->prepare("
UPDATE users
SET current_step=current_step+1
WHERE id=?
")->execute([$user_id]);

if(isset($_GET['from'])){

header("Location: ../candidate-summary.php");

}else{

header("Location: save-resume.php");

}

exit;

?>