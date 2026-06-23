<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

$q    = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

$posts = $q ? search_posts($q, $page) : [];
$total = $q ? count_search_posts($q) : 0;

$seo = [
    'title'       => $q ? 'Search results for "' . h($q) . '"' : 'Search',
    'description' => 'Search blog posts',
    'canonical'   => SITE_URL . '/search' . ($q ? '?q=' . urlencode($q) : ''),
    'robots'      => 'noindex, follow',
];

require __DIR__ . '/themes/default/header.php';
?>

<div class="content-area">
  <div class="main-content">

    <div class="page-header">
      <h1><?= $q ? 'Search results for "' . h($q) . '"' : 'Search' ?></h1>
      <?php if ($q && $total): ?>
        <p><?= $total ?> result<?= $total === 1 ? '' : 's' ?> found</p>
      <?php endif; ?>
    </div>

    <form class="search-page-form" action="<?= SITE_URL ?>/search" method="get" role="search">
      <input type="search" name="q" value="<?= h($q) ?>"
             placeholder="Search posts…" aria-label="Search posts" autofocus>
      <button class="btn btn-primary" type="submit">Search</button>
    </form>

    <?php if ($q): ?>
      <?php if (empty($posts)): ?>
        <div class="no-posts">
          <h2>No results found</h2>
          <p>Try different keywords or browse by <a href="<?= SITE_URL ?>">all posts</a>.</p>
        </div>
      <?php else: ?>
        <div class="post-list">
          <?php foreach ($posts as $post):
            $cats = get_post_categories($post['id']);
          ?>
          <article class="post-card">
            <div class="post-card-body">
              <div class="post-card-meta">
                <time datetime="<?= h($post['published_at']) ?>"><?= format_date($post['published_at']) ?></time>
                <?php foreach ($cats as $cat): ?>
                  <a href="<?= category_url($cat) ?>"><?= h($cat['name']) ?></a>
                <?php endforeach; ?>
              </div>
              <h2 class="post-card-title">
                <a href="<?= post_url($post) ?>"><?= h($post['title']) ?></a>
              </h2>
              <p class="post-card-excerpt">
                <?= h($post['excerpt'] ?: excerpt($post['content'])) ?>
              </p>
              <a href="<?= post_url($post) ?>" class="btn btn-primary">Read more</a>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
        <?= pagination_links($page, $total, POSTS_PER_PAGE, SITE_URL . '/search?q=' . urlencode($q)) ?>
      <?php endif; ?>
    <?php endif; ?>

  </div><!-- .main-content -->

  <?php include __DIR__ . '/includes/sidebar.php'; ?>

</div><!-- .content-area -->

<?php require __DIR__ . '/themes/default/footer.php'; ?>
