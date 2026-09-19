<?php
require_once __DIR__ . '/../../app/Core/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$dotenv = getDotEnv(__DIR__ . '/../../.env');
foreach ($dotenv as $key => $value) {
    if (!array_key_exists($key, $_ENV)) {
        $_ENV[$key] = $value;
    }
}

$adminSecret = trim((string) ($_ENV['ADMIN_PUBLIC_SECRET'] ?? getenv('ADMIN_PUBLIC_SECRET') ?? ''));
$submittedSecret = trim((string) ($_POST['secret'] ?? ''));
$error = '';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    session_start();
}

if (($_SESSION['admin_logged_in'] ?? false) === true) {
    header('Location: /admin/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($adminSecret !== '' && $submittedSecret !== '' && hash_equals($adminSecret, $submittedSecret)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login_time'] = time();
        header('Location: /admin/index.php');
        exit;
    }

    $error = 'Invalid admin secret.';
}
?>
<!doctype html>
<html lang="en-CA">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login</title>
  <style>
    :root {
      --bg: #f4f6f8;
      --panel: #ffffff;
      --text: #122130;
      --muted: #5b6978;
      --border: #dfe7ee;
      --primary: #10253d;
      --danger: #b42318;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: grid;
      place-items: center;
    }
    .card {
      width: min(92vw, 440px);
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 2rem;
      box-shadow: 0 14px 28px rgba(16, 37, 61, 0.08);
    }
    h1 {
      margin: 0 0 0.75rem;
      font-size: 2rem;
    }
    p {
      margin: 0 0 1.25rem;
      color: var(--muted);
      line-height: 1.5;
    }
    label {
      display: block;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }
    input {
      width: 100%;
      padding: 0.8rem 0.9rem;
      border-radius: 8px;
      border: 1px solid var(--border);
      font: inherit;
      margin-bottom: 1rem;
    }
    button {
      width: 100%;
      background: var(--primary);
      color: #fff;
      border: 0;
      border-radius: 8px;
      padding: 0.9rem 1rem;
      font: inherit;
      font-weight: 700;
      cursor: pointer;
    }
    .error {
      color: var(--danger);
      margin-bottom: 1rem;
      font-weight: 600;
    }
  </style>
</head>
<body>
  <div class="card">
    <h1>Admin access</h1>
    <p>Enter the admin secret to continue to the dashboard.</p>

    <?php if ($error !== ''): ?>
      <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="/admin/login.php">
      <label for="secret">Secret</label>
      <input id="secret" type="password" name="secret" required autocomplete="off">
      <button type="submit">Access dashboard</button>
    </form>

    <p style="margin-top: 1rem; margin-bottom: 0; text-align: center;">
      <a href="/index.php" style="color: #10253d; font-weight: 700; text-decoration: none;">Back to site</a>
    </p>
  </div>
</body>
</html>
