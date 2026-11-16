<?php
/**
 * Shared Header Partial — Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/auth_guard.php';

$pageTitle = $pageTitle ?? 'Portal — PRAYAS The Automation Academy';
$user = getAuthUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" type="image/png" href="<?= app_url('/assets/images/favicon.png') ?>">
    <!-- Google Font: Urbanist -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- PRAYAS Design System & Enterprise Dashboard CSS -->
    <link rel="stylesheet" href="<?= app_url('/assets/css/dashboard.css') ?>?v=2.0.0">
</head>
<body>
<div class="app-layout" id="appLayout">
