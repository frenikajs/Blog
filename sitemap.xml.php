<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

$posts = db()->query(
    'SELECT slug, updated_at, published_at FROM posts WHERE status = "published" ORDER BY published_at DESC'
)->fetchAll();

$cats = db()->query(
    'SELECT c.slug, MAX(p.published_at) as last_post
     FROM categories c
     JOIN post_categories pc ON pc.category_id = c.id
     JOIN posts p ON p.id = pc.post_id AND p.status = "published"
     GROUP BY c.id'
)->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?= SITE_URL ?>/</loc>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
  <?php foreach ($posts as $p): $mod = $p['updated_at'] ?? $p['published_at']; ?>
  <url>
    <loc><?= SITE_URL . '/' . h($p['slug']) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($mod)) ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.8</priority>
  </url>
  <?php endforeach; ?>
  <?php foreach ($cats as $c): ?>
  <url>
    <loc><?= SITE_URL . '/category/' . h($c['slug']) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($c['last_post'])) ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.6</priority>
  </url>
  <?php endforeach; ?>
</urlset>
