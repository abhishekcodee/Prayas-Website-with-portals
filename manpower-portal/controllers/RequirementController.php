<?php
/**
 * Manpower Requirement Engine Controller
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/requirement_validation_engine.php';
require_once __DIR__ . '/../includes/notification_service.php';

class RequirementController
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Concurrency-Safe Requirement Number Generator
     * Format: REQ-YYYYMM-XXXX
     */
    public function generateRequirementCode(): string
    {
        $yearMonth = date('Ym');

        // Atomic sequence increment using ON DUPLICATE KEY UPDATE
        $seqSql = "INSERT INTO `requirement_sequences` (`year_month`, `last_sequence`) 
                   VALUES (:ym, 1) 
                   ON DUPLICATE KEY UPDATE `last_sequence` = `last_sequence` + 1";
        $seqStmt = $this->pdo->prepare($seqSql);
        $seqStmt->execute([':ym' => $yearMonth]);

        $fetchStmt = $this->pdo->prepare("SELECT `last_sequence` FROM `requirement_sequences` WHERE `year_month` = :ym");
        $fetchStmt->execute([':ym' => $yearMonth]);
        $seq = (int)$fetchStmt->fetchColumn();

        return sprintf('REQ-%s-%04d', $yearMonth, $seq);
    }

    /**
     * Comprehensive Multi-Stage Server-Side Validation Engine
     * Delegates to centralized RequirementValidationEngine (ALG-VAL-2026-V1.0)
     */
    public function validateRequirement(array $data, int $companyId, ?int $userId = null, ?int $existingReqId = null): array
    {
        $engine = new RequirementValidationEngine($this->pdo);
        return $engine->validate($data, $companyId, $userId, $existingReqId, true);
    }

    /**
     * Save / Update Draft Requirement
     * Does NOT publish or trigger matching. Stores wizard step progress.
     */
    public function saveDraft(int $companyId, array $data, ?int $requirementId = null): array
    {
        $userId = getUserId();
        $title = trim((string)($data['title'] ?? 'Untitled Draft Requirement'));
        $plantId = !empty($data['plant_id']) ? (int)$data['plant_id'] : null;
        $step = max(1, min(8, (int)($data['wizard_step'] ?? 1)));

        // Extract or compute quantity
        $trades = $data['trades'] ?? [];
        $quantity = (int)($data['quantity_required'] ?? 0);
        if ($quantity <= 0 && is_array($trades)) {
            foreach ($trades as $tr) {
                $quantity += (int)($tr['vacancies'] ?? 0);
            }
        }
        $quantity = max(1, $quantity);

        $payloadJson = json_encode($data, JSON_UNESCAPED_UNICODE);

        try {
            $this->pdo->beginTransaction();

            // Strict Plant Facility Ownership Verification
            if ($plantId) {
                $pCheck = $this->pdo->prepare("SELECT id FROM `manufacturing_plants` WHERE id = :pid AND company_id = :cid");
                $pCheck->execute([':pid' => $plantId, ':cid' => $companyId]);
                if (!$pCheck->fetch()) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Selected manufacturing plant does not belong to your company.'];
                }
            }

            if ($requirementId) {
                // Verify ownership
                $check = $this->pdo->prepare("SELECT id, requirement_id, status FROM `manpower_requirements` WHERE id = :id AND company_id = :cid");
                $check->execute([':id' => $requirementId, ':cid' => $companyId]);
                $existing = $check->fetch();

                if (!$existing) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Draft requirement not found or access denied.'];
                }

                if (!in_array($existing['status'], ['DRAFT', 'NEEDS_CORRECTION'], true)) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => "Only drafts or requirements needing correction can be saved as draft."];
                }

                $updateSql = "UPDATE `manpower_requirements` SET 
                    `plant_id` = :pid,
                    `title` = :title,
                    `quantity_required` = :qty,
                    `wizard_step` = :step,
                    `draft_payload_json` = :payload,
                    `updated_at` = NOW()
                    WHERE id = :id AND company_id = :cid";

                $stmt = $this->pdo->prepare($updateSql);
                $stmt->execute([
                    ':pid'     => $plantId,
                    ':title'   => $title,
                    ':qty'     => $quantity,
                    ':step'    => $step,
                    ':payload' => $payloadJson,
                    ':id'      => $requirementId,
                    ':cid'     => $companyId
                ]);

                $this->saveSubTradesAndSkills($requirementId, $data);
                $this->logAudit('REQUIREMENT_DRAFT_UPDATED', $userId, $companyId, ['requirement_id' => $requirementId, 'step' => $step]);

                $this->pdo->commit();
                return [
                    'success'        => true,
                    'message'        => 'Requirement draft saved successfully.',
                    'requirement_id' => $requirementId,
                    'req_code'       => $existing['requirement_id']
                ];
            } else {
                // Generate a temporary draft code or official sequence
                $reqCode = $this->generateRequirementCode();

                $insertSql = "INSERT INTO `manpower_requirements` 
                    (`requirement_id`, `company_id`, `plant_id`, `title`, `quantity_required`, 
                     `qualification`, `board_type`, `experience_required`, `job_location_state`, `job_location_district`, 
                     `job_location_city`, `job_description`, `min_salary`, `max_salary`, `submission_date`, 
                     `application_deadline`, `interview_date`, `target_joining_date`, `priority`, `status`, 
                     `wizard_step`, `draft_payload_json`, `created_by`) 
                    VALUES 
                    (:code, :cid, :pid, :title, :qty, 
                     :qual, :board, :exp, :state, :district, 
                     :city, :desc, :min_sal, :max_sal, CURDATE(), 
                     DATE_ADD(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 
                     :prio, 'DRAFT', :step, :payload, :uid)";

                $stmt = $this->pdo->prepare($insertSql);
                $stmt->execute([
                    ':code'    => $reqCode,
                    ':cid'     => $companyId,
                    ':pid'     => $plantId,
                    ':title'   => $title,
                    ':qty'     => $quantity,
                    ':qual'    => $data['qualification'] ?? 'ITI',
                    ':board'   => $data['board_type'] ?? 'Either',
                    ':exp'     => $data['experience_required'] ?? 'Fresher',
                    ':state'   => $data['job_location_state'] ?? 'Maharashtra',
                    ':district'=> $data['job_location_district'] ?? 'Pune',
                    ':city'    => $data['job_location_city'] ?? 'Pune',
                    ':desc'    => !empty($data['job_description']) ? $data['job_description'] : 'Draft requirement details pending.',
                    ':min_sal' => floatval($data['min_salary'] ?? 15000),
                    ':max_sal' => floatval($data['max_salary'] ?? 20000),
                    ':prio'    => $data['priority'] ?? 'Medium',
                    ':step'    => $step,
                    ':payload' => $payloadJson,
                    ':uid'     => $userId
                ]);

                $newId = (int)$this->pdo->lastInsertId();
                $this->saveSubTradesAndSkills($newId, $data);

                // History
                $this->pdo->prepare("INSERT INTO `requirement_status_history` (`requirement_id`, `new_status`, `changed_by`, `remarks`) 
                    VALUES (:rid, 'DRAFT', :uid, 'Draft saved at step {$step}.')")
                    ->execute([':rid' => $newId, ':uid' => $userId]);

                $this->logAudit('REQUIREMENT_DRAFT_CREATED', $userId, $companyId, ['requirement_id' => $newId, 'code' => $reqCode]);

                $this->pdo->commit();
                return [
                    'success'        => true,
                    'message'        => "New requirement draft initialized ({$reqCode}).",
                    'requirement_id' => $newId,
                    'req_code'       => $reqCode
                ];
            }
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Error saving draft: ' . $e->getMessage()];
        }
    }

    /**
     * Formal Requirement Submission
     * Validates payload, runs approval logic, generates REQ code, records status & history.
     */
    public function submitRequirement(int $companyId, array $data, ?int $requirementId = null): array
    {
        $userId = getUserId();

        // 1. Execute Multi-Stage Validation
        $valResult = $this->validateRequirement($data, $companyId);
        if (!$valResult['is_valid']) {
            return [
                'success'       => false,
                'message'       => 'Requirement validation failed. Please correct the highlighted errors.',
                'errors'        => $valResult['errors'],
                'warnings'      => $valResult['warnings'],
                'target_status' => $valResult['target_status']
            ];
        }

        $targetStatus = $valResult['target_status']; // 'APPROVED' or 'PENDING_REVIEW'
        $title = trim((string)$data['title']);
        $plantId = !empty($data['plant_id']) ? (int)$data['plant_id'] : null;

        // Strict Plant Facility Ownership Verification
        if ($plantId) {
            $pCheck = $this->pdo->prepare("SELECT id FROM `manufacturing_plants` WHERE id = :pid AND company_id = :cid");
            $pCheck->execute([':pid' => $plantId, ':cid' => $companyId]);
            if (!$pCheck->fetch()) {
                return ['success' => false, 'message' => 'Selected manufacturing plant does not belong to your company.'];
            }
        }

        // Trade sum calculation
        $trades = $data['trades'] ?? [];
        $sumVacancies = 0;
        foreach ($trades as $tr) {
            $sumVacancies += (int)($tr['vacancies'] ?? 0);
        }
        $quantity = (int)($data['quantity_required'] ?? $sumVacancies);

        // Backend Capacity Limit Verification
        require_once __DIR__ . '/CompanyController.php';
        $compCtrl = new CompanyController($this->pdo);
        if (!$requirementId) {
            $limitCheck = $compCtrl->checkCompanyLimit($companyId, 'max_requirements');
            if (!$limitCheck['allowed']) {
                return ['success' => false, 'message' => $limitCheck['message']];
            }
        }
        $vacCheck = $compCtrl->checkCompanyLimit($companyId, 'max_vacancies', $quantity);
        if (!$vacCheck['allowed']) {
            return ['success' => false, 'message' => $vacCheck['message']];
        }

        try {
            $this->pdo->beginTransaction();

            $reqCode = '';
            if ($requirementId) {
                // Verify ownership
                $check = $this->pdo->prepare("SELECT id, requirement_id, status FROM `manpower_requirements` WHERE id = :id AND company_id = :cid");
                $check->execute([':id' => $requirementId, ':cid' => $companyId]);
                $existing = $check->fetch();

                if (!$existing) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Requirement not found or access denied.'];
                }

                $reqCode = $existing['requirement_id'];
                $oldStatus = $existing['status'];

                $updateSql = "UPDATE `manpower_requirements` SET 
                    `plant_id` = :pid,
                    `department` = :dept,
                    `industry_sector` = :sector,
                    `requirement_purpose` = :purpose,
                    `title` = :title,
                    `quantity_required` = :qty,
                    `qualification` = :qual,
                    `board_type` = :board,
                    `passing_year_from` = :pfrom,
                    `passing_year_to` = :pto,
                    `experience_required` = :exp,
                    `job_location_state` = :state,
                    `job_location_district` = :district,
                    `job_location_city` = :city,
                    `job_location_pincode` = :pin,
                    `job_description` = :desc,
                    `min_age` = :min_age,
                    `max_age` = :max_age,
                    `gender_preference` = :gender,
                    `min_salary` = :min_sal,
                    `max_salary` = :max_sal,
                    `in_hand_salary` = :in_hand,
                    `salary_type` = :sal_type,
                    `employment_type` = :emp_type,
                    `working_hours` = :hours,
                    `shift_details` = :shift,
                    `weekly_working_days` = :work_days,
                    `rotational_shift` = :rot_shift,
                    `accommodation_provided` = :accom,
                    `food_facility` = :food,
                    `transportation_provided` = :trans,
                    `safety_equipment` = :safety,
                    `other_facilities` = :other_fac,
                    `pf_esic_benefit` = :pf,
                    `overtime_available` = :ot,
                    `bonus_details` = :bonus,
                    `additional_benefits` = :benefits,
                    `application_deadline` = :deadline,
                    `interview_date` = :int_date,
                    `interview_end_date` = :int_end,
                    `target_joining_date` = :join_date,
                    `priority` = :prio,
                    `interview_mode` = :int_mode,
                    `interview_location` = :int_loc,
                    `interview_process` = :int_proc,
                    `number_of_rounds` = :rounds,
                    `technical_interview_required` = :tech_int,
                    `practical_assessment_required` = :pract_int,
                    `document_verification_required` = :doc_int,
                    `contact_person` = :c_person,
                    `contact_email` = :c_email,
                    `contact_phone` = :c_phone,
                    `status` = :status,
                    " . ($targetStatus === 'APPROVED' ? "`approved_by` = :app_by, `approved_at` = NOW()," : "") . "
                    `wizard_step` = 8,
                    `draft_payload_json` = NULL,
                    `updated_at` = NOW()
                    WHERE id = :id AND company_id = :cid";

                $params = [
                    ':pid'        => $plantId,
                    ':dept'       => $data['department'] ?? null,
                    ':sector'     => $data['industry_sector'] ?? null,
                    ':purpose'    => $data['requirement_purpose'] ?? null,
                    ':title'      => $title,
                    ':qty'        => $quantity,
                    ':qual'       => $data['qualification'] ?? 'ITI',
                    ':board'      => $data['board_type'] ?? 'Either',
                    ':pfrom'      => $data['passing_year_from'] ?? null,
                    ':pto'        => $data['passing_year_to'] ?? null,
                    ':exp'        => $data['experience_required'] ?? 'Fresher',
                    ':state'      => $data['job_location_state'] ?? 'Maharashtra',
                    ':district'   => $data['job_location_district'] ?? 'Pune',
                    ':city'       => $data['job_location_city'] ?? 'Pune',
                    ':pin'        => $data['job_location_pincode'] ?? null,
                    ':desc'       => $data['job_description'] ?? '',
                    ':min_age'    => (int)($data['min_age'] ?? 18),
                    ':max_age'    => !empty($data['max_age']) ? (int)$data['max_age'] : null,
                    ':gender'     => $data['gender_preference'] ?? 'Any',
                    ':min_sal'    => floatval($data['min_salary'] ?? 15000),
                    ':max_sal'    => floatval($data['max_salary'] ?? 20000),
                    ':in_hand'    => !empty($data['in_hand_salary']) ? floatval($data['in_hand_salary']) : null,
                    ':sal_type'   => $data['salary_type'] ?? 'Monthly',
                    ':emp_type'   => $data['employment_type'] ?? 'Full Time',
                    ':hours'      => $data['working_hours'] ?? '8 Hours',
                    ':shift'      => $data['shift_details'] ?? 'General Shift',
                    ':work_days'  => (int)($data['weekly_working_days'] ?? 6),
                    ':rot_shift'  => $data['rotational_shift'] ?? 'Yes',
                    ':accom'      => is_array($data['accommodation_provided'] ?? null) ? implode(', ', array_filter($data['accommodation_provided'])) : ($data['accommodation_provided'] ?? 'No'),
                    ':food'       => $data['food_facility'] ?? 'Subsidized',
                    ':trans'      => $data['transportation_provided'] ?? 'No',
                    ':safety'     => $data['safety_equipment'] ?? 'Yes',
                    ':other_fac'  => $data['other_facilities'] ?? null,
                    ':pf'         => $data['pf_esic_benefit'] ?? 'Yes',
                    ':ot'         => $data['overtime_available'] ?? 'No',
                    ':bonus'      => $data['bonus_details'] ?? null,
                    ':benefits'   => $data['additional_benefits'] ?? null,
                    ':deadline'   => $data['application_deadline'] ?? date('Y-m-d', strtotime('+15 days')),
                    ':int_date'   => $data['interview_date'] ?? date('Y-m-d', strtotime('+20 days')),
                    ':int_end'    => !empty($data['interview_end_date']) ? $data['interview_end_date'] : null,
                    ':join_date'  => $data['target_joining_date'] ?? date('Y-m-d', strtotime('+30 days')),
                    ':prio'       => $data['priority'] ?? 'Medium',
                    ':int_mode'   => $data['interview_mode'] ?? 'Plant Campus Walk-in',
                    ':int_loc'    => $data['interview_location'] ?? null,
                    ':int_proc'   => $data['interview_process'] ?? null,
                    ':rounds'     => (int)($data['number_of_rounds'] ?? 2),
                    ':tech_int'   => isset($data['technical_interview_required']) ? (int)$data['technical_interview_required'] : 1,
                    ':pract_int'  => isset($data['practical_assessment_required']) ? (int)$data['practical_assessment_required'] : 1,
                    ':doc_int'    => isset($data['document_verification_required']) ? (int)$data['document_verification_required'] : 1,
                    ':c_person'   => $data['contact_person'] ?? null,
                    ':c_email'    => $data['contact_email'] ?? null,
                    ':c_phone'    => $data['contact_phone'] ?? null,
                    ':status'     => $targetStatus,
                    ':id'         => $requirementId,
                    ':cid'        => $companyId
                ];

                if ($targetStatus === 'APPROVED') {
                    $params[':app_by'] = $userId;
                }

                $stmt = $this->pdo->prepare($updateSql);
                $stmt->execute($params);

                $targetReqId = $requirementId;
            } else {
                // New Requirement Creation
                $reqCode = $this->generateRequirementCode();

                $insertSql = "INSERT INTO `manpower_requirements` 
                    (`requirement_id`, `company_id`, `plant_id`, `department`, `industry_sector`, `requirement_purpose`, 
                     `title`, `quantity_required`, `qualification`, `board_type`, `passing_year_from`, `passing_year_to`, 
                     `experience_required`, `job_location_state`, `job_location_district`, `job_location_city`, `job_location_pincode`, 
                     `job_description`, `min_age`, `max_age`, `gender_preference`, `min_salary`, `max_salary`, `in_hand_salary`, 
                     `salary_type`, `employment_type`, `working_hours`, `shift_details`, `weekly_working_days`, `rotational_shift`, 
                     `accommodation_provided`, `food_facility`, `transportation_provided`, `safety_equipment`, `other_facilities`, 
                     `pf_esic_benefit`, `overtime_available`, `bonus_details`, `additional_benefits`, 
                     `submission_date`, `application_deadline`, `interview_date`, `interview_end_date`, `target_joining_date`, 
                     `priority`, `interview_mode`, `interview_location`, `interview_process`, `number_of_rounds`, 
                     `technical_interview_required`, `practical_assessment_required`, `document_verification_required`, 
                     `contact_person`, `contact_email`, `contact_phone`, `status`, " . ($targetStatus === 'APPROVED' ? "`approved_by`, `approved_at`," : "") . "
                     `wizard_step`, `created_by`) 
                    VALUES 
                    (:code, :cid, :pid, :dept, :sector, :purpose, 
                     :title, :qty, :qual, :board, :pfrom, :pto, 
                     :exp, :state, :district, :city, :pin, 
                     :desc, :min_age, :max_age, :gender, :min_sal, :max_sal, :in_hand, 
                     :sal_type, :emp_type, :hours, :shift, :work_days, :rot_shift, 
                     :accom, :food, :trans, :safety, :other_fac, 
                     :pf, :ot, :bonus, :benefits, 
                     CURDATE(), :deadline, :int_date, :int_end, :join_date, 
                     :prio, :int_mode, :int_loc, :int_proc, :rounds, 
                     :tech_int, :pract_int, :doc_int, 
                     :c_person, :c_email, :c_phone, :status, " . ($targetStatus === 'APPROVED' ? ":app_by, NOW()," : "") . "
                     8, :uid)";

                $params = [
                    ':code'       => $reqCode,
                    ':cid'        => $companyId,
                    ':pid'        => $plantId,
                    ':dept'       => $data['department'] ?? null,
                    ':sector'     => $data['industry_sector'] ?? null,
                    ':purpose'    => $data['requirement_purpose'] ?? null,
                    ':title'      => $title,
                    ':qty'        => $quantity,
                    ':qual'       => $data['qualification'] ?? 'ITI',
                    ':board'      => $data['board_type'] ?? 'Either',
                    ':pfrom'      => $data['passing_year_from'] ?? null,
                    ':pto'        => $data['passing_year_to'] ?? null,
                    ':exp'        => $data['experience_required'] ?? 'Fresher',
                    ':state'      => $data['job_location_state'] ?? 'Maharashtra',
                    ':district'   => $data['job_location_district'] ?? 'Pune',
                    ':city'       => $data['job_location_city'] ?? 'Pune',
                    ':pin'        => $data['job_location_pincode'] ?? null,
                    ':desc'       => $data['job_description'] ?? '',
                    ':min_age'    => (int)($data['min_age'] ?? 18),
                    ':max_age'    => !empty($data['max_age']) ? (int)$data['max_age'] : null,
                    ':gender'     => $data['gender_preference'] ?? 'Any',
                    ':min_sal'    => floatval($data['min_salary'] ?? 15000),
                    ':max_sal'    => floatval($data['max_salary'] ?? 20000),
                    ':in_hand'    => !empty($data['in_hand_salary']) ? floatval($data['in_hand_salary']) : null,
                    ':sal_type'   => $data['salary_type'] ?? 'Monthly',
                    ':emp_type'   => $data['employment_type'] ?? 'Full Time',
                    ':hours'      => $data['working_hours'] ?? '8 Hours',
                    ':shift'      => $data['shift_details'] ?? 'General Shift',
                    ':work_days'  => (int)($data['weekly_working_days'] ?? 6),
                    ':rot_shift'  => $data['rotational_shift'] ?? 'Yes',
                    ':accom'      => is_array($data['accommodation_provided'] ?? null) ? implode(', ', array_filter($data['accommodation_provided'])) : ($data['accommodation_provided'] ?? 'No'),
                    ':food'       => $data['food_facility'] ?? 'Subsidized',
                    ':trans'      => $data['transportation_provided'] ?? 'No',
                    ':safety'     => $data['safety_equipment'] ?? 'Yes',
                    ':other_fac'  => $data['other_facilities'] ?? null,
                    ':pf'         => $data['pf_esic_benefit'] ?? 'Yes',
                    ':ot'         => $data['overtime_available'] ?? 'No',
                    ':bonus'      => $data['bonus_details'] ?? null,
                    ':benefits'   => $data['additional_benefits'] ?? null,
                    ':deadline'   => $data['application_deadline'] ?? date('Y-m-d', strtotime('+15 days')),
                    ':int_date'   => $data['interview_date'] ?? date('Y-m-d', strtotime('+20 days')),
                    ':int_end'    => !empty($data['interview_end_date']) ? $data['interview_end_date'] : null,
                    ':join_date'  => $data['target_joining_date'] ?? date('Y-m-d', strtotime('+30 days')),
                    ':prio'       => $data['priority'] ?? 'Medium',
                    ':int_mode'   => $data['interview_mode'] ?? 'Plant Campus Walk-in',
                    ':int_loc'    => $data['interview_location'] ?? null,
                    ':int_proc'   => $data['interview_process'] ?? null,
                    ':rounds'     => (int)($data['number_of_rounds'] ?? 2),
                    ':tech_int'   => isset($data['technical_interview_required']) ? (int)$data['technical_interview_required'] : 1,
                    ':pract_int'  => isset($data['practical_assessment_required']) ? (int)$data['practical_assessment_required'] : 1,
                    ':doc_int'    => isset($data['document_verification_required']) ? (int)$data['document_verification_required'] : 1,
                    ':c_person'   => $data['contact_person'] ?? null,
                    ':c_email'    => $data['contact_email'] ?? null,
                    ':c_phone'    => $data['contact_phone'] ?? null,
                    ':status'     => $targetStatus,
                    ':uid'        => $userId
                ];

                if ($targetStatus === 'APPROVED') {
                    $params[':app_by'] = $userId;
                }

                $stmt = $this->pdo->prepare($insertSql);
                $stmt->execute($params);

                $targetReqId = (int)$this->pdo->lastInsertId();
            }

            // Save Sub-Trades and Skills
            $this->saveSubTradesAndSkills($targetReqId, $data);

            // Persist Algorithmic Validation Run History
            $valEngine = new RequirementValidationEngine($this->pdo);
            $valEngine->persistValidationHistory($targetReqId, $valResult, $userId);

            // Record Status History
            $remarks = $targetStatus === 'APPROVED' 
                ? 'Requirement validated and auto-approved according to business rules.' 
                : 'Requirement submitted and forwarded to Super Admin for allocation review.';

            $this->pdo->prepare("INSERT INTO `requirement_status_history` (`requirement_id`, `new_status`, `changed_by`, `remarks`) 
                VALUES (:rid, :status, :uid, :rem)")
                ->execute([':rid' => $targetReqId, ':status' => $targetStatus, ':uid' => $userId, ':rem' => $remarks]);

            // Audit Log
            $this->logAudit(
                $targetStatus === 'APPROVED' ? 'REQUIREMENT_APPROVED' : 'REQUIREMENT_SUBMITTED', 
                $userId, 
                $companyId, 
                ['requirement_id' => $targetReqId, 'code' => $reqCode, 'status' => $targetStatus]
            );

            // Dispatch System Notification & Email via Centralized Service
            $notifService = new NotificationService($this->pdo);
            $notifService->dispatchRequirementSubmitted([
                'id'                => $targetReqId,
                'requirement_id'    => $reqCode,
                'title'             => $title,
                'status'            => $targetStatus,
                'quantity_required' => $data['quantity_required'] ?? 0,
                'company_id'        => $companyId
            ], $userId, $companyId);

            $this->pdo->commit();

            return [
                'success'        => true,
                'message'        => "Requirement '{$reqCode}' submitted successfully as '{$targetStatus}'.",
                'requirement_id' => $targetReqId,
                'req_code'       => $reqCode,
                'status'         => $targetStatus,
                'warnings'       => $valResult['warnings']
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Error submitting requirement: ' . $e->getMessage()];
        }
    }

    /**
     * Cancel an Active or Pending Requirement
     */
    public function cancelRequirement(int $companyId, int $requirementId, string $reason): array
    {
        $userId = getUserId();
        $reason = trim($reason);

        if (strlen($reason) < 5) {
            return ['success' => false, 'message' => 'A valid cancellation reason (min 5 characters) is required.'];
        }

        $check = $this->pdo->prepare("SELECT id, requirement_id, status FROM `manpower_requirements` WHERE id = :id AND company_id = :cid");
        $check->execute([':id' => $requirementId, ':cid' => $companyId]);
        $req = $check->fetch();

        if (!$req) {
            return ['success' => false, 'message' => 'Requirement not found or access denied.'];
        }

        if (in_array($req['status'], ['CLOSED', 'CANCELLED', 'FULFILLED'], true)) {
            return ['success' => false, 'message' => "Requirement is already {$req['status']} and cannot be cancelled."];
        }

        $stmt = $this->pdo->prepare("UPDATE `manpower_requirements` SET `status` = 'CANCELLED', `updated_at` = NOW() WHERE id = :id AND company_id = :cid");
        $stmt->execute([':id' => $requirementId, ':cid' => $companyId]);

        // History
        $this->pdo->prepare("INSERT INTO `requirement_status_history` (`requirement_id`, `previous_status`, `new_status`, `changed_by`, `remarks`) 
            VALUES (:rid, :old, 'CANCELLED', :uid, :rem)")
            ->execute([':rid' => $requirementId, ':old' => $req['status'], ':uid' => $userId, ':rem' => $reason]);

        $this->logAudit('REQUIREMENT_CANCELLED', $userId, $companyId, ['requirement_id' => $requirementId, 'reason' => $reason]);

        return ['success' => true, 'message' => "Requirement '{$req['requirement_id']}' has been cancelled."];
    }

    /**
     * Duplicate an Existing Requirement as a New Draft
     */
    public function duplicateRequirement(int $companyId, int $requirementId): array
    {
        $userId = getUserId();
        $source = $this->getRequirementById($companyId, $requirementId);
        if (!$source) {
            return ['success' => false, 'message' => 'Requirement not found or access denied.'];
        }

        try {
            $this->pdo->beginTransaction();

            $newCode = $this->generateRequirementCode();
            $newTitle = 'Copy of ' . $source['title'];

            $insertSql = "INSERT INTO `manpower_requirements` 
                (`requirement_id`, `company_id`, `plant_id`, `department`, `industry_sector`, `requirement_purpose`, 
                 `title`, `quantity_required`, `qualification`, `board_type`, `passing_year_from`, `passing_year_to`, 
                 `experience_required`, `job_location_state`, `job_location_district`, `job_location_city`, `job_location_pincode`, 
                 `job_description`, `min_age`, `max_age`, `gender_preference`, `min_salary`, `max_salary`, `in_hand_salary`, 
                 `salary_type`, `employment_type`, `working_hours`, `shift_details`, `weekly_working_days`, `rotational_shift`, 
                 `accommodation_provided`, `food_facility`, `transportation_provided`, `safety_equipment`, `other_facilities`, 
                 `pf_esic_benefit`, `overtime_available`, `bonus_details`, `additional_benefits`, 
                 `submission_date`, `application_deadline`, `interview_date`, `interview_end_date`, `target_joining_date`, 
                 `priority`, `interview_mode`, `interview_location`, `interview_process`, `number_of_rounds`, 
                 `technical_interview_required`, `practical_assessment_required`, `document_verification_required`, 
                 `contact_person`, `contact_email`, `contact_phone`, `status`, `wizard_step`, `created_by`) 
                VALUES 
                (:code, :cid, :pid, :dept, :sector, :purpose, 
                 :title, :qty, :qual, :board, :pfrom, :pto, 
                 :exp, :state, :district, :city, :pin, 
                 :desc, :min_age, :max_age, :gender, :min_sal, :max_sal, :in_hand, 
                 :sal_type, :emp_type, :hours, :shift, :work_days, :rot_shift, 
                 :accom, :food, :trans, :safety, :other_fac, 
                 :pf, :ot, :bonus, :benefits, 
                 CURDATE(), DATE_ADD(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 22 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 
                 :prio, :int_mode, :int_loc, :int_proc, :rounds, 
                 :tech_int, :pract_int, :doc_int, 
                 :c_person, :c_email, :c_phone, 'DRAFT', 1, :uid)";

            $stmt = $this->pdo->prepare($insertSql);
            $stmt->execute([
                ':code'       => $newCode,
                ':cid'        => $companyId,
                ':pid'        => $source['plant_id'],
                ':dept'       => $source['department'] ?? null,
                ':sector'     => $source['industry_sector'] ?? null,
                ':purpose'    => $source['requirement_purpose'] ?? null,
                ':title'      => $newTitle,
                ':qty'        => $source['quantity_required'],
                ':qual'       => $source['qualification'],
                ':board'      => $source['board_type'],
                ':pfrom'      => $source['passing_year_from'] ?? null,
                ':pto'        => $source['passing_year_to'] ?? null,
                ':exp'        => $source['experience_required'],
                ':state'      => $source['job_location_state'],
                ':district'   => $source['job_location_district'],
                ':city'       => $source['job_location_city'],
                ':pin'        => $source['job_location_pincode'] ?? null,
                ':desc'       => $source['job_description'],
                ':min_age'    => $source['min_age'] ?? 18,
                ':max_age'    => $source['max_age'] ?? null,
                ':gender'     => $source['gender_preference'] ?? 'Any',
                ':min_sal'    => $source['min_salary'],
                ':max_sal'    => $source['max_salary'],
                ':in_hand'    => $source['in_hand_salary'] ?? null,
                ':sal_type'   => $source['salary_type'] ?? 'Monthly',
                ':emp_type'   => $source['employment_type'] ?? 'Full Time',
                ':hours'      => $source['working_hours'] ?? '8 Hours',
                ':shift'      => $source['shift_details'] ?? 'General Shift',
                ':work_days'  => $source['weekly_working_days'] ?? 6,
                ':rot_shift'  => $source['rotational_shift'] ?? 'Yes',
                ':accom'      => $source['accommodation_provided'] ?? 'No',
                ':food'       => $source['food_facility'] ?? 'Subsidized',
                ':trans'      => $source['transportation_provided'] ?? 'No',
                ':safety'     => $source['safety_equipment'] ?? 'Yes',
                ':other_fac'  => $source['other_facilities'] ?? null,
                ':pf'         => $source['pf_esic_benefit'] ?? 'Yes',
                ':ot'         => $source['overtime_available'] ?? 'No',
                ':bonus'      => $source['bonus_details'] ?? null,
                ':benefits'   => $source['additional_benefits'] ?? null,
                ':prio'       => $source['priority'] ?? 'Medium',
                ':int_mode'   => $source['interview_mode'] ?? 'Plant Campus Walk-in',
                ':int_loc'    => $source['interview_location'] ?? null,
                ':int_proc'   => $source['interview_process'] ?? null,
                ':rounds'     => $source['number_of_rounds'] ?? 2,
                ':tech_int'   => $source['technical_interview_required'] ?? 1,
                ':pract_int'  => $source['practical_assessment_required'] ?? 1,
                ':doc_int'    => $source['document_verification_required'] ?? 1,
                ':c_person'   => $source['contact_person'] ?? null,
                ':c_email'    => $source['contact_email'] ?? null,
                ':c_phone'    => $source['contact_phone'] ?? null,
                ':uid'        => $userId
            ]);

            $newReqId = (int)$this->pdo->lastInsertId();

            // Clone sub trades & skills
            if (!empty($source['trades'])) {
                $this->saveSubTradesAndSkills($newReqId, [
                    'trades' => $source['trades'],
                    'skills' => $source['skills'] ?? []
                ]);
            }

            // History
            $this->pdo->prepare("INSERT INTO `requirement_status_history` (`requirement_id`, `new_status`, `changed_by`, `remarks`) 
                VALUES (:rid, 'DRAFT', :uid, :rem)")
                ->execute([
                    ':rid' => $newReqId,
                    ':uid' => $userId,
                    ':rem' => "Duplicated from requirement {$source['requirement_id']}."
                ]);

            $this->logAudit('REQUIREMENT_DUPLICATED', $userId, $companyId, ['source_id' => $requirementId, 'new_id' => $newReqId, 'new_code' => $newCode]);

            $this->pdo->commit();

            return [
                'success'        => true,
                'message'        => "Requirement '{$source['requirement_id']}' duplicated successfully as '{$newCode}'.",
                'requirement_id' => $newReqId,
                'req_code'       => $newCode
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Error duplicating requirement: ' . $e->getMessage()];
        }
    }

    /**
     * Update Requirement Status Lifecycle Transition
     */
    public function updateRequirementStatus(int $companyId, int $requirementId, string $newStatus, string $remarks, ?int $changedBy = null): array
    {
        $userId = $changedBy ?? getUserId();
        $allowedStatuses = [
            'DRAFT', 'SUBMITTED', 'UNDER_VALIDATION', 'NEEDS_CORRECTION', 
            'PENDING_REVIEW', 'APPROVED', 'RECRUITMENT_IN_PROGRESS', 
            'PARTIALLY_FULFILLED', 'FULFILLED', 'CLOSED', 'CANCELLED', 'REJECTED'
        ];

        if (!in_array($newStatus, $allowedStatuses, true)) {
            return ['success' => false, 'message' => "Invalid status '{$newStatus}' specified."];
        }

        $check = $this->pdo->prepare("SELECT id, requirement_id, status FROM `manpower_requirements` WHERE id = :id AND company_id = :cid");
        $check->execute([':id' => $requirementId, ':cid' => $companyId]);
        $req = $check->fetch();

        if (!$req) {
            return ['success' => false, 'message' => 'Requirement not found or access denied.'];
        }

        $oldStatus = $req['status'];
        if ($oldStatus === $newStatus) {
            return ['success' => true, 'message' => "Requirement is already in {$newStatus} status.", 'requirement_id' => $requirementId];
        }

        try {
            $this->pdo->beginTransaction();

            $updateExtra = "";
            $params = [
                ':status' => $newStatus,
                ':id'     => $requirementId,
                ':cid'    => $companyId
            ];

            if ($newStatus === 'APPROVED' && empty($req['approved_at'])) {
                $updateExtra = ", `approved_by` = :app_by, `approved_at` = NOW()";
                $params[':app_by'] = $userId;
            }

            $sql = "UPDATE `manpower_requirements` SET `status` = :status, `updated_at` = NOW() {$updateExtra} WHERE id = :id AND company_id = :cid";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            // History
            $this->pdo->prepare("INSERT INTO `requirement_status_history` (`requirement_id`, `previous_status`, `new_status`, `changed_by`, `remarks`) 
                VALUES (:rid, :old, :new, :uid, :rem)")
                ->execute([
                    ':rid' => $requirementId,
                    ':old' => $oldStatus,
                    ':new' => $newStatus,
                    ':uid' => $userId,
                    ':rem' => $remarks
                ]);

            $this->logAudit('REQUIREMENT_STATUS_UPDATED', $userId, $companyId, [
                'requirement_id'  => $requirementId,
                'previous_status' => $oldStatus,
                'new_status'      => $newStatus,
                'remarks'         => $remarks
            ]);

            $this->pdo->commit();

            return [
                'success'         => true,
                'message'         => "Requirement '{$req['requirement_id']}' status updated from '{$oldStatus}' to '{$newStatus}'.",
                'requirement_id'  => $requirementId,
                'previous_status' => $oldStatus,
                'new_status'      => $newStatus
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Error updating status: ' . $e->getMessage()];
        }
    }

    /**
     * Get Requirement Summary Metrics for Dashboard
     */
    public function getRequirementDashboardStats(int $companyId): array
    {
        $sql = "SELECT 
            COUNT(*) AS total_requirements,
            SUM(CASE WHEN `status` = 'DRAFT' THEN 1 ELSE 0 END) AS total_drafts,
            SUM(CASE WHEN `status` IN ('SUBMITTED', 'UNDER_VALIDATION', 'PENDING_REVIEW') THEN 1 ELSE 0 END) AS pending_validation,
            SUM(CASE WHEN `status` = 'APPROVED' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN `status` = 'RECRUITMENT_IN_PROGRESS' THEN 1 ELSE 0 END) AS in_recruitment,
            SUM(CASE WHEN `status` = 'PARTIALLY_FULFILLED' THEN 1 ELSE 0 END) AS partially_fulfilled,
            SUM(CASE WHEN `status` = 'FULFILLED' THEN 1 ELSE 0 END) AS fulfilled,
            SUM(CASE WHEN `status` IN ('CLOSED', 'CANCELLED', 'REJECTED') THEN 1 ELSE 0 END) AS closed_or_cancelled,
            COALESCE(SUM(`quantity_required`), 0) AS total_vacancies
        FROM `manpower_requirements` 
        WHERE `company_id` = :cid";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cid' => $companyId]);
        $stats = $stmt->fetch() ?: [];

        return [
            'total_requirements'  => (int)($stats['total_requirements'] ?? 0),
            'total_drafts'        => (int)($stats['total_drafts'] ?? 0),
            'pending_validation'  => (int)($stats['pending_validation'] ?? 0),
            'approved'            => (int)($stats['approved'] ?? 0),
            'in_recruitment'      => (int)($stats['in_recruitment'] ?? 0),
            'partially_fulfilled' => (int)($stats['partially_fulfilled'] ?? 0),
            'fulfilled'           => (int)($stats['fulfilled'] ?? 0),
            'closed_or_cancelled' => (int)($stats['closed_or_cancelled'] ?? 0),
            'total_vacancies'     => (int)($stats['total_vacancies'] ?? 0)
        ];
    }

    /**
     * Get Paginated Requirements with Advanced Filters
     */
    public function getRequirements(int $companyId, array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $conditions = ["mr.company_id = :cid"];
        $params = [':cid' => $companyId];

        if (!empty($filters['search'])) {
            $conditions[] = "(mr.title LIKE :s1 OR mr.requirement_id LIKE :s2 OR mr.job_location_city LIKE :s3 OR mp.plant_name LIKE :s4)";
            $sVal = '%' . trim($filters['search']) . '%';
            $params[':s1'] = $sVal;
            $params[':s2'] = $sVal;
            $params[':s3'] = $sVal;
            $params[':s4'] = $sVal;
        }

        if (!empty($filters['status'])) {
            $conditions[] = "mr.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['plant_id'])) {
            $conditions[] = "mr.plant_id = :pid";
            $params[':pid'] = (int)$filters['plant_id'];
        }

        if (!empty($filters['trade'])) {
            $conditions[] = "EXISTS (SELECT 1 FROM `requirement_trades` rt WHERE rt.requirement_id = mr.id AND rt.trade_name = :trade)";
            $params[':trade'] = trim($filters['trade']);
        }

        $whereClause = implode(" AND ", $conditions);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM `manpower_requirements` mr 
            LEFT JOIN `manufacturing_plants` mp ON mr.plant_id = mp.id 
            WHERE $whereClause");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT mr.*, mp.plant_name, mp.plant_code,
                (SELECT GROUP_CONCAT(DISTINCT rt.trade_name SEPARATOR ', ') FROM `requirement_trades` rt WHERE rt.requirement_id = mr.id) AS trade_names,
                (SELECT COUNT(*) FROM `candidate_matches` cm WHERE cm.requirement_id = mr.id) AS matched_count,
                (SELECT COUNT(*) FROM `candidate_matches` cm WHERE cm.requirement_id = mr.id AND cm.status = 'Joined') AS joined_count
            FROM `manpower_requirements` mr
            LEFT JOIN `manufacturing_plants` mp ON mr.plant_id = mp.id
            WHERE $whereClause
            ORDER BY mr.id DESC
            LIMIT :offset, :limit";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll();

        return [
            'data'        => $records,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get Requirement By ID (Strict Ownership Verification)
     */
    public function getRequirementById(int $companyId, int $requirementId): ?array
    {
        $sql = "SELECT mr.*, mp.plant_name, mp.plant_code, mp.plant_address, mp.city AS plant_city,
                u.full_name AS created_by_name,
                app.full_name AS approved_by_name
            FROM `manpower_requirements` mr
            LEFT JOIN `manufacturing_plants` mp ON mr.plant_id = mp.id
            LEFT JOIN `users` u ON mr.created_by = u.id
            LEFT JOIN `users` app ON mr.approved_by = app.id
            WHERE mr.id = :id AND mr.company_id = :cid
            LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $requirementId, ':cid' => $companyId]);
        $req = $stmt->fetch();
        if (!$req) return null;

        // Fetch Trades
        $tradesStmt = $this->pdo->prepare("SELECT * FROM `requirement_trades` WHERE `requirement_id` = :rid ORDER BY id ASC");
        $tradesStmt->execute([':rid' => $requirementId]);
        $req['trades'] = $tradesStmt->fetchAll();

        // Fetch Skills
        $skillsStmt = $this->pdo->prepare("SELECT * FROM `requirement_skills` WHERE `requirement_id` = :rid ORDER BY id ASC");
        $skillsStmt->execute([':rid' => $requirementId]);
        $req['skills'] = $skillsStmt->fetchAll();

        // Fetch Status History
        $histStmt = $this->pdo->prepare("SELECT rsh.*, u.full_name AS changed_by_name 
            FROM `requirement_status_history` rsh
            LEFT JOIN `users` u ON rsh.changed_by = u.id
            WHERE rsh.requirement_id = :rid
            ORDER BY rsh.id DESC");
        $histStmt->execute([':rid' => $requirementId]);
        $req['history'] = $histStmt->fetchAll();

        return $req;
    }

    /**
     * Helper to save sub-trades and skills
     */
    private function saveSubTradesAndSkills(int $requirementId, array $data): void
    {
        // 1. Trades
        if (isset($data['trades']) && is_array($data['trades'])) {
            $this->pdo->prepare("DELETE FROM `requirement_trades` WHERE `requirement_id` = :rid")->execute([':rid' => $requirementId]);

            $tradeStmt = $this->pdo->prepare("INSERT INTO `requirement_trades` 
                (`requirement_id`, `trade_name`, `vacancies`, `qualification`, `board_type`, `passing_year_from`, `passing_year_to`, `required_skills`, `min_experience`, `max_experience`, `max_age`, `gender`, `fresher_allowed`) 
                VALUES 
                (:rid, :name, :vac, :qual, :board, :pfrom, :pto, :skills, :min_exp, :max_exp, :max_age, :gender, :fresher)");

            foreach ($data['trades'] as $tr) {
                $tName = trim((string)($tr['trade_name'] ?? ''));
                $tVac  = max(1, (int)($tr['vacancies'] ?? 1));
                if (!empty($tName)) {
                    $tradeStmt->execute([
                        ':rid'     => $requirementId,
                        ':name'    => $tName,
                        ':vac'     => $tVac,
                        ':qual'    => $tr['qualification'] ?? ($data['qualification'] ?? 'ITI'),
                        ':board'   => $tr['board_type'] ?? ($data['board_type'] ?? 'Either'),
                        ':pfrom'   => $tr['passing_year_from'] ?? ($data['passing_year_from'] ?? null),
                        ':pto'     => $tr['passing_year_to'] ?? ($data['passing_year_to'] ?? null),
                        ':skills'  => $tr['required_skills'] ?? null,
                        ':min_exp' => $tr['min_experience'] ?? ($data['experience_required'] ?? 'Fresher'),
                        ':max_exp' => $tr['max_experience'] ?? null,
                        ':max_age' => !empty($tr['max_age']) ? (int)$tr['max_age'] : null,
                        ':gender'  => (string)($tr['gender'] ?? ($data['gender_preference'] ?? 'Any')),
                        ':fresher' => isset($tr['fresher_allowed']) ? (int)$tr['fresher_allowed'] : 1
                    ]);
                }
            }
        }

        // 2. Skills
        if (isset($data['skills'])) {
            $skillsList = is_array($data['skills']) 
                ? $data['skills'] 
                : array_filter(array_map('trim', explode(',', (string)$data['skills'])));

            if (!empty($skillsList)) {
                $this->pdo->prepare("DELETE FROM `requirement_skills` WHERE `requirement_id` = :rid")->execute([':rid' => $requirementId]);
                $skillStmt = $this->pdo->prepare("INSERT INTO `requirement_skills` (`requirement_id`, `skill_name`) VALUES (:rid, :sname)");
                foreach ($skillsList as $sk) {
                    $sName = is_array($sk) ? trim((string)($sk['skill_name'] ?? '')) : trim((string)$sk);
                    if (!empty($sName)) {
                        $skillStmt->execute([':rid' => $requirementId, ':sname' => $sName]);
                    }
                }
            }
        }
    }

    /**
     * Trigger Manual Revalidation for an existing requirement
     * Strictly verifies company ownership or Super Admin authorization.
     * Re-runs all 7 validation categories and persists run to `requirement_validation_history`.
     */
    public function revalidateRequirement(int $companyId, int $requirementId, ?int $userId = null): array
    {
        $userId = $userId ?? (function_exists('getUserId') ? getUserId() : null);

        // 1. Fetch requirement and verify isolation
        $isSA = (function_exists('isSuperAdmin') && isSuperAdmin()) || $companyId === 0;
        if ($isSA) {
            $stmt = $this->pdo->prepare("SELECT * FROM `manpower_requirements` WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $requirementId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM `manpower_requirements` WHERE id = :id AND company_id = :cid LIMIT 1");
            $stmt->execute([':id' => $requirementId, ':cid' => $companyId]);
        }
        $req = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$req) {
            return [
                'success' => false,
                'message' => 'Requirement not found or access denied.'
            ];
        }

        $effectiveCid = (int)$req['company_id'];

        // 2. Fetch associated trades
        $trStmt = $this->pdo->prepare("SELECT * FROM `requirement_trades` WHERE requirement_id = :rid ORDER BY id ASC");
        $trStmt->execute([':rid' => $requirementId]);
        $trades = $trStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Fetch associated skills
        $skStmt = $this->pdo->prepare("SELECT skill_name FROM `requirement_skills` WHERE requirement_id = :rid ORDER BY id ASC");
        $skStmt->execute([':rid' => $requirementId]);
        $skills = $skStmt->fetchAll(PDO::FETCH_COLUMN);

        $payload = $req;
        $payload['trades'] = $trades;
        $payload['skills'] = $skills;

        // 4. Run Algorithmic Validation Engine
        $valEngine = new RequirementValidationEngine($this->pdo);
        $valResult = $valEngine->validate(
            $payload,
            $effectiveCid,
            $userId,
            $requirementId,
            true, // autoPersist
            'MANUAL_REVALIDATION'
        );

        // 5. Audit Log
        $this->logAudit('REQUIREMENT_REVALIDATED', $userId, $effectiveCid, [
            'requirement_id'    => $requirementId,
            'code'              => $req['requirement_id'],
            'validation_status' => $valResult['validation_status'],
            'errors_count'      => count($valResult['errors']),
            'warnings_count'    => count($valResult['warnings'])
        ]);

        return [
            'success'           => true,
            'requirement_id'    => $requirementId,
            'req_code'          => $req['requirement_id'],
            'validation_status' => $valResult['validation_status'],
            'is_valid'          => $valResult['is_valid'],
            'errors'            => $valResult['errors'],
            'warnings'          => $valResult['warnings'],
            'rule_results'      => $valResult['rule_results'],
            'categories'        => $valResult['categories'],
            'requirement'       => $req
        ];
    }

    /**
     * Helper to log audit event
     */
    private function logAudit(string $action, ?int $userId, ?int $companyId, array $details = []): void
    {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Internal';
            $stmt = $this->pdo->prepare("INSERT INTO `audit_logs` 
                (`user_id`, `company_id`, `action`, `ip_address`, `user_agent`, `details_json`) 
                VALUES (:uid, :cid, :action, :ip, :ua, :details)");
            $stmt->execute([
                ':uid'     => $userId,
                ':cid'     => $companyId,
                ':action'  => $action,
                ':ip'      => $ip,
                ':ua'      => substr($ua, 0, 255),
                ':details' => json_encode($details)
            ]);
        } catch (Exception $e) {
            // Silently absorb audit log failure
        }
    }
}

