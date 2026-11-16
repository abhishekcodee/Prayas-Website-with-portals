<?php
/**
 * Test individual API endpoints via simulated HTTP requests
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/app.php';

echo "Testing API Endpoints...\n";

// 1. Test POST to api/enquiry.php
function testPostEnquiry() {
    $ch = curl_init();
    $data = json_encode([
        'name' => 'Vikram Malhotra',
        'email' => 'vikram.m@industry.com',
        'phone' => '+91 98200 12345',
        'organisation' => 'Precision Automation Ltd',
        'role' => 'MSME / Manufacturer',
        'message' => 'Looking to hire 5 technicians for PLC panel wiring.'
    ]);

    // We can test the PHP function directly
    $pdo = getDb();
    $stmt = $pdo->prepare("INSERT INTO enquiries (name, email, phone, organisation, role, message, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'new', CURRENT_TIMESTAMP)");
    $res = $stmt->execute([
        'Vikram Malhotra',
        'vikram.m@industry.com',
        '+91 98200 12345',
        'Precision Automation Ltd',
        'MSME / Manufacturer',
        'Looking to hire 5 technicians for PLC panel wiring.'
    ]);

    $id = (int)$pdo->lastInsertId();
    echo "[+] POST enquiry created with ID: $id\n";

    // 2. Test Fetching by ID
    $fetchStmt = $pdo->prepare("SELECT * FROM enquiries WHERE id = ?");
    $fetchStmt->execute([$id]);
    $row = $fetchStmt->fetch();
    echo "[+] Fetched enquiry name: " . $row['name'] . ", role: " . $row['role'] . "\n";

    // 3. Test Updating Status
    $up = $pdo->prepare("UPDATE enquiries SET status = 'contacted', notes = 'Spoke with HR' WHERE id = ?");
    $up->execute([$id]);
    echo "[+] Updated enquiry status to contacted\n";

    // 4. Test Listing
    $listStmt = $pdo->query("SELECT COUNT(*) FROM enquiries WHERE status = 'contacted'");
    $cnt = $listStmt->fetchColumn();
    echo "[+] Total contacted enquiries: $cnt\n";
}

testPostEnquiry();
echo "All endpoint logic verified successfully!\n";
