<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Fetch candidate profile details
$profileStmt = $pdo->prepare("SELECT * FROM student_profile WHERE user_id=?");
$profileStmt->execute([$user_id]);
$profile = $profileStmt->fetch(PDO::FETCH_ASSOC);

// Fetch resume details
$resumeStmt = $pdo->prepare("SELECT * FROM resume WHERE user_id=?");
$resumeStmt->execute([$user_id]);
$resume = $resumeStmt->fetch(PDO::FETCH_ASSOC);

// Candidate photo fallback
$candidate_photo = 'assets/images/logo.png';
if (!empty($profile['photo'])) {
    $photo_filename = basename($profile['photo']);
    if (file_exists("uploads/photos/" . $photo_filename)) {
        $candidate_photo = "uploads/photos/" . $photo_filename;
    } elseif (file_exists($profile['photo'])) {
        $candidate_photo = $profile['photo'];
    } elseif (file_exists("student/" . $profile['photo'])) {
        $candidate_photo = "student/" . $profile['photo'];
    }
}

// Cache-busting parameter for browser display
$candidate_photo_url = $candidate_photo;
if ($candidate_photo !== 'assets/images/logo.png' && file_exists($candidate_photo)) {
    $candidate_photo_url .= '?v=' . filemtime($candidate_photo);
} else {
    $candidate_photo_url .= '?v=' . time();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Executive Dashboard | PRAYAS Student Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        :root {
            --navy-dark: #0F172A;
            --navy-card: #1E293B;
            --coral-accent: #FF5252;
            --coral-dark: #E11D48;
            --coral-light: #FF7878;
            --emerald-accent: #10B981;
            --blue-accent: #3B82F6;
            --indigo-accent: #6366F1;
            --amber-accent: #F59E0B;
            --bg-slate: #F8FAFC;
            --border-line: #E2E8F0;
            --text-heading: #0F172A;
            --text-muted: #64748B;
            --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            background-color: #F8FAFC;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--navy-dark);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* Animations */
        @keyframes fadeSlideUp {
            from {
                opacity: 0;
                transform: translateY(24px) scale(0.985);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        @keyframes waveHand {
            0%, 100% { transform: rotate(0deg); }
            20%, 60% { transform: rotate(14deg); }
            40%, 80% { transform: rotate(-10deg); }
        }

        /* Top Header Navbar */
        .dash-navbar {
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-line);
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .dash-navbar-inner {
            max-width: 1320px;
            margin: 0 auto;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .dash-logo-box {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .dash-logo-img {
            height: 52px;
            max-height: 52px;
            width: auto;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .dash-logo-img:hover {
            transform: scale(1.05) rotate(-1deg);
        }

        .dash-portal-tag {
            background: rgba(255, 82, 82, 0.12);
            color: var(--coral-accent);
            font-weight: 800;
            font-size: 0.72rem;
            padding: 5px 11px;
            border-radius: 8px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            border: 1px solid rgba(255, 82, 82, 0.2);
        }

        .dash-nav-links {
            display: flex;
            align-items: center;
            gap: 22px;
            white-space: nowrap;
        }

        .dash-nav-links a {
            color: #475569;
            font-weight: 700;
            text-decoration: none;
            font-size: 0.9rem;
            padding: 6px 12px;
            border-radius: 8px;
            transition: all 0.25s ease;
        }

        .dash-nav-links a:hover {
            color: var(--coral-accent);
            background: rgba(255, 82, 82, 0.06);
        }

        .dash-header-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .dash-user-pill {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--bg-slate);
            padding: 6px 14px;
            border-radius: 40px;
            border: 1px solid var(--border-line);
            transition: border-color 0.25s ease;
        }

        .dash-user-pill:hover {
            border-color: rgba(255, 82, 82, 0.3);
        }

        .dash-avatar-wrapper {
            position: relative;
        }

        .dash-avatar-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--coral-accent);
            box-shadow: 0 4px 12px rgba(255, 82, 82, 0.25);
            display: block;
        }

        .dash-online-dot {
            width: 10px;
            height: 10px;
            background: #10B981;
            border: 2px solid white;
            border-radius: 50%;
            position: absolute;
            bottom: 0;
            right: 0;
            animation: pulseDot 2s infinite ease-in-out;
        }

        .dash-user-meta {
            text-align: right;
            line-height: 1.25;
        }

        .dash-user-name {
            font-weight: 800;
            font-size: 0.88rem;
            color: var(--navy-dark);
            display: block;
        }

        .dash-user-reg {
            font-size: 0.76rem;
            color: var(--coral-accent);
            font-weight: 700;
            font-family: ui-monospace, SFMono-Regular, monospace;
        }

        .btn-dash-logout {
            background: #FFF1F1;
            color: #E11D48;
            border: 1px solid #FCD3D3;
            padding: 9px 18px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.25s var(--ease-out);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-dash-logout:hover {
            background: var(--coral-accent);
            color: #FFFFFF;
            border-color: var(--coral-accent);
            box-shadow: 0 6px 18px rgba(255, 82, 82, 0.35);
            transform: translateY(-1px);
        }

        /* Dashboard Container & Staggered Entrance */
        .dash-main {
            max-width: 1320px;
            margin: 32px auto 60px;
            padding: 0 24px;
        }

        .hero-banner { animation: fadeSlideUp 0.6s var(--ease-out) forwards; }
        .modules-grid { animation: fadeSlideUp 0.6s var(--ease-out) 0.15s both; }
        .bottom-split-grid { animation: fadeSlideUp 0.6s var(--ease-out) 0.3s both; }

        /* Hero Executive Welcome Banner */
        .hero-banner {
            background:
                radial-gradient(circle at 90% 10%, rgba(255, 82, 82, 0.25) 0%, transparent 45%),
                radial-gradient(circle at 10% 90%, rgba(59, 130, 246, 0.2) 0%, transparent 45%),
                linear-gradient(135deg, #0F172A 0%, #1E293B 60%, #0F172A 100%);
            border-radius: 24px;
            padding: 40px 48px;
            color: #FFFFFF;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.16);
            margin-bottom: 36px;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .hero-content-split {
            display: grid;
            grid-template-columns: 1.25fr 0.75fr;
            gap: 36px;
            align-items: center;
            margin-bottom: 36px;
        }

        @media (max-width: 992px) {
            .hero-content-split {
                grid-template-columns: 1fr;
            }
        }

        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 82, 82, 0.15);
            border: 1px solid rgba(255, 82, 82, 0.35);
            color: #FF8A8A;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 6px 14px;
            border-radius: 30px;
            margin-bottom: 20px;
        }

        .hero-banner h1 {
            font-size: 2.3rem;
            font-weight: 800;
            margin: 0 0 14px 0;
            line-height: 1.25;
            letter-spacing: -0.5px;
        }

        .hero-name-highlight {
            background: linear-gradient(135deg, #FFFFFF 0%, #FF8A8A 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-banner h1 .wave-emoji {
            display: inline-block;
            transition: transform 0.3s ease;
        }

        .hero-banner h1:hover .wave-emoji {
            animation: waveHand 0.8s ease-in-out infinite;
        }

        .hero-banner p {
            font-size: 1.02rem;
            color: #94A3B8;
            margin: 0 0 28px 0;
            line-height: 1.65;
        }

        .hero-cta-group {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-hero-primary {
            background: linear-gradient(135deg, #FF5252 0%, #E11D48 100%);
            color: #FFFFFF;
            padding: 13px 26px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 24px rgba(255, 82, 82, 0.4);
            transition: all 0.3s var(--ease-out);
        }

        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 32px rgba(255, 82, 82, 0.55);
            color: #FFFFFF;
        }

        .btn-hero-secondary {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #FFFFFF;
            padding: 13px 24px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s var(--ease-out);
        }

        .btn-hero-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-3px);
            color: #FFFFFF;
        }

        /* Candidate Digital Executive Pass Card */
        .candidate-pass-card {
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 22px;
            padding: 24px;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.25);
            transition: all 0.35s var(--ease-out);
            position: relative;
            overflow: hidden;
        }

        .candidate-pass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #FF5252, #10B981, #3B82F6);
        }

        .candidate-pass-card:hover {
            transform: translateY(-5px) scale(1.01);
            border-color: rgba(255, 82, 82, 0.4);
            box-shadow: 0 22px 48px rgba(0, 0, 0, 0.35);
        }

        .pass-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .pass-brand-tag {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.75);
            text-transform: uppercase;
        }

        .pass-status-pill {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #34D399;
            padding: 4px 11px;
            border-radius: 20px;
            font-size: 0.74rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .pass-card-body {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 20px;
        }

        .pass-avatar-box {
            position: relative;
            min-width: 64px;
        }

        .pass-avatar-img {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            object-fit: cover;
            border: 2px solid var(--coral-accent);
            box-shadow: 0 6px 16px rgba(255, 82, 82, 0.3);
        }

        .pass-verified-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #10B981;
            color: white;
            font-size: 0.65rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            bottom: -4px;
            right: -4px;
            border: 2px solid #0F172A;
        }

        .pass-user-info {
            flex-grow: 1;
        }

        .pass-user-name {
            font-size: 1.1rem;
            font-weight: 800;
            color: #FFFFFF;
            margin: 0 0 3px 0;
            line-height: 1.25;
        }

        .pass-user-role {
            font-size: 0.78rem;
            color: #94A3B8;
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .pass-reg-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(0, 0, 0, 0.3);
            padding: 4px 10px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .pass-reg-badge label {
            font-size: 0.68rem;
            font-weight: 800;
            color: #94A3B8;
        }

        .pass-reg-badge code {
            font-family: ui-monospace, SFMono-Regular, monospace;
            font-size: 0.8rem;
            font-weight: 800;
            color: #FF8A8A;
        }

        .pass-readiness-bar {
            background: rgba(0, 0, 0, 0.25);
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .readiness-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.76rem;
            margin-bottom: 6px;
        }

        .readiness-meta span {
            color: #94A3B8;
            font-weight: 600;
        }

        .readiness-meta strong {
            color: #34D399;
            font-weight: 800;
        }

        .readiness-track {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            overflow: hidden;
        }

        .readiness-fill {
            height: 100%;
            background: linear-gradient(90deg, #10B981, #34D399);
            border-radius: 10px;
        }

        /* Metric Grid inside Hero */
        .hero-metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            position: relative;
            z-index: 2;
        }

        .hero-metric-card {
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.14);
            padding: 18px 22px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s var(--ease-out);
        }

        .hero-metric-card:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 82, 82, 0.5);
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.2);
        }

        .metric-icon-box {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #FFFFFF;
        }

        .icon-coral {
            background: linear-gradient(135deg, #FF5252 0%, #E11D48 100%);
            box-shadow: 0 6px 16px rgba(255, 82, 82, 0.4);
        }

        .icon-emerald {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }

        .icon-blue {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
        }

        .icon-amber {
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            box-shadow: 0 6px 16px rgba(245, 158, 11, 0.4);
        }

        .metric-info-label {
            font-size: 0.74rem;
            color: #94A3B8;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.8px;
            display: block;
            margin-bottom: 2px;
        }

        .metric-info-val {
            font-size: 1.05rem;
            font-weight: 800;
            color: #FFFFFF;
        }

        /* Section Headings */
        .grid-section-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--navy-dark);
            margin: 0 0 24px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.3px;
        }

        .title-icon-tag {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(255, 82, 82, 0.12);
            color: var(--coral-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        /* 4 Main Action Cards Grid */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .module-card {
            background: #FFFFFF;
            border-radius: 20px;
            padding: 32px 28px 24px;
            border: 1px solid var(--border-line);
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
            transition: all 0.35s var(--ease-out);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .module-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: transparent;
            transition: all 0.3s ease;
        }

        .module-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.09);
            border-color: rgba(255, 82, 82, 0.35);
        }

        .module-card:hover::before {
            background: linear-gradient(90deg, #FF5252, #FF7878);
        }

        .module-icon-wrap {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 22px;
            color: #FFFFFF;
            transition: transform 0.3s ease;
        }

        .module-card:hover .module-icon-wrap {
            transform: scale(1.08) rotate(-3deg);
        }

        .icon-wrap-indigo {
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.35);
        }

        .icon-wrap-emerald {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.35);
        }

        .icon-wrap-coral {
            background: linear-gradient(135deg, #FF5252 0%, #E11D48 100%);
            box-shadow: 0 8px 20px rgba(255, 82, 82, 0.35);
        }

        .icon-wrap-blue {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.35);
        }

        .module-card h3 {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--navy-dark);
            margin: 0 0 10px 0;
            letter-spacing: -0.2px;
        }

        .module-card p {
            font-size: 0.92rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin: 0 0 28px 0;
            font-weight: 500;
        }

        .btn-module-link {
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            background: #F8FAFC;
            color: var(--navy-dark);
            padding: 14px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            border: 1px solid var(--border-line);
            transition: all 0.3s ease;
        }

        .btn-module-link i {
            transition: transform 0.3s ease;
        }

        .module-card:hover .btn-module-link {
            background: var(--coral-accent);
            color: #FFFFFF;
            border-color: var(--coral-accent);
            box-shadow: 0 8px 20px rgba(255, 82, 82, 0.35);
        }

        .module-card:hover .btn-module-link i {
            transform: translateX(4px);
        }

        /* Split Section: Hardware Competencies & Quick Info */
        .bottom-split-grid {
            display: grid;
            grid-template-columns: 1.25fr 0.75fr;
            gap: 28px;
        }

        @media (max-width: 992px) {
            .bottom-split-grid {
                grid-template-columns: 1fr;
            }

            .dash-nav-links {
                display: none;
            }
        }

        .panel-box {
            background: #FFFFFF;
            border-radius: 20px;
            padding: 32px;
            border: 1px solid var(--border-line);
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
            transition: box-shadow 0.3s ease;
        }

        .panel-box:hover {
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.07);
        }

        .panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-line);
        }

        .panel-head h3 {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--navy-dark);
            margin: 0;
            display: flex;
            align-items: center;
        }

        /* Hardware Module Checklist */
        .competency-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }

        .competency-item {
            background: var(--bg-slate);
            border: 1px solid var(--border-line);
            padding: 16px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.25s ease;
        }

        .competency-item:hover {
            background: #FFFFFF;
            border-color: var(--coral-accent);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
        }

        .competency-badge {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 10px;
            background: #DCFCE7;
            color: #15803D;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 800;
        }

        .competency-info label {
            font-size: 0.74rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.5px;
            display: block;
        }

        .competency-info span {
            font-size: 0.92rem;
            font-weight: 800;
            color: var(--navy-dark);
        }

        /* Profile Quick Summary Grid */
        .profile-quick-grid {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .info-pill-item {
            background: var(--bg-slate);
            padding: 14px 18px;
            border-radius: 12px;
            border: 1px solid var(--border-line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background 0.2s ease;
        }

        .info-pill-item:hover {
            background: #F1F5F9;
        }

        .info-pill-item label {
            font-size: 0.82rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .info-pill-item span {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--navy-dark);
        }

        .placement-badge-online {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #DCFCE7;
            color: #15803D;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 800;
        }
    </style>
</head>
<body>

<!-- Header Navbar (Matches Official Website Header) -->
<?php require_once __DIR__ . '/includes/nav_header.php'; ?>

<!-- Main Content Area -->
<main class="dash-main">

    <!-- Hero Executive Welcome Banner -->
    <div class="hero-banner">
        <div class="hero-content-split">
            <!-- Left Column: Welcome Text & CTA Buttons -->
            <div class="hero-text-col">
                <div class="hero-pill">
                    <i class="fa-solid fa-circle-check"></i> Registration Verified &amp; Active
                </div>
                <h1>Welcome Back, <span class="hero-name-highlight"><?php echo htmlspecialchars($user['full_name']); ?></span> <span class="wave-emoji">👋</span></h1>
                <p>Your industrial automation candidate profile is verified in the PRAYAS Student Recruitment Portal. Manage your profile, build your official industrial resume, and view hardware competencies below.</p>
                
                <div class="hero-cta-group">
                    <a href="student/resume.php" class="btn-hero-primary">
                        <span>Open Resume Builder</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                    <a href="student/candidate-summary.php?from=dashboard" class="btn-hero-secondary">
                        <i class="fa-solid fa-address-card"></i>
                        <span>View Profile Summary</span>
                    </a>
                </div>
            </div>

            <!-- Right Column: Digital Candidate Executive Pass Card -->
            <div class="hero-badge-col">
                <div class="candidate-pass-card">
                    <div class="pass-card-header">
                        <span class="pass-brand-tag">PRAYAS ACADEMY</span>
                        <span class="pass-status-pill"><span class="dash-online-dot" style="position:static;display:inline-block;"></span> Active</span>
                    </div>

                    <div class="pass-card-body">
                        <div class="pass-avatar-box">
                            <img src="<?php echo htmlspecialchars($candidate_photo_url); ?>" alt="Candidate Photo" class="pass-avatar-img">
                            <div class="pass-verified-icon"><i class="fa-solid fa-check"></i></div>
                        </div>
                        <div class="pass-user-info">
                            <h4 class="pass-user-name"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                            <span class="pass-user-role">Industrial Automation Candidate</span>
                            <div class="pass-reg-badge">
                                <label>REG ID</label>
                                <code><?php echo htmlspecialchars($user['registration_no']); ?></code>
                            </div>
                        </div>
                    </div>

                    <div class="pass-card-footer">
                        <div class="pass-readiness-bar">
                            <div class="readiness-meta">
                                <span>Readiness Score</span>
                                <strong>100% Day-One Ready</strong>
                            </div>
                            <div class="readiness-track">
                                <div class="readiness-fill" style="width: 100%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Integrated Metrics Grid -->
        <div class="hero-metrics-grid">
            <div class="hero-metric-card">
                <div class="metric-icon-box icon-coral"><i class="fa-solid fa-id-card"></i></div>
                <div>
                    <span class="metric-info-label">Candidate ID</span>
                    <span class="metric-info-val"><?php echo htmlspecialchars($user['registration_no']); ?></span>
                </div>
            </div>

            <div class="hero-metric-card">
                <div class="metric-icon-box icon-emerald"><i class="fa-solid fa-user-check"></i></div>
                <div>
                    <span class="metric-info-label">Account Status</span>
                    <span class="metric-info-val"><?php echo htmlspecialchars($user['status']); ?></span>
                </div>
            </div>

            <div class="hero-metric-card">
                <div class="metric-icon-box icon-blue"><i class="fa-solid fa-microchip"></i></div>
                <div>
                    <span class="metric-info-label">Readiness Level</span>
                    <span class="metric-info-val">Day-One Ready</span>
                </div>
            </div>

            <div class="hero-metric-card">
                <div class="metric-icon-box icon-amber"><i class="fa-solid fa-industry"></i></div>
                <div>
                    <span class="metric-info-label">Target Industry</span>
                    <span class="metric-info-val">MSME &amp; Automation</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 Main Portal Action Modules -->
    <h2 class="grid-section-title">
        <span class="title-icon-tag"><i class="fa-solid fa-cubes"></i></span>
        Candidate Portal Modules
    </h2>

    <div class="modules-grid">

        <!-- Module 1: Candidate Summary -->
        <div class="module-card">
            <div>
                <div class="module-icon-wrap icon-wrap-indigo">
                    <i class="fa-solid fa-address-card"></i>
                </div>
                <h3>Candidate Summary</h3>
                <p>View, update, and manage your complete candidate personal details, academic history, and photo verification.</p>
            </div>
            <a href="student/candidate-summary.php?from=dashboard" class="btn-module-link">
                <span>View Full Summary</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <!-- Module 2: Resume Builder -->
        <div class="module-card">
            <div>
                <div class="module-icon-wrap icon-wrap-emerald">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <h3>Resume Builder</h3>
                <p>Create and customize your official industrial resume formatted to PRAYAS Academy hardware standards.</p>
            </div>
            <a href="student/resume.php" class="btn-module-link">
                <span>Open Resume Builder</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <!-- Module 3: Face & Photo Verification -->
        <div class="module-card">
            <div>
                <div class="module-icon-wrap icon-wrap-coral">
                    <i class="fa-solid fa-camera"></i>
                </div>
                <h3>Photo Verification</h3>
                <p>Verify or capture your official passport photo for plant placement credentials and corporate records.</p>
            </div>
            <a href="student/camera.php?from=dashboard" class="btn-module-link">
                <span>Manage Candidate Photo</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <!-- Module 4: Aptitude & Control Exam -->
        <div class="module-card">
            <div>
                <div class="module-icon-wrap icon-wrap-blue">
                    <i class="fa-solid fa-brain"></i>
                </div>
                <h3>Aptitude &amp; Technical Portal</h3>
                <p>Complete or review your 415V control schematics, PLC logic, and diagnostic root-cause assessment test.</p>
            </div>
            <a href="aptitude/start-exam.php" class="btn-module-link">
                <span>Start Aptitude Exam</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

    </div>

    <!-- Bottom Split Section: Hardware Competencies & Profile Summary -->
    <div class="bottom-split-grid">
        
        <!-- Hardware Competencies -->
        <div class="panel-box">
            <div class="panel-head">
                <h3><i class="fa-solid fa-gears" style="color: var(--coral-accent); margin-right: 10px;"></i> Practical Hardware Competencies</h3>
                <span style="font-size: 0.8rem; font-weight: 800; color: #15803D; background: #DCFCE7; padding: 5px 12px; border-radius: 20px;">100% Practical Exposure</span>
            </div>

            <div class="competency-list">
                <div class="competency-item">
                    <div class="competency-badge"><i class="fa-solid fa-check"></i></div>
                    <div class="competency-info">
                        <label>MODULE 01</label>
                        <span>415V Control Panel Wiring</span>
                    </div>
                </div>

                <div class="competency-item">
                    <div class="competency-badge"><i class="fa-solid fa-check"></i></div>
                    <div class="competency-info">
                        <label>MODULE 02</label>
                        <span>PLC Programming &amp; Logic</span>
                    </div>
                </div>

                <div class="competency-item">
                    <div class="competency-badge"><i class="fa-solid fa-check"></i></div>
                    <div class="competency-info">
                        <label>MODULE 03</label>
                        <span>VFD &amp; Motor Speed Control</span>
                    </div>
                </div>

                <div class="competency-item">
                    <div class="competency-badge"><i class="fa-solid fa-check"></i></div>
                    <div class="competency-info">
                        <label>MODULE 04</label>
                        <span>Pneumatic Actuator Systems</span>
                    </div>
                </div>

                <div class="competency-item">
                    <div class="competency-badge"><i class="fa-solid fa-check"></i></div>
                    <div class="competency-info">
                        <label>MODULE 05</label>
                        <span>LOTO &amp; Workplace Safety</span>
                    </div>
                </div>

                <div class="competency-item">
                    <div class="competency-badge"><i class="fa-solid fa-check"></i></div>
                    <div class="competency-info">
                        <label>MODULE 06</label>
                        <span>Fault Isolation &amp; Diagnostics</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Profile Card -->
        <div class="panel-box">
            <div class="panel-head">
                <h3><i class="fa-solid fa-user-gear" style="color: var(--blue-accent); margin-right: 10px;"></i> Account Metadata</h3>
                <a href="student/candidate-summary.php?from=dashboard" style="color: var(--coral-accent); font-size: 0.85rem; font-weight: 700; text-decoration: none;">View Summary <i class="fa-solid fa-arrow-right" style="font-size: 0.75rem;"></i></a>
            </div>

            <div class="profile-quick-grid">
                <div class="info-pill-item">
                    <label>Full Name</label>
                    <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                </div>

                <div class="info-pill-item">
                    <label>Registered Email</label>
                    <span style="font-size: 0.85rem; font-family: monospace;"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>

                <div class="info-pill-item">
                    <label>Mobile Number</label>
                    <span><?php echo htmlspecialchars($user['mobile']); ?></span>
                </div>

                <div class="info-pill-item">
                    <label>Registration ID</label>
                    <span style="color: var(--coral-accent); font-family: monospace; font-weight: 800;"><?php echo htmlspecialchars($user['registration_no']); ?></span>
                </div>

                <div class="info-pill-item">
                    <label>Placement Status</label>
                    <span class="placement-badge-online">
                        <span class="dash-online-dot" style="position: static; display: inline-block;"></span>
                        Verified Candidate
                    </span>
                </div>
            </div>
        </div>

    </div>

</main>



<script>
    localStorage.setItem('prayas_student_user', JSON.stringify({
        loggedIn: true,
        name: <?php echo json_encode($user['full_name']); ?>,
        regNo: <?php echo json_encode($user['registration_no']); ?>,
        photo: <?php echo json_encode($candidate_photo === 'assets/images/logo.png' ? '' : 'student-portal/' . $candidate_photo_url); ?>
    }));
</script>
<script src="../js/app.js"></script>
</body>
</html>