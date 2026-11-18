<?php
/**
 * Global site configuration.
 */

// Error display - OFF for production, errors are logged instead.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Base URL - update this to your actual install path/domain.
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    // Strip known sub-folders (admin, api, auth) so BASE_URL always points to site root.
    $scriptDir = preg_replace('#/(admin|api|auth)(/.*)?$#', '', $scriptDir);
    define('BASE_URL', $protocol . $host . $scriptDir);
}

define('SITE_ROOT', dirname(__DIR__));
define('UPLOAD_PATH', SITE_ROOT . '/assets/uploads');
define('UPLOAD_URL', BASE_URL . '/assets/uploads');

define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'webp']);
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 20);

define('AVAILABLE_SIZES', ['XS', 'S', 'M', 'L', 'XL', 'XXL']);

// Secure session cookie params - must be set before session_start().
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// CSRF token helper bootstrap
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
