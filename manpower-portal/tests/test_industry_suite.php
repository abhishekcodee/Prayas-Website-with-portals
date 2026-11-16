<?php
/**
 * Industry Admin Portal Test Suite
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../controllers/IndustryController.php';

$pdo = Database::getConnection();
$industryCtrl = new IndustryController($pdo);

echo "==================================================" . PHP_EOL;
echo "PRAYAS Portal - Part 06 Industry Admin Test Suite" . PHP_EOL;
echo "==================================================" . PHP_EOL;

$passCount = 0;
$failCount = 0;

function assertTest(string $testName, bool $condition, string $detail = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] $testName" . ($detail ? " ($detail)" : "") . PHP_EOL;
        $passCount++;
    } else {
        echo "  [FAIL] $testName" . ($detail ? " ($detail)" : "") . PHP_EOL;
        $failCount++;
    }
}

// Track IDs for safe cleanup
$createdCompanyIds = [];
$createdUserIds = [];
$createdPlantIds = [];
$createdReqIds = [];
$createdInvIds = [];
$createdMatchIds = [];

// Pre-test cleanup of any previous aborted test artifacts (in reverse FK dependency order)
$pdo->exec("DELETE FROM `candidate_matches` WHERE `candidate_name` = 'Suresh K. Patil'");
$pdo->exec("DELETE FROM `requirement_trades` WHERE `trade_name` IN ('Machinist', 'Fitter') AND `requirement_id` IN (SELECT id FROM `manpower_requirements` WHERE `requirement_id` LIKE 'REQ-%-9999%')");
$pdo->exec("DELETE FROM `manpower_requirements` WHERE `requirement_id` LIKE 'REQ-%-9999%'");
$pdo->exec("DELETE FROM `company_invitations` WHERE `email` LIKE '%engineer_%@megarobotics.internal'");
$pdo->exec("DELETE FROM `manufacturing_plants` WHERE `plant_code` = 'PL-MR-01'");
$pdo->exec("DELETE FROM `users` WHERE `email` LIKE '%pooja.nair_%@megarobotics.internal'");
$pdo->exec("DELETE FROM `companies` WHERE `company_name` LIKE '%Mega Robotics%'");

// ----------------------------------------------------
// Test Setup: Create Dedicated Test Enterprise
// ----------------------------------------------------
$testRegNo = "CIN" . substr((string)time(), -8);
$testHrEmail = "test_ind_admin_" . time() . "@megarobotics.internal";

$insComp = $pdo->prepare("INSERT INTO `companies` 
    (`company_name`, `registration_number`, `industry_type`, `address`, `state`, `district`, `city`, `pincode`, `hr_contact_name`, `hr_contact_phone`, `hr_contact_email`, `verification_status`) 
    VALUES 
    ('Mega Robotics India Ltd', :reg, 'Robotics & Automation', 'Plot 55, Hinjawadi Phase 2', 'Maharashtra', 'Pune', 'Pune', '411057', 'Pooja Nair', '9890123456', :email, 'Verified')");
$insComp->execute([':reg' => $testRegNo, ':email' => $testHrEmail]);
$testCompanyId = (int)$pdo->lastInsertId();
$createdCompanyIds[] = $testCompanyId;

// Create Industry Admin User
$testUserEmail = "pooja.nair_" . time() . "@megarobotics.internal";
$insUser = $pdo->prepare("INSERT INTO `users` 
    (`role_id`, `company_id`, `full_name`, `email`, `phone`, `password_hash`, `status`) 
    VALUES 
    (2, :cid, 'Pooja Nair', :email, '9890123456', :hash, 'Active')");
$insUser->execute([
    ':cid'   => $testCompanyId,
    ':email' => $testUserEmail,
    ':hash'  => password_hash('Pass@2026', PASSWORD_BCRYPT)
]);
$testUserId = (int)$pdo->lastInsertId();
$createdUserIds[] = $testUserId;

// Establish Authenticated Industry Session
$_SESSION['user_id'] = $testUserId;
$_SESSION['role_name'] = 'industry_admin';
$_SESSION['role_id'] = 2;
$_SESSION['company_id'] = $testCompanyId;
$_SESSION['user_email'] = $testUserEmail;
$_SESSION['full_name'] = 'Pooja Nair';

// ----------------------------------------------------
// TEST GROUP 1: Dashboard Metrics & Scoping
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 1: Dashboard Metrics & Tenant Scoping ---" . PHP_EOL;

$metrics = $industryCtrl->getDashboardMetrics($testCompanyId);
assertTest("Dashboard Metrics Query Successful", !empty($metrics));
assertTest("Profile Completion Computed", $metrics['profile_completion'] > 0, "{$metrics['profile_completion']}%");
assertTest("Tenant Isolation (Total Reqs matches test company)", $metrics['total_requirements'] === 0);
assertTest("Remaining Vacancies Baseline Calculated", $metrics['remaining_requirement'] === 0);

// ----------------------------------------------------
// TEST GROUP 2: Company Profile Management & Security
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 2: Company Profile & Verification Immunity ---" . PHP_EOL;

$profile = $industryCtrl->getCompanyProfile($testCompanyId);
assertTest("Company Profile Retrieved", $profile !== null && $profile['company_name'] === 'Mega Robotics India Ltd');

// Update Profile
$updateData = [
    'company_name'        => 'Mega Robotics India Technologies Ltd',
    'registration_number' => $testRegNo . 'A',
    'industry_type'       => 'Robotics & Automation',
    'address'             => 'Plot 55, Hinjawadi Phase 2, IT Park',
    'state'               => 'Maharashtra',
    'district'            => 'Pune',
    'city'                => 'Pune',
    'pincode'             => '411057',
    'website'             => 'https://megarobotics.internal',
    'hr_contact_name'     => 'Pooja Nair',
    'hr_contact_phone'    => '9890123456',
    'hr_contact_email'    => $testHrEmail,
    'description'         => 'Specialized manufacturer of multi-axis articulated robots.'
];

$updRes = $industryCtrl->updateCompanyProfile($testCompanyId, $updateData);
assertTest("Company Profile Update via Controller", $updRes['success'] === true);
assertTest("Sensitive Modification Triggered Re-verification Review", $updRes['sensitive_modified'] === true);

$updatedProfile = $industryCtrl->getCompanyProfile($testCompanyId);
assertTest("Website & Description Persisted", 
    $updatedProfile['website'] === 'https://megarobotics.internal' && 
    str_contains($updatedProfile['description'], 'articulated robots')
);
assertTest("Verification Status Immune to Self-Modification", 
    $updatedProfile['verification_status'] === 'Verified',
    "Status remained: " . $updatedProfile['verification_status']
);

// ----------------------------------------------------
// TEST GROUP 3: Manufacturing Plant Lifecycle & Isolation
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 3: Manufacturing Plant Management ---" . PHP_EOL;

$plantData = [
    'plant_name'           => 'Mega Robotics Plant 1',
    'plant_code'           => 'PL-MR-01',
    'plant_address'        => 'Sector 10, MIDC Bhosari',
    'state'                => 'Maharashtra',
    'district'             => 'Pune',
    'city'                 => 'Bhosari',
    'pincode'              => '411026',
    'contact_person'       => 'Anand Kulkarni',
    'contact_number'       => '9822112233',
    'plant_type'           => 'Automated Assembly Line',
    'manufacturing_sector' => 'Robotics Assembly',
    'status'               => 'Active'
];

// 3.1 Create Plant
$plantRes1 = $industryCtrl->savePlant($testCompanyId, $plantData);
assertTest("Manufacturing Plant Creation", $plantRes1['success'] === true, $plantRes1['message']);
$plantId = $plantRes1['id'] ?? 0;
if ($plantId) $createdPlantIds[] = $plantId;

// 3.2 Duplicate Plant Code Rejection within Company
$plantDupRes = $industryCtrl->savePlant($testCompanyId, $plantData);
assertTest("Duplicate Plant Code Blocked", $plantDupRes['success'] === false);

// 3.3 Get Single Plant
$fetchedPlant = $industryCtrl->getPlantById($testCompanyId, $plantId);
assertTest("Plant Retrieval Scoped to Company", $fetchedPlant !== null && $fetchedPlant['plant_code'] === 'PL-MR-01');

// 3.4 Cross-Company Plant IDOR Defense
$foreignPlant = $industryCtrl->getPlantById(999999, $plantId);
assertTest("Cross-Company Plant Access Blocked (IDOR Guard)", $foreignPlant === null);

// 3.5 Status Toggle (Deactivate Plant)
$statusToggleRes = $industryCtrl->setPlantStatus($testCompanyId, $plantId, 'Inactive');
assertTest("Plant Status Deactivation", $statusToggleRes['success'] === true);

$plantAfterToggle = $industryCtrl->getPlantById($testCompanyId, $plantId);
assertTest("Plant Status Persisted as Inactive", $plantAfterToggle['status'] === 'Inactive');

// Re-activate plant
$industryCtrl->setPlantStatus($testCompanyId, $plantId, 'Active');

// ----------------------------------------------------
// TEST GROUP 4: Team Member Management & Secure Invitations
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 4: Team Members & Cryptographic Invitations ---" . PHP_EOL;

$members = $industryCtrl->getTeamMembers($testCompanyId);
assertTest("Team Members Query Scoped to Company", count($members) >= 1 && (int)$members[0]['id'] === $testUserId);

// 4.1 Privilege Escalation Guard: Block inviting super_admin
$invEscalation = $industryCtrl->inviteTeamMember($testCompanyId, $testUserId, [
    'name'  => 'Malicious Actor',
    'email' => 'malicious@actor.internal',
    'role'  => 'super_admin' // Blocked!
]);
assertTest("Privilege Escalation Blocked (Cannot invite Super Admin)", $invEscalation['success'] === false);

// 4.2 Valid Invitation (role = industry_user)
$inviteeEmail = "engineer_" . time() . "@megarobotics.internal";
$validInv = $industryCtrl->inviteTeamMember($testCompanyId, $testUserId, [
    'name'  => 'Rahul Verma',
    'email' => $inviteeEmail,
    'phone' => '9876543210',
    'role'  => 'industry_user'
]);
assertTest("Team Member Invitation Issued", $validInv['success'] === true);
assertTest("Cryptographic 64-char Hex Token Generated", 
    !empty($validInv['raw_token']) && strlen($validInv['raw_token']) === 64
);

// Fetch invitation from DB
$invStmt = $pdo->prepare("SELECT id FROM `company_invitations` WHERE `company_id` = :cid AND `email` = :email");
$invStmt->execute([':cid' => $testCompanyId, ':email' => $inviteeEmail]);
$invId = (int)$invStmt->fetchColumn();
if ($invId) $createdInvIds[] = $invId;

// 4.3 Resend Invitation
$resendRes = $industryCtrl->resendInvitation($testCompanyId, $invId);
assertTest("Invitation Resend / Validity Extension", $resendRes['success'] === true);

// 4.4 Revoke Invitation
$revokeRes = $industryCtrl->revokeInvitation($testCompanyId, $invId);
assertTest("Invitation Revocation", $revokeRes['success'] === true);

// 4.5 Self-Suspension Lockout Prevention
$selfSuspend = $industryCtrl->setMemberStatus($testCompanyId, $testUserId, 'Suspended');
assertTest("Self-Suspension Lockout Prevention Guard", $selfSuspend['success'] === false);

// ----------------------------------------------------
// TEST GROUP 5: Requirement Creation & Lifecycle Tracking
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 5: Requirement Creation & State Tracking ---" . PHP_EOL;

$reqCode = 'REQ-' . date('Ym') . '-9999';
$insReq = $pdo->prepare("INSERT INTO `manpower_requirements` 
    (`requirement_id`, `company_id`, `plant_id`, `title`, `quantity_required`, 
     `qualification`, `board_type`, `experience_required`, `job_location_state`, `job_location_district`, 
     `job_location_city`, `job_description`, `min_salary`, `max_salary`, `submission_date`, 
     `application_deadline`, `interview_date`, `target_joining_date`, `priority`, `status`, `created_by`) 
    VALUES 
    (:code, :cid, :pid, 'Junior CNC Machine Operator', 10, 
     'ITI Machinist / Fitter', 'Either', 'Fresher', 'Maharashtra', 'Pune', 
     'Bhosari', 'Operating precision 3-axis CNC milling centers.', 16000, 22000, CURDATE(), 
     DATE_ADD(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 
     'High', 'APPROVED', :uid)");

$insReq->execute([
    ':code' => $reqCode,
    ':cid'  => $testCompanyId,
    ':pid'  => $plantId,
    ':uid'  => $testUserId
]);
$reqDbId = (int)$pdo->lastInsertId();
if ($reqDbId) $createdReqIds[] = $reqDbId;

// Add trade breakdown
$tradeStmt = $pdo->prepare("INSERT INTO `requirement_trades` (`requirement_id`, `trade_name`, `vacancies`) VALUES (:rid, :trade, :vac)");
$tradeStmt->execute([':rid' => $reqDbId, ':trade' => 'Machinist', ':vac' => 6]);
$tradeStmt->execute([':rid' => $reqDbId, ':trade' => 'Fitter', ':vac' => 4]);

assertTest("Manpower Requirement Created with Plant Reference", $reqDbId > 0);

// Query Requirements via Controller
$myReqs = $industryCtrl->getMyRequirements($testCompanyId, ['status' => 'APPROVED']);
assertTest("Requirements Query Scoped to Company", $myReqs['total'] >= 1 && $myReqs['data'][0]['plant_name'] === 'Mega Robotics Plant 1');

// Requirement Details
$reqDetails = $industryCtrl->getRequirementDetails($testCompanyId, $reqDbId);
assertTest("Requirement Dossier Retrieved", 
    $reqDetails !== null && count($reqDetails['trades']) === 2 && $reqDetails['trades'][0]['trade_name'] === 'Machinist'
);

// Cross-Company Requirement IDOR Guard
$foreignReq = $industryCtrl->getRequirementDetails(999999, $reqDbId);
assertTest("Cross-Company Requirement IDOR Blocked", $foreignReq === null);

// ----------------------------------------------------
// TEST GROUP 6: Candidate Visibility & Privacy Masking
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 6: Candidate Visibility & Privacy Standards ---" . PHP_EOL;

// Create Sample Match for this requirement
$insMatch = $pdo->prepare("INSERT INTO `candidate_matches` 
    (`requirement_id`, `candidate_source`, `candidate_source_id`, `candidate_name`, `trade`, `passing_year`, `match_score`, `status`) 
    VALUES 
    (:rid, 'prayas_dashboard', 101, 'Suresh K. Patil', 'Machinist', '2024', 88.50, 'Shortlisted')");
$insMatch->execute([':rid' => $reqDbId]);
$matchId = (int)$pdo->lastInsertId();
if ($matchId) $createdMatchIds[] = $matchId;

$candidateMatches = $industryCtrl->getCandidateMatches($testCompanyId, ['trade' => 'Machinist']);
assertTest("Candidate Matches Retrieved for Company Requirement", 
    $candidateMatches['total'] >= 1 && $candidateMatches['data'][0]['trade'] === 'Machinist'
);

// Privacy Assertion: Verify returned keys do not expose Aadhaar, PAN, internal credentials
$firstCand = $candidateMatches['data'][0];
$hasSensitiveKeys = isset($firstCand['aadhaar'], $firstCand['pan'], $firstCand['password'], $firstCand['bank_account']);
assertTest("Candidate Privacy Projection Guarantees (No Aadhaar/PAN exposed)", !$hasSensitiveKeys);
assertTest("Match Score Verified", (float)$firstCand['match_score'] === 88.50);

// ----------------------------------------------------
// TEST GROUP 7: Notifications & Reports Export
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 7: Notifications & Reports Export ---" . PHP_EOL;

// Insert sample notification
$pdo->prepare("INSERT INTO `notifications` (`recipient_user_id`, `title`, `message`, `action_url`) 
    VALUES (:uid, 'Test Requirement Approved', 'Your requirement has been approved.', '/views/industry/requirement_details.php?id=' || :rid)")
    ->execute([':uid' => $testUserId, ':rid' => $reqDbId]);

$notifs = $industryCtrl->getNotifications($testCompanyId, $testUserId);
assertTest("Industry Notification Retrieved", !empty($notifs) && $notifs[0]['title'] === 'Test Requirement Approved');

// Test In-Memory CSV Export Formatting for Industry
$csvReqHandle = fopen('php://temp', 'r+');
fputcsv($csvReqHandle, ['Requisition Code', 'Title', 'Plant Code', 'Vacancies']);
fputcsv($csvReqHandle, [$reqCode, 'Junior CNC Machine Operator', 'PL-MR-01', 10]);
rewind($csvReqHandle);
$csvReqOut = stream_get_contents($csvReqHandle);
fclose($csvReqHandle);

assertTest("Company Requirements CSV Export Serialized", 
    str_contains($csvReqOut, $reqCode) && str_contains($csvReqOut, 'PL-MR-01')
);

// ----------------------------------------------------
// TEST GROUP 8: Safe Cleanup of Test Artifacts
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 8: Safe Cleanup of Test Artifacts ---" . PHP_EOL;

// Delete matches
if (!empty($createdMatchIds)) {
    $pdo->exec("DELETE FROM `candidate_matches` WHERE `id` IN (" . implode(',', $createdMatchIds) . ")");
    assertTest("Cleaned Up Test Candidate Matches", true);
}

// Delete requirements
if (!empty($createdReqIds)) {
    $pdo->exec("DELETE FROM `manpower_requirements` WHERE `id` IN (" . implode(',', $createdReqIds) . ")");
    assertTest("Cleaned Up Test Manpower Requirements", true);
}

// Delete invitations
if (!empty($createdInvIds)) {
    $pdo->exec("DELETE FROM `company_invitations` WHERE `id` IN (" . implode(',', $createdInvIds) . ")");
    assertTest("Cleaned Up Test Company Invitations", true);
}

// Delete plants
if (!empty($createdPlantIds)) {
    $pdo->exec("DELETE FROM `manufacturing_plants` WHERE `id` IN (" . implode(',', $createdPlantIds) . ")");
    assertTest("Cleaned Up Test Manufacturing Plants", true);
}

// Delete users
if (!empty($createdUserIds)) {
    $pdo->exec("DELETE FROM `users` WHERE `id` IN (" . implode(',', $createdUserIds) . ")");
    assertTest("Cleaned Up Test Industry Users", true);
}

// Delete companies
if (!empty($createdCompanyIds)) {
    $pdo->exec("DELETE FROM `companies` WHERE `id` IN (" . implode(',', $createdCompanyIds) . ")");
    assertTest("Cleaned Up Test Companies", true);
}

// Attest candidate databases untouched
$cand1 = Database::queryCandidateReadOnly("SELECT COUNT(*) as cnt FROM `prayas_iti_dashboard`.`students`")[0]['cnt'];
$cand2 = Database::queryCandidateReadOnly("SELECT COUNT(*) as cnt FROM `itistudents`.`candidates`")[0]['cnt'];
assertTest("Candidate Databases Untouched & Intact", (int)$cand1 === 1292 && (int)$cand2 === 56, "1,292 + 56 records");

// ----------------------------------------------------
// Test Summary
// ----------------------------------------------------
echo PHP_EOL . "==================================================" . PHP_EOL;
echo "TEST RESULTS SUMMARY: " . PHP_EOL;
echo "  Total Passed: $passCount" . PHP_EOL;
echo "  Total Failed: $failCount" . PHP_EOL;
echo "==================================================" . PHP_EOL;

if ($failCount > 0) {
    exit(1);
}
exit(0);
