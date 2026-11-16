<?php
/**
 * PRAYAS Platform - Secure File Upload API
 */

require_once dirname(__DIR__) . '/config/app.php';

sendCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'POST method required.'], 405);
}

if (empty($_FILES['file'])) {
    jsonResponse(['status' => 'error', 'message' => 'No file was uploaded.'], 400);
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['status' => 'error', 'message' => 'Upload failed with error code: ' . $file['error']], 400);
}

// Maximum size: 10MB
$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    jsonResponse(['status' => 'error', 'message' => 'File size exceeds maximum allowed limit (10MB).'], 413);
}

$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
];

// Check real MIME type using finfo
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

if (!array_key_exists($mime, $allowedMimes)) {
    jsonResponse(['status' => 'error', 'message' => 'Invalid file type. Allowed: PDF, DOC, DOCX, JPG, PNG, WEBP.'], 415);
}

$ext = $allowedMimes[$mime];
$category = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['category'] ?? 'documents');
$uploadDir = dirname(__DIR__) . "/student-portal/uploads/$category";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$safeFilename = bin2hex(random_bytes(16)) . '.' . $ext;
$targetPath = $uploadDir . '/' . $safeFilename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    jsonResponse(['status' => 'error', 'message' => 'Failed to save uploaded file.'], 500);
}

$publicPath = "uploads/$category/$safeFilename";

jsonResponse([
    'status' => 'success',
    'message' => 'File uploaded successfully.',
    'data' => [
        'file_path' => $publicPath,
        'original_name' => sanitizeInput($file['name']),
        'file_size' => $file['size'],
        'mime_type' => $mime
    ]
], 201);
