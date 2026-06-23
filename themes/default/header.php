<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php render_seo_head($seo ?? []); ?>
<link rel="alternate" type="application/rss+xml" title="<?= h(SITE_NAME) ?> Feed" href="<?= SITE_URL ?>/feed">
<link rel="stylesheet" href="<?= SITE_URL ?>/themes/default/style.css">
<?php if (!empty($extra_head)) echo $extra_head; ?>
</head>
<body class="<?= $bodyClass ?? '' ?>">

<header class="site-header">
  <div class="container">
    <div class="site-branding">
      <a href="<?= SITE_URL ?>" class="site-title"><?= h(SITE_NAME) ?></a>
      <?php $tagline = get_setting('tagline', ''); if ($tagline): ?>
        <p class="site-tagline"><?= h($tagline) ?></p>
      <?php endif; ?>
    </div>
    <nav class="site-nav" aria-label="Main navigation">
      <button class="nav-toggle" aria-expanded="false" aria-controls="nav-menu">&#9776;</button>
      <ul id="nav-menu">
        <li><a href="<?= SITE_URL ?>">Home</a></li>
        <?php foreach (get_all_categories() as $cat): if ($cat['post_count'] > 0): ?>
          <li><a href="<?= category_url($cat) ?>"><?= h($cat['name']) ?></a></li>
        <?php endif; endforeach; ?>
        <li><a href="<?= SITE_URL ?>/search">Search</a></li>
      </ul>
    </nav>
  </div>
</header>

<main class="site-main">
  <div class="container">
