<?php
/**
 * PRAYAS Platform - Dashboard Statistics API
 */

require_once dirname(__DIR__) . '/config/app.php';

sendCorsHeaders();
requireAdmin();

$pdo = getDb();

try {
    // Total Enquiries
    $totalEnquiries = (int)$pdo->query("SELECT COUNT(*) FROM enquiries")->fetchColumn();
    $newEnquiries = (int)$pdo->query("SELECT COUNT(*) FROM enquiries WHERE status = 'new'")->fetchColumn();

    // Enquiries by Role
    $roleStmt = $pdo->query("SELECT role, COUNT(*) as count FROM enquiries GROUP BY role ORDER BY count DESC");
    $byRole = $roleStmt->fetchAll();

    // Enquiries by Status
    $statusStmt = $pdo->query("SELECT status, COUNT(*) as count FROM enquiries GROUP BY status");
    $byStatus = $statusStmt->fetchAll();

    // Total Candidates
    $totalCandidates = 0;
    try {
        $totalCandidates = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    } catch (Exception $e) {}

    // Recent 5 Enquiries
    $recentStmt = $pdo->query("SELECT id, name, email, phone, role, status, created_at FROM enquiries ORDER BY created_at DESC LIMIT 5");
    $recentEnquiries = $recentStmt->fetchAll();

    jsonResponse([
        'status' => 'success',
        'data' => [
            'totalEnquiries' => $totalEnquiries,
            'newEnquiries' => $newEnquiries,
            'totalCandidates' => $totalCandidates,
            'byRole' => $byRole,
            'byStatus' => $byStatus,
            'recentEnquiries' => $recentEnquiries
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error in stats API: " . $e->getMessage());
    jsonResponse(['status' => 'error', 'message' => 'Failed to retrieve stats.'], 500);
}
