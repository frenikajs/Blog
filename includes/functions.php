<?php
require_once __DIR__ . '/db.php';

// ── Slugs ──────────────────────────────────────────────────────────────────

function slugify(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^\w\s-]/u', '', $text);
    $text = preg_replace('/[\s_-]+/', '-', $text);
    return trim($text, '-');
}

function unique_slug(string $base, int $excludeId = 0): string {
    $slug = slugify($base);
    $original = $slug;
    $i = 1;
    while (true) {
        $stmt = db()->prepare('SELECT id FROM posts WHERE slug = ? AND id != ?');
        $stmt->execute([$slug, $excludeId]);
        if (!$stmt->fetch()) break;
        $slug = $original . '-' . $i++;
    }
    return $slug;
}

// ── Posts ──────────────────────────────────────────────────────────────────

function get_posts(int $page = 1, int $perPage = POSTS_PER_PAGE, string $status = 'published', ?int $categoryId = null, ?string $tag = null): array {
    $offset = ($page - 1) * $perPage;
    $params = [$status];
    $join = '';
    $where = 'p.status = ?';

    if ($categoryId !== null) {
        $join .= ' JOIN post_categories pc ON pc.post_id = p.id';
        $where .= ' AND pc.category_id = ?';
        $params[] = $categoryId;
    }
    if ($tag !== null) {
        $join .= ' JOIN post_tags pt ON pt.post_id = p.id JOIN tags t ON t.id = pt.tag_id';
        $where .= ' AND t.slug = ?';
        $params[] = $tag;
    }

    $params[] = $perPage;
    $params[] = $offset;

    $sql = "SELECT p.*, u.display_name as author_name
            FROM posts p
            LEFT JOIN admin_user u ON u.id = p.author_id
            $join
            WHERE $where
            ORDER BY p.published_at DESC
            LIMIT ? OFFSET ?";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function count_posts(string $status = 'published', ?int $categoryId = null, ?string $tag = null): int {
    $params = [$status];
    $join = '';
    $where = 'p.status = ?';

    if ($categoryId !== null) {
        $join .= ' JOIN post_categories pc ON pc.post_id = p.id';
        $where .= ' AND pc.category_id = ?';
        $params[] = $categoryId;
    }
    if ($tag !== null) {
        $join .= ' JOIN post_tags pt ON pt.post_id = p.id JOIN tags t ON t.id = pt.tag_id';
        $where .= ' AND t.slug = ?';
        $params[] = $tag;
    }

    $stmt = db()->prepare("SELECT COUNT(DISTINCT p.id) FROM posts p $join WHERE $where");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function get_post_by_slug(string $slug): ?array {
    $stmt = db()->prepare(
        'SELECT p.*, u.display_name as author_name
         FROM posts p
         LEFT JOIN admin_user u ON u.id = p.author_id
         WHERE p.slug = ? AND p.status = "published" LIMIT 1'
    );
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
    if (!$post) return null;
    $post['categories'] = get_post_categories($post['id']);
    $post['tags']       = get_post_tags($post['id']);
    return $post;
}

function get_post_by_id(int $id): ?array {
    $stmt = db()->prepare(
        'SELECT p.*, u.display_name as author_name
         FROM posts p
         LEFT JOIN admin_user u ON u.id = p.author_id
         WHERE p.id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    if (!$post) return null;
    $post['categories'] = get_post_categories($id);
    $post['tags']       = get_post_tags($id);
    return $post;
}

// ── Categories & Tags ──────────────────────────────────────────────────────

function get_all_categories(): array {
    return db()->query('SELECT c.*, COUNT(pc.post_id) as post_count
                        FROM categories c
                        LEFT JOIN post_categories pc ON pc.category_id = c.id
                        LEFT JOIN posts p ON p.id = pc.post_id AND p.status = "published"
                        GROUP BY c.id ORDER BY c.name ASC')->fetchAll();
}

function get_post_categories(int $postId): array {
    $stmt = db()->prepare(
        'SELECT c.* FROM categories c
         JOIN post_categories pc ON pc.category_id = c.id
         WHERE pc.post_id = ?'
    );
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

function get_post_tags(int $postId): array {
    $stmt = db()->prepare(
        'SELECT t.* FROM tags t
         JOIN post_tags pt ON pt.tag_id = t.id
         WHERE pt.post_id = ?'
    );
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

function get_all_tags(): array {
    return db()->query('SELECT t.*, COUNT(pt.post_id) as post_count
                        FROM tags t
                        LEFT JOIN post_tags pt ON pt.tag_id = t.id
                        LEFT JOIN posts p ON p.id = pt.post_id AND p.status = "published"
                        GROUP BY t.id ORDER BY t.name ASC')->fetchAll();
}

// ── Settings ───────────────────────────────────────────────────────────────

function get_setting(string $name, string $default = ''): string {
    static $cache = [];
    if (!isset($cache[$name])) {
        $stmt = db()->prepare('SELECT value FROM settings WHERE name = ? LIMIT 1');
        $stmt->execute([$name]);
        $row = $stmt->fetch();
        $cache[$name] = $row ? $row['value'] : $default;
    }
    return $cache[$name];
}

function set_setting(string $name, string $value): void {
    $stmt = db()->prepare('INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?');
    $stmt->execute([$name, $value, $value]);
}

// ── Search ─────────────────────────────────────────────────────────────────

function search_posts(string $q, int $page = 1): array {
    $offset = ($page - 1) * POSTS_PER_PAGE;
    $like = '%' . $q . '%';
    $stmt = db()->prepare(
        'SELECT p.*, u.display_name as author_name
         FROM posts p
         LEFT JOIN admin_user u ON u.id = p.author_id
         WHERE p.status = "published" AND (p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)
         ORDER BY p.published_at DESC
         LIMIT ? OFFSET ?'
    );
    $stmt->execute([$like, $like, $like, POSTS_PER_PAGE, $offset]);
    return $stmt->fetchAll();
}

function count_search_posts(string $q): int {
    $like = '%' . $q . '%';
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM posts
         WHERE status = "published" AND (title LIKE ? OR content LIKE ? OR excerpt LIKE ?)'
    );
    $stmt->execute([$like, $like, $like]);
    return (int)$stmt->fetchColumn();
}

// ── Recent & Related ───────────────────────────────────────────────────────

function get_recent_posts(int $limit = 5): array {
    $stmt = db()->prepare(
        'SELECT id, title, slug, published_at, featured_image
         FROM posts WHERE status = "published"
         ORDER BY published_at DESC LIMIT ?'
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// ── Pagination helper ──────────────────────────────────────────────────────

function pagination_links(int $current, int $total, int $perPage, string $baseUrl): string {
    $pages = (int)ceil($total / $perPage);
    if ($pages <= 1) return '';
    $sep = str_contains($baseUrl, '?') ? '&' : '?';
    $html = '<nav class="pagination" aria-label="Posts navigation"><ul>';
    if ($current > 1)
        $html .= '<li><a href="' . $baseUrl . $sep . 'page=' . ($current - 1) . '">&laquo; Previous</a></li>';
    for ($i = max(1, $current - 2); $i <= min($pages, $current + 2); $i++) {
        if ($i === $current)
            $html .= '<li class="active"><span>' . $i . '</span></li>';
        else
            $html .= '<li><a href="' . $baseUrl . $sep . 'page=' . $i . '">' . $i . '</a></li>';
    }
    if ($current < $pages)
        $html .= '<li><a href="' . $baseUrl . $sep . 'page=' . ($current + 1) . '">Next &raquo;</a></li>';
    $html .= '</ul></nav>';
    return $html;
}

// ── Utilities ──────────────────────────────────────────────────────────────

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function excerpt(string $html, int $words = 55): string {
    $text = wp_strip_all_tags($html);
    $arr = explode(' ', $text);
    if (count($arr) <= $words) return $text;
    return implode(' ', array_slice($arr, 0, $words)) . '…';
}

function wp_strip_all_tags(string $html): string {
    $text = preg_replace('#<(script|style)[^>]*>.*?</\1>#si', '', $html);
    return trim(strip_tags($text));
}

function format_date(string $dateStr, string $format = 'F j, Y'): string {
    return date($format, strtotime($dateStr));
}

function post_url(array $post): string {
    return SITE_URL . '/' . $post['slug'];
}

function category_url(array $cat): string {
    return SITE_URL . '/category/' . $cat['slug'];
}

function tag_url(array $tag): string {
    return SITE_URL . '/tag/' . $tag['slug'];
}

// ── Image upload ───────────────────────────────────────────────────────────

function handle_image_upload(string $inputName): ?string {
    if (empty($_FILES[$inputName]['name'])) return null;
    $file = $_FILES[$inputName];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed)) return null;
    $ext = match($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        default      => 'jpg',
    };
    $dir = UPLOADS_DIR . '/' . date('Y/m');
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
    return 'uploads/' . date('Y/m') . '/' . $filename;
}

// ── Reading time ───────────────────────────────────────────────────────────

function reading_time(string $html): int {
    $words = str_word_count(wp_strip_all_tags($html));
    return max(1, (int)ceil($words / 200));
}
