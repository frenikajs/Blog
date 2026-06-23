  </div><!-- .container -->
</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-widgets">
      <div class="footer-widget">
        <h3>About</h3>
        <p><?= h(get_setting('site_description', SITE_DESCRIPTION)) ?></p>
      </div>
      <div class="footer-widget">
        <h3>Categories</h3>
        <ul>
          <?php foreach (get_all_categories() as $cat): if ($cat['post_count'] > 0): ?>
            <li><a href="<?= category_url($cat) ?>"><?= h($cat['name']) ?> (<?= $cat['post_count'] ?>)</a></li>
          <?php endif; endforeach; ?>
        </ul>
      </div>
      <div class="footer-widget">
        <h3>Recent Posts</h3>
        <ul>
          <?php foreach (get_recent_posts(5) as $rp): ?>
            <li><a href="<?= post_url($rp) ?>"><?= h($rp['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> <?= h(SITE_NAME) ?>. All rights reserved.</p>
      <p><a href="<?= SITE_URL ?>/feed">RSS Feed</a> &middot; <a href="<?= SITE_URL ?>/sitemap.xml">Sitemap</a></p>
    </div>
  </div>
</footer>

<script>
document.querySelector('.nav-toggle').addEventListener('click', function() {
  var menu = document.getElementById('nav-menu');
  var expanded = this.getAttribute('aria-expanded') === 'true';
  this.setAttribute('aria-expanded', !expanded);
  menu.classList.toggle('open');
});
</script>

<?php
$gaId = get_setting('google_analytics', '');
if ($gaId && preg_match('/^G-[A-Z0-9]+$/', $gaId)):
?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= h($gaId) ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '<?= h($gaId) ?>');
</script>
<?php endif; ?>
</body>
</html>
