<?php
/**
 * Candidate Source Data Access Model (Strictly Read-Only)
 * 
 * Project: Industry Manpower Requirement Portal
 * Organization: PRAYAS – The Automation Academy
 * Technology: Core PHP + PDO + MySQL
 * 
 * Guarantees:
 * - Read-only SELECT operations on prayas_iti_dashboard.students (1,292 records)
 * - Read-only SELECT operations on itistudents.candidates (56 records)
 * - Zero mutation (No INSERT, UPDATE, DELETE, ALTER, DROP, TRUNCATE)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class CandidateSourceModel
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Get registered candidate source definitions.
     */
    public function getAvailableSources(): array
    {
        return [
            'prayas_dashboard' => [
                'name'        => 'PRAYAS ITI Student Dashboard',
                'database'    => 'prayas_iti_dashboard',
                'table'       => 'students',
                'read_only'   => true
            ],
            'itistudents' => [
                'name'        => 'Accredited ITI Candidates Database',
                'database'    => 'itistudents',
                'table'       => 'candidates',
                'read_only'   => true
            ]
        ];
    }

    /**
     * Get real-time candidate counts from both external repositories.
     */
    public function getTotalCandidateCounts(): array
    {
        $c1 = (int)$this->pdo->query("SELECT COUNT(*) FROM `prayas_iti_dashboard`.`students`")->fetchColumn();
        $c2 = (int)$this->pdo->query("SELECT COUNT(*) FROM `itistudents`.`candidates`")->fetchColumn();

        return [
            'prayas_dashboard' => $c1,
            'itistudents'      => $c2,
            'total'            => $c1 + $c2
        ];
    }

    /**
     * Fetch normalized candidates from a single specified source.
     */
    public function fetchCandidates(string $source, array $criteria = []): array
    {
        $limit = max(1, (int)($criteria['limit'] ?? 100));
        $offset = max(0, (int)($criteria['offset'] ?? 0));
        $trade = $criteria['trade'] ?? null;
        if ($trade && empty($criteria['trades'])) {
            $criteria['trades'] = [$trade];
        }

        $results = [];
        if ($source === 'prayas_dashboard' || $source === 'prayas_iti_dashboard') {
            $rows = $this->queryPrayasStudents($criteria, $limit, $offset);
            foreach ($rows as $r) {
                $results[] = $this->normalizePrayasRecord($r);
            }
        } elseif ($source === 'itistudents') {
            $rows = $this->queryItiCandidates($criteria, $limit, $offset);
            foreach ($rows as $r) {
                $results[] = $this->normalizeItiRecord($r);
            }
        }

        return $results;
    }

    /**
     * Search and retrieve normalized candidate records matching requirement search criteria.
     * Uses SQL filtering first to minimize in-memory footprints.
     * 
     * @param array $criteria Search constraints (trades, passing_years, location, gender, etc.)
     * @param int $limit Max candidates to retrieve per source
     * @param int $offset Offset for pagination
     * @return array Normalized candidate list from both sources
     */
    public function searchCandidates(array $criteria = [], int $limit = 500, int $offset = 0): array
    {
        $results = [];

        // 1. Fetch from prayas_iti_dashboard.students
        $prayasCandidates = $this->queryPrayasStudents($criteria, $limit, $offset);
        foreach ($prayasCandidates as $row) {
            $results[] = $this->normalizePrayasRecord($row);
        }

        // 2. Fetch from itistudents.candidates
        $itiCandidates = $this->queryItiCandidates($criteria, $limit, $offset);
        foreach ($itiCandidates as $row) {
            $results[] = $this->normalizeItiRecord($row);
        }

        return $results;
    }

    /**
     * Retrieve a single normalized candidate by source and source ID.
     */
    public function getCandidateById(string $source, int $sourceId): ?array
    {
        if ($source === 'prayas_dashboard' || $source === 'prayas_iti_dashboard') {
            $stmt = $this->pdo->prepare("SELECT * FROM `prayas_iti_dashboard`.`students` WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $sourceId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $this->normalizePrayasRecord($row) : null;
        }

        if ($source === 'itistudents') {
            $stmt = $this->pdo->prepare("SELECT * FROM `itistudents`.`candidates` WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $sourceId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $this->normalizeItiRecord($row) : null;
        }

        return null;
    }

    /**
     * Query prayas_iti_dashboard.students with parameterized filters.
     */
    private function queryPrayasStudents(array $criteria, int $limit, int $offset): array
    {
        $sql = "SELECT id, name, trade, passing_year, district, hsc, ssc, gender, 
                       institute_id, iti, dob, experience, previous_company, preferred_location, 
                       expected_salary, computer_knowledge, languages_known, driving_licence, 
                       registration_no, result, status, job_role, remarks, created_at, updated_at
                FROM `prayas_iti_dashboard`.`students` 
                WHERE 1=1";
        $params = [];

        // Exclude failed candidates if specified
        if (!empty($criteria['must_be_passed'])) {
            $sql .= " AND (result IS NULL OR result != 'FAIL')";
        }

        // Trade Filter
        if (!empty($criteria['trades']) && is_array($criteria['trades'])) {
            $tradePlaceholders = [];
            foreach ($criteria['trades'] as $idx => $t) {
                $ph = ":pr_trd_{$idx}";
                $tradePlaceholders[] = $ph;
                $params[$ph] = $t;
            }
            $sql .= " AND trade IN (" . implode(',', $tradePlaceholders) . ")";
        }

        // Passing Year Filter
        if (!empty($criteria['passing_year'])) {
            $sql .= " AND passing_year LIKE :pr_pyear";
            $params[':pr_pyear'] = '%' . $criteria['passing_year'] . '%';
        }

        // District / Location Filter
        if (!empty($criteria['district'])) {
            $sql .= " AND (district LIKE :pr_dist OR preferred_location LIKE :pr_loc)";
            $params[':pr_dist'] = '%' . $criteria['district'] . '%';
            $params[':pr_loc']  = '%' . $criteria['district'] . '%';
        }

        // Gender Filter
        if (!empty($criteria['gender']) && in_array($criteria['gender'], ['Male', 'Female'], true)) {
            $sql .= " AND gender = :pr_gender";
            $params[':pr_gender'] = $criteria['gender'];
        }

        // Keyword Search
        if (!empty($criteria['keyword'])) {
            $sql .= " AND (name LIKE :pr_kw1 OR trade LIKE :pr_kw2 OR iti LIKE :pr_kw3)";
            $params[':pr_kw1'] = '%' . $criteria['keyword'] . '%';
            $params[':pr_kw2'] = '%' . $criteria['keyword'] . '%';
            $params[':pr_kw3'] = '%' . $criteria['keyword'] . '%';
        }

        $sql .= " ORDER BY id ASC LIMIT :lim OFFSET :off";

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
     * Query itistudents.candidates with parameterized filters.
     */
    private function queryItiCandidates(array $criteria, int $limit, int $offset): array
    {
        $sql = "SELECT id, candidate_serial_no, candidate_registration_no, full_name, date_of_birth, 
                       iti_trade, iti_college_name, year_of_passing, passing_month, tenth_percentage, 
                       iti_percentage_grade, experience, previous_company, preferred_job_location, 
                       can_relocate, expected_salary, computer_knowledge, languages_known, driving_licence, 
                       expected_joining_date, communication, confidence, knowledge, behaviour, feedback, 
                       verification_status, status, gender
                FROM `itistudents`.`candidates` 
                WHERE 1=1";
        $params = [];

        // Trade Filter
        if (!empty($criteria['trades']) && is_array($criteria['trades'])) {
            $tradePlaceholders = [];
            foreach ($criteria['trades'] as $idx => $t) {
                $ph = ":iti_trd_{$idx}";
                $tradePlaceholders[] = $ph;
                $params[$ph] = $t;
            }
            $sql .= " AND iti_trade IN (" . implode(',', $tradePlaceholders) . ")";
        }

        // Passing Year Filter
        if (!empty($criteria['passing_year'])) {
            $sql .= " AND year_of_passing = :iti_pyear";
            $params[':iti_pyear'] = (string)$criteria['passing_year'];
        }

        // Location Filter
        if (!empty($criteria['district'])) {
            $sql .= " AND preferred_job_location LIKE :iti_loc";
            $params[':iti_loc'] = '%' . $criteria['district'] . '%';
        }

        // Gender Filter
        if (!empty($criteria['gender']) && in_array($criteria['gender'], ['Male', 'Female'], true)) {
            $sql .= " AND gender = :iti_gender";
            $params[':iti_gender'] = $criteria['gender'];
        }

        // Keyword Search
        if (!empty($criteria['keyword'])) {
            $sql .= " AND (full_name LIKE :iti_kw1 OR iti_trade LIKE :iti_kw2 OR iti_college_name LIKE :iti_kw3)";
            $params[':iti_kw1'] = '%' . $criteria['keyword'] . '%';
            $params[':iti_kw2'] = '%' . $criteria['keyword'] . '%';
            $params[':iti_kw3'] = '%' . $criteria['keyword'] . '%';
        }

        $sql .= " ORDER BY id ASC LIMIT :lim OFFSET :off";

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
     * Field Normalization for prayas_iti_dashboard.students.
     */
    private function normalizePrayasRecord(array $r): array
    {
        // Parse passing year integer
        $rawYear = (string)($r['passing_year'] ?? '');
        $parsedYear = null;
        if (preg_match('/\b(20\d{2})\b/', $rawYear, $matches)) {
            $parsedYear = (int)$matches[1];
        }

        return [
            'source'                    => 'prayas_dashboard',
            'candidate_source'          => 'prayas_dashboard',
            'source_id'                 => (int)$r['id'],
            'candidate_source_id'       => (int)$r['id'],
            'registration_no'           => $r['registration_no'] ?: null,
            'serial_no'                 => null,
            'candidate_name'            => trim((string)$r['name']),
            'full_name'                 => trim((string)$r['name']),
            'trade'                     => trim((string)($r['trade'] ?? '')),
            'college_name'              => trim((string)($r['iti'] ?? '')),
            'passing_year_raw'          => $rawYear,
            'passing_year'              => $parsedYear,
            'passing_month'             => null,
            'qualification'             => 'ITI Passed',
            'ssc_percentage'            => !empty($r['ssc']) ? floatval($r['ssc']) : null,
            'hsc_percentage'            => !empty($r['hsc']) ? floatval($r['hsc']) : null,
            'iti_percentage'            => null, // not separately stored in students
            'gender'                    => trim((string)($r['gender'] ?? 'Any')),
            'date_of_birth'             => $r['dob'] ?: null,
            'district'                  => trim((string)($r['district'] ?? '')),
            'preferred_location'        => trim((string)($r['preferred_location'] ?? '')),
            'state'                     => 'Maharashtra',
            'experience'                => trim((string)($r['experience'] ?? 'Fresher')),
            'previous_company'          => $r['previous_company'] ?: null,
            'expected_salary'           => $r['expected_salary'] ?: null,
            'computer_knowledge'        => $r['computer_knowledge'] ?: null,
            'languages_known'           => $r['languages_known'] ?: null,
            'driving_licence'           => $r['driving_licence'] ?: null,
            'availability_date'         => null,
            'result_status'             => $r['result'] ?: 'PASS',
            'is_passed'                 => (strtoupper((string)($r['result'] ?? '')) !== 'FAIL') ? 1 : 0,
            'verification_status'       => $r['status'] ?: 'Active',
            'evaluator_feedback'        => $r['remarks'] ?: null,
            'has_resume'                => false
        ];
    }

    /**
     * Field Normalization for itistudents.candidates.
     */
    private function normalizeItiRecord(array $r): array
    {
        $rawYear = (string)($r['year_of_passing'] ?? '');
        $parsedYear = null;
        if (preg_match('/\b(20\d{2})\b/', $rawYear, $matches)) {
            $parsedYear = (int)$matches[1];
        }

        // Clean numeric percentages
        $ssc = null;
        if (!empty($r['tenth_percentage'])) {
            $cleanSsc = preg_replace('/[^0-9.]/', '', (string)$r['tenth_percentage']);
            if (is_numeric($cleanSsc)) $ssc = floatval($cleanSsc);
        }

        $itiPct = null;
        if (!empty($r['iti_percentage_grade'])) {
            $cleanIti = preg_replace('/[^0-9.]/', '', (string)$r['iti_percentage_grade']);
            if (is_numeric($cleanIti)) $itiPct = floatval($cleanIti);
        }

        return [
            'source'                    => 'itistudents',
            'candidate_source'          => 'itistudents',
            'source_id'                 => (int)$r['id'],
            'candidate_source_id'       => (int)$r['id'],
            'registration_no'           => $r['candidate_registration_no'] ?: null,
            'serial_no'                 => $r['candidate_serial_no'] ?: null,
            'candidate_name'            => trim((string)($r['full_name'] ?? '')),
            'full_name'                 => trim((string)($r['full_name'] ?? '')),
            'trade'                     => trim((string)($r['iti_trade'] ?? '')),
            'college_name'              => trim((string)($r['iti_college_name'] ?? '')),
            'passing_year_raw'          => $rawYear,
            'passing_year'              => $parsedYear,
            'passing_month'             => $r['passing_month'] ?: null,
            'qualification'             => 'ITI Passed',
            'ssc_percentage'            => $ssc,
            'hsc_percentage'            => null,
            'iti_percentage'            => $itiPct,
            'gender'                    => trim((string)($r['gender'] ?? 'Any')),
            'date_of_birth'             => $r['date_of_birth'] ?: null,
            'district'                  => trim((string)($r['preferred_job_location'] ?? '')),
            'preferred_location'        => trim((string)($r['preferred_job_location'] ?? '')),
            'state'                     => 'Maharashtra',
            'experience'                => trim((string)($r['experience'] ?? 'Fresher')),
            'previous_company'          => $r['previous_company'] ?: null,
            'expected_salary'           => $r['expected_salary'] ?: null,
            'computer_knowledge'        => $r['computer_knowledge'] ?: null,
            'languages_known'           => $r['languages_known'] ?: null,
            'driving_licence'           => $r['driving_licence'] ?: null,
            'availability_date'         => $r['expected_joining_date'] ?: null,
            'result_status'             => 'PASS',
            'is_passed'                 => 1,
            'verification_status'       => $r['verification_status'] ?: 'VERIFIED',
            'evaluator_feedback'        => $r['feedback'] ?: null,
            'has_resume'                => false
        ];
    }
}

