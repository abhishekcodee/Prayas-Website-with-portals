<?php
/**
 * Seeder: System Settings
 * Database: manpower_portal_db
 * Organization: PRAYAS – The Automation Academy
 */

function seedSystemSettings(PDO $pdo): void {
    echo "[+] Seeding System Settings..." . PHP_EOL;

    $settings = [
        [
            'key' => 'org_name',
            'value' => 'PRAYAS – The Automation Academy',
            'desc' => 'Official organization name',
            'public' => 1
        ],
        [
            'key' => 'portal_title',
            'value' => 'Industry Manpower Requirement & Recruitment Portal',
            'desc' => 'Portal title for display',
            'public' => 1
        ],
        [
            'key' => 'min_candidate_age',
            'value' => '18',
            'desc' => 'Statutory minimum age for factory deployment',
            'public' => 1
        ],
        [
            'key' => 'auto_approval_max_quantity',
            'value' => '50',
            'desc' => 'Maximum headcount for auto-approval without manual review',
            'public' => 0
        ],
        [
            'key' => 'session_timeout_minutes',
            'value' => '30',
            'desc' => 'Session inactivity timeout in minutes',
            'public' => 0
        ],
        [
            'key' => 'matching_threshold_tier1',
            'value' => '85.0',
            'desc' => 'Score cutoff for Tier 1 Top Match',
            'public' => 0
        ],
        [
            'key' => 'matching_threshold_tier2',
            'value' => '70.0',
            'desc' => 'Score cutoff for Tier 2 Strong Match',
            'public' => 0
        ],
        [
            'key' => 'matching_threshold_min',
            'value' => '50.0',
            'desc' => 'Minimum score required to display candidate match',
            'public' => 0
        ],
        [
            'key' => 'buffer_selection_percentage',
            'value' => '20',
            'desc' => 'Buffer percentage allowed over total quantity required',
            'public' => 0
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`, `is_public`) 
        VALUES (:key, :value, :desc, :public)
        ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `description` = VALUES(`description`), `is_public` = VALUES(`is_public`)");

    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }

    echo "    System settings seeded successfully." . PHP_EOL;
}

