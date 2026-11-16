<?php
/**
 * Centralized Requirement Validation & Auto-Approval Engine
 * 
 * Identifier: ALG-VAL-2026-V1.0
 * Project: Industry Manpower Requirement Portal
 * Organization: PRAYAS – The Automation Academy
 * Technology: Core PHP + PDO + MySQL
 */

declare(strict_types=1);

class RequirementValidationEngine
{
    private PDO $pdo;
    private const VERSION = 'ALG-VAL-2026-V1.0';

    // Standard ITI Technical Trade Master
    private const TRADE_MASTER = [
        'Fitter', 'Electrician', 'Machinist', 'Turner', 'Welder (Gas & Electric)',
        'Instrument Mechanic', 'Tool & Die Maker', 'Electronics Mechanic',
        'Wireman', 'Mechanic Motor Vehicle (MMV)', 'COPA (Computer Operator)',
        'Refrigeration & AC Mechanic', 'Sheet Metal Worker', 'Draughtsman (Mechanical)'
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Executes the complete multi-stage validation pipeline for a requirement.
     * 
     * @param array $data Raw requisition payload or database record
     * @param int $companyId Company requesting validation
     * @param int|null $triggeredBy User ID triggering validation
     * @param int|null $existingReqId Optional existing requirement primary ID
     * @param bool $persistHistory Whether to save audit history to database
     * @param string $triggerType Event triggering validation (SUBMISSION, DRAFT_SAVE, MANUAL_REVALIDATION, SCHEDULED_CRON)
     * @return array Standardized explainable validation outcome
     */
    public function validate(
        array $data, 
        int $companyId, 
        ?int $triggeredBy = null, 
        ?int $existingReqId = null,
        bool $persistHistory = true,
        string $triggerType = 'SUBMISSION'
    ): array {
        $runId = 'VAL-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4));
        $timestamp = date('Y-m-d H:i:s');
        
        $ruleResults = [];
        $errors = [];
        $warnings = [];
        $manualReviews = [];

        // -------------------------------------------------------------
        // CATEGORY A: BASIC REQUIREMENT & COMPANY VALIDATION
        // -------------------------------------------------------------
        $compStmt = $this->pdo->prepare("SELECT id, company_name, verification_status, industry_type FROM `companies` WHERE id = :cid LIMIT 1");
        $compStmt->execute([':cid' => $companyId]);
        $company = $compStmt->fetch();

        if (!$company) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'BR-REG-001', 'Company Entity Existence', 'BASIC_INFO', 'ERROR', 'FAIL',
                'company_id', 'Associated industrial company record not found.', $timestamp,
                'Verify company registration and enterprise credentials in PRAYAS portal.', $companyId
            );
            $errors['company_id'] = 'Associated company record not found.';
        } else {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'BR-REG-001', 'Company Entity Existence', 'BASIC_INFO', 'INFO', 'PASS',
                'company_id', null, $timestamp, null, $company['company_name']
            );

            // Check Suspended Status
            if (($company['verification_status'] ?? '') === 'Suspended') {
                $ruleResults[] = $this->createResult(
                    $runId, $existingReqId, 'BR-REG-003', 'Company Operational Status', 'BASIC_INFO', 'ERROR', 'FAIL',
                    'verification_status', 'Company account is currently suspended. Submissions are blocked.', $timestamp,
                    'Contact PRAYAS administration to resolve account suspension.', 'Suspended'
                );
                $errors['company_status'] = 'Company account is currently suspended. Submissions are blocked.';
            } else {
                $ruleResults[] = $this->createResult(
                    $runId, $existingReqId, 'BR-REG-003', 'Company Operational Status', 'BASIC_INFO', 'INFO', 'PASS',
                    'verification_status', null, $timestamp, null, $company['verification_status']
                );
            }

