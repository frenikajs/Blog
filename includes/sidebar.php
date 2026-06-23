<aside class="sidebar">

  <div class="widget">
    <h3>Search</h3>
    <form class="search-form" action="<?= SITE_URL ?>/search" method="get" role="search">
      <input class="search-input" type="search" name="q" placeholder="Search posts…"
             aria-label="Search posts" value="<?= h($_GET['q'] ?? '') ?>">
      <button class="btn btn-primary" type="submit">Go</button>
    </form>
  </div>

  <?php $cats = get_all_categories(); $hasCats = array_filter($cats, fn($c) => $c['post_count'] > 0); ?>
  <?php if ($hasCats): ?>
  <div class="widget">
    <h3>Categories</h3>
    <ul>
      <?php foreach ($cats as $cat): if ($cat['post_count'] > 0): ?>
        <li>
          <a href="<?= category_url($cat) ?>"><?= h($cat['name']) ?></a>
          <span style="float:right;color:#999;font-size:.8rem">(<?= $cat['post_count'] ?>)</span>
        </li>
      <?php endif; endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <div class="widget">
    <h3>Recent Posts</h3>
    <ul>
      <?php foreach (get_recent_posts(7) as $rp): ?>
        <li>
          <a href="<?= post_url($rp) ?>"><?= h($rp['title']) ?></a>
          <br><small style="color:#999"><?= format_date($rp['published_at'], 'M j, Y') ?></small>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <?php $tags = get_all_tags(); $hasTags = array_filter($tags, fn($t) => $t['post_count'] > 0); ?>
  <?php if ($hasTags): ?>
  <div class="widget">
    <h3>Tags</h3>
    <div style="display:flex;flex-wrap:wrap;gap:.4rem">
      <?php foreach ($tags as $tag): if ($tag['post_count'] > 0): ?>
        <a href="<?= tag_url($tag) ?>" class="tag-badge"><?= h($tag['name']) ?></a>
      <?php endif; endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</aside>
