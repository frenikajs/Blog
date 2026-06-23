<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post   = $postId ? get_post_by_id($postId) : null;
$errors = [];
$notice = '';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    } else {
        $title      = trim($_POST['title'] ?? '');
        $content    = $_POST['content'] ?? '';
        $excerpt    = trim($_POST['excerpt'] ?? '');
        $status     = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'draft';
        $metaTitle  = trim($_POST['meta_title'] ?? '');
        $metaDesc   = trim($_POST['meta_description'] ?? '');
        $catIds     = array_map('intval', $_POST['categories'] ?? []);
        $tagsRaw    = trim($_POST['tags'] ?? '');
        $customSlug = trim($_POST['slug'] ?? '');

        if (!$title) $errors[] = 'Title is required.';

        if (empty($errors)) {
            $slug = $customSlug ?: unique_slug($title, $postId);
            $slug = unique_slug($slug, $postId);
            $publishedAt = null;

            if ($status === 'published') {
                if ($post && $post['published_at']) {
                    $publishedAt = $post['published_at'];
                } else {
                    $publishedAt = date('Y-m-d H:i:s');
                }
            }

            // Handle featured image upload
            $featuredImage = $post['featured_image'] ?? null;
            if (!empty($_FILES['featured_image']['name'])) {
                $uploaded = handle_image_upload('featured_image');
                if ($uploaded) $featuredImage = $uploaded;
            }

            $db = db();

            if ($postId && $post) {
                $stmt = $db->prepare(
                    'UPDATE posts SET title=?, slug=?, content=?, excerpt=?, status=?, meta_title=?,
                     meta_description=?, featured_image=?, published_at=?, updated_at=NOW()
                     WHERE id=?'
                );
                $stmt->execute([$title, $slug, $content, $excerpt, $status, $metaTitle,
                                $metaDesc, $featuredImage, $publishedAt, $postId]);
            } else {
                $stmt = $db->prepare(
                    'INSERT INTO posts (author_id, title, slug, content, excerpt, status, meta_title,
                     meta_description, featured_image, published_at, created_at, updated_at)
                     VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
                );
                $stmt->execute([$title, $slug, $content, $excerpt, $status, $metaTitle,
                                $metaDesc, $featuredImage, $publishedAt]);
                $postId = (int)$db->lastInsertId();
            }

            // Sync categories
            $db->prepare('DELETE FROM post_categories WHERE post_id = ?')->execute([$postId]);
            foreach ($catIds as $cid) {
                if ($cid > 0) {
                    $db->prepare('INSERT IGNORE INTO post_categories (post_id, category_id) VALUES (?, ?)')
                       ->execute([$postId, $cid]);
                }
            }

            // Sync tags
            $db->prepare('DELETE FROM post_tags WHERE post_id = ?')->execute([$postId]);
            if ($tagsRaw) {
                foreach (array_unique(array_filter(array_map('trim', explode(',', $tagsRaw)))) as $tagName) {
                    if (!$tagName) continue;
                    $tagSlug = slugify($tagName);
                    $db->prepare('INSERT IGNORE INTO tags (name, slug) VALUES (?, ?)')->execute([$tagName, $tagSlug]);
                    $tagId = (int)$db->query("SELECT id FROM tags WHERE slug = '$tagSlug' LIMIT 1")->fetchColumn();
                    if ($tagId) {
                        $db->prepare('INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)')->execute([$postId, $tagId]);
                    }
                }
            }

            header('Location: ' . SITE_URL . '/admin/posts.php?saved=1');
            exit;
        }
    }
}

$allCategories = get_all_categories();
$postCatIds    = $post ? array_column($post['categories'] ?? [], 'id') : [];
$postTagsStr   = $post ? implode(', ', array_column($post['tags'] ?? [], 'name')) : '';

$pageTitle = $postId ? 'Edit Post' : 'New Post';

$adminExtraHead = '
<link href="https://cdn.jsdelivr.net/npm/tinymce@6/skins/ui/oxide/skin.min.css" rel="stylesheet">
';

$adminFootScripts = '
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
  selector: "#content",
  height: 500,
  menubar: true,
  plugins: "advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste wordcount",
  toolbar: "undo redo | blocks | bold italic underline strikethrough | link image media | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | code fullscreen",
  image_advtab: true,
  relative_urls: false,
  remove_script_host: false,
  convert_urls: false,
  content_style: "body { font-family: system-ui, sans-serif; font-size: 16px; line-height: 1.7; max-width: 700px; margin: 1rem auto; padding: 0 1rem; }",
  branding: false,
  promotion: false,
});

// Auto-generate slug from title
document.getElementById("title").addEventListener("blur", function() {
  var slugField = document.getElementById("slug");
  if (!slugField.value) {
    var slug = this.value.toLowerCase()
      .replace(/[^\w\s-]/g, "")
      .replace(/[\s_]+/g, "-")
      .replace(/^-+|-+$/g, "");
    slugField.value = slug;
  }
});

