<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . SITE_URL . '/admin/');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        if (login($_POST['password'] ?? '')) {
            header('Location: ' . SITE_URL . '/admin/');
            exit;
        } else {
            $error = 'Incorrect password.';
            sleep(1); // Slow brute force
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — <?= htmlspecialchars(SITE_NAME) ?></title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="login-page">
<div class="login-card">
  <h1>Admin Login</h1>
  <p class="login-sub"><?= htmlspecialchars(SITE_NAME) ?></p>

  <?php if ($error): ?>
    <div class="notice notice-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="form-group">
      <label for="password">Password</label>
      <input id="password" type="password" name="password" autofocus required class="form-control">
    </div>
    <button type="submit" class="btn btn-primary btn-block">Log In</button>
  </form>
</div>
</body>
</html>
