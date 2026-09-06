<?php
require_once __DIR__ . '/../../app/Core/db.php';

session_start();

$adminUser = $_ENV['ADMIN_USERNAME'] ?? 'admin';
$adminPass = $_ENV['ADMIN_PASSWORD'] ?? 'change-this-password';

$loggedIn = ($_SESSION['admin_logged_in'] ?? false) === true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $user = trim((string) ($_POST['username'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');

    if ($user === $adminUser && $pass === $adminPass) {
        $_SESSION['admin_logged_in'] = true;
        $loggedIn = true;
    } else {
        $error = 'Invalid admin login.';
    }
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    $loggedIn = false;
}

if (!$loggedIn && !($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login']))) {
    ?>
    <!doctype html>
    <html lang="en-CA">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Admin Login</title>
      <link rel="stylesheet" href="/assets/css/app.css">
    </head>
    <body>
      <main style="max-width: 420px; margin: 5rem auto; padding: 2rem; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08);">
        <h1>Review Admin</h1>
        <?php if (!empty($error)): ?><p style="color: #b00020;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
        <form method="post">
          <label>Username<input type="text" name="username" required></label>
          <label>Password<input type="password" name="password" required></label>
          <button type="submit" name="login" value="1">Login</button>
        </form>
      </main>
    </body>
    </html>
    <?php
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

$pdo = getPDO();
$reviews = $pdo->query('SELECT id, name, city, rating, review, approved, created_at FROM reviews ORDER BY created_at DESC')->fetchAll();
?>
<!doctype html>
<html lang="en-CA">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Review Admin</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <main style="max-width: 1100px; margin: 2rem auto; padding: 2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
      <h1>Review Admin</h1>
      <a href="/admin/reviews.php?logout=1">Logout</a>
    </div>

    <?php if (!empty($success)): ?><p style="color: green;"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
    <?php if (!empty($error)): ?><p style="color: #b00020;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>

    <section style="background:#fff; border-radius:12px; padding:1.5rem; margin-bottom:2rem; box-shadow:0 10px 30px rgba(0,0,0,0.06);">
      <h2>Add Review</h2>
      <form method="post">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem;">
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

    <section style="background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 10px 30px rgba(0,0,0,0.06);">
      <h2>Existing Reviews</h2>
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left; padding:0.75rem; border-bottom:1px solid #ddd;">Name</th>
            <th style="text-align:left; padding:0.75rem; border-bottom:1px solid #ddd;">City</th>
            <th style="text-align:left; padding:0.75rem; border-bottom:1px solid #ddd;">Rating</th>
            <th style="text-align:left; padding:0.75rem; border-bottom:1px solid #ddd;">Review</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reviews as $review): ?>
            <tr>
              <td style="padding:0.75rem; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($review['name'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td style="padding:0.75rem; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($review['city'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td style="padding:0.75rem; border-bottom:1px solid #eee;"><?php echo (int) $review['rating']; ?> / 5</td>
              <td style="padding:0.75rem; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($review['review'], ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </main>
</body>
</html>
