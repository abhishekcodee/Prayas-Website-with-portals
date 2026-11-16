<?php
/**
 * Logout Endpoint
 * Organization: PRAYAS – The Automation Academy
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

$auth = new AuthController();
$auth->logout();

header("Location: /views/auth/login.php?success=" . urlencode("You have been signed out successfully."));
exit;

