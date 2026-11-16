<?php
/**
 * Create Requirement Gateway
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';

header("Location: " . app_url('/views/industry/requirement_form.php'));
exit;
