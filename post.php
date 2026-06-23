<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) { header('Location: ' . SITE_URL, true, 302); exit; }

$post = get_post_by_slug($slug);
if (!$post) {
    http_response_code(404);
    $seo = ['title' => '404 Not Found', 'robots' => 'noindex'];
    require __DIR__ . '/themes/default/header.php';
    echo '<div class="no-posts"><h2>404 — Page Not Found</h2><p><a href="' . SITE_URL . '">Return home</a></p></div>';
    require __DIR__ . '/themes/default/footer.php';
    exit;
}

$ogImage = !empty($post['featured_image'])
    ? SITE_URL . '/' . $post['featured_image']
    : get_setting('default_og_image', '');

$seo = [
    'title'       => $post['meta_title'] ?: $post['title'],
    'description' => $post['meta_description'] ?: excerpt($post['content'], 35),
    'canonical'   => post_url($post),
    'og_type'     => 'article',
    'image'       => $ogImage,
];

$extra_head = '';
ob_start();
render_article_jsonld($post);
render_breadcrumb_jsonld([
    ['name' => SITE_NAME, 'url' => SITE_URL],
    ['name' => $post['title'], 'url' => post_url($post)],
]);
$extra_head = ob_get_clean();

require __DIR__ . '/themes/default/header.php';
?>

<div class="content-area">
  <div class="main-content">

    <nav class="breadcrumbs" aria-label="Breadcrumb">
      <a href="<?= SITE_URL ?>">Home</a>
      <?php foreach ($post['categories'] as $cat): ?>
        <span class="sep">/</span>
        <a href="<?= category_url($cat) ?>"><?= h($cat['name']) ?></a>
      <?php endforeach; ?>
      <span class="sep">/</span>
      <span><?= h($post['title']) ?></span>
    </nav>

    <article class="post-full" itemscope itemtype="https://schema.org/BlogPosting">
      <header class="post-full-header">
        <h1 class="post-full-title" itemprop="headline"><?= h($post['title']) ?></h1>
        <div class="post-full-meta">
          <time datetime="<?= h($post['published_at']) ?>" itemprop="datePublished">
            <?= format_date($post['published_at'], 'F j, Y') ?>
          </time>
          <?php if ($post['author_name']): ?>
            <span>By <span itemprop="author"><?= h($post['author_name']) ?></span></span>
          <?php endif; ?>
          <?php foreach ($post['categories'] as $cat): ?>
            <a href="<?= category_url($cat) ?>"><?= h($cat['name']) ?></a>
          <?php endforeach; ?>
          <span><?= reading_time($post['content']) ?> min read</span>
        </div>
      </header>

      <?php if (!empty($post['featured_image'])): ?>
      <div class="post-full-image">
        <img src="<?= SITE_URL . '/' . h($post['featured_image']) ?>"
             alt="<?= h($post['title']) ?>" itemprop="image"
             width="1200" height="630">
      </div>
      <?php endif; ?>

      <div class="post-content" itemprop="articleBody">
        <?= $post['content'] ?>
      </div>

      <?php if (!empty($post['tags'])): ?>
      <div class="post-tags">
        <strong>Tags:</strong>
        <?php foreach ($post['tags'] as $tag): ?>
          <a href="<?= tag_url($tag) ?>" class="tag-badge"><?= h($tag['name']) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </article>

  </div><!-- .main-content -->

  <?php include __DIR__ . '/includes/sidebar.php'; ?>

</div><!-- .content-area -->

<?php require __DIR__ . '/themes/default/footer.php'; ?>
