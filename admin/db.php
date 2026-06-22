<?php
// ── Database configuration ───────────────────────────────────────────────
// Credentials load from a PHP config file kept OUTSIDE the web root (so it is
// never web-served or committed to git), then fall back to environment
// variables. Create the file — see gw-db-config.example.php for the format —
// at:  <one directory above the web root>/gw-db-config.php
// then remove the old "SetEnv DB_*" lines from .htaccess.
$__gwConfigFile = dirname(__DIR__, 2) . '/gw-db-config.php';
$__gwCfg = is_readable($__gwConfigFile) ? require $__gwConfigFile : [];
if (!is_array($__gwCfg)) { $__gwCfg = []; }

define('DB_HOST', $__gwCfg['host'] ?? (getenv('DB_HOST') ?: 'localhost'));
define('DB_USER', $__gwCfg['user'] ?? (getenv('DB_USER') ?: ''));
define('DB_PASS', $__gwCfg['pass'] ?? (getenv('DB_PASS') ?: ''));
define('DB_NAME', $__gwCfg['name'] ?? (getenv('DB_NAME') ?: ''));

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// ── Clean-URL helpers ────────────────────────────────────────────────────
// Used to build /catalog/<category> and /product/<id>/<slug> links so the
// whole site emits the same canonical clean URLs.
function gw_slugify($text) {
    $text = strtolower(trim((string)$text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    if (strlen($text) > 50) {                 // keep slugs short & tidy
        $text = substr($text, 0, 50);
        $text = preg_replace('/-[^-]*$/', '', $text);
    }
    return $text !== '' ? $text : 'item';
}
function gw_product_url($id, $name) {
    return '/product/' . (int)$id . '/' . gw_slugify($name);
}
function gw_category_url($categoryName) {
    return '/catalog/' . gw_slugify($categoryName);
}

// Function to sanitize input
function sanitize($conn, $data) {
    return $conn->real_escape_string(trim($data));
}

// Function to send JSON response
function sendResponse($success, $message = '', $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
?>