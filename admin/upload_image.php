<?php
require_once __DIR__ . '/auth_check.php'; 
header('Content-Type: application/json');

$uploadType = isset($_POST['upload_type']) ? $_POST['upload_type'] : 'products';

$uploadDirs = [
    'parent-image'  => 'uploads/parent-image/',
    'variant-image' => 'uploads/products/',
    'products'      => 'uploads/products/',
    'project-image' => 'uploads/projects/',
    'banner-image'  => 'uploads/banners/'
];

$uploadDir = isset($uploadDirs[$uploadType]) ? $uploadDirs[$uploadType] : 'uploads/products/';

$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp'
];
$maxFileSize = 5 * 1024 * 1024; // 5MB

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['image'];

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload error occurred']);
    exit;
}

if ($file['size'] > $maxFileSize) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB']);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!isset($allowedTypes[$mime])) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed']);
    exit;
}

$extension = $allowedTypes[$mime];

$prefix = ($uploadType === 'parent-image') ? 'parent_' : (($uploadType === 'project-image') ? 'project_' : (($uploadType === 'banner-image') ? 'banner_' : 'product_'));
$filename = uniqid($prefix, true) . '.' . $extension;
$filepath = $uploadDir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}

echo json_encode([
    'success'  => true,
    'message'  => 'Image uploaded successfully',
    'filename' => $filename,
    'filepath' => $filepath, 
    'path'     => $filepath 
]);