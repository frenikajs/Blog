<?php
// ============================================================
// config.php  —  Edit this file before running install.php
// ============================================================

define('DB_HOST',     'localhost');
define('DB_NAME',     'your_database_name');
define('DB_USER',     'your_database_user');
define('DB_PASS',     'your_database_password');
define('DB_CHARSET',  'utf8mb4');

// Full URL of your site — no trailing slash
define('SITE_URL',    'https://yourdomain.com');

// Shown in the browser title and header
define('SITE_NAME',   'My Blog');

// Used for the RSS feed
define('SITE_DESCRIPTION', 'My personal blog');

// Timezone — see https://www.php.net/manual/en/timezones.php
define('SITE_TIMEZONE', 'Europe/London');

// Admin session secret — change this to a random string
define('SECRET_KEY',  'change-this-to-a-long-random-string-abc123');

// Posts per page on the front-end
define('POSTS_PER_PAGE', 10);

// Absolute path to uploads directory
define('UPLOADS_DIR', __DIR__ . '/uploads');
define('UPLOADS_URL', SITE_URL . '/uploads');

// ---- Do not edit below this line ----
date_default_timezone_set(SITE_TIMEZONE);
define('BASE_PATH', __DIR__);
