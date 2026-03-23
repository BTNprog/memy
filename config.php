<?php
// config.php
// Update these values for your environment.

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'memy_shop');

// Local directory where product images are stored.
define('IMAGE_DIR', __DIR__ . '/images');

// Connect to MySQL
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// If the database does not exist yet (e.g., first run), show a friendly message.
if ($mysqli->connect_errno) {
    if ($mysqli->connect_errno === 1049) { // Unknown database
        die('Database "' . DB_NAME . '" not found. Please run <a href="init_db.php">init_db.php</a> to create it.');
    }
    die('Database connection failed: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

function esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return '/' . $path;
}

/**
 * Get a safe URL for a product image stored in the database.
 *
 * If the image value is already a full URL (http:// or https://) or an absolute path,
 * it is returned as-is. Otherwise it is treated as a relative path under the images directory.
 */
function productImageUrl(string $image): string
{
    $image = trim($image);
    if ($image === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $image) || strpos($image, '/') === 0) {
        return $image;
    }

    return url(ltrim($image, '/'));
}

/**
 * Download an image from a URL and save it under the local images directory.
 *
 * Returns the stored relative image path (e.g. "images/abc.jpg") on success, or false on failure.
 */
function downloadImageFromUrl(string $url)
{
    $url = trim($url);
    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        return false;
    }

    $data = @file_get_contents($url);
    if ($data === false) {
        return false;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($data);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        return false;
    }

    if (!is_dir(IMAGE_DIR) && !@mkdir(IMAGE_DIR, 0755, true) && !is_dir(IMAGE_DIR)) {
        return false;
    }

    $filename = uniqid('img_', true) . '.' . $allowed[$mime];
    $path = IMAGE_DIR . DIRECTORY_SEPARATOR . $filename;

    if (@file_put_contents($path, $data) === false) {
        return false;
    }

    return 'images/' . $filename;
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
    ];
}

function isAdmin(): bool
{
    $user = currentUser();
    if (!$user) {
        return false;
    }

    // Change this check to match your desired admin account(s).
    return $user['email'] === 'user@example.com';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void
{
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

function requireGuest(): void
{
    if (isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}
