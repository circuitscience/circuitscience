<?php
require_once __DIR__ . '/../app/Core/auth.php';

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $passwordConfirm = trim($_POST['password_confirm'] ?? '');

    if ($firstName && $email && $password && $passwordConfirm) {
        if ($password !== $passwordConfirm) {
            $message = 'Passwords do not match.';
        } else {
            $pdo = getPDO();
            $existing = $pdo->prepare('SELECT id FROM customers WHERE email = :email');
            $existing->execute([':email' => strtolower($email)]);

            if ($existing->fetch()) {
                $message = 'An account with that email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO customers (first_name, last_name, email, phone, password_hash, created_at, updated_at)
                    VALUES (:first_name, :last_name, :email, :phone, :password_hash, NOW(), NOW())');
                $stmt->execute([
                    ':first_name' => $firstName,
                    ':last_name' => $lastName,
                    ':email' => strtolower($email),
                    ':phone' => $phone,
                    ':password_hash' => $hash,
                ]);
                $customerId = $pdo->lastInsertId();
                $customer = $pdo->prepare('SELECT * FROM customers WHERE id = :id');
                $customer->execute([':id' => $customerId]);
                loginUser($customer->fetch());
                redirect('/customer/dashboard.php');
            }
        }
    } else {
        $message = 'Please complete all required fields.';
    }
}

function old_value(string $key, string $default = ''): string
{
    return htmlspecialchars($_POST[$key] ?? $default, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Circuit Science Inc.</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="auth-shell">
    <header>
        <a class="brand" href="/">Circuit Science Inc.</a>
    </header>
    <main class="auth-card">
        <h1>Create an account</h1>
        <p>Register to save properties, track requests, and make future jobs easier.</p>
        <?php if ($message): ?>
            <div class="message-box"><?php echo escape($message); ?></div>
        <?php endif; ?>
        <form method="post" class="request-form">
            <label>
                First name
                <input type="text" name="first_name" value="<?php echo old_value('first_name'); ?>" required>
            </label>
            <label>
                Last name
                <input type="text" name="last_name" value="<?php echo old_value('last_name'); ?>">
            </label>
            <label>
                Email
                <input type="email" name="email" value="<?php echo old_value('email'); ?>" required>
            </label>
            <label>
                Phone
                <input type="text" name="phone" value="<?php echo old_value('phone'); ?>">
            </label>
            <label>
                Password
                <input type="password" name="password" required>
            </label>
            <label>
                Confirm password
                <input type="password" name="password_confirm" required>
            </label>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        <p>Already have an account? <a href="/login.php">Login</a>.</p>
    </main>
</div>
</body>
</html>
