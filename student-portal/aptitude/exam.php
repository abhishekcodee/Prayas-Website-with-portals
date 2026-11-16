<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Exam</title>
</head>
<body>

<h2 style="text-align:center;margin-top:100px;">

Exam Page Coming Soon...

</h2>
<div class="text-center mt-5">

    <a href="../video/introduction.php" class="btn btn-success btn-lg">

        Submit Test & Continue

    </a>

</div>
</body>
</html>