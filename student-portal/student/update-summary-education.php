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


$user_id = (int) $_SESSION['user_id'];


/* =========================================
   2. GET FORM ARRAYS
========================================= */

$education_ids =
    $_POST['education_id'] ?? [];

$qualifications =
    $_POST['qualification'] ?? [];

$specializations =
    $_POST['specialization'] ?? [];

$institutes =
    $_POST['institute'] ?? [];

$boards =
    $_POST['board'] ?? [];

$passing_years =
    $_POST['passing_year'] ?? [];

$percentages =
    $_POST['percentage'] ?? [];

$statuses =
    $_POST['status'] ?? [];


/* =========================================
   3. BASIC VALIDATION
========================================= */

if (
    !is_array($education_ids) ||
    !is_array($qualifications) ||
    !is_array($specializations) ||
    !is_array($institutes) ||
    !is_array($boards) ||
    !is_array($passing_years) ||
    !is_array($percentages) ||
    !is_array($statuses)
) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid education data."
    ]);

    exit;
}


/* =========================================
   4. CHECK ARRAY COUNTS
========================================= */

$totalRows = count($qualifications);


if (
    count($education_ids) !== $totalRows ||
    count($specializations) !== $totalRows ||
    count($institutes) !== $totalRows ||
    count($boards) !== $totalRows ||
    count($passing_years) !== $totalRows ||
    count($percentages) !== $totalRows ||
    count($statuses) !== $totalRows
) {

    echo json_encode([
        "success" => false,
        "message" => "Education form data is incomplete."
    ]);

    exit;
}


/* =========================================
   5. START TRANSACTION
========================================= */

try {

    $pdo->beginTransaction();


    /*
    =========================================
    6. GET ALL CURRENT EDUCATION IDS

    Important:
    Only rows belonging to logged-in user
    are considered.
    =========================================
    */

    $stmt = $pdo->prepare("
        SELECT id
        FROM education
        WHERE user_id = ?
    ");

    $stmt->execute([
        $user_id
    ]);


    $existingIds =
        $stmt->fetchAll(
            PDO::FETCH_COLUMN
        );


    $existingIds =
        array_map(
            'intval',
            $existingIds
        );


    /*
    IDs received from browser that were
    successfully updated.
    */

    $submittedExistingIds = [];


    /*
    =========================================
    7. PREPARE UPDATE QUERY
    =========================================
    */

    $updateStmt = $pdo->prepare("

        UPDATE education

        SET
            qualification = ?,
            specialization = ?,
            institute = ?,
            board = ?,
            passing_year = ?,
            percentage = ?,
            status = ?

        WHERE id = ?
        AND user_id = ?

    ");


    /*
    =========================================
    8. PREPARE INSERT QUERY
    =========================================
    */

    $insertStmt = $pdo->prepare("

        INSERT INTO education
        (
            user_id,
            qualification,
            specialization,
            institute,
            board,
            passing_year,
            percentage,
            status
        )

        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, ?
        )

    ");


    /*
    =========================================
    9. PROCESS EACH EDUCATION ROW
    =========================================
    */

    for (
        $i = 0;
        $i < $totalRows;
        $i++
    ) {


        $educationId =
            isset($education_ids[$i])
            ? (int)$education_ids[$i]
            : 0;


        $qualification =
            trim(
                $qualifications[$i] ?? ''
            );


        $specialization =
            trim(
                $specializations[$i] ?? ''
            );


        $institute =
            trim(
                $institutes[$i] ?? ''
            );


        $board =
            trim(
                $boards[$i] ?? ''
            );


        $passingYear =
            trim(
                $passing_years[$i] ?? ''
            );


        $percentage =
            trim(
                $percentages[$i] ?? ''
            );


        $status =
            trim(
                $statuses[$i] ?? ''
            );


        /*
        =====================================
        REQUIRED FIELDS
        =====================================
        */

        if (
            $qualification === '' ||
            $institute === ''
        ) {

            throw new Exception(
                "Qualification and Institute are required."
            );
        }


        /*
        =====================================
        VALIDATE PASSING YEAR
        =====================================
        */

        if ($passingYear !== '') {

            if (
                !ctype_digit($passingYear) ||
                (int)$passingYear < 1950 ||
                (int)$passingYear > 2100
            ) {

                throw new Exception(
                    "Please enter a valid passing year."
                );
            }

        } else {

            $passingYear = null;

        }


        /*
        =====================================
        VALIDATE PERCENTAGE
        =====================================
        */

        if ($percentage !== '') {

            if (
                !is_numeric($percentage) ||
                (float)$percentage < 0 ||
                (float)$percentage > 100
            ) {

                throw new Exception(
                    "Percentage must be between 0 and 100."
                );
            }

        } else {

            $percentage = null;

        }


        /*
        =====================================
        VALIDATE STATUS
        =====================================
        */

        $allowedStatuses = [
            "Completed",
            "Pursuing"
        ];


        if (
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {

            throw new Exception(
                "Invalid education status."
            );
        }


        /*
        =====================================
        EXISTING ROW → UPDATE
        =====================================
        */

        if ($educationId > 0) {


            /*
            Security check:
            This ID must belong to this user.
            */

            if (
                !in_array(
                    $educationId,
                    $existingIds,
                    true
                )
            ) {

                throw new Exception(
                    "Invalid education record."
                );
            }


            $updateStmt->execute([

                $qualification,

                $specialization,

                $institute,

                $board,

                $passingYear,

                $percentage,

                $status,

                $educationId,

                $user_id

            ]);


            $submittedExistingIds[] =
                $educationId;

        }


        /*
        =====================================
        NEW ROW → INSERT
        =====================================
        */

        else {


            $insertStmt->execute([

                $user_id,

                $qualification,

                $specialization,

                $institute,

                $board,

                $passingYear,

                $percentage,

                $status

            ]);

        }

    }


    /*
    =========================================
    10. DELETE REMOVED EDUCATION ROWS
    =========================================

    Example:

    Database IDs:
    10, 11, 12

    User removes row 11.

    Browser sends:
    10, 12

    So ID 11 will be deleted.
    =========================================
    */

    $idsToDelete =
        array_diff(
            $existingIds,
            $submittedExistingIds
        );


    if (!empty($idsToDelete)) {


        $deleteStmt =
            $pdo->prepare("

                DELETE FROM education

                WHERE id = ?

                AND user_id = ?

            ");


        foreach (
            $idsToDelete
            as $deleteId
        ) {


            $deleteStmt->execute([

                (int)$deleteId,

                $user_id

            ]);

        }

    }


    /*
    =========================================
    11. COMMIT EVERYTHING
    =========================================
    */

    $pdo->commit();


    echo json_encode([

        "success" => true,

        "message" =>
            "Education details updated successfully."

    ]);


    exit;


}
catch (Throwable $e) {


    /*
    Roll back all changes if anything fails.
    */

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    echo json_encode([

        "success" => false,

        "message" =>
            $e->getMessage()

    ]);


    exit;

}