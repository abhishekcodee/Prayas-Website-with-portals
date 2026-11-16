<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<script>
    localStorage.removeItem('prayas_student_user');
    window.location.href = "login.php";
</script>
</head>
<body>
</body>
</html>