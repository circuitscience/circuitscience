<?php
require_once __DIR__ . '/../app/Core/db.php';

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $property_address = trim($_POST['property_address'] ?? '');
    $issue_description = trim($_POST['issue_description'] ?? '');
    $urgency = trim($_POST['urgency'] ?? 'normal');
    $intent = trim($_POST['intent'] ?? 'planning_stage');

    if ($first_name && $email && $issue_description) {
        $pdo = getPDO();
        $stmt = $pdo->prepare(
            'INSERT INTO service_requests (first_name, last_name, email, phone, address_line1, description, urgency, process_stage, status, created_at, updated_at)
            VALUES (:first_name, :last_name, :email, :phone, :address_line1, :description, :urgency, :process_stage, :status, NOW(), NOW())'
        );
        $stmt->execute([
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':email' => $email,
            ':phone' => $phone,
            ':address_line1' => $property_address,
            ':description' => $issue_description,
            ':urgency' => $urgency,
            ':process_stage' => $intent,
            ':status' => 'submitted',
        ]);

        $message = 'Thanks! Your request is submitted. We will review it and follow up shortly.';
    } else {
        $message = 'Please provide your name, email, and a description of the issue.';
    }
}

function old(string $key, string $default = ''): string
{
    return escape($_POST[$key] ?? $default);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Request | Circuit Science Inc.</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="page-shell">
    <header class="page-header">
        <a class="brand" href="/">Circuit Science Inc.</a>
        <nav class="header-nav">
            <a href="/">Home</a>
            <a href="/login.php">Login</a>
            <a href="/register.php">Register</a>
        </nav>
    </header>
    <main class="page-content">
        <section class="request-panel">
            <h1>Build a quote-ready request</h1>
            <p>Tell us the issue, upload photos later, and we’ll give you a better response.</p>

            <?php if ($message): ?>
                <div class="message-box"><?php echo escape($message); ?></div>
            <?php endif; ?>

            <form method="post" action="/smart-request.php" class="request-form">
                <label>
                    First name
                    <input type="text" name="first_name" value="<?php echo old('first_name'); ?>" required>
                </label>
                <label>
                    Last name
                    <input type="text" name="last_name" value="<?php echo old('last_name'); ?>">
                </label>
                <label>
                    Email
                    <input type="email" name="email" value="<?php echo old('email'); ?>" required>
                </label>
                <label>
                    Phone
                    <input type="text" name="phone" value="<?php echo old('phone'); ?>">
                </label>
                <label>
                    Property address
                    <input type="text" name="property_address" value="<?php echo old('property_address'); ?>">
                </label>
                <label>
                    Issue description
                    <textarea name="issue_description" rows="5" required><?php echo old('issue_description'); ?></textarea>
                </label>
                <label>
                    Request type
                    <select name="intent">
                        <option value="general_inquiry">General Inquiry</option>
                        <option value="planning_stage" selected>Planning Stage</option>
                        <option value="ready_to_book">Ready to Book</option>
                        <option value="emergency_urgent">Emergency / Urgent</option>
                    </select>
                </label>
                <label>
                    Urgency
                    <select name="urgency">
                        <option value="normal">Normal</option>
                        <option value="soon">Soon</option>
                        <option value="urgent">Urgent</option>
                        <option value="emergency">Emergency</option>
                    </select>
                </label>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </form>
        </section>
    </main>
</div>
</body>
</html>
