<?php
/**
 * PRAYAS Platform - Database Seeder
 */

require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = getDb();
    echo "Seeding database...\n";

    // 1. Seed Super Admin if none exists
    $adminCheck = $pdo->query("SELECT COUNT(*) FROM admin WHERE email = 'admin@prayas.org'")->fetchColumn();
    if ($adminCheck == 0) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO admin (name, email, password, role, status) VALUES (?, ?, ?, 'super_admin', 'active')");
        $stmt->execute(['PRAYAS Super Admin', 'admin@prayas.org', $hash]);
        echo "[+] Super admin seeded: admin@prayas.org / admin123\n";
    } else {
        echo "[i] Super admin already exists.\n";
    }

    // 2. Seed Sample Aptitude Questions if empty
    $aptCheck = $pdo->query("SELECT COUNT(*) FROM aptitude_questions")->fetchColumn();
    if ($aptCheck == 0) {
        $questions = [
            [
                'question' => 'What does PLC stand for in Industrial Automation?',
                'a' => 'Programmable Logic Controller',
                'b' => 'Personal Logic Computer',
                'c' => 'Power Line Communication',
                'd' => 'Process Level Circuit',
                'correct' => 'A'
            ],
            [
                'question' => 'Which sensor is primarily used to detect metallic objects on an industrial conveyor?',
                'a' => 'Capacitive Proximity Sensor',
                'b' => 'Inductive Proximity Sensor',
                'c' => 'Ultrasonic Sensor',
                'd' => 'Photoelectric Sensor',
                'correct' => 'B'
            ],
            [
                'question' => 'In electrical control panel wiring, what is standard wire color for 24V DC positive?',
                'a' => 'Blue',
                'b' => 'Brown or Red',
                'c' => 'Black',
                'd' => 'Green-Yellow',
                'correct' => 'B'
            ],
            [
                'question' => 'What is the function of a contactor in a motor starter circuit?',
                'a' => 'To step up AC voltage',
                'b' => 'To switch electrical power circuits on and off remotely',
                'c' => 'To measure line frequency',
                'd' => 'To protect only against ground faults',
                'correct' => 'B'
            ],
            [
                'question' => 'What standard industrial protocol operates over RS-485 serial communication?',
                'a' => 'HTTP',
                'b' => 'Modbus RTU',
                'c' => 'Bluetooth Low Energy',
                'd' => 'FTP',
                'correct' => 'B'
            ]
        ];

        $insApt = $pdo->prepare("INSERT INTO aptitude_questions (question, option_a, option_b, option_c, option_d, correct_option, marks) VALUES (?, ?, ?, ?, ?, ?, 1)");
        foreach ($questions as $q) {
            $insApt->execute([$q['question'], $q['a'], $q['b'], $q['c'], $q['d'], $q['correct']]);
        }
        echo "[+] 5 technical aptitude questions seeded.\n";
    }

    echo "[SUCCESS] Seeding completed.\n";

} catch (Exception $e) {
    echo "[ERROR] Seeding error: " . $e->getMessage() . "\n";
}
