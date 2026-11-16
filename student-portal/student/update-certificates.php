<?php

session_start();

header('Content-Type: application/json');

require_once "../db.php";


/* =========================================
   LOGIN CHECK
========================================= */

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        'success' => false,
        'message' => 'Session expired. Please login again.'
    ]);

    exit;
}

$user_id = (int) $_SESSION['user_id'];


/* =========================================
   ONLY POST REQUEST
========================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);

    exit;
}


/* =========================================
   FORM DATA
========================================= */

$certificateDbIds =
    $_POST['certificate_db_id'] ?? [];

$certificateNames =
    $_POST['certificate_name'] ?? [];

$organizations =
    $_POST['organization'] ?? [];

$issueDates =
    $_POST['issue_date'] ?? [];

$certificateIds =
    $_POST['certificate_id'] ?? [];

$oldCertificateFiles =
    $_POST['old_certificate_file'] ?? [];


/* =========================================
   UPLOAD DIRECTORY
========================================= */

$uploadDir =
    "../assets/uploads/certificates/";

if (!is_dir($uploadDir)) {

    if (!mkdir($uploadDir, 0775, true)) {

        echo json_encode([
            'success' => false,
            'message' => 'Certificate upload directory could not be created.'
        ]);

        exit;
    }
}


/* =========================================
   ALLOWED FILE TYPES
========================================= */

$allowedExtensions = [
    'pdf',
    'jpg',
    'jpeg',
    'png'
];

$maxFileSize = 5 * 1024 * 1024; // 5 MB


