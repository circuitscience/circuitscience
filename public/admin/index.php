<?php
require_once __DIR__ . '/../../app/Core/helpers.php';
require_once __DIR__ . '/../../app/Core/db.php';

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

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: /admin/login.php');
    exit;
}

if (($_SESSION['admin_logged_in'] ?? false) !== true) {
    header('Location: /admin/login.php');
    exit;
}

$pdo = null;
$reviews = 0;
$requests = 0;
$archivedRequests = 0;
$servicePackages = 0;
$productCategories = 0;
$catalogItems = 0;
$dbError = '';

try {
    $pdo = getPDO();
    $hasArchivedColumn = (bool) $pdo->query("SHOW COLUMNS FROM estimate_requests LIKE 'archived'")->fetch();

    $reviews = (int) $pdo->query('SELECT COUNT(*) FROM reviews WHERE approved = 1')->fetchColumn();

    if ($hasArchivedColumn) {
        $requests = (int) $pdo->query('SELECT COUNT(*) FROM estimate_requests WHERE archived = 0')->fetchColumn();
        $archivedRequests = (int) $pdo->query('SELECT COUNT(*) FROM estimate_requests WHERE archived = 1')->fetchColumn();
    } else {
        $requests = (int) $pdo->query('SELECT COUNT(*) FROM estimate_requests WHERE archived_at IS NULL OR archived_at = "0000-00-00 00:00:00"')->fetchColumn();
        $archivedRequests = (int) $pdo->query('SELECT COUNT(*) FROM estimate_requests WHERE archived_at IS NOT NULL AND archived_at <> "0000-00-00 00:00:00"')->fetchColumn();
    }

    $servicePackages = (int) $pdo->query('SELECT COUNT(*) FROM service_packages WHERE is_active = 1')->fetchColumn();
    $productCategories = (int) $pdo->query('SELECT COUNT(*) FROM product_categories WHERE is_active = 1')->fetchColumn();
    $tables = $pdo->query('SELECT table_name FROM product_categories WHERE is_active = 1')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $catalogItems += (int) $pdo->query('SELECT COUNT(*) FROM `' . str_replace('`', '', $table) . '` WHERE is_active = 1')->fetchColumn();
    }
} catch (Throwable $e) {
    $dbError = 'Database connection failed: ' . $e->getMessage();
    $reviews = 0;
    $requests = 0;
    $archivedRequests = 0;
    $servicePackages = 0;
    $productCategories = 0;
    $catalogItems = 0;
}
?>
<!doctype html>
<html lang="en-CA">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard</title>
  <style>
    :root {
      --bg: #f4f6f8;
      --panel: #ffffff;
      --panel-alt: #eef3f8;
      --text: #112434;
      --muted: #5d6f7c;
      --border: #dfe7ee;
      --primary: #10253d;
      --primary-soft: #e7edf6;
      --accent: #dca93d;
      --success: #1b7f5a;
      --danger: #b42318;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: var(--bg);
      color: var(--text);
    }
    a { color: var(--primary); }
    .layout {
      display: grid;
      grid-template-columns: 230px 1fr;
      min-height: 100vh;
    }
    .sidebar {
      background: var(--primary);
      color: white;
      padding: 1.5rem 1rem;
    }
    .brand {
      display: block;
      font-size: 1.15rem;
      font-weight: 700;
      margin-bottom: 2rem;
      color: white;
      text-decoration: none;
    }
    .nav {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }
    .nav a {
      display: block;
      color: rgba(255,255,255,0.88);
      text-decoration: none;
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 8px;
      padding: 0.75rem 0.9rem;
      font-weight: 600;
    }
    .nav a:hover {
      background: rgba(255,255,255,0.08);
    }
    .content {
      padding: 2rem;
    }
    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    h1, h2, h3 { margin-top: 0; }
    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
    }
    .stat {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 1.15rem;
      box-shadow: 0 8px 18px rgba(17, 36, 52, 0.04);
    }
    .stat small {
      display: block;
      color: var(--muted);
      margin-bottom: 0.5rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .stat strong {
      font-size: 2rem;
      display: block;
      line-height: 1;
    }
    .panel {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 1.4rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 8px 18px rgba(17, 36, 52, 0.04);
    }
    .links {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
    }
    .links a {
      text-decoration: none;
      background: var(--primary-soft);
      border: 1px solid var(--border);
      padding: 0.7rem 0.9rem;
      border-radius: 8px;
      font-weight: 700;
    }
    .muted { color: var(--muted); }
    @media (max-width: 860px) {
      .layout { grid-template-columns: 1fr; }
      .sidebar { padding-bottom: 1rem; }
      .content { padding: 1rem; }
    }
  </style>
</head>
<body>
  <div class="layout">
    <aside class="sidebar">
      <a href="/admin/index.php" class="brand">Circuit Science Admin</a>
      <nav class="nav" aria-label="Admin navigation">
        <a href="/admin/index.php">Overview</a>
        <a href="/admin/reviews.php#requests">Requests</a>
        <a href="/admin/reviews.php#reviews">Reviews</a>
        <a href="/admin/reviews.php#catalog">Catalog</a>
        <a href="/admin/reviews.php#settings">Settings</a>
        <a href="/admin/index.php?logout=1">Sign out</a>
      </nav>
    </aside>

    <main class="content">
      <div class="topbar">
        <div>
          <p class="muted" style="margin:0 0 0.25rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight:700;">Operations</p>
          <h1 style="margin:0;">Dashboard</h1>
        </div>
      </div>

      <?php if ($dbError !== ''): ?>
        <div class="panel" style="border-left: 4px solid #b42318; background: #fff6f6;">
          <strong style="color:#b42318;">Database unavailable</strong>
          <p style="margin:0.5rem 0 0; color:#5d6f7c;"><?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
      <?php else: ?>
      <div class="stats">
        <div class="stat">
          <small>Active requests</small>
          <strong><?php echo (int) $requests; ?></strong>
        </div>
        <div class="stat">
          <small>Archived requests</small>
          <strong><?php echo (int) $archivedRequests; ?></strong>
        </div>
        <div class="stat">
          <small>Reviews</small>
          <strong><?php echo (int) $reviews; ?></strong>
        </div>
        <div class="stat">
          <small>Categories</small>
          <strong><?php echo (int) $productCategories; ?></strong>
        </div>
        <div class="stat">
          <small>Catalog items</small>
          <strong><?php echo (int) $catalogItems; ?></strong>
        </div>
        <div class="stat">
          <small>Service packages</small>
          <strong><?php echo (int) $servicePackages; ?></strong>
        </div>
      </div>
      <?php endif; ?>

      <section class="panel">
        <h2>Quick actions</h2>
        <div class="links">
          <a href="/admin/reviews.php#requests">View requests</a>
          <a href="/admin/reviews.php#reviews">Add review</a>
          <a href="/admin/reviews.php#catalog">Manage catalog</a>
          <a href="/admin/reviews.php#settings">Business settings</a>
        </div>
      </section>

      <section class="panel">
        <h2>Admin notes</h2>
        <p class="muted">This front door keeps the admin area behind a single authenticated landing page and avoids exposing the full dashboard directly through ad hoc file URLs.</p>
      </section>
    </main>
  </div>
</body>
</html>
