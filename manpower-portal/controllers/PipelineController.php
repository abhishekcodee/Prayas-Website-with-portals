<?php
/**
 * Recruitment Pipeline Management Controller (Screening to Joining)
 * 
 * Project: Industry Manpower Requirement Portal
 * Organization: PRAYAS – The Automation Academy
 * Technology: Core PHP + PDO + MySQL
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/notification_service.php';

class PipelineController
{
    private PDO $pdo;
    private NotificationService $notifService;

    // Standardized Pipeline Stages
    public const STAGES = [
        'SOURCED'             => 'Matched',
        'SHORTLISTED'         => 'Shortlisted',
        'SCREENING'           => 'Screened',
        'INTERVIEW_SCHEDULED' => 'Scheduled',
        'INTERVIEW_COMPLETED' => 'Completed',
        'SELECTED'            => 'Selected',
        'OFFER_RELEASED'      => 'Offer Released',
        'OFFER_ACCEPTED'      => 'Offer Accepted',
        'OFFER_DECLINED'      => 'Offer Declined',
        'JOINING_PENDING'     => 'Joining Pending',
        'JOINED'              => 'Joined',
        'REJECTED'            => 'Rejected',
        'ON_HOLD'             => 'On Hold',
        'WITHDRAWN'           => 'Withdrawn',
        'NO_SHOW'             => 'No Show',
        'CLOSED'              => 'Closed'
    ];

    // Approved Valid Stage Transitions State Machine
    public const ALLOWED_TRANSITIONS = [
        'Matched'          => ['Shortlisted', 'Rejected', 'On Hold'],
        'Shortlisted'      => ['Screened', 'Scheduled', 'Rejected', 'On Hold'],
        'Screened'         => ['Scheduled', 'Completed', 'Rejected', 'On Hold', 'Shortlisted'],
        'Scheduled'        => ['Completed', 'Cancelled', 'No Show', 'Rescheduled', 'On Hold'],
        'Completed'        => ['Selected', 'Rejected', 'On Hold', 'Scheduled'],
        'Selected'         => ['Offer Released', 'Rejected', 'On Hold'],
        'Offer Released'   => ['Offer Accepted', 'Offer Declined', 'Withdrawn', 'Rejected'],
        'Offer Accepted'   => ['Joining Pending', 'Joined', 'Withdrawn'],
        'Offer Declined'   => ['Closed', 'Matched'],
        'Joining Pending'  => ['Joined', 'No Show', 'Withdrawn', 'Joining Delayed'],
        'Joined'           => ['Closed'],
        'On Hold'          => ['Shortlisted', 'Screened', 'Scheduled', 'Selected', 'Rejected'],
        'Rescheduled'      => ['Scheduled', 'Cancelled'],
        'Cancelled'        => ['Scheduled', 'Shortlisted'],
        'No Show'          => ['Scheduled', 'Closed'],
        'Rejected'         => [], // Terminal
        'Withdrawn'        => [], // Terminal
        'Closed'           => []  // Terminal
    ];

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->notifService = new NotificationService($this->pdo);
    }

    /**
     * State Machine: Check if a stage transition is valid.
     */
    public function canTransition(string $currentStage, string $targetStage): bool
    {
        if ($currentStage === $targetStage) {
            return true;
        }
        $allowed = self::ALLOWED_TRANSITIONS[$currentStage] ?? [];
        return in_array($targetStage, $allowed, true);
    }

    /**
     * Transition candidate to a target stage with transition verification and audit logging.
     */
    public function transitionStage(int $matchId, string $targetStage, ?string $notes = null, int $userId = 1, bool $bypassCheck = false): array
    {
        $match = $this->getMatchDetails($matchId);
        if (!$match) {
            return ['success' => false, 'message' => 'Candidate match record not found.'];
        }

        $currentStage = $match['status'];
        if (!$bypassCheck && !$this->canTransition($currentStage, $targetStage)) {
            return [
                'success' => false, 
                'message' => "Invalid transition: Cannot move candidate from '{$currentStage}' to '{$targetStage}'."
            ];
        }

        try {
            $this->pdo->beginTransaction();

            $this->pdo->prepare("UPDATE `candidate_matches` SET `status` = :st WHERE id = :id")
                ->execute([':st' => $targetStage, ':id' => $matchId]);

            $this->recordStageHistory($matchId, $currentStage, $targetStage, $notes, $userId);

            $this->pdo->commit();
            return ['success' => true, 'message' => "Candidate transitioned from '{$currentStage}' to '{$targetStage}'."];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to transition stage: ' . $e->getMessage()];
        }
    }

    /**
     * STAGE 3: Record Candidate Screening Assessment
     */
    public function recordScreening(int $matchId, array $data, int $recruiterId): array
    {
        $match = $this->getMatchDetails($matchId);
        if (!$match) {
            return ['success' => false, 'message' => 'Candidate match record not found.'];
        }

        $outcome = trim((string)($data['screening_outcome'] ?? 'Passed'));
        $validOutcomes = ['Passed', 'Failed', 'On Hold', 'Needs Re-Screening'];
        if (!in_array($outcome, $validOutcomes, true)) {
            return ['success' => false, 'message' => 'Invalid screening outcome.'];
        }

        $newStage = match ($outcome) {
            'Passed'            => 'Screened',
            'Failed'            => 'Rejected',
            'On Hold'           => 'On Hold',
            'Needs Re-Screening'=> 'Shortlisted'
        };

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("INSERT INTO `candidate_screening` 
                (`match_id`, `screener_id`, `screening_outcome`, `willing_to_relocate`, `candidate_availability`, `expected_salary`, `verified_trade_skills`, `screening_notes`, `screened_at`) 
                VALUES 
                (:mid, :sid, :outcome, :relocate, :avail, :salary, :verified, :notes, NOW())");
            
            $stmt->execute([
                ':mid'      => $matchId,
                ':sid'      => $recruiterId,
                ':outcome'  => $outcome,
                ':relocate' => trim((string)($data['willing_to_relocate'] ?? 'Yes')),
                ':avail'    => trim((string)($data['candidate_availability'] ?? 'Immediate')),
                ':salary'   => trim((string)($data['expected_salary'] ?? '')),
                ':verified' => !empty($data['verified_trade_skills']) ? 1 : 1,
                ':notes'    => trim((string)($data['screening_notes'] ?? ''))
            ]);

            // Update match status
            $this->pdo->prepare("UPDATE `candidate_matches` SET `status` = :st WHERE id = :id")
                ->execute([':st' => $newStage, ':id' => $matchId]);

            // Record stage history
            $this->recordStageHistory($matchId, $match['status'], $newStage, "Screening: {$outcome}. " . ($data['screening_notes'] ?? ''), $recruiterId);

            $this->pdo->commit();
            return ['success' => true, 'message' => "Screening successfully recorded as '{$outcome}'."];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to record screening: ' . $e->getMessage()];
        }
    }

    /**
     * STAGE 4: Schedule Candidate Interview
     */
    public function scheduleInterview(int $matchId, array $data, int $recruiterId): array
    {
        $match = $this->getMatchDetails($matchId);
        if (!$match) {
            return ['success' => false, 'message' => 'Candidate match record not found.'];
        }

        $datetime = trim((string)($data['interview_datetime'] ?? ''));
        if (empty($datetime) || strtotime($datetime) === false) {
            return ['success' => false, 'message' => 'Valid interview date and time is required.'];
        }

        if (strtotime($datetime) < time()) {
            return ['success' => false, 'message' => 'Interview cannot be scheduled in the past.'];
        }

        $venue = trim((string)($data['venue_or_link'] ?? ''));
        if (empty($venue)) {
            return ['success' => false, 'message' => 'Interview venue address or online link is required.'];
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("INSERT INTO `interviews` 
                (`match_id`, `interview_datetime`, `venue_or_link`, `interview_mode`, `interview_round`, `interviewer_name`, `interviewer_designation`, `status`, `interview_remarks`, `scheduled_by`, `created_at`) 
                VALUES 
                (:mid, :dt, :venue, :mode, :round, :name, :desig, 'Scheduled', :rem, :uid, NOW())");
            
            $stmt->execute([
                ':mid'   => $matchId,
                ':dt'    => $datetime,
                ':venue' => $venue,
                ':mode'  => trim((string)($data['interview_mode'] ?? 'In-Person')),
                ':round' => trim((string)($data['interview_round'] ?? 'Round 1 - Technical')),
                ':name'  => trim((string)($data['interviewer_name'] ?? 'Interview Panel')),
                ':desig' => trim((string)($data['interviewer_designation'] ?? 'Technical Evaluator')),
                ':rem'   => trim((string)($data['interview_remarks'] ?? 'Scheduled interview drive')),
                ':uid'   => $recruiterId
            ]);
            $interviewId = (int)$this->pdo->lastInsertId();

            // Update candidate status to Scheduled
            $this->pdo->prepare("UPDATE `candidate_matches` SET `status` = 'Scheduled' WHERE id = :id")
                ->execute([':id' => $matchId]);

            $this->recordStageHistory($matchId, $match['status'], 'Scheduled', "Interview scheduled for {$datetime} at {$venue}", $recruiterId);

            // Notify Industry Admin
            $this->notifService->sendInApp(
                (int)$match['created_by'],
                'INTERVIEW_SCHEDULED',
                "Interview Scheduled: {$match['candidate_name']}",
                "Interview scheduled for {$match['candidate_name']} ({$match['trade']}) on {$datetime}.",
                "/views/recruiter/recruitment_pipeline.php",
                (int)$match['company_id'],
                'interview',
                $interviewId
            );

            $this->pdo->commit();
            return ['success' => true, 'interview_id' => $interviewId, 'message' => "Interview scheduled successfully for {$datetime}."];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to schedule interview: ' . $e->getMessage()];
        }
    }

    /**
     * STAGE 5 & 6: Submit Interview Evaluation Feedback
     */
    public function recordInterviewFeedback(int $interviewId, array $data, int $evaluatorId): array
    {
        $stmt = $this->pdo->prepare("SELECT i.*, cm.id as match_id, cm.status as match_status, cm.candidate_name, mr.company_id 
            FROM `interviews` i 
            JOIN `candidate_matches` cm ON cm.id = i.match_id 
            JOIN `manpower_requirements` mr ON mr.id = cm.requirement_id 
            WHERE i.id = :id LIMIT 1");
        $stmt->execute([':id' => $interviewId]);
        $interview = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$interview) {
            return ['success' => false, 'message' => 'Interview record not found.'];
        }

        $recommendation = trim((string)($data['recommendation'] ?? 'Recommended'));
        $validRecs = ['Strongly Recommended', 'Recommended', 'Hold', 'Not Recommended'];
        if (!in_array($recommendation, $validRecs, true)) {
            return ['success' => false, 'message' => 'Invalid interview recommendation.'];
        }

        try {
            $this->pdo->beginTransaction();

            $fbStmt = $this->pdo->prepare("INSERT INTO `interview_feedback` 
                (`interview_id`, `technical_rating`, `practical_rating`, `trade_knowledge_rating`, `communication_rating`, `safety_rating`, `overall_rating`, `recommendation`, `feedback_comments`, `submitted_by`, `submitted_at`) 
                VALUES 
                (:iid, :tech, :pract, :trade, :comm, :safe, :overall, :rec, :comments, :uid, NOW())");
            
            $fbStmt->execute([
                ':iid'      => $interviewId,
                ':tech'     => max(1, min(5, (int)($data['technical_rating'] ?? 3))),
                ':pract'    => max(1, min(5, (int)($data['practical_rating'] ?? 3))),
                ':trade'    => max(1, min(5, (int)($data['trade_knowledge_rating'] ?? 3))),
                ':comm'     => max(1, min(5, (int)($data['communication_rating'] ?? 3))),
                ':safe'     => max(1, min(5, (int)($data['safety_rating'] ?? 3))),
                ':overall'  => max(1, min(5, (int)($data['overall_rating'] ?? 3))),
                ':rec'      => $recommendation,
                ':comments' => trim((string)($data['feedback_comments'] ?? 'Evaluated according to technical standards.')),
                ':uid'      => $evaluatorId
            ]);

            // Update interview status to Completed
            $this->pdo->prepare("UPDATE `interviews` SET `status` = 'Completed' WHERE id = :id")
                ->execute([':id' => $interviewId]);

            // Update candidate match status
            $newStage = ($recommendation === 'Not Recommended') ? 'Rejected' : 'Completed';
            $this->pdo->prepare("UPDATE `candidate_matches` SET `status` = :st WHERE id = :id")
                ->execute([':st' => $newStage, ':id' => $interview['match_id']]);

            $this->recordStageHistory(
                (int)$interview['match_id'], 
                $interview['match_status'], 
                $newStage, 
                "Interview Completed. Recommendation: {$recommendation}. Rating: " . ($data['overall_rating'] ?? 3) . "/5", 
                $evaluatorId
            );

            $this->pdo->commit();
            return ['success' => true, 'message' => "Interview evaluation recorded with recommendation '{$recommendation}'."];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to record feedback: ' . $e->getMessage()];
        }
    }

    /**
     * STAGE 7: Final Candidate Selection Decision
     */
    public function recordSelectionDecision(int $matchId, array $data, int $decisionMakerId): array
    {
        $match = $this->getMatchDetails($matchId);
        if (!$match) {
            return ['success' => false, 'message' => 'Candidate match not found.'];
        }

        $decision = trim((string)($data['selection_status'] ?? 'Selected'));
        $validDecisions = ['Selected', 'Rejected', 'On Hold'];
        if (!in_array($decision, $validDecisions, true)) {
            return ['success' => false, 'message' => 'Invalid selection decision.'];
        }

        try {
            $this->pdo->beginTransaction();

            if ($decision === 'Selected') {
                $designation = trim((string)($data['offered_designation'] ?? $match['trade'] . ' Technician'));
                $ctc         = floatval($data['offered_ctc'] ?? $match['min_salary'] ?? 15000.0);
                $offerDate   = !empty($data['offer_date']) ? $data['offer_date'] : date('Y-m-d');
                $targetJoin  = !empty($data['target_joining_date']) ? $data['target_joining_date'] : date('Y-m-d', strtotime('+15 days'));

                $selStmt = $this->pdo->prepare("INSERT INTO `selections` 
                    (`match_id`, `offered_designation`, `offered_ctc`, `offer_date`, `target_joining_date`, `selection_status`, `decision_remarks`, `selected_by`, `created_at`) 
                    VALUES 
                    (:mid, :desig, :ctc, :odate, :jdate, 'Selected', :rem, :uid, NOW()) 
                    ON DUPLICATE KEY UPDATE 
                        `offered_designation` = VALUES(`offered_designation`),
                        `offered_ctc`         = VALUES(`offered_ctc`),
                        `offer_date`          = VALUES(`offer_date`),
                        `target_joining_date` = VALUES(`target_joining_date`),
                        `selection_status`    = 'Selected',
                        `decision_remarks`    = VALUES(`decision_remarks`),
                        `selected_by`         = VALUES(`selected_by`)");
                
                $selStmt->execute([
                    ':mid'   => $matchId,
                    ':desig' => $designation,
                    ':ctc'   => $ctc,
                    ':odate' => $offerDate,
                    ':jdate' => $targetJoin,
                    ':rem'   => trim((string)($data['decision_remarks'] ?? 'Selected based on ground technical assessment')),
                    ':uid'   => $decisionMakerId
                ]);

                $newStage = 'Selected';
            } else {
                $newStage = $decision; // 'Rejected' or 'On Hold'
            }

            $this->pdo->prepare("UPDATE `candidate_matches` SET `status` = :st WHERE id = :id")
                ->execute([':st' => $newStage, ':id' => $matchId]);

            $this->recordStageHistory($matchId, $match['status'], $newStage, "Final Selection Decision: {$decision}. " . ($data['decision_remarks'] ?? ''), $decisionMakerId);

            // Notify Industry
            if ($decision === 'Selected') {
                $this->notifService->sendInApp(
                    (int)$match['created_by'],
                    'CANDIDATE_SELECTED',
                    "Candidate Selected: {$match['candidate_name']}",
                    "Candidate {$match['candidate_name']} ({$match['trade']}) has been officially SELECTED for requirement {$match['requirement_id']}.",
                    "/views/recruiter/recruitment_pipeline.php",
                    (int)$match['company_id'],
                    'candidate',
                    $matchId
                );
            }

            $selectionId = 0;
            if ($decision === 'Selected') {
                $selIdStmt = $this->pdo->prepare("SELECT id FROM `selections` WHERE match_id = :mid LIMIT 1");
                $selIdStmt->execute([':mid' => $matchId]);
                $selectionId = (int)$selIdStmt->fetchColumn();
            }

            $this->pdo->commit();
            return ['success' => true, 'selection_id' => $selectionId, 'message' => "Candidate selection decision '{$decision}' recorded successfully."];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to record selection decision: ' . $e->getMessage()];
        }
    }

    /**
     * STAGE 8: Create & Release Formal Offer Letter
     */
    public function createAndReleaseOffer(int $selectionId, array $data, int $userId): array
    {
        $selStmt = $this->pdo->prepare("SELECT s.*, cm.id as match_id, cm.status as match_status, cm.candidate_name, mr.company_id, mr.requirement_id as req_code, mr.created_by 
            FROM `selections` s 
            JOIN `candidate_matches` cm ON cm.id = s.match_id 
            JOIN `manpower_requirements` mr ON mr.id = cm.requirement_id 
            WHERE s.id = :id LIMIT 1");
        $selStmt->execute([':id' => $selectionId]);
        $selection = $selStmt->fetch(PDO::FETCH_ASSOC);

        if (!$selection) {
            return ['success' => false, 'message' => 'Selection record not found.'];
        }

        $refNo = 'OFFER-' . date('Ym') . '-' . sprintf('%04d', $selectionId);
        $validityDate = !empty($data['validity_date']) ? $data['validity_date'] : date('Y-m-d', strtotime('+7 days'));
        $joiningDate  = !empty($data['proposed_joining_date']) ? $data['proposed_joining_date'] : $selection['target_joining_date'];

        try {
            $this->pdo->beginTransaction();

            $offerStmt = $this->pdo->prepare("INSERT INTO `offer_letters` 
                (`selection_id`, `letter_reference_no`, `job_designation`, `salary_offered`, `salary_type`, `benefits_summary`, `job_location`, `issue_date`, `validity_date`, `proposed_joining_date`, `status`, `offer_remarks`, `approved_by`, `created_at`) 
                VALUES 
                (:sid, :ref, :desig, :sal, :stype, :benefits, :loc, CURDATE(), :vdate, :jdate, 'Released', :rem, :uid, NOW()) 
                ON DUPLICATE KEY UPDATE 
                    `letter_reference_no` = VALUES(`letter_reference_no`),
                    `job_designation`     = VALUES(`job_designation`),
                    `salary_offered`      = VALUES(`salary_offered`),
                    `validity_date`       = VALUES(`validity_date`),
                    `proposed_joining_date` = VALUES(`proposed_joining_date`),
                    `status`              = 'Released',
                    `approved_by`         = VALUES(`approved_by`)");
            
            $offerStmt->execute([
                ':sid'      => $selectionId,
                ':ref'      => $refNo,
                ':desig'    => trim((string)($data['job_designation'] ?? $selection['offered_designation'])),
                ':sal'      => floatval($data['salary_offered'] ?? $selection['offered_ctc']),
                ':stype'    => trim((string)($data['salary_type'] ?? 'Monthly')),
                ':benefits' => trim((string)($data['benefits_summary'] ?? 'PF, ESIC, Industrial Transport, Subsidized Canteen')),
                ':loc'      => trim((string)($data['job_location'] ?? 'Plant Facility')),
                ':vdate'    => $validityDate,
                ':jdate'    => $joiningDate,
                ':rem'      => trim((string)($data['offer_remarks'] ?? 'Offer letter released and queued for candidate acceptance')),
                ':uid'      => $userId
            ]);

            // Update pipeline status
            $this->pdo->prepare("UPDATE `selections` SET `selection_status` = 'Offer Released' WHERE id = :id")
                ->execute([':id' => $selectionId]);
            $this->pdo->prepare("UPDATE `candidate_matches` SET `status` = 'Offer Released' WHERE id = :id")
                ->execute([':id' => $selection['match_id']]);

            $this->recordStageHistory(
                (int)$selection['match_id'], 
                $selection['match_status'], 
                'Offer Released', 
                "Offer Reference {$refNo} issued with proposed joining on {$joiningDate}", 
                $userId
            );

            $this->pdo->commit();
            return ['success' => true, 'reference_no' => $refNo, 'message' => "Offer letter '{$refNo}' released successfully."];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to release offer: ' . $e->getMessage()];
        }
    }

    /**
     * STAGE 9: Record Candidate Offer Response (Accepted / Declined)
     */
    public function recordOfferResponse(int $offerId, string $response, string $notes, int $recruiterId): array
    {
        $stmt = $this->pdo->prepare("SELECT ol.*, s.match_id, cm.status as match_status 
            FROM `offer_letters` ol 
            JOIN `selections` s ON s.id = ol.selection_id 
            JOIN `candidate_matches` cm ON cm.id = s.match_id 
            WHERE ol.id = :id LIMIT 1");
        $stmt->execute([':id' => $offerId]);
        $offer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$offer) {
            return ['success' => false, 'message' => 'Offer letter record not found.'];
        }

        if (!in_array($response, ['Accepted', 'Declined'], true)) {
            return ['success' => false, 'message' => 'Invalid offer response. Must be Accepted or Declined.'];
        }

        try {
            $this->pdo->beginTransaction();

            $newOfferStatus = ($response === 'Accepted') ? 'Accepted' : 'Declined';
            $newMatchStatus = ($response === 'Accepted') ? 'Offer Accepted' : 'Offer Declined';

            $this->pdo->prepare("UPDATE `offer_letters` 
                SET `status` = :st, `candidate_response_notes` = :notes 
                WHERE id = :id")
                ->execute([':st' => $newOfferStatus, ':notes' => trim($notes), ':id' => $offerId]);

            $this->pdo->prepare("UPDATE `selections` SET `selection_status` = :st WHERE id = :id")
                ->execute([':st' => $newMatchStatus, ':id' => $offer['selection_id']]);

            $this->pdo->prepare("UPDATE `candidate_matches` SET `status` = :st WHERE id = :id")
                ->execute([':st' => $newMatchStatus, ':id' => $offer['match_id']]);

            $this->recordStageHistory(
                (int)$offer['match_id'], 
                $offer['match_status'], 
                $newMatchStatus, 
                "Candidate Offer {$response}: {$notes}", 
                $recruiterId
            );

            $this->pdo->commit();
            return ['success' => true, 'message' => "Candidate offer response '{$response}' recorded successfully."];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to record offer response: ' . $e->getMessage()];
        }
    }

    /**
     * STAGE 10 & 11: Confirm Candidate Physical Joining on Ground
     */
    public function confirmJoining(int $selectionId, array $data, int $verifierId): array
    {
        $selStmt = $this->pdo->prepare("SELECT s.*, cm.id as match_id, cm.status as match_status, cm.candidate_name, 
            mr.company_id, mr.requirement_id as req_code, mr.created_by, mr.id as req_id, mr.quantity_required, mr.status as req_status
            FROM `selections` s 
            JOIN `candidate_matches` cm ON cm.id = s.match_id 
            JOIN `manpower_requirements` mr ON mr.id = cm.requirement_id 
            WHERE s.id = :id LIMIT 1");
        $selStmt->execute([':id' => $selectionId]);
        $selection = $selStmt->fetch(PDO::FETCH_ASSOC);

        if (!$selection) {
            return ['success' => false, 'message' => 'Selection record not found.'];
        }

        $joiningStatus = trim((string)($data['joining_status'] ?? 'Joined'));
        $validStatuses = ['Joined', 'Joining Pending', 'Joining Delayed', 'Did Not Join', 'No Show', 'Cancelled'];
        if (!in_array($joiningStatus, $validStatuses, true)) {
            return ['success' => false, 'message' => 'Invalid joining status specified.'];
        }

        $actualDate = !empty($data['actual_joining_date']) ? $data['actual_joining_date'] : date('Y-m-d');

        try {
            $this->pdo->beginTransaction();

            $joinStmt = $this->pdo->prepare("INSERT INTO `joinings` 
                (`selection_id`, `actual_joining_date`, `plant_reporting_unit`, `joining_status`, `onboarding_remarks`, `delay_reason`, `verified_by`, `created_at`) 
                VALUES 
                (:sid, :adate, :unit, :st, :rem, :delay, :uid, NOW()) 
                ON DUPLICATE KEY UPDATE 
                    `actual_joining_date`  = VALUES(`actual_joining_date`),
                    `plant_reporting_unit` = VALUES(`plant_reporting_unit`),
                    `joining_status`       = VALUES(`joining_status`),
                    `onboarding_remarks`   = VALUES(`onboarding_remarks`),
                    `delay_reason`         = VALUES(`delay_reason`),
                    `verified_by`          = VALUES(`verified_by`)");
            
            $joinStmt->execute([
                ':sid'   => $selectionId,
                ':adate' => $actualDate,
                ':unit'  => trim((string)($data['plant_reporting_unit'] ?? 'Main Plant Facility')),
                ':st'    => $joiningStatus,
                ':rem'   => trim((string)($data['onboarding_remarks'] ?? 'Ground onboarding completed.')),
                ':delay' => trim((string)($data['delay_reason'] ?? '')),
                ':uid'   => $verifierId
            ]);

            $newMatchStatus = match ($joiningStatus) {
                'Joined'          => 'Joined',
                'No Show'         => 'No Show',
                'Joining Delayed' => 'Joining Pending',
                default           => 'Withdrawn'
            };

            $this->pdo->prepare("UPDATE `candidate_matches` SET `status` = :st WHERE id = :id")
                ->execute([':st' => $newMatchStatus, ':id' => $selection['match_id']]);

            $this->recordStageHistory(
                (int)$selection['match_id'], 
                $selection['match_status'], 
                $newMatchStatus, 
                "Joining Status: {$joiningStatus} on {$actualDate}. Remarks: " . ($data['onboarding_remarks'] ?? ''), 
                $verifierId
            );

            // Requisition Fulfillment Integration: Update requirement fulfillment state
            if ($joiningStatus === 'Joined') {
                $reqId = (int)$selection['req_id'];
                $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM `candidate_matches` WHERE requirement_id = :rid AND `status` = 'Joined'");
                $countStmt->execute([':rid' => $reqId]);
                $totalJoined = (int)$countStmt->fetchColumn();

                $needed = (int)$selection['quantity_required'];
                $newReqStatus = ($totalJoined >= $needed) ? 'FULFILLED' : 'PARTIALLY_FULFILLED';

                if ($selection['req_status'] !== $newReqStatus && $selection['req_status'] !== 'FULFILLED') {
                    $this->pdo->prepare("UPDATE `manpower_requirements` SET `status` = :st, `updated_at` = NOW() WHERE id = :rid")
                        ->execute([':st' => $newReqStatus, ':rid' => $reqId]);
                    
                    $this->pdo->prepare("INSERT INTO `requirement_status_history` 
                        (`requirement_id`, `new_status`, `changed_by`, `remarks`) 
                        VALUES (:rid, :to_st, :uid, :rem)")
                        ->execute([
                            ':rid'   => $reqId,
                            ':to_st' => $newReqStatus,
                            ':uid'   => $verifierId,
                            ':rem'   => "Requirement fulfillment update: {$totalJoined}/{$needed} vacancies confirmed joined."
                        ]);
                }
            }

            // Notify Industry
            if ($joiningStatus === 'Joined') {
                $this->notifService->sendInApp(
                    (int)$selection['created_by'],
                    'JOINING_CONFIRMED',
                    "Joining Confirmed: {$selection['candidate_name']}",
                    "Candidate {$selection['candidate_name']} has reported on-site and joined your team for requirement {$selection['req_code']}.",
                    "/views/industry/candidate_matches.php?status=Joined",
                    (int)$selection['company_id'],
                    'joining',
                    $selectionId
                );
            }

            $this->pdo->commit();
            return ['success' => true, 'message' => "Candidate joining confirmed as '{$joiningStatus}'."];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to confirm joining: ' . $e->getMessage()];
        }
    }

    /**
     * Bulk Action Execution with Pre-Validation and Audit Logging
     */
    public function executeBulkAction(string $action, array $matchIds, array $payload, int $userId, ?int $companyId = null): array
    {
        $validActions = ['bulk_shortlist', 'bulk_advance', 'bulk_share', 'bulk_hold', 'bulk_schedule_interview'];
        if (!in_array($action, $validActions, true)) {
            return ['success' => false, 'message' => "Invalid bulk action '{$action}'."];
        }

        $matchIds = array_unique(array_filter(array_map('intval', $matchIds)));
        if (empty($matchIds)) {
            return ['success' => false, 'message' => 'No candidates selected for bulk operation.'];
        }

        $successCount = 0;
        $skipped = [];

        foreach ($matchIds as $mId) {
            $match = $this->getMatchDetails($mId);
            if (!$match) {
                $skipped[] = ['match_id' => $mId, 'reason' => 'Candidate match not found'];
                continue;
            }

            if ($companyId !== null && $companyId > 0 && (int)$match['company_id'] !== $companyId) {
                $skipped[] = ['match_id' => $mId, 'reason' => 'Access denied: Company boundary mismatch'];
                continue;
            }

            if ($action === 'bulk_shortlist') {
                if ($match['status'] !== 'Matched') {
                    $skipped[] = ['match_id' => $mId, 'reason' => "Candidate is already in '{$match['status']}' stage"];
                    continue;
                }
                if ($match['eligibility_status'] === 'NOT_ELIGIBLE') {
                    $skipped[] = ['match_id' => $mId, 'reason' => 'Candidate failed mandatory eligibility criteria'];
                    continue;
                }
                $this->transitionStage($mId, 'Shortlisted', $payload['remarks'] ?? 'Bulk shortlisted by recruiter', $userId);
                $this->pdo->prepare("INSERT INTO `candidate_shortlist_actions` (`match_id`, `action`, `remarks`, `performed_by`, `created_at`) VALUES (:mid, 'SHORTLIST', :rem, :uid, NOW())")
                    ->execute([':mid' => $mId, ':rem' => $payload['remarks'] ?? 'Bulk shortlist', ':uid' => $userId]);
                $successCount++;
            } elseif ($action === 'bulk_share') {
                $this->pdo->prepare("UPDATE `candidate_matches` SET `is_shared_with_industry` = 1, `shared_at` = NOW() WHERE id = :id")
                    ->execute([':id' => $mId]);
                $this->pdo->prepare("INSERT INTO `candidate_shortlist_actions` (`match_id`, `action`, `remarks`, `performed_by`, `created_at`) VALUES (:mid, 'SHARE_WITH_INDUSTRY', :rem, :uid, NOW())")
                    ->execute([':mid' => $mId, ':rem' => 'Bulk authorized for industry release', ':uid' => $userId]);
                $successCount++;
            } elseif ($action === 'bulk_hold') {
                if (in_array($match['status'], ['Joined', 'Rejected', 'Closed', 'Withdrawn'], true)) {
                    $skipped[] = ['match_id' => $mId, 'reason' => "Cannot place terminal stage '{$match['status']}' on hold"];
                    continue;
                }
                $this->transitionStage($mId, 'On Hold', $payload['remarks'] ?? 'Bulk placed on hold', $userId);
                $successCount++;
            } elseif ($action === 'bulk_advance') {
                $nextMap = [
                    'Matched'         => 'Shortlisted',
                    'Shortlisted'     => 'Screened',
                    'Screened'        => 'Scheduled',
                    'Scheduled'       => 'Completed',
                    'Completed'       => 'Selected',
                    'Selected'        => 'Offer Released',
                    'Offer Released'  => 'Offer Accepted',
                    'Offer Accepted'  => 'Joining Pending',
                    'Joining Pending' => 'Joined'
                ];
                $next = $nextMap[$match['status']] ?? null;
                if (!$next) {
                    $skipped[] = ['match_id' => $mId, 'reason' => "No default next stage for '{$match['status']}'"];
                    continue;
                }
                $res = $this->transitionStage($mId, $next, $payload['remarks'] ?? "Bulk advanced to {$next}", $userId);
                if ($res['success']) {
                    $successCount++;
                } else {
                    $skipped[] = ['match_id' => $mId, 'reason' => $res['message']];
                }
            } elseif ($action === 'bulk_schedule_interview') {
                if (!in_array($match['status'], ['Shortlisted', 'Screened', 'Scheduled'], true)) {
                    $skipped[] = ['match_id' => $mId, 'reason' => "Cannot schedule interview for candidate in '{$match['status']}' stage"];
                    continue;
                }
                $dt = $payload['interview_datetime'] ?? date('Y-m-d H:i:s', strtotime('+3 days 10:00:00'));
                $res = $this->scheduleInterview($mId, [
                    'interview_datetime'    => $dt,
                    'venue_or_link'         => $payload['venue_or_link'] ?? 'Main Plant Office',
                    'interview_mode'        => $payload['interview_mode'] ?? 'In-Person',
                    'interview_round'       => $payload['interview_round'] ?? 'Round 1 - Technical',
                    'interviewer_name'      => $payload['interviewer_name'] ?? 'Interview Panel',
                    'interviewer_designation'=> $payload['interviewer_designation'] ?? 'Technical Evaluator',
                    'interview_remarks'     => $payload['interview_remarks'] ?? 'Bulk interview schedule'
                ], $userId);
                if ($res['success']) {
                    $successCount++;
                } else {
                    $skipped[] = ['match_id' => $mId, 'reason' => $res['message']];
                }
            }
        }

        return [
            'success'       => ($successCount > 0),
            'action'        => $action,
            'success_count' => $successCount,
            'skipped_count' => count($skipped),
            'skipped'       => $skipped,
            'message'       => "Bulk action '{$action}' completed: {$successCount} succeeded, " . count($skipped) . " skipped."
        ];
    }

    /**
     * Retrieve Comprehensive Industry Recruitment Dashboard Statistics
     */
    public function getIndustryDashboardStats(int $companyId): array
    {
        // 1. KPI Counts
        $kpiSql = "SELECT 
                COUNT(DISTINCT mr.id) as total_active_requirements,
                COUNT(cm.id) as total_matched_candidates,
                SUM(CASE WHEN cm.status = 'Shortlisted' THEN 1 ELSE 0 END) as total_shortlisted_candidates,
                SUM(CASE WHEN cm.status = 'Screened' THEN 1 ELSE 0 END) as candidates_under_screening,
                SUM(CASE WHEN cm.status IN ('Shortlisted', 'Screened') THEN 1 ELSE 0 END) as assessments_pending,
                SUM(CASE WHEN cm.status = 'Scheduled' THEN 1 ELSE 0 END) as interviews_scheduled,
                SUM(CASE WHEN cm.status = 'Completed' THEN 1 ELSE 0 END) as interviews_completed,
                SUM(CASE WHEN cm.status = 'Selected' THEN 1 ELSE 0 END) as candidates_selected,
                SUM(CASE WHEN cm.status = 'Offer Released' THEN 1 ELSE 0 END) as offers_released,
                SUM(CASE WHEN cm.status = 'Offer Accepted' THEN 1 ELSE 0 END) as offers_accepted,
                SUM(CASE WHEN cm.status = 'Joining Pending' THEN 1 ELSE 0 END) as candidates_awaiting_joining,
                SUM(CASE WHEN cm.status = 'Joined' THEN 1 ELSE 0 END) as candidates_joined,
                SUM(CASE WHEN cm.status = 'Rejected' THEN 1 ELSE 0 END) as candidates_rejected,
                SUM(CASE WHEN cm.status = 'Withdrawn' THEN 1 ELSE 0 END) as candidates_withdrawn
            FROM `manpower_requirements` mr
            LEFT JOIN `candidate_matches` cm ON cm.requirement_id = mr.id AND (cm.is_shared_with_industry = 1 OR cm.status != 'Matched')
            WHERE mr.company_id = :cid AND mr.status IN ('APPROVED', 'IN_PROGRESS', 'PARTIALLY_FULFILLED', 'RECRUITMENT_IN_PROGRESS')";
        
        $kpiStmt = $this->pdo->prepare($kpiSql);
        $kpiStmt->execute([':cid' => $companyId]);
        $kpis = $kpiStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // 2. Funnel metrics across 16 stages
        $funnel = $this->getPipelineFunnelMetrics(null, $companyId);

        // 3. Upcoming Activities
        $upcomingSql = "SELECT i.id, i.interview_datetime, i.interview_mode, i.interview_round, i.venue_or_link,
                               cm.candidate_name, cm.trade, mr.requirement_id as req_code
                        FROM `interviews` i
                        JOIN `candidate_matches` cm ON cm.id = i.match_id
                        JOIN `manpower_requirements` mr ON mr.id = cm.requirement_id
                        WHERE mr.company_id = :cid AND i.status = 'Scheduled' AND i.interview_datetime >= NOW()
                        ORDER BY i.interview_datetime ASC LIMIT 10";
        $upStmt = $this->pdo->prepare($upcomingSql);
        $upStmt->execute([':cid' => $companyId]);
        $upcomingInterviews = $upStmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Trade-wise Progress
        $tradeSql = "SELECT rt.trade_name, 
                            SUM(rt.vacancies) as vacancies_demanded,
                            COUNT(CASE WHEN cm.status = 'Joined' THEN 1 END) as candidates_joined
                     FROM `requirement_trades` rt
                     JOIN `manpower_requirements` mr ON mr.id = rt.requirement_id
                     LEFT JOIN `candidate_matches` cm ON cm.requirement_id = mr.id AND cm.trade = rt.trade_name
                     WHERE mr.company_id = :cid
                     GROUP BY rt.trade_name";
        $trStmt = $this->pdo->prepare($tradeSql);
        $trStmt->execute([':cid' => $companyId]);
        $tradeProgress = $trStmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. Plant-wise Progress
        $plantSql = "SELECT p.id, p.plant_name, p.plant_code,
                            COUNT(DISTINCT mr.id) as active_requirements,
                            COALESCE(SUM(mr.quantity_required), 0) as vacancies_demanded,
                            COUNT(CASE WHEN cm.status = 'Joined' THEN 1 END) as candidates_joined
                     FROM `manufacturing_plants` p
                     LEFT JOIN `manpower_requirements` mr ON mr.plant_id = p.id AND mr.status NOT IN ('CANCELLED', 'CLOSED')
                     LEFT JOIN `candidate_matches` cm ON cm.requirement_id = mr.id
                     WHERE p.company_id = :cid
                     GROUP BY p.id";
        $plStmt = $this->pdo->prepare($plantSql);
        $plStmt->execute([':cid' => $companyId]);
        $plantProgress = $plStmt->fetchAll(PDO::FETCH_ASSOC);

        // 6. Recruitment Alerts
        $alerts = [];
        // Pending feedback alert
        $fbStmt = $this->pdo->prepare("SELECT COUNT(*) FROM `interviews` i 
            JOIN `candidate_matches` cm ON cm.id = i.match_id 
            JOIN `manpower_requirements` mr ON mr.id = cm.requirement_id 
            WHERE mr.company_id = :cid AND i.status = 'Scheduled' AND i.interview_datetime < NOW()");
        $fbStmt->execute([':cid' => $companyId]);
        $pendingFeedbackCount = (int)$fbStmt->fetchColumn();
        if ($pendingFeedbackCount > 0) {
            $alerts[] = [
                'type'    => 'WARNING',
                'title'   => 'Interview Feedback Pending',
                'message' => "{$pendingFeedbackCount} past interview(s) require technical evaluation feedback submission.",
                'icon'    => 'bi-star-half'
            ];
        }

        // Approaching application deadlines
        $dlStmt = $this->pdo->prepare("SELECT COUNT(*) FROM `manpower_requirements` 
            WHERE company_id = :cid AND status IN ('APPROVED', 'IN_PROGRESS') 
            AND application_deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)");
        $dlStmt->execute([':cid' => $companyId]);
        $deadlinesApproaching = (int)$dlStmt->fetchColumn();
        if ($deadlinesApproaching > 0) {
            $alerts[] = [
                'type'    => 'INFO',
                'title'   => 'Requirement Deadlines Approaching',
                'message' => "{$deadlinesApproaching} requisition(s) have application deadlines within the next 3 days.",
                'icon'    => 'bi-clock-history'
            ];
        }

        return [
            'kpis'                => $kpis,
            'funnel'              => $funnel,
            'upcoming_interviews' => $upcomingInterviews,
            'trade_progress'      => $tradeProgress,
            'plant_progress'      => $plantProgress,
            'alerts'              => $alerts
        ];
    }

    /**
     * Retrieve Comprehensive Offer Letter Dossier for Formal PDF / Print Generation
     */
    public function getOfferDetails(int $offerId, ?int $companyId = null): ?array
    {
        $sql = "SELECT ol.*, 
                       s.offered_designation, s.offered_ctc, s.target_joining_date as sel_joining_date,
                       cm.candidate_name, cm.trade, cm.passing_year, cm.candidate_source, cm.candidate_source_id,
                       mr.requirement_id as req_code, mr.title as req_title, mr.job_description,
                       c.id as company_id, c.company_name, c.registration_number as company_reg, 
                       c.address as company_address, c.city as company_city, c.state as company_state, c.pincode as company_pincode,
                       c.hr_contact_name, c.hr_contact_email, c.hr_contact_phone,
                       p.plant_name, p.plant_code, p.city as plant_city, p.district as plant_district,
                       u.full_name as approved_by_name, u.email as approved_by_email
                FROM `offer_letters` ol
                JOIN `selections` s ON s.id = ol.selection_id
                JOIN `candidate_matches` cm ON cm.id = s.match_id
                JOIN `manpower_requirements` mr ON mr.id = cm.requirement_id
                JOIN `companies` c ON c.id = mr.company_id
                LEFT JOIN `manufacturing_plants` p ON p.id = mr.plant_id
                LEFT JOIN `users` u ON u.id = ol.approved_by
                WHERE ol.id = :id";
        
        $params = [':id' => $offerId];
        if ($companyId !== null && $companyId > 0) {
            $sql .= " AND mr.company_id = :cid";
            $params[':cid'] = $companyId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // =========================================================================
    // PIPELINE METRICS & REPORTS
    // =========================================================================

    /**
     * Get real-time funnel counts for all pipeline stages.
     */
    public function getPipelineFunnelMetrics(?int $requirementId = null, ?int $companyId = null): array
    {
        $sql = "SELECT 
                COUNT(*) as total_sourced,
                SUM(CASE WHEN cm.status = 'Shortlisted' THEN 1 ELSE 0 END) as shortlisted,
                SUM(CASE WHEN cm.status = 'Screened' THEN 1 ELSE 0 END) as screened,
                SUM(CASE WHEN cm.status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN cm.status = 'Completed' THEN 1 ELSE 0 END) as interviewed,
                SUM(CASE WHEN cm.status = 'Selected' THEN 1 ELSE 0 END) as selected,
                SUM(CASE WHEN cm.status = 'Offer Released' THEN 1 ELSE 0 END) as offers_released,
                SUM(CASE WHEN cm.status = 'Offer Accepted' THEN 1 ELSE 0 END) as offers_accepted,
                SUM(CASE WHEN cm.status = 'Joining Pending' THEN 1 ELSE 0 END) as joining_pending,
                SUM(CASE WHEN cm.status = 'Joined' THEN 1 ELSE 0 END) as joined,
                SUM(CASE WHEN cm.status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN cm.status = 'On Hold' THEN 1 ELSE 0 END) as on_hold,
                SUM(CASE WHEN cm.status = 'No Show' THEN 1 ELSE 0 END) as no_show
            FROM `candidate_matches` cm
            JOIN `manpower_requirements` mr ON mr.id = cm.requirement_id
            WHERE 1=1";
        
        $params = [];
        if ($requirementId) {
            $sql .= " AND cm.requirement_id = :rid";
            $params[':rid'] = $requirementId;
        }
        if ($companyId) {
            $sql .= " AND mr.company_id = :cid";
            $params[':cid'] = $companyId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Retrieve pipeline candidates with pagination and stage filters.
     */
    public function getPipelineCandidates(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT cm.*, 
                       mr.requirement_id as req_code, mr.title as req_title, mr.company_id, 
                       c.company_name,
                       i.id as interview_id, i.interview_datetime, i.interview_round, i.venue_or_link,
                       s.id as selection_id, s.offered_designation, s.offered_ctc,
                       ol.id as offer_id, ol.letter_reference_no, ol.status as offer_status,
                       j.id as joining_id, j.actual_joining_date, j.joining_status
                FROM `candidate_matches` cm
                JOIN `manpower_requirements` mr ON mr.id = cm.requirement_id
                JOIN `companies` c ON c.id = mr.company_id
                LEFT JOIN `interviews` i ON i.match_id = cm.id AND i.status != 'Cancelled'
                LEFT JOIN `selections` s ON s.match_id = cm.id
                LEFT JOIN `offer_letters` ol ON ol.selection_id = s.id
                LEFT JOIN `joinings` j ON j.selection_id = s.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['company_id'])) {
            $sql .= " AND mr.company_id = :cid";
            $params[':cid'] = (int)$filters['company_id'];
        }

        if (!empty($filters['requirement_id'])) {
            $sql .= " AND cm.requirement_id = :rid";
            $params[':rid'] = (int)$filters['requirement_id'];
        }

        if (!empty($filters['stage'])) {
            $sql .= " AND cm.status = :stage";
            $params[':stage'] = $filters['stage'];
        }

        if (!empty($filters['trade'])) {
            $sql .= " AND cm.trade = :trade";
            $params[':trade'] = $filters['trade'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (cm.candidate_name LIKE :s1 OR cm.trade LIKE :s2 OR c.company_name LIKE :s3)";
            $params[':s1'] = '%' . $filters['search'] . '%';
            $params[':s2'] = '%' . $filters['search'] . '%';
            $params[':s3'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY cm.id DESC LIMIT :lim OFFSET :off";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate Comprehensive Recruitment Reports
     */
    public function generateRecruitmentReport(array $filters = []): array
    {
        $funnel = $this->getPipelineFunnelMetrics(
            !empty($filters['requirement_id']) ? (int)$filters['requirement_id'] : null,
            !empty($filters['company_id']) ? (int)$filters['company_id'] : null
        );

        $totalSourced = max(1, (int)($funnel['total_sourced'] ?? 1));
        $interviewed  = max(1, (int)($funnel['interviewed'] ?? 1));
        $selected     = max(1, (int)($funnel['selected'] ?? 1));
        $offersRel    = max(1, (int)($funnel['offers_released'] ?? 1));

        $conversionRates = [
            'shortlist_rate'  => round(((int)($funnel['shortlisted'] ?? 0) / $totalSourced) * 100, 1),
            'interview_rate'  => round(((int)($funnel['interviewed'] ?? 0) / $totalSourced) * 100, 1),
            'selection_rate'  => round(((int)($funnel['selected'] ?? 0) / $interviewed) * 100, 1),
            'offer_acc_rate'  => round(((int)($funnel['offers_accepted'] ?? 0) / $offersRel) * 100, 1),
            'joining_conv_rate'=> round(((int)($funnel['joined'] ?? 0) / $selected) * 100, 1)
        ];

        return [
            'funnel'           => $funnel,
            'conversion_rates' => $conversionRates
        ];
    }

    /**
     * Requisition-level pipeline fulfillment breakdown
     */
    public function getRequisitionBreakdown(?int $companyId = null): array
    {
        $sql = "SELECT 
                mr.id, mr.requirement_id, mr.title, mr.quantity_required, mr.status as req_status,
                c.company_name,
                COUNT(cm.id) as total_sourced,
                SUM(CASE WHEN cm.status = 'Shortlisted' THEN 1 ELSE 0 END) as shortlisted_count,
                SUM(CASE WHEN cm.status IN ('Screened', 'Scheduled', 'Completed', 'Selected', 'Offer Released', 'Offer Accepted', 'Joining Pending', 'Joined') THEN 1 ELSE 0 END) as screened_count,
                SUM(CASE WHEN cm.status IN ('Completed', 'Selected', 'Offer Released', 'Offer Accepted', 'Joining Pending', 'Joined') THEN 1 ELSE 0 END) as interviewed_count,
                SUM(CASE WHEN cm.status IN ('Selected', 'Offer Released', 'Offer Accepted', 'Joining Pending', 'Joined') THEN 1 ELSE 0 END) as selected_count,
                SUM(CASE WHEN cm.status IN ('Offer Released', 'Offer Accepted', 'Joining Pending', 'Joined') THEN 1 ELSE 0 END) as offered_count,
                SUM(CASE WHEN cm.status = 'Joined' THEN 1 ELSE 0 END) as joined_count
            FROM `manpower_requirements` mr
            JOIN `companies` c ON c.id = mr.company_id
            LEFT JOIN `candidate_matches` cm ON cm.requirement_id = mr.id
            WHERE 1=1";
        
        $params = [];
        if ($companyId) {
            $sql .= " AND mr.company_id = :cid";
            $params[':cid'] = $companyId;
        }

        $sql .= " GROUP BY mr.id ORDER BY mr.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // INTERNAL HELPERS
    // =========================================================================

    /**
     * Retrieve complete audit trail history for candidate stages
     */
    public function getStageHistory(int $matchId): array
    {
        $stmt = $this->pdo->prepare("SELECT rsh.*, u.full_name as changed_by_name, u.email as changed_by_email 
            FROM `recruitment_stage_history` rsh
            LEFT JOIN `users` u ON u.id = rsh.changed_by
            WHERE rsh.match_id = :mid
            ORDER BY rsh.id ASC");
        $stmt->execute([':mid' => $matchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getMatchDetails(int $matchId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT cm.*, mr.company_id, mr.requirement_id as req_code, mr.min_salary, mr.created_by 
            FROM `candidate_matches` cm 
            JOIN `manpower_requirements` mr ON mr.id = cm.requirement_id 
            WHERE cm.id = :id LIMIT 1");
        $stmt->execute([':id' => $matchId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function recordStageHistory(int $matchId, ?string $oldStage, string $newStage, ?string $notes, int $userId): void
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO `recruitment_stage_history` 
                (`match_id`, `previous_stage`, `new_stage`, `notes`, `changed_by`, `created_at`) 
                VALUES (:mid, :old, :new, :notes, :uid, NOW())");
            $stmt->execute([
                ':mid'   => $matchId,
                ':old'   => $oldStage,
                ':new'   => $newStage,
                ':notes' => $notes,
                ':uid'   => $userId
            ]);
        } catch (Exception $e) {
            error_log("Failed to record recruitment stage history: " . $e->getMessage());
        }
    }
}
