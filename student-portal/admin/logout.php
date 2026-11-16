<?php
session_start();

// Admin session destroy
session_unset();
session_destroy();

// Redirect to unified main portal login page
header("Location: ../login.php");
exit;
?>