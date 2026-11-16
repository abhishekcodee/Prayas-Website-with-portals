<?php
/**
 * Formal Offer Letter Dossier & Printable View (Recruiter & Administration)
 * Organization: PRAYAS – The Automation Academy
 * Module: Part 10 Recruitment Pipeline Management System
 */

declare(strict_types=1);

$pageTitle = 'Formal Offer Letter Dossier — PRAYAS Recruitment Desk';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../controllers/PipelineController.php';

requireRole(['recruiter', 'recruitment_admin', 'super_admin']);

$pdo = Database::getConnection();
$pipelineCtrl = new PipelineController($pdo);

$offerId = !empty($_GET['offer_id']) ? (int)$_GET['offer_id'] : 0;
if ($offerId <= 0) {
    echo "<div class='container p-5 text-center'><h4>Error: Offer letter ID is missing or invalid.</h4></div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$offer = $pipelineCtrl->getOfferDetails($offerId);
if (!$offer) {
    echo "<div class='container p-5 text-center'><h4>Error: Offer letter record not found.</h4></div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}
?>

<style>
@media print {
    #sidebar, #navbar, .no-print, header, footer {
        display: none !important;
    }
    body, #main-content, .page-container {
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
    }
    .offer-paper {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
}
.offer-paper {
    background: #ffffff;
    max-width: 820px;
    margin: 20px auto;
    padding: 50px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    font-family: 'Urbanist', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: #1e293b;
    line-height: 1.6;
}
.offer-header-border {
    border-bottom: 3px solid #EF6B6B;
    padding-bottom: 20px;
    margin-bottom: 30px;
}
.term-number {
    font-weight: 700;
    color: #0F172A;
    min-width: 25px;
}
</style>

<div id="main-content" class="flex-grow-1">
    <div class="no-print bg-light border-bottom p-3">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <a href="<?= app_url('/views/recruiter/recruitment_pipeline.php') ?>" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="bi bi-arrow-left me-1"></i>Back to Pipeline
                </a>
                <span class="fw-bold text-dark">Ref: <?= htmlspecialchars($offer['letter_reference_no']) ?></span>
                <span class="badge bg-success ms-2"><?= htmlspecialchars($offer['status']) ?></span>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-primary btn-sm shadow-sm">
                    <i class="bi bi-printer me-1"></i>Print / Save as PDF
                </button>
            </div>
        </div>
    </div>

    <div class="page-container p-3 p-md-4">
        <div class="offer-paper">
            <!-- Organization & Company Header -->
            <div class="offer-header-border d-flex justify-content-between align-items-start">
                <div>
                    <h3 class="fw-bold mb-0" style="color: #0F172A; letter-spacing: -0.5px;">
                        <?= htmlspecialchars($offer['company_name']) ?>
                    </h3>
                    <div class="small text-muted mt-1">
                        <?= htmlspecialchars($offer['plant_name'] ?? 'Manufacturing Plant Facility') ?> &bull; 
                        <?= htmlspecialchars($offer['plant_city'] ?? $offer['company_city'] ?? 'India') ?>
                    </div>
                    <div class="small text-secondary" style="font-size: 0.75rem;">
                        CIN / Reg: <?= htmlspecialchars($offer['company_reg'] ?? 'PR-IND-AUTO') ?> &bull; 
                        Partnered with PRAYAS – The Automation Academy
                    </div>
                </div>
                <div class="text-end">
                    <div class="badge" style="background-color: #EF6B6B; font-size: 0.8rem; padding: 6px 12px;">
                        FORMAL OFFER OF EMPLOYMENT
                    </div>
                    <div class="small text-muted mt-2">
                        <strong>Date:</strong> <?= date('d F Y', strtotime($offer['created_at'])) ?>
                    </div>
                    <div class="small text-muted">
                        <strong>Ref No:</strong> <span class="fw-bold text-dark"><?= htmlspecialchars($offer['letter_reference_no']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Recipient Information -->
            <div class="mb-4">
                <div class="text-muted small text-uppercase fw-bold mb-1">To:</div>
                <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($offer['candidate_name']) ?></h5>
                <div class="text-muted small">
                    Trade: <strong><?= htmlspecialchars($offer['trade']) ?></strong> (Passing Batch: <?= htmlspecialchars((string)($offer['passing_year'] ?? 'N/A')) ?>)<br>
                    Application / Match Ref: #<?= (int)$offer['candidate_source_id'] ?> &bull; Source: <?= htmlspecialchars($offer['candidate_source']) ?>
                </div>
            </div>

            <!-- Subject -->
            <div class="p-2 mb-4 bg-light border-start border-3 border-dark rounded-end">
                <strong class="text-dark">Subject:</strong> Offer of Appointment for the position of 
                <span class="text-primary fw-bold"><?= htmlspecialchars($offer['job_designation']) ?></span>
            </div>

            <!-- Letter Body -->
            <p>Dear <strong><?= htmlspecialchars($offer['candidate_name']) ?></strong>,</p>

            <p>
                We are pleased to offer you the position of <strong><?= htmlspecialchars($offer['job_designation']) ?></strong> at 
                <strong><?= htmlspecialchars($offer['company_name']) ?></strong>, facilitated under the industry manpower placement partnership with 
                <strong>PRAYAS – The Automation Academy</strong>.
            </p>

            <p>
                This offer is made in recognition of your technical skills, trade assessment, and performance during the selection process. 
                The terms and conditions governing your appointment are set out below:
            </p>

            <!-- Key Offer Terms Table -->
            <table class="table table-bordered table-sm my-4 align-middle" style="font-size: 0.9rem;">
                <tbody>
                    <tr>
                        <th class="bg-light" style="width: 35%;">Designation &amp; Role</th>
                        <td class="fw-bold text-dark"><?= htmlspecialchars($offer['job_designation']) ?></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Monthly Compensation (CTC)</th>
                        <td class="fw-bold text-success">
                            ₹ <?= number_format((float)$offer['salary_offered'], 2) ?> per month (<?= htmlspecialchars($offer['salary_type'] ?? 'Monthly') ?>)
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-light">Reporting Facility / Location</th>
                        <td><?= htmlspecialchars($offer['job_location'] ?? 'Plant Facility') ?> (<?= htmlspecialchars($offer['plant_name'] ?? 'Main Factory') ?>)</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Proposed Joining Date</th>
                        <td class="fw-bold text-primary">
                            <?= !empty($offer['proposed_joining_date']) ? date('d F Y', strtotime($offer['proposed_joining_date'])) : 'To Be Communicated' ?>
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-light">Offer Acceptance Validity</th>
                        <td class="text-danger fw-semibold">
                            <?= !empty($offer['validity_date']) ? date('d F Y', strtotime($offer['validity_date'])) : '7 Days from Issue' ?>
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-light">Statutory &amp; Plant Benefits</th>
                        <td><?= htmlspecialchars($offer['benefits_summary'] ?? 'Provident Fund (PF), ESIC, Subsidized Canteen, Safety Gear & Industrial Transport.') ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Standard Terms & Conditions -->
            <h6 class="fw-bold text-uppercase mt-4 mb-3" style="color: #0F172A; font-size: 0.85rem; letter-spacing: 0.5px;">
                Terms &amp; Conditions of Employment:
            </h6>
            <ol class="small text-secondary ps-3 mb-4" style="line-height: 1.7;">
                <li class="mb-2"><strong>Probation Period:</strong> You will be on probation for a period of six (6) months from the actual date of commencement of duties.</li>
                <li class="mb-2"><strong>Documentation &amp; Verification:</strong> This offer is subject to the authenticity of your ITI trade qualification certificate, National Trade Certificate (NTC/NAC), Aadhaar credentials, and satisfactory medical fitness clearance.</li>
                <li class="mb-2"><strong>Factory Safety &amp; Discipline:</strong> You shall adhere to all shopfloor standard operating procedures (SOP), PPE mandates, shift discipline, and safety regulations prescribed by <?= htmlspecialchars($offer['company_name']) ?>.</li>
                <li class="mb-2"><strong>Acceptance of Offer:</strong> Please sign and return the duplicate copy of this letter or confirm acceptance via the PRAYAS portal on or before the validity date specified above.</li>
            </ol>

            <!-- Signatory Block -->
            <div class="row pt-4 mt-5 border-top align-items-end">
                <div class="col-6">
                    <div style="height: 50px;" class="d-flex align-items-center">
                        <span class="text-muted fst-italic" style="font-size: 0.8rem;">[Digitally Authorized &amp; Dispatched]</span>
                    </div>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($offer['approved_by_name'] ?? 'PRAYAS Recruitment Desk') ?></div>
                    <div class="small text-muted">Authorized HR Signatory &bull; <?= htmlspecialchars($offer['company_name']) ?></div>
                </div>
                <div class="col-6 text-end">
                    <div style="height: 50px;"></div>
                    <div class="fw-bold text-dark">Candidate Acknowledgment</div>
                    <div class="small text-muted">Signature &amp; Date: _____________________</div>
                </div>
            </div>

            <!-- Footer note -->
            <div class="mt-4 pt-3 border-top text-center text-muted" style="font-size: 0.7rem;">
                PRAYAS Industry Manpower Requirement Portal &bull; Exclusively Managed by PRAYAS – The Automation Academy &bull; Confidential
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
