<?php
/**
 * Candidate Algorithmic Matching & Shortlisting Engine
 * 
 * Identifier: ALG-MAT-2026-V1.0
 * Project: Industry Manpower Requirement Portal
 * Organization: PRAYAS – The Automation Academy
 * Technology: Core PHP + PDO + MySQL
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/CandidateSourceModel.php';

class CandidateMatchingModel
{
    private PDO $pdo;
    private CandidateSourceModel $sourceModel;

    // Default Configurable Scoring Weights (Sum = 100 Points)
    public const DEFAULT_WEIGHTS = [
        'trade'        => 30.0,
        'qual'         => 15.0,
        'batch'        => 15.0,
        'skills'       => 20.0,
        'location'     => 10.0,
        'experience'   => 5.0,
        'availability' => 5.0
    ];

    // Related Trade Cross-Walk Matrix
    private const RELATED_TRADES = [
        'Electrician' => ['Wireman', 'Electronic Mechanic', 'Instrument Mechanic'],
        'Wireman'     => ['Electrician'],
        'Fitter'      => ['Turner', 'Machinist', 'Tool & Die Maker', 'Welder'],
        'Turner'      => ['Fitter', 'Machinist'],
        'Machinist'   => ['Turner', 'Fitter', 'Tool & Die Maker', 'CNC Operator'],
        'Welder'      => ['Fitter', 'Sheet Metal Worker'],
        'Mechanic Motor Vehicle' => ['Diesel Mechanic', 'Tractor Mechanic'],
        'Diesel Mechanic'        => ['Mechanic Motor Vehicle']
    ];

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->sourceModel = new CandidateSourceModel($this->pdo);
    }

    /**
     * Get default scoring weights.
     */
    public function getScoringWeights(): array
    {
        return self::DEFAULT_WEIGHTS;
    }

    /**
     * Classifies a matching score into quality tiers.
     */
    public function classifyScore(float $score, bool $isEligible = true): string
    {
        if (!$isEligible || $score <= 0.0) {
            return 'Not Eligible';
        }
        return match(true) {
            $score >= 90.0 => 'Excellent Match',
            $score >= 75.0 => 'Strong Match',
            $score >= 60.0 => 'Possible Match',
            default        => 'Low Match'
        };
    }

    /**
     * Evaluates a single candidate against a requirement using 7-factor model.
     */
    public function evaluateMatch(array $cand, array $req, array $weights = []): array
    {
        $w = array_merge(self::DEFAULT_WEIGHTS, $weights);
        if (isset($req['trade']) && empty($req['trades'])) {
            $req['trades'] = [['trade_name' => $req['trade']]];
        }
        if (isset($req['skills']) && is_string($req['skills'])) {
            $skillNames = array_map('trim', explode(',', $req['skills']));
            $req['skills'] = array_map(fn($s) => ['skill_name' => $s], array_filter($skillNames));
        }
        if (!isset($req['passing_year_from']) && isset($req['passing_year'])) {
            $req['passing_year_from'] = (int)$req['passing_year'];
        }
        if (!isset($req['job_location_district']) && isset($req['work_location'])) {
            $parts = array_map('trim', explode(',', $req['work_location']));
            $req['job_location_district'] = $parts[0] ?? '';
            $req['job_location_state'] = $parts[1] ?? 'Maharashtra';
        }

        $res = $this->evaluateCandidate($cand, $req, $w);
        return [
            'is_eligible'        => $res['is_eligible'],
            'eligibility_status' => $res['is_eligible'] ? 'ELIGIBLE' : 'NOT_ELIGIBLE',
            'eligibility_reasons'=> implode('; ', $res['ineligible_reasons']),
            'total_score'        => $res['total_score'],
            'match_category'     => $res['match_category'],
            'trade_score'        => $res['subscores']['trade'],
            'batch_score'        => $res['subscores']['batch'],
            'qual_score'         => $res['subscores']['qual'],
            'skills_score'       => $res['subscores']['skills'],
            'location_score'     => $res['subscores']['location'],
            'exp_score'          => $res['subscores']['experience'],
            'avail_score'        => $res['subscores']['availability'],
            'explanation'        => $res['explanation']
        ];
    }

    /**
     * Executes algorithmic matching for an approved requirement and generates ranked matches.
     * 
     * @param int $requirementId Manpower Requirement primary ID
     * @param int|null $triggeredBy User ID initiating matching run
     * @param array|int $customWeightsOrCompanyId Overridden weights or company ID for multi-tenant check
     * @param int|null $companyId Optional company ID for multi-tenant verification
     * @return array Summary of matching run outcomes
     */
    public function executeMatchingRun(int $requirementId, ?int $triggeredBy = null, $customWeightsOrCompanyId = [], ?int $companyId = null): array
    {
        if (is_int($customWeightsOrCompanyId)) {
            $companyId = $customWeightsOrCompanyId;
            $customWeights = [];
        } else {
            $customWeights = (array)$customWeightsOrCompanyId;
        }

        $req = $this->getRequirementDetails($requirementId);
        if (!$req) {
            return ['success' => false, 'message' => 'Requirement not found.'];
        }

        // Active Requirement Eligibility Gate: Only approved/active requirements can be matched
        $eligibleStatuses = ['APPROVED', 'IN_PROGRESS', 'PARTIALLY_FULFILLED', 'RECRUITMENT_IN_PROGRESS'];
        if (!in_array($req['status'], $eligibleStatuses, true)) {
            return [
                'success' => false,
                'message' => "Requirement status '{$req['status']}' is not eligible for automated matching. Only active requisitions can be processed."
            ];
        }

        // Multi-Tenant Isolation Gate
        if ($companyId !== null && $companyId > 0 && (int)$req['company_id'] !== $companyId) {
            return [
                'success' => false,
                'message' => 'Access denied: Requisition does not belong to your company.'
            ];
        }

        $weights = array_merge(self::DEFAULT_WEIGHTS, $customWeights);
        $runId   = 'RUN-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3));

        // Build search criteria from requirement specification (without restricting location/batch at query level so candidates can be scored)
        $tradeNames = !empty($req['trades']) ? array_column($req['trades'], 'trade_name') : [];
        $criteria = [
            'trades'        => $tradeNames,
            'gender'        => (!empty($req['gender_preference']) && $req['gender_preference'] !== 'Any') ? $req['gender_preference'] : null,
            'must_be_passed'=> true
        ];

        // Search candidates from both read-only sources
        $candidates = $this->sourceModel->searchCandidates($criteria, 500, 0);

        $scannedCount = count($candidates);
        $eligibleCount = 0;
        $ineligibleCount = 0;

        // Deduplication tracker (keyed by normalized registration_no or name + DOB)
        $seenCandidates = [];

        $this->pdo->beginTransaction();
        try {
            // Create matching run entry
            $runStmt = $this->pdo->prepare("INSERT INTO `candidate_matching_runs` 
                (`requirement_id`, `run_identifier`, `total_candidates_scanned`, `weights_json`, `filters_applied_json`, `triggered_by`, `created_at`) 
                VALUES (:rid, :run, :scanned, :weights, :filters, :uid, NOW())");
            $runStmt->execute([
                ':rid'     => $requirementId,
                ':run'     => $runId,
                ':scanned' => $scannedCount,
                ':weights' => json_encode($weights),
                ':filters' => json_encode($criteria),
                ':uid'     => $triggeredBy
            ]);

            // Upsert statement into candidate_matches
            $upsertMatchSql = "INSERT INTO `candidate_matches` 
                (`requirement_id`, `matching_run_id`, `candidate_source`, `candidate_source_id`, 
                 `candidate_name`, `trade`, `passing_year`, `match_score`, `eligibility_status`, 
                 `trade_score`, `batch_score`, `qual_score`, `skills_score`, `location_score`, `exp_score`, `avail_score`, 
                 `match_details_json`, `eligibility_reasons`, `status`, `matched_at`) 
                VALUES 
                (:rid, :run, :src, :src_id, :name, :trade, :pyear, :score, :elig, 
                 :s_trd, :s_btc, :s_ql, :s_skl, :s_loc, :s_exp, :s_avl, 
                 :details, :el_reasons, :status, NOW()) 
                ON DUPLICATE KEY UPDATE 
                    `matching_run_id`    = VALUES(`matching_run_id`),
                    `match_score`        = VALUES(`match_score`),
                    `eligibility_status` = VALUES(`eligibility_status`),
                    `trade_score`        = VALUES(`trade_score`),
                    `batch_score`        = VALUES(`batch_score`),
                    `qual_score`         = VALUES(`qual_score`),
                    `skills_score`       = VALUES(`skills_score`),
                    `location_score`     = VALUES(`location_score`),
                    `exp_score`          = VALUES(`exp_score`),
                    `avail_score`        = VALUES(`avail_score`),
                    `match_details_json` = VALUES(`match_details_json`),
                    `eligibility_reasons`= VALUES(`eligibility_reasons`),
                    `matched_at`         = NOW()";
            
            $upsertStmt = $this->pdo->prepare($upsertMatchSql);

            foreach ($candidates as $cand) {
                // Deduplication signature by Candidate Name + Trade
                $dedupKey = strtolower(trim((string)($cand['full_name'] ?? ''))) . '|' . strtolower(trim((string)($cand['trade'] ?? '')));

                if (isset($seenCandidates[$dedupKey])) {
                    continue; // Skip exact duplicate cross-source record
                }
                $seenCandidates[$dedupKey] = true;

                // Evaluate Eligibility Gates & Calculate Scores
                $eval = $this->evaluateCandidate($cand, $req, $weights);

                if ($eval['is_eligible']) {
                    $eligibleCount++;
                    $initStatus = 'Matched';
                } else {
                    $ineligibleCount++;
                    $initStatus = 'Rejected';
                }

                $upsertStmt->execute([
                    ':rid'        => $requirementId,
                    ':run'        => $runId,
                    ':src'        => $cand['candidate_source'],
                    ':src_id'     => $cand['candidate_source_id'],
                    ':name'       => $cand['full_name'],
                    ':trade'      => $cand['trade'],
                    ':pyear'      => (string)($cand['passing_year'] ?? $cand['passing_year_raw']),
                    ':score'      => $eval['total_score'],
                    ':elig'       => $eval['is_eligible'] ? 'ELIGIBLE' : 'NOT_ELIGIBLE',
                    ':s_trd'      => $eval['subscores']['trade'],
                    ':s_btc'      => $eval['subscores']['batch'],
                    ':s_ql'       => $eval['subscores']['qual'],
                    ':s_skl'      => $eval['subscores']['skills'],
                    ':s_loc'      => $eval['subscores']['location'],
                    ':s_exp'      => $eval['subscores']['experience'],
                    ':s_avl'      => $eval['subscores']['availability'],
                    ':details'    => json_encode($eval['explanation']),
                    ':el_reasons' => !empty($eval['ineligible_reasons']) ? implode('; ', $eval['ineligible_reasons']) : null,
                    ':status'     => $initStatus
                ]);
            }

            // Update matching run tallies
            $updateRun = $this->pdo->prepare("UPDATE `candidate_matching_runs` 
                SET `eligible_candidates_found` = :el, `ineligible_candidates_count` = :inel 
                WHERE run_identifier = :run");
            $updateRun->execute([':el' => $eligibleCount, ':inel' => $ineligibleCount, ':run' => $runId]);

            $this->pdo->commit();

            return [
                'success'                   => true,
                'run_id'                    => $runId,
                'total_scanned'             => $scannedCount,
                'total_candidates_scanned'  => $scannedCount,
                'eligible_count'            => $eligibleCount,
                'eligible_candidates_found' => $eligibleCount,
                'ineligible_count'          => $ineligibleCount,
                'message'                   => "Matching completed: {$eligibleCount} eligible candidates ranked and mapped."
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Matching Run Failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Matching run failed: ' . $e->getMessage()];
        }
    }

    /**
     * Evaluates mandatory eligibility gates and computes granular score breakdown for a candidate.
     */
    public function evaluateCandidate(array $cand, array $req, array $weights): array
    {
        $ineligibleReasons = [];
        $isEligible = true;

        $candTrade = trim((string)($cand['trade'] ?? ''));
        $reqTrades = array_column($req['trades'], 'trade_name');

        // -------------------------------------------------------------
        // PHASE 6: MANDATORY ELIGIBILITY GATES
        // -------------------------------------------------------------

        // 1. Mandatory Trade Gate
        $exactTrade = in_array($candTrade, $reqTrades, true);
        $relatedTrade = false;
        if (!$exactTrade) {
            foreach ($reqTrades as $rt) {
                if (in_array($candTrade, self::RELATED_TRADES[$rt] ?? [], true)) {
                    $relatedTrade = true;
                    break;
                }
            }
        }

        if (!$exactTrade && !$relatedTrade) {
            $isEligible = false;
            $ineligibleReasons[] = "Trade '{$candTrade}' does not match requirement trades (" . implode(', ', $reqTrades) . ").";
        }

        // 2. Exam Result Gate
        if (($cand['is_passed'] ?? 1) === 0 || ($cand['result_status'] ?? '') === 'FAIL' || ($cand['result'] ?? '') === 'FAIL') {
            $isEligible = false;
            $ineligibleReasons[] = "Candidate ITI examination status is marked FAIL.";
        }

        // 3. Gender Gate
        $reqGender = $req['gender_preference'] ?? 'Any';
        if ($reqGender !== 'Any' && !empty($cand['gender']) && $cand['gender'] !== 'Any') {
            if ($reqGender === 'Female Only' && stripos($cand['gender'], 'Female') === false) {
                $isEligible = false;
                $ineligibleReasons[] = "Requirement specifies Female Only; candidate gender is {$cand['gender']}.";
            } elseif ($reqGender === 'Male Only' && stripos($cand['gender'], 'Male') === false) {
                $isEligible = false;
                $ineligibleReasons[] = "Requirement specifies Male Only; candidate gender is {$cand['gender']}.";
            } elseif ($reqGender !== 'Female Only' && $reqGender !== 'Male Only' && strcasecmp($cand['gender'], $reqGender) !== 0) {
                $isEligible = false;
                $ineligibleReasons[] = "Requirement specifies '{$reqGender}' candidates; candidate is '{$cand['gender']}'.";
            }
        }

        // 4. Mandatory Qualification Gate
        $reqQual = trim((string)($req['qualification'] ?? ''));
        if (!empty($reqQual) && $reqQual !== 'ITI' && $reqQual !== 'ITI Passed' && $reqQual !== 'Any Technical') {
            $candQual = trim((string)($cand['qualification'] ?? 'ITI Passed'));
            if ((stripos($reqQual, 'Diploma') !== false || stripos($reqQual, 'Polytechnic') !== false) && 
                stripos($candQual, 'Diploma') === false && stripos($candQual, 'Engineering') === false) {
                $isEligible = false;
                $ineligibleReasons[] = "Requirement mandates qualification '{$reqQual}'; candidate holds '{$candQual}'.";
            }
        }

        // -------------------------------------------------------------
        // PHASE 5: 100-POINT SCORING SUBSYSTEM
        // -------------------------------------------------------------

        // A. Trade Score (30 pts)
        $scoreTrade = 0.0;
        if ($exactTrade) {
            $scoreTrade = $weights['trade']; // 30.0
        } elseif ($relatedTrade) {
            $scoreTrade = round($weights['trade'] * (20.0 / 30.0), 2); // 20.0
        }

        // B. Passing Batch Score (15 pts)
        $scoreBatch = 0.0;
        $reqPFrom = (int)($req['passing_year_from'] ?? 0);
        $reqPTo   = (int)($req['passing_year_to'] ?? 0);
        $candYear = (int)($cand['passing_year'] ?? 0);

        if ($candYear > 0) {
            if ($reqPFrom > 0 && $reqPTo > 0) {
                if ($candYear >= $reqPFrom && $candYear <= $reqPTo) {
                    $scoreBatch = $weights['batch']; // Exact window: 15 pts
                } elseif (abs($candYear - $reqPFrom) <= 1 || abs($candYear - $reqPTo) <= 1) {
                    $scoreBatch = 10.0; // Adjacent batch (+/-1 yr): 10 pts
                } elseif (abs($candYear - $reqPFrom) <= 2 || abs($candYear - $reqPTo) <= 2) {
                    $scoreBatch = 5.0; // 5 pts
                }
            } elseif ($reqPFrom > 0) {
                if ($candYear === $reqPFrom) {
                    $scoreBatch = $weights['batch'];
                } elseif (abs($candYear - $reqPFrom) === 1) {
                    $scoreBatch = 10.0;
                }
            } else {
                // If requirement did not restrict year, award full batch weight
                $scoreBatch = $weights['batch'];
            }
        } else {
            // Year unknown, neutral baseline
            $scoreBatch = round($weights['batch'] * 0.40, 2);
        }

        // C. Qualification Score (15 pts)
        $scoreQual = 0.0;
        $candQual = $cand['qualification'] ?? 'ITI Passed';
        if ($candQual === 'ITI Passed' || $candQual === 'ITI') {
            $scoreQual = $weights['qual']; // 15 pts
        } elseif (in_array($candQual, ['Diploma', 'Apprentice', 'Certificate'], true)) {
            $scoreQual = round($weights['qual'] * 0.80, 2); // 12 pts
        } else {
            $scoreQual = round($weights['qual'] * 0.50, 2);
        }

        // D. Skills Score (20 pts)
        $scoreSkills = 0.0;
        $reqSkills = array_column($req['skills'] ?? [], 'skill_name');
        if (!empty($reqSkills)) {
            $matchedSkills = 0;
            $candText = strtolower(($cand['skills'] ?? '') . ' ' . ($cand['computer_knowledge'] ?? '') . ' ' . ($cand['languages_known'] ?? '') . ' ' . ($cand['experience'] ?? '') . ' ' . ($cand['evaluator_feedback'] ?? ''));
            foreach ($reqSkills as $sk) {
                if (str_contains($candText, strtolower(trim($sk)))) {
                    $matchedSkills++;
                }
            }
            $skillRatio = $matchedSkills / count($reqSkills);
            $scoreSkills = round($weights['skills'] * max(0.40, $skillRatio), 2);
        } else {
            // General technician profile baseline
            $scoreSkills = $weights['skills']; // 20 pts
        }

        // E. Location Score (10 pts)
        $scoreLocation = 0.0;
        $reqDistrict = strtolower(trim((string)($req['job_location_district'] ?? '')));
        $reqCity     = strtolower(trim((string)($req['job_location_city'] ?? '')));
        $reqState    = strtolower(trim((string)($req['job_location_state'] ?? 'maharashtra')));
        $candDist    = strtolower(trim((string)($cand['district'] ?? '')));
        $candPrefLoc = strtolower(trim((string)($cand['preferred_location'] ?? '')));
        $candState   = strtolower(trim((string)($cand['state'] ?? 'maharashtra')));

        if (!empty($reqDistrict) && !empty($candDist) && strcasecmp($candDist, $reqDistrict) === 0) {
            $scoreLocation = $weights['location']; // 10 pts (Same District)
        } elseif (!empty($reqCity) && !empty($candDist) && strcasecmp($candDist, $reqCity) === 0) {
            $scoreLocation = $weights['location']; // 10 pts (Same City)
        } elseif (!empty($reqDistrict) && (str_contains($candPrefLoc, $reqDistrict) || str_contains($candDist, $reqDistrict))) {
            $scoreLocation = round($weights['location'] * 0.80, 2); // 8 pts
        } elseif ($candState === 'maharashtra' || $candState === $reqState) {
            $scoreLocation = round($weights['location'] * 0.60, 2); // 6 pts (Same State)
        } else {
            $scoreLocation = round($weights['location'] * 0.30, 2); // 3 pts (Other)
        }

        // F. Experience Score (5 pts)
        $scoreExp = 0.0;
        $candExp = strtolower($cand['experience'] ?? 'fresher');
        $reqExp  = strtolower($req['experience_required'] ?? 'fresher');
        if (str_contains($candExp, 'fresher') && str_contains($reqExp, 'fresher')) {
            $scoreExp = $weights['experience']; // 5 pts
        } elseif (!str_contains($candExp, 'fresher')) {
            $scoreExp = $weights['experience']; // 5 pts
        } else {
            $scoreExp = round($weights['experience'] * 0.60, 2); // 3 pts
        }

        // G. Availability Score (5 pts)
        $scoreAvail = $weights['availability']; // 5 pts baseline for active ITI students

        // Compute Total Score
        $totalRaw = $scoreTrade + $scoreBatch + $scoreQual + $scoreSkills + $scoreLocation + $scoreExp + $scoreAvail;
        $totalScore = min(100.0, max(0.0, round($totalRaw, 2)));

        // If candidate failed mandatory gate, score is 0.00 for ranking purposes
        if (!$isEligible) {
            $totalScore = 0.00;
        }

        $matchCategory = match(true) {
            !$isEligible        => 'Not Eligible',
            $totalScore >= 90.0 => 'Excellent Match',
            $totalScore >= 75.0 => 'Strong Match',
            $totalScore >= 60.0 => 'Possible Match',
            default             => 'Low Match'
        };

        $explanation = [
            'category'     => $matchCategory,
            'is_eligible'  => $isEligible,
            'total_score'  => $totalScore,
            'trade'        => ['score' => $scoreTrade, 'max' => $weights['trade'], 'notes' => $exactTrade ? 'Exact Trade Match' : ($relatedTrade ? 'Related Trade Cross-Walk' : 'Unmatched Trade')],
            'batch'        => ['score' => $scoreBatch, 'max' => $weights['batch'], 'notes' => 'Batch: ' . ($cand['passing_year_raw'] ?? (string)($cand['passing_year'] ?? 'N/A'))],
            'qual'         => ['score' => $scoreQual, 'max' => $weights['qual'], 'notes' => $candQual],
            'skills'       => ['score' => $scoreSkills, 'max' => $weights['skills'], 'notes' => 'Technical alignment: ' . ($matchedSkills ?? 0) . ' of ' . (count($reqSkills ?? [])) . ' skills'],
            'location'     => ['score' => $scoreLocation, 'max' => $weights['location'], 'notes' => $cand['district'] ?: 'Maharashtra'],
            'experience'   => ['score' => $scoreExp, 'max' => $weights['experience'], 'notes' => $cand['experience']],
            'availability' => ['score' => $scoreAvail, 'max' => $weights['availability'], 'notes' => 'Standard Joining Availability']
        ];

        return [
            'is_eligible'        => $isEligible,
            'total_score'        => $totalScore,
            'match_category'     => $matchCategory,
            'subscores'          => [
                'trade'        => $scoreTrade,
                'batch'        => $scoreBatch,
                'qual'         => $scoreQual,
                'skills'       => $scoreSkills,
                'location'     => $scoreLocation,
                'experience'   => $scoreExp,
                'availability' => $scoreAvail
            ],
            'explanation'        => $explanation,
            'ineligible_reasons' => $ineligibleReasons
        ];
    }

    // =========================================================================
    // RECRUITER ACTIONS & SHORTLIST WORKFLOW
    // =========================================================================

    /**
     * Shortlist a matched candidate.
     */
    public function shortlistCandidate(int $matchId, int $recruiterId, ?string $remarks = null): bool
    {
        $stmt = $this->pdo->prepare("UPDATE `candidate_matches` 
            SET `status` = 'Shortlisted' 
            WHERE id = :id");
        $stmt->execute([':id' => $matchId]);

        $this->logAction($matchId, 'SHORTLIST', $remarks, $recruiterId);
        return true;
    }

    /**
     * Reject a matched candidate with mandatory rationale.
     */
    public function rejectCandidate(int $matchId, int $recruiterId, string $reason): bool
    {
        $cleanReason = trim($reason);
        if (empty($cleanReason)) {
            return false;
        }

        $stmt = $this->pdo->prepare("UPDATE `candidate_matches` 
            SET `status` = 'Rejected', `rejection_reason` = :reason 
            WHERE id = :id");
        $stmt->execute([':reason' => $cleanReason, ':id' => $matchId]);

        $this->logAction($matchId, 'REJECT', $cleanReason, $recruiterId);
        return true;
    }

    /**
     * Put a matched candidate on hold.
     */
    public function holdCandidate(int $matchId, int $recruiterId, ?string $remarks = null): bool
    {
        $stmt = $this->pdo->prepare("UPDATE `candidate_matches` 
            SET `status` = 'On Hold' 
            WHERE id = :id");
        $stmt->execute([':id' => $matchId]);

        $this->logAction($matchId, 'HOLD', $remarks, $recruiterId);
        return true;
    }

    /**
     * Authorize and share a shortlisted candidate with the industrial employer.
     */
    public function shareWithIndustry(int $matchId, int $recruiterId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE `candidate_matches` 
            SET `is_shared_with_industry` = 1, `shared_at` = NOW() 
            WHERE id = :id AND `status` = 'Shortlisted'");
        $stmt->execute([':id' => $matchId]);

        $this->logAction($matchId, 'SHARE_WITH_INDUSTRY', 'Authorized candidate profile release to industry portal', $recruiterId);
        return true;
    }

    /**
     * Retrieve matches for a requirement with recruiter filters and pagination.
     */
    public function getMatchesForRequirement(int $requirementId, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT cm.*, 
                       mr.requirement_id as req_code, mr.title as req_title, mr.quantity_required, 
                       c.company_name
                FROM `candidate_matches` cm
                JOIN `manpower_requirements` mr ON mr.id = cm.requirement_id
                JOIN `companies` c ON c.id = mr.company_id
                WHERE cm.requirement_id = :rid";
        $params = [':rid' => $requirementId];

        // Eligibility Filter
        if (!empty($filters['eligibility'])) {
            $sql .= " AND cm.eligibility_status = :elig";
            $params[':elig'] = $filters['eligibility'];
        }

        // Status Filter
        if (!empty($filters['status'])) {
            $sql .= " AND cm.status = :status";
            $params[':status'] = $filters['status'];
        }

        // Score Tier Filter
        if (!empty($filters['score_tier'])) {
            if ($filters['score_tier'] === 'tier1') {
                $sql .= " AND cm.match_score >= 85.00";
            } elseif ($filters['score_tier'] === 'tier2') {
                $sql .= " AND cm.match_score >= 70.00 AND cm.match_score < 85.00";
            } elseif ($filters['score_tier'] === 'tier3') {
                $sql .= " AND cm.match_score >= 50.00 AND cm.match_score < 70.00";
            } elseif ($filters['score_tier'] === 'below70') {
                $sql .= " AND cm.match_score < 70.00";
            }
        }

        // Search Filter
        if (!empty($filters['search'])) {
            $sql .= " AND (cm.candidate_name LIKE :s1 OR cm.trade LIKE :s2)";
            $params[':s1'] = '%' . $filters['search'] . '%';
            $params[':s2'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY cm.eligibility_status ASC, cm.match_score DESC, cm.id ASC LIMIT :lim OFFSET :off";

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
     * Retrieve matching statistics for a requirement.
     */
    public function getMatchingMetrics(int $requirementId): array
    {
        $stmt = $this->pdo->prepare("SELECT 
                COUNT(*) as total_matched,
                SUM(CASE WHEN eligibility_status = 'ELIGIBLE' THEN 1 ELSE 0 END) as eligible_count,
                SUM(CASE WHEN status = 'Shortlisted' THEN 1 ELSE 0 END) as shortlisted_count,
                SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected_count,
                SUM(CASE WHEN match_score >= 85.0 THEN 1 ELSE 0 END) as tier1_count,
                SUM(CASE WHEN match_score >= 70.0 AND match_score < 85.0 THEN 1 ELSE 0 END) as tier2_count,
                SUM(CASE WHEN is_shared_with_industry = 1 THEN 1 ELSE 0 END) as shared_with_industry
            FROM `candidate_matches` 
            WHERE requirement_id = :rid");
        $stmt->execute([':rid' => $requirementId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Helper to load requirement and sub-trades.
     */
    private function getRequirementDetails(int $requirementId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `manpower_requirements` WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $requirementId]);
        $req = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$req) return null;

        $tradeStmt = $this->pdo->prepare("SELECT * FROM `requirement_trades` WHERE requirement_id = :rid");
        $tradeStmt->execute([':rid' => $requirementId]);
        $req['trades'] = $tradeStmt->fetchAll(PDO::FETCH_ASSOC);

        $skillStmt = $this->pdo->prepare("SELECT * FROM `requirement_skills` WHERE requirement_id = :rid");
        $skillStmt->execute([':rid' => $requirementId]);
        $req['skills'] = $skillStmt->fetchAll(PDO::FETCH_ASSOC);

        return $req;
    }

    /**
     * Log a recruiter shortlisting action.
     */
    private function logAction(int $matchId, string $action, ?string $remarks, int $performedBy): void
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO `candidate_shortlist_actions` 
                (`match_id`, `action`, `remarks`, `performed_by`, `created_at`) 
                VALUES (:mid, :act, :rem, :uid, NOW())");
            $stmt->execute([
                ':mid' => $matchId,
                ':act' => $action,
                ':rem' => $remarks,
                ':uid' => $performedBy
            ]);
        } catch (Exception $e) {
            error_log("Failed to log candidate shortlist action: " . $e->getMessage());
        }
    }
}
