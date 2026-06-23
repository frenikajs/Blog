<?php
/**
 * install.php  —  Run once to create the database tables and admin account.
 * IMPORTANT: Delete or restrict this file after setup.
 */

define('INSTALLING', true);
require_once __DIR__ . '/config.php';

$step    = $_POST['step'] ?? 'form';
$errors  = [];
$success = false;

if ($step === 'install' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminPassword = trim($_POST['admin_password'] ?? '');
    $adminName     = trim($_POST['admin_name'] ?? 'Admin');
    $siteName      = trim($_POST['site_name'] ?? SITE_NAME);
    $siteDesc      = trim($_POST['site_description'] ?? '');

    if (strlen($adminPassword) < 8) $errors[] = 'Password must be at least 8 characters.';

    if (empty($errors)) {
        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME),
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Create tables
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS admin_user (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    display_name VARCHAR(100) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS settings (
                    name VARCHAR(100) PRIMARY KEY,
                    value TEXT,
                    password_hash VARCHAR(255) NULL DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS categories (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    slug VARCHAR(100) NOT NULL UNIQUE,
                    description TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS tags (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    slug VARCHAR(100) NOT NULL UNIQUE,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS posts (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    author_id INT UNSIGNED,
                    title VARCHAR(500) NOT NULL,
                    slug VARCHAR(500) NOT NULL UNIQUE,
                    content LONGTEXT,
                    excerpt TEXT,
                    featured_image VARCHAR(500),
                    status ENUM('draft','published') DEFAULT 'draft',
                    meta_title VARCHAR(200),
                    meta_description VARCHAR(500),
                    published_at DATETIME,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_status_published (status, published_at),
                    INDEX idx_slug (slug(191))
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS post_categories (
                    post_id INT UNSIGNED NOT NULL,
                    category_id INT UNSIGNED NOT NULL,
                    PRIMARY KEY (post_id, category_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS post_tags (
                    post_id INT UNSIGNED NOT NULL,
                    tag_id INT UNSIGNED NOT NULL,
                    PRIMARY KEY (post_id, tag_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS media (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    filename VARCHAR(500) NOT NULL,
                    original_name VARCHAR(500),
                    mime_type VARCHAR(100),
                    file_size INT UNSIGNED,
                    path VARCHAR(500) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // Insert admin user
            $pdo->exec("DELETE FROM admin_user WHERE id = 1");
            $stmt = $pdo->prepare("INSERT INTO admin_user (id, display_name) VALUES (1, ?)");
            $stmt->execute([$adminName]);

            // Insert settings
            $hash = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $inserts = [
                ['admin_password', '', $hash],
                ['site_name', $siteName, null],
                ['site_description', $siteDesc, null],
                ['tagline', '', null],
                ['twitter_handle', '', null],
                ['default_og_image', '', null],
                ['google_analytics', '', null],
            ];
            $stmt = $pdo->prepare("INSERT INTO settings (name, value, password_hash) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), password_hash = VALUES(password_hash)");
            foreach ($inserts as $row) $stmt->execute($row);

            // Insert default category
            $pdo->exec("INSERT IGNORE INTO categories (name, slug) VALUES ('General', 'general')");

            $success = true;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blog Installation</title>
<style>
body { font-family: system-ui, sans-serif; background: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
.card { background: #fff; padding: 2rem 2.5rem; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,.12); max-width: 480px; width: 100%; }
h1 { font-size: 1.5rem; margin-bottom: .25rem; }
p.sub { color: #666; font-size: .875rem; margin-bottom: 1.5rem; }
label { display: block; font-size: .875rem; font-weight: 600; margin-bottom: .35rem; color: #333; }
input[type=text], input[type=password], textarea {
  width: 100%; padding: .55rem .75rem; border: 1px solid #ccc; border-radius: 5px; font-size: .95rem; margin-bottom: 1rem; box-sizing: border-box;
}
input:focus { outline: 2px solid #1a6bcc; border-color: transparent; }
button { width: 100%; padding: .7rem; background: #1a6bcc; color: #fff; border: none; border-radius: 5px; font-size: 1rem; font-weight: 600; cursor: pointer; }
button:hover { background: #145299; }
.error { background: #f8d7da; color: #721c24; padding: .65rem .9rem; border-radius: 5px; margin-bottom: 1rem; font-size: .875rem; }
.success { background: #d4edda; color: #155724; padding: .65rem .9rem; border-radius: 5px; margin-bottom: 1rem; font-size: .875rem; }
.warning { background: #fff3cd; color: #856404; padding: .65rem .9rem; border-radius: 5px; margin-top: 1rem; font-size: .8rem; }
a { color: #1a6bcc; }
</style>
</head>
<body>
<div class="card">
  <h1>Blog Installation</h1>
  <p class="sub">Set up your database and admin account</p>

  <?php foreach ($errors as $e): ?>
    <div class="error"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <?php if ($success): ?>
    <div class="success">
      <strong>Installation complete!</strong><br>
      Your blog is ready. <a href="<?= SITE_URL ?>/admin/login.php">Go to admin login &rarr;</a><br><br>
      <strong>Important:</strong> Restrict access to <code>install.php</code> in your <code>.htaccess</code> or delete it.
    </div>
  <?php else: ?>
  <form method="post">
    <input type="hidden" name="step" value="install">

    <label>Site Name</label>
    <input type="text" name="site_name" value="<?= htmlspecialchars($_POST['site_name'] ?? SITE_NAME) ?>" required>

    <label>Site Description</label>
    <input type="text" name="site_description" value="<?= htmlspecialchars($_POST['site_description'] ?? '') ?>" placeholder="A brief description of your blog">

    <label>Your Display Name</label>
    <input type="text" name="admin_name" value="<?= htmlspecialchars($_POST['admin_name'] ?? 'Admin') ?>" required>

    <label>Admin Password</label>
    <input type="password" name="admin_password" required placeholder="At least 8 characters">

    <button type="submit">Install Blog</button>
  </form>
  <div class="warning">
    Make sure you have edited <code>config.php</code> with your database credentials before running this installer.
  </div>
  <?php endif; ?>
</div>
</body>
</html>
