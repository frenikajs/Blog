<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$db     = db();
$notice = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_category') {
        $name = trim($_POST['cat_name'] ?? '');
        $desc = trim($_POST['cat_description'] ?? '');
        if (!$name) { $errors[] = 'Category name required.'; }
        else {
            $slug = unique_slug($name);
            $db->prepare('INSERT IGNORE INTO categories (name, slug, description) VALUES (?, ?, ?)')
               ->execute([$name, $slug, $desc]);
            $notice = 'Category added.';
        }
    }

    if ($action === 'delete_category') {
        $id = (int)($_POST['cat_id'] ?? 0);
        if ($id) {
            $db->prepare('DELETE FROM post_categories WHERE category_id = ?')->execute([$id]);
            $db->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            $notice = 'Category deleted.';
        }
    }

    if ($action === 'add_tag') {
        $name = trim($_POST['tag_name'] ?? '');
        if (!$name) { $errors[] = 'Tag name required.'; }
        else {
            $slug = slugify($name);
            $db->prepare('INSERT IGNORE INTO tags (name, slug) VALUES (?, ?)')->execute([$name, $slug]);
            $notice = 'Tag added.';
        }
    }

    if ($action === 'delete_tag') {
        $id = (int)($_POST['tag_id'] ?? 0);
        if ($id) {
            $db->prepare('DELETE FROM post_tags WHERE tag_id = ?')->execute([$id]);
            $db->prepare('DELETE FROM tags WHERE id = ?')->execute([$id]);
            $notice = 'Tag deleted.';
        }
    }
}

$categories = get_all_categories();
$tags       = get_all_tags();
$pageTitle  = 'Categories & Tags';
require __DIR__ . '/partials/head.php';
?>

<div class="admin-wrap">
  <?php require __DIR__ . '/partials/sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-header"><h1>Categories &amp; Tags</h1></div>

    <?php if ($notice): ?><div class="notice notice-success"><?= h($notice) ?></div><?php endif; ?>
    <?php foreach ($errors as $e): ?><div class="notice notice-error"><?= h($e) ?></div><?php endforeach; ?>

    <div class="two-col-layout">
      <!-- Categories -->
      <div>
        <h2>Categories</h2>
        <form method="post" class="add-form">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="add_category">
          <div class="form-group">
            <label>Name</label>
            <input type="text" name="cat_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Description <small class="muted">(optional)</small></label>
            <input type="text" name="cat_description" class="form-control">
          </div>
          <button type="submit" class="btn btn-primary">Add Category</button>
        </form>

        <table class="admin-table" style="margin-top:1.5rem">
          <thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($categories as $cat): ?>
            <tr>
              <td><?= h($cat['name']) ?></td>
              <td><code><?= h($cat['slug']) ?></code></td>
              <td><?= $cat['post_count'] ?></td>
              <td>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete category?')">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="action" value="delete_category">
                  <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                  <button type="submit" class="btn-link danger">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Tags -->
      <div>
        <h2>Tags</h2>
        <form method="post" class="add-form">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="add_tag">
          <div class="form-group">
            <label>Name</label>
            <input type="text" name="tag_name" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary">Add Tag</button>
        </form>

        <table class="admin-table" style="margin-top:1.5rem">
          <thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($tags as $tag): ?>
            <tr>
              <td><?= h($tag['name']) ?></td>
              <td><code><?= h($tag['slug']) ?></code></td>
              <td><?= $tag['post_count'] ?></td>
              <td>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete tag?')">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="action" value="delete_tag">
                  <input type="hidden" name="tag_id" value="<?= $tag['id'] ?>">
                  <button type="submit" class="btn-link danger">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
