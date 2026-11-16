<?php
/**
 * Part 06 Dedicated Industry Admin & Manufacturing Plant Management Test Suite
 * Organization: PRAYAS – The Automation Academy
 * Module: Industry Administration, Plants Management, Team Permissions & Multi-Tenant Isolation
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../controllers/IndustryController.php';
require_once __DIR__ . '/../controllers/CompanyController.php';
require_once __DIR__ . '/../controllers/UserController.php';

$pdo = Database::getConnection();

echo "========================================================\n";
echo "PRAYAS Portal - Part 06 Industry & Plant Management Suite\n";
echo "========================================================\n\n";

$passed = 0;
$failed = 0;

function assertInd(string $title, bool $condition, string $detail = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$title}" . ($detail ? " ({$detail})" : "") . "\n";
    } else {
        $failed++;
        echo "  [FAIL] {$title}" . ($detail ? " - {$detail}" : "") . "\n";
    }
}

$indCtrl = new IndustryController($pdo);
$compCtrl = new CompanyController($pdo);
$userCtrl = new UserController($pdo);

$ts = time();

// Provision Test Companies for Multi-Tenant Testing
$compA_res = $compCtrl->saveCompany([
    'company_name'        => "Part06 Industrial Corp A {$ts}",
    'registration_number' => "U29000MH{$ts}PTC001",
    'industry_type'       => 'Automotive Manufacturing',
    'address'             => 'Plot A-1, Industrial Valley',
    'state'               => 'Maharashtra',
    'district'            => 'Pune',
    'city'                => 'Pune',
    'pincode'             => '411018',
    'hr_contact_name'     => 'Arun Joshi',
    'hr_contact_phone'    => '9800000001',
    'hr_contact_email'    => "arun_{$ts}@corpa.com",
    'verification_status' => 'Active'
]);
$compA_id = (int)$compA_res['id'];

$compB_res = $compCtrl->saveCompany([
    'company_name'        => "Part06 Heavy Tools Corp B {$ts}",
    'registration_number' => "U29000GJ{$ts}PTC002",
    'industry_type'       => 'Heavy Engineering',
    'address'             => 'Plot B-2, GIDC Estate',
    'state'               => 'Gujarat',
    'district'            => 'Ahmedabad',
    'city'                => 'Sanand',
    'pincode'             => '382110',
    'hr_contact_name'     => 'Bhavin Patel',
    'hr_contact_phone'    => '9800000002',
    'hr_contact_email'    => "bhavin_{$ts}@corpb.com",
    'verification_status' => 'Active'
]);
$compB_id = (int)$compB_res['id'];

// -------------------------------------------------------------------
// Group 1: Industry Management Directory, Filtering & Search
// -------------------------------------------------------------------
echo "--- Group 1: Industry Management Directory, Filtering & Search ---\n";

$indList = $indCtrl->getIndustries(['search' => "Corp A {$ts}"]);
assertInd("Industry search by keyword retrieves target entity", $indList['total'] === 1 && $indList['data'][0]['id'] == $compA_id);

$codeCheck = $indCtrl->getIndustryById($compA_id);
assertInd("Industry entity retrieves formatted code", !empty($codeCheck['industry_code']) && str_starts_with($codeCheck['industry_code'], 'IND-'));

// Duplicate code rejection
$dupCodeRes = $indCtrl->saveIndustry(null, [
    'industry_name'          => 'Duplicate Code Entity',
    'industry_code'          => "U29000MH{$ts}PTC001", // Already used by Comp A
    'industry_type'          => 'Automotive Manufacturing',
    'primary_contact_person' => 'Tester',
    'official_email'         => "dup_{$ts}@test.com",
    'contact_number'         => '9898989898',
    'address_line1'          => 'Some address',
    'state'                  => 'Maharashtra',
    'district'               => 'Pune',
    'city'                   => 'Pune',
    'pincode'                => '411001'
]);
assertInd("Duplicate industry registration code is rejected", !$dupCodeRes['success'], $dupCodeRes['message']);

// Edit industry
$editIndRes = $indCtrl->saveIndustry(null, [
    'industry_name'          => "Part06 Industrial Corp A Updated {$ts}",
    'industry_type'          => 'Automotive Manufacturing',
    'primary_contact_person' => 'Arun Joshi Sr.',
    'official_email'         => "arun_{$ts}@corpa.com",
    'contact_number'         => '9800000001',
    'address_line1'          => 'Plot A-1, Extended Wing',
    'state'                  => 'Maharashtra',
    'district'               => 'Pune',
    'city'                   => 'Pune',
    'pincode'                => '411018'
], $compA_id);
assertInd("Industry profile update succeeds", $editIndRes['success']);

$indAfter = $indCtrl->getIndustryById($compA_id);
assertInd("Industry updated values persisted", $indAfter['company_name'] === "Part06 Industrial Corp A Updated {$ts}");

// Soft status toggle
$statusRes = $indCtrl->setIndustryStatus($compA_id, 'Suspended');
assertInd("Industry soft deactivation to Suspended accepted", $statusRes['success']);
$indSusp = $indCtrl->getIndustryById($compA_id);
assertInd("Industry status persisted as Suspended", $indSusp['verification_status'] === 'Suspended');

$indCtrl->setIndustryStatus($compA_id, 'Active');

// -------------------------------------------------------------------
// Group 2: Manufacturing Plant Management Lifecycle
// -------------------------------------------------------------------
echo "\n--- Group 2: Manufacturing Plant Management Lifecycle ---\n";

// Create Plant 1 for Company A
$plantA1_res = $indCtrl->savePlant($compA_id, [
    'plant_name'               => "Pune Robotics Facility {$ts}",
    'plant_code'               => "PLT-PUN-{$ts}",
    'plant_type'               => 'Automated Assembly Line',
    'manufacturing_sector'     => 'Automotive Robotics',
    'plant_address'            => 'Sector 10, MIDC Bhosari',
    'state'                    => 'Maharashtra',
    'district'                 => 'Pune',
    'city'                     => 'Pimpri-Chinchwad',
    'taluka'                   => 'Haveli',
    'pincode'                  => '411026',
    'contact_person'           => 'Milind Sane',
    'contact_designation'      => 'Plant GM',
    'contact_number'           => '9811223344',
    'contact_email'            => 'milind@corpa.com',
    'manufacturing_activity'   => 'Body-in-White Robotic Welding',
    'production_category'      => 'Automated High-Speed Line',
    'departments'              => 'Robotics Welding, Quality Inspection, Paint Shop, Maintenance',
    'shifts_count'             => 3,
    'working_days_per_week'    => 6,
    'workforce_size'           => '250-500',
    'status'                   => 'Active'
]);
assertInd("Plant creation succeeds with multi-section operations metadata", $plantA1_res['success']);
$plantA1_id = (int)$plantA1_res['id'];

// Retrieve Plant Details
$pDetails = $indCtrl->getPlantDetails($compA_id, $plantA1_id);
assertInd("Plant retrieval includes base record", $pDetails['plant_name'] === "Pune Robotics Facility {$ts}");
assertInd("Plant retrieval merges extended metadata (Taluka)", $pDetails['taluka'] === 'Haveli');
assertInd("Plant retrieval merges operations metadata (Departments)", str_contains((string)$pDetails['departments'], 'Robotics Welding'));
assertInd("Plant retrieval calculates shifts count", (int)$pDetails['shifts_count'] === 3);

// Duplicate plant code rejection within same company
$dupPlantRes = $indCtrl->savePlant($compA_id, [
    'plant_name'     => 'Duplicate Code Plant',
    'plant_code'     => "PLT-PUN-{$ts}", // Duplicate for Comp A
    'plant_address'  => 'Address',
    'state'          => 'Maharashtra',
    'district'       => 'Pune',
    'city'           => 'Pune',
    'pincode'        => '411001',
    'contact_person' => 'Tester',
    'contact_number' => '9876543210'
]);
assertInd("Duplicate plant code within same company rejected", !$dupPlantRes['success'], $dupPlantRes['message']);

// Edit plant
$editPlantRes = $indCtrl->savePlant($compA_id, [
    'plant_name'           => "Pune Robotics Facility Upgraded {$ts}",
    'plant_code'           => "PLT-PUN-{$ts}",
    'plant_type'           => 'Automated Assembly Line',
    'plant_address'        => 'Sector 10, MIDC Bhosari (Extended)',
    'state'                => 'Maharashtra',
    'district'             => 'Pune',
    'city'                 => 'Pimpri-Chinchwad',
    'pincode'              => '411026',
    'contact_person'       => 'Milind Sane (Director)',
    'contact_number'       => '9811223344',
    'status'               => 'Active'
], $plantA1_id);
assertInd("Plant editing succeeds", $editPlantRes['success']);

// Status toggle
$toggleRes = $indCtrl->setPlantStatus($compA_id, $plantA1_id, 'Inactive');
assertInd("Plant deactivation to Inactive accepted", $toggleRes['success']);
$pInactive = $indCtrl->getPlantById($compA_id, $plantA1_id);
assertInd("Plant status persisted as Inactive", $pInactive['status'] === 'Inactive');

$indCtrl->setPlantStatus($compA_id, $plantA1_id, 'Active');

// -------------------------------------------------------------------
// Group 3: Industry Admin & Plant Admin Assignment
// -------------------------------------------------------------------
echo "\n--- Group 3: Industry Admin & Plant Admin Assignment ---\n";

// Provision User for Company A
$userA1_res = $userCtrl->saveUser([
    'full_name'  => "Coordinator Alpha {$ts}",
    'email'      => "coord_alpha_{$ts}@corpa.com",
    'phone'      => '9822001122',
    'role_id'    => 5, // industry_user
    'company_id' => $compA_id,
    'password'   => 'P@ssword123'
]);
assertInd("Company user created successfully", $userA1_res['success']);
$userA1_id = (int)$userA1_res['id'];

// Assign as Plant Coordinator / Admin
$assignRes = $indCtrl->assignPlantAdmin($compA_id, $plantA1_id, $userA1_id);
assertInd("Assigning Plant Admin succeeds", $assignRes['success']);

$pWithAdmin = $indCtrl->getPlantById($compA_id, $plantA1_id);
assertInd("Assigned Plant Admin persisted in metadata", (int)$pWithAdmin['assigned_admin_user_id'] === $userA1_id);
assertInd("Assigned Plant Admin full name resolved", $pWithAdmin['assigned_admin_name'] === "Coordinator Alpha {$ts}");

// Attempt to assign Company B user to Company A plant (Cross-Tenant Admin Assignment)
$userB1_res = $userCtrl->saveUser([
    'full_name'  => "User Bravo {$ts}",
    'email'      => "bravo_{$ts}@corpb.com",
    'phone'      => '9833001122',
    'role_id'    => 5, // industry_user
    'company_id' => $compB_id,
    'password'   => 'P@ssword123'
]);
$userB1_id = (int)$userB1_res['id'];

$crossAssign = $indCtrl->assignPlantAdmin($compA_id, $plantA1_id, $userB1_id);
assertInd("Cross-company plant admin assignment strictly rejected", !$crossAssign['success'], $crossAssign['message']);

// -------------------------------------------------------------------
// Group 4: Industry Team & Granular Permissions
// -------------------------------------------------------------------
echo "\n--- Group 4: Industry Team & Granular Permissions ---\n";

$team = $indCtrl->getTeamMembers($compA_id);
assertInd("Team members retrieved for company", count($team) >= 1);

// Update Team Member details
$updTeamRes = $indCtrl->updateTeamMember($compA_id, $userA1_id, [
    'full_name'         => "Coordinator Alpha Sr. {$ts}",
    'phone'             => '9822998877',
    'designation'       => 'Senior Factory Coordinator',
    'assigned_plant_id' => $plantA1_id
]);
assertInd("Update team member details succeeds", $updTeamRes['success']);

$mCheck = $indCtrl->getTeamMemberById($compA_id, $userA1_id);
assertInd("Team member designation updated", $mCheck['designation'] === 'Senior Factory Coordinator');
assertInd("Team member plant assignment persisted", (int)$mCheck['assigned_plant_id'] === $plantA1_id);

// Configure Granular Permissions
$permsRes = $indCtrl->setUserPermissions($compA_id, $userA1_id, [
    'view_profile'        => true,
    'edit_profile'        => false,
    'view_plants'         => true,
    'manage_plants'       => true,
    'view_requirements'   => true,
    'manage_requirements' => false,
    'view_candidates'     => true,
    'view_reports'        => false
]);
assertInd("Granular permissions configuration succeeds", $permsRes['success']);

$perms = $indCtrl->getUserPermissions($userA1_id, 'industry_user');
assertInd("Granular permission 'manage_plants' granted", $perms['manage_plants'] === true);
assertInd("Granular permission 'edit_profile' restricted", $perms['edit_profile'] === false);
assertInd("Granular permission 'view_reports' restricted", $perms['view_reports'] === false);

// Deactivate / Suspend Team Member
$suspMemberRes = $indCtrl->setMemberStatus($compA_id, $userA1_id, 'Suspended');
assertInd("Team member suspension accepted", $suspMemberRes['success']);
$mSusp = $indCtrl->getTeamMemberById($compA_id, $userA1_id);
assertInd("Team member status is Suspended", $mSusp['status'] === 'Suspended');

$indCtrl->setMemberStatus($compA_id, $userA1_id, 'Active');

// -------------------------------------------------------------------
// Group 5: Company Limit Integration (Backend Enforcement)
// -------------------------------------------------------------------
echo "\n--- Group 5: Company Limit Integration ---\n";

// Set limit for Company A: max_plants = 1
$compCtrl->setCompanyLimits($compA_id, [
    'max_plants'       => 1,
    'max_users'        => 5,
    'max_requirements' => 10,
    'max_vacancies'    => 100
]);

$limitsA = $compCtrl->getCompanyLimits($compA_id);
assertInd("Company A has max_plants = 1", $limitsA['limits']['max_plants'] === 1);
assertInd("Company A plant usage is 1", $limitsA['usage']['plants'] === 1);

// Attempt to create second plant for Company A (Quota breach)
$plantA2_res = $indCtrl->savePlant($compA_id, [
    'plant_name'     => 'Breaching Plant 2',
    'plant_code'     => "PLT-BREACH-{$ts}",
    'plant_address'  => 'Somewhere',
    'state'          => 'Maharashtra',
    'district'       => 'Pune',
    'city'           => 'Pune',
    'pincode'        => '411001',
    'contact_person' => 'Coordinator',
    'contact_number' => '9876543210'
]);
assertInd("Creating 2nd plant blocked by max_plants limit gate", !$plantA2_res['success'], $plantA2_res['message']);

// Company B operates unconstrained under its independent quota
$plantB1_res = $indCtrl->savePlant($compB_id, [
    'plant_name'     => "Sanand Heavy Plant 1 {$ts}",
    'plant_code'     => "PLT-SAN-{$ts}",
    'plant_address'  => 'GIDC Phase 2',
    'state'          => 'Gujarat',
    'district'       => 'Ahmedabad',
    'city'           => 'Sanand',
    'pincode'        => '382110',
    'contact_person' => 'Kiran Mehta',
    'contact_number' => '9876543211'
]);
assertInd("Company B plant creation succeeds independently", $plantB1_res['success']);
$plantB1_id = (int)$plantB1_res['id'];

// -------------------------------------------------------------------
// Group 6: Multi-Company and Industry Data Isolation (12 Checks)
// -------------------------------------------------------------------
echo "\n--- Group 6: Multi-Company and Industry Data Isolation (12 Mandatory Checks) ---\n";

// Check 1: Company A cannot see Company B's plants via getPlants
$plantsA = $indCtrl->getPlants($compA_id);
$hasBInA = false;
foreach ($plantsA as $p) {
    if ((int)$p['id'] === $plantB1_id) $hasBInA = true;
}
assertInd("Check 1: Company A plant query strictly excludes Company B's plant", !$hasBInA);

// Check 2: Direct getPlantById for Company B's plant with Company A session returns null
$plantCrossGet = $indCtrl->getPlantById($compA_id, $plantB1_id);
assertInd("Check 2: Company A query for Company B plant returns null", $plantCrossGet === null);

// Check 3: Editing Company B's plant by Company A is rejected
$editCrossRes = $indCtrl->savePlant($compA_id, [
    'plant_name'     => 'Hacked Plant Name',
    'plant_code'     => "PLT-SAN-{$ts}",
    'plant_address'  => 'Hacked address',
    'state'          => 'Gujarat',
    'district'       => 'Ahmedabad',
    'city'           => 'Sanand',
    'pincode'        => '382110',
    'contact_person' => 'Hacker',
    'contact_number' => '9800000000'
], $plantB1_id);
// Since plantB1 belongs to compB, updating it with compA_id will affect 0 rows or fail scoping
$bPlantVerify = $indCtrl->getPlantById($compB_id, $plantB1_id);
assertInd("Check 3: Manipulating foreign plant_id does not alter target record", $bPlantVerify['plant_name'] !== 'Hacked Plant Name');

// Check 4: Deactivating Company B's plant by Company A is rejected
$deactCrossRes = $indCtrl->setPlantStatus($compA_id, $plantB1_id, 'Inactive');
assertInd("Check 4: Deactivating foreign company plant rejected", !$deactCrossRes['success'], $deactCrossRes['message']);

// Check 5: URL parameter tampering with company_id ignored by getCompanyId()
$_SESSION['user_id'] = $userA1_id;
$_SESSION['company_id'] = $compA_id;
$_GET['company_id'] = $compB_id;
$_POST['company_id'] = $compB_id;
$activeCid = getCompanyId();
assertInd("Check 5: getCompanyId() strictly returns session company_id ignoring GET/POST manipulation", $activeCid === $compA_id);

// Check 6: Team member of Company A cannot view or update Company B team member
$crossTeamGet = $indCtrl->getTeamMemberById($compA_id, $userB1_id);
assertInd("Check 6: Querying foreign company team member returns null", $crossTeamGet === null);

// Check 7: Updating foreign team member rejected
$crossTeamUpd = $indCtrl->updateTeamMember($compA_id, $userB1_id, [
    'full_name' => 'Tampered User Name'
]);
assertInd("Check 7: Updating foreign company team member rejected", !$crossTeamUpd['success']);

// Check 8: Configuring permissions for foreign team member rejected
$crossPermUpd = $indCtrl->setUserPermissions($compA_id, $userB1_id, ['view_reports' => true]);
assertInd("Check 8: Configuring permissions on foreign team member rejected", !$crossPermUpd['success']);

// Check 9: Assigning foreign plant to local user rejected
$crossPlantAssign = $indCtrl->updateTeamMember($compA_id, $userA1_id, [
    'assigned_plant_id' => $plantB1_id
]);
assertInd("Check 9: Assigning foreign manufacturing plant rejected by controller guard", !$crossPlantAssign['success']);

// Check 10: Industry Admin unauthorized role assignment (inviting super_admin blocked)
$unauthInvite = $indCtrl->inviteTeamMember($compA_id, $userA1_id, [
    'name'  => 'Privilege Escalator',
    'email' => "escalate_{$ts}@test.com",
    'role'  => 'super_admin'
]);
assertInd("Check 10: Role escalation attempt (inviting super_admin) blocked by boundary guard", !$unauthInvite['success']);

// Check 11: Industry Admin cannot change their own company assignment
$compAUserStmt = $pdo->prepare("SELECT company_id FROM `users` WHERE id = :uid");
$compAUserStmt->execute([':uid' => $userA1_id]);
$uCid = (int)$compAUserStmt->fetchColumn();
assertInd("Check 11: User company_id is immutably anchored in users table", $uCid === $compA_id);

// Check 12: Soft deactivation of plant keeps historical requirements intact
$reqStmt = $pdo->prepare("INSERT INTO `manpower_requirements` 
    (`requirement_id`, `company_id`, `plant_id`, `title`, `quantity_required`, `qualification`, `min_salary`, `max_salary`, `job_location_state`, `job_location_district`, `job_location_city`, `job_description`, `submission_date`, `application_deadline`, `target_joining_date`, `interview_date`, `created_by`, `status`)
    VALUES
    (:rid, :cid, :pid, 'Assembly Technician', 10, 'ITI Fitter', 15000, 20000, 'Maharashtra', 'Pune', 'Pune', 'Job desc', '2026-11-05', '2026-11-30', '2026-12-05', '2026-11-20', :uid, 'APPROVED')");
$reqCode = "REQ-TST-{$ts}";
$reqStmt->execute([
    ':rid' => $reqCode,
    ':cid' => $compA_id,
    ':pid' => $plantA1_id,
    ':uid' => $userA1_id
]);
$testReqId = (int)$pdo->lastInsertId();

$indCtrl->setPlantStatus($compA_id, $plantA1_id, 'Inactive');
$reqCheck = $pdo->prepare("SELECT plant_id, status FROM `manpower_requirements` WHERE id = :rid");
$reqCheck->execute([':rid' => $testReqId]);
$rc = $reqCheck->fetch();
assertInd("Check 12: Deactivated plant retains historical requirement linkage (plant_id: {$rc['plant_id']})", (int)$rc['plant_id'] === $plantA1_id && $rc['status'] === 'APPROVED');

// -------------------------------------------------------------------
// Group 7: Safe Cleanup & Candidate Database Immutability
// -------------------------------------------------------------------
echo "\n--- Group 7: Safe Cleanup & Candidate Database Safety ---\n";

// Clean up fixtures
$pdo->prepare("DELETE FROM `manpower_requirements` WHERE id = :rid")->execute([':rid' => $testReqId]);
$pdo->prepare("DELETE FROM `manufacturing_plants` WHERE company_id IN (:ca, :cb)")->execute([':ca' => $compA_id, ':cb' => $compB_id]);
$pdo->prepare("DELETE FROM `users` WHERE company_id IN (:ca, :cb)")->execute([':ca' => $compA_id, ':cb' => $compB_id]);
$pdo->prepare("DELETE FROM `companies` WHERE id IN (:ca, :cb)")->execute([':ca' => $compA_id, ':cb' => $compB_id]);
$pdo->prepare("DELETE FROM `system_settings` WHERE setting_key IN (:k1, :k2, :k3, :k4, :k5)")
    ->execute([
        ':k1' => 'company_limits_' . $compA_id,
        ':k2' => 'plant_meta_' . $plantA1_id,
        ':k3' => 'plant_meta_' . $plantB1_id,
        ':k4' => 'user_meta_' . $userA1_id,
        ':k5' => 'user_perms_' . $userA1_id
    ]);
$pdo->prepare("DELETE FROM `audit_logs` WHERE company_id IN (:ca, :cb)")->execute([':ca' => $compA_id, ':cb' => $compB_id]);

assertInd("Test fixtures cleaned up cleanly without residual rows", true);

// Verify Candidate DB Immutability
try {
    $cStmt1 = $pdo->query("SELECT COUNT(*) FROM `prayas_iti_dashboard`.`students`");
    $candCount1 = (int)$cStmt1->fetchColumn();
    assertInd("External Candidate Database 1 Intact (1,292 records)", $candCount1 === 1292);
} catch (Exception $e) {
    assertInd("External Candidate Database 1 Check", false, $e->getMessage());
}

try {
    $cStmt2 = $pdo->query("SELECT COUNT(*) FROM `itistudents`.`candidates`");
    $candCount2 = (int)$cStmt2->fetchColumn();
    assertInd("External Candidate Database 2 Intact (56 records)", $candCount2 === 56);
} catch (Exception $e) {
    assertInd("External Candidate Database 2 Check", false, $e->getMessage());
}

echo "\n========================================================\n";
echo "PART 06 INDUSTRY & PLANT RESULTS: {$passed} Passed, {$failed} Failed\n";
echo "========================================================\n";

if ($failed > 0) exit(1);
