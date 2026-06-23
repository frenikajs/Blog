<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/rss+xml; charset=utf-8');
header('X-Robots-Tag: noindex');

$posts = get_posts(1, 20);
$lastBuild = !empty($posts) ? date('r', strtotime($posts[0]['published_at'])) : date('r');
?>
<?= '<?xml version="1.0" encoding="UTF-8"?>' ?>
<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:dc="http://purl.org/dc/elements/1.1/">
  <channel>
    <title><?= h(SITE_NAME) ?></title>
    <link><?= SITE_URL ?></link>
    <description><?= h(get_setting('site_description', SITE_DESCRIPTION)) ?></description>
    <language>en-us</language>
    <lastBuildDate><?= $lastBuild ?></lastBuildDate>
    <atom:link href="<?= SITE_URL ?>/feed" rel="self" type="application/rss+xml"/>
    <?php foreach ($posts as $post): ?>
    <item>
      <title><?= h($post['title']) ?></title>
      <link><?= post_url($post) ?></link>
      <guid isPermaLink="true"><?= post_url($post) ?></guid>
      <pubDate><?= date('r', strtotime($post['published_at'])) ?></pubDate>
      <dc:creator><?= h($post['author_name'] ?? SITE_NAME) ?></dc:creator>
      <description><?= h($post['excerpt'] ?: excerpt($post['content'])) ?></description>
      <content:encoded><![CDATA[<?= $post['content'] ?>]]></content:encoded>
    </item>
    <?php endforeach; ?>
  </channel>
</rss>
