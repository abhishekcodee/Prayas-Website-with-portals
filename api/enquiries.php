<?php
/**
 * PRAYAS Platform - Enquiries List & Filter API
 */

require_once dirname(__DIR__) . '/config/app.php';

sendCorsHeaders();
requireAdmin();

$pdo = getDb();

try {
    $search = trim($_GET['search'] ?? '');
    $role = trim($_GET['role'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if (!empty($search)) {
        $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR organisation LIKE ?)";
        $term = "%$search%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    if (!empty($role)) {
        $where[] = "role = ?";
        $params[] = $role;
    }

    if (!empty($status)) {
        $where[] = "status = ?";
        $params[] = $status;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Total count
    $countSql = "SELECT COUNT(*) FROM enquiries $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Data query
    $dataSql = "SELECT id, name, email, phone, organisation, role, message, status, notes, created_at 
                FROM enquiries 
                $whereClause 
                ORDER BY created_at DESC 
                LIMIT $limit OFFSET $offset";
    $dataStmt = $pdo->prepare($dataSql);
    $dataStmt->execute($params);
    $records = $dataStmt->fetchAll();

    jsonResponse([
        'status' => 'success',
        'data' => $records,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit)
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error in enquiries list API: " . $e->getMessage());
    jsonResponse(['status' => 'error', 'message' => 'Failed to retrieve enquiries.'], 500);
}
