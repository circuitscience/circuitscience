<?php
require_once __DIR__ . '/../../app/Core/auth.php';

requireLogin();
$user = currentUser();
$pdo = getPDO();
$message = null;
$properties = $pdo->prepare('SELECT id, label, address_line1 FROM customer_properties WHERE customer_id = :id ORDER BY created_at DESC');
$properties->execute([':id' => $user['id']]);
$properties = $properties->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $propertyId = $_POST['property_id'] ?: null;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $urgency = $_POST['urgency'] ?? 'normal';
    $processStage = $_POST['process_stage'] ?? 'ready_to_book';

    if ($description) {
        $propertyId = $propertyId ?: null;
        $stmt = $pdo->prepare('INSERT INTO service_requests (customer_id, property_id, process_stage, urgency, title, description, status, request_source, created_at, updated_at)
            VALUES (:customer_id, :property_id, :process_stage, :urgency, :title, :description, :status, :request_source, NOW(), NOW())');
        $stmt->execute([
            ':customer_id' => $user['id'],
            ':property_id' => $propertyId,
            ':process_stage' => $processStage,
            ':urgency' => $urgency,
            ':title' => $title ?: 'Service request',
            ':description' => $description,
            ':status' => 'submitted',
            ':request_source' => 'customer_portal',
        ]);
        $message = 'Your request has been submitted. We will review it and follow up soon.';
    } else {
        $message = 'Please describe the work you want done.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Request | Circuit Science Inc.</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="page-shell">
    <header class="page-header">
        <a class="brand" href="/">Circuit Science Inc.</a>
        <nav class="header-nav">
            <a href="/customer/dashboard.php">Dashboard</a>
            <a href="/customer/new-request.php">New Request</a>
            <a href="/customer/properties.php">Properties</a>
            <a href="/customer/requests.php">Requests</a>
            <a href="/logout.php">Logout</a>
        </nav>
    </header>
    <main class="page-content">
        <section class="request-panel">
            <h1>New service request</h1>
            <p>Submit a new request for your saved property or start with a new work description.</p>

            <?php if ($message): ?>
                <div class="message-box"><?php echo escape($message); ?></div>
            <?php endif; ?>

            <form method="post" class="request-form">
                <?php if (count($properties)): ?>
                    <label>
                        Property
                        <select name="property_id">
                            <option value="">Choose a saved property</option>
                            <?php foreach ($properties as $property): ?>
                                <option value="<?php echo escape($property['id']); ?>"><?php echo escape($property['label'] . ' — ' . $property['address_line1']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <label>
                    Request title
                    <input type="text" name="title" value="<?php echo escape($_POST['title'] ?? ''); ?>" placeholder="Fix leaking faucet, panel upgrade">
                </label>
                <label>
                    Description
                    <textarea name="description" rows="5" required><?php echo escape($_POST['description'] ?? ''); ?></textarea>
                </label>
                <label>
                    Request type
                    <select name="process_stage">
                        <option value="planning_stage">Planning Stage</option>
                        <option value="ready_to_book" selected>Ready to Book</option>
                        <option value="general_inquiry">General Inquiry</option>
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
