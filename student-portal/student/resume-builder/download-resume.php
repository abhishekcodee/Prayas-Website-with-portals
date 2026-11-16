<?php

session_start();

require_once "../../db.php";
require_once "../../vendor/autoload.php";

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

ob_start();

include "generate-resume.php";

$html=ob_get_clean();

$dompdf->loadHtml($html);

$dompdf->setPaper("A4","portrait");

$dompdf->render();


//============================
// SAVE PDF
//============================

$folder="../../assets/uploads/resumes/";

if(!is_dir($folder)){

    mkdir($folder,0777,true);

}

$fileName="RESUME_".$user_id.".pdf";

$filePath=$folder.$fileName;

file_put_contents(

    $filePath,

    $dompdf->output()

);


//============================
// DATABASE
//============================

$check=$pdo->prepare("
SELECT id
FROM resume
WHERE user_id=?
");

$check->execute([$user_id]);

if($check->rowCount()){

    $update=$pdo->prepare("
    UPDATE resume
    SET
    resume_file=?
    WHERE user_id=?
    ");

    $update->execute([

        $fileName,

        $user_id

    ]);

}else{

    $insert=$pdo->prepare("
    INSERT INTO resume
    (
        user_id,
        resume_file
    )
    VALUES
    (?,?)
    ");

    $insert->execute([

        $user_id,

        $fileName

    ]);

}



//============================
// NEXT STEP
//============================

$pdo->prepare("
UPDATE users
SET current_step=2
WHERE id=?
")->execute([$user_id]);




//============================
// REDIRECT
//============================

header("Location: ../photo.php");

exit;