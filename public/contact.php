<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Core/db.php';

session_start();

function loadEmailEnv(string $path): array
{
    $env = [];
    if (!is_file($path) || !is_readable($path)) {
        return $env;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key === '') {
            continue;
        }

        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }

        $env[$key] = $value;
    }

    return $env;
}

function finish(string $state, ?string $reason = null): void
{
    $location = '/?form=' . rawurlencode($state);
    if ($reason !== null && $reason !== '') {
        $location .= '&reason=' . rawurlencode($reason);
    }

    header('Location: ' . $location, true, 303);
    exit;
}

function validateAttachments(): array
{
    if (!isset($_FILES['attachments']) || !is_array($_FILES['attachments'])) {
        return [];
    }

    $names = $_FILES['attachments']['name'] ?? [];
    $tmpNames = $_FILES['attachments']['tmp_name'] ?? [];
    $errors = $_FILES['attachments']['error'] ?? [];
    $sizes = $_FILES['attachments']['size'] ?? [];

    if (!is_array($names) || !is_array($tmpNames) || !is_array($errors) || !is_array($sizes)) {
        return [];
    }

    $allowedMimeTypes = [
        'image/gif' => 'gif',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];
    $validated = [];

    foreach ($names as $index => $name) {
        $fileName = (string) $name;
        if ($fileName === '') {
            continue;
        }

        $error = (int) ($errors[$index] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            continue;
        }

        $tmpName = (string) ($tmpNames[$index] ?? '');
        $size = (int) ($sizes[$index] ?? 0);
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            continue;
        }

        $mime = (string) mime_content_type($tmpName);

        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            continue;
        }

        if (!isset($allowedMimeTypes[$mime])) {
            continue;
        }

        $validated[] = [
            'name' => $fileName,
            'tmp_name' => $tmpName,
            'size' => $size,
            'mime_type' => $mime,
            'extension' => $allowedMimeTypes[$mime],
        ];

        if (count($validated) >= 3) {
            break;
        }
    }

    return $validated;
}

function saveAttachments(int $requestId, array $attachments): array
{
    $rootDir = dirname(__DIR__) . '/storage/estimate-requests/' . $requestId;
    if (!is_dir($rootDir) && !mkdir($rootDir, 0775, true) && !is_dir($rootDir)) {
        throw new RuntimeException('Could not create the upload directory for this request.');
    }

    $saved = [];

    foreach ($attachments as $attachment) {
        $originalName = basename((string) ($attachment['name'] ?? 'attachment'));
        $tmpName = (string) ($attachment['tmp_name'] ?? '');
        $size = (int) ($attachment['size'] ?? 0);
        $mimeType = (string) ($attachment['mime_type'] ?? 'application/octet-stream');
        $extension = (string) ($attachment['extension'] ?? 'bin');

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            continue;
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $storedPath = $rootDir . '/' . $storedName;

        if (!@move_uploaded_file($tmpName, $storedPath)) {
            continue;
        }

        $saved[] = [
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'stored_path' => 'storage/estimate-requests/' . $requestId . '/' . $storedName,
            'mime_type' => $mimeType,
            'file_size' => $size,
        ];
    }

    return $saved;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

if (!empty($_POST['website'])) {
    finish('sent');
}

$token = (string) ($_POST['csrf_token'] ?? '');
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    finish('error', 'Your request could not be processed. Please refresh the page and try again.');
}

$lastSubmission = (int) ($_SESSION['last_submission'] ?? 0);
if ($lastSubmission > time() - 30) {
    finish('error', 'Please wait a moment before sending another request.');
}

$name = trim((string) preg_replace('/[\r\n]+/', ' ', (string) ($_POST['name'] ?? '')));
$phone = trim((string) ($_POST['phone'] ?? ''));
$email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
$property = trim((string) ($_POST['property'] ?? ''));
$contact = trim((string) ($_POST['contact'] ?? ''));
$details = trim((string) ($_POST['details'] ?? ''));
$communityRate = ($_POST['community_rate'] ?? '') === 'Yes' ? 'Yes' : 'No';
$attachments = validateAttachments();

