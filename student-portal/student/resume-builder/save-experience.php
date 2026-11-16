<?php

session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../../login.php");
    exit;
}

$type = $_POST['experience_type'];

$_SESSION['experience_type'] = $type;

if($type == "Fresher"){

    header("Location: certificates.php");

}else{

    header("Location: experience-details.php");

}

exit;