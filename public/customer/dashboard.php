<?php
require_once __DIR__ . '/../../app/Core/auth.php';

requireLogin();
$user = currentUser();
$pdo = getPDO();

$propertyCount = $pdo->prepare('SELECT COUNT(*) AS total FROM customer_properties WHERE customer_id = :id');
$propertyCount->execute([':id' => $user['id']]);
$propertyCount = $propertyCount->fetchColumn();

$requestCount = $pdo->prepare('SELECT COUNT(*) AS total FROM service_requests WHERE customer_id = :id');
$requestCount->execute([':id' => $user['id']]);
$requestCount = $requestCount->fetchColumn();

$recentRequests = $pdo->prepare('SELECT id, title, status, urgency, created_at FROM service_requests WHERE customer_id = :id ORDER BY created_at DESC LIMIT 5');
$recentRequests->execute([':id' => $user['id']]);
$recentRequests = $recentRequests->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Circuit Science Inc.</title>
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
            <h1>Welcome back, <?php echo escape($user['first_name'] ?? 'Customer'); ?></h1>
            <p>Track your service requests, save properties, and create new requests faster.</p>

            <div class="summary-grid">
                <div class="summary-card">
                    <strong><?php echo escape((string)$propertyCount); ?></strong>
                    <span>Saved properties</span>
                </div>
                <div class="summary-card">
                    <strong><?php echo escape((string)$requestCount); ?></strong>
                    <span>Requests submitted</span>
                </div>
            </div>

            <div class="panel-block">
                <h2>Recent requests</h2>
                <?php if (count($recentRequests) === 0): ?>
                    <p>No requests yet. Start with a new service request.</p>
                <?php else: ?>
                    <table class="list-table">
                        <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Urgency</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentRequests as $request): ?>
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
            </div>
        </section>
    </main>
</div>
</body>
</html>
