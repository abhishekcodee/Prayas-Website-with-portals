<?php
/**
 * PRAYAS Platform - Enquiry API Endpoint
 * Handles submission, retrieval, status update, and deletion of enquiries.
 */

require_once dirname(__DIR__) . '/config/app.php';

sendCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$data = getRequestData();
$pdo = getDb();

switch ($method) {
    case 'POST':
        handleCreateEnquiry($pdo, $data);
        break;

    case 'GET':
        handleGetEnquiry($pdo);
        break;

    case 'PUT':
    case 'PATCH':
        handleUpdateEnquiry($pdo, $data);
        break;

    case 'DELETE':
        handleDeleteEnquiry($pdo, $data);
        break;

    default:
        jsonResponse(['status' => 'error', 'message' => 'Method not allowed'], 405);
}

/**
 * Handle Enquiry Submission from Contact Form
 */
function handleCreateEnquiry(PDO $pdo, array $raw): void {
    $name = trim($raw['name'] ?? '');
    $email = trim($raw['email'] ?? '');
    $phone = trim($raw['phone'] ?? '');
    $organisation = trim($raw['organisation'] ?? '');
    $role = trim($raw['role'] ?? '');
    $message = trim($raw['message'] ?? '');

    // Validation
    $errors = [];
    if (empty($name)) {
        $errors[] = 'Full name is required.';
    } elseif (mb_strlen($name) > 150) {
        $errors[] = 'Full name must not exceed 150 characters.';
    }

    if (empty($email)) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    } else {
        // Allow digits, plus, hyphens, spaces
        $cleanPhone = preg_replace('/[^\d+]/', '', $phone);
        if (strlen($cleanPhone) < 7 || strlen($cleanPhone) > 20) {
            $errors[] = 'Please provide a valid phone number.';
        }
    }

    if (empty($role)) {
        $errors[] = 'Please select your role.';
    }

    if (empty($message)) {
        $errors[] = 'Please enter your message.';
    } elseif (mb_strlen($message) < 5) {
        $errors[] = 'Message must be at least 5 characters.';
    }

    if (!empty($errors)) {
        jsonResponse([
            'status' => 'error',
            'message' => implode(' ', $errors),
            'errors' => $errors
        ], 422);
    }

    // Input sanitization
    $cleanName = sanitizeInput($name);
    $cleanEmail = filter_var($email, FILTER_SANITIZE_EMAIL);
    $cleanPhone = sanitizeInput($phone);
    $cleanOrg = sanitizeInput($organisation);
    $cleanRole = sanitizeInput($role);
    $cleanMessage = sanitizeInput($message);

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

    try {
        $stmt = $pdo->prepare("INSERT INTO enquiries 
            (name, email, phone, organisation, role, message, status, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'new', ?, ?, CURRENT_TIMESTAMP)");

        $stmt->execute([
            $cleanName,
            $cleanEmail,
            $cleanPhone,
            $cleanOrg,
            $cleanRole,
            $cleanMessage,
            $ipAddress,
            $userAgent
        ]);

        $enquiryId = (int)$pdo->lastInsertId();

        $enquiryRecord = [
            'id' => $enquiryId,
            'name' => $cleanName,
            'email' => $cleanEmail,
            'phone' => $cleanPhone,
            'organisation' => $cleanOrg,
            'role' => $cleanRole,
            'message' => $cleanMessage
        ];

        // Trigger notification / email logging
        notifyEnquiry($enquiryRecord);

        jsonResponse([
            'status' => 'success',
            'message' => "Thanks — we've received your enquiry and will be in touch within 24 hours.",
            'data' => [
                'id' => $enquiryId,
                'name' => $cleanName,
                'email' => $cleanEmail
            ]
        ], 201);

    } catch (PDOException $e) {
        error_log("Database error in handleCreateEnquiry: " . $e->getMessage());
        jsonResponse([
            'status' => 'error',
            'message' => 'Unable to save enquiry at this moment. Please try again later.'
        ], 500);
    }
}

/**
 * Handle Single Enquiry Retrieval
 */
function handleGetEnquiry(PDO $pdo): void {
    requireAdmin();

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        jsonResponse(['status' => 'error', 'message' => 'Missing or invalid enquiry ID.'], 400);
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM enquiries WHERE id = ?");
        $stmt->execute([$id]);
        $enquiry = $stmt->fetch();

        if (!$enquiry) {
            jsonResponse(['status' => 'error', 'message' => 'Enquiry not found.'], 404);
        }

        jsonResponse(['status' => 'success', 'data' => $enquiry]);

    } catch (PDOException $e) {
        error_log("Error in handleGetEnquiry: " . $e->getMessage());
        jsonResponse(['status' => 'error', 'message' => 'Failed to fetch enquiry.'], 500);
    }
}

/**
 * Handle Enquiry Update (Status / Admin Notes)
 */
function handleUpdateEnquiry(PDO $pdo, array $data): void {
    requireAdmin();

    $id = filter_var($data['id'] ?? ($_GET['id'] ?? null), FILTER_VALIDATE_INT);
    if (!$id) {
        jsonResponse(['status' => 'error', 'message' => 'Missing or invalid enquiry ID.'], 400);
    }

    $allowedStatuses = ['new', 'in_progress', 'contacted', 'converted', 'closed'];
    $updates = [];
    $params = [];

    if (isset($data['status'])) {
        $status = strtolower(trim($data['status']));
        if (!in_array($status, $allowedStatuses, true)) {
            jsonResponse(['status' => 'error', 'message' => 'Invalid status value.'], 422);
        }
        $updates[] = "`status` = ?";
        $params[] = $status;
    }

    if (isset($data['notes'])) {
        $updates[] = "`notes` = ?";
        $params[] = sanitizeInput($data['notes']);
    }

    if (empty($updates)) {
        jsonResponse(['status' => 'error', 'message' => 'No valid fields provided to update.'], 400);
    }

    try {
        $params[] = $id;
        $sql = "UPDATE enquiries SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        jsonResponse([
            'status' => 'success',
            'message' => 'Enquiry updated successfully.'
        ]);

    } catch (PDOException $e) {
        error_log("Error in handleUpdateEnquiry: " . $e->getMessage());
        jsonResponse(['status' => 'error', 'message' => 'Failed to update enquiry.'], 500);
    }
}

/**
 * Handle Enquiry Deletion
 */
function handleDeleteEnquiry(PDO $pdo, array $data): void {
    requireAdmin();

    $id = filter_var($data['id'] ?? ($_GET['id'] ?? null), FILTER_VALIDATE_INT);
    if (!$id) {
        jsonResponse(['status' => 'error', 'message' => 'Missing or invalid enquiry ID.'], 400);
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM enquiries WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse([
            'status' => 'success',
            'message' => 'Enquiry deleted successfully.'
        ]);

    } catch (PDOException $e) {
        error_log("Error in handleDeleteEnquiry: " . $e->getMessage());
        jsonResponse(['status' => 'error', 'message' => 'Failed to delete enquiry.'], 500);
    }
}
