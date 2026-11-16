<?php

session_start();
require_once "../../db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../../login.php");
    exit;
}

$user_id=$_SESSION['user_id'];

$folder="../../assets/uploads/certificates/";

if(!is_dir($folder)){
    mkdir($folder,0777,true);
}

/* Delete old certificates */

$delete=$pdo->prepare("DELETE FROM certificates WHERE user_id=?");
$delete->execute([$user_id]);

$total=count($_POST['certificate_name']);

for($i=0;$i<$total;$i++){

    if(trim($_POST['certificate_name'][$i])==""){
        continue;
    }

    $fileName=NULL;

    if(isset($_FILES['certificate_file']['name'][$i]) &&
       $_FILES['certificate_file']['error'][$i]==0){

        $ext=pathinfo($_FILES['certificate_file']['name'][$i],PATHINFO_EXTENSION);

        $fileName="CERTIFICATE_".$user_id."_".time()."_".$i.".".$ext;

        move_uploaded_file(

            $_FILES['certificate_file']['tmp_name'][$i],

            $folder.$fileName

        );

    }

    $sql=$pdo->prepare("

    INSERT INTO certificates

    (

        user_id,

        certificate_name,

        organization,

        issue_date,

        certificate_id,

        certificate_file

    )

    VALUES

    (?,?,?,?,?,?)

    ");

    $sql->execute([

        $user_id,

        $_POST['certificate_name'][$i],

        $_POST['organization'][$i],

        $_POST['issue_date'][$i],

        $_POST['certificate_id'][$i],

        $fileName

    ]);

}

/* Next Step */

$update=$pdo->prepare("

UPDATE users

SET current_step=current_step+1

WHERE id=?

");

$update->execute([$user_id]);

if(isset($_GET['from'])){

header("Location: ../candidate-summary.php");

}else{

header("Location: hobbies.php");

}

exit;

?>