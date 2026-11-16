<?php
/**
 * PRAYAS Platform - Authentication API
 */

require_once dirname(__DIR__) . '/config/app.php';

sendCorsHeaders();

$action = $_GET['action'] ?? 'status';
$pdo = getDb();

switch ($action) {
    case 'login':
        handleLogin($pdo);
        break;

    case 'logout':
        handleLogout();
        break;

    case 'status':
    default:
        handleStatus();
        break;
}

function handleLogin(PDO $pdo): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['status' => 'error', 'message' => 'POST method required.'], 405);
    }

    $data = getRequestData();
    $email = trim($data['email'] ?? ($data['username'] ?? ''));
    $password = trim($data['password'] ?? '');

    if (empty($email) || empty($password)) {
        jsonResponse(['status' => 'error', 'message' => 'Email and password are required.'], 422);
    }

    try {
        // 1. Try Admin Login
        $stmt = $pdo->prepare("SELECT id, name, email, password, role, status FROM admin WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            if (($admin['status'] ?? 'active') !== 'active') {
                jsonResponse(['status' => 'error', 'message' => 'Your admin account is currently inactive.'], 403);
            }

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_logged_in'] = true;

            // Update last_login
            $pdo->prepare("UPDATE admin SET last_login = CURRENT_TIMESTAMP WHERE id = ?")->execute([$admin['id']]);

            jsonResponse([
                'status' => 'success',
                'message' => 'Admin login successful.',
                'role' => 'admin',
                'user' => [
                    'id' => $admin['id'],
                    'name' => $admin['name'],
                    'email' => $admin['email'],
                    'role' => $admin['role']
                ],
                'redirect' => '/admin/dashboard.php'
            ]);
        }

        // 2. Try Student User Login
        $stmt = $pdo->prepare("SELECT id, full_name, email, mobile, password, status, current_step FROM users WHERE email = ? OR mobile = ? LIMIT 1");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch();

        if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
            if ($user['status'] === 'Blocked') {
                jsonResponse(['status' => 'error', 'message' => 'Your student account has been blocked.'], 403);
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_mobile'] = $user['mobile'];
            $_SESSION['user_logged_in'] = true;

            jsonResponse([
                'status' => 'success',
                'message' => 'Login successful.',
                'role' => 'student',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['full_name'],
                    'email' => $user['email'],
                    'current_step' => $user['current_step']
                ],
                'redirect' => '/student-portal/dashboard.php'
            ]);
        }

        jsonResponse(['status' => 'error', 'message' => 'Invalid email or password.'], 401);

    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        jsonResponse(['status' => 'error', 'message' => 'An error occurred during authentication.'], 500);
    }
}

function handleLogout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    jsonResponse(['status' => 'success', 'message' => 'Logged out successfully.']);
}

function handleStatus(): void {
    if (isAdminLoggedIn()) {
        jsonResponse([
            'status' => 'success',
            'authenticated' => true,
            'role' => 'admin',
            'user' => [
                'id' => $_SESSION['admin_id'],
                'name' => $_SESSION['admin_name'],
                'email' => $_SESSION['admin_email'],
                'role' => $_SESSION['admin_role']
            ]
        ]);
    }

    if (!empty($_SESSION['user_id'])) {
        jsonResponse([
            'status' => 'success',
            'authenticated' => true,
            'role' => 'student',
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email']
            ]
        ]);
    }

    jsonResponse([
        'status' => 'success',
        'authenticated' => false
    ]);
}
