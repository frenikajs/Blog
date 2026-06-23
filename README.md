# Blog CMS

A lightweight, SEO-optimised blog CMS built with PHP + MySQL. Works on any shared hosting (IONOS, cPanel, etc.) with PHP 8.0+ and MySQL 5.7+.

## Features

- Clean blog with categories, tags, pagination, search
- Rich WYSIWYG editor (TinyMCE) in admin
- Per-post SEO: meta title, meta description, canonical URL
- Open Graph + Twitter Card meta tags
- Schema.org `BlogPosting` JSON-LD structured data
- Auto XML sitemap at `/sitemap.xml`
- RSS feed at `/feed`
- Google Analytics support
- Image uploads (featured images + media library)
- Clean URLs via `.htaccess` (`/my-post-slug`)
- CSRF protection, bcrypt password hashing, secure session cookies
- Mobile-responsive front-end and admin panel

## Deployment

### 1. Upload files
Upload all files to your IONOS public_html (or subdirectory).

### 2. Create database
In IONOS control panel, go to MySQL and create a new database. Note the host, name, user, and password.

### 3. Edit config.php
```php
define('DB_HOST',  'localhost');
define('DB_NAME',  'your_db');
define('DB_USER',  'your_user');
define('DB_PASS',  'your_password');
define('SITE_URL', 'https://yourdomain.com');
define('SITE_NAME', 'My Blog');
define('SECRET_KEY', 'some-long-random-string');
```

### 4. Run installer
Visit `https://yourdomain.com/install.php` and fill in the form.
**Delete or restrict install.php after setup.**

### 5. Log in
Go to `https://yourdomain.com/admin/` and log in with the password you set.

### 6. Submit sitemap to search engines
- Google Search Console: submit `https://yourdomain.com/sitemap.xml`
- Bing Webmaster Tools: same sitemap URL

## URL Structure

| URL | Description |
|-----|-------------|
| `/` | Homepage (post list) |
| `/my-post-slug` | Single post |
| `/category/general` | Category archive |
| `/tag/php` | Tag archive |
| `/search?q=term` | Search results |
| `/feed` | RSS feed |
| `/sitemap.xml` | XML sitemap |
| `/admin/` | Admin dashboard |

## Requirements

- PHP 8.0+ with PDO, pdo_mysql, fileinfo, mbstring
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite enabled (standard on IONOS shared hosting)
