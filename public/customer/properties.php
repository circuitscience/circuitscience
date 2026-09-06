<?php
require_once __DIR__ . '/../../app/Core/auth.php';

requireLogin();
$user = currentUser();
$pdo = getPDO();
$message = null;
$propertyTypes = $pdo->query('SELECT id, name FROM property_types WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $label = trim($_POST['label'] ?? '');
    $propertyTypeId = $_POST['property_type_id'] ?: null;
    $addressLine1 = trim($_POST['address_line1'] ?? '');
    $addressLine2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? 'Ontario');
    $postalCode = trim($_POST['postal_code'] ?? '');
    $accessNotes = trim($_POST['access_notes'] ?? '');
    $parkingNotes = trim($_POST['parking_notes'] ?? '');

    if ($addressLine1 && $city) {
        $stmt = $pdo->prepare('INSERT INTO customer_properties (customer_id, property_type_id, label, address_line1, address_line2, city, province, postal_code, access_notes, parking_notes, created_at, updated_at)
            VALUES (:customer_id, :property_type_id, :label, :address_line1, :address_line2, :city, :province, :postal_code, :access_notes, :parking_notes, NOW(), NOW())');
        $stmt->execute([
            ':customer_id' => $user['id'],
            ':property_type_id' => $propertyTypeId,
            ':label' => $label ?: 'Home',
            ':address_line1' => $addressLine1,
            ':address_line2' => $addressLine2,
            ':city' => $city,
            ':province' => $province,
            ':postal_code' => $postalCode,
            ':access_notes' => $accessNotes,
            ':parking_notes' => $parkingNotes,
        ]);
        $message = 'Property saved successfully.';
    } else {
        $message = 'Please provide the address and city for this property.';
    }
}

$properties = $pdo->prepare('SELECT cp.*, pt.name AS property_type FROM customer_properties cp LEFT JOIN property_types pt ON cp.property_type_id = pt.id WHERE cp.customer_id = :id ORDER BY cp.created_at DESC');
$properties->execute([':id' => $user['id']]);
$properties = $properties->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Properties | Circuit Science Inc.</title>
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
            <h1>Your properties</h1>
            <p>Save addresses so future requests are faster.</p>

            <?php if ($message): ?>
                <div class="message-box"><?php echo escape($message); ?></div>
            <?php endif; ?>

            <?php if (count($properties)): ?>
                <table class="list-table">
                    <thead>
                    <tr>
                        <th>Label</th>
                        <th>Type</th>
                        <th>Address</th>
                        <th>City</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($properties as $property): ?>
                        <tr>
                            <td><?php echo escape($property['label']); ?></td>
                            <td><?php echo escape($property['property_type'] ?: 'Other'); ?></td>
                            <td><?php echo escape($property['address_line1']); ?></td>
                            <td><?php echo escape($property['city']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No saved properties yet.</p>
            <?php endif; ?>

            <div class="panel-block">
                <h2>Add a property</h2>
                <form method="post" class="request-form">
                    <label>
                        Property label
                        <input type="text" name="label" value="<?php echo escape($_POST['label'] ?? ''); ?>" placeholder="Home, Rental, Shop">
                    </label>
                    <label>
                        Property type
                        <select name="property_type_id">
                            <option value="">Select a type</option>
                            <?php foreach ($propertyTypes as $type): ?>
                                <option value="<?php echo escape($type['id']); ?>"><?php echo escape($type['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Address line 1
                        <input type="text" name="address_line1" value="<?php echo escape($_POST['address_line1'] ?? ''); ?>" required>
                    </label>
                    <label>
                        Address line 2
                        <input type="text" name="address_line2" value="<?php echo escape($_POST['address_line2'] ?? ''); ?>">
                    </label>
                    <label>
                        City
                        <input type="text" name="city" value="<?php echo escape($_POST['city'] ?? ''); ?>" required>
                    </label>
                    <label>
                        Province / State
                        <input type="text" name="province" value="<?php echo escape($_POST['province'] ?? 'Ontario'); ?>">
                    </label>
                    <label>
                        Postal code
                        <input type="text" name="postal_code" value="<?php echo escape($_POST['postal_code'] ?? ''); ?>">
                    </label>
                    <label>
                        Access notes
                        <textarea name="access_notes"><?php echo escape($_POST['access_notes'] ?? ''); ?></textarea>
                    </label>
                    <label>
                        Parking notes
                        <textarea name="parking_notes"><?php echo escape($_POST['parking_notes'] ?? ''); ?></textarea>
                    </label>
                    <button type="submit" class="btn btn-primary">Save Property</button>
                </form>
            </div>
        </section>
    </main>
</div>
</body>
</html>
