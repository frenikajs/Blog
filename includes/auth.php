<?php
require_once __DIR__ . '/../config.php';

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('blog_session');
        session_set_cookie_params([
            'lifetime' => 86400 * 30,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function is_logged_in(): bool {
    start_session();
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

function login(string $password): bool {
    require_once __DIR__ . '/db.php';
    start_session();
    $stmt = db()->prepare('SELECT password_hash FROM settings WHERE name = "admin_password" LIMIT 1');
    $stmt->execute();
    $row = $stmt->fetch();
    if ($row && password_verify($password, $row['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        return true;
    }
    return false;
}

function logout(): void {
    start_session();
    $_SESSION = [];
    session_destroy();
}

function csrf_token(): string {
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool {
    start_session();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
