<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$db     = db();
$notice = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? 'general';

    if ($action === 'general') {
        set_setting('site_name',        trim($_POST['site_name'] ?? ''));
        set_setting('site_description', trim($_POST['site_description'] ?? ''));
        set_setting('tagline',          trim($_POST['tagline'] ?? ''));
        set_setting('twitter_handle',   trim($_POST['twitter_handle'] ?? ''));
        set_setting('google_analytics', trim($_POST['google_analytics'] ?? ''));

        // OG image upload
        if (!empty($_FILES['og_image']['name'])) {
            $uploaded = handle_image_upload('og_image');
            if ($uploaded) set_setting('default_og_image', SITE_URL . '/' . $uploaded);
        }

        // Display name
        $dname = trim($_POST['display_name'] ?? '');
        if ($dname) {
            $db->prepare('UPDATE admin_user SET display_name = ? WHERE id = 1')->execute([$dname]);
        }

        $notice = 'Settings saved.';
    }

    if ($action === 'password') {
        $newPass    = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';
        if (strlen($newPass) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif ($newPass !== $confirmPass) {
            $errors[] = 'Passwords do not match.';
        } else {
            $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare('UPDATE settings SET password_hash = ? WHERE name = "admin_password"')->execute([$hash]);
            $notice = 'Password changed successfully.';
        }
    }
}

$adminUser = $db->query('SELECT * FROM admin_user WHERE id = 1')->fetch();
$pageTitle = 'Settings';
require __DIR__ . '/partials/head.php';
?>

<div class="admin-wrap">
  <?php require __DIR__ . '/partials/sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-header"><h1>Settings</h1></div>

    <?php if ($notice): ?><div class="notice notice-success"><?= h($notice) ?></div><?php endif; ?>
    <?php foreach ($errors as $e): ?><div class="notice notice-error"><?= h($e) ?></div><?php endforeach; ?>

    <!-- General settings -->
    <div class="meta-box" style="max-width:640px">
      <h2 class="meta-box-title">General</h2>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="general">

        <div class="form-group">
          <label>Site Name</label>
          <input type="text" name="site_name" class="form-control" value="<?= h(get_setting('site_name', SITE_NAME)) ?>">
        </div>
        <div class="form-group">
          <label>Tagline</label>
          <input type="text" name="tagline" class="form-control" value="<?= h(get_setting('tagline')) ?>" placeholder="A short site description shown in header">
        </div>
        <div class="form-group">
          <label>Site Description <small class="muted">(used in RSS, footer, default meta)</small></label>
          <textarea name="site_description" class="form-control" rows="3"><?= h(get_setting('site_description', SITE_DESCRIPTION)) ?></textarea>
        </div>
        <div class="form-group">
          <label>Your Display Name</label>
          <input type="text" name="display_name" class="form-control" value="<?= h($adminUser['display_name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Twitter/X Handle <small class="muted">(e.g. @handle)</small></label>
          <input type="text" name="twitter_handle" class="form-control" value="<?= h(get_setting('twitter_handle')) ?>">
        </div>
        <div class="form-group">
          <label>Google Analytics ID <small class="muted">(e.g. G-XXXXXXXXXX)</small></label>
          <input type="text" name="google_analytics" class="form-control" value="<?= h(get_setting('google_analytics')) ?>">
        </div>
        <div class="form-group">
          <label>Default OG Image <small class="muted">(used when post has no featured image)</small></label>
          <?php $ogImg = get_setting('default_og_image'); if ($ogImg): ?>
            <img src="<?= h($ogImg) ?>" alt="" style="max-width:200px;margin-bottom:.5rem;display:block;border-radius:4px">
          <?php endif; ?>
          <input type="file" name="og_image" accept="image/*" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">Save Settings</button>
      </form>
    </div>

    <!-- Change password -->
    <div class="meta-box" style="max-width:400px;margin-top:2rem">
      <h2 class="meta-box-title">Change Password</h2>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="password">
        <div class="form-group">
          <label>New Password</label>
          <input type="password" name="new_password" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Change Password</button>
      </form>
    </div>

    <!-- Site tools -->
    <div class="meta-box" style="max-width:400px;margin-top:2rem">
      <h2 class="meta-box-title">Site Tools</h2>
      <ul style="list-style:none;line-height:2">
        <li><a href="<?= SITE_URL ?>/sitemap.xml" target="_blank">View Sitemap</a></li>
        <li><a href="<?= SITE_URL ?>/feed" target="_blank">View RSS Feed</a></li>
        <li><a href="<?= SITE_URL ?>/robots.txt" target="_blank">View robots.txt</a></li>
        <li><a href="https://search.google.com/search-console" target="_blank" rel="noopener">Google Search Console</a></li>
        <li><a href="https://www.bing.com/webmasters" target="_blank" rel="noopener">Bing Webmaster Tools</a></li>
      </ul>
    </div>

  </main>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
