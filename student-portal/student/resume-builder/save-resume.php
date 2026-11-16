<?php
session_start();

require_once __DIR__ . "/../../db.php";
require_once __DIR__ . "/../../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

if(!isset($_SESSION['user_id'])){
    header("Location: ../../login.php");
    exit;
}

$user_id=$_SESSION['user_id'];

$options=new Options();
$options->set('isHtml5ParserEnabled',true);
$options->set('isRemoteEnabled',true);

$dompdf=new Dompdf($options);


// Generate HTML
ob_start();

include "generate-resume.php";

$html=ob_get_clean();

$dompdf->loadHtml($html);

$dompdf->setPaper("A4","portrait");

$dompdf->render();


// Folder

$folder="../../assets/uploads/resumes/";

if(!is_dir($folder)){
    mkdir($folder,0777,true);
}


// File Name

$fileName="RESUME_".$user_id.".pdf";

$filePath=$folder.$fileName;


// Save PDF

file_put_contents(
    $filePath,
    $dompdf->output()
);


// Resume Table

$check=$pdo->prepare("
SELECT id
FROM resume
WHERE user_id=?
");

$check->execute([$user_id]);

if($check->rowCount()>0){

    $pdo->prepare("
    UPDATE resume
    SET resume_file=?
    WHERE user_id=?
    ")
    ->execute([
        $fileName,
        $user_id
    ]);

}else{

    $pdo->prepare("
    INSERT INTO resume
    (
        user_id,
        resume_file
    )
    VALUES
    (?,?)
    ")
    ->execute([
        $user_id,
        $fileName
    ]);

}


// Next Step

$pdo->prepare("
UPDATE users
SET current_step=2
WHERE id=?
")->execute([$user_id]);


// Redirect

if(isset($_GET['from'])){

header("Location: ../candidate-summary.php");

}else{

header("Location: ../photo.php");

}

exit;

?>