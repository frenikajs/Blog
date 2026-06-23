<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $id = (int)($_POST['post_id'] ?? 0);
        if ($id) {
            db()->prepare('DELETE FROM post_categories WHERE post_id = ?')->execute([$id]);
            db()->prepare('DELETE FROM post_tags WHERE post_id = ?')->execute([$id]);
            db()->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
        }
    }
    header('Location: ' . SITE_URL . '/admin/posts.php?deleted=1');
    exit;
}

$statusFilter = $_GET['status'] ?? 'all';
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

$where  = $statusFilter !== 'all' ? 'WHERE p.status = ?' : '';
$params = $statusFilter !== 'all' ? [$statusFilter] : [];

$total = (int)db()->prepare("SELECT COUNT(*) FROM posts p $where")->execute($params) ? 0 : 0;
$countStmt = db()->prepare("SELECT COUNT(*) FROM posts p $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$params[] = $perPage; $params[] = $offset;
$stmt = db()->prepare("SELECT p.*, u.display_name as author_name
    FROM posts p LEFT JOIN admin_user u ON u.id = p.author_id
    $where ORDER BY p.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute($params);
$posts = $stmt->fetchAll();

$pageTitle = 'Posts';
require __DIR__ . '/partials/head.php';
?>

<div class="admin-wrap">
  <?php require __DIR__ . '/partials/sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-header">
      <h1>Posts</h1>
      <a href="<?= SITE_URL ?>/admin/edit-post.php" class="btn btn-primary">+ New Post</a>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
      <div class="notice notice-success">Post deleted.</div>
    <?php endif; ?>
    <?php if (isset($_GET['saved'])): ?>
      <div class="notice notice-success">Post saved successfully.</div>
    <?php endif; ?>

    <div class="filter-bar">
      <a href="?status=all"       class="<?= $statusFilter === 'all'       ? 'active' : '' ?>">All</a>
      <a href="?status=published" class="<?= $statusFilter === 'published' ? 'active' : '' ?>">Published</a>
      <a href="?status=draft"     class="<?= $statusFilter === 'draft'     ? 'active' : '' ?>">Drafts</a>
    </div>

    <table class="admin-table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Status</th>
          <th>Categories</th>
          <th>Published</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($posts as $p):
          $cats = get_post_categories($p['id']);
        ?>
        <tr>
          <td>
            <strong><?= h($p['title']) ?></strong>
            <br><small class="muted"><?= h($p['slug']) ?></small>
          </td>
          <td><span class="badge badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
          <td><?= implode(', ', array_map(fn($c) => h($c['name']), $cats)) ?></td>
          <td><?= $p['published_at'] ? format_date($p['published_at'], 'M j, Y') : '—' ?></td>
          <td class="actions">
            <a href="<?= SITE_URL ?>/admin/edit-post.php?id=<?= $p['id'] ?>">Edit</a>
            <?php if ($p['status'] === 'published'): ?>
              &middot; <a href="<?= SITE_URL . '/' . h($p['slug']) ?>" target="_blank">View</a>
            <?php endif; ?>
            &middot;
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this post?')">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
              <button type="submit" class="btn-link danger">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($posts)): ?>
          <tr><td colspan="5" style="text-align:center;padding:2rem;color:#999">No posts found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <?= pagination_links($page, $total, $perPage, SITE_URL . '/admin/posts.php?status=' . $statusFilter) ?>

  </main>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
