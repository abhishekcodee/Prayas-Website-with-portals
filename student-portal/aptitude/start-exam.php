<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../db.php";

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
SELECT profile_confirmed
FROM users
WHERE id=?
");

$stmt->execute([$user_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['profile_confirmed'] == 0) {
    header("Location: ../student/candidate-summary.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Online Aptitude Assessment | PRAYAS
</title>


<!-- BOOTSTRAP -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- FONT AWESOME -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
>


<!-- GOOGLE FONT -->

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>


<style>

/* =========================================
   GLOBAL
========================================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{

    font-family:"Inter",sans-serif;

    background:
        linear-gradient(
            135deg,
            #f8fbff 0%,
            #f4f7fc 50%,
            #fff7f7 100%
        );

    color:#0f172a;

    min-height:100vh;

}


/* =========================================
   TOP HEADER
========================================= */


.brand-icon{

    width:44px;
    height:44px;

    border-radius:12px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:
        linear-gradient(
            135deg,
            #ef6b6b,
            #e25555
        );

    color:#ffffff;

    font-size:20px;

}


.brand-title{

    font-size:20px;

    font-weight:800;

    color:#0f172a;

    margin:0;

}


.brand-subtitle{

    font-size:12px;

    color:#64748b;

    margin-top:2px;

}


.step-badge{

    background:#fff1f1;

    color:#e25555;

    border:1px solid #ffd7d7;

    padding:8px 14px;

    border-radius:30px;

    font-size:13px;

    font-weight:700;

}


/* =========================================
   PAGE WRAPPER
========================================= */

.page-wrapper{

    max-width:1000px;

    margin:0 auto;

    padding:55px 20px 70px;

}


/* =========================================
   PAGE INTRO
========================================= */

.page-intro{

    text-align:center;

    margin-bottom:32px;

}


.assessment-icon{

    width:68px;
    height:68px;

    margin:0 auto 18px;

    border-radius:20px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:
        linear-gradient(
            135deg,
            #ef6b6b,
            #e25555
        );

    color:#ffffff;

    font-size:28px;

    box-shadow:
        0 12px 28px
        rgba(239,107,107,.25);

}


.page-intro h1{

    font-size:30px;

    font-weight:800;

    margin-bottom:8px;

    color:#0f172a;

}


.page-intro p{

    color:#64748b;

    font-size:15px;

    margin:0;

}


/* =========================================
   MAIN CARD
========================================= */

.assessment-card{

    background:#ffffff;

    border:1px solid #e8edf4;

    border-radius:22px;

    overflow:hidden;

    box-shadow:
        0 18px 45px
        rgba(15,23,42,.08);

}


/* =========================================
   CARD HEADER
========================================= */

.assessment-header{

    padding:26px 30px;

    background:
        linear-gradient(
            135deg,
            #0f172a,
            #1e293b
        );

    color:#ffffff;

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

}


.assessment-header h2{

    font-size:20px;

    font-weight:700;

    margin:0 0 5px;

}


.assessment-header p{

    margin:0;

    color:#cbd5e1;

    font-size:13px;

}


.secure-badge{

    white-space:nowrap;

    background:
        rgba(255,255,255,.1);

    border:
        1px solid
        rgba(255,255,255,.15);

    padding:9px 13px;

    border-radius:10px;

    font-size:12px;

    font-weight:600;

}


/* =========================================
   BODY
========================================= */

.assessment-body{

    padding:32px;

}


.section-heading{

    display:flex;

    align-items:center;

    gap:10px;

    margin-bottom:20px;

}


.section-heading i{

    color:#ef6b6b;

    font-size:20px;

}


.section-heading h3{

    font-size:18px;

    font-weight:750;

    margin:0;

}


/* =========================================
   QUICK STATS
========================================= */

.stats-grid{

    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:16px;

    margin-bottom:30px;

}


.stat-box{

    border:1px solid #e5eaf1;

    background:#f8fafc;

    border-radius:14px;

    padding:18px;

    display:flex;

    align-items:center;

    gap:13px;

}


.stat-icon{

    width:42px;
    height:42px;

    flex-shrink:0;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:11px;

    background:#fff1f1;

    color:#ef6b6b;

    font-size:17px;

}


.stat-value{

    font-size:17px;

    font-weight:800;

    color:#0f172a;

}


.stat-label{

    color:#64748b;

    font-size:12px;

    margin-top:2px;

}


/* =========================================
   INSTRUCTIONS
========================================= */

.instructions{

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:13px;

}


.instruction-item{

    display:flex;

    align-items:flex-start;

    gap:12px;

    padding:15px;

    border:1px solid #e8edf4;

    border-radius:12px;

    background:#ffffff;

}


.instruction-number{

    width:28px;
    height:28px;

    flex-shrink:0;

    border-radius:8px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:#fff1f1;

    color:#e25555;

    font-size:12px;

    font-weight:800;

}


.instruction-text{

    font-size:13px;

    color:#475569;

    line-height:1.55;

}


/* =========================================
   WARNING
========================================= */

.warning-box{

    margin-top:25px;

    padding:17px 18px;

    border-radius:13px;

    background:#fff8e7;

    border:1px solid #fde3a7;

    display:flex;

    gap:13px;

    align-items:flex-start;

}


.warning-box i{

    color:#d97706;

    font-size:18px;

    margin-top:2px;

}


.warning-title{

    font-weight:700;

    color:#92400e;

    font-size:14px;

    margin-bottom:3px;

}


.warning-text{

    color:#a16207;

    font-size:12px;

    line-height:1.5;

}


/* =========================================
   ACTION AREA
========================================= */

.action-area{

    margin-top:30px;

    padding-top:25px;

    border-top:1px solid #e8edf4;

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:20px;

}


.ready-text{

    font-size:13px;

    color:#64748b;

}


.ready-text strong{

    display:block;

    color:#0f172a;

    font-size:14px;

    margin-bottom:3px;

}


.start-btn{

    border:none;

    text-decoration:none;

    background:
        linear-gradient(
            135deg,
            #ef6b6b,
            #e25555
        );

    color:#ffffff;

    padding:13px 24px;

    border-radius:11px;

    font-size:14px;

    font-weight:700;

    display:inline-flex;

    align-items:center;

    gap:9px;

    box-shadow:
        0 8px 20px
        rgba(239,107,107,.25);

    transition:.25s;

}


.start-btn:hover{

    color:#ffffff;

    transform:
        translateY(-2px);

    box-shadow:
        0 12px 25px
        rgba(239,107,107,.32);

}


/* =========================================
   FOOTER NOTE
========================================= */

.footer-note{

    text-align:center;

    color:#94a3b8;

    font-size:12px;

    margin-top:20px;

}


/* =========================================
   RESPONSIVE
========================================= */

@media(max-width:768px){

    .page-wrapper{

        padding:
            35px 15px
            50px;

    }

    .page-intro h1{

        font-size:24px;

    }

    .stats-grid{

        grid-template-columns:1fr;

    }

    .instructions{

        grid-template-columns:1fr;

    }

    .assessment-header{

        align-items:flex-start;

        flex-direction:column;

    }

    .assessment-body{

        padding:22px 18px;

    }

    .action-area{

        flex-direction:column;

        align-items:stretch;

    }

    .start-btn{

        justify-content:center;

        width:100%;

    }

    .header-container{

        padding:0 15px;

    }

    .step-badge{

        display:none;

    }

}
/* =========================================
   PRAYAS HEADER - NO LOGO CUT
========================================= */


/* TEXT BESIDE LOGO */
.brand > div {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.brand-title {
    margin: 0;

    color: #0F172A;

    font-size: 17px;
    font-weight: 700;
    line-height: 1.25;
}

.brand-subtitle {
    margin-top: 5px;

    color: #94A3B8;

    font-size: 10px;
    font-weight: 500;
}

/* RIGHT BADGE */
.step-badge {
    flex-shrink: 0;

    display: inline-flex;
    align-items: center;

    background: #FFF1F1;
    color: #E25555;

    border: 1px solid #FFD7D7;

    padding: 9px 16px;
    border-radius: 30px;

    font-size: 13px;
    font-weight: 700;
}


/* ==========================
   TABLET
========================== */

@media (max-width: 768px) {

    .portal-header {
        height: 120px;
    }

    .header-container {
        height: 117px;
        padding: 5px 18px;
    }

    .header-logo {
        height: 105px;
        max-height: 105px;
        max-width: 100px;
    }

    .brand {
        gap: 13px;
    }

    .brand-title {
        font-size: 14px;
    }

    .brand-subtitle {
        font-size: 9px;
    }

    .step-badge {
        display: none;
    }
}


/* ==========================
   MOBILE
========================== */

@media (max-width: 576px) {

    .portal-header {
        height: 105px;
    }

    .header-container {
        height: 102px;
        padding: 4px 15px;
    }

    .header-logo {
        height: 90px;
        max-height: 90px;
        max-width: 85px;
    }

    .brand {
        gap: 10px;
    }

    .brand-title {
        font-size: 13px;
    }

    .brand-subtitle {
        font-size: 8px;
    }
}

/* =========================================
   PRAYAS HEADER - FINAL
========================================= */

.portal-header{
    width:100%;
    height:112px;
    padding:0;

    background:#ffffff;

    border-top:3px solid #EF6B6B;
    border-bottom:1px solid #E8EDF3;

    box-shadow:0 2px 12px rgba(15,23,42,.04);

    overflow:visible;
}

.header-container{
    width:100%;
    max-width:1240px;
    height:109px;

    margin:0 auto;
    padding:8px 28px;

    display:flex;
    align-items:center;
    justify-content:space-between;

    gap:30px;
}


/* =========================
   LEFT BRAND AREA
========================= */

.brand{
    height:100%;

    display:flex;
    align-items:center;

    gap:18px;

    overflow:visible;
}


/* =========================
   PRAYAS LOGO
========================= */

.header-logo{
    display:block;

    width:auto;
    height:92px;

    max-width:190px;
    max-height:92px;

    object-fit:contain;
    object-position:left center;

    flex-shrink:0;
}


/* =========================
   BRAND TEXT
========================= */

.brand > div{
    display:flex;
    flex-direction:column;
    justify-content:center;

    min-width:0;
}

.brand-title{
    margin:0;

    color:#0F172A;

    font-family:"Inter",sans-serif;

    font-size:17px;
    line-height:1.3;

    font-weight:700;

    letter-spacing:-.2px;
}

.brand-subtitle{
    margin-top:5px;

    color:#94A3B8;

    font-size:10px;
    line-height:1.4;

    font-weight:500;
}


/* Small Coral Accent */

.brand-subtitle::after{
    content:"";

    display:block;

    width:35px;
    height:3px;

    margin-top:8px;

    border-radius:20px;

    background:#EF6B6B;
}


/* =========================
   RIGHT BADGE
========================= */

.step-badge{
    flex-shrink:0;

    display:inline-flex;
    align-items:center;
    justify-content:center;

    gap:6px;

    min-height:38px;

    padding:8px 15px;

    border:1px solid #F4CACA;
    border-radius:30px;

    background:#FFF7F7;

    color:#D95757;

    font-size:12px;
    line-height:1;

    font-weight:650;

    box-shadow:none;
}

.step-badge i{
    font-size:12px;
    color:#EF6B6B;
}


/* =========================
   TABLET
========================= */

@media(max-width:768px){

    .portal-header{
        height:96px;
    }

    .header-container{
        height:93px;

        padding:6px 18px;
    }

    .header-logo{
        height:78px;

        max-width:155px;
        max-height:78px;
    }

    .brand{
        gap:13px;
    }

    .brand-title{
        font-size:14px;
    }

    .brand-subtitle{
        font-size:9px;
    }

    .step-badge{
        display:none;
    }

}


/* =========================
   MOBILE
========================= */

@media(max-width:576px){

    .portal-header{
        height:86px;
    }

    .header-container{
        height:83px;

        padding:5px 14px;
    }

    .header-logo{
        height:70px;

        max-width:135px;
        max-height:70px;
    }

    .brand{
        gap:10px;
    }

    .brand-title{
        font-size:12px;

        white-space:normal;
    }

    .brand-subtitle{
        margin-top:3px;

        font-size:8px;
    }

    .brand-subtitle::after{
        width:25px;
        height:2px;

        margin-top:5px;
    }

}


/* =========================
   VERY SMALL MOBILE
========================= */

@media(max-width:400px){

    .portal-header{
        height:78px;
    }

    .header-container{
        height:75px;

        padding:4px 10px;
    }

    .header-logo{
        height:62px;

        max-width:115px;
        max-height:62px;
    }

    .brand{
        gap:8px;
    }

    .brand-title{
        font-size:11px;
    }

    .brand-subtitle{
        font-size:7.5px;
    }

}
</style>

</head>


<body>


<!-- =====================================
     HEADER
====================================== -->

<header class="portal-header">
    <div class="header-container">

        <div class="brand">
            <img
                src="../assets/images/prayas-logo.png"
                alt="PRAYAS - The Automation Academy"
                class="header-logo"
            >
        </div>

        <div class="step-badge">
            <i class="fa-solid fa-clipboard-check me-1"></i>
            Aptitude Assessment
        </div>

    </div>
</header>



<!-- =====================================
     MAIN PAGE
====================================== -->

<main class="page-wrapper">


    <!-- PAGE TITLE -->

    <div class="page-intro">

        <div class="assessment-icon">

            <i class="fa-solid fa-clipboard-question"></i>

        </div>

        <h1>
            Online Aptitude Assessment
        </h1>

        <p>
            Please review the assessment guidelines carefully before you begin.
        </p>

    </div>



    <!-- ASSESSMENT CARD -->

    <div class="assessment-card">


        <!-- HEADER -->

        <div class="assessment-header">

            <div>

                <h2>
                    Assessment Instructions
                </h2>

                <p>
                    Make sure you understand all instructions before starting the test.
                </p>

            </div>


            <div class="secure-badge">

                <i class="fa-solid fa-shield-halved me-1"></i>

                Secure Assessment

            </div>

        </div>



        <!-- BODY -->

        <div class="assessment-body">


            <!-- QUICK OVERVIEW -->

            <div class="section-heading">

                <i class="fa-solid fa-chart-simple"></i>

                <h3>
                    Assessment Overview
                </h3>

            </div>


            <div class="stats-grid">


                <div class="stat-box">

                    <div class="stat-icon">

                        <i class="fa-solid fa-list-ol"></i>

                    </div>

                    <div>

                        <div class="stat-value">
                            15
                        </div>

                        <div class="stat-label">
                            Total Questions
                        </div>

                    </div>

                </div>


                <div class="stat-box">

                    <div class="stat-icon">

                        <i class="fa-solid fa-star"></i>

                    </div>

                    <div>

                        <div class="stat-value">
                            1 Mark
                        </div>

                        <div class="stat-label">
                            Per Question
                        </div>

                    </div>

                </div>


                <div class="stat-box">

                    <div class="stat-icon">

                        <i class="fa-solid fa-clock"></i>

                    </div>

                    <div>

                        <div class="stat-value">
                            30 Sec
                        </div>

                        <div class="stat-label">
                            Per Question
                        </div>

                    </div>

                </div>


            </div>



            <!-- INSTRUCTIONS -->

            <div class="section-heading">

                <i class="fa-solid fa-circle-info"></i>

                <h3>
                    Important Instructions
                </h3>

            </div>


            <div class="instructions">


                <div class="instruction-item">

                    <div class="instruction-number">
                        01
                    </div>

                    <div class="instruction-text">

                        The assessment contains
                        <strong>15 questions</strong>.

                    </div>

                </div>


                <div class="instruction-item">

                    <div class="instruction-number">
                        02
                    </div>

                    <div class="instruction-text">

                        Each correct answer carries
                        <strong>1 mark</strong>.

                    </div>

                </div>


                <div class="instruction-item">

                    <div class="instruction-number">
                        03
                    </div>

                    <div class="instruction-text">

                        There is
                        <strong>no negative marking</strong>
                        for incorrect answers.

                    </div>

                </div>


                <div class="instruction-item">

                    <div class="instruction-number">
                        04
                    </div>

                    <div class="instruction-text">

                        You will have
                        <strong>30 seconds</strong>
                        to answer each question.

                    </div>

                </div>


                <div class="instruction-item">

                    <div class="instruction-number">
                        05
                    </div>

                    <div class="instruction-text">

                        Do not switch browser tabs
                        while the assessment is active.

                    </div>

                </div>


                <div class="instruction-item">

                    <div class="instruction-number">
                        06
                    </div>

                    <div class="instruction-text">

                        Do not refresh or close the
                        assessment page during the test.

                    </div>

                </div>


                <div class="instruction-item">

                    <div class="instruction-number">
                        07
                    </div>

                    <div class="instruction-text">

                        Your answer will be submitted
                        automatically when the allotted
                        time expires.

                    </div>

                </div>


                <div class="instruction-item">

                    <div class="instruction-number">
                        08
                    </div>

                    <div class="instruction-text">

                        Ensure you have a stable internet
                        connection before starting.

                    </div>

                </div>


            </div>



            <!-- WARNING -->

            <div class="warning-box">

                <i class="fa-solid fa-triangle-exclamation"></i>

                <div>

                    <div class="warning-title">

                        Important Assessment Notice

                    </div>

                    <div class="warning-text">

                        Once you start the assessment,
                        avoid switching tabs, refreshing,
                        or closing the browser window.
                        Make sure you are ready before
                        proceeding.

                    </div>

                </div>

            </div>



            <!-- ACTION -->

            <div class="action-area">

                <div class="ready-text">

                    <strong>
                        Ready to begin?
                    </strong>

                    Start only when you are prepared
                    to complete the assessment.

                </div>


                <a
                    href="exam.php"
                    class="start-btn">

                    Start Assessment

                    <i class="fa-solid fa-arrow-right"></i>

                </a>

            </div>


        </div>

    </div>


    <div class="footer-note">

        PRAYAS · Student Recruitment Portal

    </div>


</main>


</body>

</html>