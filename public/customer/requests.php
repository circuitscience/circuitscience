<?php
require_once __DIR__ . '/../../app/Core/auth.php';

requireLogin();
$user = currentUser();
$pdo = getPDO();

$requests = $pdo->prepare('SELECT id, title, status, urgency, created_at FROM service_requests WHERE customer_id = :id ORDER BY created_at DESC');
$requests->execute([':id' => $user['id']]);
$requests = $requests->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Requests | Circuit Science Inc.</title>
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
            <h1>Your service requests</h1>
            <p>Review the status of your submitted requests and see recent activity.</p>

            <?php if (count($requests) === 0): ?>
                <p>You have not submitted any requests yet.</p>
            <?php else: ?>
                <table class="list-table">
                    <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Urgency</th>
                        <th>Submitted</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?php echo escape($request['title'] ?: 'Request'); ?></td>
                            <td><?php echo escape($request['status']); ?></td>
                            <td><?php echo escape($request['urgency']); ?></td>
                            <td><?php echo escape($request['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>
