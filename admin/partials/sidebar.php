<nav class="admin-sidebar">
  <div class="admin-logo">
    <a href="<?= SITE_URL ?>/admin/"><?= h(SITE_NAME) ?></a>
    <small>Admin</small>
  </div>
  <ul class="admin-nav">
    <li class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' && dirname($_SERVER['PHP_SELF']) !== '/admin/edit-post.php' ? 'active' : '' ?>">
      <a href="<?= SITE_URL ?>/admin/">Dashboard</a>
    </li>
    <li class="nav-section">Content</li>
    <li class="<?= in_array(basename($_SERVER['PHP_SELF']), ['posts.php']) ? 'active' : '' ?>">
      <a href="<?= SITE_URL ?>/admin/posts.php">Posts</a>
    </li>
    <li class="<?= basename($_SERVER['PHP_SELF']) === 'edit-post.php' ? 'active' : '' ?>">
      <a href="<?= SITE_URL ?>/admin/edit-post.php">New Post</a>
    </li>
    <li class="<?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : '' ?>">
      <a href="<?= SITE_URL ?>/admin/categories.php">Categories &amp; Tags</a>
    </li>
    <li class="nav-section">Media</li>
    <li class="<?= basename($_SERVER['PHP_SELF']) === 'media.php' ? 'active' : '' ?>">
      <a href="<?= SITE_URL ?>/admin/media.php">Media Library</a>
    </li>
    <li class="nav-section">Settings</li>
    <li class="<?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>">
      <a href="<?= SITE_URL ?>/admin/settings.php">Settings</a>
    </li>
  </ul>
  <div class="admin-footer-links">
    <a href="<?= SITE_URL ?>" target="_blank">View Blog</a>
    <a href="<?= SITE_URL ?>/admin/logout.php">Log Out</a>
  </div>
</nav>
