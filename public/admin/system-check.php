<?php
declare(strict_types=1);

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

if (($_SESSION['admin_logged_in'] ?? false) !== true) {
    header('Location: /admin/login.php');
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

function checkRow(string $label, bool $passed, string $details): array
{
    return ['label' => $label, 'passed' => $passed, 'details' => $details];
}

$checks = [];
$checks[] = checkRow('PHP version', version_compare(PHP_VERSION, '8.0.0', '>='), PHP_VERSION);
$checks[] = checkRow('PDO extension', extension_loaded('pdo'), extension_loaded('pdo') ? 'Loaded' : 'Missing');
$pdoDrivers = class_exists('PDO') ? PDO::getAvailableDrivers() : [];
$checks[] = checkRow('PDO MySQL driver', in_array('mysql', $pdoDrivers, true), $pdoDrivers === [] ? 'No PDO drivers available' : implode(', ', $pdoDrivers));
$checks[] = checkRow('Fileinfo extension', extension_loaded('fileinfo'), extension_loaded('fileinfo') ? 'Loaded' : 'Missing');
$checks[] = checkRow('Session extension', extension_loaded('session'), extension_loaded('session') ? 'Loaded' : 'Missing');

$photoDirectory = __DIR__ . '/assets/product_pics';
$photoCount = 0;
if (is_dir($photoDirectory) && is_readable($photoDirectory)) {
    foreach (scandir($photoDirectory) ?: [] as $fileName) {
        if (is_file($photoDirectory . DIRECTORY_SEPARATOR . $fileName) && in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), ['gif', 'jpg', 'jpeg', 'png', 'webp'], true)) {
            $photoCount++;
        }
    }
}
$checks[] = checkRow('Product-photo directory', is_dir($photoDirectory) && is_readable($photoDirectory), $photoCount . ' supported image(s) found');

try {
    require_once __DIR__ . '/../../app/Core/db.php';
    $pdo = getPDO();
    $checks[] = checkRow('Database connection', true, 'Connected');
    foreach (['material_categories', 'material_units', 'material_manufacturers', 'job_materials'] as $tableName) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name');
        $stmt->execute([':table_name' => $tableName]);
        $exists = (int) $stmt->fetchColumn() > 0;
        $checks[] = checkRow('Table: ' . $tableName, $exists, $exists ? 'Present' : 'Missing — import database/add_job_materials.sql');
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'job_materials' AND column_name = 'subcategory'");
    $stmt->execute();
    $hasSubcategory = (int) $stmt->fetchColumn() > 0;
    $checks[] = checkRow('Column: job_materials.subcategory', $hasSubcategory, $hasSubcategory ? 'Present' : 'Missing — import database/add_material_subcategory_manufacturers.sql');
} catch (Throwable $error) {
    error_log('Admin system check application/database error: ' . $error->getMessage());
    $checks[] = checkRow('Application and database', false, get_class($error) . ': ' . $error->getMessage());
}

$allPassed = !in_array(false, array_column($checks, 'passed'), true);
?>
<!doctype html>
<html lang="en-CA">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>System Check | Circuit Science Admin</title>
  <style>
    body { margin:0; background:#f4f6f8; color:#112434; font-family:Arial,sans-serif; }
    main { width:min(860px,calc(100% - 2rem)); margin:2rem auto; }
    .panel { background:#fff; border:1px solid #dfe7ee; border-radius:12px; padding:1.4rem; }
    table { width:100%; border-collapse:collapse; }
    th,td { padding:.8rem; text-align:left; border-bottom:1px solid #e4e9ee; }
    .pass { color:#16734f; font-weight:700; }
    .fail { color:#a12626; font-weight:700; }
    a { color:#10253d; font-weight:700; }
  </style>
</head>
<body>
<main>
  <p><a href="/admin/materials.php">← Job Materials</a></p>
  <section class="panel">
    <h1>System Check</h1>
    <p class="<?php echo $allPassed ? 'pass' : 'fail'; ?>"><?php echo $allPassed ? 'All required checks passed.' : 'One or more required checks failed.'; ?></p>
    <table>
      <thead><tr><th>Check</th><th>Status</th><th>Details</th></tr></thead>
      <tbody>
      <?php foreach ($checks as $check): ?>
        <tr><td><?php echo htmlspecialchars($check['label'], ENT_QUOTES, 'UTF-8'); ?></td><td class="<?php echo $check['passed'] ? 'pass' : 'fail'; ?>"><?php echo $check['passed'] ? 'Pass' : 'Fail'; ?></td><td><?php echo htmlspecialchars($check['details'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>
</body>
</html>
