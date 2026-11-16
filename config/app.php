<?php
/**
 * PRAYAS Platform - Core Application Configuration & Security Helpers
 */

require_once __DIR__ . '/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    // Secure session cookie params
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// App definitions
define('APP_NAME', getenv('APP_NAME') ?: "PRAYAS - Building India's Manufacturing Workforce");
define('APP_URL', getenv('APP_URL') ?: "http://localhost:8000");
define('APP_ENV', getenv('APP_ENV') ?: "development");
define('NOTIFY_EMAILS', getenv('NOTIFY_EMAILS') ?: "admissions@prayas.org");

/**
 * Send CORS and JSON response headers
 */
function sendCorsHeaders(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

/**
 * Return JSON response
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Sanitize string or array recursively
 */
function sanitizeInput(mixed $data): mixed {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value);
        }
        return $data;
    }
    if (is_string($data)) {
        return htmlspecialchars(trim(strip_tags($data)), ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

/**
 * Read request payload (supports JSON body, form POST, or query params)
 */
function getRequestData(): array {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

    if (strpos($contentType, 'application/json') !== false || strpos($contentType, 'text/plain') !== false) {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }
    }

    if ($method === 'POST') {
        return $_POST;
    }

    if ($method === 'PUT' || $method === 'PATCH' || $method === 'DELETE') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (is_array($data)) {
            return $data;
        }
        parse_str($raw, $parsed);
        return is_array($parsed) ? $parsed : [];
    }

    return $_GET;
}

/**
 * Check if current user is logged in as admin
 */
function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']) || !empty($_SESSION['admin_logged_in']);
}

/**
 * Require admin authentication
 */
function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            jsonResponse(['status' => 'error', 'message' => 'Unauthorized. Admin session required.'], 401);
        }
        header("Location: /admin/login.php");
        exit;
    }
}

/**
 * CSRF token generation and validation
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Send Notification / Log Enquiry
 */
function notifyEnquiry(array $enquiry): bool {
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $logEntry = sprintf(
        "[%s] NEW ENQUIRY #%s | %s (%s) | Role: %s | Org: %s | Message: %s\n",
        date('Y-m-d H:i:s'),
        $enquiry['id'] ?? 'N/A',
        $enquiry['name'] ?? '',
        $enquiry['email'] ?? '',
        $enquiry['role'] ?? '',
        $enquiry['organisation'] ?? '',
        str_replace(["\r", "\n"], ' ', $enquiry['message'] ?? '')
    );
    file_put_contents($logDir . '/enquiries.log', $logEntry, FILE_APPEND);

    // Try PHPMailer if configured
    $smtpHost = getenv('SMTP_HOST');
    $smtpUser = getenv('SMTP_USER');
    $smtpPass = getenv('SMTP_PASS');

    if (!empty($smtpHost) && !empty($smtpUser) && !empty($smtpPass)) {
        $vendorAutoload = dirname(__DIR__) . '/student-portal/vendor/autoload.php';
        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                try {
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = $smtpHost;
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtpUser;
                    $mail->Password = $smtpPass;
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = (int)(getenv('SMTP_PORT') ?: 587);

                    $fromEmail = getenv('SMTP_FROM') ?: 'admissions@prayas.org';
                    $fromName = getenv('SMTP_FROM_NAME') ?: 'PRAYAS Admissions';
                    $mail->setFrom($fromEmail, $fromName);

                    $recipients = explode(',', NOTIFY_EMAILS);
                    foreach ($recipients as $r) {
                        $r = trim($r);
                        if (!empty($r)) {
                            $mail->addAddress($r);
                        }
                    }

                    $mail->isHTML(true);
                    $mail->Subject = "New PRAYAS Enquiry — " . ($enquiry['role'] ?? 'General') . " — " . ($enquiry['name'] ?? 'Candidate');
                    $mail->Body = "<h3>New PRAYAS Website Enquiry</h3>" .
                        "<p><strong>Name:</strong> " . htmlspecialchars($enquiry['name'] ?? '') . "</p>" .
                        "<p><strong>Email:</strong> " . htmlspecialchars($enquiry['email'] ?? '') . "</p>" .
                        "<p><strong>Phone:</strong> " . htmlspecialchars($enquiry['phone'] ?? '') . "</p>" .
                        "<p><strong>Organisation:</strong> " . htmlspecialchars($enquiry['organisation'] ?? '-') . "</p>" .
                        "<p><strong>Role:</strong> " . htmlspecialchars($enquiry['role'] ?? '-') . "</p>" .
                        "<p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($enquiry['message'] ?? '')) . "</p>" .
                        "<hr><p><small>Sent via PRAYAS Website Backend</small></p>";

                    $mail->send();
                    return true;
                } catch (\Exception $e) {
                    error_log("Mail send error: " . $e->getMessage());
                }
            }
        }
    }

    return true;
}
