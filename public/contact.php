<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

session_start();

function finish(string $state): never
{
    header('Location: /?form=' . rawurlencode($state) . '#estimate', true, 303);
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
    require dirname(__DIR__) . '/vendor/autoload.php';
    require dirname(__DIR__) . '/app/bootstrap.php';

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = envValue('SMTP_HOST');
    $mail->Port = (int) envValue('SMTP_PORT', '587');
    $mail->SMTPAuth = true;
    $mail->Username = envValue('SMTP_USERNAME');
    $mail->Password = envValue('SMTP_PASSWORD');
    $encryption = strtolower(envValue('SMTP_ENCRYPTION', 'tls'));
    $mail->SMTPSecure = $encryption === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom(envValue('SMTP_FROM_EMAIL'), envValue('SMTP_FROM_NAME', 'Circuit Science Website'));
    $mail->addAddress(envValue('MAIL_TO_EMAIL'), envValue('MAIL_TO_NAME', 'Jerry Bilous'));
    $mail->addReplyTo((string) $email, $name);

    $mail->Subject = "Website estimate request — {$property}";
    $mail->Body = implode(PHP_EOL, [
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

    $files = $_FILES['attachments'] ?? null;
    if ($files && is_array($files['name'] ?? null)) {
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $count = min(count($files['name']), 3);
        for ($i = 0; $i < $count; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            if (($files['error'][$i] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($files['size'][$i] ?? 0) > 5 * 1024 * 1024) {
                throw new RuntimeException('Invalid attachment.');
            }
            $tmp = (string) $files['tmp_name'][$i];
            $mime = $fileInfo->file($tmp);
            if (!isset($allowedTypes[$mime])) throw new RuntimeException('Unsupported attachment.');
            $safeName = 'attachment-' . ($i + 1) . '.' . $allowedTypes[$mime];
            $mail->addAttachment($tmp, $safeName);
        }
    }

    $mail->send();
    $_SESSION['last_submission'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    finish('sent');
} catch (Throwable $error) {
    error_log('Circuit Science contact form error: ' . $error->getMessage());
    finish('error');
}
