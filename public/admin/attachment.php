<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/Core/db.php';

session_start();

if (($_SESSION['admin_logged_in'] ?? false) !== true) {
    http_response_code(403);
    exit('Forbidden');
}

$attachmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$attachmentId || $attachmentId < 1) {
    http_response_code(400);
    exit('Invalid attachment.');
}

$stmt = getPDO()->prepare('SELECT original_name, stored_path, mime_type FROM estimate_request_attachments WHERE id = :id');
$stmt->execute([':id' => $attachmentId]);
$attachment = $stmt->fetch();

if (!$attachment) {
    http_response_code(404);
    exit('Attachment not found.');
}

$projectRoot = dirname(__DIR__, 2);
$relativePath = str_replace('\\', '/', ltrim((string) $attachment['stored_path'], '/'));

if (str_starts_with($relativePath, 'storage/estimate-requests/')) {
    $candidate = $projectRoot . '/' . $relativePath;
    $allowedRoot = realpath($projectRoot . '/storage/estimate-requests');
} elseif (str_starts_with($relativePath, 'uploads/estimate-requests/')) {
    $candidate = $projectRoot . '/public/' . $relativePath;
    $allowedRoot = realpath($projectRoot . '/public/uploads/estimate-requests');
} else {
    http_response_code(404);
    exit('Attachment not found.');
}

$filePath = realpath($candidate);
if ($allowedRoot === false || $filePath === false || !str_starts_with($filePath, $allowedRoot . DIRECTORY_SEPARATOR) || !is_file($filePath)) {
    http_response_code(404);
    exit('Attachment not found.');
}

$fileName = str_replace(["\r", "\n", '"', '\\'], '_', basename((string) $attachment['original_name']));
$mimeType = (string) (mime_content_type($filePath) ?: $attachment['mime_type'] ?: 'application/octet-stream');

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . (string) filesize($filePath));
header("Content-Disposition: inline; filename=\"{$fileName}\"; filename*=UTF-8''" . rawurlencode($fileName));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

readfile($filePath);
