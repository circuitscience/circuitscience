<?php
require_once __DIR__ . '/../app/Core/auth.php';

$message = null;
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        $customer = authenticate($email, $password);
        if ($customer) {
            loginUser($customer);
            redirect('/customer/dashboard.php');
        }
        $message = 'Invalid email or password.';
    } else {
        $message = 'Please enter your email and password.';
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
    <title>Login | Circuit Science Inc.</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="auth-shell">
    <header>
        <a class="brand" href="/">Circuit Science Inc.</a>
    </header>
    <main class="auth-card">
        <h1>Login</h1>
        <p>Sign in to manage your requests and saved properties.</p>
        <?php if ($message): ?>
            <div class="message-box"><?php echo escape($message); ?></div>
        <?php endif; ?>
        <form method="post" class="request-form">
            <label>
                Email
                <input type="email" name="email" value="<?php echo old_value('email', $email); ?>" required>
            </label>
            <label>
                Password
                <input type="password" name="password" required>
            </label>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <p>New here? <a href="/register.php">Create an account</a>.</p>
    </main>
</div>
</body>
</html>
