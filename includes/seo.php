<?php
require_once __DIR__ . '/functions.php';

function render_seo_head(array $opts = []): void {
    $siteName    = h(SITE_NAME);
    $siteUrl     = SITE_URL;
    $title       = isset($opts['title'])       ? h($opts['title'])       : $siteName;
    $description = isset($opts['description']) ? h($opts['description']) : h(get_setting('site_description', SITE_DESCRIPTION));
    $canonical   = isset($opts['canonical'])   ? h($opts['canonical'])   : h(current_url());
    $type        = $opts['og_type']   ?? 'website';
    $image       = isset($opts['image'])       ? h($opts['image'])       : h(get_setting('default_og_image', ''));
    $robots      = $opts['robots']    ?? 'index, follow';
    $fullTitle   = ($title === $siteName) ? $title : $title . ' — ' . $siteName;

    echo '<title>' . $fullTitle . '</title>' . "\n";
    echo '<meta name="description" content="' . $description . '">' . "\n";
    echo '<meta name="robots" content="' . h($robots) . '">' . "\n";
    echo '<link rel="canonical" href="' . $canonical . '">' . "\n";

    // Open Graph
    echo '<meta property="og:type" content="' . h($type) . '">' . "\n";
    echo '<meta property="og:title" content="' . $title . '">' . "\n";
    echo '<meta property="og:description" content="' . $description . '">' . "\n";
    echo '<meta property="og:url" content="' . $canonical . '">' . "\n";
    echo '<meta property="og:site_name" content="' . $siteName . '">' . "\n";
    if ($image) echo '<meta property="og:image" content="' . $image . '">' . "\n";

    // Twitter Card
    echo '<meta name="twitter:card" content="' . ($image ? 'summary_large_image' : 'summary') . '">' . "\n";
    echo '<meta name="twitter:title" content="' . $title . '">' . "\n";
    echo '<meta name="twitter:description" content="' . $description . '">' . "\n";
    if ($image) echo '<meta name="twitter:image" content="' . $image . '">' . "\n";
    $twitterHandle = get_setting('twitter_handle', '');
    if ($twitterHandle) echo '<meta name="twitter:site" content="' . h($twitterHandle) . '">' . "\n";
}

function render_article_jsonld(array $post): void {
    $data = [
        '@context'         => 'https://schema.org',
        '@type'            => 'BlogPosting',
        'headline'         => $post['title'],
        'description'      => $post['meta_description'] ?: excerpt($post['content']),
        'url'              => post_url($post),
        'datePublished'    => date('c', strtotime($post['published_at'])),
        'dateModified'     => date('c', strtotime($post['updated_at'] ?? $post['published_at'])),
        'author'           => [
            '@type' => 'Person',
            'name'  => $post['author_name'] ?? SITE_NAME,
        ],
        'publisher'        => [
            '@type' => 'Organization',
            'name'  => SITE_NAME,
            'url'   => SITE_URL,
        ],
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id'   => post_url($post),
        ],
    ];
    if (!empty($post['featured_image'])) {
        $data['image'] = SITE_URL . '/' . $post['featured_image'];
    }
    echo '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}

function render_breadcrumb_jsonld(array $items): void {
    $list = [];
    foreach ($items as $i => $item) {
        $list[] = [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => $item['name'],
            'item'     => $item['url'],
        ];
    }
    $data = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $list,
    ];
    echo '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}

function current_url(): string {
    $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/');
}
