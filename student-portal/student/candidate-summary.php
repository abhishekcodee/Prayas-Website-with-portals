<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ============================
   1. Fetch User
============================ */
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

/* ============================
   2. Fetch Student Profile
============================ */
$profileStmt = $pdo->prepare("SELECT * FROM student_profile WHERE user_id=?");
$profileStmt->execute([$user_id]);
$profile = $profileStmt->fetch(PDO::FETCH_ASSOC) ?: [];

/* ============================
   3. Fetch Resume
============================ */
$resumeStmt = $pdo->prepare("SELECT * FROM resume WHERE user_id=?");
$resumeStmt->execute([$user_id]);
$resume = $resumeStmt->fetch(PDO::FETCH_ASSOC) ?: [];

/* ============================
   4. Fetch Education
============================ */
$educationStmt = $pdo->prepare("SELECT * FROM education WHERE user_id=? ORDER BY id ASC");
$educationStmt->execute([$user_id]);
$education = $educationStmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   5. Fetch Experience
============================ */
$experienceStmt = $pdo->prepare("SELECT * FROM experience WHERE user_id=? ORDER BY id ASC");
$experienceStmt->execute([$user_id]);
$experience = $experienceStmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   6. Fetch Skills
============================ */
$skillStmt = $pdo->prepare("SELECT * FROM skills WHERE user_id=? ORDER BY id ASC");
$skillStmt->execute([$user_id]);
$skills = $skillStmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   7. Fetch Certificates
============================ */
$certificateStmt = $pdo->prepare("SELECT * FROM certificates WHERE user_id=? ORDER BY id ASC");
$certificateStmt->execute([$user_id]);
$certificates = $certificateStmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   8. Fetch Hobbies
============================ */
$hobbyStmt = $pdo->prepare("SELECT * FROM hobbies WHERE user_id=?");
$hobbyStmt->execute([$user_id]);
$hobbies = $hobbyStmt->fetch(PDO::FETCH_ASSOC) ?: [];