try {

    $pdo->beginTransaction();


    /* =====================================
       GET CURRENT USER CERTIFICATES
    ====================================== */

    $existingStmt = $pdo->prepare("
        SELECT id, certificate_file
        FROM certificates
        WHERE user_id = ?
    ");

    $existingStmt->execute([$user_id]);

    $existingCertificates =
        $existingStmt->fetchAll(PDO::FETCH_ASSOC);


    /*
       Store existing IDs so we can safely
       delete records removed from edit form.
    */

    $existingIds = [];

    $existingFiles = [];

    foreach ($existingCertificates as $existing) {

        $id = (int) $existing['id'];

        $existingIds[] = $id;

        $existingFiles[$id] =
            $existing['certificate_file'] ?? '';
    }


    /* =====================================
       IDS STILL PRESENT IN FORM
    ====================================== */

    $submittedExistingIds = [];


    /* =====================================
       LOOP CERTIFICATES
    ====================================== */

    foreach ($certificateNames as $index => $certificateName) {

        $certificateName =
            trim($certificateName);

        $organization =
            trim($organizations[$index] ?? '');

        $issueDate =
            trim($issueDates[$index] ?? '');

        $certificateId =
            trim($certificateIds[$index] ?? '');

        $dbId =
            isset($certificateDbIds[$index])
                ? (int) $certificateDbIds[$index]
                : 0;

        $oldFile =
            basename(
                $oldCertificateFiles[$index] ?? ''
            );


        /* ---------------------------------
           VALIDATION
        ---------------------------------- */

        if ($certificateName === '') {

            throw new Exception(
                'Certificate name is required.'
            );
        }

        if ($organization === '') {

            throw new Exception(
                'Issuing organization is required.'
            );
        }


        if ($issueDate === '') {

            $issueDate = null;
        }


        /* =================================
           VERIFY EXISTING RECORD OWNERSHIP
        ================================== */

        if ($dbId > 0) {

            if (!in_array(
                $dbId,
                $existingIds,
                true
            )) {

                throw new Exception(
                    'Invalid certificate record.'
                );
            }

            $submittedExistingIds[] = $dbId;

            /*
             Never trust old filename coming
             from browser. Use DB value.
            */

            $oldFile =
                $existingFiles[$dbId] ?? '';
        }


        /* =================================
           FILE UPLOAD
        ================================== */

        $finalFile = $oldFile;

        $newUploadedFile = null;


        if (
            isset($_FILES['certificate_file']) &&
            isset(
                $_FILES['certificate_file']['error'][$index]
            ) &&
            $_FILES['certificate_file']['error'][$index]
                !== UPLOAD_ERR_NO_FILE
        ) {

            $fileError =
                $_FILES['certificate_file']['error'][$index];

            if ($fileError !== UPLOAD_ERR_OK) {

                throw new Exception(
                    'Certificate file upload failed.'
                );
            }


            $tmpName =
                $_FILES['certificate_file']['tmp_name'][$index];

            $originalName =
                $_FILES['certificate_file']['name'][$index];

            $fileSize =
                (int)
                $_FILES['certificate_file']['size'][$index];


            /* FILE SIZE */

            if ($fileSize > $maxFileSize) {

                throw new Exception(
                    'Certificate file must be 5 MB or smaller.'
                );
            }


            /* EXTENSION */

            $extension =
                strtolower(
                    pathinfo(
                        $originalName,
                        PATHINFO_EXTENSION
                    )
                );

            if (
                !in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )
            ) {

                throw new Exception(
                    'Only PDF, JPG, JPEG and PNG files are allowed.'
                );
            }


            /* MIME VALIDATION */

            $finfo =
                new finfo(FILEINFO_MIME_TYPE);

            $mimeType =
                $finfo->file($tmpName);

            $allowedMimeTypes = [

                'application/pdf',

                'image/jpeg',

                'image/png'
            ];


            if (
                !in_array(
                    $mimeType,
                    $allowedMimeTypes,
                    true
                )
            ) {

                throw new Exception(
                    'Invalid certificate file type.'
                );
            }


            /* UNIQUE FILE NAME */

            $newFileName =
                'certificate_' .
                $user_id . '_' .
                bin2hex(random_bytes(8)) .
                '.' .
                $extension;


            $destination =
                $uploadDir . $newFileName;


            if (
                !move_uploaded_file(
                    $tmpName,
                    $destination
                )
            ) {

                throw new Exception(
                    'Unable to save certificate file.'
                );
            }


            $finalFile =
                $newFileName;

            $newUploadedFile =
                $newFileName;
        }


        /* =================================
           UPDATE EXISTING CERTIFICATE
        ================================== */

        if ($dbId > 0) {

            $updateStmt =
                $pdo->prepare("

                    UPDATE certificates

                    SET
                        certificate_name = ?,
                        organization = ?,
                        issue_date = ?,
                        certificate_id = ?,
                        certificate_file = ?

                    WHERE id = ?
                    AND user_id = ?

                ");


            $updateStmt->execute([

                $certificateName,

                $organization,

                $issueDate,

                $certificateId,

                $finalFile,

                $dbId,

                $user_id

            ]);


            /* DELETE OLD FILE AFTER REPLACEMENT */

            if (
                $newUploadedFile !== null &&
                !empty($oldFile) &&
                $oldFile !== $newUploadedFile
            ) {

                $oldPath =
                    $uploadDir . basename($oldFile);

                if (is_file($oldPath)) {

                    @unlink($oldPath);
                }
            }

        }


        /* =================================
           INSERT NEW CERTIFICATE
        ================================== */

        else {

            $insertStmt =
                $pdo->prepare("

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
                    (?, ?, ?, ?, ?, ?)

                ");


            $insertStmt->execute([

                $user_id,

                $certificateName,

                $organization,

                $issueDate,

                $certificateId,

                $finalFile

            ]);

        }

    }


    /* =====================================
       DELETE REMOVED CERTIFICATES
    ====================================== */

    foreach ($existingIds as $existingId) {

        if (
            !in_array(
                $existingId,
                $submittedExistingIds,
                true
            )
        ) {

            /* Get file before deleting */

            $fileToDelete =
                $existingFiles[$existingId] ?? '';


            /* Delete only current user's row */

            $deleteStmt =
                $pdo->prepare("

                    DELETE FROM certificates

                    WHERE id = ?
                    AND user_id = ?

                ");


            $deleteStmt->execute([

                $existingId,

                $user_id

            ]);


            /* Delete physical file */

            if (!empty($fileToDelete)) {

                $filePath =
                    $uploadDir .
                    basename($fileToDelete);

                if (is_file($filePath)) {

                    @unlink($filePath);
                }
            }
        }
    }


    /* =====================================
       COMMIT
    ====================================== */

    $pdo->commit();


    echo json_encode([

        'success' => true,

        'message' =>
            'Certificates updated successfully.'

    ]);

}
catch (Throwable $e) {

    if ($pdo->inTransaction()) {

        $pdo->rollBack();
    }


    echo json_encode([

        'success' => false,

        'message' => $e->getMessage()

    ]);
}