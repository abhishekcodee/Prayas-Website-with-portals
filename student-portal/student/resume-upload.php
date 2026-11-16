<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if(isset($_POST['upload'])){

    $user_id = $_SESSION['user_id'];
   

    if(!isset($_FILES['resume']) || $_FILES['resume']['error'] != 0){
        die("Please select a resume.");
    }

    $file = $_FILES['resume'];

    $allowed = ['pdf','doc','docx'];

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if(!in_array($extension,$allowed)){
        die("Only PDF, DOC and DOCX files are allowed.");
    }

    if($file['size'] > 5 * 1024 * 1024){
        die("Maximum file size is 5 MB.");
    }

    $folder = "../assets/uploads/resumes/";

    if(!is_dir($folder)){
        mkdir($folder,0777,true);
    }

    $newFile = "RESUME_" . $user_id . "_" . time() . "." . $extension;

    if(move_uploaded_file($file['tmp_name'],$folder.$newFile)){

    $check = $pdo->prepare("SELECT id FROM resume WHERE user_id=?");
$check->execute([$user_id]);

if($check->rowCount()>0){

    $stmt = $pdo->prepare("
        UPDATE resume
        SET
            resume_type=?,
            resume_file=?,
            original_name=?,
            file_size=?
        WHERE user_id=?
    ");

    $stmt->execute([
        'upload',
        $newFile,
        $file['name'],
        $file['size'],
        $user_id
    ]);

}else{

    $stmt = $pdo->prepare("
        INSERT INTO resume
        (user_id,resume_type,resume_file,original_name,file_size)
        VALUES(?,?,?,?,?)
    ");

    $stmt->execute([
        $user_id,
        'upload',
        $newFile,
        $file['name'],
        $file['size']
    ]);

}


     // Agar Candidate Summary se aaya hai
if(isset($_POST['from']) && $_POST['from']=="summary"){

    header("Location: candidate-summary.php");
    exit;

}

// Registration Flow
$update = $pdo->prepare("
UPDATE users
SET current_step = 2
WHERE id = ?
");

$update->execute([$user_id]);

header("Location: resume-builder.php");
exit;

    }else{

        die("Upload Failed");

    }

}
?>