            // Check Verification Status
            $verStatus = $company['verification_status'] ?? 'Pending';
            if ($verStatus !== 'Verified' && $verStatus !== 'Active') {
                $ruleResults[] = $this->createResult(
                    $runId, $existingReqId, 'BR-VER-001', 'Commercial Verification Status', 'BASIC_INFO', 'MANUAL_REVIEW', 'REVIEW_REQUIRED',
                    'verification_status', "Employer is {$verStatus}. Requisition requires Super Admin vetting prior to release.", $timestamp,
                    'Submit enterprise accreditation documents for Super Admin verification.', $verStatus
                );
                $manualReviews['verification_status'] = "Employer verification status is '{$verStatus}'. Sourcing requires administrative approval.";
            } else {
                $ruleResults[] = $this->createResult(
                    $runId, $existingReqId, 'BR-VER-001', 'Commercial Verification Status', 'BASIC_INFO', 'INFO', 'PASS',
                    'verification_status', null, $timestamp, null, $verStatus
                );
            }
        }

        // Title Check
        $title = trim((string)($data['title'] ?? ''));
        if (strlen($title) < 5 || strlen($title) > 255) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-TIT-01', 'Requirement Title Length', 'BASIC_INFO', 'ERROR', 'FAIL',
                'title', 'Requirement title must be between 5 and 255 characters.', $timestamp,
                'Provide an informative job role title between 5 and 255 characters (e.g. CNC Machine Operator).', $title
            );
            $errors['title'] = 'Requirement title must be between 5 and 255 characters.';
        } else {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-TIT-01', 'Requirement Title Length', 'BASIC_INFO', 'INFO', 'PASS',
                'title', null, $timestamp, null, $title
            );
        }

        // Job Description Check
        $description = trim((string)($data['job_description'] ?? ''));
        if (strlen($description) < 20) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-DSC-01', 'Job Description Completeness', 'BASIC_INFO', 'ERROR', 'FAIL',
                'job_description', 'Job description must provide at least 20 characters of contextual specifications.', $timestamp,
                'Detail the operating machinery, tool maintenance, shift duties, and expected technical work (at least 20 chars).', $description
            );
            $errors['job_description'] = 'Job description must provide at least 20 characters of contextual specifications.';
        } else {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-DSC-01', 'Job Description Completeness', 'BASIC_INFO', 'INFO', 'PASS',
                'job_description', null, $timestamp, null, substr($description, 0, 50) . '...'
            );
        }

        // Industry Sector Check
        $sector = trim((string)($data['industry_sector'] ?? ($company['industry_type'] ?? '')));
        if (empty($sector)) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-SEC-01', 'Industry Sector Specification', 'BASIC_INFO', 'ERROR', 'FAIL',
                'industry_sector', 'Industry sector is mandatory.', $timestamp,
                'Select or specify your industrial manufacturing sector (e.g. Automotive & Robotics).', $sector
            );
            $errors['industry_sector'] = 'Industry sector is mandatory.';
        } else {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-SEC-01', 'Industry Sector Specification', 'BASIC_INFO', 'INFO', 'PASS',
                'industry_sector', null, $timestamp, null, $sector
            );
        }

        // Priority Check
        $priority = trim((string)($data['priority'] ?? 'Medium'));
        $validPriorities = ['Low', 'Medium', 'High', 'Urgent'];
        if (!in_array($priority, $validPriorities, true)) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-PRI-01', 'Hiring Priority Specification', 'BASIC_INFO', 'WARNING', 'WARNING',
                'priority', "Priority '{$priority}' is non-standard; defaulting to Medium.", $timestamp,
                'Select one of Low, Medium, High, or Urgent.', $priority
            );
            $warnings['priority'] = 'Unrecognized priority value.';
        } else {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-PRI-01', 'Hiring Priority Specification', 'BASIC_INFO', 'INFO', 'PASS',
                'priority', null, $timestamp, null, $priority
            );
        }

        // Contact Email Format Check (if provided)
        $contactEmail = trim((string)($data['contact_email'] ?? ''));
        if (!empty($contactEmail) && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-CEM-01', 'Contact Email Format', 'BASIC_INFO', 'WARNING', 'WARNING',
                'contact_email', "Contact email '{$contactEmail}' format is invalid.", $timestamp,
                'Provide a valid corporate email format (e.g. coordinator@plant.com).', $contactEmail
            );
            $warnings['contact_email'] = 'Contact email format is invalid.';
        }

        // -------------------------------------------------------------
        // CATEGORY B: TRADE-WISE MANPOWER DEMAND VALIDATION
        // -------------------------------------------------------------
        $quantity = (int)($data['quantity_required'] ?? 0);
        if ($quantity < 1 || $quantity > 500) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-QTY-01', 'Vacancy Headcount Bound', 'TRADES', 'ERROR', 'FAIL',
                'quantity_required', 'Total vacancy quantity must be a positive integer between 1 and 500.', $timestamp,
                'Enter total vacancies between 1 and 500.', (string)$quantity
            );
            $errors['quantity_required'] = 'Total vacancy quantity must be a positive integer between 1 and 500.';
        } else {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-QTY-01', 'Vacancy Headcount Bound', 'TRADES', 'INFO', 'PASS',
                'quantity_required', null, $timestamp, null, (string)$quantity
            );
        }

        // Trade Allocations & Sum Parity
        $trades = $data['trades'] ?? [];
        if (!is_array($trades) || empty($trades)) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-TRD-01', 'Mandatory Trade Selection', 'TRADES', 'ERROR', 'FAIL',
                'trades', 'At least one ITI technical trade must be allocated.', $timestamp,
                'Add at least one technical trade row in Step 2 of the wizard (e.g. Fitter, Electrician).', '0 trades'
            );
            $errors['trades'] = 'At least one ITI technical trade must be allocated.';
        } else {
            $sumTradeVacancies = 0;
            $hasInvalidTrade = false;
            $seenTrades = [];
            $hasDuplicates = false;

            foreach ($trades as $idx => $trd) {
                $trdName = trim((string)($trd['trade_name'] ?? ''));
                $trdQty  = (int)($trd['vacancies'] ?? 0);

                if (empty($trdName) || strlen($trdName) < 2) {
                    $hasInvalidTrade = true;
                    $errors["trade_name_{$idx}"] = "Trade row #" . ($idx + 1) . " requires a specified trade title.";
                }
                if ($trdQty < 1) {
                    $hasInvalidTrade = true;
                    $errors["trade_vacancies_{$idx}"] = "Trade '{$trdName}' requires at least 1 allocated vacancy.";
                }

                // Duplicate trade detection
                if (!empty($trdName)) {
                    if (in_array(strtolower($trdName), $seenTrades, true)) {
                        $hasDuplicates = true;
                    }
                    $seenTrades[] = strtolower($trdName);
                }

                $sumTradeVacancies += $trdQty;
            }

            if ($hasInvalidTrade) {
                $ruleResults[] = $this->createResult(
                    $runId, $existingReqId, 'ERR-TRD-02', 'Trade Specification Validity', 'TRADES', 'ERROR', 'FAIL',
                    'trades', 'One or more allocated trade rows have invalid names or negative vacancies.', $timestamp,
                    'Ensure every trade discipline has a title and at least 1 allocated vacancy.', null
                );
            } else {
                $ruleResults[] = $this->createResult(
                    $runId, $existingReqId, 'ERR-TRD-02', 'Trade Specification Validity', 'TRADES', 'INFO', 'PASS',
                    'trades', null, $timestamp, null, count($trades) . ' trade rows'
                );
            }

            if ($hasDuplicates) {
                $ruleResults[] = $this->createResult(
                    $runId, $existingReqId, 'WARN-TRD-01', 'Duplicate Trade Entry Check', 'TRADES', 'WARNING', 'WARNING',
                    'trades', 'Multiple trade rows specify identical trade titles; consider consolidating entries.', $timestamp,
                    'Consolidate duplicate trade allocations into a single row unless configuring separate shifts.', null
                );
                $warnings['trades_duplicate'] = 'Duplicate trade allocations detected.';
            }

            if ($quantity > 0 && $sumTradeVacancies !== $quantity) {
                $ruleResults[] = $this->createResult(
                    $runId, $existingReqId, 'ERR-TRD-03', 'Trade Vacancy Sum Parity', 'TRADES', 'ERROR', 'FAIL',
                    'quantity_required', "Total vacancies ({$quantity}) does not match the sum of trade allocations ({$sumTradeVacancies}).", $timestamp,
                    "Adjust total required vacancies ({$quantity}) to match trade allocation sum ({$sumTradeVacancies}).",
                    "Total: {$quantity}, Sum: {$sumTradeVacancies}"
                );
                $errors['quantity_required'] = "Total vacancies ({$quantity}) does not match the sum of trade allocations ({$sumTradeVacancies}).";
                $errors['quantity_consistency'] = "Total required vacancies ({$quantity}) does not match the sum of trade-wise vacancies ({$sumTradeVacancies}).";
            } else {
                $ruleResults[] = $this->createResult(
                    $runId, $existingReqId, 'ERR-TRD-03', 'Trade Vacancy Sum Parity', 'TRADES', 'INFO', 'PASS',
                    'quantity_required', null, $timestamp, null, "Sum: {$sumTradeVacancies} = Total: {$quantity}"
                );
            }
        }

        // -------------------------------------------------------------
        // CATEGORY C: QUALIFICATION & TECHNICAL ELIGIBILITY
        // -------------------------------------------------------------
        $qualification = trim((string)($data['qualification'] ?? ''));
        $validQuals = ['ITI Passed', 'Diploma', 'Apprentice', 'Certificate', 'Any Technical'];
        if (!empty($qualification) && !in_array($qualification, $validQuals, true)) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-QLF-01', 'Qualification Level Validity', 'QUALIFICATION', 'WARNING', 'WARNING',
                'qualification', "Qualification '{$qualification}' is unconventional; standard technical tier recommended.", $timestamp,
                'Select one of the standard tiers: ITI Passed, Diploma, Apprentice, or Certificate.', $qualification
            );
            $warnings['qualification'] = "Qualification '{$qualification}' is unconventional; verify candidate sourcing pool.";
        } else {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-QLF-01', 'Qualification Level Validity', 'QUALIFICATION', 'INFO', 'PASS',
                'qualification', null, $timestamp, null, $qualification ?: 'ITI Passed'
            );
        }

        // Board Certification
        $boardType = trim((string)($data['board_type'] ?? 'Either'));
        $validBoards = ['Either', 'NCVT', 'SCVT'];
        if (!in_array($boardType, $validBoards, true)) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-BRD-01', 'Board Certification Check', 'QUALIFICATION', 'WARNING', 'WARNING',
                'board_type', "Board certification '{$boardType}' unrecognized; defaulting to Either.", $timestamp,
                'Select Either, NCVT, or SCVT.', $boardType
            );
            $warnings['board_type'] = 'Unrecognized board certification type.';
        } else {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-BRD-01', 'Board Certification Check', 'QUALIFICATION', 'INFO', 'PASS',
                'board_type', null, $timestamp, null, $boardType
            );
        }

        // Passing Batch Years
        $passingFrom = (int)($data['passing_year_from'] ?? 0);
        $passingTo   = (int)($data['passing_year_to'] ?? 0);
        $currentYear = (int)date('Y');

        if ($passingFrom > 0 && ($passingFrom < 2010 || $passingFrom > ($currentYear + 2))) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-YEA-01', 'Passing Year From Range', 'QUALIFICATION', 'ERROR', 'FAIL',
                'passing_year_from', "Passing year from must be between 2010 and " . ($currentYear + 2) . ".", $timestamp,
                "Set passing year from between 2010 and " . ($currentYear + 2) . ".", (string)$passingFrom
            );
            $errors['passing_year_from'] = "Passing year from must be between 2010 and " . ($currentYear + 2) . ".";
        }
        if ($passingTo > 0 && ($passingTo < 2010 || $passingTo > ($currentYear + 2))) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-YEA-02', 'Passing Year To Range', 'QUALIFICATION', 'ERROR', 'FAIL',
                'passing_year_to', "Passing year to must be between 2010 and " . ($currentYear + 2) . ".", $timestamp,
                "Set passing year to between 2010 and " . ($currentYear + 2) . ".", (string)$passingTo
            );
            $errors['passing_year_to'] = "Passing year to must be between 2010 and " . ($currentYear + 2) . ".";
        }
        if ($passingFrom > 0 && $passingTo > 0 && $passingFrom > $passingTo) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-YEA-03', 'Passing Batch Range Order', 'QUALIFICATION', 'ERROR', 'FAIL',
                'passing_year_to', "Passing year to ({$passingTo}) cannot precede passing year from ({$passingFrom}).", $timestamp,
                "Ensure passing year to ({$passingTo}) is greater than or equal to passing year from ({$passingFrom}).",
                "From: {$passingFrom}, To: {$passingTo}"
            );
            $errors['passing_year_to'] = "Passing year to cannot precede passing year from.";
        }

        // -------------------------------------------------------------
        // CATEGORY D: SALARY & COMPENSATION VALIDATION
        // -------------------------------------------------------------
        $minSal = floatval($data['min_salary'] ?? 0);
        $maxSal = floatval($data['max_salary'] ?? 0);
        $salType = trim((string)($data['salary_type'] ?? 'Monthly'));
        $validSalTypes = ['Monthly', 'Stipend', 'Annual', 'Daily', 'Per Hour'];

        if (!in_array($salType, $validSalTypes, true)) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-SAL-03', 'Salary Remuneration Type', 'SALARY', 'ERROR', 'FAIL',
                'salary_type', 'Invalid compensation frequency type specified.', $timestamp,
                'Select a valid salary type: Monthly, Stipend, or Annual.', $salType
            );
            $errors['salary_type'] = 'Invalid compensation frequency type specified.';
        } else {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-SAL-03', 'Salary Remuneration Type', 'SALARY', 'INFO', 'PASS',
                'salary_type', null, $timestamp, null, $salType
            );
        }

        if ($minSal <= 0) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-SAL-01', 'Minimum Salary Bound', 'SALARY', 'ERROR', 'FAIL',
                'min_salary', 'Minimum salary must be greater than zero.', $timestamp,
                'Specify a valid positive minimum monthly salary/stipend amount (e.g. ₹15,000).', (string)$minSal
            );
            $errors['min_salary'] = 'Minimum salary must be greater than zero.';
        } else {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-SAL-01', 'Minimum Salary Bound', 'SALARY', 'INFO', 'PASS',
                'min_salary', null, $timestamp, null, "₹" . number_format($minSal)
            );
        }

        if ($maxSal < $minSal) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-SAL-02', 'Salary Inversion Check', 'SALARY', 'ERROR', 'FAIL',
                'max_salary', 'Maximum salary must be greater than or equal to minimum salary.', $timestamp,
                "Set maximum salary greater than or equal to minimum salary (min: ₹" . number_format($minSal) . ").",
                "Min: ₹{$minSal}, Max: ₹{$maxSal}"
            );
            $errors['max_salary'] = 'Maximum salary must be greater than or equal to minimum salary.';
        } else {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-SAL-02', 'Salary Inversion Check', 'SALARY', 'INFO', 'PASS',
                'max_salary', null, $timestamp, null, "₹" . number_format($maxSal)
            );
        }

        // Statutory Benchmark Warning (Under ₹12,000)
        if ($minSal > 0 && $minSal < 12000.0) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'WARN-SAL-01', 'Technician Remuneration Benchmark', 'SALARY', 'WARNING', 'WARNING',
                'min_salary', 'Minimum salary is below the regional ₹12,000 industrial technician benchmark.', $timestamp,
                'Consider raising minimum salary to at least ₹12,000 for standard auto-approval eligibility.', "₹" . number_format($minSal)
            );
            $warnings['min_salary'] = 'Minimum salary is below the regional ₹12,000 industrial technician benchmark.';
        }

        // -------------------------------------------------------------
        // CATEGORY E: CHRONOLOGICAL TIMELINE VALIDATION
        // -------------------------------------------------------------
        $deadlineStr  = trim((string)($data['application_deadline'] ?? ''));
        $interviewStr = trim((string)($data['interview_date'] ?? ''));
        $joiningStr   = trim((string)($data['target_joining_date'] ?? ''));
        $intEndStr    = trim((string)($data['interview_end_date'] ?? ''));

        $todayTs = strtotime(date('Y-m-d'));
        $deadTs  = !empty($deadlineStr)  ? strtotime($deadlineStr)  : false;
        $intTs   = !empty($interviewStr) ? strtotime($interviewStr) : false;
        $joinTs  = !empty($joiningStr)   ? strtotime($joiningStr)   : false;
        $intEndTs= !empty($intEndStr)    ? strtotime($intEndStr)    : false;

        // 1. Deadline in past check
        if ($deadTs !== false && $deadTs < $todayTs) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-DAT-01', 'Application Deadline Chronology', 'DATES', 'ERROR', 'FAIL',
                'application_deadline', 'Application deadline cannot be in the past.', $timestamp,
                'Set application deadline to today or a future calendar date.', $deadlineStr
            );
            $errors['application_deadline'] = 'Application deadline cannot be in the past.';
        } elseif ($deadTs !== false) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-DAT-01', 'Application Deadline Chronology', 'DATES', 'INFO', 'PASS',
                'application_deadline', null, $timestamp, null, $deadlineStr
            );
        }

        // 2. Interview before deadline check
        if ($deadTs !== false && $intTs !== false && $intTs < $deadTs) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-DAT-02', 'Interview vs Deadline Chronology', 'DATES', 'ERROR', 'FAIL',
                'interview_date', 'Interview date must occur on or after the application deadline.', $timestamp,
                "Schedule interview on or after deadline ({$deadlineStr}).", "Interview: {$interviewStr}, Deadline: {$deadlineStr}"
            );
            $errors['interview_date'] = 'Interview date must occur on or after the application deadline.';
        } elseif ($intTs !== false) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-DAT-02', 'Interview vs Deadline Chronology', 'DATES', 'INFO', 'PASS',
                'interview_date', null, $timestamp, null, $interviewStr
            );
        }

        // 3. Interview end date before start date
        if ($intTs !== false && $intEndTs !== false && $intEndTs < $intTs) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-DAT-04', 'Interview Window Span', 'DATES', 'ERROR', 'FAIL',
                'interview_end_date', 'Interview end date cannot precede interview start date.', $timestamp,
                "Set interview end date on or after interview start date ({$interviewStr}).", $intEndStr
            );
            $errors['interview_end_date'] = 'Interview end date cannot precede interview start date.';
        }

        // 4. Joining before interview or deadline check
        $benchmarkDate = ($intTs !== false) ? $intTs : $deadTs;
        if ($benchmarkDate !== false && $joinTs !== false && $joinTs < $benchmarkDate) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-DAT-03', 'Joining Milestone Chronology', 'DATES', 'ERROR', 'FAIL',
                'target_joining_date', 'Target joining date must occur after the interview/application milestones.', $timestamp,
                "Ensure target joining date is after interview/deadline milestones.", $joiningStr
            );
            $errors['target_joining_date'] = 'Target joining date must occur after the interview/application milestones.';
        } elseif ($joinTs !== false) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-DAT-03', 'Joining Milestone Chronology', 'DATES', 'INFO', 'PASS',
                'target_joining_date', null, $timestamp, null, $joiningStr
            );
        }

        // 5. Expedited Turnaround Warning (< 7 days)
        if ($joinTs !== false && ($joinTs - $todayTs) < (7 * 86400)) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'WARN-DAT-01', 'Turnaround Velocity Warning', 'DATES', 'WARNING', 'WARNING',
                'target_joining_date', 'Target joining date is within 7 days; expedited recruitment sourcing will be prioritized.', $timestamp,
                'Allowing $\ge 7$ days enables standard screening and student mobilization.', $joiningStr
            );
            $warnings['target_joining_date'] = 'Target joining date is within 7 days; expedited recruitment sourcing will be prioritized.';
        }

        // -------------------------------------------------------------
        // CATEGORY F: LOCATION & PLANT VALIDATION
        // -------------------------------------------------------------
        $plantId = !empty($data['plant_id']) ? (int)$data['plant_id'] : null;
        if ($plantId) {
            $plantStmt = $this->pdo->prepare("SELECT id, plant_name, status, state, district, city FROM `manufacturing_plants` WHERE id = :pid AND company_id = :cid LIMIT 1");
            $plantStmt->execute([':pid' => $plantId, ':cid' => $companyId]);
            $plant = $plantStmt->fetch();

            if (!$plant) {
                $ruleResults[] = $this->createResult(
                    $runId, $existingReqId, 'ERR-LOC-01', 'Plant Ownership Verification', 'LOCATION', 'ERROR', 'FAIL',
                    'plant_id', 'Specified manufacturing plant was not found or does not belong to your company.', $timestamp,
                    'Select a manufacturing plant registered under your company profile.', (string)$plantId
                );
                $errors['plant_id'] = 'Specified manufacturing plant was not found or does not belong to your company.';
            } elseif ($plant['status'] !== 'Active') {
                $ruleResults[] = $this->createResult(
                    $runId, $existingReqId, 'ERR-LOC-02', 'Plant Active Status', 'LOCATION', 'ERROR', 'FAIL',
                    'plant_id', "Selected plant '{$plant['plant_name']}' is currently inactive.", $timestamp,
                    "Activate plant '{$plant['plant_name']}' in Plant Management before creating requirements.", $plant['status']
                );
                $errors['plant_id'] = "Selected manufacturing plant '{$plant['plant_name']}' is inactive.";
            } else {
                $ruleResults[] = $this->createResult(
                    $runId, $existingReqId, 'ERR-LOC-01', 'Plant Ownership & Status', 'LOCATION', 'INFO', 'PASS',
                    'plant_id', null, $timestamp, null, $plant['plant_name']
                );
            }
        }

        // Job Location fields
        $locState = trim((string)($data['job_location_state'] ?? ''));
        $locCity  = trim((string)($data['job_location_city'] ?? ''));
        if (empty($locState) && empty($locCity) && !$plantId) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-LOC-03', 'Deployment Location Specification', 'LOCATION', 'WARNING', 'WARNING',
                'job_location_state', 'No manufacturing plant or deployment state specified; default corporate headquarters will be assumed.', $timestamp,
                'Specify city and state deployment location or link a registered manufacturing plant.', null
            );
            $warnings['job_location_state'] = 'Deployment location unspecified; please confirm plant facility location.';
        } else {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-LOC-03', 'Deployment Location Specification', 'LOCATION', 'INFO', 'PASS',
                'job_location_state', null, $timestamp, null, "{$locCity}, {$locState}"
            );
        }

        // Employment Type check
        $empType = trim((string)($data['employment_type'] ?? 'Full Time'));
        $validEmpTypes = ['Full Time', 'Apprenticeship', 'Fixed Term', 'OJT'];
        if (!in_array($empType, $validEmpTypes, true)) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-EMP-01', 'Employment Type Validity', 'LOCATION', 'WARNING', 'WARNING',
                'employment_type', "Employment type '{$empType}' unrecognized.", $timestamp,
                'Select Full Time, Apprenticeship, Fixed Term, or OJT.', $empType
            );
            $warnings['employment_type'] = 'Unrecognized employment type.';
        }

        // -------------------------------------------------------------
        // CATEGORY G: RECRUITMENT & STATUTORY ELIGIBILITY VALIDATION
        // -------------------------------------------------------------
        $minAge = (int)($data['min_age'] ?? 18);
        $maxAge = !empty($data['max_age']) ? (int)$data['max_age'] : null;

        if ($minAge < 18) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-AGE-01', 'Statutory Minimum Age Compliance', 'RECRUITMENT', 'ERROR', 'FAIL',
                'min_age', 'Minimum candidate age cannot be less than 18 years under the Factories Act.', $timestamp,
                'Set minimum candidate age to 18 or above to comply with statutory industrial safety regulations.', (string)$minAge
            );
            $errors['min_age'] = 'Minimum candidate age cannot be less than 18 years under the Factories Act.';
        } else {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-AGE-01', 'Statutory Minimum Age Compliance', 'RECRUITMENT', 'INFO', 'PASS',
                'min_age', null, $timestamp, null, (string)$minAge
            );
        }

        if ($maxAge !== null && $maxAge < $minAge) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-AGE-02', 'Maximum Age Inversion', 'RECRUITMENT', 'ERROR', 'FAIL',
                'max_age', 'Maximum age cannot be less than minimum age.', $timestamp,
                "Ensure maximum age is greater than or equal to minimum age ({$minAge}).",
                "Min: {$minAge}, Max: {$maxAge}"
            );
            $errors['max_age'] = 'Maximum age cannot be less than minimum age.';
        }

        // Interview Mode Check
        $interviewMode = trim((string)($data['interview_mode'] ?? 'Plant Campus Walk-in'));
        $validModes = ['Plant Campus Walk-in', 'Face to Face', 'Virtual / Online', 'Hybrid', 'In-Person', 'Campus Drive', 'Plant Interview'];
        if (!in_array($interviewMode, $validModes, true)) {
            $ruleResults[] = $this->createResult(
                $runId, $existingReqId, 'ERR-INT-01', 'Interview Mode Specification', 'RECRUITMENT', 'WARNING', 'WARNING',
                'interview_mode', 'Unrecognized interview mode; standard in-person or campus drive advised.', $timestamp,
                'Select Plant Campus Walk-in, Face to Face, Virtual / Online, or Hybrid.', $interviewMode
            );
            $warnings['interview_mode'] = 'Unrecognized interview mode.';
        }

        // -------------------------------------------------------------
        // VALIDATION RESULT CLASSIFICATION (Section 4)
        // -------------------------------------------------------------
        $hasErrors = !empty($errors);
        $hasWarnings = !empty($warnings);

        if ($hasErrors) {
            $validationStatus = 'VALIDATION_FAILED';
            $targetStatus = 'NEEDS_CORRECTION';
        } elseif ($hasWarnings) {
            $validationStatus = 'VALIDATION_WARNING';
            $targetStatus = 'APPROVED';
        } else {
            $validationStatus = 'VALIDATED';
            $targetStatus = 'APPROVED';
        }

        // Check High Volume & Review Conditions if technically valid
        if (!$hasErrors) {
            // High volume threshold (> 50 headcount)
            if ($quantity > 50) {
                $ruleResults[] = $this->createResult(
                    $runId, $existingReqId, 'BR-APP-002', 'High-Volume Requisition Threshold', 'TRADES', 'MANUAL_REVIEW', 'REVIEW_REQUIRED',
                    'quantity_required', "Requisition quantity ({$quantity}) exceeds standard automated threshold (50); routing to Super Admin review.", $timestamp,
                    'Super Administration will review batch allocation before sourcing commences.', (string)$quantity
                );
                $manualReviews['quantity_required'] = "Requisition headcount of {$quantity} exceeds standard automated approval ceiling (50).";
                $targetStatus = 'PENDING_REVIEW';
            }

            // Unverified employer standing
            $verStatus = $company['verification_status'] ?? 'Pending';
            if ($verStatus !== 'Verified' && $verStatus !== 'Active') {
                $targetStatus = 'PENDING_REVIEW';
            }

            // Below ₹12,000 threshold
            if ($minSal < 12000.0) {
                $targetStatus = 'PENDING_REVIEW';
                $manualReviews['min_salary'] = "Minimum salary ₹" . number_format($minSal, 2) . " is below ₹12,000 benchmark and requires administrative concurrence.";
            }

            // Expedited turnaround under 7 days
            if ($joinTs !== false && ($joinTs - $todayTs) < (7 * 86400)) {
                $targetStatus = 'PENDING_REVIEW';
                $manualReviews['target_joining_date'] = "Target joining date turnaround is less than 7 days, requiring recruiter capacity review.";
            }
        }

        // Group Results by Category
        $categoriesList = ['BASIC_INFO', 'TRADES', 'QUALIFICATION', 'SALARY', 'DATES', 'LOCATION', 'RECRUITMENT'];
        $categoriesSummary = [];
        foreach ($categoriesList as $catKey) {
            $catRules = array_filter($ruleResults, fn($r) => $r['category'] === $catKey);
            $catPassed = count(array_filter($catRules, fn($r) => $r['result'] === 'PASS'));
            $catFailed = count(array_filter($catRules, fn($r) => $r['result'] === 'FAIL'));
            $catWarnings = count(array_filter($catRules, fn($r) => $r['result'] === 'WARNING'));

            $catStatus = 'PASS';
            if ($catFailed > 0) {
                $catStatus = 'FAIL';
            } elseif ($catWarnings > 0) {
                $catStatus = 'WARNING';
            }

            $categoriesSummary[$catKey] = [
                'status'      => $catStatus,
                'total_rules' => count($catRules),
                'passed'      => $catPassed,
                'failed'      => $catFailed,
                'warnings'    => $catWarnings,
                'rules'       => array_values($catRules)
            ];
        }

        // Critical Issues list (Rules with FAIL result)
        $criticalIssues = array_values(array_filter($ruleResults, fn($r) => $r['result'] === 'FAIL'));

        // Summary Statistics
        $summary = [
            'total_rules'       => count($ruleResults),
            'passed'            => count(array_filter($ruleResults, fn($r) => $r['result'] === 'PASS')),
            'failed'            => count(array_filter($ruleResults, fn($r) => $r['result'] === 'FAIL')),
            'warnings'          => count($warnings),
            'manual_reviews'    => count($manualReviews),
            'validation_status' => $validationStatus
        ];

        $outcome = [
            'is_valid'          => !$hasErrors,
            'validation_status' => $validationStatus,
            'target_status'     => $targetStatus,
            'trigger_type'      => $triggerType,
            'errors'            => $errors,
            'warnings'          => $warnings,
            'manual_reviews'    => $manualReviews,
            'rule_results'      => $ruleResults,
            'summary'           => $summary,
            'categories'        => $categoriesSummary,
            'critical_issues'   => $criticalIssues,
            'version'           => self::VERSION,
            'timestamp'         => $timestamp
        ];

        // Persist to Validation History if requested and requirement ID is available
        if ($persistHistory && $existingReqId !== null && $existingReqId > 0) {
            $this->persistValidationHistory($existingReqId, $outcome, $triggeredBy, $triggerType);
        }

        return $outcome;
    }

    /**
     * Persists validation run decisions to `requirement_validation_history`.
     */
    public function persistValidationHistory(int $requirementId, array $outcome, ?int $triggeredBy = null, string $triggerType = 'SUBMISSION'): bool
    {
        if (empty($outcome['rule_results'])) {
            return false;
        }

        try {
            $sql = "INSERT INTO `requirement_validation_history` 
                (`requirement_id`, `validation_run_id`, `rule_id`, `rule_name`, `category`, `severity`, 
                 `result`, `field_name`, `error_message`, `details_json`, `validation_version`, `triggered_by`, `created_at`) 
                VALUES 
                (:req_id, :run_id, :rule_id, :rule_name, :cat, :sev, :res, :field, :msg, :details, :ver, :user, :created)";
            
            $stmt = $this->pdo->prepare($sql);

            foreach ($outcome['rule_results'] as $r) {
                $detailsPayload = array_merge($r['details'] ?? [], [
                    'suggested_correction' => $r['suggested_correction'] ?? null,
                    'current_value'        => $r['current_value'] ?? null,
                    'trigger_type'         => $triggerType,
                    'validation_status'    => $outcome['validation_status'] ?? 'VALIDATING'
                ]);

                $stmt->execute([
                    ':req_id'    => $requirementId,
                    ':run_id'    => $r['validation_run_id'],
                    ':rule_id'   => $r['rule_id'],
                    ':rule_name' => $r['rule_name'],
                    ':cat'       => $r['category'],
                    ':sev'       => $r['severity'],
                    ':res'       => $r['result'],
                    ':field'     => $r['field_name'],
                    ':msg'       => $r['error_message'],
                    ':details'   => json_encode($detailsPayload, JSON_UNESCAPED_UNICODE),
                    ':ver'       => $outcome['version'] ?? self::VERSION,
                    ':user'      => $triggeredBy,
                    ':created'   => $r['timestamp'] ?? date('Y-m-d H:i:s')
                ]);
            }
            return true;
        } catch (Exception $e) {
            error_log("Failed to persist validation history: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves all historical validation runs for a specific requirement.
     */
    public function getValidationHistory(int $requirementId): array
    {
        $stmt = $this->pdo->prepare("SELECT vh.*, u.full_name as triggered_by_name 
            FROM `requirement_validation_history` vh
            LEFT JOIN `users` u ON u.id = vh.triggered_by
            WHERE vh.requirement_id = :rid 
            ORDER BY vh.id DESC");
        $stmt->execute([':rid' => $requirementId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve the latest validation run for a requirement with rich structured telemetry.
     */
    public function getLatestValidationRun(int $requirementId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT validation_run_id, created_at, triggered_by, validation_version 
            FROM `requirement_validation_history` 
            WHERE requirement_id = :rid 
            ORDER BY id DESC LIMIT 1");
        $stmt->execute([':rid' => $requirementId]);
        $latestMeta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$latestMeta) {
            return null;
        }

        $runId = $latestMeta['validation_run_id'];

        $ruleStmt = $this->pdo->prepare("SELECT * FROM `requirement_validation_history` 
            WHERE requirement_id = :rid AND validation_run_id = :run_id 
            ORDER BY id ASC");
        $ruleStmt->execute([':rid' => $requirementId, ':run_id' => $runId]);
        $rows = $ruleStmt->fetchAll(PDO::FETCH_ASSOC);

        $passed = 0;
        $warnings = 0;
        $errors = 0;
        $critical = 0;
        $ruleResults = [];
        $validationStatus = 'VALIDATED';
        $triggerType = 'SUBMISSION';

        foreach ($rows as $row) {
            $decoded = !empty($row['details_json']) ? json_decode($row['details_json'], true) : [];
            $sugCorr = $decoded['suggested_correction'] ?? null;
            $currVal = $decoded['current_value'] ?? null;
            if (!empty($decoded['trigger_type'])) {
                $triggerType = $decoded['trigger_type'];
            }
            if (!empty($decoded['validation_status'])) {
                $validationStatus = $decoded['validation_status'];
            }

            $resType = match ($row['result']) {
                'PASS'            => 'PASS',
                'WARNING'         => 'WARNING',
                'FAIL'            => 'ERROR',
                'REVIEW_REQUIRED' => 'WARNING',
                default           => 'PASS'
            };

            if ($resType === 'PASS') $passed++;
            if ($resType === 'WARNING') $warnings++;
            if ($resType === 'ERROR') {
                $errors++;
                if ($row['severity'] === 'ERROR') $critical++;
            }

            $ruleResults[] = [
                'rule_id'              => $row['rule_id'],
                'rule_name'            => $row['rule_name'],
                'category'             => $row['category'],
                'severity'             => $row['severity'] === 'ERROR' ? 'Critical' : ($row['severity'] === 'WARNING' ? 'Medium' : 'Low'),
                'status'               => $resType,
                'result'               => $row['result'],
                'field'                => $row['field_name'] ?? 'general',
                'message'              => $row['error_message'] ?? 'Validation check passed.',
                'suggested_correction' => $sugCorr,
                'current_value'        => $currVal,
                'timestamp'            => $row['created_at']
            ];
        }

        $isValid = ($errors === 0);
        if (!$isValid) {
            $validationStatus = 'VALIDATION_FAILED';
        } elseif ($warnings > 0 && $validationStatus !== 'VALIDATION_FAILED') {
            $validationStatus = 'VALIDATION_WARNING';
        }

        return [
            'requirement_id'    => $requirementId,
            'validation_run_id' => $runId,
            'is_valid'          => $isValid,
            'validated_at'      => $latestMeta['created_at'],
            'trigger_type'      => $triggerType,
            'details'           => [
                'validation_status' => $validationStatus,
                'algorithm_version' => $latestMeta['validation_version'] ?? self::VERSION,
                'passed_count'      => $passed,
                'warning_count'     => $warnings,
                'error_count'       => $errors,
                'critical_issues'   => $critical,
                'rule_results'      => $ruleResults,
                'trigger_type'      => $triggerType
            ]
        ];
    }

    /**
     * Retrieves grouped validation runs by run ID with trigger info and aggregated counts.
     */
    public function getValidationRunsSummary(int $requirementId): array
    {
        $stmt = $this->pdo->prepare("SELECT 
                vh.validation_run_id,
                MAX(vh.created_at) as run_time,
                COUNT(*) as total_rules,
                SUM(CASE WHEN vh.result = 'PASS' THEN 1 ELSE 0 END) as passed_count,
                SUM(CASE WHEN vh.result = 'WARNING' OR vh.result = 'REVIEW_REQUIRED' THEN 1 ELSE 0 END) as warning_count,
                SUM(CASE WHEN vh.result = 'FAIL' THEN 1 ELSE 0 END) as error_count,
                u.full_name as triggered_by_name,
                MAX(vh.details_json) as sample_details
            FROM `requirement_validation_history` vh
            LEFT JOIN `users` u ON u.id = vh.triggered_by
            WHERE vh.requirement_id = :rid
            GROUP BY vh.validation_run_id, u.full_name
            ORDER BY MAX(vh.id) DESC");
        $stmt->execute([':rid' => $requirementId]);
        $runs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decorate summary fields
        foreach ($runs as &$run) {
            $triggerType = 'SUBMISSION';
            if (!empty($run['sample_details'])) {
                $decoded = json_decode($run['sample_details'], true);
                if (!empty($decoded['trigger_type'])) {
                    $triggerType = $decoded['trigger_type'];
                }
            }
            $run['trigger_type'] = $triggerType;
            $run['validated_at'] = $run['run_time'];
            $run['is_valid']     = ((int)$run['error_count'] === 0);
        }
        return $runs;
    }

    /**
     * Helper to construct individual rule result items.
     */
    private function createResult(
        string $runId, 
        ?int $reqId, 
        string $ruleId, 
        string $ruleName, 
        string $category, 
        string $severity, 
        string $result, 
        ?string $fieldName, 
        ?string $errorMessage, 
        string $timestamp,
        ?string $suggestedCorrection = null,
        $currentValue = null,
        ?array $details = null
    ): array {
        return [
            'validation_run_id'    => $runId,
            'requirement_id'       => $reqId,
            'rule_id'              => $ruleId,
            'rule_name'            => $ruleName,
            'category'             => $category,
            'severity'             => $severity,
            'result'               => $result,
            'status'               => $result,
            'field_name'           => $fieldName,
            'error_message'        => $errorMessage,
            'suggested_correction' => $suggestedCorrection,
            'current_value'        => $currentValue !== null ? (string)$currentValue : null,
            'details'              => $details,
            'timestamp'            => $timestamp,
            'detected_at'          => $timestamp,
            'version'              => self::VERSION
        ];
    }
}
