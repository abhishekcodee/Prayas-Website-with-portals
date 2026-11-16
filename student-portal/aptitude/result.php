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
<title>Result</title>
</head>
<body>

<h2 style="text-align:center;margin-top:100px;">

Result Page Coming Soon...

</h2>

</body>
</html>