<?php
/**
 * Cross-Site Request Forgery (CSRF) Protection
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/session.php';

function getCSRFToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function renderCSRFField(): string
{
    $token = htmlspecialchars(getCSRFToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function validateCSRFToken(?string $token = null): bool
{
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }

    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

function requireCSRF(): void
{
    if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'DELETE', 'PATCH'])) {
        if (!validateCSRFToken()) {
            http_response_code(403);
            require_once __DIR__ . '/../config/database.php';
            try {
                $pdo = Database::getConnection();
                $stmt = $pdo->prepare("INSERT INTO `audit_logs` (`user_id`, `action`, `ip_address`, `details_json`) 
                    VALUES (:uid, 'CSRF_VALIDATION_FAILED', :ip, :details)");
                $stmt->execute([
                    ':uid' => $_SESSION['user_id'] ?? null,
                    ':ip'  => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                    ':details' => json_encode(['uri' => $_SERVER['REQUEST_URI'] ?? '', 'method' => $_SERVER['REQUEST_METHOD'] ?? ''])
                ]);
            } catch (Exception $e) {
                // Ignore audit log failure during security termination
            }

            echo '<!DOCTYPE html><html><head><title>403 Forbidden - CSRF Error</title>';
            echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">';
            echo '</head><body class="bg-light d-flex align-items-center min-vh-100">';
            echo '<div class="container text-center"><div class="card p-5 shadow-sm mx-auto" style="max-width:500px;">';
            echo '<h2 class="text-danger mb-3">Security Validation Failed</h2>';
            echo '<p class="text-muted">Your session token has expired or is invalid (CSRF Protection). Please return to the form and try again.</p>';
            echo '<a href="javascript:history.back()" class="btn btn-primary mt-3">Go Back</a>';
            echo '</div></div></body></html>';
            exit;
        }
    }
}

