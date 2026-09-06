<?php
declare(strict_types=1);

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

function finish(string $state): never
{
    header('Location: /?form=' . rawurlencode($state), true, 303);
    exit;
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
    finish('error');
}

$lastSubmission = (int) ($_SESSION['last_submission'] ?? 0);
if ($lastSubmission > time() - 30) {
    finish('error');
}

$name = trim((string) ($_POST['name'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
$property = trim((string) ($_POST['property'] ?? ''));
$contact = trim((string) ($_POST['contact'] ?? ''));
$details = trim((string) ($_POST['details'] ?? ''));
$communityRate = ($_POST['community_rate'] ?? '') === 'Yes' ? 'Yes' : 'No';

$allowedProperties = ['Residential', 'Commercial', 'Healthcare'];
$allowedContacts = ['Phone', 'Text message', 'Email'];
if ($name === '' || strlen($name) > 120 || $phone === '' || strlen($phone) > 40 || !$email || strlen($details) < 10 || strlen($details) > 5000 || !in_array($property, $allowedProperties, true) || !in_array($contact, $allowedContacts, true)) {
    finish('error');
}

try {
    $env = loadEmailEnv(dirname(__DIR__) . '/.env');

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
        throw new RuntimeException('Failed to send estimate email via PHP mail().');
    }

    $confirmationSubject = 'We received your estimate request';
    $confirmationBody = implode(PHP_EOL, [
        "Hi {$name},",
        '',
        'Thank you for contacting Circuit Science Inc. We have received your estimate request and will respond as soon as possible.',
        '',
        'Request summary:',
        "Property type: {$property}",
        "Preferred contact: {$contact}",
        "Phone: {$phone}",
        '',
        'If this is an urgent electrical hazard, please call 905-616-2987 instead of waiting for email.',
        '',
        'Thank you,',
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
    finish('error');
}
