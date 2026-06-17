<?php
// Database configuration — credentials loaded from environment variables.
// Set these in Hostinger's environment config or a server-level SetEnv in .htaccess (not committed to git).
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'u742028483_admin');
define('DB_PASS', getenv('DB_PASS') ?: '');  // Set DB_PASS in server environment — never hardcode here
define('DB_NAME', getenv('DB_NAME') ?: 'u742028483_greenwood');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

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