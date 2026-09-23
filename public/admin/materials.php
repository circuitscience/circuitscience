<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/Core/db.php';
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

if (($_SESSION['admin_logged_in'] ?? false) !== true) {
    header('Location: /admin/login.php');
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

function materialPageRedirect(string $message): void
{
    $_SESSION['admin_flash'] = $message;
    header('Location: /admin/materials.php', true, 303);
    exit;
}

function materialValue(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function discoverProductPhotos(string $directory): array
{
    if (!is_dir($directory) || !is_readable($directory)) {
        return [];
    }

    $allowedExtensions = ['gif', 'jpg', 'jpeg', 'png', 'webp'];
    $photos = [];
    foreach (scandir($directory) ?: [] as $fileName) {
        if ($fileName === '.' || $fileName === '..' || !is_file($directory . DIRECTORY_SEPARATOR . $fileName)) {
            continue;
        }

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            continue;
        }

        $webPath = '/admin/assets/product_pics/' . rawurlencode($fileName);
        $photos[$webPath] = $fileName;
    }

    natcasesort($photos);
    return $photos;
}

$productPhotos = discoverProductPhotos(__DIR__ . '/assets/product_pics');

try {
    $pdo = getPDO();
    $categories = $pdo->query('SELECT slug, name FROM material_categories WHERE is_active = 1 ORDER BY name')->fetchAll();
    $units = $pdo->query('SELECT code, name FROM material_units WHERE is_active = 1 ORDER BY name')->fetchAll();
    $manufacturers = $pdo->query('SELECT id, name FROM material_manufacturers WHERE is_active = 1 ORDER BY name')->fetchAll();
    $pdo->query('SELECT id, subcategory FROM job_materials LIMIT 1');
} catch (Throwable $error) {
    error_log('Materials page database error: ' . $error->getMessage());
    http_response_code(503);
    exit('The materials catalog is not ready. Import database/add_job_materials.sql and verify the database connection.');
}
$categoryMap = array_column($categories, 'name', 'slug');
$unitMap = array_column($units, 'name', 'code');
$manufacturerMap = array_column($manufacturers, 'id', 'name');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) $_SESSION['admin_csrf_token'], $csrfToken)) {
        http_response_code(403);
        exit('The form session expired. Return to the materials page and try again.');
    }

    if (isset($_POST['add_manufacturer'])) {
        $newManufacturer = materialValue('new_manufacturer');
        if ($newManufacturer === '' || strlen($newManufacturer) > 120) {
            $error = 'Enter a manufacturer name of no more than 120 characters.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO material_manufacturers (name, is_active) VALUES (:name, 1) ON DUPLICATE KEY UPDATE is_active = 1, updated_at = NOW()');
            $stmt->execute([':name' => $newManufacturer]);
            materialPageRedirect('Manufacturer added to the material list.');
        }
    }

    if (isset($_POST['deactivate_material'], $_POST['material_id'])) {
        $materialId = (int) $_POST['material_id'];
        if ($materialId > 0) {
            $stmt = $pdo->prepare('UPDATE job_materials SET is_active = 0, updated_at = NOW() WHERE id = :id');
            $stmt->execute([':id' => $materialId]);
            materialPageRedirect('Material removed from the active catalog.');
        }
    }

    if (isset($_POST['reactivate_material'], $_POST['material_id'])) {
        $materialId = (int) $_POST['material_id'];
        if ($materialId > 0) {
            $stmt = $pdo->prepare('UPDATE job_materials SET is_active = 1, updated_at = NOW() WHERE id = :id');
            $stmt->execute([':id' => $materialId]);
            materialPageRedirect('Material restored to the active catalog.');
        }
    }

    if (isset($_POST['save_material'])) {
        $materialId = (int) ($_POST['material_id'] ?? 0);
        $name = materialValue('name');
        $category = materialValue('category');
        $subcategory = materialValue('subcategory');
        $description = materialValue('description');
        $manufacturer = materialValue('manufacturer');
        $unit = materialValue('unit');
        $otherInfo = materialValue('other_info');
        $imageUrl = materialValue('image_url');
        $priceInput = materialValue('price', '0');

        if ($name === '' || strlen($name) > 180) {
            $error = 'Enter a material name of no more than 180 characters.';
        } elseif (!isset($categoryMap[$category])) {
            $error = 'Select a valid material category.';
        } elseif (strlen($subcategory) > 100) {
            $error = 'Enter a subcategory of no more than 100 characters.';
        } elseif ($manufacturer !== '' && !isset($manufacturerMap[$manufacturer])) {
            $error = 'Select a valid manufacturer.';
        } elseif (!isset($unitMap[$unit])) {
            $error = 'Select a valid unit.';
        } elseif (!is_numeric($priceInput) || (float) $priceInput < 0 || (float) $priceInput > 99999999.99) {
            $error = 'Enter a valid non-negative price.';
        } elseif ($imageUrl !== '' && !isset($productPhotos[$imageUrl])) {
            $error = 'Select a product photo from admin/assets/product_pics.';
        } else {
            $values = [
                ':name' => $name,
                ':category' => $category,
                ':subcategory' => $subcategory !== '' ? $subcategory : null,
                ':description' => $description !== '' ? $description : null,
                ':manufacturer' => $manufacturer !== '' ? $manufacturer : null,
                ':unit' => $unit,
                ':other_info' => $otherInfo !== '' ? $otherInfo : null,
                ':image_url' => $imageUrl !== '' ? $imageUrl : null,
                ':price' => number_format((float) $priceInput, 2, '.', ''),
            ];

            if ($materialId > 0) {
                $values[':id'] = $materialId;
                $stmt = $pdo->prepare('UPDATE job_materials SET name = :name, category = :category, subcategory = :subcategory, description = :description, manufacturer = :manufacturer, unit = :unit, other_info = :other_info, image_url = :image_url, price = :price, updated_at = NOW() WHERE id = :id');
                $stmt->execute($values);
                materialPageRedirect('Material updated.');
            }

            $stmt = $pdo->prepare('INSERT INTO job_materials (name, category, subcategory, description, manufacturer, unit, other_info, image_url, price) VALUES (:name, :category, :subcategory, :description, :manufacturer, :unit, :other_info, :image_url, :price)');
            $stmt->execute($values);
            materialPageRedirect('Material added to the catalog.');
        }
    }
}