// Character counters
function updateCounter(inputId, counterId, max) {
  var el = document.getElementById(inputId);
  var counter = document.getElementById(counterId);
  if (!el || !counter) return;
  function update() {
    var len = el.value.length;
    counter.textContent = len + "/" + max;
    counter.style.color = len > max ? "#c0392b" : len > max * 0.9 ? "#e67e22" : "#666";
  }
  el.addEventListener("input", update);
  update();
}
updateCounter("meta_title", "meta-title-count", 60);
updateCounter("meta_description", "meta-desc-count", 160);
</script>
';

require __DIR__ . '/partials/head.php';
?>

<div class="admin-wrap">
  <?php require __DIR__ . '/partials/sidebar.php'; ?>
  <main class="admin-main admin-main-wide">

    <div class="admin-header">
      <h1><?= $pageTitle ?></h1>
      <div class="admin-header-actions">
        <?php if ($postId && ($post['status'] ?? '') === 'published'): ?>
          <a href="<?= SITE_URL . '/' . h($post['slug']) ?>" target="_blank" class="btn btn-secondary">View Post</a>
        <?php endif; ?>
      </div>
    </div>

    <?php foreach ($errors as $e): ?>
      <div class="notice notice-error"><?= h($e) ?></div>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data" class="post-edit-form">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="post-edit-layout">

        <!-- Main column -->
        <div class="post-edit-main">

          <div class="form-group">
            <label for="title">Title <span class="required">*</span></label>
            <input id="title" type="text" name="title" class="form-control form-control-lg"
                   value="<?= h($post['title'] ?? '') ?>" required placeholder="Post title">
          </div>

          <div class="form-group">
            <label for="content">Content</label>
            <textarea id="content" name="content"><?= h($post['content'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label for="excerpt">Excerpt <small class="muted">(leave blank to auto-generate)</small></label>
            <textarea id="excerpt" name="excerpt" class="form-control" rows="3" placeholder="Brief summary shown in post listings…"><?= h($post['excerpt'] ?? '') ?></textarea>
          </div>

          <!-- SEO box -->
          <div class="meta-box">
            <h3 class="meta-box-title">SEO Settings</h3>
            <div class="form-group">
              <label for="meta_title">
                Meta Title
                <small class="muted">Ideal: 50–60 chars</small>
                <span id="meta-title-count" class="char-count">0/60</span>
              </label>
              <input id="meta_title" type="text" name="meta_title" class="form-control"
                     value="<?= h($post['meta_title'] ?? '') ?>"
                     placeholder="Leave blank to use post title">
            </div>
            <div class="form-group">
              <label for="meta_description">
                Meta Description
                <small class="muted">Ideal: 120–160 chars</small>
                <span id="meta-desc-count" class="char-count">0/160</span>
              </label>
              <textarea id="meta_description" name="meta_description" class="form-control" rows="3"
                        placeholder="Shown in search engine results…"><?= h($post['meta_description'] ?? '') ?></textarea>
            </div>
          </div>

        </div><!-- .post-edit-main -->

        <!-- Sidebar column -->
        <div class="post-edit-sidebar">

          <!-- Publish box -->
          <div class="meta-box">
            <h3 class="meta-box-title">Publish</h3>
            <div class="form-group">
              <label for="status">Status</label>
              <select id="status" name="status" class="form-control">
                <option value="draft"     <?= ($post['status'] ?? 'draft') === 'draft'     ? 'selected' : '' ?>>Draft</option>
                <option value="published" <?= ($post['status'] ?? '')       === 'published' ? 'selected' : '' ?>>Published</option>
              </select>
            </div>
            <div class="form-group">
              <label for="slug">URL Slug</label>
              <div class="slug-preview">
                <span class="slug-base"><?= SITE_URL ?>/</span>
                <input id="slug" type="text" name="slug" class="form-control"
                       value="<?= h($post['slug'] ?? '') ?>"
                       placeholder="auto-generated-from-title">
              </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Save Post</button>
          </div>

          <!-- Featured image -->
          <div class="meta-box">
            <h3 class="meta-box-title">Featured Image</h3>
            <?php if (!empty($post['featured_image'])): ?>
              <img src="<?= SITE_URL . '/' . h($post['featured_image']) ?>" alt="" class="featured-preview">
            <?php endif; ?>
            <input type="file" name="featured_image" accept="image/*" class="form-control">
            <small class="muted">JPEG, PNG, GIF, or WebP</small>
          </div>

          <!-- Categories -->
          <div class="meta-box">
            <h3 class="meta-box-title">Categories</h3>
            <div class="checkbox-list">
              <?php foreach ($allCategories as $cat): ?>
              <label class="checkbox-item">
                <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>"
                       <?= in_array($cat['id'], $postCatIds) ? 'checked' : '' ?>>
                <?= h($cat['name']) ?>
              </label>
              <?php endforeach; ?>
            </div>
            <a href="<?= SITE_URL ?>/admin/categories.php" class="text-small">+ Add new category</a>
          </div>

          <!-- Tags -->
          <div class="meta-box">
            <h3 class="meta-box-title">Tags</h3>
            <input type="text" name="tags" class="form-control"
                   value="<?= h($postTagsStr) ?>"
                   placeholder="tag1, tag2, tag3">
            <small class="muted">Comma-separated</small>
          </div>

        </div><!-- .post-edit-sidebar -->
      </div><!-- .post-edit-layout -->
    </form>

  </main>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
