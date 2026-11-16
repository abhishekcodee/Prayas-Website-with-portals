<?php
/**
 * Shared Alerts & Flash Messaging Helper
 * Organization: PRAYAS – The Automation Academy
 */

$flashSuccess = $_SESSION['flash_success'] ?? $_GET['success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? $_GET['error'] ?? null;
$flashWarning = $_SESSION['flash_warning'] ?? $_GET['warning'] ?? null;
$flashInfo    = $_SESSION['flash_info'] ?? $_GET['info'] ?? null;

unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['flash_warning'], $_SESSION['flash_info']);
?>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-check-circle-fill fs-5 me-2"></i>
        <div><?= e($flashSuccess) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
        <div><?= e($flashError) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($flashWarning): ?>
    <div class="alert alert-warning alert-dismissible fade show shadow-sm d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-shield-exclamation fs-5 me-2"></i>
        <div><?= e($flashWarning) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($flashInfo): ?>
    <div class="alert alert-info alert-dismissible fade show shadow-sm d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-info-circle-fill fs-5 me-2"></i>
        <div><?= e($flashInfo) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

