<?php
/**
 * Industry Requirements Directory Gateway
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';

$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: " . app_url('/views/industry/requirements.php' . $qs));
exit;
