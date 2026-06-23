<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$db     = db();
$notice = '';
$errors = [];

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    if (!empty($_FILES['media_file']['name'])) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($_FILES['media_file']['tmp_name']);

        if (!in_array($mime, $allowed)) {
            $errors[] = 'File type not allowed.';
        } elseif ($_FILES['media_file']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'File is too large (max 5 MB).';
        } else {
            $ext  = pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION);
            $dir  = UPLOADS_DIR . '/' . date('Y/m');
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $name = bin2hex(random_bytes(8)) . '.' . strtolower($ext);
            $dest = $dir . '/' . $name;
            if (move_uploaded_file($_FILES['media_file']['tmp_name'], $dest)) {
                $path = 'uploads/' . date('Y/m') . '/' . $name;
                $db->prepare('INSERT INTO media (filename, original_name, mime_type, file_size, path) VALUES (?, ?, ?, ?, ?)')
                   ->execute([$name, $_FILES['media_file']['name'], $mime, $_FILES['media_file']['size'], $path]);
                $notice = 'File uploaded successfully.';
            } else {
                $errors[] = 'Upload failed. Check folder permissions.';
            }
        }
    }

    if (($_POST['action'] ?? '') === 'delete') {
        $id   = (int)($_POST['media_id'] ?? 0);
        $row  = $db->prepare('SELECT path FROM media WHERE id = ?');
        $row->execute([$id]);
        $file = $row->fetch();
        if ($file) {
            $abs = BASE_PATH . '/' . $file['path'];
            if (file_exists($abs)) unlink($abs);
            $db->prepare('DELETE FROM media WHERE id = ?')->execute([$id]);
            $notice = 'File deleted.';
        }
    }
}

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 24;
$offset  = ($page - 1) * $perPage;
$total   = (int)$db->query('SELECT COUNT(*) FROM media')->fetchColumn();
$files   = $db->prepare('SELECT * FROM media ORDER BY created_at DESC LIMIT ? OFFSET ?');
$files->execute([$perPage, $offset]);
$files   = $files->fetchAll();

$pageTitle = 'Media Library';
require __DIR__ . '/partials/head.php';
?>

<div class="admin-wrap">
  <?php require __DIR__ . '/partials/sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-header"><h1>Media Library</h1></div>

    <?php if ($notice): ?><div class="notice notice-success"><?= h($notice) ?></div><?php endif; ?>
    <?php foreach ($errors as $e): ?><div class="notice notice-error"><?= h($e) ?></div><?php endforeach; ?>

    <form method="post" enctype="multipart/form-data" class="upload-form">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="upload-zone">
        <input type="file" name="media_file" id="media_file" accept="image/*" required>
        <label for="media_file">Click to choose an image, or drop it here</label>
        <small class="muted">JPEG, PNG, GIF, WebP — max 5 MB</small>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:.75rem">Upload</button>
    </form>

    <div class="media-grid">
      <?php foreach ($files as $f): ?>
      <div class="media-item">
        <div class="media-thumb">
          <img src="<?= SITE_URL . '/' . h($f['path']) ?>" alt="<?= h($f['original_name']) ?>" loading="lazy">
        </div>
        <div class="media-info">
          <p class="media-name" title="<?= h($f['original_name']) ?>"><?= h($f['original_name']) ?></p>
          <p class="media-url">
            <input type="text" value="<?= SITE_URL . '/' . h($f['path']) ?>" readonly onclick="this.select()" class="form-control">
          </p>
        </div>
        <form method="post" onsubmit="return confirm('Delete this file?')">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="media_id" value="<?= $f['id'] ?>">
          <button type="submit" class="btn-link danger">Delete</button>
        </form>
      </div>
      <?php endforeach; ?>
      <?php if (empty($files)): ?>
        <p class="muted" style="padding:2rem">No media uploaded yet.</p>
      <?php endif; ?>
    </div>

    <?= pagination_links($page, $total, $perPage, SITE_URL . '/admin/media.php') ?>

  </main>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
