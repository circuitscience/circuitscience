<?php
require_once __DIR__ . '/../../app/Core/helpers.php';
require_once __DIR__ . '/../../app/Core/db.php';

session_start();

$dotenv = getDotEnv(__DIR__ . '/../../.env');
foreach ($dotenv as $key => $value) {
    if (!array_key_exists($key, $_ENV)) {
        $_ENV[$key] = $value;
    }
}

$adminSecret = trim((string) ($_ENV['ADMIN_PUBLIC_SECRET'] ?? getenv('ADMIN_PUBLIC_SECRET') ?? ''));
$providedPassword = trim((string) ($_GET['password'] ?? ''));

if ($providedPassword !== '' && $adminSecret !== '' && hash_equals($adminSecret, $providedPassword)) {
    $_SESSION['admin_logged_in'] = true;
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: /index.php');
    exit;
}

$loggedIn = ($_SESSION['admin_logged_in'] ?? false) === true;
if (!$loggedIn) {
    header('Location: /index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_review'])) {
    $name = trim((string) ($_POST['name'] ?? ''));
    $city = trim((string) ($_POST['city'] ?? ''));
    $rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
    $review = trim((string) ($_POST['review'] ?? ''));

    if ($name !== '' && $city !== '' && $review !== '') {
        $pdo = getPDO();
        $stmt = $pdo->prepare('INSERT INTO reviews (name, city, rating, review, approved, created_at) VALUES (:name, :city, :rating, :review, 1, NOW())');
        $stmt->execute([
            ':name' => $name,
            ':city' => $city,
            ':rating' => $rating,
            ':review' => $review,
        ]);
        $success = 'Review added successfully.';
    } else {
        $error = 'Name, city and review are required.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_reviewed'])) {
    $requestId = (int) ($_POST['request_id'] ?? 0);
    if ($requestId > 0) {
        $pdo = getPDO();
        $pdo->prepare('UPDATE estimate_requests SET status = :status, updated_at = NOW() WHERE id = :id')->execute([
            ':status' => 'reviewing',
            ':id' => $requestId,
        ]);
        $success = 'Request marked as reviewed.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_request'])) {
    $requestId = (int) ($_POST['request_id'] ?? 0);
    if ($requestId > 0) {
        $pdo = getPDO();
        $pdo->prepare('UPDATE estimate_requests SET archived = 1, archived_at = NOW(), updated_at = NOW() WHERE id = :request_id AND archived = 0')->execute([':request_id' => $requestId]);
        $success = 'Request archived.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_request'])) {
    $requestId = (int) ($_POST['request_id'] ?? 0);
    if ($requestId > 0) {
        $pdo = getPDO();
        $pdo->prepare('UPDATE estimate_requests SET archived = 0, archived_at = NULL, updated_at = NOW() WHERE id = :request_id')->execute([':request_id' => $requestId]);
        $success = 'Request restored to active queue.';
    }
}

