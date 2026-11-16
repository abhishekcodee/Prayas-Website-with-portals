<?php
/**
 * Root Login Redirect Gateway
 * Organization: PRAYAS – The Automation Academy
 */

require_once __DIR__ . '/includes/session.php';

header("Location: " . app_url('/views/auth/login.php'));
exit;