/* ============================
   9. Fetch Languages
============================ */
$langStmt = $pdo->prepare("
    SELECT l.language_name, sl.can_read, sl.can_write, sl.can_speak
    FROM student_languages sl
    INNER JOIN languages l ON sl.language_id=l.id
    WHERE sl.user_id=?
    ORDER BY l.language_name ASC
");
$langStmt->execute([$user_id]);
$languages = $langStmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   10. Fetch Siblings
============================ */
$siblingStmt = $pdo->prepare("SELECT * FROM student_siblings WHERE user_id=? ORDER BY id ASC");
$siblingStmt->execute([$user_id]);
$siblings = $siblingStmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   Candidate Photo Resolution
============================ */
$candidate_photo = '../assets/images/logo.png';
if (!empty($profile['photo'])) {
    $photo_filename = basename($profile['photo']);
    if (file_exists("../uploads/photos/" . $photo_filename)) {
        $candidate_photo = "../uploads/photos/" . $photo_filename;
    } elseif (file_exists($profile['photo'])) {
        $candidate_photo = $profile['photo'];
    } elseif (file_exists("../" . $profile['photo'])) {
        $candidate_photo = "../" . $profile['photo'];
    }
}
$photo_url = $candidate_photo . (file_exists($candidate_photo) ? '?v=' . filemtime($candidate_photo) : '?v=' . time());

// Calculate Age helper
$age_display = '-';
if (!empty($profile['dob'])) {
    $dobDate = new DateTime($profile['dob']);
    $today = new DateTime();
    $age_display = $dobDate->diff($today)->y . ' Years';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Executive Summary Dossier | PRAYAS Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy-dark: #0F172A;
            --navy-card: #1E293B;
            --coral-accent: #FF5252;
            --coral-dark: #E11D48;
            --emerald-accent: #10B981;
            --blue-accent: #3B82F6;
            --indigo-accent: #6366F1;
            --bg-slate: #F8FAFC;
            --border-line: #E2E8F0;
            --text-heading: #0F172A;
            --text-muted: #64748B;
        }

        body {
            background-color: var(--bg-slate);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--navy-dark);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* Top Sticky Header */
        .summary-navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-line);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
        }

        .summary-navbar-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .summary-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .summary-brand img {
            height: 44px;
            width: auto;
        }

        .summary-brand-text {
            line-height: 1.2;
        }

        .summary-brand-title {
            font-weight: 800;
            font-size: 1rem;
            color: var(--navy-dark);
            display: block;
        }

        .summary-brand-sub {
            font-size: 0.76rem;
            color: var(--coral-accent);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-back-dashboard {
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
            color: #FFFFFF !important;
            padding: 10px 22px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.88rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.15);
            border: none;
        }

        .btn-back-dashboard:hover {
            background: linear-gradient(135deg, #FF5252 0%, #E11D48 100%);
            color: #FFFFFF !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 82, 82, 0.3);
        }

        .btn-print-profile {
            background: #FFFFFF;
            color: #475569;
            border: 1px solid var(--border-line);
            padding: 10px 18px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.88rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-print-profile:hover {
            background: #F1F5F9;
            color: var(--navy-dark);
            border-color: #CBD5E1;
        }

        /* Container */
        .summary-container {
            max-width: 1200px;
            margin: 32px auto 60px;
            padding: 0 24px;
        }

        /* Hero Header Card */
        .hero-dossier-card {
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 65%, #0F172A 100%);
            border-radius: 24px;
            padding: 36px 40px;
            color: #FFFFFF;
            margin-bottom: 28px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .hero-dossier-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 82, 82, 0.2) 0%, transparent 70%);
            pointer-events: none;
        }

        .hero-dossier-flex {
            display: flex;
            align-items: center;
            gap: 28px;
            flex-wrap: wrap;
        }

        .hero-avatar-box {
            position: relative;
            flex-shrink: 0;
        }

        .hero-avatar-img {
            width: 120px;
            height: 120px;
            border-radius: 20px;
            object-fit: cover;
            border: 3px solid var(--coral-accent);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            background: #FFFFFF;
        }

        .hero-verified-badge {
            position: absolute;
            bottom: -6px;
            right: -6px;
            background: #10B981;
            color: #FFFFFF;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 3px solid #1E293B;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .hero-info-col {
            flex-grow: 1;
        }

        .hero-candidate-name {
            font-size: 2rem;
            font-weight: 800;
            margin: 0 0 8px 0;
            letter-spacing: -0.5px;
        }

        .hero-meta-row {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .hero-reg-pill {
            background: rgba(255, 82, 82, 0.18);
            border: 1px solid rgba(255, 82, 82, 0.4);
            color: #FF8A8A;
            font-weight: 800;
            font-size: 0.85rem;
            padding: 4px 14px;
            border-radius: 20px;
            font-family: ui-monospace, SFMono-Regular, monospace;
        }

        .hero-status-pill {
            background: rgba(16, 185, 129, 0.18);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #6EE7B7;
            font-weight: 800;
            font-size: 0.82rem;
            padding: 4px 14px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .hero-contact-chips {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 0.9rem;
            color: #94A3B8;
        }

        .hero-contact-chips span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #E2E8F0;
            font-weight: 600;
        }

        .hero-contact-chips i {
            color: var(--coral-accent);
        }

        /* Section Cards */
        .dossier-card {
            background: #FFFFFF;
            border-radius: 20px;
            border: 1px solid var(--border-line);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
            padding: 28px 32px;
            margin-bottom: 24px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .dossier-card:hover {
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.07);
        }

        .card-header-clean {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #F1F5F9;
            padding-bottom: 16px;
            margin-bottom: 22px;
        }

        .card-header-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--navy-dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-header-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
        }

        .icon-box-coral { background: rgba(255, 82, 82, 0.12); color: var(--coral-accent); }
        .icon-box-blue { background: rgba(59, 130, 246, 0.12); color: var(--blue-accent); }
        .icon-box-emerald { background: rgba(16, 185, 129, 0.12); color: var(--emerald-accent); }
        .icon-box-indigo { background: rgba(99, 102, 241, 0.12); color: var(--indigo-accent); }
        .icon-box-amber { background: rgba(245, 158, 11, 0.12); color: #D97706; }

        /* Data Grids */
        .info-data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .data-item {
            background: #F8FAFC;
            padding: 14px 18px;
            border-radius: 12px;
            border: 1px solid #F1F5F9;
        }

        .data-label {
            font-size: 0.76rem;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            display: block;
        }

        .data-value {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--navy-dark);
            display: block;
            word-break: break-word;
        }

        .data-value-empty {
            color: #94A3B8;
            font-weight: 500;
            font-style: italic;
        }

        /* Styled Tables */
        .dossier-table-wrap {
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid var(--border-line);
        }

        .dossier-table {
            width: 100%;
            margin: 0;
            border-collapse: collapse;
        }

        .dossier-table th {
            background: #F1F5F9;
            color: #334155;
            font-weight: 800;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 18px;
            border-bottom: 1px solid var(--border-line);
        }

        .dossier-table td {
            padding: 14px 18px;
            font-size: 0.92rem;
            color: var(--navy-dark);
            border-bottom: 1px solid #F1F5F9;
            vertical-align: middle;
            font-weight: 600;
        }

        .dossier-table tr:last-child td {
            border-bottom: none;
        }

        .dossier-table tr:nth-child(even) td {
            background: #F8FAFC;
        }

        /* Chips & Badges */
        .badge-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .chip-skill {
            background: #EFF6FF;
            color: #1D4ED8;
            border: 1px solid #BFDBFE;
        }

        .chip-hobby {
            background: #FDF2F8;
            color: #DB2777;
            border: 1px solid #FBCFE8;
        }

        .chip-cert {
            background: #ECFDF5;
            color: #047857;
            border: 1px solid #A7F3D0;
        }

        .chip-lang {
            background: #F5F3FF;
            color: #6D28D9;
            border: 1px solid #DDD6FE;
        }

        /* Timeline Cards */
        .timeline-item {
            background: #F8FAFC;
            border: 1px solid var(--border-line);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            position: relative;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--navy-dark);
            margin: 0 0 4px 0;
        }

        .timeline-sub {
            font-size: 0.88rem;
            color: var(--coral-accent);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .timeline-desc {
            font-size: 0.9rem;
            color: #475569;
            line-height: 1.5;
            margin: 0;
        }

        /* Empty State Warning */
        .empty-section-alert {
            background: #F8FAFC;
            border: 1px dashed #CBD5E1;
            padding: 18px 24px;
            border-radius: 14px;
            color: #64748B;
            font-weight: 600;
            font-size: 0.88rem;
            text-align: center;
        }

        /* Print Styling */
        @media print {
            .summary-navbar, .bottom-footer-bar, .btn-print-profile, .btn-back-dashboard {
                display: none !important;
            }
            body {
                background: #FFFFFF !important;
            }
            .summary-container {
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
            .dossier-card, .hero-dossier-card {
                box-shadow: none !important;
                border: 1px solid #CCCCCC !important;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<!-- Top Navigation Header -->
<header class="summary-navbar">
    <div class="summary-navbar-inner">
        <a href="../dashboard.php" class="summary-brand">
            <img src="../assets/images/prayas-logo.png" alt="PRAYAS Logo">
            <div class="summary-brand-text">
                <span class="summary-brand-title">PRAYAS Student Portal</span>
                <span class="summary-brand-sub">Candidate Executive Summary</span>
            </div>
        </a>

        <div class="summary-nav-actions">
            <button onclick="window.print()" class="btn-print-profile">
                <i class="fa-solid fa-print"></i>
                <span>Print Summary</span>
            </button>
            <a href="../dashboard.php" class="btn-back-dashboard">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </div>
</header>

<main class="summary-container">

    <!-- Hero Executive Profile Card -->
    <div class="hero-dossier-card">
        <div class="hero-dossier-flex">
            <div class="hero-avatar-box">
                <img src="<?php echo htmlspecialchars($photo_url); ?>" alt="Candidate Photo" class="hero-avatar-img">
                <div class="hero-verified-badge" title="Verified Profile">
                    <i class="fa-solid fa-check"></i>
                </div>
            </div>

            <div class="hero-info-col">
                <h1 class="hero-candidate-name"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                
                <div class="hero-meta-row">
                    <span class="hero-reg-pill">REG: <?php echo htmlspecialchars($user['registration_no'] ?? 'PRY-' . str_pad($user_id, 5, '0', STR_PAD_LEFT)); ?></span>
                    <span class="hero-status-pill"><i class="fa-solid fa-circle-check"></i> Verified Profile Dossier</span>
                    <?php if (!empty($profile['domicile_state'])): ?>
                        <span class="badge bg-secondary px-3 py-2 rounded-pill"><i class="fa-solid fa-location-dot me-1"></i> <?php echo htmlspecialchars($profile['domicile_state']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="hero-contact-chips">
                    <span><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></span>
                    <span><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($user['mobile']); ?></span>
                    <?php if (!empty($profile['city']) && !empty($profile['state'])): ?>
                        <span><i class="fa-solid fa-map-pin"></i> <?php echo htmlspecialchars($profile['city'] . ', ' . $profile['state']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid Layout for Personal & Contact Info -->
    <div class="row g-4 mb-4">
        
        <!-- Personal & Demographics -->
        <div class="col-lg-6">
            <div class="dossier-card h-100 mb-0">
                <div class="card-header-clean">
                    <h3 class="card-header-title">
                        <div class="card-header-icon icon-box-coral"><i class="fa-solid fa-user"></i></div>
                        Personal &amp; Demographics
                    </h3>
                </div>

                <div class="info-data-grid">
                    <div class="data-item">
                        <span class="data-label">Full Name</span>
                        <span class="data-value"><?php echo htmlspecialchars($user['full_name']); ?></span>
                    </div>

                    <div class="data-item">
                        <span class="data-label">Date of Birth</span>
                        <span class="data-value"><?php echo !empty($profile['dob']) ? htmlspecialchars(date('d M Y', strtotime($profile['dob']))) : '<span class="data-value-empty">Not Provided</span>'; ?></span>
                    </div>

                    <div class="data-item">
                        <span class="data-label">Age</span>
                        <span class="data-value"><?php echo htmlspecialchars($age_display); ?></span>
                    </div>

                    <div class="data-item">
                        <span class="data-label">Gender</span>
                        <span class="data-value"><?php echo !empty($profile['gender']) ? htmlspecialchars($profile['gender']) : '<span class="data-value-empty">Not Specified</span>'; ?></span>
                    </div>

                    <div class="data-item">
                        <span class="data-label">Blood Group</span>
                        <span class="data-value"><?php echo !empty($profile['blood_group']) ? htmlspecialchars($profile['blood_group']) : '<span class="data-value-empty">Not Specified</span>'; ?></span>
                    </div>

                    <div class="data-item">
                        <span class="data-label">Marital Status</span>
                        <span class="data-value"><?php echo !empty($profile['marital_status']) ? htmlspecialchars($profile['marital_status']) : '<span class="data-value-empty">Not Specified</span>'; ?></span>
                    </div>

                    <div class="data-item">
                        <span class="data-label">Religion</span>
                        <span class="data-value"><?php echo !empty($profile['religion']) ? htmlspecialchars($profile['religion']) : '<span class="data-value-empty">Not Specified</span>'; ?></span>
                    </div>

                    <div class="data-item">
                        <span class="data-label">Category</span>
                        <span class="data-value"><?php echo !empty($profile['category']) ? htmlspecialchars($profile['category']) : '<span class="data-value-empty">General</span>'; ?></span>
                    </div>

                    <div class="data-item">
                        <span class="data-label">Nationality</span>
                        <span class="data-value"><?php echo !empty($profile['nationality']) ? htmlspecialchars($profile['nationality']) : 'Indian'; ?></span>
                    </div>

                    <div class="data-item">
                        <span class="data-label">PwBD Status</span>
                        <span class="data-value"><?php echo !empty($profile['pwbd']) && strtolower($profile['pwbd']) !== 'no' ? 'Yes (' . htmlspecialchars($profile['pwbd_id'] ?? 'Specified') . ')' : 'No'; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact & Address Details -->
        <div class="col-lg-6">
            <div class="dossier-card h-100 mb-0">
                <div class="card-header-clean">
                    <h3 class="card-header-title">
                        <div class="card-header-icon icon-box-blue"><i class="fa-solid fa-address-book"></i></div>
                        Contact &amp; Location Details
                    </h3>
                </div>

                <div class="info-data-grid">
                    <div class="data-item">
                        <span class="data-label">Primary Email</span>
                        <span class="data-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>

                    <div class="data-item">
                        <span class="data-label">Primary Mobile</span>
                        <span class="data-value"><?php echo htmlspecialchars($user['mobile']); ?></span>
                    </div>

                    <div class="data-item">
                        <span class="data-label">Alternate Mobile</span>
                        <span class="data-value"><?php echo !empty($profile['alternate_number']) ? htmlspecialchars($profile['alternate_number']) : '<span class="data-value-empty">Not Provided</span>'; ?></span>
                    </div>

                    <div class="data-item">
                        <span class="data-label">Emergency Contact</span>
                        <span class="data-value"><?php echo !empty($profile['emergency_contact']) ? htmlspecialchars($profile['emergency_contact']) : '<span class="data-value-empty">Not Provided</span>'; ?></span>
                    </div>

                    <div class="data-item style-full-width" style="grid-column: 1 / -1;">
                        <span class="data-label">Present Address</span>
                        <span class="data-value">
                            <?php
                            $addrParts = array_filter([
                                $profile['address'] ?? '',
                                $profile['city'] ?? '',
                                $profile['state'] ?? '',
                                $profile['pincode'] ?? ''
                            ]);
                            echo !empty($addrParts) ? htmlspecialchars(implode(', ', $addrParts)) : '<span class="data-value-empty">Not Provided</span>';
                            ?>
                        </span>
                    </div>

                    <div class="data-item style-full-width" style="grid-column: 1 / -1;">
                        <span class="data-label">Permanent Address</span>
                        <span class="data-value">
                            <?php
                            $permParts = array_filter([
                                $profile['permanent_address'] ?? '',
                                $profile['permanent_city'] ?? '',
                                $profile['permanent_state'] ?? '',
                                $profile['permanent_pincode'] ?? ''
                            ]);
                            echo !empty($permParts) ? htmlspecialchars(implode(', ', $permParts)) : '<span class="data-value-empty">Same as Present Address / Not Specified</span>';
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Government Identification -->
    <div class="dossier-card">
        <div class="card-header-clean">
            <h3 class="card-header-title">
                <div class="card-header-icon icon-box-indigo"><i class="fa-solid fa-id-card"></i></div>
                Government Identification &amp; Credentials
            </h3>
        </div>

        <div class="info-data-grid">
            <div class="data-item">
                <span class="data-label">Aadhaar Card Number</span>
                <span class="data-value"><?php echo !empty($profile['aadhaar_no']) ? htmlspecialchars($profile['aadhaar_no']) : '<span class="data-value-empty">Not Provided</span>'; ?></span>
            </div>

            <div class="data-item">
                <span class="data-label">PAN Card Number</span>
                <span class="data-value"><?php echo !empty($profile['pan_no']) ? htmlspecialchars($profile['pan_no']) : '<span class="data-value-empty">Not Provided</span>'; ?></span>
            </div>

            <div class="data-item">
                <span class="data-label">Passport Number</span>
                <span class="data-value"><?php echo !empty($profile['passport_no']) ? htmlspecialchars($profile['passport_no']) : '<span class="data-value-empty">Not Provided</span>'; ?></span>
            </div>

            <div class="data-item">
                <span class="data-label">Government ID Type</span>
                <span class="data-value"><?php echo !empty($profile['govt_id_type']) ? htmlspecialchars($profile['govt_id_type']) : '<span class="data-value-empty">Not Specified</span>'; ?></span>
            </div>
        </div>
    </div>

    <!-- Academic History / Education -->
    <div class="dossier-card">
        <div class="card-header-clean">
            <h3 class="card-header-title">
                <div class="card-header-icon icon-box-emerald"><i class="fa-solid fa-graduation-cap"></i></div>
                Academic Qualifications &amp; Education
            </h3>
        </div>

        <?php if (!empty($education) && count($education) > 0): ?>
            <div class="dossier-table-wrap">
                <table class="dossier-table">
                    <thead>
                        <tr>
                            <th>Level / Degree</th>
                            <th>Institution / Board</th>
                            <th>Field of Study</th>
                            <th>Passing Year</th>
                            <th>Percentage / CGPA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($education as $edu): ?>
                            <tr>
                                <td><strong class="text-dark"><?php echo htmlspecialchars($edu['qualification_level'] ?? $edu['degree_name'] ?? 'Education'); ?></strong></td>
                                <td><?php echo htmlspecialchars($edu['institution_name'] ?? $edu['board_university'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($edu['field_of_study'] ?? '-'); ?></td>
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($edu['passing_year'] ?? '-'); ?></span></td>
                                <td><strong class="text-success"><?php echo htmlspecialchars($edu['percentage_cgpa'] ?? '-'); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-section-alert">
                <i class="fa-solid fa-folder-open me-2"></i> No formal academic qualifications recorded in candidate profile.
            </div>
        <?php endif; ?>
    </div>

    <!-- Work Experience -->
    <div class="dossier-card">
        <div class="card-header-clean">
            <h3 class="card-header-title">
                <div class="card-header-icon icon-box-amber"><i class="fa-solid fa-briefcase"></i></div>
                Industrial &amp; Work Experience
            </h3>
        </div>

        <?php if (!empty($experience) && count($experience) > 0): ?>
            <?php foreach ($experience as $exp): ?>
                <div class="timeline-item">
                    <h4 class="timeline-title"><?php echo htmlspecialchars($exp['designation'] ?? 'Candidate Role'); ?></h4>
                    <div class="timeline-sub">
                        <i class="fa-solid fa-building me-1"></i> <?php echo htmlspecialchars($exp['company_name'] ?? 'Organization'); ?> 
                        <span class="mx-2">•</span> 
                        <i class="fa-regular fa-calendar-check me-1"></i> <?php echo htmlspecialchars($exp['start_date'] ?? ''); ?> - <?php echo !empty($exp['is_current']) ? 'Present' : htmlspecialchars($exp['end_date'] ?? 'N/A'); ?>
                    </div>
                    <?php if (!empty($exp['job_description'])): ?>
                        <p class="timeline-desc"><?php echo nl2br(htmlspecialchars($exp['job_description'])); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-section-alert">
                <i class="fa-solid fa-user-clock me-2"></i> Industrial experience details not recorded (Fresher Candidate).
            </div>
        <?php endif; ?>
    </div>

    <!-- Skills & Languages Split -->
    <div class="row g-4 mb-4">
        <!-- Technical Competencies -->
        <div class="col-lg-6">
            <div class="dossier-card h-100 mb-0">
                <div class="card-header-clean">
                    <h3 class="card-header-title">
                        <div class="card-header-icon icon-box-coral"><i class="fa-solid fa-gears"></i></div>
                        Technical Competencies &amp; Skills
                    </h3>
                </div>

                <?php if (!empty($skills) && count($skills) > 0): ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($skills as $sk): ?>
                            <span class="badge-chip chip-skill">
                                <i class="fa-solid fa-check-circle"></i>
                                <?php echo htmlspecialchars($sk['skill_name']); ?>
                                <?php if (!empty($sk['skill_level'])): ?>
                                    <small class="text-muted">(<?php echo htmlspecialchars($sk['skill_level']); ?>)</small>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-section-alert">
                        <i class="fa-solid fa-code me-2"></i> No technical skills listed.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Languages Known -->
        <div class="col-lg-6">
            <div class="dossier-card h-100 mb-0">
                <div class="card-header-clean">
                    <h3 class="card-header-title">
                        <div class="card-header-icon icon-box-indigo"><i class="fa-solid fa-language"></i></div>
                        Languages Proficiency
                    </h3>
                </div>

                <?php if (!empty($languages) && count($languages) > 0): ?>
                    <div class="dossier-table-wrap">
                        <table class="dossier-table">
                            <thead>
                                <tr>
                                    <th>Language</th>
                                    <th class="text-center">Read</th>
                                    <th class="text-center">Write</th>
                                    <th class="text-center">Speak</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($languages as $lg): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($lg['language_name']); ?></strong></td>
                                        <td class="text-center"><?php echo !empty($lg['can_read']) ? '<i class="fa-solid fa-circle-check text-success"></i>' : '<i class="fa-solid fa-minus text-muted"></i>'; ?></td>
                                        <td class="text-center"><?php echo !empty($lg['can_write']) ? '<i class="fa-solid fa-circle-check text-success"></i>' : '<i class="fa-solid fa-minus text-muted"></i>'; ?></td>
                                        <td class="text-center"><?php echo !empty($lg['can_speak']) ? '<i class="fa-solid fa-circle-check text-success"></i>' : '<i class="fa-solid fa-minus text-muted"></i>'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-section-alert">
                        <i class="fa-solid fa-comment-slash me-2"></i> No languages specified.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Certificates & Hobbies Split -->
    <div class="row g-4 mb-4">
        <!-- Professional Certifications -->
        <div class="col-lg-6">
            <div class="dossier-card h-100 mb-0">
                <div class="card-header-clean">
                    <h3 class="card-header-title">
                        <div class="card-header-icon icon-box-emerald"><i class="fa-solid fa-certificate"></i></div>
                        Certifications &amp; Credentials
                    </h3>
                </div>

                <?php if (!empty($certificates) && count($certificates) > 0): ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($certificates as $cert): ?>
                            <div class="badge-chip chip-cert w-100 justify-content-between p-3 mb-2 rounded-3">
                                <div>
                                    <strong class="d-block text-dark"><?php echo htmlspecialchars($cert['certificate_name']); ?></strong>
                                    <small class="text-muted"><?php echo htmlspecialchars($cert['issuing_organization'] ?? 'Issuing Body'); ?></small>
                                </div>
                                <?php if (!empty($cert['issue_date'])): ?>
                                    <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($cert['issue_date']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-section-alert">
                        <i class="fa-solid fa-award me-2"></i> No certifications added.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Hobbies & Interests -->
        <div class="col-lg-6">
            <div class="dossier-card h-100 mb-0">
                <div class="card-header-clean">
                    <h3 class="card-header-title">
                        <div class="card-header-icon icon-box-blue"><i class="fa-solid fa-icons"></i></div>
                        Hobbies &amp; Interests
                    </h3>
                </div>

                <?php 
                $hobby_text = $hobbies['hobby_names'] ?? $hobbies['hobbies_list'] ?? '';
                if (!empty($hobby_text)): 
                    $hobby_list = array_filter(array_map('trim', explode(',', $hobby_text)));
                ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($hobby_list as $hb): ?>
                            <span class="badge-chip chip-hobby">
                                <i class="fa-solid fa-heart"></i> <?php echo htmlspecialchars($hb); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-section-alert">
                        <i class="fa-solid fa-face-smile me-2"></i> No hobbies listed.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Family Details Card -->
    <div class="dossier-card">
        <div class="card-header-clean">
            <h3 class="card-header-title">
                <div class="card-header-icon icon-box-amber"><i class="fa-solid fa-people-roof"></i></div>
                Family Background &amp; Dependents
            </h3>
        </div>

        <div class="row g-4 mb-4">
            <!-- Father -->
            <div class="col-md-6">
                <div class="data-item">
                    <span class="data-label" style="color: var(--coral-accent);">Father Details</span>
                    <span class="data-value mb-1"><?php echo !empty($profile['father_name']) ? htmlspecialchars($profile['father_name']) : '<span class="data-value-empty">Not Provided</span>'; ?></span>
                    <small class="text-muted d-block">
                        Qualification: <?php echo htmlspecialchars($profile['father_qualification'] ?? '-'); ?> | 
                        Occupation: <?php echo htmlspecialchars($profile['father_occupation'] ?? '-'); ?>
                    </small>
                </div>
            </div>

            <!-- Mother -->
            <div class="col-md-6">
                <div class="data-item">
                    <span class="data-label" style="color: var(--emerald-accent);">Mother Details</span>
                    <span class="data-value mb-1"><?php echo !empty($profile['mother_name']) ? htmlspecialchars($profile['mother_name']) : '<span class="data-value-empty">Not Provided</span>'; ?></span>
                    <small class="text-muted d-block">
                        Qualification: <?php echo htmlspecialchars($profile['mother_qualification'] ?? '-'); ?> | 
                        Occupation: <?php echo htmlspecialchars($profile['mother_occupation'] ?? '-'); ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Siblings Table -->
        <?php if (!empty($siblings) && count($siblings) > 0): ?>
            <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-users me-2 text-primary"></i> Brother / Sister Information</h6>
            <div class="dossier-table-wrap">
                <table class="dossier-table">
                    <thead>
                        <tr>
                            <th>Relation</th>
                            <th>Full Name</th>
                            <th>Age</th>
                            <th>Qualification</th>
                            <th>Occupation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($siblings as $sib): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($sib['relation']); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($sib['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($sib['age'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($sib['qualification'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($sib['occupation'] ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Official Resume & Summary Card -->
    <div class="dossier-card">
        <div class="card-header-clean">
            <h3 class="card-header-title">
                <div class="card-header-icon icon-box-coral"><i class="fa-solid fa-file-pdf"></i></div>
                Official Industrial Resume &amp; Summary
            </h3>
        </div>

        <?php if (!empty($resume['summary']) || !empty($resume['objective']) || !empty($resume['career_objective'])): ?>
            <div class="mb-4">
                <span class="data-label">Career Objective / Profile Overview</span>
                <p class="data-value font-monospace fw-normal text-muted mt-1 p-3 bg-light rounded-3 border">
                    <?php echo nl2br(htmlspecialchars($resume['summary'] ?? $resume['objective'] ?? $resume['career_objective'] ?? '')); ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if (!empty($resume['resume_file'])): ?>
            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 border">
                <div class="d-flex align-items-center gap-3">
                    <i class="fa-solid fa-file-pdf text-danger fs-2"></i>
                    <div>
                        <strong class="d-block text-dark"><?php echo htmlspecialchars(basename($resume['resume_file'])); ?></strong>
                        <small class="text-muted">Attached Candidate Resume File</small>
                    </div>
                </div>
                <a href="../uploads/resumes/<?php echo htmlspecialchars(basename($resume['resume_file'])); ?>" target="_blank" class="btn btn-outline-primary btn-sm px-3 fw-bold">
                    <i class="fa-solid fa-download me-1"></i> View / Download
                </a>
            </div>
        <?php else: ?>
            <div class="empty-section-alert">
                <i class="fa-solid fa-file-arrow-up me-2"></i> No attached PDF resume document found in records.
            </div>
        <?php endif; ?>
    </div>

    <!-- Bottom Action Footer Bar -->
    <div class="text-center mt-5">
        <a href="../dashboard.php" class="btn-back-dashboard py-3 px-5 fs-6 shadow-lg">
            <i class="fa-solid fa-arrow-left me-2"></i> Return to Executive Dashboard
        </a>
    </div>

</main>

</body>
</html>