$pdo = getPDO();
$reviews = $pdo->query('SELECT id, name, city, rating, review, approved, created_at FROM reviews ORDER BY created_at DESC')->fetchAll();
$requests = $pdo->query('SELECT * FROM estimate_requests WHERE archived = 0 ORDER BY created_at DESC')->fetchAll();
$archivedRequests = $pdo->query('SELECT * FROM estimate_requests WHERE archived = 1 ORDER BY archived_at DESC, created_at DESC')->fetchAll();
$attachmentsByRequest = [];
$allRequestIds = array_map('intval', array_merge(array_column($requests, 'id'), array_column($archivedRequests, 'id')));
if ($allRequestIds !== []) {
    $in = implode(',', array_fill(0, count($allRequestIds), '?'));
    $stmt = $pdo->prepare('SELECT * FROM estimate_request_attachments WHERE estimate_request_id IN (' . $in . ') ORDER BY created_at DESC');
    $stmt->execute($allRequestIds);
    foreach ($stmt->fetchAll() as $attachment) {
        $attachmentsByRequest[(int) $attachment['estimate_request_id']][] = $attachment;
    }
}
?>
<!doctype html>
<html lang="en-CA">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Review Admin</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f6f8; color: #18212d; margin: 0; }
    main { max-width: 1200px; margin: 2rem auto; padding: 1.25rem; }
    h1, h2, h3 { margin-top: 0; }
    .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .panel { background: #fff; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.06); }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
    label { display: block; font-weight: 600; margin-bottom: 0.75rem; }
    input, select, textarea, button { width: 100%; box-sizing: border-box; padding: 0.8rem 0.9rem; border: 1px solid #d7dfe6; border-radius: 8px; font: inherit; }
    textarea { min-height: 110px; }
    button { background: #0d1d2d; color: #fff; border: none; cursor: pointer; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 0.8rem; text-align: left; border-bottom: 1px solid #eaeef2; vertical-align: top; }
    .request-card { border: 1px solid #eaeef2; border-radius: 10px; padding: 1rem; margin-bottom: 1rem; background: #fbfcfd; }
    .request-meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
    .attachments { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.75rem; }
    .attachments a { display: inline-block; }
    .attachments img { width: 110px; height: 110px; object-fit: cover; border-radius: 8px; border: 1px solid #dfe6ec; }
    .badge { display: inline-block; padding: 0.35rem 0.6rem; border-radius: 999px; font-size: 12px; font-weight: 700; background: #eceff4; color: #213343; }
    .success { color: green; }
    .error { color: #b00020; }
    .muted { color: #536477; }
    a { color: #0d1d2d; }
  </style>
</head>
<body>
  <main>
    <div class="topbar">
      <h1>Admin Dashboard</h1>
      <a href="/admin/reviews.php?logout=1">Jerry, sign out</a>
    </div>

    <?php if (!empty($success)): ?><p class="success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
    <?php if (!empty($error)): ?><p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>

    <section class="panel">
      <h2>Estimate Requests</h2>
      <?php if ($requests === []): ?>
        <p class="muted">No estimate requests have been submitted yet.</p>
      <?php else: ?>
        <?php foreach ($requests as $request): ?>
          <?php $requestId = (int) $request['id']; $requestAttachments = $attachmentsByRequest[$requestId] ?? []; ?>
          <article class="request-card">
            <div class="request-meta">
              <div><strong>Name:</strong><br><?php echo htmlspecialchars((string) $request['name'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div><strong>Email:</strong><br><?php echo htmlspecialchars((string) $request['email'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div><strong>Phone:</strong><br><?php echo htmlspecialchars((string) $request['phone'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div><strong>Property:</strong><br><?php echo htmlspecialchars((string) $request['property_type'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div><strong>Preferred contact:</strong><br><?php echo htmlspecialchars((string) $request['preferred_contact'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div><strong>Status:</strong><br><span class="badge"><?php echo htmlspecialchars((string) $request['status'], ENT_QUOTES, 'UTF-8'); ?></span></div>
              <div><strong>Submitted:</strong><br><?php echo htmlspecialchars((string) $request['created_at'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <div>
              <strong>Project details</strong>
              <p><?php echo nl2br(htmlspecialchars((string) $request['details'], ENT_QUOTES, 'UTF-8')); ?></p>
            </div>

            <div style="display:flex; gap:0.75rem; margin-top:1rem; flex-wrap:wrap;">
              <form method="post">
                <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                <button type="submit" name="mark_reviewed" value="1">Mark as reviewed</button>
              </form>
              <form method="post" onsubmit="return confirm('Archive this request and keep it for record-keeping?');">
                <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                <button type="submit" name="archive_request" value="1">Archive request</button>
              </form>
            </div>

            <?php if ($requestAttachments !== []): ?>
              <div>
                <strong>Attachments</strong>
                <div class="attachments">
                  <?php foreach ($requestAttachments as $attachment): ?>
                    <?php $url = htmlspecialchars((string) $attachment['stored_path'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php $isImage = preg_match('/\.(png|gif|jpe?g|webp)$/i', (string) $attachment['stored_name']) === 1; ?>
                    <?php if ($isImage): ?>
                      <a href="<?php echo $url; ?>" target="_blank" rel="noopener"><img src="<?php echo $url; ?>" alt="<?php echo htmlspecialchars((string) $attachment['original_name'], ENT_QUOTES, 'UTF-8'); ?>"></a>
                    <?php else: ?>
                      <a href="<?php echo $url; ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars((string) $attachment['original_name'], ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <section class="panel">
      <h2>Add Review</h2>
      <form method="post">
        <div class="grid">
          <label>Name<input type="text" name="name" required></label>
          <label>City<input type="text" name="city" required></label>
          <label>Rating
            <select name="rating">
              <option value="5">5</option>
              <option value="4">4</option>
              <option value="3">3</option>
              <option value="2">2</option>
              <option value="1">1</option>
            </select>
          </label>
        </div>
        <label>Review<textarea name="review" rows="4" required></textarea></label>
        <button type="submit" name="add_review" value="1">Save review</button>
      </form>
    </section>

    <?php if ($archivedRequests !== []): ?>
    <section class="panel">
      <h2>Archived Requests</h2>
      <?php foreach ($archivedRequests as $request): ?>
        <?php $requestId = (int) $request['id']; $requestAttachments = $attachmentsByRequest[$requestId] ?? []; ?>
        <article class="request-card">
          <div class="request-meta">
            <div><strong>Name:</strong><br><?php echo htmlspecialchars((string) $request['name'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Email:</strong><br><?php echo htmlspecialchars((string) $request['email'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Phone:</strong><br><?php echo htmlspecialchars((string) $request['phone'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Archived:</strong><br><?php echo htmlspecialchars((string) ($request['archived_at'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
          <div style="display:flex; gap:0.75rem; margin-top:1rem; flex-wrap:wrap;">
            <form method="post">
              <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
              <button type="submit" name="restore_request" value="1">Restore request</button>
            </form>
          </div>
          <?php if ($requestAttachments !== []): ?>
            <div>
              <strong>Attachments</strong>
              <div class="attachments">
                <?php foreach ($requestAttachments as $attachment): ?>
                  <?php $url = htmlspecialchars((string) $attachment['stored_path'], ENT_QUOTES, 'UTF-8'); ?>
                  <?php $isImage = preg_match('/\.(png|gif|jpe?g|webp)$/i', (string) $attachment['stored_name']) === 1; ?>
                  <?php if ($isImage): ?>
                    <a href="<?php echo $url; ?>" target="_blank" rel="noopener"><img src="<?php echo $url; ?>" alt="<?php echo htmlspecialchars((string) $attachment['original_name'], ENT_QUOTES, 'UTF-8'); ?>"></a>
                  <?php else: ?>
                    <a href="<?php echo $url; ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars((string) $attachment['original_name'], ENT_QUOTES, 'UTF-8'); ?></a>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <section class="panel">
      <h2>Existing Reviews</h2>
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>City</th>
            <th>Rating</th>
            <th>Review</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reviews as $review): ?>
            <tr>
              <td><?php echo htmlspecialchars($review['name'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($review['city'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo (int) $review['rating']; ?> / 5</td>
              <td><?php echo htmlspecialchars($review['review'], ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </main>
</body>
</html>
