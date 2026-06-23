<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

$page    = max(1, (int)($_GET['page'] ?? 1));
$catSlug = $_GET['category'] ?? null;
$tagSlug = $_GET['tag'] ?? null;

$categoryId = null;
$currentCat = null;
$currentTag = null;

if ($catSlug) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE slug = ? LIMIT 1');
    $stmt->execute([$catSlug]);
    $currentCat = $stmt->fetch();
    if ($currentCat) $categoryId = (int)$currentCat['id'];
}
if ($tagSlug) {
    $stmt = db()->prepare('SELECT * FROM tags WHERE slug = ? LIMIT 1');
    $stmt->execute([$tagSlug]);
    $currentTag = $stmt->fetch();
}

$posts = get_posts($page, POSTS_PER_PAGE, 'published', $categoryId, $tagSlug ? $tagSlug : null);
$total = count_posts('published', $categoryId, $tagSlug ? $tagSlug : null);

$pageTitle = SITE_NAME;
$pageDesc  = get_setting('site_description', SITE_DESCRIPTION);
$canonical = SITE_URL;

if ($currentCat) {
    $pageTitle = h($currentCat['name']);
    $pageDesc  = 'Posts in category: ' . $currentCat['name'];
    $canonical = SITE_URL . '/category/' . $currentCat['slug'];
} elseif ($currentTag) {
    $pageTitle = 'Tag: ' . h($currentTag['name']);
    $canonical = SITE_URL . '/tag/' . $currentTag['slug'];
}

$seo = [
    'title'       => $pageTitle,
    'description' => $pageDesc,
    'canonical'   => $canonical . ($page > 1 ? '?page=' . $page : ''),
];

require __DIR__ . '/themes/default/header.php';
?>

<div class="content-area">
  <div class="main-content">

    <?php if ($currentCat || $currentTag): ?>
      <div class="page-header">
        <?php if ($currentCat): ?>
          <h1>Category: <?= h($currentCat['name']) ?></h1>
          <?php if (!empty($currentCat['description'])): ?>
            <p><?= h($currentCat['description']) ?></p>
          <?php endif; ?>
        <?php elseif ($currentTag): ?>
          <h1>Tag: <?= h($currentTag['name']) ?></h1>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
      <div class="no-posts">
        <h2>No posts found</h2>
        <p>Check back soon for new content.</p>
      </div>
    <?php else: ?>
      <div class="post-list">
        <?php foreach ($posts as $post):
          $cats = get_post_categories($post['id']);
          $tags = get_post_tags($post['id']);
        ?>
        <article class="post-card">
          <?php if (!empty($post['featured_image'])): ?>
          <div class="post-card-image">
            <a href="<?= post_url($post) ?>">
              <img src="<?= SITE_URL . '/' . h($post['featured_image']) ?>"
                   alt="<?= h($post['title']) ?>"
                   loading="lazy" width="800" height="450">
            </a>
          </div>
          <?php endif; ?>
          <div class="post-card-body">
            <div class="post-card-meta">
              <time datetime="<?= h($post['published_at']) ?>"><?= format_date($post['published_at']) ?></time>
              <?php if ($post['author_name']): ?>
                <span>By <?= h($post['author_name']) ?></span>
              <?php endif; ?>
              <?php foreach ($cats as $cat): ?>
                <a href="<?= category_url($cat) ?>"><?= h($cat['name']) ?></a>
              <?php endforeach; ?>
              <span><?= reading_time($post['content']) ?> min read</span>
            </div>
            <h2 class="post-card-title">
              <a href="<?= post_url($post) ?>"><?= h($post['title']) ?></a>
            </h2>
            <p class="post-card-excerpt">
              <?= h($post['excerpt'] ?: excerpt($post['content'])) ?>
            </p>
            <div class="post-card-footer">
              <a href="<?= post_url($post) ?>" class="btn btn-primary">Read more</a>
              <?php foreach ($tags as $tag): ?>
                <a href="<?= tag_url($tag) ?>" class="tag-badge"><?= h($tag['name']) ?></a>
              <?php endforeach; ?>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>

      <?php
        $base = $currentCat ? SITE_URL . '/category/' . $currentCat['slug']
              : ($currentTag ? SITE_URL . '/tag/' . $currentTag['slug'] : SITE_URL . '/');
        echo pagination_links($page, $total, POSTS_PER_PAGE, $base);
      ?>
    <?php endif; ?>

  </div><!-- .main-content -->

  <?php include __DIR__ . '/includes/sidebar.php'; ?>

</div><!-- .content-area -->

<?php require __DIR__ . '/themes/default/footer.php'; ?>