$flash = (string) ($_SESSION['admin_flash'] ?? '');
unset($_SESSION['admin_flash']);

$editMaterial = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM job_materials WHERE id = :id');
    $stmt->execute([':id' => $editId]);
    $editMaterial = $stmt->fetch() ?: null;
}

$filterCategory = trim((string) ($_GET['category'] ?? ''));
$showInactive = ($_GET['status'] ?? '') === 'all';
$where = $showInactive ? [] : ['jm.is_active = 1'];
$params = [];
if ($filterCategory !== '' && isset($categoryMap[$filterCategory])) {
    $where[] = 'jm.category = :category';
    $params[':category'] = $filterCategory;
}

$sql = 'SELECT jm.*, mc.name AS category_name, mu.name AS unit_name
        FROM job_materials jm
        JOIN material_categories mc ON mc.slug = jm.category
        JOIN material_units mu ON mu.code = jm.unit';
if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY mc.name, jm.name';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$materials = $stmt->fetchAll();

$form = $editMaterial ?: [];
if ($error !== '' && isset($_POST['save_material'])) {
    $form = $_POST;
}
?>
<!doctype html>
<html lang="en-CA">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Job Materials | Circuit Science Admin</title>
  <style>
    :root { --navy:#10253d; --gold:#dca93d; --bg:#f4f6f8; --panel:#fff; --border:#dfe7ee; --muted:#5d6f7c; --danger:#a12626; }
    * { box-sizing:border-box; }
    body { margin:0; font-family:Arial,sans-serif; color:#112434; background:var(--bg); }
    .shell { width:min(1200px, calc(100% - 2rem)); margin:0 auto; padding:1.5rem 0 3rem; }
    .topbar { display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1.5rem; }
    nav { display:flex; gap:.5rem; flex-wrap:wrap; }
    nav a, .button-link { color:var(--navy); background:#e7edf6; border:1px solid var(--border); border-radius:8px; padding:.65rem .85rem; text-decoration:none; font-weight:700; }
    .panel { background:var(--panel); border:1px solid var(--border); border-radius:12px; padding:1.25rem; margin-bottom:1.25rem; box-shadow:0 8px 18px rgba(17,36,52,.04); }
    .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1rem; }
    label { display:block; font-weight:700; }
    input, select, textarea, button { width:100%; margin-top:.35rem; padding:.75rem; border:1px solid #cad5df; border-radius:8px; font:inherit; }
    textarea { min-height:100px; resize:vertical; }
    button { border:0; background:var(--navy); color:#fff; font-weight:700; cursor:pointer; }
    .danger { background:var(--danger); }
    .secondary { background:#536477; }
    .message { border-left:4px solid #1b7f5a; background:#effaf5; padding:.8rem 1rem; }
    .error { border-left-color:var(--danger); background:#fff2f2; color:#7b1c1c; }
    .filters { display:flex; gap:.75rem; align-items:end; flex-wrap:wrap; }
    .filters label { min-width:220px; }
    .filters button { width:auto; }
    table { width:100%; border-collapse:collapse; }
    th, td { text-align:left; vertical-align:top; padding:.75rem; border-bottom:1px solid #e6ebef; }
    .actions { display:flex; gap:.4rem; min-width:180px; }
    .actions form { flex:1; }
    .actions a, .actions button { display:block; width:100%; text-align:center; padding:.55rem; margin:0; border-radius:7px; text-decoration:none; font-size:.9rem; }
    .muted { color:var(--muted); }
    .inactive { opacity:.58; }
    .photo-preview { display:block; width:110px; height:110px; margin-top:.6rem; object-fit:contain; border:1px solid var(--border); border-radius:8px; background:#fff; }
    .catalog-thumb { display:block; width:72px; height:72px; object-fit:contain; border:1px solid var(--border); border-radius:8px; background:#fff; }
    @media (max-width:760px) { .topbar { align-items:flex-start; flex-direction:column; } table,thead,tbody,tr,th,td { display:block; } thead { display:none; } td { padding:.45rem 0; border:0; } td::before { content:attr(data-label); display:block; margin-bottom:.25rem; color:var(--muted); font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; } tr { padding:1rem 0; border-bottom:1px solid var(--border); } }
  </style>
</head>
<body>
<main class="shell">
  <div class="topbar">
    <div><p class="muted" style="margin:0;">Catalog administration</p><h1 style="margin:.2rem 0 0;">Job Materials</h1></div>
    <nav><a href="/admin/index.php">Overview</a><a href="/admin/reviews.php">Operations</a><a href="/admin/materials.php">Materials</a></nav>
  </div>

  <?php if ($flash !== ''): ?><p class="message"><?php echo escape($flash); ?></p><?php endif; ?>
  <?php if ($error !== ''): ?><p class="message error"><?php echo escape($error); ?></p><?php endif; ?>

  <section class="panel">
    <h2><?php echo $editMaterial ? 'Edit material' : 'Add a material'; ?></h2>
    <p class="muted">Add materials only when they are needed for a job. Prices can be overridden later on an estimate or invoice.</p>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?php echo escape((string) $_SESSION['admin_csrf_token']); ?>">
      <input type="hidden" name="material_id" value="<?php echo (int) ($form['id'] ?? $form['material_id'] ?? 0); ?>">
      <div class="grid">
        <label>Name<input name="name" maxlength="180" required value="<?php echo escape((string) ($form['name'] ?? '')); ?>"></label>
        <label>Category<select name="category" required><option value="">Select category</option><?php foreach ($categories as $category): ?><option value="<?php echo escape($category['slug']); ?>" <?php echo (($form['category'] ?? '') === $category['slug']) ? 'selected' : ''; ?>><?php echo escape($category['name']); ?></option><?php endforeach; ?></select></label>
        <label>Subcategory<input name="subcategory" maxlength="100" value="<?php echo escape((string) ($form['subcategory'] ?? '')); ?>" placeholder="e.g. Breakers"></label>
        <label>Manufacturer
          <select name="manufacturer">
            <option value="">No manufacturer</option>
            <?php foreach ($manufacturers as $manufacturer): ?>
              <option value="<?php echo escape($manufacturer['name']); ?>" <?php echo (($form['manufacturer'] ?? '') === $manufacturer['name']) ? 'selected' : ''; ?>><?php echo escape($manufacturer['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Unit<select name="unit" required><option value="">Select unit</option><?php foreach ($units as $unit): ?><option value="<?php echo escape($unit['code']); ?>" <?php echo (($form['unit'] ?? '') === $unit['code']) ? 'selected' : ''; ?>><?php echo escape($unit['name']); ?></option><?php endforeach; ?></select></label>
        <label>Price<input type="number" name="price" min="0" max="99999999.99" step="0.01" required value="<?php echo escape((string) ($form['price'] ?? '0.00')); ?>"></label>
        <label>Product photo
          <?php $selectedPhoto = (string) ($form['image_url'] ?? ''); ?>
          <select name="image_url" id="image-url">
            <option value="">No photo</option>
            <?php foreach ($productPhotos as $photoPath => $photoName): ?>
              <option value="<?php echo escape($photoPath); ?>" <?php echo $selectedPhoto === $photoPath ? 'selected' : ''; ?>><?php echo escape($photoName); ?></option>
            <?php endforeach; ?>
          </select>
          <small class="muted">Images are read from public/admin/assets/product_pics.</small>
          <?php if ($selectedPhoto !== ''): ?><img class="photo-preview" id="photo-preview" src="<?php echo escape($selectedPhoto); ?>" alt="Selected product photo"><?php else: ?><img class="photo-preview" id="photo-preview" alt="" hidden><?php endif; ?>
        </label>
      </div>
      <label>Description<textarea name="description"><?php echo escape((string) ($form['description'] ?? '')); ?></textarea></label>
      <label>Other information<textarea name="other_info"><?php echo escape((string) ($form['other_info'] ?? '')); ?></textarea></label>
      <div class="actions" style="margin-top:1rem;max-width:360px;">
        <button type="submit" name="save_material" value="1"><?php echo $editMaterial ? 'Save changes' : 'Add material'; ?></button>
        <?php if ($editMaterial): ?><a class="button-link" href="/admin/materials.php">Cancel</a><?php endif; ?>
      </div>
    </form>
  </section>

  <section class="panel">
    <h2>Add a manufacturer</h2>
    <p class="muted"><?php echo count($manufacturers); ?> active manufacturer(s). Existing names are reused rather than duplicated.</p>
    <form method="post" class="filters">
      <input type="hidden" name="csrf_token" value="<?php echo escape((string) $_SESSION['admin_csrf_token']); ?>">
      <label>Manufacturer name<input name="new_manufacturer" maxlength="120" required></label>
      <button type="submit" name="add_manufacturer" value="1">Add manufacturer</button>
    </form>
  </section>

  <section class="panel">
    <div style="display:flex;justify-content:space-between;gap:1rem;align-items:start;flex-wrap:wrap;">
      <div><h2 style="margin-bottom:.3rem;">Material catalog</h2><p class="muted" style="margin-top:0;"><?php echo count($materials); ?> item(s) shown · <?php echo count($productPhotos); ?> product photo(s) available</p></div>
      <form method="get" class="filters">
        <label>Category<select name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?php echo escape($category['slug']); ?>" <?php echo $filterCategory === $category['slug'] ? 'selected' : ''; ?>><?php echo escape($category['name']); ?></option><?php endforeach; ?></select></label>
        <label>Status<select name="status"><option value="active">Active only</option><option value="all" <?php echo $showInactive ? 'selected' : ''; ?>>Include inactive</option></select></label>
        <button type="submit">Filter</button>
      </form>
    </div>
    <?php if ($materials === []): ?>
      <p>No materials match this view.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Photo</th><th>Material</th><th>Category</th><th>Unit</th><th>Price</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($materials as $material): ?>
          <tr class="<?php echo (int) $material['is_active'] === 1 ? '' : 'inactive'; ?>">
            <td data-label="Photo"><?php if ($material['image_url']): ?><a href="<?php echo escape($material['image_url']); ?>" target="_blank" rel="noopener noreferrer"><img class="catalog-thumb" src="<?php echo escape($material['image_url']); ?>" alt="<?php echo escape($material['name']); ?>"></a><?php else: ?><span class="muted">No photo assigned</span><?php endif; ?></td>
            <td data-label="Material"><strong><?php echo escape($material['name']); ?></strong><?php if ($material['manufacturer']): ?><br><span class="muted"><?php echo escape($material['manufacturer']); ?></span><?php endif; ?><?php if ($material['description']): ?><br><small><?php echo escape($material['description']); ?></small><?php endif; ?></td>
            <td data-label="Category"><?php echo escape($material['category_name']); ?><?php if ($material['subcategory']): ?><br><small class="muted"><?php echo escape($material['subcategory']); ?></small><?php endif; ?></td>
            <td data-label="Unit"><?php echo escape($material['unit_name']); ?></td>
            <td data-label="Price">$<?php echo number_format((float) $material['price'], 2); ?></td>
            <td data-label="Actions"><div class="actions"><a class="button-link" href="/admin/materials.php?edit=<?php echo (int) $material['id']; ?>">Edit</a><form method="post"><input type="hidden" name="csrf_token" value="<?php echo escape((string) $_SESSION['admin_csrf_token']); ?>"><input type="hidden" name="material_id" value="<?php echo (int) $material['id']; ?>"><?php if ((int) $material['is_active'] === 1): ?><button class="danger" name="deactivate_material" value="1">Deactivate</button><?php else: ?><button class="secondary" name="reactivate_material" value="1">Restore</button><?php endif; ?></form></div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
</main>
<script>
  const photoSelect = document.getElementById('image-url');
  const photoPreview = document.getElementById('photo-preview');
  if (photoSelect && photoPreview) {
    photoSelect.addEventListener('change', () => {
      photoPreview.src = photoSelect.value;
      photoPreview.hidden = photoSelect.value === '';
    });
  }
</script>
</body>
</html>
