<?php
/**
 * PRAYAS Platform - Export Enquiries to CSV
 */

require_once dirname(__DIR__) . '/config/app.php';

requireAdmin();

$pdo = getDb();

try {
    $search = trim($_GET['search'] ?? '');
    $role = trim($_GET['role'] ?? '');
    $status = trim($_GET['status'] ?? '');

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
    $sql = "SELECT id, name, email, phone, organisation, role, message, status, notes, ip_address, created_at 
            FROM enquiries 
            $whereClause 
            ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "prayas_enquiries_" . date('Y-m-d_His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    // Output UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // CSV Headers
    fputcsv($output, ['ID', 'Full Name', 'Email', 'Phone', 'Organisation', 'Role', 'Message', 'Status', 'Admin Notes', 'IP Address', 'Submitted At']);

    foreach ($rows as $r) {
        fputcsv($output, [
            $r['id'],
            $r['name'],
            $r['email'],
            $r['phone'],
            $r['organisation'],
            $r['role'],
            $r['message'],
            ucfirst($r['status']),
            $r['notes'],
            $r['ip_address'],
            $r['created_at']
        ]);
    }

    fclose($output);
    exit;

} catch (PDOException $e) {
    echo "Export Error: " . $e->getMessage();
    exit;
}
