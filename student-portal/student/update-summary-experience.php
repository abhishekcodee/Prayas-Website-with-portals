<?php

session_start();

header("Content-Type: application/json");

require_once "../db.php";


/* =========================================
   JSON RESPONSE HELPER
========================================= */

function sendResponse(
    bool $success,
    string $message,
    array $extra = []
): void {

    echo json_encode(
        array_merge(
            [
                "success" => $success,
                "message" => $message
            ],
            $extra
        )
    );

    exit;
}


/* =========================================
   1. CHECK LOGIN
========================================= */

if (!isset($_SESSION['user_id'])) {

    sendResponse(
        false,
        "Session expired. Please login again."
    );

}


$user_id = (int) $_SESSION['user_id'];


/* =========================================
   2. CHECK REQUEST METHOD
========================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    sendResponse(
        false,
        "Invalid request method."
    );

}


/* =========================================
   3. CHECK FRESHER STATUS
========================================= */

$isFresher =

    isset($_POST['is_fresher']) &&

    (string)$_POST['is_fresher'] === "1";


try {


    /* =====================================
       START TRANSACTION
    ===================================== */

    $pdo->beginTransaction();


    /* =====================================
       4. USER SELECTED FRESHER
    ===================================== */

    if ($isFresher) {


        /*
         If user becomes Fresher,
         remove all existing experience.
        */

        $deleteStmt = $pdo->prepare("

            DELETE FROM experience

            WHERE user_id = ?

        ");


        $deleteStmt->execute([

            $user_id

        ]);


        $pdo->commit();


        sendResponse(

            true,

            "Experience updated successfully.",

            [
                "fresher" => true
            ]

        );

    }


    /* =====================================
       5. GET FORM ARRAYS
    ===================================== */

    $experienceIds =

        $_POST['experience_id'] ?? [];


    $companyNames =

        $_POST['company_name'] ?? [];


    $designations =

        $_POST['designation'] ?? [];


    $startDates =

        $_POST['start_date'] ?? [];


    $endDates =

        $_POST['end_date'] ?? [];


    $currentlyWorking =

        $_POST['currently_working'] ?? [];


/* =========================================
   6. VALIDATE ARRAY TYPES
========================================= */

    if (

        !is_array($experienceIds) ||

        !is_array($companyNames) ||

        !is_array($designations) ||

        !is_array($startDates) ||

        !is_array($endDates) ||

        !is_array($currentlyWorking)

    ) {

        throw new Exception(

            "Invalid experience data."

        );

    }


    /* =====================================
       TOTAL EXPERIENCE ROWS
    ===================================== */

    $totalRows =

        count($companyNames);


    /*
     User is not Fresher,
     therefore at least one experience
     must exist.
    */

    if ($totalRows === 0) {

        throw new Exception(

            "Please add at least one experience or select Fresher."

        );

    }


    /* =====================================
       7. CHECK ARRAY ALIGNMENT
    ===================================== */

    if (

        count($experienceIds) !== $totalRows ||

        count($designations) !== $totalRows ||

        count($startDates) !== $totalRows

    ) {

        throw new Exception(

            "Experience form data is incomplete."

        );

    }


    /* =====================================
       8. GET EXISTING EXPERIENCE IDS
    ===================================== */

    $existingStmt = $pdo->prepare("

        SELECT id

        FROM experience

        WHERE user_id = ?

    ");


    $existingStmt->execute([

        $user_id

    ]);


    $existingIds =

        $existingStmt->fetchAll(

            PDO::FETCH_COLUMN

        );


    $existingIds =

        array_map(

            'intval',

            $existingIds

        );


    /*
     Store IDs which user keeps.

     Any old database ID not present here
     will be deleted later.
    */

    $submittedExistingIds = [];


/* =========================================
   9. PREPARE UPDATE QUERY
========================================= */

    $updateStmt = $pdo->prepare("

        UPDATE experience

        SET

            company_name = ?,

            designation = ?,

            start_date = ?,

            end_date = ?,

            currently_working = ?

        WHERE id = ?

        AND user_id = ?

    ");


/* =========================================
   10. PREPARE INSERT QUERY
========================================= */

    $insertStmt = $pdo->prepare("

        INSERT INTO experience

        (

            user_id,

            company_name,

            designation,

            start_date,

            end_date,

            currently_working

        )

        VALUES

        (

            ?, ?, ?, ?, ?, ?

        )

    ");


/* =========================================
   11. PROCESS EACH EXPERIENCE ROW
========================================= */

    for (

        $i = 0;

        $i < $totalRows;

        $i++

    ) {


        /* =================================
           EXPERIENCE ID
        ================================= */

        $experienceId =

            isset($experienceIds[$i])

            ? (int)$experienceIds[$i]

            : 0;


        /* =================================
           COMPANY
        ================================= */

        $companyName =

            trim(

                (string)(

                    $companyNames[$i] ?? ''

                )

            );


        /* =================================
           DESIGNATION
        ================================= */

        $designation =

            trim(

                (string)(

                    $designations[$i] ?? ''

                )

            );


        /* =================================
           START DATE
        ================================= */

        $startDate =

            trim(

                (string)(

                    $startDates[$i] ?? ''

                )

            );


        /* =================================
           CURRENTLY WORKING

           IMPORTANT FIX:

           "0" = Not currently working
           "1" = Currently working

           Sirf isset() check nahi karenge,
           warna "0" bhi true samjha ja sakta hai.
        ================================= */

        $isCurrentlyWorking =
    isset($currentlyWorking[$i]) &&
    (string)$currentlyWorking[$i] === "1"
        ? 1
        : 0;


        /* =================================
           END DATE
        ================================= */

        if ($isCurrentlyWorking === 1) {


            /*
             Currently Working means
             no end date.
            */

            $endDate = null;


        }
        else {


            $endDate =

                trim(

                    (string)(

                        $endDates[$i] ?? ''

                    )

                );


            if ($endDate === '') {

                $endDate = null;

            }

        }


        /* =================================
           12. REQUIRED VALIDATION
        ================================= */

        if ($companyName === '') {

            throw new Exception(

                "Company name is required."

            );

        }


        if ($designation === '') {

            throw new Exception(

                "Designation is required."

            );

        }


        if ($startDate === '') {

            throw new Exception(

                "Start date is required."

            );

        }


        /* =================================
           13. VALIDATE START DATE
        ================================= */

        $startDateObject =

            DateTime::createFromFormat(

                'Y-m-d',

                $startDate

            );


        if (

            !$startDateObject ||

            $startDateObject->format('Y-m-d')

                !== $startDate

        ) {

            throw new Exception(

                "Invalid start date."

            );

        }


        /* =================================
           14. VALIDATE END DATE
        ================================= */

        if (

            $isCurrentlyWorking === 0 &&

            $endDate !== null

        ) {


            $endDateObject =

                DateTime::createFromFormat(

                    'Y-m-d',

                    $endDate

                );


            if (

                !$endDateObject ||

                $endDateObject->format('Y-m-d')

                    !== $endDate

            ) {

                throw new Exception(

                    "Invalid end date."

                );

            }


            /*
             End date cannot be
             before start date.
            */

            if (

                $endDateObject <

                $startDateObject

            ) {

                throw new Exception(

                    "End date cannot be before start date."

                );

            }

        }


        /* =================================
           15. EXISTING RECORD → UPDATE
        ================================= */

        if ($experienceId > 0) {


            /*
             Security check:

             Experience ID must belong
             to logged-in user.
            */

            if (

                !in_array(

                    $experienceId,

                    $existingIds,

                    true

                )

            ) {

                throw new Exception(

                    "Invalid experience record."

                );

            }


            $updateStmt->execute([


                $companyName,


                $designation,


                $startDate,


                $endDate,


                $isCurrentlyWorking,


                $experienceId,


                $user_id


            ]);


            /*
             Keep this ID.

             It should NOT be deleted later.
            */

            $submittedExistingIds[] =

                $experienceId;

        }


        /* =================================
           16. NEW RECORD → INSERT
        ================================= */

        else {


            $insertStmt->execute([


                $user_id,


                $companyName,


                $designation,


                $startDate,


                $endDate,


                $isCurrentlyWorking


            ]);

        }

    }


    /* =====================================
       17. DELETE REMOVED EXPERIENCES
    ===================================== */

    $idsToDelete =

        array_diff(

            $existingIds,

            $submittedExistingIds

        );


    if (!empty($idsToDelete)) {


        $deleteStmt = $pdo->prepare("

            DELETE FROM experience

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


    /* =====================================
       18. COMMIT EVERYTHING
    ===================================== */

    $pdo->commit();


    sendResponse(

        true,

        "Experience updated successfully.",

        [
            "fresher" => false
        ]

    );


}
catch (Throwable $e) {


    /* =====================================
       ROLLBACK IF ANYTHING FAILED
    ===================================== */

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    sendResponse(

        false,

        $e->getMessage()

    );

}