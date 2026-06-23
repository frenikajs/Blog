<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$db = db();

$totalPublished = (int)$db->query('SELECT COUNT(*) FROM posts WHERE status = "published"')->fetchColumn();
$totalDrafts    = (int)$db->query('SELECT COUNT(*) FROM posts WHERE status = "draft"')->fetchColumn();
$totalCats      = (int)$db->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$totalTags      = (int)$db->query('SELECT COUNT(*) FROM tags')->fetchColumn();

$recentPosts = $db->query('SELECT * FROM posts ORDER BY created_at DESC LIMIT 8')->fetchAll();

require __DIR__ . '/partials/head.php';
?>

<div class="admin-wrap">
  <?php require __DIR__ . '/partials/sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-header">
      <h1>Dashboard</h1>
      <a href="<?= SITE_URL ?>/admin/edit-post.php" class="btn btn-primary">+ New Post</a>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <span class="stat-number"><?= $totalPublished ?></span>
        <span class="stat-label">Published Posts</span>
      </div>
      <div class="stat-card">
        <span class="stat-number"><?= $totalDrafts ?></span>
        <span class="stat-label">Drafts</span>
      </div>
      <div class="stat-card">
        <span class="stat-number"><?= $totalCats ?></span>
        <span class="stat-label">Categories</span>
      </div>
      <div class="stat-card">
        <span class="stat-number"><?= $totalTags ?></span>
        <span class="stat-label">Tags</span>
      </div>
    </div>

    <div class="admin-section">
      <div class="section-header">
        <h2>Recent Posts</h2>
        <a href="<?= SITE_URL ?>/admin/posts.php">View all</a>
      </div>
      <table class="admin-table">
        <thead><tr><th>Title</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($recentPosts as $p): ?>
          <tr>
            <td><strong><?= h($p['title']) ?></strong></td>
            <td><span class="badge badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
            <td><?= format_date($p['created_at'], 'M j, Y') ?></td>
            <td>
              <a href="<?= SITE_URL ?>/admin/edit-post.php?id=<?= $p['id'] ?>">Edit</a>
              <?php if ($p['status'] === 'published'): ?>
                &middot; <a href="<?= SITE_URL . '/' . h($p['slug']) ?>" target="_blank">View</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="admin-section">
      <h2>Quick Links</h2>
      <div class="quick-links">
        <a href="<?= SITE_URL ?>/admin/edit-post.php" class="quick-link">✏️ New Post</a>
        <a href="<?= SITE_URL ?>/admin/posts.php" class="quick-link">📝 All Posts</a>
        <a href="<?= SITE_URL ?>/admin/categories.php" class="quick-link">🗂 Categories</a>
        <a href="<?= SITE_URL ?>/admin/media.php" class="quick-link">🖼 Media</a>
        <a href="<?= SITE_URL ?>/admin/settings.php" class="quick-link">⚙️ Settings</a>
        <a href="<?= SITE_URL ?>" target="_blank" class="quick-link">🌐 View Blog</a>
      </div>
    </div>

  </main>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
