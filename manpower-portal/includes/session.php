<?php
/**
 * Session Security & Lifecycle Management
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

if (!defined('SESSION_TIMEOUT_SECONDS')) {
    define('SESSION_TIMEOUT_SECONDS', 1800); // 30 minutes idle timeout
}

function initSecureSession(): void
{
    if (!ob_get_level()) {
        ob_start(null, 0);
    }

    if (session_status() === PHP_SESSION_NONE) {
        // Enforce cookie security parameters
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
                || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax' // Lax supports standard form POST redirects
        ]);

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');

        session_start();
    }

    // Verify Idle Session Timeout for authenticated sessions
    if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
        $idleTime = time() - $_SESSION['last_activity'];
        if ($idleTime > SESSION_TIMEOUT_SECONDS) {
            destroySecureSession();
            header("Location: /views/auth/login.php?error=" . urlencode("Your session has expired due to inactivity. Please log in again."));
            exit;
        }
    }

    // Update activity timestamp
    if (isset($_SESSION['user_id'])) {
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Universal Base URL Helper
 * Returns correct URL paths for both root domains (http://localhost:8000/) 
 * and arbitrary subdirectories (e.g. http://localhost/Manpower Portal/)
 */
function app_url(string $path = ''): string
{
    static $base = null;
    if ($base === null) {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $pos = strpos($script, '/views/');
        if ($pos === false) $pos = strpos($script, '/api/');
        if ($pos === false) $pos = strpos($script, '/assets/');
        if ($pos === false) $pos = strpos($script, '/index.php');
        if ($pos === false) $pos = strpos($script, '/cron/');
        if ($pos === false) $pos = strpos($script, '/tests/');
        $base = ($pos !== false) ? substr($script, 0, $pos) : '';
        $base = rtrim($base, '/');
    }
    return $base . '/' . ltrim($path, '/');
}

function destroySecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];

        if (!headers_sent() && ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }
}

// Auto-initialize session when included
initSecureSession();
