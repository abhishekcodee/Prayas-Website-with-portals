<?php

session_start();

header('Content-Type: application/json');

require_once "../db.php";


if(!isset($_SESSION['user_id'])){

    echo json_encode([
        "success" => false,
        "message" => "Session expired."
    ]);

    exit;

}


$user_id = $_SESSION['user_id'];


if(
    !isset($_FILES['photo']) ||
    $_FILES['photo']['error'] !== UPLOAD_ERR_OK
){

    echo json_encode([
        "success" => false,
        "message" => "Photo not received."
    ]);

    exit;

}


$file = $_FILES['photo'];


/* ============================
   VALIDATE SIZE
============================ */

if($file['size'] > 5 * 1024 * 1024){

    echo json_encode([
        "success" => false,
        "message" => "Photo must be below 5MB."
    ]);

    exit;

}


/* ============================
   VALIDATE IMAGE
============================ */

$imageInfo =
    getimagesize($file['tmp_name']);

if($imageInfo === false){

    echo json_encode([
        "success" => false,
        "message" => "Invalid image file."
    ]);

    exit;

}


$allowedMime = [
    "image/jpeg",
    "image/png"
];


if(!in_array($imageInfo['mime'], $allowedMime)){

    echo json_encode([
        "success" => false,
        "message" => "Only JPG and PNG allowed."
    ]);

    exit;

}


/* ============================
   UPLOAD DIRECTORY
============================ */

$uploadDir =
    "../uploads/photos/";


if(!is_dir($uploadDir)){

    mkdir(
        $uploadDir,
        0755,
        true
    );

}


/* ============================
   FILE NAME
============================ */

$fileName =
    "candidate_" .
    $user_id .
    "_" .
    time() .
    ".jpg";


$destination =
    $uploadDir .
    $fileName;


/* ============================
   MOVE FILE
============================ */

if(
    !move_uploaded_file(
        $file['tmp_name'],
        $destination
    )
){

    echo json_encode([
        "success" => false,
        "message" => "Unable to save photo."
    ]);

    exit;

}


/* ============================
   GET OLD PHOTO
============================ */

$stmt = $pdo->prepare("
    SELECT photo
    FROM student_profile
    WHERE user_id = ?
");

$stmt->execute([$user_id]);

$oldPhoto =
    $stmt->fetchColumn();


/* ============================
   UPDATE DATABASE
============================ */

$stmt = $pdo->prepare("
    UPDATE student_profile
    SET photo = ?
    WHERE user_id = ?
");

$success =
    $stmt->execute([
        $fileName,
        $user_id
    ]);


if(!$success){

    @unlink($destination);

    echo json_encode([
        "success" => false,
        "message" => "Database update failed."
    ]);

    exit;

}


/* ============================
   DELETE OLD PHOTO
============================ */

if(!empty($oldPhoto)){

    $oldPath =
        $uploadDir .
        basename($oldPhoto);

    if(
        file_exists($oldPath) &&
        $oldPhoto !== $fileName
    ){

        @unlink($oldPath);

    }

}


echo json_encode([

    "success" => true,

    "photo" => $fileName

]);
