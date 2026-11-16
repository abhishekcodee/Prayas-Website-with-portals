<?php

session_start();

header("Content-Type: application/json");

require_once "../db.php";


/* =========================================
   1. CHECK LOGIN
========================================= */

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "message" => "Session expired. Please login again."
    ]);

    exit;
}


$user_id = $_SESSION['user_id'];


/* =========================================
   2. CHECK RESUME FILE
========================================= */

if (
    !isset($_FILES['resume']) ||
    $_FILES['resume']['error'] !== UPLOAD_ERR_OK
) {

    echo json_encode([
        "success" => false,
        "message" => "Resume file was not received."
    ]);

    exit;
}


$file = $_FILES['resume'];


/* =========================================
   3. CHECK FILE SIZE
   Maximum 5 MB
========================================= */

if ($file['size'] > 5 * 1024 * 1024) {

    echo json_encode([
        "success" => false,
        "message" => "Resume must be less than 5 MB."
    ]);

    exit;
}


/* =========================================
   4. GET ORIGINAL EXTENSION
========================================= */

$originalName = $file['name'];

$extension = strtolower(
    pathinfo(
        $originalName,
        PATHINFO_EXTENSION
    )
);


$allowedExtensions = [
    "pdf",
    "doc",
    "docx"
];


if (!in_array(
    $extension,
    $allowedExtensions,
    true
)) {

    echo json_encode([
        "success" => false,
        "message" => "Only PDF, DOC and DOCX files are allowed."
    ]);

    exit;
}


/* =========================================
   5. VALIDATE MIME TYPE
========================================= */

$finfo = new finfo(FILEINFO_MIME_TYPE);

$mimeType = $finfo->file(
    $file['tmp_name']
);


$allowedMimeTypes = [

    "pdf" => [
        "application/pdf"
    ],

    "doc" => [
        "application/msword",
        "application/octet-stream"
    ],

    "docx" => [
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        "application/zip",
        "application/octet-stream"
    ]

];


if (
    !isset($allowedMimeTypes[$extension]) ||
    !in_array(
        $mimeType,
        $allowedMimeTypes[$extension],
        true
    )
) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid resume file type."
    ]);

    exit;
}


/* =========================================
   6. UPLOAD DIRECTORY
========================================= */

$uploadDir =
    "../assets/uploads/resumes/";


if (!is_dir($uploadDir)) {

    if (
        !mkdir(
            $uploadDir,
            0755,
            true
        )
    ) {

        echo json_encode([
            "success" => false,
            "message" => "Unable to create resume upload directory."
        ]);

        exit;
    }
}


/* =========================================
   7. GET OLD RESUME FIRST
========================================= */

$stmt = $pdo->prepare("

    SELECT resume_file

    FROM resume

    WHERE user_id = ?

");

$stmt->execute([
    $user_id
]);


$oldResume =
    $stmt->fetchColumn();


/* =========================================
   8. CREATE SAFE UNIQUE FILE NAME
========================================= */

$fileName =

    "resume_" .

    $user_id .

    "_" .

    time() .

    "." .

    $extension;


$destination =

    $uploadDir .

    $fileName;


/* =========================================
   9. MOVE NEW RESUME
========================================= */

if (
    !move_uploaded_file(
        $file['tmp_name'],
        $destination
    )
) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to save uploaded resume."
    ]);

    exit;
}


/* =========================================
   10. UPDATE DATABASE
========================================= */

try {


    /*
     Check if resume record already exists
    */

    $checkStmt = $pdo->prepare("

        SELECT id

        FROM resume

        WHERE user_id = ?

        LIMIT 1

    ");


    $checkStmt->execute([
        $user_id
    ]);


    $resumeRecord =
        $checkStmt->fetch(
            PDO::FETCH_ASSOC
        );


    if ($resumeRecord) {


        /* Existing record */

        $updateStmt =
            $pdo->prepare("

                UPDATE resume

                SET resume_file = ?

                WHERE user_id = ?

            ");


        $updateStmt->execute([

            $fileName,

            $user_id

        ]);


    } else {


        /*
         No resume record exists.

         NOTE:
         This assumes resume table allows
         inserting user_id + resume_file.
        */

        $insertStmt =
            $pdo->prepare("

                INSERT INTO resume
                (
                    user_id,
                    resume_file
                )

                VALUES (?, ?)

            ");


        $insertStmt->execute([

            $user_id,

            $fileName

        ]);

    }


    /* =====================================
       11. DELETE OLD RESUME
    ===================================== */

    if (!empty($oldResume)) {

        $oldResumePath =

            $uploadDir .

            basename($oldResume);


        if (
            file_exists($oldResumePath) &&
            basename($oldResume) !== $fileName
        ) {

            @unlink(
                $oldResumePath
            );

        }

    }


    /* =====================================
       12. SUCCESS
    ===================================== */

    echo json_encode([

        "success" => true,

        "message" =>
            "Resume updated successfully.",

        "resume" =>
            $fileName

    ]);


    exit;


}
catch (PDOException $e) {


    /*
     Database failed,
     so remove newly uploaded file.
    */

    if (
        file_exists(
            $destination
        )
    ) {

        @unlink(
            $destination
        );

    }


    echo json_encode([

        "success" => false,

        "message" =>
            "Database error while updating resume."

    ]);


    exit;

}