$allowedProperties = ['Residential', 'Commercial', 'Healthcare'];
$allowedContacts = ['Phone', 'Text message', 'Email'];
if ($name === '' || strlen($name) > 120 || $phone === '' || strlen($phone) > 40 || !$email || strlen($details) < 10 || strlen($details) > 5000 || !in_array($property, $allowedProperties, true) || !in_array($contact, $allowedContacts, true)) {
    finish('error', 'Please complete your name, phone, email, and project details before sending the request.');
}

try {
    $env = loadEmailEnv(dirname(__DIR__) . '/.env');

    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO estimate_requests (name, phone, email, property_type, preferred_contact, community_rate, details, source, status) VALUES (:name, :phone, :email, :property_type, :preferred_contact, :community_rate, :details, :source, :status)');
    $stmt->execute([
        ':name' => $name,
        ':phone' => $phone,
        ':email' => $email,
        ':property_type' => $property,
        ':preferred_contact' => $contact,
        ':community_rate' => $communityRate,
        ':details' => $details,
        ':source' => 'public_site',
        ':status' => 'new',
    ]);
    $requestId = (int) $pdo->lastInsertId();

    $savedAttachments = $attachments === [] ? [] : saveAttachments($requestId, $attachments);
    if ($savedAttachments !== []) {
        $attachStmt = $pdo->prepare('INSERT INTO estimate_request_attachments (estimate_request_id, original_name, stored_name, stored_path, mime_type, file_size) VALUES (:estimate_request_id, :original_name, :stored_name, :stored_path, :mime_type, :file_size)');
        foreach ($savedAttachments as $file) {
            $attachStmt->execute([
                ':estimate_request_id' => $requestId,
                ':original_name' => $file['original_name'],
                ':stored_name' => $file['stored_name'],
                ':stored_path' => $file['stored_path'],
                ':mime_type' => $file['mime_type'],
                ':file_size' => $file['file_size'],
            ]);
        }
    }

    $mailTo = $env['MAIL_TO_EMAIL'] ?? 'info@circuitscience.ca';
    $mailFrom = $env['SMTP_FROM_EMAIL'] ?? 'info@circuitscience.ca';
    $mailFromName = $env['SMTP_FROM_NAME'] ?? 'Circuit Science Website';
    $ccEmail = $env['MAIL_CC_EMAIL'] ?? 'mail@jerrybilous.ca';
    $subject = "Website estimate request — {$property}";
    $body = implode(PHP_EOL, [
        "Name: {$name}",
        "Phone: {$phone}",
        "Email: {$email}",
        "Property type: {$property}",
        "Preferred contact: {$contact}",
        "Community Care Rate enquiry: {$communityRate}",
        '',
        'Project details:',
        $details,
    ]);

    $headers = [
        'From: ' . $mailFromName . ' <' . $mailFrom . '>',
        'Reply-To: ' . $name . ' <' . $email . '>',
        'Cc: ' . $ccEmail,
        'Content-Type: text/plain; charset=UTF-8',
    ];

    $sent = mail($mailTo, $subject, $body, implode("\r\n", $headers));
    if (!$sent) {
        error_log('Circuit Science notification email was not accepted by the hosting mail transport for request #' . $requestId);
    }

    $confirmationSubject = 'We received your estimate request';
    $confirmationBody = implode(PHP_EOL, [
        "Hi {$name},",
        '',
        'Thank you for reaching out. I appreciate the opportunity to review your project and I will be in touch as soon as I can.',
        '',
        'Here is the summary of your request:',
        "Property type: {$property}",
        "Preferred contact: {$contact}",
        "Phone: {$phone}",
        '',
        'If this is an urgent electrical hazard, please call 905-616-2987 instead of waiting for email.',
        '',
        'Thank you again,',
        'Jerry',
        'Circuit Science Inc.',
    ]);

    $confirmationHeaders = [
        'From: ' . $mailFromName . ' <' . $mailFrom . '>',
        'Reply-To: ' . $mailFromName . ' <' . $mailFrom . '>',
        'Content-Type: text/plain; charset=UTF-8',
    ];

    mail($email, $confirmationSubject, $confirmationBody, implode("\r\n", $confirmationHeaders));

    $_SESSION['last_submission'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    finish('sent');
} catch (Throwable $error) {
    error_log('Circuit Science contact form error: ' . $error->getMessage());
    finish('error', 'We could not send your request. Please call 905-616-2987 or try again later.');
}
